<?php

namespace Tests\Feature;

use App\Models\QRCode;
use App\Models\Table;
use App\Models\User;
use App\Support\MenuUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A QR is read by a phone, not by the browser that rendered it.
 *
 * These tests pin the two properties that decide whether a scan works from a
 * device on the same Wi-Fi: the address encoded in the image is the configured
 * QR_BASE_URL rather than the host that happened to serve the panel, and nothing
 * about the token or the table behind it depends on that address, so moving the
 * server to a different IP never duplicates or invalidates a code.
 */
class QrBaseUrlTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The LAN address the computer running the server would have. Written out as
     * a literal in the test on purpose: the point of QR_BASE_URL is that the
     * address lives in configuration and nowhere else.
     */
    private const IP_LOCAL = 'http://192.168.1.50:8000';

    public function test_the_menu_qr_uses_the_configured_base_url(): void
    {
        config()->set('menuvision.qr_base_url', self::IP_LOCAL);

        $mesa = Table::factory()->create();
        $codigo = QRCode::factory()->create(['table_id' => $mesa->id])->code;

        $this->assertSame(self::IP_LOCAL.'/menu/'.$codigo, MenuUrl::menu($codigo));
    }

    public function test_the_login_qr_uses_the_configured_base_url(): void
    {
        config()->set('menuvision.qr_base_url', self::IP_LOCAL);

        $this->assertSame(self::IP_LOCAL.'/login', MenuUrl::login());
    }

    public function test_a_configured_local_ip_keeps_localhost_and_the_test_domain_out_of_the_qr(): void
    {
        config()->set('menuvision.qr_base_url', self::IP_LOCAL);

        $url = MenuUrl::menu('ABC123');

        $this->assertStringStartsWith('http://192.168.1.50:8000/', $url);
        $this->assertStringNotContainsString('localhost', $url);
        $this->assertStringNotContainsString('127.0.0.1', $url);
        $this->assertStringNotContainsString('.test', $url);
    }

    public function test_the_token_is_preserved_in_the_url_and_still_identifies_its_table(): void
    {
        config()->set('menuvision.qr_base_url', self::IP_LOCAL);

        $primera = Table::factory()->create(['numero' => '1']);
        $segunda = Table::factory()->create(['numero' => '2']);

        $codigo = QRCode::factory()->create(['table_id' => $primera->id])->code;

        $url = MenuUrl::menu($codigo);

        // The token is the last segment and is not rewritten in any way.
        $this->assertStringEndsWith('/menu/'.$codigo, $url);
        $this->assertSame($codigo, $primera->fresh()->qrCode->code);

        // And the path it points at is the menu of that table, not of the other.
        $codigoDeLaSegunda = QRCode::factory()->create(['table_id' => $segunda->id])->code;

        $this->assertStringNotContainsString($codigoDeLaSegunda, $url);
        $this->assertSame($primera->id, QRCode::where('code', $codigo)->sole()->table_id);
    }

    public function test_a_trailing_slash_in_the_configuration_does_not_double_up(): void
    {
        config()->set('menuvision.qr_base_url', self::IP_LOCAL.'/');

        $this->assertSame(self::IP_LOCAL.'/login', MenuUrl::login());
    }

    public function test_changing_the_base_url_creates_no_extra_tables_or_codes(): void
    {
        $mesa = Table::factory()->create();
        $codigo = QRCode::factory()->create(['table_id' => $mesa->id])->code;

        // Serving the same sticker from a different address is a configuration
        // change, not a data change: nothing is written when the PNG is drawn.
        config()->set('menuvision.qr_base_url', self::IP_LOCAL);
        $this->get(route('qr.menu', $codigo))->assertOk();

        config()->set('menuvision.qr_base_url', 'http://10.0.0.7:8080');
        $this->get(route('qr.menu', $codigo))->assertOk();
        $this->get(route('qr.personal'))->assertOk();

        $this->assertSame(1, Table::query()->count());
        $this->assertSame(1, QRCode::query()->count());
        $this->assertSame($codigo, $mesa->fresh()->qrCode->code);
    }

    public function test_the_same_token_points_at_a_different_address_after_the_ip_changes(): void
    {
        $mesa = Table::factory()->create();
        $codigo = QRCode::factory()->create(['table_id' => $mesa->id])->code;

        config()->set('menuvision.qr_base_url', self::IP_LOCAL);
        $primera = MenuUrl::menu($codigo);

        config()->set('menuvision.qr_base_url', 'http://10.0.0.7:8080');
        $segunda = MenuUrl::menu($codigo);

        $this->assertStringEndsWith('/menu/'.$codigo, $primera);
        $this->assertStringEndsWith('/menu/'.$codigo, $segunda);
        $this->assertNotSame($primera, $segunda);
        $this->assertSame($codigo, $mesa->fresh()->qrCode->code);
    }

    public function test_the_drawn_qr_changes_with_the_address_but_the_token_survives(): void
    {
        $mesa = Table::factory()->create();
        $codigo = QRCode::factory()->create(['table_id' => $mesa->id])->code;

        config()->set('menuvision.qr_base_url', self::IP_LOCAL);
        $primera = $this->get(route('qr.menu', $codigo))->assertOk()->getContent();

        config()->set('menuvision.qr_base_url', 'http://10.0.0.7:8080');
        $segunda = $this->get(route('qr.menu', $codigo))->assertOk()->getContent();

        // Same token, same row, different PNG: the address lives in the image and
        // nowhere else, which is what lets a reprint follow a new IP.
        $this->assertNotSame($primera, $segunda);
        $this->assertSame(1, QRCode::query()->count());
        $this->assertSame($codigo, $mesa->fresh()->qrCode->code);
    }

    public function test_without_a_configured_address_the_qr_falls_back_to_the_host_of_the_request(): void
    {
        config()->set('menuvision.qr_base_url', '');

        $this->assertSame(route('menu.show', 'ABC123'), MenuUrl::menu('ABC123'));
        $this->assertFalse(MenuUrl::estaConfigurada());
    }

    public function test_an_address_without_a_scheme_is_ignored_rather_than_encoded(): void
    {
        // A QR encoding `192.168.1.50:8000/menu/ABC` makes several scanners treat
        // the string as a search term instead of a link, which is a worse failure
        // than falling back to a URL that at least works on the local machine.
        config()->set('menuvision.qr_base_url', '192.168.1.50:8000');

        $this->assertFalse(MenuUrl::estaConfigurada());
        $this->assertSame(route('menu.show', 'ABC123'), MenuUrl::menu('ABC123'));
    }

    public function test_the_panel_warns_when_the_qr_points_at_a_host_only_this_machine_has(): void
    {
        config()->set('menuvision.qr_base_url', '');
        $this->assertStringContainsString('QR_BASE_URL está vacío', (string) MenuUrl::problema());

        config()->set('menuvision.qr_base_url', 'http://menuvision2.0.test');
        $this->assertStringContainsString('menuvision2.0.test', (string) MenuUrl::problema());

        config()->set('menuvision.qr_base_url', 'http://localhost:8000');
        $this->assertStringContainsString('localhost', (string) MenuUrl::problema());

        // A real LAN address is perfectly usable, so nothing is reported.
        config()->set('menuvision.qr_base_url', self::IP_LOCAL);
        $this->assertNull(MenuUrl::problema());
    }

    public function test_the_table_panel_shows_the_address_each_qr_encodes(): void
    {
        $this->withoutVite();
        config()->set('menuvision.qr_base_url', self::IP_LOCAL);

        $mesa = Table::factory()->create(['numero' => '1']);
        $codigo = QRCode::factory()->create(['table_id' => $mesa->id])->code;

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.tables.index'))
            ->assertOk()
            ->assertSee(self::IP_LOCAL.'/login', escape: false)
            ->assertSee(self::IP_LOCAL.'/menu/'.$codigo, escape: false);
    }
}
