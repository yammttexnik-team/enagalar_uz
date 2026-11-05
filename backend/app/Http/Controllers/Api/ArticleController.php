<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\JsonResponse;

class ArticleController extends Controller
{
    public function index(): JsonResponse
    {
        $page = Article::query()->published()
            ->select(['id', 'slug', 'title_uz', 'title_ru', 'excerpt_uz', 'excerpt_ru', 'published_at'])
            ->orderByDesc('published_at')->orderByDesc('id')
            ->paginate(12);

        return response()->json([
            'data' => $page->getCollection()->map(fn (Article $a) => $this->summary($a))->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $article = Article::query()->published()->where('slug', $slug)->firstOrFail();

        return response()->json(['data' => $this->summary($article) + [
            'body' => ['uz' => $article->body_uz, 'ru' => $article->body_ru],
        ]]);
    }

    private function summary(Article $a): array
    {
        return [
            'slug' => $a->slug,
            'title' => ['uz' => $a->title_uz, 'ru' => $a->title_ru],
            'excerpt' => ['uz' => $a->excerpt_uz, 'ru' => $a->excerpt_ru],
            'published_at' => $a->published_at?->toIso8601String(),
        ];
    }
}
