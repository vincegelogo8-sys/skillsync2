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

        return view('faculty.profile.edit', [
            'user' => $request->user(),
            'profile' => $profile,
            // The "My Skills" card on the profile shows the faculty member's own saved skills.
            'skills' => $profile
                ? $profile->expertise()
                    ->whereIn('expertise_area', config('expertise.areas'))
                    ->orderBy('expertise_area')->get()
                : collect(),
            'otherArea' => config('expertise.other'),
        ]);
    }

    public function update(FacultyProfileRequest $request, FacultyProfileService $profiles): RedirectResponse
    {
        $profiles->save($request->user(), $request->validated());

        return redirect()->route('faculty.profile.edit')->with('status', 'faculty-profile-updated');
    }
}