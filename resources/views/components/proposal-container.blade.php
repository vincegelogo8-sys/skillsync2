{{-- FILE LOCATION: resources/views/components/proposal-container.blade.php  (NEW FILE; if you already added it, REPLACE it: the heading text changed) --}}
{{--
    One shared container for every "View proposal" button.

    Links marked with data-proposal-view open here instead of navigating away.
    resources/js/proposal-container.js fetches the link's own URL, so the existing
    routes, authorization, watermark and no-store headers are still what decide
    what the person sees. The body is emptied again when the container closes.
--}}
<dialog id="proposal-container" class="proposal-container" aria-labelledby="proposal-container-title" data-proposal-container>
    <div class="proposal-container-header">
        <h2 id="proposal-container-title">{{ __('Extracted Proposal Text') }}</h2>
        <button type="button" class="button button-secondary" data-close-modal aria-label="{{ __('Close proposal') }}">{{ __('Close') }}</button>
    </div>
    <div class="proposal-container-body" data-proposal-body tabindex="0"></div>
</dialog>