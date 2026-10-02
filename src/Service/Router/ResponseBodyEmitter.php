<?php declare(strict_types=1);

namespace Movary\Service\Router;

use Closure;
use Movary\ValueObject\Http\Response;
use RuntimeException;

class ResponseBodyEmitter
{
    public function emit(Response $response, ?Closure $beforeBody = null) : void
    {
        $filePath = $response->getFilePath();
        if ($filePath === null) {
            $beforeBody?->__invoke();
            echo $response->getBody();

            return;
        }

        $fileHandle = null;

        try {
            if (is_readable($filePath) === false) {
                throw new RuntimeException('Could not read response file: ' . $filePath);
            }

            $fileHandle = fopen($filePath, 'rb');
            if ($fileHandle === false) {
                throw new RuntimeException('Could not open response file: ' . $filePath);
            }

            $beforeBody?->__invoke();
            fpassthru($fileHandle);
        } finally {
            if (is_resource($fileHandle)) {
                fclose($fileHandle);
            }

            if ($response->shouldDeleteFileAfterSend() === true && is_file($filePath) === true) {
                if (unlink($filePath) === false) {
                    throw new RuntimeException('Could not delete response file: ' . $filePath);
                }
            }
        }
    }
}
