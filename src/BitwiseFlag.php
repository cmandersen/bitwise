<?php

namespace Cmandersen\Bitwise;

class BitwiseFlag
{
    public function __construct(
        public readonly string $name,
        public readonly int $value
    ) {}

    public function is(string|BitwiseFlag $flag): bool
    {
        if ($flag instanceof BitwiseFlag) {
            return $this->name === $flag->name && $this->value === $flag->value;
        }

        return $this->name === $flag;
    }

    public function hasValue(int $value): bool
    {
        return $this->value === $value;
    }

    public function isPowerOfTwo(): bool
    {
        return $this->value > 0 && ($this->value & ($this->value - 1)) === 0;
    }

    public function combine(BitwiseFlag ...$flags): int
    {
        $result = $this->value;
        foreach ($flags as $flag) {
            $result |= $flag->value;
        }

        return $result;
    }

    public function isSetIn(int $value): bool
    {
        return ($value & $this->value) === $this->value;
    }

    public function setBit(int $value): int
    {
        return $value | $this->value;
    }

    public function unsetBit(int $value): int
    {
        return $value & ~$this->value;
    }

    public function toggleBit(int $value): int
    {
        return $value ^ $this->value;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
        ];
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function __debugInfo(): array
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
            'binary' => '0b' . decbin($this->value),
        ];
    }
}
