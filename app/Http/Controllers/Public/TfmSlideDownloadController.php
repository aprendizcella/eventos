<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

final class TfmSlideDownloadController
{
    private const ALLOWED_FILES = [
        'Presentacion_Demo_TFM_Eventos.pptx',
        'Presentacion_TFM_Eventos_Multitenant.pptx',
        'Presentacion_Demo_TFM_Eventos.pdf',
        'Presentacion_TFM_Eventos_Multitenant.pdf',
    ];

    private const MIME_MAP = [
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'pdf' => 'application/pdf',
    ];

    /**
     * Serve a TFM slide file from private storage.
     * PPTX files are served as attachment download; PDF files are served inline for preview.
     */
    public function __invoke(string $file): BinaryFileResponse
    {
        if (!in_array($file, self::ALLOWED_FILES, true)) {
            abort(404, 'File not found.');
        }

        $disk = Storage::disk('local');
        $path = 'tfm/slides/'.$file;

        if (!$disk->exists($path)) {
            abort(404, 'File not found.');
        }

        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $contentType = self::MIME_MAP[$extension] ?? 'application/octet-stream';

        $disposition = $extension === 'pdf'
            ? ResponseHeaderBag::DISPOSITION_INLINE
            : ResponseHeaderBag::DISPOSITION_ATTACHMENT;

        return response()->file(
            $disk->path($path),
            ['Content-Type' => $contentType],
        )->setContentDisposition($disposition, $file);
    }
}
