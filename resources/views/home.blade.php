@extends(\Ruvelo\HelpCenter\HelpCenter::layout())

@section('description', 'Answers, guides and how-tos from the '.config('help-center.name').'.')
@section('canonical', route('help.home'))

@section('hero')
    <section class="hc-hero">
        <h1>{{ config('help-center.headline', 'How can we help?') }}</h1>
        <p>Search the help center, or browse by topic below.</p>
        @include('help::partials.search', ['id' => 'hc-hero-search', 'placeholder' => 'Search for answers…', 'autofocus' => true])
        @if ($popular->isNotEmpty())
            <p class="hc-hero-hint">Popular:
                @foreach ($popular->take(3) as $article)
                    <a href="{{ route('help.articles.show', $article) }}">{{ $article->title }}</a>@if (! $loop->last), @endif
                @endforeach
            </p>
        @endif
    </section>
@endsection

@section('help')
    <main class="hc-page">
        <div style="padding-top: 1.75rem">@include('help::partials.flash')</div>

        <section class="hc-section" aria-labelledby="hc-topics" style="margin-top: 1.25rem">
            <h2 id="hc-topics">Browse by topic</h2>
            @if ($categories->isEmpty())
                <p class="hc-empty">
                    There are no articles yet.
                    @if ($canEdit)
                        <a href="{{ route('help.manage.categories.index') }}">Create a category</a>, then write the first article.
                    @else
                        Check back soon.
                    @endif
                </p>
            @else
                <ul class="hc-cards">
                    @foreach ($categories as $category)
                        <li>
                            <a class="hc-card" href="{{ route('help.categories.show', $category) }}">
                                <span class="hc-card-icon" aria-hidden="true">{{ $category->icon ?: '📘' }}</span>
                                <strong>{{ $category->name }}</strong>
                                @if ($category->description)
                                    <span class="hc-card-text">{{ $category->description }}</span>
                                @endif
                                <span class="hc-card-count">{{ $category->published_articles_count }} {{ \Illuminate\Support\Str::plural('article', $category->published_articles_count) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        @if ($popular->isNotEmpty())
            <section class="hc-section" aria-labelledby="hc-popular">
                <h2 id="hc-popular">Popular articles</h2>
                <ul class="hc-popular">
                    @foreach ($popular as $article)
                        <li><a href="{{ route('help.articles.show', $article) }}">{{ $article->title }} @if ($article->category)<small>{{ $article->category->name }}</small>@endif</a></li>
                    @endforeach
                </ul>
            </section>
        @endif

        @include('help::partials.contact')
    </main>
@endsection
