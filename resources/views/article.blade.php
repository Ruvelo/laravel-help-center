@extends(\Ruvelo\HelpCenter\HelpCenter::layout())

@section('title', $article->title)
@section('description', $article->summary())
@section('canonical', route('help.articles.show', $article))
@unless ($article->isPublished())
    @section('robots', 'noindex')
@endunless

@section('help')
    <div class="hc-shell">
        @include('help::partials.menu', ['currentArticle' => $article->id, 'currentCategory' => $article->category_id])

        <main class="hc-main">
            @include('help::partials.flash')

            <article>
                <ol class="hc-crumbs" aria-label="Breadcrumbs">
                    <li><a href="{{ route('help.home') }}">{{ config('help-center.name') }}</a></li>
                    @if ($article->category)
                        <li><a href="{{ route('help.categories.show', $article->category) }}">{{ $article->category->name }}</a></li>
                    @endif
                </ol>

                <div class="hc-head">
                    <h1>{{ $article->title }}</h1>
                    @if ($canEdit)
                        <div class="hc-actions">
                            <a href="{{ route('help.manage.articles.edit', $article) }}">Edit</a>
                        </div>
                    @endif
                </div>

                <p class="hc-meta">
                    @unless ($article->isPublished())<span class="hc-chip hc-chip--draft">Draft: only editors can see this</span>@endunless
                    <span>Updated <time datetime="{{ $article->updated_at->toIso8601String() }}">{{ $article->updated_at->format('j F Y') }}</time></span>
                </p>

                <div class="hc-prose">
                    @if (trim($article->body) === '')
                        <p class="hc-muted"><em>This article is empty.</em></p>
                    @else
                        {!! $html !!}
                    @endif
                </div>
            </article>

            <section class="hc-feedback" id="feedback" data-state="{{ $feedbackState }}" data-article="{{ $article->slug }}" aria-live="polite">
                <div class="hc-fb-ask">
                    <form method="post" action="{{ route('help.articles.feedback', $article) }}">
                        @csrf
                        <h2>Was this article helpful?</h2>
                        <div class="hc-fb-buttons">
                            <button type="submit" name="helpful" value="1" class="hc-btn--quiet">Yes</button>
                            <button type="submit" name="helpful" value="0" class="hc-btn--quiet">No</button>
                        </div>
                    </form>
                </div>
                <div class="hc-fb-yes">
                    <p class="hc-thanks" tabindex="-1">Thanks! Glad it helped.</p>
                </div>
                <div class="hc-fb-no">
                    <form method="post" action="{{ route('help.articles.feedback', $article) }}">
                        @csrf
                        <input type="hidden" name="helpful" value="0">
                        <h2>Sorry about that. What were you looking for?</h2>
                        <label for="hc-feedback-comment">Tell us what was missing or unclear <span class="hc-muted">(optional)</span></label>
                        <textarea id="hc-feedback-comment" name="comment" maxlength="500" rows="3" placeholder="I wanted to know how to…"></textarea>
                        @error('comment') <p class="hc-error">{{ $message }}</p> @enderror
                        <div class="hc-row">
                            <button type="submit">Send feedback</button>
                        </div>
                    </form>
                </div>
                <div class="hc-fb-done">
                    <p class="hc-thanks" tabindex="-1">Thanks for telling us. We'll use it to improve this article.</p>
                </div>
            </section>

            @if ($related->isNotEmpty())
                <aside class="hc-related" aria-labelledby="hc-related-title">
                    <h2 id="hc-related-title">Related articles</h2>
                    <ul>
                        @foreach ($related as $item)
                            <li><a href="{{ route('help.articles.show', $item) }}">{{ $item->title }}</a></li>
                        @endforeach
                    </ul>
                </aside>
            @endif

            @include('help::partials.contact')
        </main>

        @if (count($outline) > 1)
            <aside class="hc-outline" aria-label="On this page">
                <h2>On this page</h2>
                <ul>
                    @foreach ($outline as $heading)
                        <li @class(['is-sub' => $heading['level'] === 3])><a href="#{{ $heading['id'] }}">{{ $heading['text'] }}</a></li>
                    @endforeach
                </ul>
            </aside>
        @endif
    </div>
@endsection
