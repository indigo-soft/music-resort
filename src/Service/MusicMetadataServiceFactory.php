<?php

declare(strict_types=1);

namespace MusicResort\Service;

final class MusicMetadataServiceFactory
{
    /**
     * @param string $filePath
     * @return MusicMetadataService
     */
    public function createFor(string $filePath): MusicMetadataService
    {
        return new MusicMetadataService($filePath);
    }
}
