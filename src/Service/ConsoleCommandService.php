<?php

namespace MusicResort\Service;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ConsoleCommandService
{
    public const string DEST_ARG_NAME = 'destination';
    public const string SRC_ARG_NAME = 'source';
    public const string DRYRUN_OPT_NAME = 'dry-run';
    public InputInterface $input;
    public OutputInterface $output;

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * @return bool
     */
    public function isDryRun(): bool
    {
        return $this->input->getOption(self::DRYRUN_OPT_NAME) || ConfigService::get('app.debug');
    }

    /**
     * @return string
     */
    public function getSourceDir(): string
    {
        return (string)$this->input->getArgument(self::SRC_ARG_NAME);
    }

    /**
     * @return string|null
     */
    public function getDestinationDir(): ?string
    {
        if ($this->input->hasArgument(self::DEST_ARG_NAME)) {
            return $this->input->getArgument(self::DEST_ARG_NAME);
        }

        return null;
    }
}
