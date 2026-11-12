{{-- FILE LOCATION: resources/views/proposals/text-only.blade.php  (NEW FILE) --}}
{{--
    Only the extracted proposal text, for the shared proposal container.
    With $watermarkName (faculty readers) it keeps the reader's protections:
    watermark, copy/drag blocking and no printing.
--}}
@php($protected = filled($watermarkName ?? null))
<section class="{{ $protected ? 'proposal-reader-print-content ' : '' }}relative isolate overflow-hidden">
    @if ($protected)
        <div class="proposal-reader-watermark" aria-hidden="true">
            @for ($watermarkIndex = 0; $watermarkIndex < 18; $watermarkIndex++)
                <span>{{ $watermarkName }} · {{ $watermarkTime }}</span>
            @endfor
        </div>
    @endif

    <div class="relative z-10">
        @if (filled($proposal->analysis?->extracted_text))
            <div class="{{ $protected ? 'proposal-reader-text' : 'whitespace-pre-wrap break-words' }} rounded-md border border-gray-200 bg-gray-50 p-5 text-sm leading-7 text-gray-800" @if ($protected) data-no-copy @endif>{{ $proposal->analysis->extracted_text }}</div>
        @else
            <p class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">{{ __('The text of this proposal is not available yet. The student can retry extraction.') }}</p>
        @endif
    </div>
</section>
@if ($protected)
    <p class="proposal-print-notice" aria-hidden="true">{{ __('Printing is disabled for proposals.') }}</p>
@endif