<nav class="hc-tabs" aria-label="Manage the help center">
    <a href="{{ route('help.manage.articles.index') }}" @if (request()->routeIs('help.manage.articles.*')) aria-current="page" @endif>Articles</a>
    <a href="{{ route('help.manage.categories.index') }}" @if (request()->routeIs('help.manage.categories.*')) aria-current="page" @endif>Categories</a>
    <a href="{{ route('help.manage.feedback') }}" @if (request()->routeIs('help.manage.feedback')) aria-current="page" @endif>Feedback</a>
</nav>
