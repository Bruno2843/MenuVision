<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Copy the local SQLite database into a PostgreSQL database, table by table, in
 * foreign key order.
 *
 * This is a one-off administrative tool for the move to production, and it is
 * deliberately not part of boot, deployment or `migrate`. It is meant to be run
 * by hand, dry-first, against a destination that has already been migrated.
 *
 * What it will not do, on purpose:
 *
 * - It never updates, deletes, truncates, or replaces a row. A row that already
 *   exists with the same id and the same values is reported as a match and
 *   skipped. A row whose id is taken by different data is a conflict, and a
 *   conflict stops the run: guessing which side wins is how a migration silently
 *   corrupts the database.
 * - It never touches the default connection. The source is always SQLite, the
 *   destination is the connection passed in `--to`, and the app's `DB_CONNECTION`
 *   is left alone.
 * - It never resets sequences downward. See `sincronizarSecuencias()`.
 *
 * Because every id is preserved and nothing is ever updated, the command is
 * idempotent: running it twice inserts nothing the second time and reports the
 * first run's inserts as matches.
 */
#[Signature('menuvision:export-sqlite-to-postgres
    {--to=pgsql : Destination connection name, as defined in config/database.php}
    {--source=sqlite_local : Source connection name; must be the SQLite one}
    {--dry-run : Report what would happen and write nothing (the default when omitted)}
    {--write : Actually perform the inserts, inside one transaction}
    {--include-framework : Also carry over sessions, cache and jobs tables}
    {--batch=500 : Rows read and inserted per batch}')]
#[Description('Copies the local SQLite data into a PostgreSQL database without overwriting anything')]
class ExportSqliteToPostgres extends Command
{
    /**
     * Tables in dependency order. Parents come before the children that
     * reference them, so a foreign key is always satisfied by the time the
     * child row is inserted.
     *
     * Keyed by table name; the value is the column used as the primary key.
     * The three framework tables with a string primary key are listed for
     * completeness but skipped by default: `sessions` and `cache` hold state
     * that should be rebuilt, not carried over. They can be included with
     * `--include-framework`.
     */
    private const TABLAS = [
        'users' => 'id',
        'tables' => 'id',
        'ingredients' => 'id',
        'categories' => 'id',
        'dishes' => 'id',
        'orders' => 'id',
        'order_items' => 'id',
        'model3ds' => 'id',
        'dish_ingredient' => 'id',
        'qr_codes' => 'id',
    ];

    /**
     * Tables that are skipped unless explicitly requested.
     */
    private const TABLAS_DE_INFRAESTRUCTURA = [
        'sessions' => 'id',
        'cache' => 'key',
        'cache_locks' => 'key',
    ];

