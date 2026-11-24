<?php

namespace App\Http\Controllers;

class AboutController extends Controller
{
    public function index()
    {
        return view('about.index', [
            'posts' => $this->posts(),
        ]);
    }

    public function show(string $slug)
    {
        $post = collect($this->posts())->firstWhere('slug', $slug);

        abort_unless($post !== null, 404);

        $contentView = 'about.posts.'.$slug;

        abort_unless(view()->exists($contentView), 404);

        return view('about.show', [
            'post' => $post,
            'contentView' => $contentView,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function posts(): array
    {
        return collect(config('about.posts', []))
            ->sortByDesc('date')
            ->values()
            ->all();
    }
}
