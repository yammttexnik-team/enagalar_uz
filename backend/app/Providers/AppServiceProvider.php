<?php

namespace App\Providers;

use App\Models\Conversation;
use App\Models\NannyProfile;
use App\Models\Vacancy;
use App\Services\Matching\ClaudeMatchRanker;
use App\Services\Matching\MatchRanker;
use App\Services\Matching\RuleBasedMatchRanker;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Kalit bor va yoqilgan bo'lsa Claude, aks holda qoidalarga asoslangan saralash.
        $this->app->bind(MatchRanker::class, fn () => config('services.anthropic.enabled') && config('services.anthropic.key')
            ? new ClaudeMatchRanker
            : new RuleBasedMatchRanker);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Sevimlilar va shikoyatlarda to'liq klass nomi o'rniga qisqa tur saqlanadi.
        Relation::morphMap(['nanny' => NannyProfile::class, 'vacancy' => Vacancy::class, 'conversation' => Conversation::class]);
    }
}
