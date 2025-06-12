<?php

namespace Cmandersen\Bitwise;

class Bitwise
{
    public static function generateFlags(array $flagNames): array
    {
        $flags = [];
        $currentBit = 1;
        
        foreach ($flagNames as $name) {
            $flags[$name] = $currentBit;
            $currentBit <<= 1;
        }
        
        return $flags;
    }
    
    public static function generateFromAssoc(array $flagsWithValues): array
    {
        $flags = [];
        $nextBit = 1;
        
        foreach ($flagsWithValues as $name => $value) {
            if ($value === null || $value === true) {
                $flags[$name] = $nextBit;
                $nextBit <<= 1;
            } else {
                $flags[$name] = (int) $value;
                $nextBit = max($nextBit, ((int) $value) << 1);
            }
        }
        
        return $flags;
    }
    
    public static function createCast(array $flagNames): AsBitwise
    {
        return new AsBitwise(self::generateFlags($flagNames));
    }
    
    public static function nextPowerOfTwo(int $value): int
    {
        if ($value <= 1) {
            return 1;
        }
        
        return 1 << (int) ceil(log($value, 2));
    }
    
    public static function isPowerOfTwo(int $value): bool
    {
        return $value > 0 && ($value & ($value - 1)) === 0;
    }
}