    /**
     * Run the command.
     */
    public function handle(): int
    {
        $origen = (string) $this->option('source');
        $destino = (string) $this->option('to');
        $lote = max(1, (int) $this->option('batch'));

        $esSimulacion = ! $this->option('write');

        if ($this->option('dry-run') && $this->option('write')) {
            $this->error('Usá --dry-run o --write, no las dos.');

            return self::FAILURE;
        }

        if (! config("database.connections.{$origen}")) {
            $this->error("No existe la conexión de origen «{$origen}» en config/database.php.");

            return self::FAILURE;
        }

        if (! config("database.connections.{$destino}")) {
            $this->error("No existe la conexión de destino «{$destino}» en config/database.php.");

            return self::FAILURE;
        }

        $this->describir($origen, $destino, $esSimulacion);

        try {
            $conexionOrigen = DB::connection($origen);
            $conexionDestino = DB::connection($destino);
        } catch (Throwable $e) {
            $this->error('No se pudo abrir una de las conexiones: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($conexionOrigen->getDriverName() !== 'sqlite') {
            $this->error('La conexión de origen debe ser SQLite; se recibió «'.$conexionOrigen->getDriverName().'».');

            return self::FAILURE;
        }

        if ($conexionDestino->getDriverName() !== 'pgsql') {
            $this->error('La conexión de destino debe ser PostgreSQL; se recibió «'.$conexionDestino->getDriverName().'».');

            return self::FAILURE;
        }

        $tablas = self::TABLAS;

        if ($this->option('include-framework')) {
            $tablas = array_merge($tablas, self::TABLAS_DE_INFRAESTRUCTURA);
        }

        $faltantes = $this->tablasFaltantes($conexionDestino, $tablas);

        if ($faltantes !== []) {
            $this->error('El destino no tiene estas tablas: '.implode(', ', $faltantes).'.');
            $this->line('Ejecutá primero las migraciones sobre la conexión de destino.');

            return self::FAILURE;
        }

        return $esSimulacion
            ? $this->simular($conexionOrigen, $conexionDestino, $tablas, $lote)
            : $this->escribir($conexionOrigen, $conexionDestino, $tablas, $lote);
    }

    /**
     * Dry run: read both sides and report, writing nothing at all.
     *
     * @param  array<string, string>  $tablas
     */
    private function simular(ConnectionInterface $origen, ConnectionInterface $destino, array $tablas, int $lote): int
    {
        $this->line('');
        $this->line('  <fg=yellow>Simulación</> — no se escribe nada. Agregá <fg=green>--write</> para importar.');

        $resumen = [];

        foreach ($tablas as $tabla => $clave) {
            $resumen[$tabla] = $this->procesarTabla($origen, $destino, $tabla, $clave, $lote, escribir: false);
        }

        $this->resumen($resumen);
        $this->line('');

        return self::SUCCESS;
    }

    /**
     * Real run: the same checks, but the inserts happen inside a single
     * transaction on the destination, so a conflict halfway through rolls the
     * whole thing back instead of leaving a partial import behind.
     *
     * @param  array<string, string>  $tablas
     */
    private function escribir(ConnectionInterface $origen, ConnectionInterface $destino, array $tablas, int $lote): int
    {
        $this->line('');
        $this->line('  <fg=red>Importación real</> — las filas se insertan de verdad.');

        $resumen = [];

        try {
            $destino->transaction(function () use ($origen, $destino, $tablas, $lote, &$resumen): void {
                foreach ($tablas as $tabla => $clave) {
                    $resumen[$tabla] = $this->procesarTabla($origen, $destino, $tabla, $clave, $lote, escribir: true);
                }
            });
        } catch (Throwable $e) {
            $this->error('');
            $this->error('La importación se detuvo y se revirtió por completo: '.$e->getMessage());
            $this->line('No se modificó ninguna fila del destino.');

            return self::FAILURE;
        }

        $this->resumen($resumen);
        $this->sincronizarSecuencias($destino, $tablas);
        $this->line('');

        return self::SUCCESS;
    }

    /**
     * Walk one table and either count or insert.
     *
     * The source is read in pages by primary key so a large table does not have
     * to fit in memory. For each source row the existing destination row with
     * the same id is looked up: identical data is a match, different data is a
     * conflict and raises, which aborts the run in both modes before anything is
     * written.
     *
     * @return array{origen: int, coincidencias: int, inserciones: int}
     */
    private function procesarTabla(
        ConnectionInterface $origen,
        ConnectionInterface $destino,
        string $tabla,
        string $clave,
        int $lote,
        bool $escribir,
    ): array {
        $columnas = $this->columnasComunes($origen, $destino, $tabla);

        $total = 0;
        $coincidencias = 0;
        $inserciones = 0;
        $filasNuevas = [];

        foreach ($origen->table($tabla)->orderBy($clave)->lazyById($lote, $clave) as $fila) {
            $total++;
            $datos = $this->normalizar((array) $fila, $columnas);

            $existente = $destino->table($tabla)->where($clave, $datos[$clave])->first();

            if ($existente !== null) {
                $this->verificarCoincidencia($tabla, $datos, (array) $existente, $columnas);
                $coincidencias++;

                continue;
            }

            $inserciones++;
            $filasNuevas[] = $datos;

            if ($escribir && count($filasNuevas) >= $lote) {
                $destino->table($tabla)->insert($filasNuevas);
                $filasNuevas = [];
            }
        }

        if ($escribir && $filasNuevas !== []) {
            $destino->table($tabla)->insert($filasNuevas);
        }

        $this->line(sprintf(
            '  %-16s %5d en origen   %5d coinciden   %5d %s',
            $tabla,
            $total,
            $coincidencias,
            $inserciones,
            $escribir ? 'insertados' : 'a insertar',
        ));

        return ['origen' => $total, 'coincidencias' => $coincidencias, 'inserciones' => $inserciones];
    }

    /**
     * Refuse to import when an id is already taken by a different row.
     *
     * @param  array<string, mixed>  $datos
     * @param  array<string, mixed>  $existente
     * @param  list<string>  $columnas
     *
     * @throws Throwable
     */
    private function verificarCoincidencia(string $tabla, array $datos, array $existente, array $columnas): void
    {
        foreach ($columnas as $columna) {
            $esperado = $this->comparable($datos[$columna] ?? null);
            $actual = $this->comparable($existente[$columna] ?? null);

            if ($esperado !== $actual) {
                throw new RuntimeException(
                    "Conflicto en «{$tabla}»: el id {$datos[$this->claveDe($tabla)]} ya existe con un valor distinto "."en «{$columna}» ({$this->resumir($actual)} en el destino, {$this->resumir($esperado)} en el origen). "
                    .'No se sobrescribe ningún registro.'
                );
            }
        }
    }

    /**
     * Columns present on both sides. A column that only exists in one database
     * is left out rather than inserted as null, which would happen if the
     * destination were running older migrations.
     *
     * @return list<string>
     */
    private function columnasComunes(ConnectionInterface $origen, ConnectionInterface $destino, string $tabla): array
    {
        $deOrigen = $origen->getSchemaBuilder()->getColumnListing($tabla);
        $deDestino = $destino->getSchemaBuilder()->getColumnListing($tabla);

        return array_values(array_intersect($deOrigen, $deDestino));
    }

    /**
     * Map a raw row to the exact column list, so both databases are compared and
     * inserted with the same keys.
     *
     * @param  array<string, mixed>  $fila
     * @param  list<string>  $columnas
     * @return array<string, mixed>
     */
    private function normalizar(array $fila, array $columnas): array
    {
        $datos = [];

        foreach ($columnas as $columna) {
            $datos[$columna] = $fila[$columna] ?? null;
        }

        return $datos;
    }

    /**
     * A value reduced to something both drivers compare the same way.
     *
     * The two engines return the same logical value with different PHP types
     * (SQLite hands back "1" where PostgreSQL hands back true, and an integer
     * where PostgreSQL hands back a numeric string), so a raw comparison would
     * report a conflict on every boolean and decimal column.
     */
    private function comparable(mixed $valor): string
    {
        if ($valor === null) {
            return "\0null";
        }

        if (is_bool($valor)) {
            return $valor ? '1' : '0';
        }

        if (is_int($valor) || is_float($valor)) {
            return (string) $valor;
        }

        if (is_string($valor)) {
            $recortado = trim($valor);

            if ($recortado === '') {
                return "\0null";
            }

            if (strcasecmp($recortado, 'true') === 0) {
                return '1';
            }

            if (strcasecmp($recortado, 'false') === 0) {
                return '0';
            }

            // Numbers arrive as strings from both drivers. Comparing the numeric
            // value keeps `8500.00` equal to `8500` instead of a conflict.
            if (preg_match('/^-?\d+(\.\d+)?$/', $recortado) === 1) {
                return (string) (0 + $recortado);
            }

            return $recortado;
        }

        return json_encode($valor) ?: '';
    }

    /**
     * A short, non sensitive rendering of a value for a conflict message.
     */
    private function resumir(mixed $valor): string
    {
        if ($valor === "\0null") {
            return 'null';
        }

        $texto = Str::limit((string) $valor, 60);

        return '«'.$texto.'»';
    }

    private function claveDe(string $tabla): string
    {
        return self::TABLAS[$tabla] ?? self::TABLAS_DE_INFRAESTRUCTURA[$tabla];
    }

    /**
     * Which of the expected tables the destination is missing.
     *
     * @param  array<string, string>  $tablas
     * @return list<string>
     */
    private function tablasFaltantes(ConnectionInterface $destino, array $tablas): array
    {
        $esquema = $destino->getSchemaBuilder();
        $faltantes = [];

        foreach (array_keys($tablas) as $tabla) {
            if (! $esquema->hasTable($tabla)) {
                $faltantes[] = $tabla;
            }
        }

        return $faltantes;
    }

    /**
     * Move each PostgreSQL identity sequence past the highest id that now
     * exists.
     *
     * A sequence was never advanced by the inserts, because every id was written
     * explicitly. Left alone, the very next insert the app makes would pick id 1
     * and collide with a migrated row. `setval(..., max(id), true)` makes the
     * next value `max(id) + 1`.
     *
     * The sequence is only ever raised, never lowered: if it already sits above
     * the maximum id, raising it to that maximum would hand out ids that were
     * used before, so it is left where it is. This also makes the step safe to
     * repeat.
     *
     * @param  array<string, string>  $tablas
     */
    private function sincronizarSecuencias(ConnectionInterface $destino, array $tablas): void
    {
        $this->line('');
        $this->line('  Secuencias de id:');

        foreach ($tablas as $tabla => $clave) {
            if ($clave !== 'id') {
                continue;
            }

            if (! $destino->getSchemaBuilder()->hasTable($tabla)) {
                continue;
            }

            $secuencia = $destino->selectOne(
                "select pg_get_serial_sequence(?, 'id') as nombre",
                [$tabla],
            );

            $nombre = $secuencia->nombre ?? null;

            if ($nombre === null) {
                continue;
            }

            $maximo = (int) $destino->table($tabla)->max('id');

            if ($maximo === 0) {
                continue;
            }

            // `last_value` reflects the last value handed out; `is_called` says
            // whether it has been used yet.
            $estado = $destino->selectOne("select last_value, is_called from {$nombre}");
            $ultimo = (int) ($estado->last_value ?? 0);

            if ($ultimo >= $maximo) {
                $this->line(sprintf('  %-16s ya está en %d (máximo %d), sin cambios', $tabla, $ultimo, $maximo));

                continue;
            }

            $destino->statement("select setval('{$nombre}', {$maximo}, true)");

            $this->line(sprintf('  %-16s %d -> %d', $tabla, $ultimo, $maximo));
        }
    }

    /**
     * Print the totals for the whole run.
     *
     * @param  array<string, array{origen: int, coincidencias: int, inserciones: int}>  $resumen
     */
    private function resumen(array $resumen): void
    {
        $filas = [];
        $totalOrigen = $totalCoincidencias = $totalInserciones = 0;

        foreach ($resumen as $tabla => $cuenta) {
            $filas[] = [$tabla, $cuenta['origen'], $cuenta['coincidencias'], $cuenta['inserciones']];
            $totalOrigen += $cuenta['origen'];
            $totalCoincidencias += $cuenta['coincidencias'];
            $totalInserciones += $cuenta['inserciones'];
        }

        $this->line('');

        if ($filas !== []) {
            $this->table(['Tabla', 'Origen', 'Coinciden', 'Insertar'], $filas);
        }

        $this->line(sprintf(
            '  Total: %d en origen, %d coinciden, %d a insertar.',
            $totalOrigen,
            $totalCoincidencias,
            $totalInserciones,
        ));
    }

    /**
     * Name the two endpoints without printing any credential.
     */
    private function describir(string $origen, string $destino, bool $esSimulacion): void
    {
        $this->line('  MenuVision — exportación SQLite → PostgreSQL');
        $this->line(sprintf('  Modo:        %s', $esSimulacion ? 'simulación (--dry-run)' : 'escritura real (--write)'));
        $this->line(sprintf('  Origen:      %s', $origen));
        $this->line(sprintf('  Destino:     %s', $destino));
    }
}
