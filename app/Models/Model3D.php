<?php

namespace App\Models;

use Database\Factories\Model3DFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table as TableAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * The 3D model attached to a dish, used by the public menu to show it in 3D on
 * desktop and to place it in the room through augmented reality on a phone.
 */
#[Fillable(['dish_id', 'archivo'])]
#[TableAttribute('model3ds')]
class Model3D extends Model
{
    /** @use HasFactory<Model3DFactory> */
    use HasFactory;

    /**
     * Disk holding the models. It is the public disk so the browser can fetch
     * the `.glb` directly, and so no private server path is ever exposed.
     */
    public const DISCO = 'public';

    /**
     * Folder inside the disk where the uploaded models are written, named after
     * the dish id so two dishes can never overwrite each other's model.
     */
    public const CARPETA = 'models';

    /**
     * @return BelongsTo<Dish, $this>
     */
    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }

    /**
     * Public URL of the model, served from the public disk.
     */
    public function url(): string
    {
        return Storage::disk(static::DISCO)->url($this->archivo);
    }

    /**
     * Whether the file is really on the disk.
     *
     * A row can outlive its file, and a viewer pointed at a missing file only
     * offers a broken experience. Callers use this to show the 3D viewer, and
     * the augmented reality button with it, only when it can really work.
     */
    public function existe(): bool
    {
        return filled($this->archivo)
            && Storage::disk(static::DISCO)->exists($this->archivo);
    }
}
