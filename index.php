<?php

require __DIR__.'/vendor/autoload.php';

use App\Comparator;
use App\Twig;

if ('POST' === $_SERVER['REQUEST_METHOD']) {
    $result = (new Comparator())->compare($_POST['old'], $_POST['new']);
}


echo (new Twig())->render('index.html.twig', [
    'result' => $result ?? null,
]);
