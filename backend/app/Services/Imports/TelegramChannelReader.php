<?php

namespace App\Services\Imports;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Ochiq Telegram kanalning veb ko'rinishini (t.me/s/<kanal>) o'qiydi. Login yo'q, API yo'q — faqat ochiq sahifa.
 * So'rovlar orasida pauza qilinadi (config imports.request_delay_ms), User-Agent ochiq yoziladi.
 */
class TelegramChannelReader
{
    private const BASE = 'https://t.me';

    private bool $firstRequest = true;

    /**
     * Kanal postlari (eskisidan yangisiga). `afterId` dan katta va `max_post_age_days` dan yosh postlar,
     * ko'pi bilan `max_posts_per_run` ta.
     *
     * @return array<int, array{id: int, channel: string, url: string, posted_at: ?Carbon, text: string, edited: bool}>
     *
     * @throws TelegramUnavailable
     */
    public function fetchChannel(string $channel, ?int $afterId = null): array
    {
        $limit = max(1, (int) config('imports.max_posts_per_run'));
        $minDate = now()->subDays((int) config('imports.max_post_age_days'));

        $posts = [];
        $before = null;

        // Har sahifada ~20 post; eskiroqlariga ?before=<eng kichik id> bilan o'tiladi.
        for ($page = 0; $page < 20; $page++) {
            try {
                $html = $this->get(self::BASE.'/s/'.rawurlencode($channel), $before ? ['before' => $before] : []);
            } catch (TelegramUnavailable $e) {
                if ($before === null) {
                    throw $e;
                }
                break; // eski sahifa ochilmadi — olinganlari bilan davom etamiz
            }
            $batch = $this->parsePosts($html, $channel);
            if ($batch === []) {
                break;
            }

            $reachedEnd = false;
            foreach ($batch as $post) {
                if (($afterId !== null && $post['id'] <= $afterId) || ($post['posted_at'] && $post['posted_at']->lt($minDate))) {
                    $reachedEnd = true;

                    continue;
                }
                $posts[$post['id']] = $post;
            }

            $oldest = min(array_column($batch, 'id'));
            if ($reachedEnd || count($posts) >= $limit || $oldest <= 1 || ($before !== null && $oldest >= $before)) {
                break;
            }
            $before = $oldest;
        }

        ksort($posts);

        // Eng yangilari qoladi (limitdan oshsa).
        return array_values(array_slice($posts, -$limit, null, true));
    }

