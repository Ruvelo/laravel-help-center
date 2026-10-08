{{-- The left-hand menu: categories and their articles, the current one marked. --}}
<aside class="hc-sidebar">
    <details class="hc-menu" open>
        <summary>Browse articles</summary>
        <nav class="hc-menu-links" aria-label="Articles by category">
            @forelse ($menu as $group)
                <h2><span aria-hidden="true">{{ $group->icon }}</span><a href="{{ route('help.categories.show', $group) }}"@if (($currentCategory ?? null) === $group->id && empty($currentArticle)) aria-current="page"@endif>{{ $group->name }}</a></h2>
                @if ($group->articles->isNotEmpty())
                    <ul>
                        @foreach ($group->articles as $item)
                            <li><a href="{{ route('help.articles.show', $item) }}"@if (($currentArticle ?? null) === $item->id) aria-current="page"@endif>{{ $item->title }}@if ($item->status !== 'published')<span class="hc-chip hc-chip--draft">Draft</span>@endif</a></li>
                        @endforeach
                    </ul>
                @endif
            @empty
                <p class="hc-muted">No articles yet.</p>
            @endforelse
        </nav>
    </details>
</aside>
