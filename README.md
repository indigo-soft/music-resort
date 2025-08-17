# Music Resort/Deduplicate Tool

Console commands for automatically sorting music files (mp3, flac, m4a) by artists in separate folders and deduplicating
audio.

Read this in Ukrainian: [README_uk](./docs/README_uk.md)

## Installation

1. #### Install dependencies:

    ```
    composer install
    ```

2. #### Make the console file executable (Linux/Mac):

    ```
    chmod +x bin/console
    ```

## Usage

- ### Sort music into artist folders

    ```
    php bin/console mp3:resort <source_directory> <destination_directory>
    ```

    - **Windows:**

        ```
        php bin/console mp3:resort "C:\Music\Unsorted" "C:\Music\Sorted"
        ```

    - **Linux/Mac:**

        ```
        php bin/console mp3:resort "/home/user/music/unsorted" "/home/user/music/sorted"
        ```

- ### Deduplicate music in a folder

    ```
    php bin/console mp3:deduplicate <source_directory>
    ```

    - **Windows:**

        ```
        php bin/console mp3:deduplicate "C:\Music\Unsorted"
        ```

    - **Linux/Mac:**

        ```
        php bin/console mp3:deduplicate "/home/user/music/unsorted"
        ```

## Features

### What the command does:

1. **Scans** the source folder for MP3 files
2. **Reads metadata** of each file
3. **Extracts artist information** from tags
4. **Handles multiple artists** — picks the first one
5. **Creates folders** named after artists
6. **Moves files** into corresponding folders
7. **Handles errors** — skips corrupted files

### Simulation mode (--dry-run):

- **Does not change the file system** — no files are moved
- **Does not create folders** — only simulates their creation
- **Shows all messages** — same as in normal execution
- **Displays an action plan** — what will be done with each file
- **Safe test** — check the result without risk

### Artist handling:

- Looks in tags: `artist`, `albumartist`, `band`, `performer`
- Splits multiple artists by: `;`, `,`, `/`, `&`, `feat.`, `ft.`, `featuring`
- Sanitizes folder names (removes invalid characters)
- Limits folder name length (100 characters)

### Error handling:

- Files without metadata — skipped
- Corrupted MP3 files — skipped
- Files without artist information — skipped
- Filename conflicts — automatically renamed

## Project structure

```
├── bin/
│   └── console                         # Console application entry point
├── src/
│   ├── Command/
│   │   ├── ResortMp3Command.php        # Resort command
│   │   └── DeduplicateMp3Command.php   # Deduplication command
│   ├── Service/
│   │   ├── Mp3ResortService.php        # Resorting logic
│   │   └── Mp3DeduplicateService.php   # Deduplication logic
│   └── ...
├── composer.json                       # Project dependencies
└── README.md                           # Documentation
```

## Dependencies

- **PHP 8.4+** — minimum PHP version
- **symfony/console** — console interface
- **symfony/finder** — file search
- **symfony/filesystem** — filesystem operations

## Localization

- All messages are localized via the global function `__()` and translation files in the `lang` directory (e.g.,
  `lang/en/console.php`).
- Default locale — `en` (see `config/app.php` → `default_lang`).
- You can change the locale via .env: `DEFAULT_LANG=uk`, or in code:
  `\Root\MusicLocal\Service\LocalizationService::setLocale('uk')`.
- To add a new language: create the directory `lang/<locale>/` and the translation file `console.php` with the same
  keys.

Example .env:

```dotenv
# Force dry-run for all commands
DEBUG=true
# CLI interface locale
DEFAULT_LANG=uk
```

## Example output

### Normal mode:

```
MP3 File Resorting
==================

 ! [NOTE] Created artist folder: The Beatles

 ! [NOTE] Created artist folder: Queen

 ! [WARNING] Skipped file corrupted.mp3: No artist information found in metadata

 3/3 [============================] 100%

 [OK] MP3 resorting completed!
 [OK] Processed files: 2
 [OK] Skipped files (errors): 1
```

## Additional capabilities

### View help:

```
php bin/console mp3:resort --help
php bin/console mp3:deduplicate --help
```

### Simulation mode:

```
php bin/console mp3:resort <source> <destination> --dry-run
php bin/console mp3:deduplicate <source> --dry-run
```

### Show version:

```
php bin/console --version
```

### List available commands:

```
php bin/console list
```

## Testing (planned)

There are currently no automated tests in this repository. Below is the planned structure and scenarios that may be
added later.

The project will include a full set of tests for code quality, written with the Pest framework.

### Test structure:

```
tests/
├── Unit/                     # Unit tests
│   └── ResortMp3CommandTest.php
├── Integration/              # Integration tests
│   └── ResortMp3CommandIntegrationTest.php
└── Fixtures/                 # Helper classes for tests
    └── Mp3TestHelper.php
```

### Running tests:

1. #### Install development dependencies:

    ```
    composer install
    ```
2. #### Run all tests:
    ```
    php vendor/bin/pest
    ```

3. #### Run only unit tests:
    ```
    php vendor/bin/pest tests/Unit
    ```

4. #### Run only integration tests:

   ```
   php vendor/bin/pest tests/Integration
   ```

5. #### Run tests with verbose output:

    ```
    php vendor/bin/pest --verbose
    ```

### Test coverage:

**Unit tests:**

- `sanitizeFolderName()` — folder name sanitization
- `extractArtist()` — artist extraction
- Handling special characters
- Handling multiple artists
- Handling long names

**Integration tests:**

- Full command execution flow
- Error handling (non-existent folders)
- Destination folder creation
- Handling empty folders
- Handling invalid MP3 files
- Command configuration

**Test data:**

- Generating valid MP3 files with metadata
- Testing edge cases
- Handling Unicode characters
- Files with different tag formats

### Test configuration:

Tests are configured via `tests/Pest.php`:

- Autoloading through `vendor/autoload.php`
- Using `PHPUnit\\Framework\\TestCase` for all tests
- Colored output
- Support for datasets and functional tests
- Code coverage for the `src/` directory

## Recommendations

1. **Back up your files** before use
2. **Check permissions** for destination folders
3. **Use absolute paths** to avoid errors
4. **Files with errors** may require manual handling
