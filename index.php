<?php

require __DIR__.'/vendor/autoload.php';

use App\Comparator;
use App\JsonNotValid;
use App\Twig;

$twig = new Twig();

$posted = 'POST' === $_SERVER['REQUEST_METHOD'];
$error = null;
$diff = null;

if ($posted) {
    $comparator = new Comparator();

    try {
        $diff = $comparator->compare($_POST['old'], $_POST['new']);
    } catch (JsonNotValid $th) {
        $error = $th;
    }
}
echo $twig->render('index.html.twig', [
    'posted' => $posted,
    'error' => $error,
    'diff' => $diff,
    'old' => $_POST['old'] ?? '',
    'new' => $_POST['new'] ?? '',
]);
