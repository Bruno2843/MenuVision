<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\QRCode;
use App\Support\MenuUrl;
use Endroid\QrCode\QrCode as QrCodeGenerator;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Component\HttpFoundation\Response;

class QrImageController extends Controller
{
    /**
     * Render the QR image that opens the public menu for a table.
     *
     * The PNG is drawn from the URL the QR code resolves right now, which is why
     * pointing QR_BASE_URL at a new address is enough to move every sticker: the
     * code is untouched, so the tokens already printed keep their table.
     */
    public function menu(string $code): Response
    {
        $qrCode = QRCode::where('code', $code)->firstOrFail();

        return $this->png(MenuUrl::menu($qrCode->code));
    }

    /**
     * Render the QR image that opens the staff login page.
     */
    public function personal(): Response
    {
        return $this->png(MenuUrl::login());
    }

    /**
     * Build a PNG response for the given QR content.
     */
    private function png(string $value): Response
    {
        $result = (new PngWriter)->write(
            new QrCodeGenerator($value, size: 480, margin: 12)
        );

        return response($result->getString(), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
