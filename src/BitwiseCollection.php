<?php

namespace Cmandersen\Bitwise;

use ArrayAccess;
use ArrayIterator;
use BadMethodCallException;
use Countable;
use IteratorAggregate;

class BitwiseCollection implements ArrayAccess, Countable, IteratorAggregate
{
    protected array $activeFlags;

    protected array $allFlags;

    public function __construct(int $value, array $allFlags)
    {
        $this->allFlags = $allFlags;
        $this->activeFlags = [];

        foreach ($allFlags as $flagName => $flagValue) {
            if (($value & $flagValue) === $flagValue) {
                $this->activeFlags[$flagName] = $flagValue;
            }
        }
    }

    public function has(string|BitwiseFlag ...$flags): bool
    {
        foreach ($flags as $flag) {
            $flagName = $flag instanceof BitwiseFlag ? $flag->name : $flag;
            if (! isset($this->activeFlags[$flagName])) {
                return false;
            }
        }

        return true;
    }

    public function hasAny(string|BitwiseFlag ...$flags): bool
    {
        foreach ($flags as $flag) {
            $flagName = $flag instanceof BitwiseFlag ? $flag->name : $flag;
            if (isset($this->activeFlags[$flagName])) {
                return true;
            }
        }

        return false;
    }

    public function add(string|BitwiseFlag ...$flags): self
    {
        $newActiveFlags = $this->activeFlags;
        foreach ($flags as $flag) {
            $flagName = $flag instanceof BitwiseFlag ? $flag->name : $flag;
            if (isset($this->allFlags[$flagName])) {
                $newActiveFlags[$flagName] = $this->allFlags[$flagName];
            }
        }

        return $this->createFromActiveFlags($newActiveFlags);
    }

    public function remove(string|BitwiseFlag ...$flags): self
    {
        $newActiveFlags = $this->activeFlags;
        foreach ($flags as $flag) {
            $flagName = $flag instanceof BitwiseFlag ? $flag->name : $flag;
            unset($newActiveFlags[$flagName]);
        }

        return $this->createFromActiveFlags($newActiveFlags);
    }

    public function toggle(string|BitwiseFlag ...$flags): self
    {
        $newActiveFlags = $this->activeFlags;
        foreach ($flags as $flag) {
            $flagName = $flag instanceof BitwiseFlag ? $flag->name : $flag;
            if (isset($newActiveFlags[$flagName])) {
                unset($newActiveFlags[$flagName]);
            } elseif (isset($this->allFlags[$flagName])) {
                $newActiveFlags[$flagName] = $this->allFlags[$flagName];
            }
        }

        return $this->createFromActiveFlags($newActiveFlags);
    }

    public function only(string|BitwiseFlag ...$flags): self
    {
        $newActiveFlags = [];
        foreach ($flags as $flag) {
            $flagName = $flag instanceof BitwiseFlag ? $flag->name : $flag;
            if (isset($this->activeFlags[$flagName])) {
                $newActiveFlags[$flagName] = $this->activeFlags[$flagName];
            }
        }

        return $this->createFromActiveFlags($newActiveFlags);
    }

    protected function createFromActiveFlags(array $activeFlags): self
    {
        $value = array_sum($activeFlags);

        return new self($value, $this->allFlags);
    }

    public function except(string|BitwiseFlag ...$flags): self
    {
        return $this->remove(...$flags);
    }

    public function clear(): self
    {
        return $this->createFromActiveFlags([]);
    }

    public function all(): self
    {
        return $this->createFromActiveFlags($this->allFlags);
    }

    public function getValue(): int
    {
        return array_sum($this->activeFlags);
    }

    public function getFlags(): array
    {
        $result = [];
        foreach ($this->activeFlags as $flagName => $flagValue) {
            $result[] = new BitwiseFlag($flagName, $flagValue);
        }

        return $result;
    }

    public function getFlagNames(): array
    {
        return array_keys($this->activeFlags);
    }

    public function isEmpty(): bool
    {
        return empty($this->activeFlags);
    }

    public function isNotEmpty(): bool
    {
        return ! empty($this->activeFlags);
    }

    public function toArray(): array
    {
        return $this->getFlagNames();
    }

    public function __toString(): string
    {
        return implode(', ', $this->getFlagNames());
    }

    public function __debugInfo(): array
    {
        $value = $this->getValue();

        return [
            'value' => $value,
            'binary' => '0b' . decbin($value),
            'flags' => $this->getFlagNames(),
        ];
    }

    // ArrayAccess implementation
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet($offset): bool
    {
        return $this->has($offset);
    }

    public function offsetSet($offset, $value): void
    {
        throw new BadMethodCallException('BitwiseCollection is immutable. Use add() or remove() methods instead.');
    }

    public function offsetUnset($offset): void
    {
        throw new BadMethodCallException('BitwiseCollection is immutable. Use remove() method instead.');
    }

    // Countable implementation
    public function count(): int
    {
        return count($this->getFlags());
    }

    // IteratorAggregate implementation
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->getFlags());
    }
}
