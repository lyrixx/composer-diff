import createPhpModule from './build/php-web.mjs';

// What PHP prints, line by line
const output = [];
const php = createPhpModule({
    print(line) {
        output.push(line + '\n');
    },
});

/**
 * Compares two composer.lock files with PHP, and returns the page it renders.
 */
export async function compare(oldLock, newLock) {
    const { ccall, FS } = await php;

    // We use the FS to pass values, otherwise we got the following error:
    // Uncaught RuntimeError: memory access out of bound
    FS.unlink('/app/data/old.json');
    FS.writeFile('/app/data/old.json', oldLock);
    FS.unlink('/app/data/new.json');
    FS.writeFile('/app/data/new.json', newLock);

    output.length = 0;
    ccall('phpw_run', null, ['string'], [
        '$_SERVER["argv"] = ["/app/lib.php", "/app/data/old.json", "/app/data/new.json"]; require "/app/lib.php";',
    ]);

    return output.join('');
}