    /**
     * Bitta postning hozirgi matni. null — post aniq yo'q ("Post not found") yoki unda matn qolmagan.
     *
     * @throws TelegramUnavailable tarmoq xatosi, 5xx yoki tanib bo'lmaydigan sahifa (arxivlash uchun asos emas)
     */
    public function fetchPost(string $channel, int $id): ?string
    {
        $html = $this->get(self::BASE.'/'.rawurlencode($channel).'/'.$id, ['embed' => 1]);
        $xpath = $this->xpath($html);

        $error = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " tgme_widget_message_error ")]')->item(0);
        if ($error && str_contains(mb_strtolower($error->textContent), 'not found')) {
            return null;
        }

        $message = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " tgme_widget_message ")][@data-post]')->item(0);
        if (! $message instanceof DOMElement) {
            throw new TelegramUnavailable("Post sahifasi tanilmadi: $channel/$id");
        }

        $text = $this->messageText($xpath, $message);

        return $text === '' ? null : $text;
    }

    /**
     * t.me/s sahifasidagi postlarni ajratadi (test uchun ochiq).
     *
     * @return array<int, array{id: int, channel: string, url: string, posted_at: ?Carbon, text: string, edited: bool}>
     */
    public function parsePosts(string $html, string $channel): array
    {
        $xpath = $this->xpath($html);
        $posts = [];

        $nodes = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " tgme_widget_message ")][@data-post]');
        foreach ($nodes as $node) {
            /** @var DOMElement $node */
            if (str_contains($node->getAttribute('class'), 'service_message')) {
                continue; // "Kanal yaratildi" kabi xizmat xabarlari
            }
            if (! preg_match('~^([A-Za-z0-9_]+)/(\d+)$~', $node->getAttribute('data-post'), $m)) {
                continue;
            }

            $text = $this->messageText($xpath, $node);
            if ($text === '') {
                continue; // faqat rasm/video, matnsiz
            }

            $time = $xpath->query('.//*[contains(@class, "tgme_widget_message_date")]//time[@datetime]', $node)->item(0)
                ?? $xpath->query('.//time[@datetime]', $node)->item(0);
            $postedAt = null;
            if ($time instanceof DOMElement) {
                try {
                    $postedAt = Carbon::parse($time->getAttribute('datetime'));
                } catch (\Throwable) {
                    $postedAt = null;
                }
            }

            $meta = $xpath->query('.//*[contains(@class, "tgme_widget_message_meta")]', $node)->item(0);
            $edited = $xpath->query('.//*[contains(@class, "tgme_widget_message_edited")]', $node)->length > 0
                || ($meta && preg_match('~\bedited\b~i', $meta->textContent));

            $posts[] = [
                'id' => (int) $m[2],
                'channel' => $m[1],
                'url' => self::BASE.'/'.$m[1].'/'.$m[2],
                'posted_at' => $postedAt,
                'text' => $text,
                'edited' => (bool) $edited,
            ];
        }

        return $posts;
    }

    /** Post matni: HTML -> oddiy matn, qatorlar saqlanadi; t.me havolalari matnga qo'shiladi (kontakt uchun). */
    private function messageText(DOMXPath $xpath, DOMElement $message): string
    {
        $node = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " tgme_widget_message_text ")]', $message)->item(0);
        if (! $node) {
            return '';
        }

        $text = html_entity_decode($this->nodeText($node), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);
        $text = preg_replace("~[ \t]+~u", ' ', $text) ?? '';
        $text = preg_replace("~ *\n *~u", "\n", $text) ?? '';
        $text = preg_replace("~\n{3,}~u", "\n\n", $text) ?? '';

        return trim($text);
    }

    private function nodeText(DOMNode $node): string
    {
        $out = '';
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $out .= $child->textContent;
            } elseif ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);
                if ($tag === 'br') {
                    $out .= "\n";

                    continue;
                }
                $inner = $this->nodeText($child);
                $out .= $inner;
                if ($tag === 'a') {
                    // "Yozing" -> "Yozing (t.me/user)": egasining username'i havola ichida bo'lishi mumkin
                    $href = $child->getAttribute('href');
                    $short = null;
                    if (preg_match('~^(?:https?://)?t\.me/([A-Za-z0-9_]+)/?$~i', $href, $m)) {
                        $short = 't.me/'.$m[1];
                        $known = stripos($inner, '@'.$m[1]) !== false || stripos($inner, $short) !== false;
                    } elseif (str_starts_with($href, 'tel:')) {
                        $short = substr($href, 4);
                        $digits = fn (string $s) => preg_replace('~\D~', '', $s) ?? '';
                        $known = strlen($digits($inner)) >= 9 && substr($digits($inner), -9) === substr($digits($short), -9);
                    }
                    if ($short !== null && ! $known) {
                        $out .= ' ('.$short.')';
                    }
                }
                if (in_array($tag, ['p', 'div', 'blockquote'], true)) {
                    $out .= "\n";
                }
            }
        }

        return $out;
    }

    private function xpath(string $html): DOMXPath
    {
        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($dom);
    }

    /** @throws TelegramUnavailable */
    private function get(string $url, array $query = []): string
    {
        if (! $this->firstRequest && ($delay = (int) config('imports.request_delay_ms')) > 0) {
            usleep($delay * 1000);
        }
        $this->firstRequest = false;

        try {
            $response = Http::timeout(15)
                ->withUserAgent((string) config('imports.user_agent'))
                ->withHeaders(['Accept-Language' => 'uz,ru;q=0.8,en;q=0.5'])
                ->get($url, $query);
        } catch (ConnectionException $e) {
            throw new TelegramUnavailable('Telegram bilan aloqa yo\'q: '.$e->getMessage(), previous: $e);
        }

        if (! $response->successful()) {
            throw new TelegramUnavailable("Telegram javobi {$response->status()}: $url");
        }

        return $response->body();
    }
}
