<dl class="space-y-3 text-sm">
    @foreach ($rows as [$question, $answer])
        <div>
            <dt class="font-medium text-gray-950 dark:text-white">{{ $question }}</dt>
            <dd class="text-gray-600 dark:text-gray-300">{{ $answer }}</dd>
        </div>
    @endforeach
</dl>
