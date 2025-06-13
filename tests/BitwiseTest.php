<?php

namespace Cmandersen\Bitwise\Tests;

use Cmandersen\Bitwise\AsBitwise;
use Cmandersen\Bitwise\Bitwise;
use PHPUnit\Framework\TestCase;

class BitwiseTest extends TestCase
{
    public function test_generate_flags()
    {
        $flags = Bitwise::generateFlags(['read', 'write', 'delete', 'admin']);

        $expected = [
            'read' => 1,
            'write' => 2,
            'delete' => 4,
            'admin' => 8,
        ];

        $this->assertEquals($expected, $flags);
    }

    public function test_generate_flags_with_empty_array()
    {
        $flags = Bitwise::generateFlags([]);
        $this->assertEquals([], $flags);
    }

    public function test_generate_flags_with_single_flag()
    {
        $flags = Bitwise::generateFlags(['single']);
        $this->assertEquals(['single' => 1], $flags);
    }

    public function test_generate_from_assoc_with_null_values()
    {
        $flags = Bitwise::generateFromAssoc([
            'read' => null,
            'write' => null,
            'delete' => null,
        ]);

        $expected = [
            'read' => 1,
            'write' => 2,
            'delete' => 4,
        ];

        $this->assertEquals($expected, $flags);
    }

    public function test_generate_from_assoc_with_true_values()
    {
        $flags = Bitwise::generateFromAssoc([
            'read' => true,
            'write' => true,
            'delete' => true,
        ]);

        $expected = [
            'read' => 1,
            'write' => 2,
            'delete' => 4,
        ];

        $this->assertEquals($expected, $flags);
    }

    public function test_generate_from_assoc_with_mixed_values()
    {
        $flags = Bitwise::generateFromAssoc([
            'read' => null,
            'write' => 16,
            'delete' => null,
            'admin' => 64,
        ]);

        $expected = [
            'read' => 1,
            'write' => 16,
            'delete' => 32,
            'admin' => 64,
        ];

        $this->assertEquals($expected, $flags);
    }

    public function test_create_cast()
    {
        $cast = Bitwise::createCast(['read', 'write', 'delete']);
        $this->assertInstanceOf(AsBitwise::class, $cast);
    }

    public function test_next_power_of_two()
    {
        $this->assertEquals(1, Bitwise::nextPowerOfTwo(1));
        $this->assertEquals(2, Bitwise::nextPowerOfTwo(2));
        $this->assertEquals(4, Bitwise::nextPowerOfTwo(3));
        $this->assertEquals(8, Bitwise::nextPowerOfTwo(5));
        $this->assertEquals(8, Bitwise::nextPowerOfTwo(8));
        $this->assertEquals(16, Bitwise::nextPowerOfTwo(9));
        $this->assertEquals(1, Bitwise::nextPowerOfTwo(0));
        $this->assertEquals(1, Bitwise::nextPowerOfTwo(-1));
    }

    public function test_is_power_of_two()
    {
        $this->assertTrue(Bitwise::isPowerOfTwo(1));
        $this->assertTrue(Bitwise::isPowerOfTwo(2));
        $this->assertTrue(Bitwise::isPowerOfTwo(4));
        $this->assertTrue(Bitwise::isPowerOfTwo(8));
        $this->assertTrue(Bitwise::isPowerOfTwo(16));
        $this->assertTrue(Bitwise::isPowerOfTwo(32));
        $this->assertTrue(Bitwise::isPowerOfTwo(64));

        $this->assertFalse(Bitwise::isPowerOfTwo(0));
        $this->assertFalse(Bitwise::isPowerOfTwo(3));
        $this->assertFalse(Bitwise::isPowerOfTwo(5));
        $this->assertFalse(Bitwise::isPowerOfTwo(6));
        $this->assertFalse(Bitwise::isPowerOfTwo(7));
        $this->assertFalse(Bitwise::isPowerOfTwo(9));
        $this->assertFalse(Bitwise::isPowerOfTwo(-1));
    }
}
