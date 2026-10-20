<?php

use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use PhpWasm\Stack;
use Symfony\Component\Console\Input\InputOption;

use function Castor\finder;
use function Castor\fs;
use function Castor\io;
use function Castor\run;
use function Castor\run_php;
use function Castor\watch;

defined('CASTOR_USE_CHDIR') || define('CASTOR_USE_CHDIR', false);

#[AsTask(namespace: 'app', description: 'Install dependencies')]
function install(): void
{
    io()->title('Installing dependencies');

    run(['composer', 'install', '--no-dev', '--optimize-autoloader']);
}

#[AsTask(namespace: 'app', description: 'Update dependencies')]
function update(): void
{
    io()->title('Updating dependencies');

    run(['composer', 'update', '--no-dev', '--optimize-autoloader']);
}

#[AsTask(namespace: 'app', description: 'Build the website in the public folder')]
function build(
    #[AsOption(description: 'Override a value of the bake file, as "docker buildx bake --set" does (the CI gives the cache this way), can be repeated', mode: InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY)]
    array $set = [],
): void {
    io()->title('Building the website');

    pack_app();
    // PHP is compiled to WASM by lyrixx/php-wasm-builder
    PhpWasm\build([
        Stack::named('composer-diff')
            // Keep in sync with config.platform.php in composer.json
            ->php('8.5.11')
            ->extensions('ctype', 'filter', 'mbstring', 'tokenizer')
            ->embed('build/app')
            ->targets('web')
            ->memory('256mb')
            ->outputDir('public/build'),
    ], $set);

    run_php('generate-homepage.php');
}

#[AsTask(namespace: 'app', description: 'Run the server')]
function serve(string $address = 'localhost:9999'): void
{
    io()->title("Serving on http://{$address}");

    run(['php', '-S', $address, '-t', 'public']);
}

#[AsTask(name: 'watch', namespace: 'app', description: 'Watch and rebuild')]
function watch_and_build(): void
{
    io()->title('Watching for changes...');

    watch([
        __DIR__.'/src',
        __DIR__.'/templates',
    ], fn () => build());
}

/**
 * Copies the code that runs in the browser to build/app/, which the stack
 * embeds in the WASM file as /app.
 */
function pack_app(): void
{
    $app = __DIR__.'/build/app';

    fs()->remove($app);
    fs()->mirror(__DIR__, $app, finder()
        ->in(__DIR__)
        ->files()
        ->path(['#^(data|src|templates|vendor)/#', '#^lib\.php$#'])
        ->exclude(['doc', 'docs', 'tests', 'Tests', 'tools', 'vendor/symfony/finder'])
        ->notName(['*.md', 'CHANGELOG', 'composer.*', 'LICENSE'])
    );
}
