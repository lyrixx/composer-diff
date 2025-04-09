<?php

require __DIR__.'/vendor/autoload.php';

use App\Comparator;
use App\Twig;

$old = file_get_contents($argv[1]);
$new = file_get_contents($argv[2]);

$result = (new Comparator())->compare($old, $new);

echo (new Twig())->render('index.html.twig', [
    'wasm' => true,
    'result' => $result,
]);
