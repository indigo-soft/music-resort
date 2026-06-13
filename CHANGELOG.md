# Changelog

## [1.2.0](https://github.com/indigo-soft/music-resort/compare/v1.1.2...v1.2.0) (2026-06-13)

### Features

* **command:** add metadata:enrich command ([6be29ed](https://github.com/indigo-soft/music-resort/commit/6be29edc493e539f2fa0644d7563dceffa5fdc8b))
* **command:** add metadata:scan command ([405d327](https://github.com/indigo-soft/music-resort/commit/405d3276dba66310d4e5a6d93efd8a89a3e7aeae))
* **command:** add migrate and migrate:refresh commands ([7e55077](https://github.com/indigo-soft/music-resort/commit/7e550776b22512bc5d105a1015012565257348c3))
* **command:** rename migrate to migrate:up for proper namespace grouping ([9908576](https://github.com/indigo-soft/music-resort/commit/9908576b5fd5e8015451fb33a32222f72bf7527a))
* **config:** add Last.fm API settings ([e8e94eb](https://github.com/indigo-soft/music-resort/commit/e8e94ebffc2a052d179baeb0f198d5617b9dc45e))
* **config:** update .env.example with database and Last.fm variables ([83f6fec](https://github.com/indigo-soft/music-resort/commit/83f6feca6bac8fdd0fb2d3e523a8459b68e153e8))
* **db:** add lastfm_artist_tags migration ([ab0b309](https://github.com/indigo-soft/music-resort/commit/ab0b30975fa3f05e65150650ede765f7f20024d1))
* **db:** migrate from SQLite to MariaDB; fix DDL transaction wrapping ([cf1ccb3](https://github.com/indigo-soft/music-resort/commit/cf1ccb3bf70bf0bc19d388fa271c2ffdd172477a))
* **db:** migrate sql files to db/migrations/ as separate sql files ([f2edbb8](https://github.com/indigo-soft/music-resort/commit/f2edbb8d991ed68db6e86f248863963ef2fd01b1))
* **db:** move database file path to db/database/music.sqlite ([0e4040f](https://github.com/indigo-soft/music-resort/commit/0e4040f8fcd459260ea245b9cb608bb2690eb0c1))
* **db:** wire migrate commands in console, remove auto-migration from bootstrap ([c16dad6](https://github.com/indigo-soft/music-resort/commit/c16dad6952e0096bf54df9b574b468f97de9d5e7))
* **deps:** add ext-curl to composer.json require ([be2042b](https://github.com/indigo-soft/music-resort/commit/be2042b873051c149c16a298eb5fcd7b3928cd8e))
* **lang:** add metadata:enrich and metadata:scan translation keys ([ed921ff](https://github.com/indigo-soft/music-resort/commit/ed921ffa9b04ef36c165cb7336a275accb31b6f0))
* **lang:** add migration command translations and MIGRATION_PRESERVE_TABLES env ([110c752](https://github.com/indigo-soft/music-resort/commit/110c752faca6c68577471c6b9ea6a05287f8806d))
* **service:** add Last.fm enrichment pipeline ([fd0affd](https://github.com/indigo-soft/music-resort/commit/fd0affd8b761599710e7d0ec4c12bc3f02d5c9c7))
* **service:** add MetadataScanService for collection inventory ([c3aa4bf](https://github.com/indigo-soft/music-resort/commit/c3aa4bf3da171ce8fcf71db253bb8ac0c0af482a))
* **service:** extend MusicMetadataService with inventory getters and detectTagSource ([92f0217](https://github.com/indigo-soft/music-resort/commit/92f02173deacf6aada28cfc6b40535b658eaaccb))

### Bug Fixes

* **config:** require ext-pdo and ext-pdo_sqlite extensions ([5c034d3](https://github.com/indigo-soft/music-resort/commit/5c034d3a6531ac5f99fcaa983354e9e17f08eebf))
* **db:** checkpoint wal on exit to allow ide connections after cli process ends ([e24db85](https://github.com/indigo-soft/music-resort/commit/e24db857179e142bf22f7571ebe96609f4a6330e))
* **db:** switch journal mode from wal to delete for ide compatibility ([1852aa8](https://github.com/indigo-soft/music-resort/commit/1852aa8f2b751a2226d7b60a25f431a2db33d778))

## [1.1.2](https://github.com/indigo-soft/music-resort/compare/v1.1.1...v1.1.2) (2026-05-25)

### Features

* **config:** add github token validation to release script ([cf36316](https://github.com/indigo-soft/music-resort/commit/cf363162c1b1803c9f87a17c06b6c5aeb53c7b69))

## [1.1.1](https://github.com/indigo-soft/music-resort/compare/v1.1.0...v1.1.1) (2026-05-25)

### Features

* **config:** add init and start scripts, move release-it and gitmessage to root ([21df03a](https://github.com/indigo-soft/music-resort/commit/21df03abc75f4f5e26be5d88d892fc90f5c9cdd3))
* **config:** add init and start scripts, move release-it and gitmessage to root ([16a898f](https://github.com/indigo-soft/music-resort/commit/16a898f70aab58a7285767b06072de7b41a57f15))

## 1.1.0 (2026-05-18)

### Features

* Add command and service for cleaning empty directories ([02004c7](https://github.com/indigo-soft/music-resort/commit/02004c787d124772983826bdfe131db6802ff5a6))
* Add ConfigException for configuration error handling ([ffbf319](https://github.com/indigo-soft/music-resort/commit/ffbf3196ab1bdf4d1d105c92acc7e99a83b21500))
* Add env config ([e348f19](https://github.com/indigo-soft/music-resort/commit/e348f192c1575f3ec2e2c3577e1964f9cbb9ea28))
* Add file extension fixing command and service ([888978a](https://github.com/indigo-soft/music-resort/commit/888978a39eb7f0fbbc73e0007ab4f783785a4cec))
* add genre handling in metadata extraction and initialize genres array ([d6b1f3c](https://github.com/indigo-soft/music-resort/commit/d6b1f3c4b06412c8ac95a14b902aec0de4f9a9f8))
* add genre handling in metadata extraction and initialize genres array ([0bf9e92](https://github.com/indigo-soft/music-resort/commit/0bf9e92afcda8879a65ba21ddf60a09367b61fb9))
* Add launcher scripts for console commands and update PHP version requirements ([4f7031b](https://github.com/indigo-soft/music-resort/commit/4f7031baf264bee349d04c535fbe09ad115e97c5))
* Add Localization ([2ca045d](https://github.com/indigo-soft/music-resort/commit/2ca045df9db7c44d050cd0677888f34230402fad))
* Add MP3 cleaning command and service ([32c9006](https://github.com/indigo-soft/music-resort/commit/32c9006b2d53da1f19bfb7ac7e933f6a90d7d302))
* Add MP3 deduplication command and service ([764e9a5](https://github.com/indigo-soft/music-resort/commit/764e9a51e0c7cf7daaf7c8aa07c21089dd457518))
* Add parallel processing to MP3 resort command ([ffaa392](https://github.com/indigo-soft/music-resort/commit/ffaa392c995045f0113d0b6f20e66bf9937acaca))
* Add RunAllCommand to orchestrate music maintenance steps ([40d1d33](https://github.com/indigo-soft/music-resort/commit/40d1d3315c950db8edb498723e60876c182a0e8c))
* Add XML schema and configuration files for framework commands ([33ddbe1](https://github.com/indigo-soft/music-resort/commit/33ddbe104271946f0635073411342262b45d3773))
* Enhance MP3 deduplication with modular methods ([f21e112](https://github.com/indigo-soft/music-resort/commit/f21e112802874119d20f248a608467c67bd34b59))
* Improve configuration handling and MP3 resorting logic ([bf33eaf](https://github.com/indigo-soft/music-resort/commit/bf33eafce448092ee86a31cd3694aede7250103f))
* Refactor MP3 resort service with improved metadata handling ([1304cac](https://github.com/indigo-soft/music-resort/commit/1304cac78973ff20f9105e10ebf3051567c3acb5))
* Replace `getID3` with `MusicMetadataService` for metadata extraction ([d7a2a5d](https://github.com/indigo-soft/music-resort/commit/d7a2a5df220819a98ac2d3b2a72fc1c14d4ce665))
* Replace SymfonyStyle with custom ConsoleStyle ([acbae38](https://github.com/indigo-soft/music-resort/commit/acbae38c57d4aa659891daedb5f96d34f1bbbda3))
* Update MP3 resorting ([0d0d299](https://github.com/indigo-soft/music-resort/commit/0d0d299b3d08a5a0ed503042d01128c804fdb058))
* Update PHP CS Fixer standards and migration options in php.xml ([ec11906](https://github.com/indigo-soft/music-resort/commit/ec11906e9ad638e17a534eb768a7f2c824c9b64b))

### Bug Fixes

* add missing genre error message in metadata ([cb53f20](https://github.com/indigo-soft/music-resort/commit/cb53f201dfc87ab8dbd6f0d47eb47b0e4f8ac96a))
* handle uppercase file extensions and utf-8 characters correctly ([d66487c](https://github.com/indigo-soft/music-resort/commit/d66487c8305d891627510e37a27b22ea004ebe4c))

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

This file is generated automatically by [release-it](https://github.com/release-it/release-it)
on each release. **Do not edit manually.**

<!-- CHANGELOG -->
