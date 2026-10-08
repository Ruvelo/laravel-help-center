@extends(\Ruvelo\HelpCenter\HelpCenter::layout())

@section('title', $category->name)
@section('description', $category->description ?: 'Articles about '.mb_strtolower($category->name).' in the '.config('help-center.name').'.')
@section('canonical', route('help.categories.show', $category))

@section('help')
    <div class="hc-shell">
        @include('help::partials.menu', ['currentCategory' => $category->id])

        <main class="hc-main">
            @include('help::partials.flash')

            <ol class="hc-crumbs" aria-label="Breadcrumbs">
                <li><a href="{{ route('help.home') }}">{{ config('help-center.name') }}</a></li>
                <li aria-current="page">{{ $category->name }}</li>
            </ol>

            <span class="hc-title-icon" aria-hidden="true">{{ $category->icon ?: '📘' }}</span>
            <div class="hc-head">
                <h1>{{ $category->name }}</h1>
                @if ($canEdit)
                    <div class="hc-actions">
                        <a href="{{ route('help.manage.articles.create', ['category' => $category->slug]) }}">New article here</a>
                    </div>
                @endif
            </div>
            @if ($category->description)
                <p class="hc-lede">{{ $category->description }}</p>
            @endif

            @if ($articles->isEmpty())
                <p class="hc-empty" style="margin-top: 2rem">No articles here yet.</p>
            @else
                <ul class="hc-list" style="margin-top: 2rem">
                    @foreach ($articles as $article)
                        <li>
                            <a href="{{ route('help.articles.show', $article) }}">{{ $article->title }}</a>
                            @unless ($article->isPublished())<span class="hc-chip hc-chip--draft">Draft</span>@endunless
                            <span class="hc-list-text">{{ $article->summary() }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            @include('help::partials.contact')
        </main>
    </div>
@endsection
