{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ route('help.home') }}</loc>
@if ($articles->isNotEmpty())
        <lastmod>{{ $articles->max('updated_at')?->toAtomString() }}</lastmod>
@endif
    </url>
@foreach ($categories as $category)
    <url>
        <loc>{{ route('help.categories.show', $category) }}</loc>
    </url>
@endforeach
@foreach ($articles as $article)
    <url>
        <loc>{{ route('help.articles.show', $article) }}</loc>
        <lastmod>{{ $article->updated_at->toAtomString() }}</lastmod>
    </url>
@endforeach
</urlset>
