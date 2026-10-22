import createPhp from './build/php-web.mjs';

const php = createPhp();

/**
 * Compares two composer.lock files with PHP, and returns the page it renders.
 */
export async function compare(oldLock, newLock) {
    const { FS, runFile } = await php;

    FS.writeFile('/tmp/old.json', oldLock);
    FS.writeFile('/tmp/new.json', newLock);

    const { exitCode, stdout } = await runFile('/app/lib.php', { args: ['/tmp/old.json', '/tmp/new.json'] });
    if (0 !== exitCode) {
        throw new Error(`PHP exits with ${exitCode}: ${stdout}`);
    }

    return stdout;
}
