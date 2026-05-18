# ADR-0003: getID3 as the Metadata Reading Library

## Status

Accepted — 2026-05-09

## Context

The project needs to read audio metadata (artist, title, duration, bitrate, format) from MP3, FLAC, and M4A files in
order to sort them into artist folders, deduplicate by artist+title, fix file extensions, and validate file integrity.

The solution must work as a PHP library without external process calls, handle all three target formats, and reliably
extract tags across different tag formats (ID3v1, ID3v2, Vorbis comments, QuickTime atoms).

## Considered Alternatives

### ffprobe (via Symfony Process)

**Pros:**
- Already a system dependency (bundled with FFmpeg, see ADR-0001)
- Handles virtually every format
- JSON output is easy to parse

**Cons:**
- Requires a shell call for every file — significant overhead when scanning thousands of files
- Adds process-spawning complexity to a metadata-only task
- No PHP-native API; error handling relies on exit codes and stderr parsing

### ID3 (PHP extension)

**Pros:**
- Native PHP extension, very fast

**Cons:**
- MP3/ID3 only — no FLAC or M4A support
- Requires compilation or `pecl install` — not available everywhere
- No Composer package; adds a system dependency

### php-id3tag / similar micro-libraries

**Pros:**
- Lightweight

**Cons:**
- Cover only ID3v2; no support for Vorbis comments or QuickTime atoms
- Poorly maintained, no significant adoption

## Decision

Use **`james-heinrich/getid3`** as the sole metadata reading library.

getID3 is invoked in `MusicMetadataService` and provides a single unified array structure regardless of the underlying
tag format. Tag lookup order: `id3v2` → `id3v1` → `quicktime` → `vorbiscomment`.

## Rationale

**Format coverage.** getID3 reads ID3v1/v2 (MP3), Vorbis comments (FLAC), and QuickTime atoms (M4A) through a single
API call — covering all three formats the project targets.

**Pure PHP.** No system dependencies, no process spawning. Works anywhere PHP runs.

**Rich metadata.** Beyond tags, getID3 exposes `audio.bitrate`, `playtime_seconds`, `audio.dataformat`, and
`fileformat` — all used by the project for deduplication ranking, integrity checks, and extension fixing.

**Stability.** The library has been maintained since 2003, is available on Packagist, and has no conflicting
dependencies with the rest of the stack.

**Isolation.** Metadata reading is intentionally kept separate from audio processing (FFmpeg, ADR-0001). getID3 is
read-only; it never modifies files.

## Consequences

### Positive

- Single unified interface for all three formats
- No system dependencies; works on Linux, macOS, Windows
- `MusicMetadataService` encapsulates all getID3 calls — the rest of the codebase never touches getID3 directly
- Tags and technical metadata (bitrate, duration, format) are available in one pass

### Negative

- getID3 loads its module files on every `analyze()` call — noticeable overhead at scale (thousands of files);
  caching metadata results in SQLite should be evaluated if performance becomes an issue
- Warnings from getID3 (e.g. truncated frames) are currently treated as fatal and cause the file to be skipped;
  this may be too strict for some real-world collections

### Neutral

- `MusicMetadataService` is the single integration point; swapping the library later requires changes only there
- Tag priority order (`id3v2` → `id3v1` → `quicktime` → `vorbiscomment`) is defined in `MusicMetadataService`
  and can be adjusted without touching consumers
