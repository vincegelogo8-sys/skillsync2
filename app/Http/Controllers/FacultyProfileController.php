<?php

namespace App\Http\Controllers;

use App\Http\Requests\FacultyProfileRequest;
use App\Services\FacultyProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FacultyProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $profile = $request->user()->facultyProfile;
        $entries = $profile
            ? $profile->expertise()
                ->whereIn('expertise_area', config('expertise.areas'))
                ->orderBy('expertise_area')->get()
            : collect();

        return view('faculty.profile.edit', [
            'user' => $request->user(),
            'profile' => $profile,
            'entries' => $entries,
            'areas' => array_values(array_diff(config('expertise.areas'), [config('expertise.other')])),
            'otherArea' => config('expertise.other'),
            'otherSpecify' => (string) $entries->firstWhere('expertise_area', config('expertise.other'))?->technical_skills,
        ]);
    }

    public function update(FacultyProfileRequest $request, FacultyProfileService $profiles): RedirectResponse
    {
        $profiles->save($request->user(), $request->validated());

        return redirect()->route('faculty.profile.edit')->with('status', 'faculty-profile-updated');
    }
}