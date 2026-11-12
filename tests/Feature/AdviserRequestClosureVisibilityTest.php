<?php

namespace Tests\Feature;

use App\Models\AdviserRequest;
use App\Models\FacultyProfile;
use App\Models\User;
use App\Services\AdviserRequestService;
use App\Services\ProposalAnalysisService;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdviserRequestClosureVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function faculty(): FacultyProfile
    {
        $faculty = User::factory()->create(['role' => User::ROLE_FACULTY])->facultyProfile()->create(['department' => 'Computing']);
        $faculty->expertise()->create(['expertise_area' => 'Web Development']);

        return $faculty;
    }

    /** A student with one proposal who has requested all three advisers. */
    private function studentWithRequests(FacultyProfile ...$advisers): array
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $profile = $student->studentProfile()->create(['student_number' => 'S-'.$student->id, 'course' => 'BSIT', 'year_level' => 3, 'section' => 'B']);
        $proposal = $profile->researchProposals()->make();
        $proposal->forceFill(['title' => 'Proposal '.$student->id, 'file_path' => 'private-'.$student->id.'.pdf', 'original_filename' => 'proposal.pdf', 'file_type' => 'pdf', 'status' => 'extracted'])->save();
        $analysis = $proposal->analysis()->make();
        $analysis->extracted_text = 'Objectives: Web application using Laravel.';
        $analysis->save();
        app(ProposalAnalysisService::class)->analyze($proposal);
        app(RecommendationService::class)->generate($proposal, $student);

        $requests = [];
        foreach ($advisers as $adviser) {
            $requests[] = app(AdviserRequestService::class)->submit($proposal, $adviser, $student);
        }

        return [$student, $requests];
    }

    public function test_when_one_adviser_accepts_the_accepted_request_moves_to_assignments_and_the_others_are_removed(): void
    {
        [$first, $second, $third] = [$this->faculty(), $this->faculty(), $this->faculty()];
        [$student, $requests] = $this->studentWithRequests($first, $second, $third);

        $this->actingAs($first->user)->post('/faculty/requests/'.$requests[0]->id.'/approve')->assertSessionHasNoErrors();

        // Student: the accepted request is under Adviser Assignments, not Adviser Requests;
        // the competing pending requests are hidden.
        $this->actingAs($student)->get('/student/requests')
            ->assertOk()
            ->assertDontSee($first->user->name)
            ->assertDontSee($second->user->name)
            ->assertDontSee($third->user->name)
            ->assertDontSee('Delete Request');
        $this->get('/student/assignments')->assertOk()->assertSee($first->user->name);

        // Accepting adviser: the student moves from Adviser Requests to Assigned Students.
        $this->actingAs($first->user)->get('/faculty/requests')->assertOk()->assertDontSee($student->name);
        $this->get('/faculty/assignments')->assertOk()->assertSee($student->name);

        // The other advisers no longer see the request and cannot accept it.
        foreach ([$second, $third] as $other) {
            $this->actingAs($other->user)->get('/faculty/requests')
                ->assertOk()
                ->assertDontSee($student->name)
                ->assertDontSee('Accept Request');
        }
        $this->actingAs($second->user)->post('/faculty/requests/'.$requests[1]->id.'/approve')->assertNotFound();
        $this->assertSame('cancelled', AdviserRequest::withTrashed()->find($requests[1]->id)->status);
        $this->assertSame('cancelled', AdviserRequest::withTrashed()->find($requests[2]->id)->status);
    }

    public function test_declined_requests_stay_in_the_lists_while_the_accepted_request_is_hidden(): void
    {
        [$first, $second, $third] = [$this->faculty(), $this->faculty(), $this->faculty()];
        [$student, $requests] = $this->studentWithRequests($first, $second, $third);

        $this->actingAs($second->user)->patch('/faculty/requests/'.$requests[1]->id.'/decline')->assertSessionHasNoErrors();
        $this->actingAs($first->user)->post('/faculty/requests/'.$requests[0]->id.'/approve')->assertSessionHasNoErrors();

        // Student: the declined request remains; the accepted one moved to assignments
        // and the other pending one was closed and hidden.
        $this->actingAs($student)->get('/student/requests')
            ->assertOk()
            ->assertSee($second->user->name)
            ->assertDontSee($first->user->name)
            ->assertDontSee($third->user->name);

        // The adviser who declined still sees the declined request; the accepting adviser does not.
        $this->actingAs($second->user)->get('/faculty/requests')->assertOk()->assertSee($student->name);
        $this->actingAs($first->user)->get('/faculty/requests')->assertOk()->assertDontSee($student->name);
    }

    public function test_cancelled_requests_stay_visible_to_the_student_while_nobody_has_accepted(): void
    {
        [$first, $second] = [$this->faculty(), $this->faculty()];
        [$student, $requests] = $this->studentWithRequests($first, $second);

        $this->actingAs($student)->patch('/student/requests/'.$requests[1]->id.'/cancel')->assertSessionHasNoErrors();

        $this->get('/student/requests')->assertOk()->assertSee($second->user->name)->assertSee('Delete Request');
    }
}