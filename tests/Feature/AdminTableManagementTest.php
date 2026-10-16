<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Models\Order;
use App\Models\QRCode;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A table and its QR code are one thing from the client's point of view: the
 * code is the only way a customer reaches the menu for that table, so the two
 * rows are always created together and a code is never quietly invalidated.
 */
class AdminTableManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_a_table_is_created_with_a_usable_qr_code(): void
    {
        $this->actingAs($this->admin())->post(route('admin.tables.store'), [
            'numero' => '7',
        ])->assertRedirect(route('admin.tables.index'))
            ->assertSessionHas('status');

        $mesa = Table::sole();
        $qr = QRCode::sole();

        $this->assertSame('7', $mesa->numero);
        $this->assertSame($mesa->id, $qr->table_id);
        $this->assertSame(16, strlen($qr->code));
    }

    public function test_the_qr_code_opens_the_public_menu_of_that_table(): void
    {
        $this->actingAs($this->admin())->post(route('admin.tables.store'), ['numero' => '7']);

        $qr = QRCode::sole();

        $this->get(route('qr.menu', $qr->code))->assertOk();
    }

    public function test_two_tables_never_share_a_code(): void
    {
        $this->actingAs($this->admin())->post(route('admin.tables.store'), ['numero' => '1']);
        $this->actingAs($this->admin())->post(route('admin.tables.store'), ['numero' => '2']);

        $codes = QRCode::query()->pluck('code');

        $this->assertCount(2, $codes);
        $this->assertCount(2, $codes->unique());
    }

    public function test_the_number_is_required(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.tables.store'), ['numero' => ''])
            ->assertSessionHasErrors('numero');

        $this->assertModelMissing(Table::class);
        $this->assertModelMissing(QRCode::class);
    }

    public function test_two_tables_cannot_share_a_number(): void
    {
        Table::factory()->create(['numero' => '7']);

        $this->actingAs($this->admin())
            ->post(route('admin.tables.store'), ['numero' => '7'])
            ->assertSessionHasErrors('numero');

        $this->assertSame(1, Table::query()->count());
    }

    public function test_a_table_can_be_renumbered(): void
    {
        $mesa = Table::factory()->create(['numero' => '7']);

        $this->actingAs($this->admin())
            ->put(route('admin.tables.update', $mesa), ['numero' => '8'])
            ->assertRedirect(route('admin.tables.index'));

        $this->assertSame('8', $mesa->fresh()->numero);
    }

    public function test_a_table_keeps_its_code_while_being_renumbered(): void
    {
        $mesa = Table::factory()->create(['numero' => '7']);
        $qr = QRCode::factory()->for($mesa)->create();

        $this->actingAs($this->admin())
            ->put(route('admin.tables.update', $mesa), ['numero' => '8']);

        $this->assertSame($qr->code, $mesa->fresh()->qrCode->code);
    }

    public function test_renewing_the_code_replaces_it(): void
    {
        $mesa = Table::factory()->create(['numero' => '7']);
        $qr = QRCode::factory()->for($mesa)->create(['code' => 'codigo-viejo-123456']);
        $anterior = $qr->code;

        $this->actingAs($this->admin())
            ->from(route('admin.tables.index'))
            ->post(route('admin.tables.qr.regenerate', $mesa))
            ->assertRedirect(route('admin.tables.index'))
            ->assertSessionHas('status');

        $this->assertNotSame($anterior, $qr->fresh()->code);
        $this->assertSame(1, QRCode::query()->count());
    }

    public function test_renewing_the_code_retires_the_old_sticker(): void
    {
        $mesa = Table::factory()->create();
        $anterior = QRCode::factory()->for($mesa)->create(['code' => 'codigo-viejo-123456'])->code;

        $this->actingAs($this->admin())->post(route('admin.tables.qr.regenerate', $mesa));

        $this->get(route('qr.menu', $anterior))->assertNotFound();
    }

    public function test_a_table_without_a_code_is_given_one_on_demand(): void
    {
        $mesa = Table::factory()->create();

        $this->assertNull($mesa->qrCode);

        $this->actingAs($this->admin())
            ->post(route('admin.tables.qr.regenerate', $mesa))
            ->assertRedirect(route('admin.tables.index'));

        $this->assertNotNull($mesa->fresh()->qrCode);
    }

    public function test_an_unused_table_is_deleted_with_its_code(): void
    {
        $mesa = Table::factory()->create();
        QRCode::factory()->for($mesa)->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.tables.destroy', $mesa))
            ->assertRedirect(route('admin.tables.index'))
            ->assertSessionHas('status');

        $this->assertModelMissing($mesa);
        $this->assertModelMissing(QRCode::class);
    }

    public function test_a_table_with_orders_is_kept_so_the_history_stays_readable(): void
    {
        $mesa = Table::factory()->create(['numero' => '7', 'estado' => TableStatus::Ocupada]);
        QRCode::factory()->for($mesa)->create();
        Order::factory()->for($mesa)->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.tables.destroy', $mesa))
            ->assertSessionHas('error');

        $this->assertModelExists($mesa);
        $this->assertModelExists($mesa->qrCode);
    }

    public function test_the_table_state_is_not_editable_by_hand(): void
    {
        $mesa = Table::factory()->create(['estado' => TableStatus::Libre]);

        $this->actingAs($this->admin())->put(route('admin.tables.update', $mesa), [
            'numero' => $mesa->numero,
            'estado' => TableStatus::Ocupada->value,
        ])->assertRedirect(route('admin.tables.index'));

        $this->assertSame(TableStatus::Libre, $mesa->fresh()->estado);
    }

    public function test_the_list_shows_the_table_with_its_code_and_order_count(): void
    {
        $mesa = Table::factory()->create(['numero' => '7']);
        $qr = QRCode::factory()->for($mesa)->create();
        Order::factory()->for($mesa)->create(['estado' => OrderStatus::Entregado]);
        Order::factory()->for($mesa)->create(['estado' => OrderStatus::Pendiente]);

        $this->actingAs($this->admin())
            ->get(route('admin.tables.index'))
            ->assertOk()
            ->assertSee('7')
            ->assertSee(route('qr.menu', $qr->code))
            ->assertSee('2');
    }

    public function test_the_table_number_survives_the_form(): void
    {
        $mesa = Table::factory()->create(['numero' => '7']);

        $this->actingAs($this->admin())
            ->get(route('admin.tables.edit', $mesa))
            ->assertOk()
            ->assertSee('value="7"', false);

        $this->actingAs($this->admin())->get(route('admin.tables.create'))->assertOk();
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }
}
