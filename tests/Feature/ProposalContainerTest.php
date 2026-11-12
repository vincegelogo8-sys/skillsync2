<?php
// FILE LOCATION: tests/Feature/ProposalContainerTest.php  (NEW FILE, optional)

namespace Tests\Feature;

use App\Models\AdviserRequest;
use App\Models\ResearchProposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * "View proposal" buttons open the proposal in one shared container on the same page.
 * The container fetches the button's own URL, so these tests cover the two halves:
 * the buttons are marked for it, and those URLs return only the page body when asked.
 */
class ProposalContainerTest extends TestCase
{
    use RefreshDatabase;

    private const CONTAINER_HEADER = ['X-Proposal-Container' => '1'];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('proposals');
    }

    private function requestFor(): AdviserRequest
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $profile = $student->studentProfile()->create([
            'student_number' => 'S-'.$student->id,
            'course' => 'BSIT',
            'year_level' => 3,
            'section' => 'A',
        ]);
        $proposal = $profile->researchProposals()->make();
        $proposal->forceFill([
            'title' => 'Container Test Proposal',
            'file_path' => 'proposal-'.$student->id.'.pdf',
            'original_filename' => 'original-proposal.pdf',
            'file_type' => 'pdf',
            'status' => 'uploaded',
        ])->save();
        $proposal->analysis()->make()->forceFill([
            'extracted_text' => 'Extracted text shown inside the container.',
        ])->save();

        $faculty = User::factory()->create(['role' => User::ROLE_FACULTY, 'name' => 'Faculty Reader']);
        $facultyProfile = $faculty->facultyProfile()->create(['department' => 'Computing']);

        $adviserRequest = new AdviserRequest;
        $adviserRequest->forceFill([
            'student_profile_id' => $profile->id,
            'faculty_profile_id' => $facultyProfile->id,
            'research_proposal_id' => $proposal->id,
            'status' => 'pending',
            'active_slot' => 1,
            'requested_at' => now(),
        ])->save();

        return $adviserRequest;
    }

    public function test_every_page_carries_the_shared_container_and_the_button_opens_it(): void
    {
        $adviserRequest = $this->requestFor();

        $page = $this->actingAs($adviserRequest->facultyProfile->user)
            ->get(route('faculty.requests.index'))
            ->assertOk()
            ->assertSee('id="proposal-container"', false)
            ->getContent();

        // The button keeps its real URL (new tab / no-JavaScript fallback) and is marked for the container.
        $this->assertMatchesRegularExpression(
            '/<a href="'.preg_quote(route('adviser-requests.proposal', $adviserRequest), '/').'" data-proposal-view /',
            $page
        );
    }

    public function test_container_request_returns_only_the_extracted_text_with_the_reader_protections(): void
    {
        $adviserRequest = $this->requestFor();

        $response = $this->actingAs($adviserRequest->facultyProfile->user)
            ->get(route('adviser-requests.proposal', $adviserRequest), self::CONTAINER_HEADER)
            ->assertOk()
            ->assertSee('Extracted text shown inside the container.')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $html = $response->getContent();

        // Text only: no title, student details, document shell, sidebar or Back link.
        $this->assertStringNotContainsString('Container Test Proposal', $html);
        $this->assertStringNotContainsString($adviserRequest->studentProfile->user->name, $html);
        $this->assertStringNotContainsString($adviserRequest->studentProfile->student_number, $html);
        $this->assertStringNotContainsString('<html', $html);
        $this->assertStringNotContainsString('workspace-sidebar', $html);
        $this->assertStringNotContainsString('Student Number', $html);
        $this->assertStringNotContainsString('href=', $html);

        // Same protections as the reader page.
        $this->assertStringContainsString('Faculty Reader · '.now()->format('Y-m-d H:i T'), $html);
        $this->assertStringContainsString('proposal-reader-watermark', $html);
        $this->assertStringContainsString('data-no-copy', $html);
        $this->assertStringContainsString('proposal-print-notice', $html);
    }

    public function test_same_url_without_the_header_is_still_the_full_reader_page(): void
    {
        $adviserRequest = $this->requestFor();

        $this->actingAs($adviserRequest->facultyProfile->user)
            ->get(route('adviser-requests.proposal', $adviserRequest))
            ->assertOk()
            ->assertSee('<!DOCTYPE html>', false)
            ->assertSee('Container Test Proposal')
            ->assertSee('Student Number')
            ->assertSee('href="'.route('faculty.requests.index').'"', false);
    }

    public function test_a_missing_extraction_shows_a_message_instead_of_text(): void
    {
        $adviserRequest = $this->requestFor();
        $adviserRequest->researchProposal->analysis->delete();

        $this->actingAs($adviserRequest->facultyProfile->user)
            ->get(route('adviser-requests.proposal', $adviserRequest), self::CONTAINER_HEADER)
            ->assertOk()
            ->assertSee('The text of this proposal is not available yet.');
    }

    public function test_the_container_never_widens_access(): void
    {
        $adviserRequest = $this->requestFor();
        $url = route('adviser-requests.proposal', $adviserRequest);

        $otherFaculty = User::factory()->create(['role' => User::ROLE_FACULTY]);
        $otherFaculty->facultyProfile()->create(['department' => 'Other']);

        $this->actingAs($otherFaculty)->get($url, self::CONTAINER_HEADER)->assertForbidden()
            ->assertDontSee('Extracted text shown inside the container.');

        auth()->logout();
        $this->get($url, self::CONTAINER_HEADER)->assertRedirect(route('login'));
    }

    public function test_student_and_admin_buttons_also_get_only_the_text(): void
    {
        $proposal = ResearchProposal::query()->findOrFail($this->requestFor()->research_proposal_id);
        $owner = $proposal->studentProfile->user;
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        foreach ([
            [$owner, route('student.proposals.show', $proposal)],
            [$admin, route('admin.proposals.show', $proposal)],
        ] as [$user, $url]) {
            $html = $this->actingAs($user)->get($url, self::CONTAINER_HEADER)
                ->assertOk()
                ->assertSee('Extracted text shown inside the container.')
                ->assertHeader('Cache-Control', 'no-store, private')
                ->getContent();

            $this->assertStringNotContainsString('<html', $html);
            $this->assertStringNotContainsString('Container Test Proposal', $html);
            $this->assertStringNotContainsString('<form', $html);
            $this->assertStringNotContainsString('href=', $html);

            // Without the header it is still the full detail page.
            $this->actingAs($user)->get($url)->assertOk()->assertSee('<!DOCTYPE html>', false)->assertSee('Container Test Proposal');
        }

        // Another student still cannot reach it, with or without the header.
        $stranger = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $stranger->studentProfile()->create(['student_number' => 'S-x', 'course' => 'BSIT', 'year_level' => 1, 'section' => 'B']);
        $this->actingAs($stranger)->get(route('student.proposals.show', $proposal), self::CONTAINER_HEADER)->assertNotFound();

        // The list page's "View" button is marked for the container.
        $this->actingAs($owner)->get(route('student.proposals.index'))
            ->assertOk()
            ->assertSee('data-proposal-view', false);
    }
}