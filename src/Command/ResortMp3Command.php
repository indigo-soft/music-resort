<?php

namespace Root\MusicLocal\Command;

use Exception;
use getID3;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'mp3:resort',
    description: 'Resort MP3 files by artist into separate folders'
)]
final class ResortMp3Command extends Command
{
    private Filesystem $filesystem;

    public function __construct()
    {
        parent::__construct();
        $this->filesystem = new Filesystem();
    }

    /** @noinspection PhpUnused */
    protected function configure(): void
    {
        $this
            ->addArgument('source', InputArgument::REQUIRED, 'Source directory with MP3 files')
            ->addArgument('destination', InputArgument::REQUIRED, 'Destination directory for sorted files')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulate the operation without making any filesystem changes')
            ->setHelp('This command resorts MP3 files by artist from source to destination directory. Use --dry-run to simulate without making changes.');
    }

    /** @noinspection PhpUnused */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $sourceDir = $input->getArgument('source');
        $destinationDir = $input->getArgument('destination');
        $dryRun = $input->getOption('dry-run');

        if ($dryRun) {
            $io->note('DRY-RUN MODE: No filesystem changes will be made');
        }

        // Validate source directory
        if (!is_dir($sourceDir)) {
            $io->error('Source directory does not exist: ' . $sourceDir);
            return Command::FAILURE;
        }

        // Create a destination directory if it doesn't exist
        if (!$this->filesystem->exists($destinationDir)) {
            if (!$dryRun) {
                $this->filesystem->mkdir($destinationDir);
            }
            $io->info('Created destination directory: ' . $destinationDir);
        }

        $finder = new Finder();
        $finder->files()->in($sourceDir)->name('*.mp3');

        $processedCount = 0;
        $errorCount = 0;

        $io->title('MP3 File Resorting');
        $io->progressStart(iterator_count($finder));

        foreach ($finder as $file) {
            try {
                $this->processFile($file->getRealPath(), $destinationDir, $io, $dryRun);
                $processedCount++;
            } catch (Exception $e) {
                $errorCount++;
                $io->warning("Skipped file {$file->getFilename()}: {$e->getMessage()}");
            }

            $io->progressAdvance();
        }

        $io->progressFinish();

        $io->success([
            "MP3 resorting completed!",
            'Processed files: ' . $processedCount,
            'Skipped files (errors): ' . $errorCount,
        ]);

        return Command::SUCCESS;
    }

    /**
     * @throws Exception
     */
    private function processFile(string $filePath, string $destinationDir, SymfonyStyle $io, bool $dryRun = false): void
    {
        // Read MP3 metadata
        $mp3Info = new getID3();
        $info = $mp3Info->analyze($filePath);

        $tags = $this->extractTags($info);

        // Extract artist and title information
        $artist = $this->extractArtist($tags);
        $title = $this->extractTitle($tags);

        // Sanitize the artist name for folder creation
        $artistFolder = $this->sanitizeFolderName($artist);
        $artistPath = $destinationDir . DIRECTORY_SEPARATOR . $artistFolder;

        // Create an artist folder if it doesn't exist
        if (!$this->filesystem->exists($artistPath)) {
            if (!$dryRun) {
                $this->filesystem->mkdir($artistPath);
            }
            $io->note('Created artist folder: ' . $artistFolder);
        }

        // Move a file to the artist folder
        $fileName = $artist . ' - ' . $title . '.mp3';
        $destinationPath = $artistPath . DIRECTORY_SEPARATOR . $fileName;

        // Handle file name conflicts
        $counter = 1;
        $originalDestination = $destinationPath;
        while ((!$dryRun && $this->filesystem->exists($destinationPath)) || ($dryRun && file_exists($destinationPath))) {
            $pathInfo = pathinfo($originalDestination);
            $destinationPath = $pathInfo['dirname'] . DIRECTORY_SEPARATOR .
                $pathInfo['filename'] . '_' . $counter . '.' . $pathInfo['extension'];
            $counter++;
        }

        if (!$dryRun) {
            $this->filesystem->rename($filePath, $destinationPath);
        } else {
            $io->note('Would move file: ' . $fileName . ' -> ' . $artistFolder . DIRECTORY_SEPARATOR . basename($destinationPath));
        }
    }

    /**
     * @throws Exception
     */
    private function extractArtist(array $tags): string
    {
        // Try different tag fields for artist information
        $artistFields = ['artist', 'albumartist', 'band', 'performer'];

        foreach ($artistFields as $field) {
            if (!empty($tags[$field])) {
                $artist = $tags[$field];

                if (is_array($artist)) {
                    $artist = $artist[0];
                }

                return $this->extractArtistFromMultiple($artist);
            }
        }

        throw new Exception('No artist information found in metadata');
    }

    private function extractArtistFromMultiple(string $artist): string
    {
        // Handle string with multiple artists separated by common delimiters
        $separators = [';', ',', '/', '&', ' feat.', ' ft.', ' featuring', ' Featuring'];

        foreach ($separators as $separator) {
            if (str_contains($artist, $separator)) {
                $artists = explode($separator, $artist);
                return trim($artists[0]);
            }
        }

        return trim($artist);
    }

    /**
     * @throws Exception
     */
    private function extractTitle(array $tags): string
    {
        // Try different tag fields for artist information
        $titleFields = ['title'];

        foreach ($titleFields as $field) {
            if (!empty($tags[$field])) {
                $title = $tags[$field];

                return trim($title[0]);
            }
        }

        throw new Exception('No title information found in metadata');
    }

    private function sanitizeFolderName(string $name): string
    {

        // Remove or replace invalid characters for folder names
        $invalid = ['<', '>', ':', '"', '|', '?', '*', '/', '\\'];
        $sanitized = str_replace($invalid, '', $name);

        // Remove leading/trailing dots and spaces
        $sanitized = trim($sanitized, '. ');

        // Limit length to avoid filesystem issues
        if (strlen($sanitized) > 100) {
            $sanitized = substr($sanitized, 0, 100);
        }

        return $sanitized ?: 'Unknown_Artist';
    }

    /**
     * @param array $info
     * @return array
     * @throws Exception
     */
    private function extractTags(array $info): array
    {
        match (true) {
            !array_key_exists('tags', $info) => throw new Exception('No tags found in metadata'),
            array_key_exists('id3v2', $info['tags']) => $tags = $info['tags']['id3v2'],
            array_key_exists('id3v1', $info['tags']) => $tags = $info['tags']['id3v1'],
            default => throw new Exception('No id3v2/id3v1 found in tags'),
        };

        return $tags;
    }
}
