<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Enums;

/**
 * Which records of a batch upsert are written: matched records are updated
 * and the rest created (Upsert), only the new ones are created (Create), or
 * only the matched ones are updated (Update). The others are skipped.
 */
enum UpsertMode: string
{
    case Upsert = 'upsert';
    case Create = 'create';
    case Update = 'update';

    /**
     * Whether a record that matched a stored product is written.
     */
    public function updates(): bool
    {
        return $this !== self::Create;
    }

    /**
     * Whether a record that matched no stored product is written.
     */
    public function creates(): bool
    {
        return $this !== self::Update;
    }
}
