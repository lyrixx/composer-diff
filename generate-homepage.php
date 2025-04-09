#!/user/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

use App\Twig;

$index = (new Twig())->render('index.html.twig', [
    'wasm' => true,
    'result' => null,
]);

file_put_contents(__DIR__.'/public/index.html', $index);
