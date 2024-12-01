<?php declare(strict_types=1);

namespace Movary\ValueObject;

use Countable;
use Iterator;
use ReturnTypeWillChange;
use Stringable;
use function array_keys;
use function count;
use function spl_object_hash;

/**
 * @template TKey
 * @template TValue
 * @implements Iterator<TKey, TValue>
 */
abstract class AbstractMap implements Countable, Iterator
{
    //region constants and properties
    private int $currentIteratorIndex = 0;

    private array $objectHashes = [];

    private array $values = [];

    //endregion constants and properties

    //region instancing
    protected function __construct()
    {
    }
    //endregion instancing

    //region methods
    public function count() : int
    {
        return count($this->values);
    }

    /**
     * @return TValue
     */
    #[ReturnTypeWillChange]
    public function current()
    {
        return $this->values[$this->objectHashes[$this->currentIteratorIndex]]['value'];
    }

    protected function getContainedKeys() : array
    {
        $keys = [];

        foreach ($this->values as $mapEntry) {
            $keys[] = $mapEntry['key'];
        }

        return $keys;
    }

    /**
     * @return TKey
     */
    #[ReturnTypeWillChange]
    public function key()
    {
        return $this->values[$this->objectHashes[$this->currentIteratorIndex]]['key'];
    }

    public function next() : void
    {
        $this->currentIteratorIndex++;
    }

    public function rewind() : void
    {
        $this->currentIteratorIndex = 0;
    }

    public function valid() : bool
    {
        if (isset($this->objectHashes[$this->currentIteratorIndex])) {
            return isset($this->values[$this->objectHashes[$this->currentIteratorIndex]]);
        }

        return false;
    }

    /**
     * @param TKey $key
     * @return TValue|null
     */
    #[ReturnTypeWillChange]
    protected function findByKey(mixed $key)
    {
        if ($this->hasKey($key)) {
            return $this->values[$this->getObjectMapKey($key)]['value'];
        }

        return null;
    }

    protected function getObjectMapKey(mixed $object) : string
    {
        return $object instanceof Stringable ? (string)$object : spl_object_hash($object);
    }

    /**
     * @param TKey $key
     */
    protected function hasKey(mixed $key) : bool
    {
        return isset($this->values[$this->getObjectMapKey($key)]);
    }

    /**
     * @param TKey $key
     * @param TValue $value
     */
    protected function setKeyAndValue(mixed $key, mixed $value) : void
    {
        $hash = $this->getObjectMapKey($key);

        if (isset($this->values[$hash]) === false) {
            $this->objectHashes[] = $hash;
        }

        $this->values[$hash] = [
            'key' => $key,
            'value' => $value,
        ];
    }

    /**
     * @param TKey $key
     */
    protected function unsetKey(mixed $key) : void
    {
        if ($this->hasKey($key) === true) {
            unset($this->values[$this->getObjectMapKey($key)]);
            $this->objectHashes = array_keys($this->values);
        }
    }
    //endregion methods
}
