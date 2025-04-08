<?php

require __DIR__.'/vendor/autoload.php';

use App\Comparator;
use App\JsonNotValid;
use App\Twig;

$twig = new Twig();
$comparator = new Comparator();

$old = file_get_contents($argv[1]);
$new = file_get_contents($argv[2]);
$error = null;
$diff = null;

try {
    $diff = $comparator->compare($old, $new);
} catch (JsonNotValid $th) {
    $error = $th;
}

echo $twig->render('index.html.twig', [
    'wasm' => true,
    'posted' => true,
    'old' => $old,
    'new' => $new,
    'error' => $error,
    'diff' => $diff,
]);
