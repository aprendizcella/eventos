<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

use function assert;
use function preg_match_all;
use function strlen;
use function strpos;
use function substr;

uses(TestCase::class, LazilyRefreshDatabase::class);

const GITHUB_URL = 'https://github.com/aprendizcella/eventos';
const FEATURES_URL = '/#features';
const DOCS_URL = '/docs';
const GETTING_STARTED_HEADING = 'Getting Started';
const HELP_CENTER_WORKFLOWS_HEADING = 'Help Center Workflows';
const TECHNICAL_REFERENCE_HEADING = 'Technical Reference';

it('renders public navigation destinations for guests', function (): void {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('<nav', false)
        ->assertSee(anchor('/'), false)
        ->assertSee(anchor(FEATURES_URL), false)
        ->assertSee(anchor(DOCS_URL), false)
        ->assertSee(anchor(route('login', absolute: false)), false)
        ->assertSee('aria-label="Log in"', false)
        ->assertSee('title="Log in"', false)
        ->assertSee('class="hidden sm:inline"', false)
        ->assertSee('Discover Events')
        ->assertSee('Features')
        ->assertSee('Docs')
        ->assertSee('GitHub');
});

it('keeps public login navigation on an organizer custom domain', function (): void {
    $this->get('https://miseventos.example.test/')
        ->assertSuccessful()
        ->assertSee(anchor(route('login', absolute: false)), false)
        ->assertDontSee('https://events.saboreateruel.com/login', false);
});

it('links to GitHub with safe external anchor attributes', function (): void {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee(anchor(GITHUB_URL), false)
        ->assertSee('target="_blank"', false)
        ->assertSee('rel="noopener noreferrer"', false)
        ->assertSee('aria-label="GitHub (opens in a new tab)"', false);
});

it('exposes Login but not Dashboard for guests', function (): void {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('Log in')
        ->assertSee(anchor(route('login', absolute: false)), false)
        ->assertDontSee('Dashboard')
        ->assertDontSee(anchor(route('dashboard', absolute: false)), false);
});

it('exposes Dashboard but not Login for authenticated users', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertSuccessful()
        ->assertSee('Dashboard')
        ->assertSee(anchor(route('dashboard', absolute: false)), false)
        ->assertSee('aria-label="Dashboard"', false)
        ->assertSee('class="hidden sm:inline"', false)
        ->assertDontSee('>Log in<')
        ->assertDontSee(anchor(route('login', absolute: false)), false);
});

it('mirrors the public destinations in the footer for guests', function (): void {
    $response = $this->get('/');

    $response->assertSuccessful()
        ->assertSee('<footer', false)
        ->assertSee(anchor(FEATURES_URL), false)
        ->assertSee(anchor(DOCS_URL), false)
        ->assertSee(anchor(route('login', absolute: false)), false)
        ->assertSee(anchor(GITHUB_URL), false);

    $footer = extractFooter($response->getContent());
    expect($footer)
        ->toContain(anchor('/'))
        ->toContain(anchor(FEATURES_URL))
        ->toContain(anchor(DOCS_URL))
        ->toContain(anchor(route('login', absolute: false)))
        ->toContain(anchor(GITHUB_URL))
        ->toContain('target="_blank"')
        ->toContain('rel="noopener noreferrer"');
});

it('mirrors the auth destination in the footer for authenticated users', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertSuccessful();

    $footer = extractFooter($response->getContent());
    expect($footer)
        ->toContain(anchor(route('dashboard', absolute: false)))
        ->not->toContain(anchor(route('login', absolute: false)));
});

it('keeps the persisted theme control on the public shell', function (): void {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('data-theme-toggle', false)
        ->assertSee('data-theme-option="light"', false)
        ->assertSee('data-theme-option="dark"', false)
        ->assertSee('data-theme-option="system"', false)
        ->assertSee('localStorage.getItem', false);
});

it('renders the public docs landing without authentication', function (): void {
    $this->get(DOCS_URL)
        ->assertSuccessful()
        ->assertSee('Documentation');
});

it('exposes exactly the three documentation categories', function (): void {
    $this->get(DOCS_URL)
        ->assertSuccessful()
        ->assertSee(GETTING_STARTED_HEADING)
        ->assertSee(HELP_CENTER_WORKFLOWS_HEADING)
        ->assertSee(TECHNICAL_REFERENCE_HEADING);
});

it('keeps docs category cards non-linking and free of guide URLs', function (): void {
    $body = $this->get(DOCS_URL)->assertSuccessful()->getContent();

    expect($body)
        ->not->toContain(DOCS_URL.'/getting-started')
        ->not->toContain(DOCS_URL.'/help-center')
        ->not->toContain(DOCS_URL.'/technical-reference')
        ->not->toContain(DOCS_URL.'/guide')
        ->not->toContain('Detailed guides')
        ->not->toContain('Coming soon');

    $categories = extractDocsCategories($body);
    expect($categories)->toHaveCount(3)
        ->and($categories)->toContain(GETTING_STARTED_HEADING)
        ->and($categories)->toContain(HELP_CENTER_WORKFLOWS_HEADING)
        ->and($categories)->toContain(TECHNICAL_REFERENCE_HEADING);
});

it('keeps technical reference distinct from product workflows', function (): void {
    $body = $this->get(DOCS_URL)->assertSuccessful()->getContent();

    $categories = extractDocsCategories($body);
    expect($categories)
        ->toContain(HELP_CENTER_WORKFLOWS_HEADING)
        ->and($categories)->toContain(TECHNICAL_REFERENCE_HEADING);

    // Reference scope must call out reference material (e.g. API/schema) rather than workflows.
    expect($body)
        ->toContain('API')
        ->toContain('database schema');
});

// Helpers --------------------------------------------------------------

function anchor(string $url): string
{
    return 'href="'.$url.'"';
}

/**
 * Extract the rendered public footer element from the response HTML.
 */
function extractFooter(string $html): string
{
    $start = strpos($html, '<footer');
    assert($start !== false, 'Public footer element was not rendered.');
    $end = strpos($html, '</footer>', $start);
    assert($end !== false, 'Public footer element was not closed.');

    return substr($html, $start, $end - $start + strlen('</footer>'));
}

/**
 * Extract the rendered docs category h3 headings from the response HTML.
 *
 * @return array<int, string>
 */
function extractDocsCategories(string $html): array
{
    preg_match_all(
        '/<h3[^>]*>\s*('.implode('|', array_map(
            static fn (string $heading): string => preg_quote($heading, '/'),
            [GETTING_STARTED_HEADING, HELP_CENTER_WORKFLOWS_HEADING, TECHNICAL_REFERENCE_HEADING],
        )).')\s*<\/h3>/',
        $html,
        $matches,
    );

    return $matches[1] ?? [];
}
