<?php

/*
 * This file is part of JoliTypo - a project by JoliCode.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

use Castor\Attribute\AsTask;
use Symfony\Component\Process\Process;

use function Castor\context;
use function Castor\fs;
use function Castor\io;
use function Castor\run as do_run;
use function Castor\run_php;
use function Castor\watch;

#[AsTask(description: 'Install dependencies')]
function install()
{
    io()->title('Installing dependencies');

    run(['composer', 'install', '--no-dev', '--optimize-autoloader']);
}

#[AsTask(description: 'Update dependencies')]
function update()
{
    io()->title('Installing dependencies');

    run(['composer', 'update', '--no-dev', '--optimize-autoloader']);
}

#[AsTask('wasm:build', description: 'Build the wasm-php binary')]
function wasm_build()
{
    io()->title('Building wasm-php binary');

    run(['docker', 'buildx', 'bake']);
}

#[AsTask('wasm:pack', description: 'Pack custom code')]
function wasm_pack()
{
    io()->title('Packing custom code');

    run(['docker', 'run',
         '-v', __DIR__ . ':/app',
         '-v', __DIR__ . '/build:/dist/build',
         '-w', '/dist',
         'emscripten/emsdk:4.0.6',
            'python3',
                '/emsdk/upstream/emscripten/tools/file_packager.py',
                'build/php-web.data',
                '--use-preload-cache',
                '--lz4',
                '--preload', '/app',
                '--js-output=build/php-web.data.js',
                '--no-node',
                '--exclude',
                    '*/.*',
                    '*/*md',
                    '*/castor.php',
                    '*/CHANGELOG',
                    '*/composer.*',
                    '*/doc',
                    '*/docs',
                    '*/LICENSE',
                    '*/tests',
                    '*/Tests',
                    '*/tools',
                    '/app/build',
                    '/app/public',
                    '/app/vendor/symfony/console',
                    '/app/vendor/symfony/finder',
                    '/app/vendor/react',
                '--export-name=createPhpModule',
    ]);
}

#[AsTask('wasm:export', description: 'Export the wasm-php binary to the public folder with custom code')]
function wasm_export(bool $pack = false, bool $build = false)
{
    if ($build) {
        wasm_build();
    }

    if ($pack) {
        wasm_pack();
    }

    io()->title('Exporting wasm-php');

    run_php('generate-homepage.php');

    fs()->remove(__DIR__.'/public/build');

    fs()->copy(__DIR__.'/build/php-web.wasm', __DIR__.'/public/build/php-web.wasm');
    fs()->copy(__DIR__.'/build/php-web.data', __DIR__.'/public/build/php-web.data');

    $data = file_get_contents(__DIR__.'/build/php-web.data.js');
    $mjs = file_get_contents(__DIR__.'/build/php-web.mjs');
    $mjs = str_replace($p = '// --pre-jses ', "{$data}\n{$p}", $mjs);
    $baseUrl = $_SERVER['BASE_URL'] ?? '';
    // see: https://github.com/emscripten-core/emscripten/issues/21289
    $mjs = str_replace("var REMOTE_PACKAGE_BASE = 'php-web.data';", "var REMOTE_PACKAGE_BASE = '{$baseUrl}/build/php-web.data';", $mjs);
    fs()->dumpFile(__DIR__.'/public/build/php-web.mjs', $mjs);
}

#[AsTask(description: 'Run the server')]
function serve(string $address = 'localhost:9999')
{
    io()->title("Serving on http://{$address}");

    run(['php', '-S', $address, '-t', 'public']);
}

#[AsTask(name: 'watch', description: 'Watch and rebuild')]
function watch_and_build()
{
    io()->title('Watching for changes...');

    watch([
        __DIR__.'/src',
        __DIR__.'/templates',
    ], fn () => run(['castor', 'wasm:export', '--pack']));
}

function run(array $command, string $path = __DIR__): Process
{
    $context = context()
        ->withWorkingDirectory($path)
        ->withEnvironment([
            'BUILDKIT_PROGRESS' => 'plain',
        ])
    ;

    return do_run($command, context: $context);
}
