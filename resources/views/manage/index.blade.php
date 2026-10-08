@extends(\Ruvelo\HelpCenter\HelpCenter::layout())

@section('title', 'Articles')
@section('robots', 'noindex')

@section('help')
    <div class="hc-shell hc-shell--wide">
        <main class="hc-main">
            @include('help::partials.flash')

            <div class="hc-head" style="margin-bottom: 1.25rem">
                <h1>Manage the help center</h1>
                <a class="hc-btn" href="{{ route('help.manage.articles.create') }}">New article</a>
            </div>

            @include('help::manage.tabs')

            <form class="hc-filters" method="get" action="{{ route('help.manage.articles.index') }}" role="search">
                <div>
                    <label class="hc-sr" for="hc-filter-q">Search articles</label>
                    <input id="hc-filter-q" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search titles and text…">
                </div>
                <div>
                    <label class="hc-sr" for="hc-filter-status">Status</label>
                    <select id="hc-filter-status" name="status">
                        <option value="">All statuses</option>
                        <option value="published" @selected($filters['status'] === 'published')>Published ({{ $counts['published'] ?? 0 }})</option>
                        <option value="draft" @selected($filters['status'] === 'draft')>Drafts ({{ $counts['draft'] ?? 0 }})</option>
                    </select>
                </div>
                <div>
                    <label class="hc-sr" for="hc-filter-category">Category</label>
                    <select id="hc-filter-category" name="category">
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->slug }}" @selected($filters['category'] === $category->slug)>{{ $category->icon }} {{ $category->name }}</option>
                        @endforeach
                        <option value="none" @selected($filters['category'] === 'none')>No category</option>
                    </select>
                </div>
                <button type="submit" class="hc-btn--quiet">Filter</button>
                @if (array_filter($filters))
                    <a href="{{ route('help.manage.articles.index') }}" style="font-size: .875rem; align-self: center">Clear</a>
                @endif
            </form>

            @if ($articles->isEmpty())
                <p class="hc-empty">
                    @if (array_filter($filters))
                        No articles match these filters.
                    @else
                        No articles yet. <a href="{{ route('help.manage.articles.create') }}">Write the first one.</a>
                    @endif
                </p>
            @else
                <div class="hc-table-wrap">
                    <table class="hc-table">
                        <thead>
                            <tr>
                                <th scope="col">Article</th>
                                <th scope="col">Category</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="num">Views</th>
                                <th scope="col" class="num">Helpful</th>
                                <th scope="col" class="num">Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($articles as $article)
                                <tr>
                                    <td><a href="{{ route('help.manage.articles.edit', $article) }}">{{ $article->title }}</a></td>
                                    <td>{{ $article->category ? trim($article->category->icon.' '.$article->category->name) : '—' }}</td>
                                    <td>
                                        @if ($article->isPublished())
                                            <span class="hc-chip hc-chip--good">Published</span>
                                        @else
                                            <span class="hc-chip hc-chip--draft">Draft</span>
                                        @endif
                                    </td>
                                    <td class="num">{{ number_format($article->views) }}</td>
                                    <td class="num">
                                        @if (($percent = $article->helpfulPercent()) !== null)
                                            <span class="hc-bar-meter" aria-hidden="true"><span style="width: {{ $percent }}%"></span></span>{{ $percent }}%
                                            <small class="hc-muted">({{ $article->helpful_count + $article->not_helpful_count }})</small>
                                        @else
                                            <span class="hc-muted">No votes</span>
                                        @endif
                                    </td>
                                    <td class="num hc-muted"><time datetime="{{ $article->updated_at->toIso8601String() }}" title="{{ $article->updated_at->toDayDateTimeString() }}">{{ $article->updated_at->diffForHumans() }}</time></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{ $articles->links() }}
            @endif
        </main>
    </div>
@endsection
