@extends(\Ruvelo\HelpCenter\HelpCenter::layout())

@section('title', 'Categories')
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

            <div style="max-width: 52rem">
                @forelse ($categories as $category)
                    <div class="hc-cat-row">
                        <div class="hc-cat-move">
                            <form method="post" action="{{ route('help.manage.categories.move', $category) }}">
                                @csrf
                                <input type="hidden" name="direction" value="up">
                                <button type="submit" class="hc-btn--quiet hc-btn--small" @disabled($loop->first) aria-label="Move {{ $category->name }} up">↑</button>
                            </form>
                            <form method="post" action="{{ route('help.manage.categories.move', $category) }}">
                                @csrf
                                <input type="hidden" name="direction" value="down">
                                <button type="submit" class="hc-btn--quiet hc-btn--small" @disabled($loop->last) aria-label="Move {{ $category->name }} down">↓</button>
                            </form>
                        </div>
                        <form class="hc-cat-edit" method="post" action="{{ route('help.manage.categories.update', $category) }}" id="hc-cat-{{ $category->id }}">
                            @csrf
                            @method('put')
                            <label class="hc-sr" for="hc-cat-{{ $category->id }}-icon">Icon for {{ $category->name }}</label>
                            <input id="hc-cat-{{ $category->id }}-icon" type="text" name="icon" value="{{ $category->icon }}" maxlength="16" placeholder="📘" style="text-align: center">
                            <label class="hc-sr" for="hc-cat-{{ $category->id }}-name">Name</label>
                            <input id="hc-cat-{{ $category->id }}-name" type="text" name="name" value="{{ $category->name }}" required maxlength="120">
                            <label class="hc-sr" for="hc-cat-{{ $category->id }}-description">Description of {{ $category->name }}</label>
                            <textarea id="hc-cat-{{ $category->id }}-description" name="description" maxlength="300" placeholder="One line about what's in here">{{ $category->description }}</textarea>
                        </form>
                        <div class="hc-cat-tools">
                            <button type="submit" form="hc-cat-{{ $category->id }}" class="hc-btn--small">Save</button>
                            <a href="{{ route('help.categories.show', $category) }}">{{ $category->articles_count }} {{ \Illuminate\Support\Str::plural('article', $category->articles_count) }}</a>
                            <a href="{{ route('help.manage.articles.create', ['category' => $category->slug]) }}">Add an article</a>
                            @if ($category->articles_count === 0)
                                <form method="post" action="{{ route('help.manage.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?')">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="hc-btn--danger hc-btn--small">Delete</button>
                                </form>
                            @else
                                <span class="hc-muted">Move or delete its articles to delete it.</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="hc-empty">No categories yet. Add the first one below: readers browse the help center by category.</p>
                @endforelse

                <h2 style="font-size: 1.1rem; margin: 2.5rem 0 0">Add a category</h2>
                <form method="post" action="{{ route('help.manage.categories.store') }}">
                    @csrf
                    <div class="hc-fields" style="grid-template-columns: 6rem minmax(0, 1fr)">
                        <div>
                            <label for="hc-new-icon">Icon</label>
                            <input id="hc-new-icon" type="text" name="icon" value="{{ old('icon') }}" maxlength="16" placeholder="💳" style="text-align: center">
                        </div>
                        <div>
                            <label for="hc-new-name">Name</label>
                            <input id="hc-new-name" type="text" name="name" value="{{ old('name') }}" required maxlength="120" placeholder="Billing & invoices">
                        </div>
                    </div>
                    @error('name') <p class="hc-error">{{ $message }}</p> @enderror
                    <label for="hc-new-description">Description <span class="hc-muted">(optional)</span></label>
                    <textarea id="hc-new-description" name="description" maxlength="300" style="min-height: 4rem; font-family: var(--help-sans)" placeholder="Plans, invoices, receipts and VAT">{{ old('description') }}</textarea>
                    <div class="hc-row">
                        <button type="submit">Add category</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
@endsection
