<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Accounts are deactivated and never deleted, because an order has to keep
 * pointing at whoever took it. The rules that matter here are the ones that stop
 * an administrator from locking everybody out of the installation.
 */
class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_the_list_shows_every_account_with_its_role_and_state(): void
    {
        User::factory()->waiter()->create(['name' => 'Camila Sosa', 'role' => Role::Mozo]);
        User::factory()->waiter()->deactivated()->create(['name' => 'Nico Ríos']);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Camila Sosa')
            ->assertSee('Nico Ríos')
            ->assertSee('Desactivado')
            ->assertSee(route('admin.users.edit', $admin));
    }

    public function test_an_account_is_created_with_a_hashed_password_and_the_chosen_role(): void
    {
        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'Camila Sosa',
            'email' => 'camila@example.com',
            'password' => 'una-clave-larga',
            'password_confirmation' => 'una-clave-larga',
            'role' => Role::Mozo->value,
            'activo' => 1,
        ])->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('status');

        $creada = User::where('email', 'camila@example.com')->sole();

        $this->assertSame(Role::Mozo, $creada->role);
        $this->assertTrue($creada->estaActivo());
        $this->assertNotSame('una-clave-larga', $creada->password);
        $this->assertTrue(Hash::check('una-clave-larga', $creada->password));
    }

    public function test_the_new_account_can_sign_in_with_the_password_it_was_given(): void
    {
        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'Camila Sosa',
            'email' => 'camila@example.com',
            'password' => 'una-clave-larga',
            'password_confirmation' => 'una-clave-larga',
            'role' => Role::Cocina->value,
            'activo' => 1,
        ]);

        $this->post(route('login.store'), [
            'email' => 'camila@example.com',
            'password' => 'una-clave-larga',
        ])->assertRedirect(route('kitchen.dashboard'));
    }

    public function test_the_email_must_be_unique(): void
    {
        User::factory()->waiter()->create(['email' => 'camila@example.com']);

        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'Otra persona',
            'email' => 'camila@example.com',
            'password' => 'una-clave-larga',
            'password_confirmation' => 'una-clave-larga',
            'role' => Role::Mozo->value,
        ])->assertSessionHasErrors('email');

        $this->assertSame(1, User::where('email', 'camila@example.com')->count());
    }

    public function test_the_password_has_to_be_confirmed(): void
    {
        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'Camila Sosa',
            'email' => 'camila@example.com',
            'password' => 'una-clave-larga',
            'password_confirmation' => 'otra-clave-distinta',
            'role' => Role::Mozo->value,
        ])->assertSessionHasErrors('password');

        $this->assertFalse(User::where('email', 'camila@example.com')->exists());
    }

    public function test_the_role_has_to_be_a_real_one(): void
    {
        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'Camila Sosa',
            'email' => 'camila@example.com',
            'password' => 'una-clave-larga',
            'password_confirmation' => 'una-clave-larga',
            'role' => 'jefe',
        ])->assertSessionHasErrors('role');
    }

    public function test_an_account_created_as_inactive_does_not_receive_a_session(): void
    {
        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'Camila Sosa',
            'email' => 'camila@example.com',
            'password' => 'una-clave-larga',
            'password_confirmation' => 'una-clave-larga',
            'role' => Role::Mozo->value,
            'activo' => 0,
        ]);

        $this->assertFalse(User::where('email', 'camila@example.com')->sole()->estaActivo());

        $this->post(route('login.store'), [
            'email' => 'camila@example.com',
            'password' => 'una-clave-larga',
        ])->assertSessionHasErrors('email');
    }

    public function test_the_role_can_be_changed_without_touching_the_password(): void
    {
        $mozo = User::factory()->waiter()->create(['email' => 'camila@example.com']);
        $claveOriginal = $mozo->password;

        $this->actingAs($this->admin())->put(route('admin.users.update', $mozo), [
            'name' => 'Camila Sosa',
            'email' => 'camila@example.com',
            'role' => Role::Cocina->value,
            'activo' => 1,
        ])->assertRedirect(route('admin.users.index'));

        $mozo->refresh();

        $this->assertSame(Role::Cocina, $mozo->role);
        $this->assertSame($claveOriginal, $mozo->password);
    }

    public function test_the_password_can_be_reset_from_the_same_form(): void
    {
        $mozo = User::factory()->waiter()->create(['email' => 'camila@example.com']);

        $this->actingAs($this->admin())->put(route('admin.users.update', $mozo), [
            'name' => $mozo->name,
            'email' => 'camila@example.com',
            'password' => 'una-clave-nueva',
            'password_confirmation' => 'una-clave-nueva',
            'role' => Role::Mozo->value,
            'activo' => 1,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertTrue(Hash::check('una-clave-nueva', $mozo->fresh()->password));

        $this->post(route('login.store'), [
            'email' => 'camila@example.com',
            'password' => 'una-clave-nueva',
        ])->assertRedirect(route('waiter.dashboard'));
    }

    public function test_deactivating_an_account_blocks_it_and_reactivating_restores_it(): void
    {
        $admin = $this->admin();
        $mozo = User::factory()->waiter()->create();

        $this->actingAs($admin)->put(route('admin.users.update', $mozo), [
            'name' => $mozo->name,
            'email' => $mozo->email,
            'role' => Role::Mozo->value,
            'activo' => 0,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertFalse($mozo->fresh()->estaActivo());

        $this->post(route('login.store'), ['email' => $mozo->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->actingAs($admin)->put(route('admin.users.update', $mozo), [
            'name' => $mozo->name,
            'email' => $mozo->email,
            'role' => Role::Mozo->value,
            'activo' => 1,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertTrue($mozo->fresh()->estaActivo());
    }

    public function test_an_administrator_cannot_deactivate_their_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => Role::Administrador->value,
            'activo' => 0,
        ])->assertSessionHas('error');

        $this->assertTrue($admin->fresh()->estaActivo());
    }

    public function test_the_last_active_administrator_cannot_be_deactivated(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => Role::Administrador->value,
            'activo' => 0,
        ])->assertSessionHas('error');

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => Role::Mozo->value,
            'activo' => 1,
        ])->assertSessionHas('error');

        $admin->refresh();

        $this->assertSame(Role::Administrador, $admin->role);
        $this->assertTrue($admin->estaActivo());
    }

    public function test_an_administrator_may_hand_their_role_to_someone_else(): void
    {
        $admin = $this->admin();
        User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => Role::Mozo->value,
            'activo' => 1,
        ])->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('status');

        $this->assertSame(Role::Mozo, $admin->fresh()->role);

        // Signing in again lands on the panel the new role belongs to, which is
        // the whole point of the change taking effect.
        $this->post(route('logout'));
        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('waiter.dashboard'));

        $this->get(route('admin.dashboard'))->assertRedirect(route('waiter.dashboard'));
    }

    public function test_another_administrator_may_be_demoted_while_one_remains(): void
    {
        $admin = $this->admin();
        $otro = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.users.update', $otro), [
            'name' => $otro->name,
            'email' => $otro->email,
            'role' => Role::Cocina->value,
            'activo' => 1,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertSame(Role::Cocina, $otro->fresh()->role);
    }

    public function test_a_deactivated_administrator_does_not_count_as_a_safety_net(): void
    {
        $admin = $this->admin();
        $apagado = User::factory()->admin()->deactivated()->create();

        // The only other administrator cannot sign in, so the one in front of the
        // screen still may not switch their own account off.
        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => Role::Mozo->value,
            'activo' => 0,
        ])->assertSessionHas('error');

        $this->assertTrue($admin->fresh()->estaActivo());

        // Handing the role to the deactivated account is still allowed, and that
        // is how a locked-out installation gets fixed.
        $this->actingAs($admin)->put(route('admin.users.update', $apagado), [
            'name' => $apagado->name,
            'email' => $apagado->email,
            'role' => Role::Administrador->value,
            'activo' => 1,
        ])->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('status');
    }

    public function test_there_is_no_way_to_delete_an_account(): void
    {
        $mozo = User::factory()->waiter()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.users.index', $mozo))
            ->assertStatus(405);

        $this->assertModelExists($mozo);
    }

    public function test_the_edit_form_prefills_the_account(): void
    {
        $admin = $this->admin();
        $mozo = User::factory()->waiter()->create(['name' => 'Camila Sosa', 'email' => 'camila@example.com']);

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $mozo))
            ->assertOk()
            ->assertSee('Camila Sosa')
            ->assertSee('camila@example.com')
            ->assertSee('value="mozo" selected', false);
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }
}
