<?php declare(strict_types=1);

namespace Movary\Service\Netflix;

use Exception;
use League\Csv\Reader;
use Movary\Service\Netflix\Dto\NetflixActivityItem;
use Movary\Service\Netflix\Dto\NetflixActivityItemList;
use Movary\ValueObject\Date;
use Psr\Log\LoggerInterface;

class NetflixActivityCsvParser
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function parseNetflixActivityCsv(string $csvFilepath) : NetflixActivityItemList
    {
        $items = NetflixActivityItemList::create();

        try {
            $csvFile = Reader::createFromPath($csvFilepath);
            $csvFile->setHeaderOffset(0);

            foreach ($csvFile->getRecords() as $csvRecord) {
                if (isset($csvRecord['Title'], $csvRecord['Date']) === false) {
                    continue;
                }

                $items->add(NetflixActivityItem::create($csvRecord['Title'], Date::createFromString($csvRecord['Date'])));
            }
        } catch (Exception $e) {
            $this->logger->warning('Netflix: Could not parse activity csv', ['exception' => $e]);
        }

        return $items;
    }
}
