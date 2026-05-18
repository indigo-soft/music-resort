# ADR-0001: FFmpeg as Audio Processing Tool

## Status

Accepted — 2026-05-09

## Context

The project requires a tool for audio file processing during the music collection preparation stage, before the main
organisation pipeline runs. Required functionality:

- Silence trimming from the beginning and end of a track (`silenceremove` filter)
- Volume normalisation across tracks (EBU R128 / loudnorm)
- Transcoding of non-MP3 formats (FLAC, OGG, M4A, WAV, AAC, etc.) to MP3 at high bitrate
- Integration with PHP 8.5 CLI via the Symfony Process Component

Processing is triggered by a dedicated command (`audio:process`) that runs before the main organisation pipeline.
Original files are preserved in a mirrored `_originals/` directory and removed by a separate command
(`audio:cleanup-originals`) after manual verification of results.

## Considered Alternatives

### SoX (Sound eXchange)

**Pros:**
- Simple syntax for silence trimming
- Built-in normalisation (`--norm`)

**Cons:**
- No native MP3 support — requires separate installation of `libmp3lame` / `lame`
- Less active development, infrequent releases
- Weak support for modern formats (M4A, AAC, Opus)
- Narrower ecosystem and documentation compared to FFmpeg

### php-ffmpeg (PHP package, `php-ffmpeg/php-ffmpeg`)

**Pros:**
- PHP-native API, no direct shell calls required

**Cons:**
- A wrapper around FFmpeg — effectively an extra dependency on top of FFmpeg itself
- Lags behind new FFmpeg features and filters
- Imposes limitations on complex filter graphs (e.g. two-pass `loudnorm`)
- Less flexibility for non-standard scenarios

### getID3 (PHP library)

Not considered for audio processing — the library is designed exclusively for reading metadata (ID3, Vorbis comments,
etc.) and has no audio stream manipulation capabilities. It is used in the project at the `metadata:enrich` stage.

## Decision

Use **FFmpeg directly** via the Symfony Process Component.

FFmpeg is invoked as an external process without intermediate PHP wrappers. This gives full control over filter graphs
and arguments without the constraints of abstraction layers.

## Rationale

**Format support.** FFmpeg supports virtually all audio formats out of the box (MP3, FLAC, OGG, M4A, WAV, AAC, Opus,
WMA), satisfying the transcoding requirement for collections with mixed formats.

**Silence trimming.** The built-in `silenceremove` filter strips silence from the beginning and end of a track with a
configurable threshold (dB) and minimum silence duration.

**Volume normalisation.** The `loudnorm` filter implements the EBU R128 standard — the same standard used by Spotify,
YouTube, and Apple Music. Two-pass mode (`linear=true`) ensures accurate target loudness (-14 LUFS by default) without
clipping or artefacts, which is superior to a simple gain boost.

**Transcoding.** `libmp3lame` support allows transcoding any format to MP3 with bitrate control (VBR/CBR), satisfying
the collection unification requirement.

**Integration.** The Symfony Process Component provides a convenient PHP API for launching external processes with
timeout support, stdout/stderr reading, and exit code handling — without additional dependencies.

**Maturity and reliability.** FFmpeg is the industry standard with active development, comprehensive documentation, and
predictable behaviour on Linux/macOS/Windows.

## Consequences

### Positive

- A single tool covers all three tasks: trimming, normalisation, transcoding
- Full control over parameters without abstraction constraints
- EBU R128 normalisation ensures consistent loudness across tracks in the collection
- No additional PHP dependencies for audio processing

### Negative

- FFmpeg is a system dependency — must be installed separately from Composer
- The minimum FFmpeg version must be documented (recommended: >= 4.4)
- Two-pass `loudnorm` doubles processing time compared to single-pass

### Neutral

- Silence trimming parameters (dB threshold, minimum duration) are moved to configuration and refined at
  implementation time
- Processing results (duration before/after, file size, status) are logged to an SQLite table `audio_processing`
  for verification before originals are deleted
