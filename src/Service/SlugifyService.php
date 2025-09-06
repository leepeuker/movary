<?php declare(strict_types=1);

namespace Movary\Service;

class SlugifyService
{
    public function slugify(string $slugOriginal) : string
    {
        $slugToLower = strtolower($slugOriginal);

        $slugReplaced = preg_replace('/[^a-z-]/', '-', $slugToLower);
        if (!is_string($slugReplaced)) {
            throw new \RuntimeException('Could not slugify string: ' . $slugToLower);
        }

        $slugReplaced = preg_replace('/-+/', '-', $slugReplaced);
        if (!is_string($slugReplaced)) {
            throw new \RuntimeException('Could not slugify string: ' . $slugToLower);
        }

        return trim($slugReplaced, '-');
    }
}
