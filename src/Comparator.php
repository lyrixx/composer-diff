<?php

namespace App;

use Composer\Package\CompletePackage;
use Composer\Package\Loader\ArrayLoader;
use Composer\Repository\ArrayRepository;
use IonBazan\ComposerDiff\Diff\DiffEntries;
use IonBazan\ComposerDiff\Formatter\JsonFormatter;
use IonBazan\ComposerDiff\PackageDiff;
use IonBazan\ComposerDiff\Url\GeneratorContainer;
use Symfony\Component\Console\Output\BufferedOutput;

class Comparator
{
    public function compare(string $old, string $new): Result
    {
        try {
            $diff = $this->doCompare($old, $new);
        } catch (JsonNotValid $th) {
            $error = $th;
        }

        return new Result(
            old: $old,
            new: $new,
            error: $error ?? null,
            diff: $diff ?? null,
        );
    }

    private function doCompare(string $old, string $new): array
    {
        $packageDiff = new PackageDiff();

        $prodOperations = new DiffEntries();
        $devOperations = new DiffEntries();

        $oldProd = $this->createRepository($old, false, 'old');
        $newProd = $this->createRepository($new, false, 'new');

        $oldDev = $this->createRepository($old, true, 'old');
        $newDev = $this->createRepository($new, true, 'new');

        $prodOperations = $packageDiff->getDiff($oldProd, $newProd, [], false);
        $devOperations = $packageDiff->getDiff($oldDev, $newDev, [], false);

        $output = new BufferedOutput();
        $formatter = new JsonFormatter($output, new GeneratorContainer([]));
        $formatter->render($prodOperations, $devOperations, true, true);

        return json_decode($output->fetch(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function createRepository(string $composerLock, bool $dev, string $path): ArrayRepository
    {
        try {
            $data = \json_decode($composerLock, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $th) {
            throw new JsonNotValid(sprintf('Invalid JSON: %s.', $th->getMessage()), $path);
        }

        if (!isset($data['packages'.($dev ? '-dev' : '')])) {
            throw new JsonNotValid('The file does not look like composer.lock file', $path);
        }

        $loader = new ArrayLoader();

        $packages = [];

        foreach ($data['packages'.($dev ? '-dev' : '')] ?? [] as $packageInfo) {
            $packages[] = $loader->load($packageInfo);
        }

        foreach ($data['platform'.($dev ? '-dev' : '')] as $name => $version) {
            $packages[] = new CompletePackage($name, $version, $version);
        }

        return new ArrayRepository($packages);
    }
}
