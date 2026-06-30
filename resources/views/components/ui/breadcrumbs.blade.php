@php
    $routeName = Route::currentRouteName();
    $organizer = request()->route('organizer') ?? auth()->user()?->currentOrganizer();
    
    $breadcrumbs = [];

    // Add Dashboard as base if we are not on the dashboard itself
    if ($routeName !== 'dashboard') {
        $breadcrumbs[] = [
            'label' => __('Dashboard'),
            'url' => route('dashboard'),
            'active' => false,
        ];
    }

    if (str_starts_with($routeName, 'organizers.')) {
        if ($organizer) {
            // Contextual path (inside an organizer)
            $breadcrumbs[] = [
                'label' => $organizer->name,
                'url' => route('organizers.show', $organizer),
                'active' => $routeName === 'organizers.show',
            ];

            if (str_contains($routeName, '.events.')) {
                $breadcrumbs[] = [
                    'label' => __('Events'),
                    'url' => route('organizers.events.index', $organizer),
                    'active' => $routeName === 'organizers.events.index',
                ];

                if ($routeName === 'organizers.events.create') {
                    $breadcrumbs[] = [
                        'label' => __('New Event'),
                        'url' => null,
                        'active' => true,
                    ];
                } elseif ($routeName === 'organizers.events.edit') {
                    $breadcrumbs[] = [
                        'label' => __('Edit Event'),
                        'url' => null,
                        'active' => true,
                    ];
                } elseif ($routeName === 'organizers.events.show') {
                    $event = request()->route('event');
                    $breadcrumbs[] = [
                        'label' => $event ? $event->title : __('Event details'),
                        'url' => null,
                        'active' => true,
                    ];
                }
            } elseif (str_contains($routeName, '.team.')) {
                $breadcrumbs[] = [
                    'label' => __('Team'),
                    'url' => route('organizers.team.index', $organizer),
                    'active' => true,
                ];
            } elseif (str_contains($routeName, '.venues.')) {
                $breadcrumbs[] = [
                    'label' => __('Venues'),
                    'url' => route('organizers.venues.index', $organizer),
                    'active' => $routeName === 'organizers.venues.index',
                ];

                if ($routeName === 'organizers.venues.create') {
                    $breadcrumbs[] = [
                        'label' => __('New Venue'),
                        'url' => null,
                        'active' => true,
                    ];
                } elseif ($routeName === 'organizers.venues.edit') {
                    $breadcrumbs[] = [
                        'label' => __('Edit Venue'),
                        'url' => null,
                        'active' => true,
                    ];
                }
            }
        } else {
            // Global path (outside an organizer)
            $breadcrumbs[] = [
                'label' => __('Organizers'),
                'url' => route('organizers.index'),
                'active' => $routeName === 'organizers.index',
            ];

            if ($routeName === 'organizers.create') {
                $breadcrumbs[] = [
                    'label' => __('Create Organizer'),
                    'url' => null,
                    'active' => true,
                ];
            }
        }
    } elseif ($routeName === 'dashboard') {
        $breadcrumbs[] = [
            'label' => __('Dashboard'),
            'url' => null,
            'active' => true,
        ];
    } elseif (str_starts_with($routeName, 'account.')) {
        $breadcrumbs[] = [
            'label' => __('Account'),
            'url' => null,
            'active' => false,
        ];
        
        if ($routeName === 'account.profile.edit') {
            $breadcrumbs[] = [
                'label' => __('Profile'),
                'url' => null,
                'active' => true,
            ];
        } elseif ($routeName === 'account.password.edit') {
            $breadcrumbs[] = [
                'label' => __('Change Password'),
                'url' => null,
                'active' => true,
            ];
        }
    }
@endphp

@if (count($breadcrumbs) > 0)
    <nav class="flex text-sm text-gray-500 dark:text-gray-400 mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-2">
            @foreach ($breadcrumbs as $index => $crumb)
                <li class="inline-flex items-center">
                    @if ($index > 0)
                        <svg class="mx-2 size-4 text-gray-400 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" />
                        </svg>
                    @endif
                    
                    @if ($crumb['url'] && !$crumb['active'])
                        <a href="{{ $crumb['url'] }}" class="font-medium text-gray-600 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400 transition-colors">
                            {{ $crumb['label'] }}
                        </a>
                    @else
                        <span class="font-semibold text-gray-900 dark:text-gray-100 {{ $crumb['active'] ? '' : 'opacity-70' }}">
                            {{ $crumb['label'] }}
                        </span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
