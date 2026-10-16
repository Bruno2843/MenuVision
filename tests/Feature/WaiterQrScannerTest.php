<?php

namespace Tests\Feature;

use App\Enums\TableStatus;
use App\Models\Dish;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\QRCode;
use App\Models\Table;
use App\Models\User;
use App\Support\MenuUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaiterQrScannerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_a_guest_cannot_resolve_a_table_qr_code(): void
    {
        $table = Table::factory()->create();
        $qrCode = QRCode::factory()->for($table)->create();

        $this->postJson(route('waiter.qr.resolve'), ['code' => $qrCode->code])
            ->assertUnauthorized();

        $this->post(route('waiter.qr.resolve'), ['code' => $qrCode->code])
            ->assertRedirect(route('login'));
    }

    public function test_a_user_who_is_not_a_waiter_cannot_resolve_a_table_qr_code(): void
    {
        $table = Table::factory()->create();
        $qrCode = QRCode::factory()->for($table)->create();

        // A non-waiter is turned away by the same middleware that guards the
        // rest of the panel: redirected to their own dashboard, and refused by
        // the request itself when the JSON call skips the redirect.
        $this->actingAs(User::factory()->kitchen()->create())
            ->post(route('waiter.qr.resolve'), ['code' => $qrCode->code])
            ->assertRedirect(route('kitchen.dashboard'));

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('waiter.qr.resolve'), ['code' => $qrCode->code])
            ->assertRedirect(route('admin.dashboard'));

        // The scanner is never answered with a table for a role that does not
        // belong to the panel, whoever asks and however the request is framed.
        $this->actingAs(User::factory()->kitchen()->create())
            ->postJson(route('waiter.qr.resolve'), ['code' => $qrCode->code])
            ->assertRedirect(route('kitchen.dashboard'))
            ->assertDontSee('success', false);
    }

    public function test_a_waiter_resolves_a_qr_code_into_its_table(): void
    {
        $table = Table::factory()->create(['numero' => '5']);
        $qrCode = QRCode::factory()->for($table)->create(['code' => '7pjpKiayDd4OKxtp']);

        $this->actingAs(User::factory()->waiter()->create())
            ->postJson(route('waiter.qr.resolve'), ['code' => '7pjpKiayDd4OKxtp'])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'table' => ['id' => $table->id, 'numero' => '5'],
            ]);
    }

    public function test_a_waiter_resolves_the_url_a_sticker_encodes(): void
    {
        $table = Table::factory()->create(['numero' => '7']);
        $qrCode = QRCode::factory()->for($table)->create(['code' => 'oaEjYAHXqobXSsyR']);

        $this->actingAs(User::factory()->waiter()->create())
            ->postJson(route('waiter.qr.resolve'), ['code' => MenuUrl::menu($qrCode->code)])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('table.id', $table->id)
            ->assertJsonPath('table.numero', '7');
    }

    public function test_the_resolved_url_opens_the_order_form_for_that_table(): void
    {
        $table = Table::factory()->create(['numero' => '5']);
        $qrCode = QRCode::factory()->for($table)->create(['code' => '7pjpKiayDd4OKxtp']);

        $url = $this->actingAs(User::factory()->waiter()->create())
            ->postJson(route('waiter.qr.resolve'), ['code' => $qrCode->code])
            ->json('url');

        $this->actingAs(User::factory()->waiter()->create())
            ->get($url)
            ->assertOk()
            ->assertViewIs('waiter.orders.create')
            ->assertSee('Mesa 5')
            ->assertViewHas('table', fn (Table $t): bool => $t->is($table));
    }

    public function test_an_unknown_qr_code_is_reported_without_a_table(): void
    {
        $this->actingAs(User::factory()->waiter()->create())
            ->postJson(route('waiter.qr.resolve'), ['code' => 'no-existe'])
            ->assertOk()
            ->assertJson([
                'success' => false,
                'message' => 'No se encontró una mesa asociada a este código QR.',
            ])
            ->assertJsonMissingPath('table');
    }

    public function test_a_code_pointing_to_a_dish_is_not_accepted_as_a_table(): void
    {
        $table = Table::factory()->create();
        $qrCode = QRCode::factory()->for($table)->create();

        $this->actingAs(User::factory()->waiter()->create())
            ->postJson(route('waiter.qr.resolve'), [
                'code' => MenuUrl::menu($qrCode->code).'/plato/1',
            ])
            ->assertOk()
            ->assertJsonPath('success', false);
    }

    public function test_the_resolver_requires_a_code(): void
    {
        $this->actingAs(User::factory()->waiter()->create())
            ->postJson(route('waiter.qr.resolve'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_the_waiter_dashboard_offers_the_scanner(): void
    {
        $this->actingAs(User::factory()->waiter()->create())
            ->get(route('waiter.dashboard'))
            ->assertOk()
            ->assertSee('Escanear QR')
            ->assertSee(route('waiter.qr.resolve'), false);
    }

    public function test_an_unknown_code_on_the_order_form_sends_the_waiter_back(): void
    {
        $this->actingAs(User::factory()->waiter()->create())
            ->get(route('waiter.orders.create', ['codigo' => 'no-existe']))
            ->assertRedirect(route('waiter.dashboard'))
            ->assertSessionHas('error');
    }

    public function test_an_order_taken_from_the_scanner_belongs_to_its_table_and_to_the_waiter(): void
    {
        $table = Table::factory()->create(['numero' => '5']);
        $qrCode = QRCode::factory()->for($table)->create();
        $otra = Table::factory()->create(['numero' => '9']);
        $dish = Dish::factory()->create();

        $mozo = User::factory()->waiter()->create(['name' => 'Juan']);

        $this->actingAs($mozo)
            ->post(route('waiter.orders.store'), [
                'codigo_qr' => $qrCode->code,
                'items' => [(string) $dish->id => ['cantidad' => 2, 'observaciones' => 'sin sal']],
            ])
            ->assertRedirect();

        $order = Order::sole();

        $this->assertTrue($order->table->is($table));
        $this->assertTrue($order->user->is($mozo));
        $this->assertSame(TableStatus::Ocupada, $table->fresh()->estado);
        $this->assertSame(2, $order->items->sole()->cantidad);
        $this->assertSame('sin sal', $order->items->sole()->observaciones);
        $this->assertNotSame($otra->id, $order->table_id);
    }

    public function test_the_table_cannot_be_swapped_when_the_order_is_saved(): void
    {
        $table = Table::factory()->create(['numero' => '5']);
        $qrCode = QRCode::factory()->for($table)->create();
        $otra = Table::factory()->create(['numero' => '9']);
        $dish = Dish::factory()->create();

        $this->actingAs(User::factory()->waiter()->create())
            ->post(route('waiter.orders.store'), [
                'codigo_qr' => $qrCode->code,
                'table_id' => $otra->id,
                'items' => [(string) $dish->id => ['cantidad' => 1]],
            ]);

        $order = Order::sole();

        $this->assertTrue($order->table->is($table));
        $this->assertSame(TableStatus::Libre, $otra->fresh()->estado);
    }

    public function test_an_order_cannot_be_saved_with_a_code_that_does_not_resolve(): void
    {
        $dish = Dish::factory()->create();

        $this->actingAs(User::factory()->waiter()->create())
            ->post(route('waiter.orders.store'), [
                'codigo_qr' => 'no-existe',
                'items' => [(string) $dish->id => ['cantidad' => 1]],
            ])
            ->assertSessionHasErrors('codigo_qr');

        $this->assertSame(0, Order::count());
    }

    public function test_the_waiter_of_an_order_cannot_be_forged_from_the_request(): void
    {
        $table = Table::factory()->create();
        $dish = Dish::factory()->create();
        $otro = User::factory()->waiter()->create();
        $mozo = User::factory()->waiter()->create();

        $this->actingAs($mozo)
            ->post(route('waiter.orders.store'), [
                'table_id' => $table->id,
                'user_id' => $otro->id,
                'items' => [(string) $dish->id => ['cantidad' => 1]],
            ]);

        $order = Order::sole();

        $this->assertTrue($order->user->is($mozo));
        $this->assertFalse($order->user->is($otro));
    }

    public function test_the_order_detail_names_the_waiter_who_took_it(): void
    {
        $order = Order::factory()->create();
        $order->update(['user_id' => User::factory()->waiter()->create(['name' => 'Juan'])->id]);

        $this->actingAs(User::factory()->waiter()->create())
            ->get(route('waiter.orders.show', $order))
            ->assertOk()
            ->assertSee('Juan');
    }

    public function test_dish_observations_are_optional(): void
    {
        $table = Table::factory()->create();
        $dish = Dish::factory()->create();

        $this->actingAs(User::factory()->waiter()->create())
            ->post(route('waiter.orders.store'), [
                'table_id' => $table->id,
                'items' => [(string) $dish->id => ['cantidad' => 1, 'observaciones' => '  ']],
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull(OrderItem::sole()->observaciones);
    }
}
