<?php

namespace App\Contracts;

interface FreshResetContributor
{
    /** @return string[] Tables ordered child-first for deletion. */
    public function transactionTables(): array;

    /** Reset preserved module master financial state. */
    public function resetPreservedState(): void;
}
