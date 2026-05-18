<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

describe('Console bootstrap', function (): void {
    it('boots without errors and lists commands', function (): void {
        $process = new Process(['php', 'bin/console', 'list']);
        $process->run();

        expect($process->isSuccessful())->toBeTrue()
            ->and($process->getOutput())->toContain('music:resort')
            ->and($process->getOutput())->toContain('music:deduplicate')
            ->and($process->getOutput())->toContain('music:fix-extensions')
            ->and($process->getOutput())->toContain('music:clean');
    });
});

describe('music:resort --dry-run', function (): void {
    it('runs on the samples directory without errors', function (): void {
        $process = new Process([
            'php', 'bin/console', 'music:resort',
            'samples/', 'samples/dest-test/',
            '--dry-run',
        ]);
        $process->run();

        expect($process->isSuccessful())->toBeTrue();
    });
});

describe('music:fix-extensions --dry-run', function (): void {
    it('runs on the samples directory without errors', function (): void {
        $process = new Process([
            'php', 'bin/console', 'music:fix-extensions',
            'samples/',
            '--dry-run',
        ]);
        $process->run();

        expect($process->isSuccessful())->toBeTrue();
    });
});

describe('music:deduplicate --dry-run', function (): void {
    it('runs on the samples directory without errors', function (): void {
        $process = new Process([
            'php', 'bin/console', 'music:deduplicate',
            'samples/',
            '--dry-run',
        ]);
        $process->run();

        expect($process->isSuccessful())->toBeTrue();
    });
});

describe('music:clean --dry-run', function (): void {
    it('runs on the samples directory without errors', function (): void {
        $process = new Process([
            'php', 'bin/console', 'music:clean',
            'samples/',
            '--dry-run',
        ]);
        $process->run();

        expect($process->isSuccessful())->toBeTrue();
    });
});
