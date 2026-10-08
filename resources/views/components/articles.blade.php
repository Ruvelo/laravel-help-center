{{-- Self-contained: every class starts with hcw-, and the styles are included once per page. --}}
@once
    <style>
        .hcw { --hcw-bg: #ffffff; --hcw-line: #e4e4ef; --hcw-ink: #16162a; --hcw-text-2: #4b4b63; --hcw-text-3: #74748b; --hcw-accent: #3d4eff; --hcw-accent-ink: #2b38d6; --hcw-accent-soft: #eef0ff;
            font: 15px/1.5 "Geist", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color: var(--hcw-ink); background: var(--hcw-bg); border: 1px solid var(--hcw-line); border-radius: 14px; padding: 1rem 1.1rem .6rem; box-sizing: border-box; }
        @media (prefers-color-scheme: dark) {
            .hcw { --hcw-bg: #171725; --hcw-line: #2a2a3f; --hcw-ink: #f1f1f8; --hcw-text-2: #b6b6cc; --hcw-text-3: #8787a3; --hcw-accent: #8f9bff; --hcw-accent-ink: #b3bbff; --hcw-accent-soft: #1e2150; }
        }
        .hcw *, .hcw *::before, .hcw *::after { box-sizing: border-box; }
        .hcw-head { display: flex; align-items: center; gap: .55rem; margin: 0 0 .5rem; font-size: .8rem; font-weight: 600; color: var(--hcw-accent-ink); letter-spacing: 0; }
        .hcw-icon { display: grid; place-items: center; width: 1.6rem; height: 1.6rem; border-radius: 8px; background: var(--hcw-accent-soft); font-size: .9rem; line-height: 1; flex: none; }
        .hcw-list { list-style: none; margin: 0; padding: 0; }
        .hcw-list li { border-top: 1px solid var(--hcw-line); }
        .hcw-list li:first-child { border-top: 0; }
        .hcw-link { display: block; padding: .6rem 0; color: var(--hcw-ink); text-decoration: none; }
        .hcw-link:hover .hcw-title, .hcw-link:focus-visible .hcw-title { color: var(--hcw-accent); }
        .hcw-link:focus-visible { outline: 2px solid var(--hcw-accent); outline-offset: 2px; border-radius: 6px; }
        .hcw-title { display: block; font-weight: 500; font-size: .925rem; }
        .hcw-title::after { content: " →"; color: var(--hcw-text-3); }
        .hcw-excerpt { display: block; color: var(--hcw-text-3); font-size: .8rem; margin-top: .1rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .hcw-all { display: inline-block; margin: .35rem 0 .4rem; font-size: .8rem; font-weight: 500; color: var(--hcw-accent); text-decoration: none; }
        .hcw-all:hover { text-decoration: underline; text-underline-offset: 3px; }
        .hcw-empty { margin: .25rem 0 .6rem; font-size: .85rem; color: var(--hcw-text-3); }
    </style>
@endonce
<aside {{ $attributes->class('hcw') }} aria-label="{{ $heading }}">
    <p class="hcw-head">
        <span class="hcw-icon" aria-hidden="true">{{ $categoryModel->icon ?? '📘' }}</span>
        <span>{{ $heading }}</span>
    </p>
    @if ($articles->isEmpty())
        <p class="hcw-empty">No articles yet. <a class="hcw-all" href="{{ route('help.home') }}">Visit the help center</a></p>
    @else
        <ul class="hcw-list">
            @foreach ($articles as $article)
                <li>
                    <a class="hcw-link" href="{{ route('help.articles.show', $article) }}">
                        <span class="hcw-title">{{ $article->title }}</span>
                        <span class="hcw-excerpt">{{ $article->summary(110) }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
        @if ($categoryModel)
            <a class="hcw-all" href="{{ route('help.categories.show', $categoryModel) }}">All {{ mb_strtolower($categoryModel->name) }} articles</a>
        @elseif ($search)
            <a class="hcw-all" href="{{ route('help.search', ['q' => $search, 'all' => 1]) }}">More results</a>
        @else
            <a class="hcw-all" href="{{ route('help.home') }}">Visit the help center</a>
        @endif
    @endif
</aside>
