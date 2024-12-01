<?php declare(strict_types=1);

namespace Movary\Service\Dashboard\Dto;

use Movary\ValueObject\AbstractList;

/**
 * @extends AbstractList<DashboardRow>
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
}
