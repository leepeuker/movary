<?php declare(strict_types=1);

namespace Movary\Service;

class VoteCountFormatter
{
    public function formatVoteCount(?int $voteCount) : string
    {
        if ($voteCount === null) {
            return '-';
        }

        if ($voteCount < 1000) {
            return (string)$voteCount;
        }

        if ($voteCount < 9950) {
            $voteCountDividedBy1000 = $voteCount / 1000;

            if ((float)$voteCountDividedBy1000 !== floor($voteCountDividedBy1000)) {
                return number_format($voteCountDividedBy1000, 1, '.', '') . 'K';
            }

            return $voteCountDividedBy1000 . 'K';
        }

        if ($voteCount < 999500) {
            $voteCountDividedBy1000 = $voteCount / 1000;

            if ((float)$voteCountDividedBy1000 !== floor($voteCountDividedBy1000)) {
                return number_format($voteCountDividedBy1000, 0, '', '') . 'K';
            }

            return $voteCountDividedBy1000 . 'K';
        }

        $voteCountDividedBy1000000 = $voteCount / 1000000;

        if ((float)$voteCountDividedBy1000000 !== floor($voteCountDividedBy1000000)) {
            if ($voteCountDividedBy1000000 < 1) {
                return '1M';
            }

            return number_format($voteCountDividedBy1000000, 1, '.', '') . 'M';
        }

        return $voteCountDividedBy1000000 . 'M';
    }
}
