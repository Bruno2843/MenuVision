<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Resolves which visual concept a category is drawn with.
 *
 * The public menu has no image column on dishes, so each category is
 * illustrated instead of photographed. The concept is picked from the
 * category name and is stable for a given name, so a category always keeps
 * the same icon between requests. The image itself is resolved separately by
 * {@see MenuImagen}, which prefers a real photograph when one exists.
 */
class MenuConcepto
{
    /**
     * Concept => the keywords that select it, matched against the lowercased
     * category name. The first concept with a hit wins, so more specific
     * concepts are listed before broader ones.
     *
     * @var array<string, array<int, string>>
     */
    protected const PALABRAS = [
        'pizza' => ['pizza'],
        'burger' => ['hamburg', 'burger', 'sangr', 'panino', 'panini'],
        'pasta' => ['pasta', 'fideo', 'tallar', 'espagu', 'macar', 'fetucc', 'fetu', 'raviol', 'canel', 'nido'],
        'postre' => ['postre', 'dulce', 'tiramis', 'flan', 'helado', 'vasej', 'cheesecake'],
        'bebida' => ['bebida', 'cafe', 'limonada', 'vino', 'coctel', 'jugo', 'infusion', 'gaseosa', 'cerveza', 'mocktail'],
        'ensalada' => ['ensalada', 'salad'],
        'cloche' => ['entrada', 'aperitivo', 'picada', 'tabla', 'compartir', 'principio'],
    ];

    /**
     * The concept that represents the given category name.
     */
    public static function para(?string $nombre): string
    {
        $nombre = Str::lower((string) $nombre);

        foreach (self::PALABRAS as $concepto => $palabras) {
            if (Str::contains($nombre, $palabras)) {
                return $concepto;
            }
        }

        return 'plato';
    }
}
