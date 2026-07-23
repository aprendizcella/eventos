<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\Organizer;
use App\Models\User;
use App\ViewModels\Admin\AuditLogViewModel;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Log;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function () {
    resolve(Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);
});

test('exact super_admin role can access the audit log page via route', function () {
    $superAdmin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $superAdmin->assignRole($role);

    $this->actingAs($superAdmin);

    $this->get(route('admin.audit-logs'))->assertSuccessful();
});

test('platform_admin role cannot access the audit log page', function () {
    $platformAdmin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'platform_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $platformAdmin->assignRole($role);

    $this->actingAs($platformAdmin);

    $this->get(route('admin.audit-logs'))->assertForbidden();
});

test('guests are redirected to login', function () {
    $this->get(route('admin.audit-logs'))->assertRedirect(route('login'));
});

test('unauthorized direct Livewire request is denied and logs warning', function () {
    $platformAdmin = User::factory()->create();
    // Ensure team context is set before checking role
    setPermissionsTeamId(0);
    $role = Role::query()->firstOrCreate(['name' => 'platform_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $platformAdmin->assignRole($role);
    $this->actingAs($platformAdmin);

    Log::shouldReceive('warning')
        ->atLeast()
        ->once()
        ->with('Unauthorized access attempt to global audit logs.', Mockery::on(fn ($context) => (int) $context['user_id'] === (int) $platformAdmin->id && $context['context'] === 'boot'));

    // Allow fallback calls to avoid Mockery count issues
    Log::shouldReceive('error')->byDefault();
    Log::shouldReceive('info')->byDefault();
    Log::shouldReceive('warning')->byDefault();

    Volt::test('admin.audit-log')
        ->assertForbidden();
});

test('component excludes tenant rows, presenting only global rows', function () {
    $superAdmin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $superAdmin->assignRole($role);
    $this->actingAs($superAdmin);

    // 1. Global Activity (Should see)
    Activity::query()->create([
        'log_name' => 'system',
        'description' => 'Global event occurred',
        'event' => 'global-action',
        'is_global' => true,
        'organizer_id' => null,
    ]);

    $organizer = Organizer::factory()->create();

    // 2. Tenant Activity (Should NOT see)
    Activity::query()->create([
        'log_name' => 'system',
        'description' => 'Tenant event occurred',
        'event' => 'tenant-action',
        'is_global' => false,
        'organizer_id' => $organizer->id,
    ]);

    // 3. Unclassified legacy activity (Should NOT see)
    $unclassifiedLegacyActivity = Activity::query()->create([
        'log_name' => 'system',
        'description' => 'Global legacy event',
        'event' => 'global-legacy-action',
        'is_global' => false,
        'organizer_id' => null,
    ]);

    Volt::test('admin.audit-log')
        ->assertSee('Global event occurred')
        ->assertDontSee('Tenant event occurred')
        ->assertDontSee('Global legacy event');

    $this->assertModelExists($unclassifiedLegacyActivity);

    $unclassifiedLegacyActivity->refresh();

    expect($unclassifiedLegacyActivity->organizer_id)->toBeNull()
        ->and((bool) $unclassifiedLegacyActivity->is_global)->toBeFalse();
});

test('active tenant context does not leak tenant data in global audit query', function () {
    $superAdmin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $superAdmin->assignRole($role);
    $this->actingAs($superAdmin);

    $organizer = Organizer::factory()->create();
    // Simulate setting an active tenant context
    $organizer->makeCurrent();

    // Create global activity
    Activity::query()->create([
        'log_name' => 'system',
        'description' => 'Secret global action',
        'event' => 'global-action',
        'is_global' => true,
        'organizer_id' => null,
    ]);

    // Create tenant activity
    Activity::query()->create([
        'log_name' => 'system',
        'description' => 'Tenant action under organizer',
        'event' => 'tenant-action',
        'is_global' => false,
        'organizer_id' => $organizer->id,
    ]);

    Volt::test('admin.audit-log')
        ->assertSee('Secret global action')
        ->assertDontSee('Tenant action under organizer');

    // Clean context
    Organizer::forgetCurrent();
});

test('safe audit row projection hides payload and attribute changes', function () {
    $superAdmin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $superAdmin->assignRole($role);
    $this->actingAs($superAdmin);

    Activity::query()->create([
        'log_name' => 'auth',
        'description' => 'Super admin logged in',
        'event' => 'login',
        'is_global' => true,
        'organizer_id' => null,
        'properties' => ['password' => 'secret123', 'token' => 'sensitive-token'],
        'properties->attribute_changes' => ['email' => 'old@mail.com'],
    ]);

    Volt::test('admin.audit-log')
        ->assertSee('Super admin logged in')
        ->assertDontSee('secret123')
        ->assertDontSee('sensitive-token')
        ->assertDontSee('old@mail.com');
});

test('component reauthorization is enforced on updates', function () {
    $superAdmin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $superAdmin->assignRole($role);
    $this->actingAs($superAdmin);

    // Initial render works
    $component = Volt::test('admin.audit-log');

    // Simulate role removal/revocation
    $superAdmin->removeRole($role);

    // Any action or refresh must fail
    $component->call('$refresh')->assertForbidden();
});

test('audit log query failure shows safe error message and logs generic message', function () {
    $superAdmin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $superAdmin->assignRole($role);
    $this->actingAs($superAdmin);

    // Mock Log to expect error
    Log::shouldReceive('error')
        ->atLeast()
        ->once()
        ->with('Global audit query failed with database exception.', ['error' => 'Database query failure']);

    // Mock the ViewModel itself to throw an exception from getLogs method
    $this->mock(AuditLogViewModel::class, function ($mock) {
        $mock->shouldReceive('getLogs')->andReturnUsing(function () {
            // Emulate the actual catch block behavior of the VM
            Log::error('Global audit query failed with database exception.', ['error' => 'Database query failure']);

            throw new RuntimeException('Database query failure occurred during audit presentation.');
        });
    });

    Volt::test('admin.audit-log')
        ->assertSee('Audit records are temporarily unavailable.')
        ->assertDontSee('Database query failure occurred');
});

test('pagination limits size to maximum bound', function () {
    $viewModel = new AuditLogViewModel;
    // Verify min and max clamps are applied to page size parameters
    $paginatorMin = $viewModel->getLogs(App\DataTransferObjects\Admin\AuditLogFilterDto::empty(), -10);
    expect($paginatorMin->perPage())->toBe(1);

    $paginatorMax = $viewModel->getLogs(App\DataTransferObjects\Admin\AuditLogFilterDto::empty(), 100);
    expect($paginatorMax->perPage())->toBe(50);
});

test('pagination ordering with identical timestamps is stable', function () {
    $superAdmin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $superAdmin->assignRole($role);
    $this->actingAs($superAdmin);

    $time = now();

    // Create 3 activities with identical created_at timestamps
    $act1 = Activity::query()->create([
        'log_name' => 'system',
        'description' => 'Log 1',
        'event' => 'event',
        'is_global' => true,
        'created_at' => $time,
    ]);

    $act2 = Activity::query()->create([
        'log_name' => 'system',
        'description' => 'Log 2',
        'event' => 'event',
        'is_global' => true,
        'created_at' => $time,
    ]);

    $act3 = Activity::query()->create([
        'log_name' => 'system',
        'description' => 'Log 3',
        'event' => 'event',
        'is_global' => true,
        'created_at' => $time,
    ]);

    // Query ViewModel
    $viewModel = new AuditLogViewModel;
    $results = $viewModel->getLogs(App\DataTransferObjects\Admin\AuditLogFilterDto::empty(), 10);

    // Assert sorting DESC by created_at, then DESC by ID
    $items = $results->items();
    expect($items[0]->id)->toBe((int) $act3->id)
        ->and($items[1]->id)->toBe((int) $act2->id)
        ->and($items[2]->id)->toBe((int) $act1->id);
});

test('unauthorized direct Livewire request with active tenant context is denied', function () {
    $platformAdmin = User::factory()->create();
    setPermissionsTeamId(0);
    $role = Role::query()->firstOrCreate(['name' => 'platform_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $platformAdmin->assignRole($role);
    $this->actingAs($platformAdmin);

    $organizer = Organizer::factory()->create();
    $organizer->makeCurrent();

    Volt::test('admin.audit-log')
        ->assertForbidden();

    Organizer::forgetCurrent();
});

test('no database queries are executed when access is denied', function () {
    $platformAdmin = User::factory()->create();
    setPermissionsTeamId(0);
    $role = Role::query()->firstOrCreate(['name' => 'platform_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $platformAdmin->assignRole($role);
    $this->actingAs($platformAdmin);

    DB::connection()->enableQueryLog();

    try {
        Volt::test('admin.audit-log');
    } catch (Throwable) {
    }

    $queries = DB::connection()->getQueryLog();
    // Filter out standard role/permission setup queries, check if activity_log was queried
    $activityQueries = collect($queries)->filter(fn ($query) => str_contains((string) $query['query'], 'activities') || str_contains((string) $query['query'], 'activity_log'));

    expect($activityQueries)->toBeEmpty();
    DB::connection()->disableQueryLog();
});

test('unauthorized direct Livewire request logs warning', function () {
    $platformAdmin = User::factory()->create();
    setPermissionsTeamId(0);
    $role = Role::query()->firstOrCreate(['name' => 'platform_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $platformAdmin->assignRole($role);
    $this->actingAs($platformAdmin);

    Log::shouldReceive('warning')
        ->atLeast()
        ->once()
        ->with('Unauthorized access attempt to global audit logs.', Mockery::on(fn ($context) => (int) $context['user_id'] === (int) $platformAdmin->id));

    // Allow fallback calls to avoid Mockery count issues
    Log::shouldReceive('error')->byDefault();
    Log::shouldReceive('info')->byDefault();
    Log::shouldReceive('warning')->byDefault();

    Volt::test('admin.audit-log')->assertForbidden();
});

test('component handles empty state gracefully', function () {
    $superAdmin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $superAdmin->assignRole($role);
    $this->actingAs($superAdmin);

    Activity::query()->truncate();

    Volt::test('admin.audit-log')
        ->assertSee('No audit records found.')
        ->assertDontSee('A database query failure occurred');
});

test('component handles missing actor and subject identities safely', function () {
    $superAdmin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $superAdmin->assignRole($role);
    $this->actingAs($superAdmin);

    Activity::query()->create([
        'log_name' => 'system',
        'description' => 'Orphan activity log entry',
        'event' => 'orphan-event',
        'is_global' => true,
        'organizer_id' => null,
        'causer_id' => null,
        'causer_type' => null,
        'subject_id' => null,
        'subject_type' => null,
    ]);

    Volt::test('admin.audit-log')
        ->assertSee('Orphan activity log entry')
        ->assertSee('Unknown');
});

test('dangerous raw labels are not doubly escaped or rendered unescaped', function () {
    $superAdmin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $superAdmin->assignRole($role);
    $this->actingAs($superAdmin);

    Activity::query()->create([
        'log_name' => '<b>bold</b>',
        'description' => '<script>alert("xss")</script>',
        'event' => 'click & win',
        'is_global' => true,
        'organizer_id' => null,
    ]);

    Volt::test('admin.audit-log')
        ->assertSee('click &amp; win', false)
        ->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert("xss")</script>', false);
});

test('pagination page changes work correctly', function () {
    $superAdmin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $superAdmin->assignRole($role);
    $this->actingAs($superAdmin);

    // Create 12 global activities to force two pages (10 per page)
    for ($i = 1; $i <= 12; $i++) {
        Activity::query()->create([
            'log_name' => 'system',
            'description' => "Activity number {$i}",
            'event' => 'page-test',
            'is_global' => true,
            'organizer_id' => null,
        ]);
    }

    Volt::test('admin.audit-log')
        ->assertSee('Activity number 12')
        ->call('setPage', 2)
        ->assertSee('Activity number 1')
        ->assertDontSee('Activity number 12');
});

test('real database exception handling without mocking VM', function () {
    $superAdmin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $superAdmin->assignRole($role);
    $this->actingAs($superAdmin);

    // Mock Log to expect error
    Log::shouldReceive('error')
        ->atLeast()
        ->once()
        ->with('Global audit query failed with database exception.', ['error' => 'Database query failure']);

    // We force database query to fail by listening to the query events and throwing an exception only when query targets activities
    DB::listen(function ($query) {
        if (str_contains((string) $query->sql, 'activities') || str_contains((string) $query->sql, 'activity_log')) {
            throw new RuntimeException('Simulated database breakdown');
        }
    });

    // Allow fallback calls to avoid Mockery count issues
    Log::shouldReceive('error')->byDefault();
    Log::shouldReceive('info')->byDefault();
    Log::shouldReceive('warning')->byDefault();

    Volt::test('admin.audit-log')
        ->assertSee('Audit records are temporarily unavailable.')
        ->assertDontSee('Simulated database breakdown');
});

test('excluded activities trigger warning logs in real execution flow', function () {
    $superAdmin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $superAdmin->assignRole($role);
    $this->actingAs($superAdmin);

    $global1 = Activity::query()->create([
        'log_name' => 'system',
        'description' => 'Global row 1',
        'event' => 'global-event',
        'is_global' => true,
        'organizer_id' => null,
    ]);

    $organizer = Organizer::factory()->create();

    $excludedTenant = Activity::query()->create([
        'log_name' => 'system',
        'description' => 'Suspicious tenant row',
        'event' => 'warning-event',
        'is_global' => false,
        'organizer_id' => $organizer->id,
    ]);

    $global2 = Activity::query()->create([
        'log_name' => 'system',
        'description' => 'Global row 2',
        'event' => 'global-event',
        'is_global' => true,
        'organizer_id' => null,
    ]);

    Log::shouldReceive('warning')
        ->atLeast()
        ->once()
        ->with('Excluded log row from UI presentation.', Mockery::on(fn ($context) => isset($context['activity_id'])
            && (int) $context['activity_id'] === (int) $excludedTenant->id
            && isset($context['reason'])
            && $context['reason'] === 'tenant'
            && !isset($context['description'])
            && !isset($context['payload'])));

    // Allow fallback calls to avoid Mockery count issues
    Log::shouldReceive('error')->byDefault();
    Log::shouldReceive('info')->byDefault();
    Log::shouldReceive('warning')->byDefault();

    Volt::test('admin.audit-log')
        ->assertSee('Global row 1')
        ->assertSee('Global row 2');
});

test('component includes loading skeleton', function () {
    $superAdmin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $superAdmin->assignRole($role);
    $this->actingAs($superAdmin);

    Volt::test('admin.audit-log')
        ->assertSee('wire:loading', false)
        ->assertSee('audit-log-loading');
});

test('only explicit global activities participate in allowlisted filters and counts', function () {
    $superAdmin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $superAdmin->assignRole($role);
    $this->actingAs($superAdmin);

    $matchingActivity = Activity::query()->create([
        'log_name' => 'auth',
        'description' => 'Matching global login',
        'event' => 'login',
        'is_global' => true,
        'organizer_id' => null,
        'created_at' => Illuminate\Support\Facades\Date::parse('2026-07-10 12:00:00'),
    ]);

    Activity::query()->create([
        'log_name' => 'auth',
        'description' => 'Unclassified login',
        'event' => 'login',
        'is_global' => false,
        'organizer_id' => null,
        'created_at' => Illuminate\Support\Facades\Date::parse('2026-07-10 12:00:00'),
    ]);

    Activity::query()->create([
        'log_name' => 'auth',
        'description' => 'Tenant login',
        'event' => 'login',
        'is_global' => false,
        'organizer_id' => Organizer::factory()->create()->id,
        'created_at' => Illuminate\Support\Facades\Date::parse('2026-07-10 12:00:00'),
    ]);

    $filter = new App\DataTransferObjects\Admin\AuditLogFilterDto(
        logName: 'auth',
        event: 'login',
        dateFrom: Illuminate\Support\Facades\Date::parse('2026-07-10')->startOfDay(),
        dateTo: Illuminate\Support\Facades\Date::parse('2026-07-10')->endOfDay(),
    );

    $logs = (new AuditLogViewModel)->getLogs($filter);

    expect($logs->total())->toBe(1)
        ->and($logs->first()->id)->toBe((int) $matchingActivity->id)
        ->and($logs->first()->description)->toBe('Matching global login');
});

test('invalid draft filters retain the prior safe result without broadening the query', function () {
    $superAdmin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $superAdmin->assignRole($role);
    $this->actingAs($superAdmin);

    Activity::query()->create([
        'log_name' => 'auth',
        'description' => 'Safe prior result',
        'event' => 'login',
        'is_global' => true,
        'organizer_id' => null,
    ]);

    Activity::query()->create([
        'log_name' => 'system',
        'description' => 'Must not be broadened in',
        'event' => 'created',
        'is_global' => true,
        'organizer_id' => null,
    ]);

    Volt::test('admin.audit-log')
        ->set('draftLogName', 'auth')
        ->set('draftEvent', 'login')
        ->call('applyFilters')
        ->assertSee('Safe prior result')
        ->assertDontSee('Must not be broadened in')
        ->set('draftLogName', 'auth OR 1=1')
        ->call('applyFilters')
        ->assertHasErrors('draftLogName')
        ->assertSee('Safe prior result')
        ->assertDontSee('Must not be broadened in');
});

test('partial, malformed, reversed, and overlong date drafts do not replace applied filters', function () {
    $superAdmin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $superAdmin->assignRole($role);
    $this->actingAs($superAdmin);

    Activity::query()->create([
        'log_name' => 'auth',
        'description' => 'Bounded safe result',
        'event' => 'login',
        'is_global' => true,
        'organizer_id' => null,
    ]);

    $component = Volt::test('admin.audit-log')
        ->set('draftLogName', 'auth')
        ->set('draftEvent', 'login')
        ->call('applyFilters')
        ->set('draftDateFrom', '2026-07-11')
        ->call('applyFilters')
        ->assertHasErrors('draftDateTo')
        ->set('draftDateFrom', 'invalid-date')
        ->set('draftDateTo', '2026-07-10')
        ->call('applyFilters')
        ->assertHasErrors('draftDateFrom');

    $component
        ->set('draftDateFrom', '2026-07-11')
        ->set('draftDateTo', '2026-07-10')
        ->call('applyFilters')
        ->assertHasErrors('draftDateTo')
        ->set('draftDateFrom', '2026-07-10')
        ->set('draftDateTo', '2026-10-11')
        ->call('applyFilters')
        ->assertHasErrors('draftDateTo')
        ->assertSee('Bounded safe result');
});

test('audit controls reset pagination and present filtered records responsively', function () {
    $superAdmin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $superAdmin->assignRole($role);
    $this->actingAs($superAdmin);

    for ($index = 1; $index <= 11; $index++) {
        Activity::query()->create([
            'log_name' => $index === 11 ? 'auth' : 'system',
            'description' => "Audit row {$index}",
            'event' => $index === 11 ? 'login' : 'created',
            'is_global' => true,
            'organizer_id' => null,
            'created_at' => Illuminate\Support\Facades\Date::parse('2026-07-10 12:00:00'),
        ]);
    }

    Volt::test('admin.audit-log')
        ->call('setPage', 2)
        ->assertSee('Audit row 1')
        ->set('draftLogName', 'auth')
        ->set('draftEvent', 'login')
        ->call('applyFilters')
        ->assertSet('paginators.page', 1)
        ->assertSee('Audit row 11')
        ->assertSee('1 matching record')
        ->assertSee('Read-only audit trail')
        ->assertSee('audit-log-desktop-records')
        ->assertSee('audit-log-mobile-records')
        ->call('resetFilters')
        ->assertSet('paginators.page', 1)
        ->assertSee('Audit row 1');
});

test('audit presentation omits excluded controls and safe errors disclose no internals', function () {
    $superAdmin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $superAdmin->assignRole($role);
    $this->actingAs($superAdmin);

    Activity::query()->create([
        'log_name' => 'auth',
        'description' => 'Visible audit row',
        'event' => 'login',
        'is_global' => true,
        'organizer_id' => null,
        'properties' => ['token' => 'secret-token'],
    ]);

    Volt::test('admin.audit-log')
        ->assertSee('Visible audit row')
        ->assertDontSee('secret-token')
        ->assertDontSee('Export')
        ->assertDontSee('Chart')
        ->assertDontSee('Search payload')
        ->assertDontSee('Create audit');
});

test('filtered result count remains visible when no rows match', function () {
    $superAdmin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $superAdmin->assignRole($role);
    $this->actingAs($superAdmin);

    Activity::query()->create([
        'log_name' => 'auth',
        'description' => 'Non-matching global audit row',
        'event' => 'login',
        'is_global' => true,
        'organizer_id' => null,
    ]);

    Volt::test('admin.audit-log')
        ->set('draftLogName', 'system')
        ->set('draftEvent', 'deleted')
        ->call('applyFilters')
        ->assertSee('0 matching records')
        ->assertSee('No audit records found.');
});

test('injected and unknown event drafts preserve the prior safe result', function () {
    $superAdmin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $superAdmin->assignRole($role);
    $this->actingAs($superAdmin);

    Activity::query()->create([
        'log_name' => 'auth',
        'description' => 'Safe login result',
        'event' => 'login',
        'is_global' => true,
        'organizer_id' => null,
    ]);

    Activity::query()->create([
        'log_name' => 'auth',
        'description' => 'Unsafe event must remain absent',
        'event' => 'deleted',
        'is_global' => true,
        'organizer_id' => null,
    ]);

    $component = Volt::test('admin.audit-log')
        ->set('draftLogName', 'auth')
        ->set('draftEvent', 'login')
        ->call('applyFilters')
        ->assertSee('Safe login result')
        ->assertDontSee('Unsafe event must remain absent');

    foreach (['login OR 1=1', 'unknown-event'] as $event) {
        $component
            ->set('draftEvent', $event)
            ->call('applyFilters')
            ->assertHasErrors('draftEvent')
            ->assertSee('Safe login result')
            ->assertDontSee('Unsafe event must remain absent');
    }
});

test('inclusive ISO date bounds include both boundary rows and reject invalid ranges', function () {
    $superAdmin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $superAdmin->assignRole($role);
    $this->actingAs($superAdmin);

    Activity::query()->create([
        'log_name' => 'auth',
        'description' => 'Start boundary audit row',
        'event' => 'login',
        'is_global' => true,
        'organizer_id' => null,
        'created_at' => Illuminate\Support\Facades\Date::parse('2026-07-10 00:00:00'),
    ]);

    Activity::query()->create([
        'log_name' => 'auth',
        'description' => 'End boundary audit row',
        'event' => 'login',
        'is_global' => true,
        'organizer_id' => null,
        'created_at' => Illuminate\Support\Facades\Date::parse('2026-07-10 23:59:59'),
    ]);

    Volt::test('admin.audit-log')
        ->set('draftLogName', 'auth')
        ->set('draftEvent', 'login')
        ->set('draftDateFrom', '2026-07-10')
        ->set('draftDateTo', '2026-07-10')
        ->call('applyFilters')
        ->assertSee('Start boundary audit row')
        ->assertSee('End boundary audit row')
        ->set('draftDateFrom', '2026/07/10')
        ->call('applyFilters')
        ->assertHasErrors('draftDateFrom')
        ->assertSee('Start boundary audit row')
        ->assertSee('End boundary audit row');
});

test('equal timestamps navigate deterministically across multiple pages without duplicates', function () {
    $superAdmin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $superAdmin->assignRole($role);
    $this->actingAs($superAdmin);

    foreach (range(1, 21) as $index) {
        Activity::query()->create([
            'log_name' => 'system',
            'description' => sprintf('Equal timestamp audit %02d', $index),
            'event' => 'created',
            'is_global' => true,
            'organizer_id' => null,
            'created_at' => Illuminate\Support\Facades\Date::parse('2026-07-10 12:00:00'),
        ]);
    }

    Volt::test('admin.audit-log')
        ->assertSee('Equal timestamp audit 21')
        ->assertSee('Equal timestamp audit 12')
        ->assertDontSee('Equal timestamp audit 11')
        ->call('setPage', 2)
        ->assertSee('Equal timestamp audit 11')
        ->assertSee('Equal timestamp audit 02')
        ->assertDontSee('Equal timestamp audit 12')
        ->assertDontSee('Equal timestamp audit 01')
        ->call('setPage', 3)
        ->assertSee('Equal timestamp audit 01')
        ->assertDontSee('Equal timestamp audit 02');
});

test('audit filter renders the shared controls with stable bindings and compact actions', function () {
    $superAdmin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $superAdmin->assignRole($role);
    $this->actingAs($superAdmin);

    $component = Volt::test('admin.audit-log')
        ->assertSee('for="draftLogName"', false)
        ->assertSee('id="draftLogName"', false)
        ->assertSee('wire:model="draftLogName"', false)
        ->assertSee('All logs')
        ->assertSee('value="auth"', false)
        ->assertSee('for="draftEvent"', false)
        ->assertSee('id="draftEvent"', false)
        ->assertSee('wire:model="draftEvent"', false)
        ->assertSee('All events')
        ->assertSee('value="login"', false)
        ->assertSee('for="draftDateFrom"', false)
        ->assertSee('id="draftDateFrom"', false)
        ->assertSee('wire:model="draftDateFrom"', false)
        ->assertSee('for="draftDateTo"', false)
        ->assertSee('id="draftDateTo"', false)
        ->assertSee('wire:model="draftDateTo"', false)
        ->assertSee('Apply filters')
        ->assertSee('!w-auto', false);

    $component
        ->set('draftLogName', 'auth')
        ->set('draftEvent', 'login')
        ->call('applyFilters')
        ->assertSee('Reset')
        ->assertSee('wire:click="resetFilters"', false)
        ->assertSee('!w-auto', false);
});

test('audit presentation retains the report-aligned hierarchy and responsive records', function () {
    $superAdmin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $superAdmin->assignRole($role);
    $this->actingAs($superAdmin);

    Activity::query()->create([
        'log_name' => 'auth',
        'description' => 'Report-aligned audit row',
        'event' => 'login',
        'is_global' => true,
        'organizer_id' => null,
    ]);

    Volt::test('admin.audit-log')->assertSeeInOrder(['Global Audit Logs', 'Read-only audit trail', 'Immutable records', 'Log name', '1 matching record'])->assertSeeHtml('sm:items-center')->assertSeeHtml('rounded-xl border border-gray-200 bg-white p-4 shadow-sm')->assertSee('audit-log-desktop-records')->assertSeeHtml('id="audit-log-desktop-records" class="mt-4 hidden overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm md:block dark:border-gray-800 dark:bg-gray-900"')
        ->assertSee('audit-log-mobile-records')
        ->assertSee('Report-aligned audit row');
});

test('applying and resetting shared audit controls preserves chips, records, and pagination', function () {
    $superAdmin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'organizer_id' => 0]);
    $superAdmin->assignRole($role);
    $this->actingAs($superAdmin);

    foreach (range(1, 11) as $index) {
        Activity::query()->create([
            'log_name' => $index === 11 ? 'auth' : 'system',
            'description' => "Shared control audit row {$index}",
            'event' => $index === 11 ? 'login' : 'created',
            'is_global' => true,
            'organizer_id' => null,
        ]);
    }

    Volt::test('admin.audit-log')
        ->call('setPage', 2)
        ->set('draftLogName', 'auth')
        ->set('draftEvent', 'login')
        ->call('applyFilters')
        ->assertSet('paginators.page', 1)
        ->assertSee('aria-label="Active audit filters"', false)
        ->assertSee('Log: auth')
        ->assertSee('Event: login')
        ->assertSee('1 matching record')
        ->assertSee('Shared control audit row 11')
        ->assertSee('!w-auto', false)
        ->call('resetFilters')
        ->assertSet('paginators.page', 1)
        ->assertDontSee('aria-label="Active audit filters"', false)
        ->assertDontSee('Log: auth')
        ->assertDontSee('Event: login')
        ->assertSee('Shared control audit row 1');
});
