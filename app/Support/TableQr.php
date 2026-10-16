<?php

namespace App\Support;

use App\Models\QRCode;
use App\Models\Table;

/**
 * Resolves the table a scanned QR code stands for.
 *
 * A table sticker encodes the URL of the public menu (see MenuUrl), so what the
 * camera decodes is normally a full URL and only sometimes the bare token, which
 * is what an older sticker or a hand-typed code looks like. Both are accepted so
 * the waiter never has to retype anything, but the lookup itself is always done
 * here against the stored token: nothing the camera returns is trusted beyond
 * being a candidate string.
 */
class TableQr
{
    /**
     * Find the table behind a scanned value.
     *
     * @return array{qrCode: QRCode, table: Table}|null Null when the value is not
     *                                                  a known QR code, or when the code points at a table that no longer
     *                                                  exists.
     */
    public static function resolver(string $valor): ?array
    {
        $token = static::token($valor);

        if ($token === null) {
            return null;
        }

        $qrCode = QRCode::query()
            ->where('code', $token)
            ->with('table')
            ->first();

        if ($qrCode === null) {
            return null;
        }

        $table = $qrCode->table;

        return $table === null ? null : ['qrCode' => $qrCode, 'table' => $table];
    }

    /**
     * The token carried by a scanned value, or null when it carries none.
     *
     * A URL keeps only the segment the `menu.show` route owns, so a QR pointing
     * at a dish or at a foreign host resolves to its own table and never to
     * another one. Anything that is not a URL is taken as a token, trimmed, as
     * long as it is a single path segment.
     */
    protected static function token(string $valor): ?string
    {
        $valor = trim($valor);

        if ($valor === '') {
            return null;
        }

        if (! preg_match('#^[a-z][a-z0-9+.-]*://#i', $valor)) {
            return str_contains($valor, '/') ? null : $valor;
        }

        $path = (string) (parse_url($valor, PHP_URL_PATH) ?? '');
        $segmentos = array_values(array_filter(explode('/', $path), fn (string $s): bool => $s !== ''));

        // `/menu/{code}` is the only shape a table QR takes; anything else is
        // somebody else's code and is treated as unknown rather than guessed at.
        return count($segmentos) === 2 && $segmentos[0] === 'menu' ? $segmentos[1] : null;
    }
}
