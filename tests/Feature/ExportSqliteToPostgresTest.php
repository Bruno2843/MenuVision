<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Dish;
use App\Models\QRCode;
use App\Models\Table;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The command that lifts the local SQLite data into PostgreSQL is a one-off
 * administrative tool, and the things worth pinning are exactly the ones that
 * make it safe to run: it writes nothing unless asked, it never overwrites a
 * row, it keeps ids and the relations between rows, it respects the foreign key
 * order, it refuses to start when the destination is not prepared, and running
 * it twice changes nothing.
 *
 * PostgreSQL cannot run in the normal suite, so these tests connect to a real
 * `pgsql` destination supplied through the environment and skip when there is
 * none. That keeps the suite green on a developer machine (and in CI) while
 * still exercising the real driver wherever one is pointed at it.
 *
 * The source is the usual in-memory SQLite of the suite, reached through the
 * `sqlite_local` connection so the destination `DB_*` variables cannot leak into
 * it.
 */
class ExportSqliteToPostgresTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The destination connection, or null when no PostgreSQL is reachable.
     */
    private ?ConnectionInterface $destino = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->destino = $this->conectarDestino();

        if ($this->destino === null) {
            $this->markTestSkipped(
                'Sin PostgreSQL: definí EXPORT_TEST_PG_HOST, EXPORT_TEST_PG_PORT, EXPORT_TEST_PG_DATABASE '
                .'y EXPORT_TEST_PG_USERNAME para ejecutar estas pruebas.'
            );
        }

        $this->prepararDestino();
        $this->apuntarOrigenALaBaseEnMemoria();
    }

    protected function tearDown(): void
    {
        if ($this->destino !== null) {
            $this->destino->disconnect();
        }

        parent::tearDown();
    }

    public function test_the_default_run_is_a_dry_run_and_writes_nothing(): void
    {
        $this->sembrarUnPlatoConRelaciones();

        $this->artisan('menuvision:export-sqlite-to-postgres', [
            '--source' => 'sqlite_local',
            '--to' => 'export_pgsql_test',
        ])->assertSuccessful();

        // No row reached the destination.
        $this->assertSame(0, $this->destino->table('dishes')->count());
        $this->assertSame(0, $this->destino->table('categories')->count());
    }

    public function test_it_moves_the_rows_preserving_ids_and_relations(): void
    {
        [$plato, $categoria, $mesa] = $this->sembrarUnPlatoConRelaciones();

        $this->artisan('menuvision:export-sqlite-to-postgres', [
            '--source' => 'sqlite_local',
            '--to' => 'export_pgsql_test',
            '--write' => true,
        ])->assertSuccessful();

        $fila = $this->destino->table('dishes')->where('id', $plato->id)->first();

        $this->assertNotNull($fila);
        $this->assertSame($categoria->id, (int) $fila->category_id);
        $this->assertSame($plato->nombre, $fila->nombre);
        $this->assertSame(1, $this->destino->table('categories')->where('id', $categoria->id)->count());
        $this->assertSame(1, $this->destino->table('tables')->where('id', $mesa->id)->count());
        $this->assertSame(1, $this->destino->table('qr_codes')->count());
    }

    public function test_the_foreign_key_of_every_imported_row_points_at_a_row_that_exists(): void
    {
        $this->sembrarUnPlatoConRelaciones();

        $this->artisan('menuvision:export-sqlite-to-postgres', [
            '--source' => 'sqlite_local',
            '--to' => 'export_pgsql_test',
            '--write' => true,
        ])->assertSuccessful();

        // The whole point of the dependency order: by the time `dishes` is
        // inserted, `categories` is already there. A row whose parent was
        // missing would have raised on insert.
        $this->assertSame(0, $this->destino->table('order_items')->count());
        $this->assertSame(
            0,
            $this->destino->table('model3ds')
                ->leftJoin('dishes', 'dishes.id', '=', 'model3ds.dish_id')
                ->whereNull('dishes.id')
                ->count()
        );
    }

    public function test_running_it_twice_inserts_nothing_the_second_time(): void
    {
        $this->sembrarUnPlatoConRelaciones();

        $parametros = [
            '--source' => 'sqlite_local',
            '--to' => 'export_pgsql_test',
            '--write' => true,
        ];

        $this->artisan('menuvision:export-sqlite-to-postgres', $parametros)->assertSuccessful();

        $antes = $this->destino->table('dishes')->count();

        // Second run: every row matches, so the counts stay put. This is what
        // makes a failed run safe to retry.
        $this->artisan('menuvision:export-sqlite-to-postgres', $parametros)
            ->expectsOutputToContain('coinciden')
            ->assertSuccessful();

        $this->assertSame($antes, $this->destino->table('dishes')->count());
        $this->assertSame(1, $this->destino->table('dishes')->where('id', 1)->count());
    }

    public function test_a_conflicting_row_stops_the_run_without_overwriting_anything(): void
    {
        [$plato] = $this->sembrarUnPlatoConRelaciones();

        // A row already sits on the destination id with different data.
        $this->destino->table('categories')->insert([
            'id' => $plato->category_id,
            'nombre' => 'Otro nombre',
            'descripcion' => null,
            'orden' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('menuvision:export-sqlite-to-postgres', [
            '--source' => 'sqlite_local',
            '--to' => 'export_pgsql_test',
            '--write' => true,
        ])->assertFailed();

        // The conflicting row is untouched and nothing else was inserted.
        $this->assertSame('Otro nombre', $this->destino->table('categories')->where('id', $plato->category_id)->value('nombre'));
        $this->assertSame(0, $this->destino->table('dishes')->count());
    }

    public function test_a_row_with_the_same_id_and_data_is_a_match_and_not_a_conflict(): void
    {
        $categoria = Category::factory()->create(['nombre' => 'Pizzas']);

        // Identical data already present, written through the destination.
        $this->destino->table('categories')->insert([
            'id' => $categoria->id,
            'nombre' => 'Pizzas',
            'descripcion' => $categoria->descripcion,
            'orden' => $categoria->orden,
            'created_at' => $categoria->created_at,
            'updated_at' => $categoria->updated_at,
        ]);

        $this->artisan('menuvision:export-sqlite-to-postgres', [
            '--source' => 'sqlite_local',
            '--to' => 'export_pgsql_test',
            '--write' => true,
        ])->assertSuccessful();

        $this->assertSame(1, $this->destino->table('categories')->where('id', $categoria->id)->count());
    }

    public function test_it_refuses_to_run_when_the_destination_is_not_migrated(): void
    {
        Schema::connection('export_pgsql_test')->dropAllTables();

        $this->artisan('menuvision:export-sqlite-to-postgres', [
            '--source' => 'sqlite_local',
            '--to' => 'export_pgsql_test',
            '--write' => true,
        ])->expectsOutputToContain('no tiene estas tablas')->assertFailed();
    }

    public function test_it_refuses_a_destination_that_is_not_postgresql(): void
    {
        $this->artisan('menuvision:export-sqlite-to-postgres', [
            '--source' => 'sqlite_local',
            '--to' => 'sqlite',
        ])->expectsOutputToContain('PostgreSQL')->assertFailed();
    }

    /**
     * A dish with the rows it depends on, so all the relations are exercised.
     *
     * @return array{0: Dish, 1: Category, 2: Table}
     */
    private function sembrarUnPlatoConRelaciones(): array
    {
        $categoria = Category::factory()->create(['nombre' => 'Pizzas']);
        $plato = Dish::factory()->for($categoria)->create(['nombre' => 'Napolitana']);
        $mesa = Table::factory()->create();
        QRCode::factory()->for($mesa)->create();

        return [$plato, $categoria, $mesa];
    }

    /**
     * Register the test connections once, from the environment.
     */
    private function conectarDestino(): ?ConnectionInterface
    {
        $host = env('EXPORT_TEST_PG_HOST');

        if (blank($host)) {
            return null;
        }

        config([
            'database.connections.export_pgsql_test' => [
                'driver' => 'pgsql',
                'host' => $host,
                'port' => env('EXPORT_TEST_PG_PORT', '5432'),
                'database' => env('EXPORT_TEST_PG_DATABASE', 'postgres'),
                'username' => env('EXPORT_TEST_PG_USERNAME', 'postgres'),
                'password' => env('EXPORT_TEST_PG_PASSWORD', ''),
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
                'search_path' => 'public',
                'sslmode' => env('EXPORT_TEST_PG_SSLMODE', 'prefer'),
            ],
        ]);

        try {
            $conexion = DB::connection('export_pgsql_test');
            $conexion->getPdo();
        } catch (\Throwable) {
            return null;
        }

        return $conexion;
    }

    /**
     * A clean destination with the real schema, so ids and sequences exist.
     */
    private function prepararDestino(): void
    {
        $this->destino->statement('drop schema if exists public cascade');
        $this->destino->statement('create schema public');

        $this->artisan('migrate', [
            '--database' => 'export_pgsql_test',
            '--force' => true,
        ])->assertSuccessful();
    }

    /**
     * Point `sqlite_local` at the same in-memory database the suite uses, so the
     * source has the rows the factories just wrote.
     */
    private function apuntarOrigenALaBaseEnMemoria(): void
    {
        config([
            'database.connections.sqlite_local.database' => ':memory:',
        ]);

        // The suite's `sqlite` connection owns the in-memory database. The PDO
        // handle has to be shared, or `sqlite_local` would open a second, empty
        // in-memory database and read nothing.
        $this->app['db']->purge('sqlite_local');
        $this->app['db']->extend('sqlite_local', function () {
            return DB::connection('sqlite');
        });
    }
}
