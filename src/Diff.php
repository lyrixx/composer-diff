<?php

namespace App;

use IonBazan\ComposerDiff\Diff\DiffEntries;

final readonly class Diff
{
    public function __construct(
        public DiffEntries $prodEntries,
        public DiffEntries $devEntries,
        public string $markdown,
    ) {}
}
