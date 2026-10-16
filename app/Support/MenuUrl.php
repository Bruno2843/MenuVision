<?php

namespace App\Support;

/**
 * Resolves the absolute URL that a QR code encodes.
 *
 * This is the only place that knows where MenuVision claims to live. The QR is
 * read by a phone, not by the browser that rendered it, so "the host that
 * served this page" is not a usable answer: `menuvision2.0.test` resolves on the
 * machine that runs Herd and nowhere else, and `127.0.0.1` on a phone means the
 * phone itself. Both produce a QR that scans to nothing.
 *
 * So the origin is configuration, read from QR_BASE_URL, and the path is still
 * built from the named route. Nothing about the code, the token or the table
 * behind it is stored or derived from here: change the address and the same
 * stickers keep working on the next render of the PNG.
 */
class MenuUrl
{
    /**
     * Hosts that a QR must not point at when it is going to be read from another
     * device. They are not wrong in themselves, they are simply unreachable from
     * a phone on the Wi-Fi, which is the case being warned about.
     *
     * @var array<int, string>
     */
    protected const INALCANZABLES = ['localhost', '127.0.0.1', '0.0.0.0', '.test', '.local', '.localhost'];

    /**
     * The configured origin, with the trailing slash removed, or an empty string
     * when there is nothing usable configured.
     *
     * A value that does not parse as an http(s) origin is treated as absent
     * rather than written into a QR: a QR encoding `192.168.1.50:8000` without a
     * scheme makes some scanners fall back to searching the web for that string,
     * which is a worse failure than falling back to the current host.
     */
    public static function base(): string
    {
        $configurada = rtrim(trim((string) config('menuvision.qr_base_url')), '/');

        if ($configurada === '') {
            return '';
        }

        $partes = parse_url($configurada);

        if (! is_array($partes) || ! in_array(strtolower((string) ($partes['scheme'] ?? '')), ['http', 'https'], true)) {
            return '';
        }

        if (blank($partes['host'] ?? null)) {
            return '';
        }

        return $configurada;
    }

    /**
     * Whether an origin has been configured for the QR codes.
     */
    public static function estaConfigurada(): bool
    {
        return static::base() !== '';
    }

    /**
     * The URL a table QR opens: the public menu of that table.
     */
    public static function menu(string $code): string
    {
        return static::url('menu.show', $code);
    }

    /**
     * The URL the staff QR opens: the login screen.
     */
    public static function login(): string
    {
        return static::url('login');
    }

    /**
     * Why the configured origin will not work from a phone, or null when it will.
     *
     * Surfaced in the admin panel, where the person about to print a sticker is
     * the only one in a position to notice that the address is unreachable.
     */
    public static function problema(): ?string
    {
        if (! static::estaConfigurada()) {
            return 'QR_BASE_URL está vacío: los QR se generan con el host de esta pantalla '
                .'(Herd o localhost), que el teléfono no puede abrir. Definí QR_BASE_URL '
                .'con la IP local de la computadora para probarlos desde un celular.';
        }

        $anfitrion = (string) (parse_url(static::base(), PHP_URL_HOST) ?? '');
        $inalcanzable = collect(static::INALCANZABLES)
            ->first(fn (string $host) => $anfitrion === trim($host, '.') || str_ends_with(strtolower($anfitrion), $host));

        if ($inalcanzable === null) {
            return null;
        }

        return 'QR_BASE_URL apunta a «'.$anfitrion.'», que solo existe en esta computadora: '
            .'el QR escaneado desde un celular no va a abrir nada. Usá la IP local de la máquina '
            .'(ipconfig) en lugar del dominio de Herd.';
    }

    /**
     * Build the absolute URL for a named route on the configured origin.
     *
     * @param  string  $nombre  Named route.
     */
    protected static function url(string $nombre, mixed ...$parametros): string
    {
        if (! static::estaConfigurada()) {
            return route($nombre, $parametros);
        }

        // The path still comes from the named route, so a renamed or moved route
        // cannot leave a QR pointing at a path that no longer answers.
        return static::base().'/'.ltrim(route($nombre, $parametros, absolute: false), '/');
    }
}
