<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Router;

use Movary\Service\Router\ResponseBodyEmitter;
use Movary\ValueObject\Http\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(ResponseBodyEmitter::class)]
class ResponseBodyEmitterTest extends TestCase
{
    public function testEmitsStringBody() : void
    {
        $subject = new ResponseBodyEmitter();

        ob_start();
        $subject->emit(Response::createCsv('content'));
        $output = ob_get_clean();

        self::assertSame('content', $output);
    }

    public function testEmitsAndDeletesTemporaryFile() : void
    {
        $filePath = $this->createTemporaryFile('zip content');
        $subject = new ResponseBodyEmitter();

        ob_start();
        $subject->emit(Response::createZipDownload($filePath, 'export.zip'));
        $output = ob_get_clean();

        self::assertSame('zip content', $output);
        self::assertFileDoesNotExist($filePath);
    }

    public function testRejectsMissingFile() : void
    {
        $subject = new ResponseBodyEmitter();
        $filePath = sys_get_temp_dir() . '/movary-missing-response-file-' . bin2hex(random_bytes(8));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not read response file: ' . $filePath);

        $subject->emit(Response::createZipDownload($filePath, 'export.zip'));
    }

    private function createTemporaryFile(string $content) : string
    {
        $filePath = tempnam(sys_get_temp_dir(), 'movary-test-');
        self::assertIsString($filePath);
        file_put_contents($filePath, $content);

        return $filePath;
    }
}
