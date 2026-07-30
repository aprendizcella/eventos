<?php

declare(strict_types=1);

namespace Tests\Feature\Public;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    Storage::fake('local');

    // Seed fake PPTX and PDF files in the private disk
    Storage::disk('local')->put('tfm/slides/Presentacion_Demo_TFM_Eventos.pptx', 'fake demo pptx content');
    Storage::disk('local')->put('tfm/slides/Presentacion_Demo_TFM_Eventos.pdf', 'fake demo pdf content');
    Storage::disk('local')->put('tfm/slides/Presentacion_TFM_Eventos_Multitenant.pptx', 'fake multitenant pptx content');
    Storage::disk('local')->put('tfm/slides/Presentacion_TFM_Eventos_Multitenant.pdf', 'fake multitenant pdf content');
});

it('downloads an approved demo PPTX file', function (): void {
    $this->get('/tfm/slides/Presentacion_Demo_TFM_Eventos.pptx/download')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.presentationml.presentation')
        ->assertHeader('Content-Disposition', 'attachment; filename=Presentacion_Demo_TFM_Eventos.pptx');
});

it('downloads an approved multitenant PPTX file', function (): void {
    $this->get('/tfm/slides/Presentacion_TFM_Eventos_Multitenant.pptx/download')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.presentationml.presentation');
});

it('returns 404 for an unknown filename', function (): void {
    $this->get('/tfm/slides/Unknown.pptx/download')
        ->assertNotFound();
});

it('does not disclose private files outside the allow-list', function (): void {
    // A file that exists on disk but is not in the allow-list
    Storage::disk('local')->put('tfm/slides/secret.pptx', 'sensitive content');

    $this->get('/tfm/slides/secret.pptx/download')
        ->assertNotFound();
});

it('serves an approved PDF inline for preview', function (): void {
    $this->get('/tfm/slides/Presentacion_Demo_TFM_Eventos.pdf/download')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'inline; filename=Presentacion_Demo_TFM_Eventos.pdf');
});

// Page-level tests ----------------------------------------------------

it('renders the slides page with two presentation cards', function (): void {
    $this->get('/tfm/slides')
        ->assertOk()
        ->assertSee('Presentación Demo TFM Eventos')
        ->assertSee('Presentación TFM Eventos Multitenant')
        ->assertSee('iframe', false);
});

it('renders a PPTX download link for each slide', function (): void {
    $this->get('/tfm/slides')
        ->assertOk()
        ->assertSee(route('tfm.slides.download', 'Presentacion_Demo_TFM_Eventos.pptx'), false)
        ->assertSee(route('tfm.slides.download', 'Presentacion_TFM_Eventos_Multitenant.pptx'), false);
});

it('shows slide metadata for each card', function (): void {
    $this->get('/tfm/slides')
        ->assertOk()
        ->assertSee('Fecha de defensa')
        ->assertSee('Última modificación');
});

it('renders the videos page with YouTube nocookie embed', function (): void {
    $this->get('/tfm/videos')
        ->assertOk()
        ->assertSee('youtube-nocookie.com', false)
        ->assertSee('embed', false);
});

it('renders video page title and description', function (): void {
    $this->get('/tfm/videos')
        ->assertOk()
        ->assertSee('Video Presentación TFM');
});
