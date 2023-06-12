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
    }
}
