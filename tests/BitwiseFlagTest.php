<?php

namespace Cmandersen\Bitwise\Tests;

use Cmandersen\Bitwise\BitwiseFlag;
use PHPUnit\Framework\TestCase;

class BitwiseFlagTest extends TestCase
{
    private BitwiseFlag $readFlag;

    private BitwiseFlag $writeFlag;

    private BitwiseFlag $deleteFlag;

    protected function setUp(): void
    {
        $this->readFlag = new BitwiseFlag('read', 1);
        $this->writeFlag = new BitwiseFlag('write', 2);
        $this->deleteFlag = new BitwiseFlag('delete', 4);
    }

    public function test_construction()
    {
        $flag = new BitwiseFlag('test', 8);
        $this->assertEquals('test', $flag->name);
        $this->assertEquals(8, $flag->value);
    }

    public function test_is_method()
    {
        $this->assertTrue($this->readFlag->is('read'));
        $this->assertFalse($this->readFlag->is('write'));

        $anotherReadFlag = new BitwiseFlag('read', 1);
        $this->assertTrue($this->readFlag->is($anotherReadFlag));

        $differentFlag = new BitwiseFlag('read', 2);
        $this->assertFalse($this->readFlag->is($differentFlag));
    }

    public function test_has_value()
    {
        $this->assertTrue($this->readFlag->hasValue(1));
        $this->assertFalse($this->readFlag->hasValue(2));
    }

    public function test_is_power_of_two()
    {
        $this->assertTrue($this->readFlag->isPowerOfTwo());
        $this->assertTrue($this->writeFlag->isPowerOfTwo());
        $this->assertTrue($this->deleteFlag->isPowerOfTwo());

        $invalidFlag = new BitwiseFlag('invalid', 3);
        $this->assertFalse($invalidFlag->isPowerOfTwo());
    }

    public function test_combine()
    {
        $result = $this->readFlag->combine($this->writeFlag, $this->deleteFlag);
        $this->assertEquals(7, $result); // 1 | 2 | 4 = 7

        $result = $this->readFlag->combine($this->writeFlag);
        $this->assertEquals(3, $result); // 1 | 2 = 3
    }

    public function test_is_set_in()
    {
        $this->assertTrue($this->readFlag->isSetIn(1)); // 0001 & 0001 = 0001
        $this->assertTrue($this->readFlag->isSetIn(3)); // 0011 & 0001 = 0001
        $this->assertTrue($this->readFlag->isSetIn(7)); // 0111 & 0001 = 0001
        $this->assertFalse($this->readFlag->isSetIn(2)); // 0010 & 0001 = 0000
        $this->assertFalse($this->readFlag->isSetIn(4)); // 0100 & 0001 = 0000

        $this->assertTrue($this->writeFlag->isSetIn(2)); // 0010 & 0010 = 0010
        $this->assertTrue($this->writeFlag->isSetIn(3)); // 0011 & 0010 = 0010
        $this->assertFalse($this->writeFlag->isSetIn(1)); // 0001 & 0010 = 0000
    }

    public function test_set_bit()
    {
        $this->assertEquals(1, $this->readFlag->setBit(0)); // 0000 | 0001 = 0001
        $this->assertEquals(3, $this->readFlag->setBit(2)); // 0010 | 0001 = 0011
        $this->assertEquals(7, $this->readFlag->setBit(6)); // 0110 | 0001 = 0111
    }

    public function test_unset_bit()
    {
        $this->assertEquals(0, $this->readFlag->unsetBit(1)); // 0001 & ~0001 = 0000
        $this->assertEquals(2, $this->readFlag->unsetBit(3)); // 0011 & ~0001 = 0010
        $this->assertEquals(6, $this->readFlag->unsetBit(7)); // 0111 & ~0001 = 0110
    }

    public function test_toggle_bit()
    {
        $this->assertEquals(1, $this->readFlag->toggleBit(0)); // 0000 ^ 0001 = 0001
        $this->assertEquals(0, $this->readFlag->toggleBit(1)); // 0001 ^ 0001 = 0000
        $this->assertEquals(2, $this->readFlag->toggleBit(3)); // 0011 ^ 0001 = 0010
    }

    public function test_to_array()
    {
        $expected = ['name' => 'read', 'value' => 1];
        $this->assertEquals($expected, $this->readFlag->toArray());
    }

    public function test_to_string()
    {
        $this->assertEquals('read', (string) $this->readFlag);
    }

    public function test_debug_info()
    {
        $debugInfo = $this->readFlag->__debugInfo();
        $expected = [
            'name' => 'read',
            'value' => 1,
            'binary' => '0b1',
        ];
        $this->assertEquals($expected, $debugInfo);

        $debugInfo = $this->deleteFlag->__debugInfo();
        $expected = [
            'name' => 'delete',
            'value' => 4,
            'binary' => '0b100',
        ];
        $this->assertEquals($expected, $debugInfo);
    }
}
