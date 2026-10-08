@php
    $contactUrl = config('help-center.contact.url') ?: (config('help-center.contact.email') ? 'mailto:'.config('help-center.contact.email') : null);
@endphp
@if ($contactUrl)
    <aside class="hc-contact" aria-labelledby="hc-contact-title">
        <div>
            <h2 id="hc-contact-title">Still need help?</h2>
            <p>{{ config('help-center.contact.text') }}</p>
        </div>
        <a class="hc-btn" href="{{ $contactUrl }}">{{ config('help-center.contact.label', 'Contact us') }}</a>
    </aside>
@endif
