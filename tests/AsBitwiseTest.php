<?php

namespace Cmandersen\Bitwise\Tests;

use Cmandersen\Bitwise\AsBitwise;
use Cmandersen\Bitwise\BitwiseCollection;
use Cmandersen\Bitwise\BitwiseServiceProvider;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;

class AsBitwiseTest extends TestCase
{
    private array $testFlags;

    protected function getPackageProviders($app)
    {
        return [BitwiseServiceProvider::class];
    }

    protected function setUp(): void
    {
        $this->testFlags = [
            'read' => 1,
            'write' => 2,
            'delete' => 4,
            'admin' => 8,
        ];
    }

    public function test_constructor_with_array()
    {
        $cast = new AsBitwise($this->testFlags);
        $this->assertInstanceOf(AsBitwise::class, $cast);

        // Validate that bits are set correctly for read (1)
        $result = $cast->get(null, 'test', 1, []);
        $this->assertInstanceOf(BitwiseCollection::class, $result);
        $this->assertEquals(['read'], $result->getFlagNames());

        // Validate that bits are set correctly for write (2)
        $result = $cast->get(null, 'test', 2, []);
        $this->assertEquals(['write'], $result->getFlagNames());

        // Validate that bits are set correctly for combinations
        $result = $cast->get(null, 'test', 15, []);
        $this->assertEquals(['read', 'write', 'delete', 'admin'], $result->getFlagNames());
    }

    public function test_constructor_with_string_flags()
    {
        $cast = new AsBitwise('read=1,write=2,delete=4');
        $this->assertInstanceOf(AsBitwise::class, $cast);

        // Validate that string-parsed bits work correctly
        $result = $cast->get(null, 'test', 1, []);
        $this->assertEquals(['read'], $result->getFlagNames());

        $result = $cast->get(null, 'test', 4, []);
        $this->assertEquals(['delete'], $result->getFlagNames());

        $result = $cast->get(null, 'test', 7, []);
        $this->assertEquals(['read', 'write', 'delete'], $result->getFlagNames());
    }

    public function test_static_flags_method()
    {
        $cast = AsBitwise::flags($this->testFlags);
        $this->assertInstanceOf(AsBitwise::class, $cast);

        // Validate that flag bits are correctly preserved
        $result = $cast->get(null, 'test', 8, []);
        $this->assertEquals(['admin'], $result->getFlagNames());

        $result = $cast->get(null, 'test', 3, []);
        $this->assertEquals(['read', 'write'], $result->getFlagNames());
    }

    public function test_static_auto_method()
    {
        $cast = AsBitwise::auto(['read', 'write', 'delete']);
        $this->assertInstanceOf(AsBitwise::class, $cast);

        // Validate that auto-generated bits follow powers of 2 (1, 2, 4)
        $result = $cast->get(null, 'test', 1, []);
        $this->assertEquals(['read'], $result->getFlagNames());

        $result = $cast->get(null, 'test', 2, []);
        $this->assertEquals(['write'], $result->getFlagNames());

        $result = $cast->get(null, 'test', 4, []);
        $this->assertEquals(['delete'], $result->getFlagNames());

        $result = $cast->get(null, 'test', 5, []);
        $this->assertEquals(['read', 'delete'], $result->getFlagNames());
    }

    public function test_get_method_with_null_value()
    {
        $cast = new AsBitwise($this->testFlags);
        $result = $cast->get(null, 'test', null, []);
        $this->assertInstanceOf(BitwiseCollection::class, $result);
        $this->assertEquals([], $result->getFlagNames());
        $this->assertTrue($result->isEmpty());
    }

    public function test_get_method_with_single_flag()
    {
        $cast = new AsBitwise($this->testFlags);
        $result = $cast->get(null, 'test', 1, []);
        $this->assertInstanceOf(BitwiseCollection::class, $result);
        $this->assertEquals(['read'], $result->getFlagNames());
    }

    public function test_get_method_with_multiple_flags()
    {
        $cast = new AsBitwise($this->testFlags);
        $result = $cast->get(null, 'test', 7, []);
        $this->assertInstanceOf(BitwiseCollection::class, $result);
        $this->assertEquals(['read', 'write', 'delete'], $result->getFlagNames());
    }

    public function test_set_method_with_null_value()
    {
        $cast = new AsBitwise($this->testFlags);
        $result = $cast->set(null, 'test', null, []);
        $this->assertEquals(0, $result);
    }

    public function test_set_method_with_array_value()
    {
        $cast = new AsBitwise($this->testFlags);
        $result = $cast->set(null, 'test', ['read', 'write'], []);
        $this->assertEquals(3, $result);
    }

    public function test_set_method_with_string_value()
    {
        $cast = new AsBitwise($this->testFlags);
        $result = $cast->set(null, 'test', 'admin', []);
        $this->assertEquals(8, $result);
    }

    public function test_set_method_with_integer_value()
    {
        $cast = new AsBitwise($this->testFlags);
        $result = $cast->set(null, 'test', 15, []);
        $this->assertEquals(15, $result);
    }

    public function test_set_method_throws_exception_for_unknown_flag()
    {
        $cast = new AsBitwise($this->testFlags);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown flag: unknown');

        $cast->set(null, 'test', ['unknown'], []);
    }

    public function test_validation_throws_exception_for_invalid_flag_value()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Flag value must be a power of 2: 3');

        new AsBitwise(['invalid' => 3]);
    }

    public function test_validation_throws_exception_for_empty_flag_name()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Flag name must be a non-empty string.');

        new AsBitwise(['' => 1]);
    }

    public function test_string_flag_parsing_throws_exception_for_invalid_format()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid flag format: invalid=. Expected \'name=value\'.');

        // This should fail because the value part is empty after the =
        new AsBitwise('invalid=');
    }

    public function test_string_flag_parsing_throws_exception_for_invalid_value()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Flag value must be a power of 2: 3');

        new AsBitwise('read=3');
    }

    public function test_validation_throws_exception_for_non_string_flag_name()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Flag name must be a non-empty string.');

        new AsBitwise([123 => 1]);
    }

    public function test_validation_throws_exception_for_non_positive_integer_value()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Flag value must be a positive integer: -1');

        new AsBitwise(['read' => -1]);
    }

    public function test_validation_throws_exception_for_zero_value()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Flag value must be a positive integer: 0');

        new AsBitwise(['read' => 0]);
    }

    public function test_string_flag_parsing_with_spaces()
    {
        $cast = new AsBitwise('read=1, write=2 , delete = 4');
        $result = $cast->get(null, 'test', 7, []);
        $this->assertEquals(['read', 'write', 'delete'], $result->getFlagNames());
    }

    public function test_set_method_with_bitwise_collection()
    {
        $cast = new AsBitwise($this->testFlags);
        $collection = new BitwiseCollection(3, $this->testFlags);

        $result = $cast->set(null, 'test', $collection, []);
        $this->assertEquals(3, $result);
    }
}
