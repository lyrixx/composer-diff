<?php

require __DIR__ . '/vendor/autoload.php';

use App\Comparator;
use App\Twig;

// php lib.php <old composer.lock> <new composer.lock>, as public/page.mjs runs it
[, $oldPath, $newPath] = $_SERVER['argv'];

$old = file_get_contents($oldPath);
$new = file_get_contents($newPath);
if (false === $old || false === $new) {
    throw new RuntimeException('Cannot read the composer.lock files to compare.');
}

$result = new Comparator()->compare($old, $new);

echo new Twig()->render('index.html.twig', [
    'wasm' => true,
    'result' => $result,
]);
