@hasSection('description')
    <meta name="description" content="@yield('description')">
@endif
@hasSection('canonical')
    <link rel="canonical" href="@yield('canonical')">
@endif
@hasSection('robots')
    <meta name="robots" content="@yield('robots')">
@endif
