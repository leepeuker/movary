<?php declare(strict_types=1);

namespace Movary\Application\Service\Letterboxd;

class ImportWatchedMovies
{
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function execute(int $userId, string $historyCsvPath) : void
    {
        unlink($historyCsvPath);
    }
}
