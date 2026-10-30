<?php declare(strict_types=1);

namespace Movary\HttpController\Api\RequestMapper;

use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\Service\Dashboard\DashboardFactory;
use Movary\ValueObject\Http\Request;

class DashboardRowsRequestMapper
{
    public function __construct(
        private readonly RequestMapper $requestMapper,
        private readonly DashboardFactory $dashboardFactory,
    ) {
    }

    /** @return array{visibleRows: string, extendedRows: string, orderRows: string} */
    public function mapRows(Request $request) : array
    {
        $data = $this->requestMapper->mapJsonObject($request);
        $rows = $data['rows'] ?? null;
        $defaultRows = $this->dashboardFactory->createDefaultDashboardRows();
        $rowIds = [];
        foreach ($defaultRows as $row) {
            $rowIds[$row->getKey()] = $row->getId();
        }

        if (is_array($rows) === false || array_is_list($rows) === false || count($rows) !== count($rowIds)) {
            throw InvalidApiRequest::invalidField('rows', 'must contain every dashboard row exactly once');
        }

        $orderedRows = [];
        $seenKeys = [];
        foreach ($rows as $index => $row) {
            if (is_array($row) === false) {
                throw InvalidApiRequest::invalidField('rows[' . $index . ']', 'must be an object');
            }

            $key = $row['key'] ?? null;
            if (is_string($key) === false || array_key_exists($key, $rowIds) === false || isset($seenKeys[$key])) {
                throw InvalidApiRequest::invalidField('rows[' . $index . '].key', 'must be a unique dashboard row key');
            }
            $seenKeys[$key] = true;

            $visible = $this->requireBoolean($row, 'visible', $index);
            $initiallyExpanded = $this->requireBoolean($row, 'initiallyExpanded', $index);
            $position = $this->requirePosition($row, $index, count($rowIds));
            if (isset($orderedRows[$position])) {
                throw InvalidApiRequest::invalidField('rows[' . $index . '].position', 'must be unique');
            }

            $orderedRows[$position] = ['id' => $rowIds[$key], 'visible' => $visible, 'initiallyExpanded' => $initiallyExpanded];
        }

        ksort($orderedRows);

        return $this->encodeRows($orderedRows);
    }

    /**
     * @param array<int, array{id: int, visible: bool, initiallyExpanded: bool}> $orderedRows
     * @return array{visibleRows: string, extendedRows: string, orderRows: string}
     */
    private function encodeRows(array $orderedRows) : array
    {
        $visibleRows = [];
        $extendedRows = [];
        $orderRows = [];
        foreach ($orderedRows as $row) {
            $orderRows[] = $row['id'];
            if ($row['visible']) {
                $visibleRows[] = $row['id'];
            }
            if ($row['initiallyExpanded']) {
                $extendedRows[] = $row['id'];
            }
        }

        return [
            'visibleRows' => implode(';', $visibleRows),
            'extendedRows' => implode(';', $extendedRows),
            'orderRows' => implode(';', $orderRows),
        ];
    }

    /** @param array<string, mixed> $row */
    private function requireBoolean(array $row, string $field, int $index) : bool
    {
        $value = $row[$field] ?? null;
        if (is_bool($value) === false) {
            throw InvalidApiRequest::invalidField('rows[' . $index . '].' . $field, 'must be a boolean');
        }

        return $value;
    }

    /** @param array<string, mixed> $row */
    private function requirePosition(array $row, int $index, int $rowCount) : int
    {
        $position = $row['position'] ?? null;
        if (is_int($position) === false || $position < 0 || $position >= $rowCount) {
            throw InvalidApiRequest::invalidField('rows[' . $index . '].position', 'must be an integer from 0 to ' . ($rowCount - 1));
        }

        return $position;
    }
}
