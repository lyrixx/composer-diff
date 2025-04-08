<?php

namespace App;

class JsonNotValid extends \RuntimeException
{
    public function __construct(string $message, public readonly string $path)
    {
        parent::__construct($message);
    }
}
