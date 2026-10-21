# Version migration

## Migrate versions from Pimcore to OpenDXP

Versions in Pimcore contain serialized data. If you want to use these versions after migrating your instance to OpenDXP you can use the following command.

You might need to adjust it to your needs though. Place it into src/Command/ in a default symfony folder structure.

```php

<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'app:migrate-version-files',
    description: 'Migrates serialized version files from pimcore to open-dxp namespace',
)]
class MigrateVersionFilesCommand extends Command
{
    private const SEARCH = 'Pimcore\\';
    private const REPLACE = 'OpenDxp\\';

    private const VERSIONS_DIR = OPENDXP_PROJECT_ROOT . '/var/versions';

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show which files would be changed without actually changing them.')
            ->addOption('path', 'p', InputOption::VALUE_REQUIRED, 'Alternative path instead of var/versions.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $basePath = $input->getOption('path') ?: self::VERSIONS_DIR;

        if (!is_dir($basePath)) {
            $io->error(sprintf('Path "%s" does not exist.', $basePath));

            return Command::FAILURE;
        }

        if ($dryRun) {
            $io->note('Dry-Run-Mode – no files will actually be changed.');
        }

        $io->title('Migration of version files: Pimcore → OpenDxp');
        $io->text(sprintf('Searching: %s', $basePath));

        $finder = new Finder();
        $finder->files()->in($basePath);

        $totalFiles = 0;
        $migratedFiles = 0;
        $skippedFiles = 0;
        $errorFiles = 0;

        foreach ($finder as $file) {
            $totalFiles++;
            $filePath = $file->getRealPath();

            // Skip binary files
            if (stripos($filePath, '.bin') !== false) {
                $skippedFiles++;

                if ($io->isVeryVerbose()) {
                    $io->text(sprintf('  ⏭  skipped (binary file): %s', $filePath));
                }

                continue;
            }

            try {
                $content = file_get_contents($filePath);

                if ($content === false) {
                    $io->warning(sprintf('Could not read file: %s', $filePath));
                    $errorFiles++;

                    continue;
                }

                // Check if the migration file does contain pimcore namespaces
                if (strpos($content, 'Pimcore\\') === false) {
                    $skippedFiles++;

                    if ($io->isVeryVerbose()) {
                        $io->text(sprintf('  ⏭  skipped (no Pimcore namespace): %s', $filePath));
                    }

                    continue;
                }

                /*
                 * "Pimcore" and "OpenDxp" are both exactly 7 chars long
                 * so the length of the serialization
                 * (e.g.. O:42:"…", s:39:"…") will stay correctly after replacement.
                 *
                 * A simple str_replace should be sufficent therefor, we do not need to un- and reserialize the content
                 */
                $migrated = str_replace(self::SEARCH, self::REPLACE, $content);

                if (!$dryRun) {
                    if (file_put_contents($filePath, $migrated) === false) {
                        $io->error(sprintf('Could not write file: %s', $filePath));
                        $errorFiles++;

                        continue;
                    }
                }

                $migratedFiles++;

                if ($io->isVerbose()) {
                    $io->text(sprintf('  ✅ Migrated: %s', $filePath));
                }
            } catch (\Throwable $e) {
                $io->error(sprintf('Error at %s: %s', $filePath, $e->getMessage()));
                $errorFiles++;
            }
        }

        $io->newLine();
        $io->definitionList(
            ['Total files' => $totalFiles],
            ['Migrated' => $migratedFiles],
            ['Skipped (no Pimcore namespace or binary)' => $skippedFiles],
            ['Error' => $errorFiles],
        );

        if ($dryRun && $migratedFiles > 0) {
            $io->warning(sprintf(
                'Dry-Run: %d Files would be migrated. Execute the command without --dry-run to make the changes.',
                $migratedFiles,
            ));
        }

        if ($errorFiles > 0) {
            $io->warning('There have been errors - see above.');

            return Command::FAILURE;
        }

        $io->success(sprintf(
            '%d of %d files successfully %s.',
            $migratedFiles,
            $totalFiles,
            $dryRun ? 'recognized (Dry-Run)' : 'migrated',
        ));

        return Command::SUCCESS;
    }
}


```
