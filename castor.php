<?php

use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use PhpWasm\Stack;
use Symfony\Component\Console\Input\InputOption;

use function Castor\finder;
use function Castor\fs;
use function Castor\io;
use function Castor\PHPQa\php_cs_fixer;
use function Castor\PHPQa\phpstan;
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

/**
 * @param list<string> $set
 */
#[AsTask(namespace: 'app', description: 'Build the website in the public folder')]
function build(
    #[AsOption(description: 'Override a value of the bake file, as "docker buildx bake --set" does (the CI gives the cache this way), can be repeated', mode: InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY)]
    array $set = [],
): void {
    // What runs in the browser needs its dependencies
    install_if_missing();

    io()->title('Building the website');

    pack_app();
    PhpWasm\build([wasm_stack()], $set);

    run_php('generate-homepage.php');
}

#[AsTask(namespace: 'app', description: 'Check that the website boots, and compares the examples')]
function smoke(): void
{
    build_if_missing();

    io()->title('Checking the website');

    PhpWasm\check_stack(wasm_stack());
    // The JavaScript of the page, in node
    PhpWasm\test_page('tests/page.test.mjs');
}

#[AsTask(namespace: 'app', description: 'Run the server')]
function serve(string $address = 'localhost:9999'): void
{
    build_if_missing();

    io()->title("Serving on http://{$address}");

    run(['php', '-S', $address, '-t', 'public']);
}

#[AsTask(name: 'watch', namespace: 'app', description: 'Watch and rebuild')]
function watch_and_build(): void
{
    io()->title('Watching for changes...');

    watch([
        __DIR__ . '/src',
        __DIR__ . '/templates',
    ], static fn() => build());
}

#[AsTask(name: 'cs', namespace: 'qa', description: 'Fix the coding standards')]
function qa_cs(bool $dryRun = false): void
{
    io()->section('Running PHP CS Fixer...');

    php_cs_fixer($dryRun ? ['fix', '--dry-run', '--diff'] : ['fix', '-v'], version: '3.95.27');
}

#[AsTask(name: 'phpstan', namespace: 'qa', description: 'Run PHPStan')]
function qa_phpstan(): void
{
    // PHPStan loads the code with its dependencies
    install_if_missing();

    io()->section('Running PHPStan...');

    phpstan(['analyse'], version: '2.3.1', extraDependencies: [
        'phpstan/extension-installer' => '1.4.3',
        'phpstan/phpstan-deprecation-rules' => '2.0.5',
    ]);
}

function install_if_missing(): void
{
    if (!is_file(__DIR__ . '/vendor/autoload.php')) {
        install();
    }
}

function build_if_missing(): void
{
    if (!is_file(wasm_stack()->artifact('web', 'mjs'))) {
        build();
    }
}

/**
 * PHP, compiled to WASM by lyrixx/php-wasm-builder.
 */
function wasm_stack(): Stack
{
    return Stack::named('composer-diff')
        // Keep in sync with config.platform.php in composer.json
        ->php('8.5.12')
        ->extensions('ctype', 'filter', 'mbstring', 'tokenizer')
        // Filled by pack_app()
        ->embed('build/app')
        ->targets('web')
        ->memory('256mb')
        ->outputDir('public/build');
}

/**
 * Copies the code that runs in the browser to build/app/, which the stack
 * embeds in the WASM file as /app.
 */
function pack_app(): void
{
    $app = __DIR__ . '/build/app';

    fs()->remove($app);
    fs()->mirror(
        __DIR__,
        $app,
        finder()
        ->in(__DIR__)
        ->files()
        ->path(['#^(data|src|templates|vendor)/#', '#^lib\.php$#'])
        ->exclude(['doc', 'docs', 'tests', 'Tests', 'tools', 'vendor/symfony/finder'])
        ->notName(['*.md', 'CHANGELOG', 'composer.*', 'LICENSE'])
    );
}
