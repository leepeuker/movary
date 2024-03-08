<?php declare(strict_types=1);

namespace Movary\Service\Dashboard\Dto;

use Movary\ValueObject\AbstractList;

/**
 * @method DashboardRow[] getIterator()
 * @psalm-suppress ImplementedReturnTypeMismatch
 */
class DashboardRowList extends AbstractList
{
    public static function create(DashboardRow ...$dashboardRows) : self
    {
        $list = new self();

        foreach ($dashboardRows as $dashboardRow) {
            $list->add($dashboardRow);
        }

        return $list;
    }

    public function add(DashboardRow $dashboardRow) : void
    {
        $this->data[] = $dashboardRow;

        ksort($this->data);
    }

    public function addAtOffset(int $position, DashboardRow $dashboardRow) : void
    {
        $this->data[$position] = $dashboardRow;

        ksort($this->data);
    }

    public function asArray() : array
    {
        $serialized = [];
        foreach($this->data as $row) {
            $serialized[] = [
                'row' => $row->getName(),
                'id' => $row->getId(),
                'isExtended' => $row->isExtended(),
                'isVisible' => $row->isVisible()
            ];
        }
        return $serialized;
    }
}
