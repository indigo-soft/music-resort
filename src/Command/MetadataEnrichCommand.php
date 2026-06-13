<?php

declare(strict_types=1);

namespace MusicResort\Command;

use MusicResort\Service\MetadataEnrichService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'metadata:enrich',
    description: 'Fetch Last.fm artist tags for the collection and cache them in the database',
)]
final class MetadataEnrichCommand extends Command
{
    public function __construct(
        private readonly MetadataEnrichService $enrichService,
        private readonly string $lastFmApiKey,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, __('console.opt.enrich_force'))
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, __('console.opt.enrich_limit'))
            ->setHelp(__('console.command.metadata_enrich.help'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (trim($this->lastFmApiKey) === '') {
            $output->writeln('<error>' . __('console.error.lastfm_key_missing') . '</error>');

            return Command::FAILURE;
        }

        $force = (bool)$input->getOption('force');

        $rawLimit = $input->getOption('limit');
        $limit    = $rawLimit !== null ? max(1, (int)$rawLimit) : null;

        $summary = $this->enrichService->enrich(
            force: $force,
            limit: $limit,
            onProgress: static function(string $event, string $artist, array $context) use ($output): void {
                match ($event) {
                    'fetched' => $output->writeln(
                        '  ' . __('console.info.enrich_fetched', ['artist' => $artist, 'count' => $context['tags'] ?? 0]),
                    ),
                    'empty' => $output->writeln(
                        '  ' . __('console.info.enrich_empty', ['artist' => $artist]),
                    ),
                    'failed' => $output->writeln(
                        '  <comment>' . __('console.warning.enrich_failed', ['artist' => $artist]) . '</comment>',
                    ),
                    'cached' => $output->isVerbose()
                        ? $output->writeln('  ' . __('console.info.enrich_cached', ['artist' => $artist]))
                        : null,
                    default => null,
                };
            },
        );

        $output->writeln('');
        $output->writeln('<info>' . __('console.success.enrich_done', [
            'total'   => $summary['total'],
            'fetched' => $summary['fetched'],
            'cached'  => $summary['cached'],
            'empty'   => $summary['empty'],
            'failed'  => $summary['failed'],
        ]) . '</info>');

        return $summary['failed'] === $summary['total'] && $summary['total'] > 0
            ? Command::FAILURE
            : Command::SUCCESS;
    }
}
