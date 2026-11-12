
<x-app-layout>
    <x-slot name="header">
        <h1 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Faculty Profile') }}
        </h1>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <section class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">

                    <h3 class="text-lg font-medium text-gray-900">
                        {{ __('Basic information') }}
                    </h3>

                    <div class="mt-4 space-y-4">

                        {{-- Email --}}
                        <p class="text-sm text-gray-700 break-words">
                            {{ $user->email }}
                        </p>

                        {{-- Full Name --}}
                        <div>
                            <p class="text-sm font-medium text-gray-700">
                                {{ __('Full Name') }}
                            </p>
                            <p class="mt-1 text-sm text-gray-700 break-words">
                                {{ $user->name }}
                            </p>
                        </div>

                        {{-- Department --}}
                        <div>
                            <p class="text-sm font-medium text-gray-700">
                                {{ __('Department') }}
                            </p>
                            <p class="mt-1 text-sm text-gray-700 break-words">
                                {{ $profile?->department ?? 'Not specified' }}
                            </p>
                        </div>

                    </div>
                </div>
            </section>

            {{-- My Skills Section --}}
            @if ($profile)
                <section class="mt-6 bg-white p-4 shadow sm:rounded-lg sm:p-8">

                    <h3 class="text-lg font-medium text-gray-900">
                        {{ __('My Skills') }}
                    </h3>

                    <ul class="mt-4 flex flex-wrap gap-2">
                        @forelse ($skills as $entry)
                            <li class="concept-chip">
                                {{ $entry->expertise_area === $otherArea && filled($entry->technical_skills)
                                    ? $entry->expertise_area . ': ' . $entry->technical_skills
                                    : $entry->expertise_area }}
                            </li>
                        @empty
                            <li class="text-sm text-gray-600">
                                {{ __('No faculty skills selected yet.') }}
                            </li>
                        @endforelse
                    </ul>

                </section>
            @endif

        </div>
    </div>
</x-app-layout>
