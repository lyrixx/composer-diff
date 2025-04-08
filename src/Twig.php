<?php

namespace App;

use Symfony\Bridge\Twig\Extension\DumpExtension;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Twig extends Environment
{
    public function __construct()
    {
        $loader = new FilesystemLoader(__DIR__.'/../templates');

        parent::__construct($loader, [
            'strict_variables' => true,
            'debug' => true,
            'cache' => false,
        ]);

        $this->addExtension(new DumpExtension(new VarCloner()));
    }
}
