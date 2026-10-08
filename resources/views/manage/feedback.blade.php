@extends(\Ruvelo\HelpCenter\HelpCenter::layout())

@section('title', 'Feedback')
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

            @if ($leastHelpful->isNotEmpty() && ! $article)
                <h2 style="font-size: 1.1rem; margin: 0 0 .75rem">Articles with the most “No” votes</h2>
                <div class="hc-table-wrap" style="margin-bottom: 2.5rem">
                    <table class="hc-table">
                        <thead>
                            <tr>
                                <th scope="col">Article</th>
                                <th scope="col" class="num">Yes</th>
                                <th scope="col" class="num">No</th>
                                <th scope="col" class="num">Helpful</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($leastHelpful as $item)
                                <tr>
                                    <td><a href="{{ route('help.manage.feedback', ['article' => $item->slug]) }}">{{ $item->title }}</a></td>
                                    <td class="num">{{ $item->helpful_count }}</td>
                                    <td class="num">{{ $item->not_helpful_count }}</td>
                                    <td class="num">{{ $item->helpfulPercent() }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="hc-head">
                <h2 style="font-size: 1.1rem; margin: 0">
                    @if ($article)
                        What readers said about “{{ $article->title }}”
                    @else
                        Recent comments from readers who answered “No”
                    @endif
                </h2>
                @if ($article)
                    <div class="hc-actions">
                        <a href="{{ route('help.manage.articles.edit', $article) }}">Edit article</a>
                        <a href="{{ route('help.manage.feedback') }}">All feedback</a>
                    </div>
                @endif
            </div>

            @if ($comments->isEmpty())
                <p class="hc-empty" style="margin-top: 1rem">No comments yet. When a reader answers “No” and says why, it shows up here.</p>
            @else
                <div style="max-width: 52rem">
                    @foreach ($comments as $feedback)
                        <div class="hc-comment">
                            <div class="hc-head">
                                <a href="{{ route('help.manage.articles.edit', $feedback->article) }}" style="font-weight: 500">{{ $feedback->article->title }}</a>
                                <small class="hc-muted"><time datetime="{{ $feedback->created_at->toIso8601String() }}">{{ $feedback->created_at->diffForHumans() }}</time></small>
                            </div>
                            <blockquote>{{ $feedback->comment }}</blockquote>
                        </div>
                    @endforeach
                </div>

                {{ $comments->links() }}
            @endif
        </main>
    </div>
@endsection
