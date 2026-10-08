<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('title')@yield('title') · @endif{{ config('help-center.name') }}</title>
    @include('help::partials.meta')
    @include('help::partials.styles')
    <style>
        body.hc { margin: 0; min-height: 100vh; }
        body.hc::before { content: ""; position: fixed; inset: 0 0 auto; height: 3px; z-index: 40; background: linear-gradient(90deg, var(--help-accent), var(--help-accent-2)); }
    </style>
    @stack('help-head')
</head>
<body class="hc">
    @include('help::partials.page')
</body>
</html>
