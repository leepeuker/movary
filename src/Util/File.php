<?php declare(strict_types=1);

namespace Movary\Util;

use RuntimeException;
use Throwable;

class File
{
    public function createDirectory(string $path, int $mode) : void
    {
        try {
            if (is_dir($path) === false && mkdir($path, $mode, true) === false && is_dir($path) === false) {
                throw new RuntimeException();
            }
        } catch (Throwable $error) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $path));
        }
    }

    public function createFile(string $filename, string $data = '') : void
    {
        file_put_contents($filename, $data);
    }

    public function createTmpFile() : string
    {
        $tempFileName = tempnam(sys_get_temp_dir(), 'movary');

        if ($tempFileName === false) {
            throw new \RuntimeException('Could not create tmp file.');
        }

        return $tempFileName;
    }

    public function deleteDirectoryContent(string $path) : void
    {
        $files = glob(rtrim($path, '/') . '/*');

        if ($files === false) {
            throw new RuntimeException('Could not get files in directory: ' . $path);
        }

        foreach ($files as $file) {
            if (is_dir($file) === true) {
                $this->deleteDirectoryContentRecursively($file);

                continue;
            }

            unlink($file);
        }
    }

    public function deleteDirectoryContentRecursively(string $path) : void
    {
        $files = glob(rtrim($path, '/') . '/*');

        if ($files === false) {
            throw new RuntimeException('Could not get files in directory: ' . $path);
        }

        foreach ($files as $file) {
            if (is_dir($file) === true) {
                $this->deleteDirectoryContentRecursively($file);

                continue;
            }

            unlink($file);
        }

        rmdir($path);
    }

    public function deleteFile(string $imageFile) : void
    {
        unlink($imageFile);
    }

    public function fileExists(string $fileName) : bool
    {
        return file_exists($fileName) === true;
    }
}
