#!/user/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

use App\Twig;

$twig = new Twig();

$index = $twig->render('index.html.twig', [
    'wasm' => true,
    'posted' => false,
    'error' => null,
    'diff' => null,
    'old' => '',
    'new' => '',
]);

file_put_contents(__DIR__.'/public/index.html', $index);
