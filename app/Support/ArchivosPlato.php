<?php

namespace App\Support;

use App\Models\Dish;
use App\Models\Model3D;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Writes and removes the files a dish owns: its photograph and its 3D model.
 *
 * Both live on the `public` disk, under a folder shared by every dish and named
 * after the dish id, so one dish can never overwrite the file of another and
 * there is never more than one file per dish to keep track of.
 *
 * Nothing here touches the database except the model row of a 3D model. The
 * caller owns the transaction, and this class is deliberately split into "write
 * the file", "promote it once the row is committed" and "remove the file the row
 * used to point at" so that a file a committed row still references is never
 * overwritten or deleted before its replacement is recorded.
 */
class ArchivosPlato
{
    /**
     * Folder inside the `public` disk that holds the 3D models.
     */
    public const CARPETA_MODELOS = Model3D::CARPETA;

    /**
     * Park the photograph of a dish and return the path the row will reference.
     *
     * The file is named after the dish id rather than after the original
     * upload, so a dish called "../../config" cannot influence where anything is
     * written. The extension comes from the detected content type, not from the
     * name the browser sent.
     *
     * The bytes land on a staged path and stay there until the caller promotes
     * them with `confirmar()`.
     */
    public static function prepararImagen(Dish $dish, UploadedFile $archivo): ArchivoPendiente
    {
        $pendiente = ArchivoPendiente::para(static::rutaImagen($dish, $archivo->guessExtension() ?: 'jpg'), Dish::disco());

        Storage::disk($pendiente->disco)->put($pendiente->rutaParcial, file_get_contents($archivo->getRealPath()));

        return $pendiente;
    }

    /**
     * Park the 3D model of a dish and return the path the row will reference.
     */
    public static function prepararModelo(Dish $dish, UploadedFile $archivo): ArchivoPendiente
    {
        $pendiente = ArchivoPendiente::para(static::rutaModelo($dish), Model3D::disco());

        Storage::disk($pendiente->disco)->put($pendiente->rutaParcial, file_get_contents($archivo->getRealPath()));

        return $pendiente;
    }

    /**
     * Move a parked file into the place the committed row points at.
     *
     * Called only once the transaction has committed. Until this runs the row
     * still references the previous file, which is why nothing is overwritten
     * before now: a failure anywhere earlier leaves the dish with the file it
     * always had.
     */
    public static function confirmar(ArchivoPendiente $pendiente): void
    {
        if (! Storage::disk($pendiente->disco)->exists($pendiente->rutaParcial)) {
            return;
        }

        Storage::disk($pendiente->disco)->move($pendiente->rutaParcial, $pendiente->rutaFinal);
    }

    /**
     * Throw away a parked file that no committed row will ever reference.
     */
    public static function descartar(ArchivoPendiente $pendiente): void
    {
        static::eliminarEnDisco($pendiente->rutaParcial);
    }

    /**
     * Point the dish at a stored model, creating the row the first time.
     *
     * A dish owns at most one model, so this is an upsert rather than a create:
     * uploading a second file replaces the first instead of failing on the
     * unique constraint.
     */
    public static function associateModelo(Dish $dish, string $ruta): Model3D
    {
        $modelo = $dish->model3D()->first();

        if ($modelo) {
            $modelo->update(['archivo' => $ruta]);

            return $modelo;
        }

        return $dish->model3D()->create(['archivo' => $ruta]);
    }

    /**
     * Delete a model row together with its file.
     */
    public static function eliminarModelo(Dish $dish): ?string
    {
        $modelo = $dish->model3D()->first();

        if (! $modelo) {
            return null;
        }

        $ruta = $modelo->archivo;

        $modelo->delete();

        return $ruta;
    }

    /**
     * Remove a file from the public disk, ignoring a path that is already gone.
     *
     * This is the raw operation: it deletes whatever path it is handed. Anything
     * acting on behalf of a dish should go through {@see eliminarSiNoEstaEnUso},
     * which refuses to delete a file another row still points at.
     */
    public static function eliminarEnDisco(?string $ruta): void
    {
        if (blank($ruta)) {
            return;
        }

        Storage::disk(Dish::disco())->delete($ruta);
    }

    /**
     * Remove a file only while no dish points at it.
     *
     * Files are named after the dish id precisely so that two dishes never share
     * one, but rows written before that convention, or by hand, can still point
     * at a common path. Deleting it there would blank the photograph of a dish
     * that was never touched. Checking the references costs one query and makes
     * the deletion safe for those rows too.
     *
     * Call this once the row that used the file is already gone from the
     * database, so the dish being cleaned up does not count as a reference.
     */
    public static function eliminarSiNoEstaEnUso(?string $ruta): void
    {
        if (blank($ruta) || static::estaEnUso($ruta)) {
            return;
        }

        static::eliminarEnDisco($ruta);
    }

    /**
     * Whether any dish photograph or 3D model still points at this path.
     */
    protected static function estaEnUso(string $ruta): bool
    {
        return Dish::query()->where('imagen', $ruta)->exists()
            || Model3D::query()->where('archivo', $ruta)->exists();
    }

    /**
     * Delete the previous file only when the dish now points somewhere else.
     *
     * Called after the new state is committed: a rollback leaves the row still
     * pointing at the old file, and deleting it here would leave a row with no
     * photograph behind it.
     */
    public static function descartarReemplazada(?string $anterior, ?string $actual): void
    {
        if ($anterior && $anterior !== $actual) {
            static::eliminarSiNoEstaEnUso($anterior);
        }
    }

    /**
     * Where the photograph of a dish is written.
     */
    protected static function rutaImagen(Dish $dish, string $extension): string
    {
        return Dish::CARPETA_IMAGENES.'/'.$dish->id.'.'.$extension;
    }

    /**
     * Where the 3D model of a dish is written.
     */
    protected static function rutaModelo(Dish $dish): string
    {
        return static::CARPETA_MODELOS.'/'.$dish->id.'.glb';
    }
}
