<?php

namespace App;

final readonly class Result
{
    public function __construct(
        public string $old,
        public string $new,
        public ?string $error = null,
        public ?Diff $diff = null,
    ) {}
}
