<?php

namespace MusicResort\Service;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleCommandService
{
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
        return $this->input->getOption('dry-run') || ConfigService::get('app.debug');
    }

    /**
     * @return string
     */
    public function getSourceDir(): string
    {
        return (string)$this->input->getArgument('source');
    }

    /**
     * @return string
     */
    public function getDestinationDir(): string
    {
        return (string)$this->input->getArgument('destination');
    }
}
