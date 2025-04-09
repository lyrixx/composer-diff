<?php

namespace App;

use IonBazan\ComposerDiff\Formatter\JsonFormatter;
use IonBazan\ComposerDiff\Formatter\MarkdownTableFormatter;
use IonBazan\ComposerDiff\PackageDiff;
use IonBazan\ComposerDiff\Url\GeneratorContainer;
use Symfony\Component\Console\Output\BufferedOutput;

class Comparator
{
    public function __construct(private readonly PackageDiff $packageDiff = new PackageDiff())
    {
    }

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

    private function doCompare(string $old, string $new): Diff
    {
        $oldLock = $this->getLockArray($old, 'old');
        $newLock = $this->getLockArray($new, 'new');

        $prodOperations = $this->packageDiff->getDiff(
            $this->packageDiff->loadPackagesFromArray($oldLock, false, false),
            $this->packageDiff->loadPackagesFromArray($newLock, false, false)
        );
        $devOperations = $this->packageDiff->getDiff(
            $this->packageDiff->loadPackagesFromArray($oldLock, true, false),
            $this->packageDiff->loadPackagesFromArray($newLock, true, false)
        );

        $generators = new GeneratorContainer([]);

        $output = new BufferedOutput();
        $formatter = new JsonFormatter($output, $generators);
        $formatter->render($prodOperations, $devOperations, true, true);
        $array = json_decode($output->fetch(), true, 512, JSON_THROW_ON_ERROR);

        $output = new BufferedOutput();
        $formatter = new MarkdownTableFormatter($output, $generators);
        $formatter->render($prodOperations, $devOperations, true, true);
        $markdown = $output->fetch();

        return new Diff($array, $markdown);
    }

    private function getLockArray(string $composerLock, string $path): array
    {
        try {
            $data = \json_decode($composerLock, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $th) {
            throw new JsonNotValid(sprintf('Invalid JSON: %s.', $th->getMessage()), $path);
        }

        if (!isset($data['packages-dev']) && !isset($data['packages'])) {
            throw new JsonNotValid('The file does not look like composer.lock file', $path);
        }

        return $data;
    }
}
