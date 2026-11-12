
<x-app-layout>
    <x-slot name="header">
        <h1 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Student Profile') }}
        </h1>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <section class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">

                    <h3 class="text-lg font-medium text-gray-900">
                        {{ __('Academic information') }}
                    </h3>

                    {{-- Student Name and Email --}}
                    <p class="mt-4 text-sm text-gray-700 break-words">
                        {{ $user->name }} &middot; {{ $user->email }}
                    </p>

                    <div class="mt-6 space-y-4">

                        {{-- Student Number --}}
                        <div>
                            <p class="text-sm font-medium text-gray-700">
                                {{ __('Student Number') }}
                            </p>
                            <p class="mt-1 text-sm text-gray-700 break-words">
                                {{ $profile?->student_number ?? 'Not specified' }}
                            </p>
                        </div>

                        {{-- Course --}}
                        <div>
                            <p class="text-sm font-medium text-gray-700">
                                {{ __('Course') }}
                            </p>
                            <p class="mt-1 text-sm text-gray-700 break-words">
                                {{ $profile?->course ?? 'Not specified' }}
                            </p>
                        </div>

                        {{-- Year Level --}}
                        <div>
                            <p class="text-sm font-medium text-gray-700">
                                {{ __('Year Level') }}
                            </p>
                            <p class="mt-1 text-sm text-gray-700 break-words">
                                @php
                                    $yearLevel = $profile?->year_level;
                                    $yearLevels = config('student_options.year_levels', []);
                                @endphp

                                {{ $yearLevel !== null
                                    ? ($yearLevels[$yearLevel] ?? $yearLevel)
                                    : 'Not specified' }}
                            </p>
                        </div>

                        {{-- Section --}}
                        <div>
                            <p class="text-sm font-medium text-gray-700">
                                {{ __('Section') }}
                            </p>
                            <p class="mt-1 text-sm text-gray-700 break-words">
                                {{ $profile?->section ?? 'Not specified' }}
                            </p>
                        </div>

                    </div>
                </div>
            </section>

        </div>
    </div>
</x-app-layout>
