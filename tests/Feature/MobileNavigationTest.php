<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Mobile Navigation Accessibility Test
 *
 * Assertions:
 * - Mobile menu toggle existence
 * - Escape key listener (simulated via x-cloak interaction checks)
 * - Accessibility attributes
 */
uses(TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

test('mobile navigation drawer has correct accessibility attributes', function () {
    $response = $this->get('/');
    $response->assertStatus(200);

    // Assert mobile toggle button has aria-expanded, aria-controls, and aria-haspopup
    $response->assertSee(':aria-expanded="open.toString()"', false);
    $response->assertSee('x-ref="mobileMenuButton"', false);
    $response->assertSee('aria-controls="mobile-navigation"', false);
    $response->assertSee('aria-haspopup="true"', false);

    // Assert nav drawer is hidden by default (x-show="open")
    $response->assertSee('id="mobile-navigation"', false);
    $response->assertSee('x-show="open"', false);
    $response->assertSee('x-cloak', false);
    $response->assertSee('@keydown.escape.window="open = false; $refs.mobileMenuButton?.focus()"', false);
});
