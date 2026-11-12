<?php
// FILE LOCATION: app/Support/ProposalTextView.php  (NEW FILE)

namespace App\Support;

use App\Models\ResearchProposal;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * What the shared proposal container (resources/js/proposal-container.js) receives:
 * the extracted proposal text and nothing else. The container asks for it by sending
 * the X-Proposal-Container header to the same URL the "View proposal" button links to,
 * so each route's own authorization has already run before this is returned.
 */
final class ProposalTextView
{
    public const HEADER = 'X-Proposal-Container';

    public static function requested(Request $request): bool
    {
        return $request->hasHeader(self::HEADER);
    }

    /**
     * @param  string|null  $watermarkName  Faculty readers pass their name: the text is then
     *                                      watermarked and copy/print protected, as on the reader page.
     */
    public static function response(ResearchProposal $proposal, ?string $watermarkName = null): Response
    {
        return response()->view('proposals.text-only', [
            'proposal' => $proposal->loadMissing('analysis'),
            'watermarkName' => $watermarkName,
            'watermarkTime' => now()->format('Y-m-d H:i T'),
        ])->withHeaders([
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}