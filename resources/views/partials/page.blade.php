<div class="hc" id="help-center">
    <header class="hc-bar">
        <a class="hc-brand" href="{{ route('help.home') }}"><span class="hc-mark" aria-hidden="true">{{ mb_strtoupper(mb_substr(config('help-center.name'), 0, 1)) }}</span>{{ config('help-center.name') }}</a>
        @unless (View::hasSection('hero'))
            @include('help::partials.search', ['id' => 'hc-bar-search', 'placeholder' => 'Search articles…', 'value' => request()->routeIs('help.search') && is_string(request('q')) ? request('q') : ''])
        @endunless
        <nav aria-label="Help center">
            @if (config('help-center.app.url') && config('help-center.app.label'))
                <a href="{{ config('help-center.app.url') }}">{{ config('help-center.app.label') }}</a>
            @endif
            @if ($canEdit)
                @if (request()->routeIs('help.manage.*'))
                    <a href="{{ route('help.home') }}">View help center</a>
                @else
                    <a class="hc-btn hc-btn--small" href="{{ route('help.manage.articles.index') }}">Manage</a>
                @endif
            @endif
        </nav>
    </header>

    @yield('hero')

    @yield('help')

    <footer class="hc-foot">
        <div class="hc-foot-in">
            <span>© {{ date('Y') }} {{ config('help-center.name') }}</span>
            <span>Powered by <a href="https://github.com/Ruvelo/laravel-help-center">Laravel Help Center</a> by Ruvelo</span>
        </div>
    </footer>

    @include('help::partials.scripts')
</div>
