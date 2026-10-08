@extends(\Ruvelo\HelpCenter\HelpCenter::layout())

@section('title', $query === '' ? 'Search' : 'Search: '.$query)
@section('robots', 'noindex')

@section('help')
    <div class="hc-shell">
        @include('help::partials.menu')

        <main class="hc-main">
            <ol class="hc-crumbs" aria-label="Breadcrumbs">
                <li><a href="{{ route('help.home') }}">{{ config('help-center.name') }}</a></li>
                <li aria-current="page">Search</li>
            </ol>

            <div class="hc-head">
                <h1>{{ $query === '' ? 'Search' : 'Results for “'.$query.'”' }}</h1>
                @if ($articles !== null && $articles->total() > 0)
                    <span class="hc-muted">{{ $articles->total() }} {{ \Illuminate\Support\Str::plural('article', $articles->total()) }}</span>
                @endif
            </div>

            <div class="hc-results">
                @if ($articles === null)
                    <p class="hc-muted">Type a question or a few words in the search box above.</p>
                @elseif ($articles->isEmpty())
                    <p class="hc-empty" style="margin-top: 2rem">No articles match “{{ $query }}”. Try fewer or different words, or browse the topics on the left.</p>
                @else
                    <ul class="hc-list">
                        @foreach ($articles as $article)
                            <li>
                                <a href="{{ route('help.articles.show', $article) }}">{!! \Ruvelo\HelpCenter\Support\Snippet::highlight($article->title, $query) !!}</a>
                                @if ($article->category)<span class="hc-chip">{{ $article->category->name }}</span>@endif
                                @unless ($article->isPublished())<span class="hc-chip hc-chip--draft">Draft</span>@endunless
                                <span class="hc-list-text">{!! \Ruvelo\HelpCenter\Support\Snippet::for($article, $query) !!}</span>
                            </li>
                        @endforeach
                    </ul>

                    {{ $articles->links() }}
                @endif
            </div>

            @include('help::partials.contact')
        </main>
    </div>
@endsection
