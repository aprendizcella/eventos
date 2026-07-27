<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

const DASHBOARD_LINK = 'a[aria-label="Dashboard"]';
const DASHBOARD_LABEL = 'a[aria-label="Dashboard"] > span';
const LOGIN_LINK = 'a[aria-label="Log in"]';
const LOGIN_LABEL = 'a[aria-label="Log in"] > span';
const MOBILE_MENU_BUTTON = 'button[aria-controls="mobile-navigation"]';

it('keeps guest login compact on a narrow viewport', function (): void {
    $page = visit('/')->on()->iPhone14Pro();

    $page->assertVisible(LOGIN_LINK)
        ->assertAriaAttribute(LOGIN_LINK, 'label', 'Log in')
        ->assertScript('window.matchMedia("(max-width: 639px)").matches', true)
        ->assertScript('getComputedStyle(document.querySelector(\''.LOGIN_LABEL.'\')).display', 'none')
        ->assertNoJavaScriptErrors();
});

it('keeps Dashboard compact and the public menu operable on a narrow viewport', function (): void {
    $this->actingAs(User::factory()->create());

    $page = visit('/')->on()->iPhone14Pro();

    $page->assertVisible(DASHBOARD_LINK)
        ->assertAriaAttribute(DASHBOARD_LINK, 'label', 'Dashboard')
        ->assertScript('window.matchMedia("(max-width: 639px)").matches', true)
        ->assertScript('getComputedStyle(document.querySelector(\''.DASHBOARD_LABEL.'\')).display', 'none')
        ->assertAriaAttribute(MOBILE_MENU_BUTTON, 'expanded', 'false')
        ->click(MOBILE_MENU_BUTTON)
        ->assertAriaAttribute(MOBILE_MENU_BUTTON, 'expanded', 'true')
        ->assertVisible('#mobile-navigation')
        ->assertSeeIn('#mobile-navigation', 'Discover Events')
        ->assertSeeIn('#mobile-navigation', 'Features')
        ->assertSeeIn('#mobile-navigation', 'Docs')
        ->keys('#mobile-navigation', 'Escape')
        ->assertAriaAttribute(MOBILE_MENU_BUTTON, 'expanded', 'false')
        ->assertMissing('#mobile-navigation')
        ->assertScript('document.activeElement === document.querySelector(\''.MOBILE_MENU_BUTTON.'\')', true)
        ->assertNoJavaScriptErrors();
});
