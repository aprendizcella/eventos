<?php

declare(strict_types=1);

namespace App\Services\Tickets;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

final readonly class QrCodeGenerator
{
    /**
     * Genera un código QR en formato SVG.
     */
    public function generateSvg(string $code, int $size = 150): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size),
            new SvgImageBackEnd,
        );

        $writer = new Writer($renderer);

        return $writer->writeString($code);
    }

    /**
     * Genera un código QR en formato Data URI Base64 listo para usar en etiquetas <img>.
     */
    public function generateBase64DataUri(string $code, int $size = 150): string
    {
        $svg = $this->generateSvg($code, $size);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
