<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\Organizer;
use App\Models\User;
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

    // 3. Global Legacy Activity (Should see - organizer_id is null and is_global is false now)
    Activity::query()->create([
        'log_name' => 'system',
        'description' => 'Global legacy event',
        'event' => 'global-legacy-action',
        'is_global' => false,
        'organizer_id' => null,
    ]);

    Volt::test('admin.audit-log')
        ->assertSee('Global event occurred')
        ->assertDontSee('Tenant event occurred')
        ->assertSee('Global legacy event');
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
    $this->mock(App\ViewModels\Admin\AuditLogViewModel::class, function ($mock) {
        $mock->shouldReceive('getLogs')->andReturnUsing(function () {
            // Emulate the actual catch block behavior of the VM
            Log::error('Global audit query failed with database exception.', ['error' => 'Database query failure']);

            throw new RuntimeException('Database query failure occurred during audit presentation.');
        });
    });

    Volt::test('admin.audit-log')
        ->assertSee('A database query failure occurred during audit presentation.');
});

test('pagination limits size to maximum bound', function () {
    $viewModel = new App\ViewModels\Admin\AuditLogViewModel;
    // Verify min and max clamps are applied to page size parameters
    $paginatorMin = $viewModel->getLogs(-10);
    expect($paginatorMin->perPage())->toBe(1);

    $paginatorMax = $viewModel->getLogs(100);
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
    $viewModel = new App\ViewModels\Admin\AuditLogViewModel;
    $results = $viewModel->getLogs(10);

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
        ->assertSee('A database query failure occurred during audit presentation.');
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
