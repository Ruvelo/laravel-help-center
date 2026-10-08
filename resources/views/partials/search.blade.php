{{-- A search form that works on its own, and suggests articles as you type when JavaScript runs. --}}
<form class="hc-search" action="{{ route('help.search') }}" method="get" role="search">
    <label class="hc-sr" for="{{ $id }}">Search the help center</label>
    <input id="{{ $id }}" type="search" name="q" value="{{ $value ?? '' }}" placeholder="{{ $placeholder }}"
        autocomplete="off" spellcheck="false" maxlength="200" @if (! empty($autofocus)) autofocus @endif
        role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="{{ $id }}-list"
        data-help-suggest="{{ route('help.search.suggest') }}">
    <div class="hc-suggest" id="{{ $id }}-list" role="listbox" aria-label="Suggested articles" hidden></div>
</form>
