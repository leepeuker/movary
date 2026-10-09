<?php declare(strict_types=1);

namespace Movary\Service;

use InvalidArgumentException;
use Movary\Util\File;
use RuntimeException;

class AssetUrlGenerator
{
    /** @var array<string, string> */
    private array $versions = [];

    public function __construct(
        private readonly File $file,
        private readonly string $publicDirectory,
        private readonly string $applicationUrl,
    ) {
    }

    public function generate(string $relativePath) : string
    {
        $this->validateRelativePath($relativePath);

        if (isset($this->versions[$relativePath]) === false) {
            $absolutePath = rtrim($this->publicDirectory, '/') . '/' . $relativePath;
            if ($this->file->fileExists($absolutePath) === false) {
                throw new RuntimeException('Asset does not exist: ' . $relativePath);
            }

            $this->versions[$relativePath] = substr(
                hash('sha256', $this->file->readFile($absolutePath)),
                0,
                12,
            );
        }

        return rtrim($this->applicationUrl, '/') . '/' . $relativePath . '?v='
            . rawurlencode($this->versions[$relativePath]);
    }

    private function validateRelativePath(string $relativePath) : void
    {
        $segments = explode('/', $relativePath);
        if ($relativePath === ''
            || str_starts_with($relativePath, '/')
            || str_contains($relativePath, '\\')
            || in_array('', $segments, true)
            || in_array('.', $segments, true)
            || in_array('..', $segments, true)
            || preg_match('/^[a-zA-Z0-9._\/-]+$/D', $relativePath) !== 1
        ) {
            throw new InvalidArgumentException('Invalid asset path: ' . $relativePath);
        }
    }
}
