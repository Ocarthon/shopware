<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Migration\Command;

use Shopware\Core\Framework\Adapter\Console\ShopwareStyle;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\Exception\UnknownMigrationSourceException;
use Shopware\Core\Framework\Migration\MigrationCollection;
use Shopware\Core\Framework\Migration\MigrationCollectionLoader;
use Shopware\Core\Framework\Migration\MigrationException;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'database:migrate',
    description: 'Executes all migrations',
)]
#[Package('framework')]
class MigrationCommand extends Command
{
    protected SymfonyStyle $io;

    /**
     * @internal
     */
    public function __construct(
        protected readonly MigrationCollectionLoader $loader,
        private readonly TagAwareAdapterInterface $cache,
        protected readonly string $shopwareVersion
    ) {
        parent::__construct();
    }

    protected function getMigrationGenerator(MigrationCollection $collection, ?int $until, ?int $limit): \Generator
    {
        yield from $collection->migrateInSteps($until, $limit);
    }

    protected function getMigrationsCount(MigrationCollection $collection, ?int $until, ?int $limit): int
    {
        return \count($collection->getExecutableMigrations($until, $limit));
    }

    protected function configure(): void
    {
        $this
            ->addArgument('identifier', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'identifier to determine which migrations to run', ['core'])
            ->addOption('all', 'all', InputOption::VALUE_NONE, 'no migration timestamp cap')
            ->addOption('until', 'u', InputOption::VALUE_OPTIONAL, 'timestamp cap for migrations')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, '', '0');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $identifiers = $input->getArgument('identifier');
        if (!\is_array($identifiers)) {
            $identifiers = [$identifiers];
        }

        $until = (int) $input->getOption('until');

        $this->io = new ShopwareStyle($input, $output);

        if (!$until && !$input->getOption('all')) {
            throw MigrationException::invalidArgument('missing timestamp cap or --all option');
        }

        if (\count($identifiers) > 1 && (!$input->getOption('all') || $input->getOption('limit'))) {
            throw MigrationException::invalidArgument('Running migrations for multiple identifiers without --all option or with --limit option is not supported.');
        }

        $limit = (int) $input->getOption('limit');

        if ($input->getOption('all')) {
            $until = null;
        }

        $total = 0;
        foreach ($identifiers as $identifier) {
            $total += $this->runMigrationForIdentifier($input, $identifier, $limit, $until);
        }

        if ($total > 0) {
            $this->cache->clear();
            $this->io->writeln('cleared the shopware cache');
        }

        return self::SUCCESS;
    }

    protected function collectMigrations(InputInterface $input, string $identifier): MigrationCollection
    {
        if ($identifier === 'core') {
            return $this->loader->collectAllForVersion($this->shopwareVersion);
        }

        return $this->loader->collect($identifier);
    }

    private function finishProgress(int $migrated, int $total): void
    {
        if ($migrated === $total) {
            $this->io->progressFinish();
        }

        $this->io->table(
            ['Action', 'Number of migrations'],
            [
                ['Migrated', $migrated . ' out of ' . $total],
            ]
        );
    }

    private function runMigrationForIdentifier(InputInterface $input, string $identifier, int $limit, ?int $until): int
    {
        $this->io->writeln(\sprintf('Get collection for identifier: "%s"', $identifier));

        try {
            $collection = $this->collectMigrations($input, $identifier);
        } catch (UnknownMigrationSourceException) {
            $this->io->note(\sprintf('No collection found for identifier: "%s", continuing', $identifier));

            return 0;
        }

        $collection->sync();

        $this->io->writeln('migrate Migrations');

        $migrationCount = $this->getMigrationsCount($collection, $until, $limit);
        $this->io->progressStart($migrationCount);
        $migratedCounter = 0;

        try {
            foreach ($this->getMigrationGenerator($collection, $until, $limit) as $ignored) {
                $this->io->progressAdvance();
                ++$migratedCounter;
            }
        } catch (\Exception $e) {
            $this->finishProgress($migratedCounter, $migrationCount);

            throw MigrationException::migrationError($e->getMessage() . \PHP_EOL . 'Trace: ' . \PHP_EOL . $e->getTraceAsString(), $e);
        }

        $this->finishProgress($migratedCounter, $migrationCount);
        $this->io->writeln(\sprintf('all migrations for identifier: "%s" executed', $identifier));

        return $migrationCount;
    }
}
