<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Suhbat kanali: faqat ishtirokchilar (xabarlar, o'qildi, "yozmoqda" whisper).
Broadcast::channel('conversation.{id}', function (User $user, int $id) {
    return $user->conversations()->whereKey($id)->exists();
});

// Shaxsiy kanal: faqat o'zi (suhbatlar ro'yxati yangilanishi).
Broadcast::channel('user.{id}', function (User $user, int $id) {
    return $user->id === $id;
});

// Onlayn holat (presence): har qanday kirgan foydalanuvchi, faqat id qaytariladi.
Broadcast::channel('online', function (User $user) {
    return ['id' => $user->id];
});
