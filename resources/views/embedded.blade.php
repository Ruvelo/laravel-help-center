{{-- Renders the help center inside the host app's layout (config: help-center.layout and help-center.section). --}}
@extends(config('help-center.layout'))

@push('help-head')
    @include('help::partials.meta')
@endpush

@section(config('help-center.section', 'content'))
    @include('help::partials.styles')
    @include('help::partials.page')
@endsection
