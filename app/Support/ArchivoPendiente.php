<?php

namespace App\Support;

/**
 * A file that has been uploaded to the disk but is not live yet.
 *
 * Dish files are named after the dish id, so replacing a photograph with the same
 * extension, or a 3D model at all, writes over the very file the row still
 * points at. Committing the row and overwriting the bytes cannot both be atomic,
 * so the new bytes are parked next to the target and only moved into place once
 * the row is safely committed. Between the two, `rutaParcial` is the copy nobody
 * points at and `rutaFinal` is what the row will reference.
 *
 * The staged file keeps the extension of its target so that on a local disk the
 * eventual move is a rename inside one directory. On an object store such as
 * Supabase Storage the move is a copy followed by a delete, but it is still
 * performed only after the row has been committed.
 */
final class ArchivoPendiente
{
    /**
     * Suffix marking a file that is waiting for its row to be committed.
     */
    public const SUFIJO = '.subiendo';

    public function __construct(
        public readonly string $rutaFinal,
        public readonly string $rutaParcial,
        public readonly string $disco = 'public',
    ) {}

    /**
     * Park an already written upload for a destination.
     */
    public static function para(string $rutaFinal, string $disco = 'public'): self
    {
        return new self($rutaFinal, $rutaFinal.self::SUFIJO, $disco);
    }
}
