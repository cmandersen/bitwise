<?php

namespace Cmandersen\Bitwise;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;

class AsBitwise implements CastsAttributes
{
    protected array $flags;

    public function __construct(array|string $flags = [])
    {
        if (is_string($flags)) {
            $this->flags = $this->parseStringFlags($flags);
        } else {
            $this->flags = $flags;
        }

        $this->validateFlags();
    }

    public static function flags(array $flags): self
    {
        return new self($flags);
    }

    public static function auto(array $flagNames): self
    {
        return new self(Bitwise::generateFlags($flagNames));
    }

    protected function parseStringFlags(string $flagString): array
    {
        $flags = [];
        $pairs = explode(',', $flagString);

        foreach ($pairs as $pair) {
            $parts = explode('=', trim($pair));
            if (count($parts) !== 2) {
                throw new InvalidArgumentException("Invalid flag format: {$pair}. Expected 'name=value'.");
            }

            $name = trim($parts[0]);
            $value = (int) trim($parts[1]);

            if ($value <= 0 || ($value & ($value - 1)) !== 0) {
                throw new InvalidArgumentException("Flag value must be a power of 2: {$value}");
            }

            $flags[$name] = $value;
        }

        return $flags;
    }

    protected function validateFlags(): void
    {
        foreach ($this->flags as $name => $value) {
            if (! is_string($name) || empty($name)) {
                throw new InvalidArgumentException('Flag name must be a non-empty string.');
            }

            if (! is_int($value) || $value <= 0) {
                throw new InvalidArgumentException("Flag value must be a positive integer: {$value}");
            }

            if (($value & ($value - 1)) !== 0) {
                throw new InvalidArgumentException("Flag value must be a power of 2: {$value}");
            }
        }
    }

    public function get($model, string $key, $value, array $attributes): BitwiseCollection
    {
        $intValue = is_null($value) ? 0 : (int) $value;

        return new BitwiseCollection($intValue, $this->flags);
    }

    public function set($model, string $key, $value, array $attributes): int
    {
        if (is_null($value)) {
            return 0;
        }

        if (is_array($value)) {
            $result = 0;
            foreach ($value as $flag) {
                if ($flag instanceof BitwiseFlag) {
                    $result |= $flag->value;
                } elseif (is_string($flag)) {
                    if (! isset($this->flags[$flag])) {
                        throw new InvalidArgumentException("Unknown flag: {$flag}");
                    }
                    $result |= $this->flags[$flag];
                } else {
                    throw new InvalidArgumentException('Invalid flag type. Expected string or BitwiseFlag instance.');
                }
            }

            return $result;
        }

        if ($value instanceof BitwiseCollection) {
            return $value->getValue();
        }

        if ($value instanceof BitwiseFlag) {
            return $value->value;
        }

        if (is_string($value)) {
            if (! isset($this->flags[$value])) {
                throw new InvalidArgumentException("Unknown flag: {$value}");
            }

            return $this->flags[$value];
        }

        return (int) $value;
    }
}
