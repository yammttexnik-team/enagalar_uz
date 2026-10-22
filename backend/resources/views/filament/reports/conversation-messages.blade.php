{{-- Shikoyat qilingan yozishma: faqat o'qish. Fayllar ko'rsatilmaydi. --}}
@php($messages = $getState() ?? collect())
<div class="space-y-2 text-sm">
    @forelse ($messages as $message)
        <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
            <div class="mb-1 flex justify-between text-xs text-gray-500">
                <span class="font-medium">{{ $message->sender?->name ?? 'Noma\'lum' }}</span>
                <span>{{ $message->created_at?->format('d.m.Y H:i') }}</span>
            </div>
            @if ($message->trashed())
                <em class="text-gray-400">Xabar o'chirilgan</em>
            @elseif ($message->type->value === 'image')
                <em>📷 Rasm</em> {{ $message->body }}
            @elseif ($message->type->value === 'voice')
                <em>🎤 Ovozli xabar</em> {{ $message->body }}
            @else
                {{ $message->body }}
            @endif
        </div>
    @empty
        <p class="text-gray-500">Xabarlar yo'q.</p>
    @endforelse
</div>
