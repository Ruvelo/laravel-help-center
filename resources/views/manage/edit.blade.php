@extends(\Ruvelo\HelpCenter\HelpCenter::layout())

@section('title', $article->exists ? 'Editing '.$article->title : 'New article')
@section('robots', 'noindex')

@section('help')
    <div class="hc-shell hc-shell--wide">
        <main class="hc-main">
            @include('help::partials.flash')

            <ol class="hc-crumbs" aria-label="Breadcrumbs">
                <li><a href="{{ route('help.manage.articles.index') }}">Articles</a></li>
                <li aria-current="page">{{ $article->exists ? $article->getOriginal('title') : 'New article' }}</li>
            </ol>

            <div class="hc-head" style="margin-bottom: 1.5rem">
                <h1>{{ $article->exists ? 'Edit article' : 'New article' }}</h1>
                @if ($article->exists)
                    <div class="hc-actions">
                        <a href="{{ route('help.articles.show', $article) }}">{{ $article->getOriginal('status') === 'published' ? 'View article' : 'Preview as reader' }}</a>
                    </div>
                @endif
            </div>

            <form method="post" action="{{ $article->exists ? route('help.manage.articles.update', $article) : route('help.manage.articles.store') }}" class="hc-editor">
                @csrf
                @if ($article->exists)
                    @method('put')
                    <input type="hidden" name="version" value="{{ old('version', $version) }}">
                @endif

                <div>
                    <label for="hc-title" style="margin-top: 0">Title</label>
                    <input id="hc-title" type="text" name="title" value="{{ old('title', $article->title) }}" required maxlength="255" @unless ($article->exists) autofocus @endunless>
                    @if ($article->exists)
                        <p class="hc-hint">Renaming keeps the address: {{ route('help.articles.show', $article) }}</p>
                    @endif
                    @error('title') <p class="hc-error">{{ $message }}</p> @enderror

                    <div class="hc-fields">
                        <div>
                            <label for="hc-category">Category</label>
                            <select id="hc-category" name="category_id">
                                <option value="">No category</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) old('category_id', $article->category_id) === (string) $category->id)>{{ $category->icon }} {{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id') <p class="hc-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="hc-status">Status</label>
                            <select id="hc-status" name="status">
                                <option value="draft" @selected(old('status', $article->status) === 'draft')>Draft: only editors see it</option>
                                <option value="published" @selected(old('status', $article->status) === 'published')>Published</option>
                            </select>
                            @error('status') <p class="hc-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="hc-position">Position</label>
                            <input id="hc-position" type="number" name="position" min="0" value="{{ old('position', $article->exists ? $article->position : '') }}" placeholder="Last">
                            @error('position') <p class="hc-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <label for="hc-body">Article</label>
                    <textarea id="hc-body" name="body" spellcheck="true" @if ($article->exists) autofocus @endif>{{ old('body', $article->body) }}</textarea>
                    @error('body') <p class="hc-error">{{ $message }}</p> @enderror

                    <details class="hc-help">
                        <summary>Formatting help</summary>
                        <p>
                            Markdown, GitHub-style: <code>## Heading</code>, <code>**bold**</code>, <code>1. steps</code>,
                            <code>- lists</code>, tables and fenced code. Start a paragraph with <code>&gt;</code> for a callout.
                            Link to another article with <code>[[Article title]]</code>, <code>[[Article title|link text]]</code>
                            or <code>[[Article title#Section]]</code>. Links to articles that aren't published yet show in red to editors
                            and as plain text to readers. Headings fill the "On this page" outline.
                        </p>
                    </details>

                    <label for="hc-excerpt">Excerpt <span class="hc-muted">(optional)</span></label>
                    <textarea id="hc-excerpt" name="excerpt" maxlength="500" placeholder="Shown in search results, lists and search engines. Leave empty to use the start of the article.">{{ old('excerpt', $article->excerpt) }}</textarea>
                    @error('excerpt') <p class="hc-error">{{ $message }}</p> @enderror

                    <div class="hc-row">
                        <button type="submit" name="action" value="save">{{ $article->exists ? 'Save changes' : 'Save article' }}</button>
                        <button type="submit" name="action" value="preview" class="hc-btn--quiet" data-help-preview-button>Preview</button>
                        @if ($article->exists)
                            <span class="hc-stats">
                                <span class="hc-chip">{{ number_format($article->views) }} {{ \Illuminate\Support\Str::plural('view', $article->views) }}</span>
                                @if (($percent = $article->helpfulPercent()) !== null)
                                    <a class="hc-chip {{ $percent >= 70 ? 'hc-chip--good' : 'hc-chip--bad' }}" href="{{ route('help.manage.feedback', ['article' => $article->slug]) }}">{{ $percent }}% found it helpful</a>
                                @endif
                            </span>
                        @endif
                    </div>
                </div>

                <section class="hc-editor-preview" aria-label="Preview" aria-live="polite">
                    <p class="hc-muted" style="margin-top: 0"><small>Preview</small></p>
                    <div class="hc-prose" id="hc-preview">{!! $preview !!}</div>
                </section>
            </form>

            @if ($article->exists)
                <form method="post" action="{{ route('help.manage.articles.destroy', $article) }}" onsubmit="return confirm('Delete this article and its feedback? This can\'t be undone.')" class="hc-row" style="margin-top: 3rem">
                    @csrf
                    @method('delete')
                    <button type="submit" class="hc-btn--danger">Delete article</button>
                </form>
            @endif
        </main>
    </div>

    <script>
        // Live preview: re-render a moment after typing stops. Without
        // JavaScript the Preview button does the same with a round trip.
        (() => {
            const form = document.querySelector('.hc-editor');
            const body = form.querySelector('textarea[name=body]');
            const pane = document.getElementById('hc-preview');
            let timer, pending;

            form.querySelector('[data-help-preview-button]').hidden = true;

            body.addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(async () => {
                    pending?.abort();
                    pending = new AbortController();
                    try {
                        const response = await fetch(@json(route('help.manage.preview')), {
                            method: 'POST',
                            signal: pending.signal,
                            headers: { 'X-CSRF-TOKEN': form.querySelector('[name=_token]').value, 'Accept': 'text/html' },
                            body: new URLSearchParams({ body: body.value }),
                        });
                        if (response.ok) pane.innerHTML = await response.text();
                    } catch (error) {
                        if (error.name !== 'AbortError') throw error;
                    }
                }, 300);
            });
        })();
    </script>
@endsection
