<?php

namespace App;

final readonly class Diff
{
    public function __construct(
        public array $array,
        public string $markdown,
    ) {}
}
