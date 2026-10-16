<?php

namespace App\Support;

use App\Models\Dish;
use Illuminate\Support\Str;

/**
 * Resolves the photograph shown for a dish or a category on the public menu.
 *
 * A dish photograph is looked up in three steps, and the first one that resolves
 * wins:
 *
 *   1. The file the administrator uploaded, whose relative path is held in
 *      `dishes.imagen` on the public disk. This is the normal case.
 *   2. A file named after the dish sitting directly in `public/platos`, which is
 *      the convention that existed before the column. It is kept so the photos
 *      already in the repository keep showing without being re-uploaded.
 *   3. A bundled scene for the category, so a dish without a photograph is
 *      never rendered as a broken image.
 *
 *     public/platos/pizza-margarita.jpg
 *
 * The scene of the third step is picked from the dish id, so a dish always shows
 * the same picture between requests while neighbouring dishes do not all share
 * one image.
 *
 * Steps 2 and 3 together are also the safety net of step 1: the views hand
 * {@see respaldoParaDish} to the browser as the image to fall back to when the
 * uploaded one fails to load, so a photograph that is missing on disk leaves the
 * card showing the same scene a photo-less dish shows, not an empty dark frame.
 */
class MenuImagen
{
    /**
     * Extensions searched in `public/platos`, most efficient first. The first
     * one present wins, so a dish can be given a .jpg and later upgraded to a
     * .webp without touching the view.
     *
     * @var array<int, string>
     */
    public const EXTENSIONES = ['avif', 'webp', 'jpg', 'jpeg', 'png', 'svg'];

    /**
     * Concept => the fallback scenes that represent it. A dish without its own
     * photograph lands on one of these, alternating per dish.
     *
     * @var array<string, array<int, string>>
     */
    protected const ESCENAS = [
        'cloche' => ['entradas-1', 'entradas-2'],
        'burger' => ['hamburguesas-1', 'hamburguesas-2'],
        'pizza' => ['pizzas-1', 'pizzas-2'],
        'pasta' => ['pastas-1', 'pastas-2'],
        'postre' => ['postres-1', 'postres-2'],
        'bebida' => ['bebidas-1', 'bebidas-2'],
        'plato' => ['plato-1', 'plato-2'],
    ];

    /**
     * Public path of the image for a dish: the uploaded photograph when there is
     * one, otherwise whatever {@see respaldoParaDish} resolves to.
     */
    public static function paraDish(Dish $dish): string
    {
        return $dish->imagenUrl() ?? static::respaldoParaDish($dish);
    }

    /**
     * The image to show when the uploaded photograph cannot be displayed.
     *
     * This is what a dish resolves to when it has no photograph at all, so it
     * doubles as the `onerror` target of the uploaded one: a URL that answers
     * with a 404 or never answers lands on the same scene a photo-less dish
     * would have shown from the start, instead of leaving the dark photo frame
     * the card reserves for it.
     *
     * A photograph already in the repository named after the dish is preferred
     * over an illustration, so upgrading an old dish degrades to something
     * closer to the real dish rather than to a generic picture.
     */
    public static function respaldoParaDish(Dish $dish): string
    {
        return static::fotografiaDe($dish->nombre) ?? static::escenaDe($dish);
    }

    /**
     * The bundled scene that stands in for the photograph of a dish.
     */
    public static function escenaDe(Dish $dish): string
    {
        $escenas = static::ESCENAS[MenuConcepto::para($dish->category?->nombre)] ?? static::ESCENAS['plato'];

        // Alternate per dish so a category never renders one image twice in a
        // row, and stay stable because the id never changes.
        $escena = $escenas[$dish->id % count($escenas)];

        return 'img/menu/'.$escena.'.svg';
    }

    /**
     * Public path of the scene for a category name, used on the category
     * headings and on the hero.
     */
    public static function paraCategoria(?string $nombre): string
    {
        $escenas = static::ESCENAS[MenuConcepto::para($nombre)] ?? static::ESCENAS['plato'];

        return 'img/menu/'.$escenas[0].'.svg';
    }

    /**
     * Public path of the hero photograph, when one has been dropped into
     * `public/platos` under the name `hero`. Until then the menu shows the
     * bundled table scene.
     */
    public static function hero(): string
    {
        if ($fotografia = static::fotografiaDe('hero')) {
            return $fotografia;
        }

        return 'img/menu/hero.svg';
    }

    /**
     * The photograph uploaded for the given name, or null when there is none.
     */
    protected static function fotografiaDe(string $nombre): ?string
    {
        $slug = Str::slug($nombre);

        if ($slug === '') {
            return null;
        }

        foreach (static::EXTENSIONES as $extension) {
            $ruta = 'platos/'.$slug.'.'.$extension;

            if (is_file(public_path($ruta))) {
                return $ruta;
            }
        }

        return null;
    }
}
