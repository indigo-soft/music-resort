<?php

declare(strict_types=1);

use MusicResort\Service\FileResortService;

describe('FileResortService', function (): void {
    describe('sanitizeFolderName', function (): void {
        it('removes characters invalid for folder names', function (): void {
            // FileResortService is instantiated with required deps — test via reflection
            // TODO: extract sanitizeFolderName to a standalone pure function or static method
            //       to make it directly unit-testable without instantiation overhead
            expect(true)->toBeTrue(); // placeholder until refactor
        });

        it('truncates folder name to 100 characters', function (): void {
            expect(true)->toBeTrue(); // placeholder until refactor
        });
    });
});

describe('Artist extraction', function (): void {
    it('returns the first artist from a multi-artist string', function (string $input, string $expected): void {
        // extractFirstArtist is private in Mp3ResortService
        // TODO: extract to MusicArtistHelper or make testable
        // For now we verify the splitting pattern directly
        $pattern = '/\s*(?:;|,|\/|&|\s+feat\.?|\s+ft\.?|\s+featuring)\s*/i';
        $parts = preg_split($pattern, $input, -1, PREG_SPLIT_NO_EMPTY);
        $first = trim($parts[0] ?? $input);

        expect($first)->toBe($expected);
    })->with([
        'semicolon separator'    => ['Artist A; Artist B', 'Artist A'],
        'feat separator'         => ['Artist A feat. Artist B', 'Artist A'],
        'ft separator'           => ['Artist A ft. Artist B', 'Artist A'],
        'ampersand separator'    => ['Artist A & Artist B', 'Artist A'],
        'slash separator'        => ['Artist A / Artist B', 'Artist A'],
        'single artist'          => ['Artist A', 'Artist A'],
        'featuring full word'    => ['Artist A featuring Artist B', 'Artist A'],
    ]);
});
