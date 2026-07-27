@extends('layouts.public')

@section('content')
    <section aria-labelledby="docs-heading">
        <div class="max-w-3xl">
            <h1 id="docs-heading" class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ __('Documentation') }}</h1>
            <p class="mt-3 text-sm leading-6 text-gray-600 dark:text-gray-400">
                {{ __('A starting point for learning what Eventos can do. Each category below describes what is available today here in the product itself.') }}
            </p>
        </div>

        <div class="mt-10 grid gap-6 md:grid-cols-3">
            <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Getting Started') }}</h3>
                <p class="mt-3 text-sm leading-6 text-gray-600 dark:text-gray-400">
                    {{ __('Discover upcoming events, search by category, city, or date, and open an event page to learn more before reserving tickets.') }}
                </p>
            </article>

            <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Help Center Workflows') }}</h3>
                <p class="mt-3 text-sm leading-6 text-gray-600 dark:text-gray-400">
                    {{ __('Step-by-step product workflows for attendees and organizers, from reserving tickets to creating, publishing, and managing an event.') }}
                </p>
            </article>

            <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Technical Reference') }}</h3>
                <p class="mt-3 text-sm leading-6 text-gray-600 dark:text-gray-400">
                    {{ __('Reference material for developers, such as the database schema and the API surface, kept distinct from product workflows.') }}
                </p>
            </article>
        </div>

        <p class="mt-10 text-sm text-gray-500 dark:text-gray-400">
            {{ __('As the documentation grows, additional category pages will be linked from this index.') }}
        </p>
    </section>
@endsection