<?php

namespace App\Providers;

use App\Models\Conversation;
use App\Models\NannyProfile;
use App\Models\Vacancy;
use App\Services\Matching\ClaudeMatchRanker;
use App\Services\Matching\MatchRanker;
use App\Services\Matching\RuleBasedMatchRanker;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        $this->configureRateLimiting();
    }

    /** Nomlangan chegaralar (routes/api.php): kirgan foydalanuvchi bo'yicha, aks holda IP bo'yicha. */
    private function configureRateLimiting(): void
    {
        $by = fn (Request $r) => $r->user()?->id ?: $r->ip();

        RateLimiter::for('public-read', fn (Request $r) => Limit::perMinute(120)->by('public:'.$r->ip()));
        RateLimiter::for('conversation-start', fn (Request $r) => Limit::perMinute(20)->by('conv-start:'.$by($r)));
        RateLimiter::for('conversation-report', fn (Request $r) => Limit::perMinute(10)->by('conv-report:'.$by($r)));
        RateLimiter::for('conversation-block', fn (Request $r) => Limit::perMinute(20)->by('conv-block:'.$by($r)));
        RateLimiter::for('favorites', fn (Request $r) => Limit::perMinute(60)->by('fav:'.$by($r)));
        RateLimiter::for('applications', fn (Request $r) => Limit::perMinute(30)->by('apps:'.$by($r)));
    }
}
