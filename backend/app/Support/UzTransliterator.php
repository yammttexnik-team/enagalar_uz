<?php

namespace App\Support;

/** O'zbek lotin yozuvini kirillga o'giradi (joy nomlari va qisqa matnlar uchun). */
class UzTransliterator
{
    private const DIGRAPHS = [
        "o'" => 'ў', "g'" => 'ғ', 'sh' => 'ш', 'ch' => 'ч',
        'yo' => 'ё', 'yu' => 'ю', 'ya' => 'я', 'ye' => 'е',
    ];

    private const LETTERS = [
        'a' => 'а', 'b' => 'б', 'd' => 'д', 'e' => 'е', 'f' => 'ф', 'g' => 'г', 'h' => 'ҳ',
        'i' => 'и', 'j' => 'ж', 'k' => 'к', 'l' => 'л', 'm' => 'м', 'n' => 'н', 'o' => 'о',
        'p' => 'п', 'q' => 'қ', 'r' => 'р', 's' => 'с', 't' => 'т', 'u' => 'у', 'v' => 'в',
        'x' => 'х', 'y' => 'й', 'z' => 'з', "'" => 'ъ',
    ];

    public static function toCyrillic(string $text): string
    {
        $text = str_replace(['ʻ', 'ʼ', '‘', '’', '`'], "'", $text);

        return preg_replace_callback("/\p{L}[\p{L}']*/u", fn ($m) => self::word($m[0]), $text);
    }

    private static function word(string $word): string
    {
        $result = '';
        $length = mb_strlen($word);

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($word, $i, 1);
            $pair = mb_strtolower(mb_substr($word, $i, 2));
            $upper = $char !== mb_strtolower($char);

            if (isset(self::DIGRAPHS[$pair])) {
                $cyr = self::DIGRAPHS[$pair];
                $i++;
            } elseif ($i === 0 && mb_strtolower($char) === 'e') {
                $cyr = 'э';
            } else {
                $cyr = self::LETTERS[mb_strtolower($char)] ?? $char;
            }

            $result .= $upper ? mb_strtoupper($cyr) : $cyr;
        }

        return $result;
    }
}
