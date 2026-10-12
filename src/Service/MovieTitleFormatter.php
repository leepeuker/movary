<?php declare(strict_types=1);

namespace Movary\Service;

use Movary\ValueObject\MovieTitleDisplay;

class MovieTitleFormatter
{
    public function format(string $localizedTitle, ?string $originalTitle, MovieTitleDisplay $display) : string
    {
        if (empty($localizedTitle) === true) {
            return $originalTitle ?? '';
        }

        if (empty($originalTitle) === true || $originalTitle === $localizedTitle) {
            return $localizedTitle;
        }

        if ($display->displaysOriginal() === true) {
            return $originalTitle;
        }

        if ($display->displaysBoth() === true) {
            return $localizedTitle . ' (' . $originalTitle . ')';
        }

        return $localizedTitle;
    }
}
