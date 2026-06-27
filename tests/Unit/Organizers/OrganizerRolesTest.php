<?php

declare(strict_types=1);

use App\Support\Organizers\OrganizerRoles;

it('defines exactly three organizer roles', function (): void {
    expect(OrganizerRoles::cases())->toHaveCount(3)
        ->and(OrganizerRoles::Admin->value)->toBe('admin')
        ->and(OrganizerRoles::Editor->value)->toBe('editor')
        ->and(OrganizerRoles::Viewer->value)->toBe('viewer');
});

it('provides human-readable labels for each role', function (): void {
    expect(OrganizerRoles::Admin->label())->toBe('Admin')
        ->and(OrganizerRoles::Editor->label())->toBe('Editor')
        ->and(OrganizerRoles::Viewer->label())->toBe('Viewer');
});

it('provides badge colors for each role', function (): void {
    $adminColors = OrganizerRoles::Admin->badgeColors();
    $editorColors = OrganizerRoles::Editor->badgeColors();
    $viewerColors = OrganizerRoles::Viewer->badgeColors();

    expect($adminColors)->toHaveKeys(['bg', 'text', 'dark_bg', 'dark_text'])
        ->and($editorColors)->toHaveKeys(['bg', 'text', 'dark_bg', 'dark_text'])
        ->and($viewerColors)->toHaveKeys(['bg', 'text', 'dark_bg', 'dark_text']);
});

it('provides select options as value => label array', function (): void {
    $options = OrganizerRoles::selectOptions();

    expect($options)->toBe([
        'admin' => 'Admin',
        'editor' => 'Editor',
        'viewer' => 'Viewer',
    ]);
});

it('provides flat values array for validation', function (): void {
    $values = OrganizerRoles::values();

    expect($values)->toBe(['admin', 'editor', 'viewer']);
});

it('identifies admin role correctly', function (): void {
    expect(OrganizerRoles::Admin->isAdmin())->toBeTrue()
        ->and(OrganizerRoles::Editor->isAdmin())->toBeFalse()
        ->and(OrganizerRoles::Viewer->isAdmin())->toBeFalse();
});

it('can be constructed from string value', function (): void {
    expect(OrganizerRoles::tryFrom('admin'))->toBe(OrganizerRoles::Admin)
        ->and(OrganizerRoles::tryFrom('editor'))->toBe(OrganizerRoles::Editor)
        ->and(OrganizerRoles::tryFrom('viewer'))->toBe(OrganizerRoles::Viewer)
        ->and(OrganizerRoles::tryFrom('invalid'))->toBeNull();
});
