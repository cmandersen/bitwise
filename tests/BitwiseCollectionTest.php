<?php

namespace Cmandersen\Bitwise\Tests;

use Cmandersen\Bitwise\BitwiseCollection;
use Cmandersen\Bitwise\BitwiseFlag;
use Cmandersen\Bitwise\BitwiseServiceProvider;
use Orchestra\Testbench\TestCase;

class BitwiseCollectionTest extends TestCase
{
    private array $flags;

    private BitwiseCollection $collection;

    protected function getPackageProviders($app)
    {
        return [BitwiseServiceProvider::class];
    }

    protected function setUp(): void
    {
        $this->flags = [
            'read' => 1,
            'write' => 2,
            'delete' => 4,
            'admin' => 8,
        ];

        $this->collection = new BitwiseCollection(7, $this->flags); // read + write + delete
    }

    public function test_construction()
    {
        $collection = new BitwiseCollection(5, $this->flags); // read + delete
        $this->assertEquals(5, $collection->getValue());
    }

    public function test_has_method()
    {
        $this->assertTrue($this->collection->has('read'));
        $this->assertTrue($this->collection->has('write'));
        $this->assertTrue($this->collection->has('delete'));
        $this->assertFalse($this->collection->has('admin'));

        $this->assertTrue($this->collection->has('read', 'write'));
        $this->assertFalse($this->collection->has('read', 'admin'));
    }

    public function test_has_with_flag_instance()
    {
        $readFlag = new BitwiseFlag('read', 1);
        $adminFlag = new BitwiseFlag('admin', 8);

        $this->assertTrue($this->collection->has($readFlag));
        $this->assertFalse($this->collection->has($adminFlag));
    }

    public function test_has_any_method()
    {
        $this->assertTrue($this->collection->hasAny('read'));
        $this->assertTrue($this->collection->hasAny('admin', 'read'));
        $this->assertFalse($this->collection->hasAny('admin'));
    }

    public function test_add_method()
    {
        $newCollection = $this->collection->add('admin');
        $this->assertEquals(15, $newCollection->getValue()); // 7 + 8 = 15
        $this->assertTrue($newCollection->has('admin'));

        // Original collection unchanged
        $this->assertEquals(7, $this->collection->getValue());
        $this->assertFalse($this->collection->has('admin'));
    }

    public function test_remove_method()
    {
        $newCollection = $this->collection->remove('write');
        $this->assertEquals(5, $newCollection->getValue()); // 7 - 2 = 5
        $this->assertFalse($newCollection->has('write'));

        // Original collection unchanged
        $this->assertEquals(7, $this->collection->getValue());
        $this->assertTrue($this->collection->has('write'));
    }

    public function test_toggle_method()
    {
        $newCollection = $this->collection->toggle('admin'); // Add admin
        $this->assertEquals(15, $newCollection->getValue());
        $this->assertTrue($newCollection->has('admin'));

        $newCollection = $this->collection->toggle('write'); // Remove write
        $this->assertEquals(5, $newCollection->getValue());
        $this->assertFalse($newCollection->has('write'));
    }

    public function test_only_method()
    {
        $newCollection = $this->collection->only('read', 'admin');
        $this->assertEquals(1, $newCollection->getValue()); // Only read (1)
        $this->assertTrue($newCollection->has('read'));
        $this->assertFalse($newCollection->has('write'));
        $this->assertFalse($newCollection->has('admin'));
    }

    public function test_except_method()
    {
        $newCollection = $this->collection->except('write');
        $this->assertEquals(5, $newCollection->getValue());
        $this->assertTrue($newCollection->has('read'));
        $this->assertFalse($newCollection->has('write'));
        $this->assertTrue($newCollection->has('delete'));
    }

    public function test_clear_method()
    {
        $newCollection = $this->collection->clear();
        $this->assertEquals(0, $newCollection->getValue());
        $this->assertTrue($newCollection->isEmpty());
    }

    public function test_all_method()
    {
        $newCollection = $this->collection->all();
        $this->assertEquals(15, $newCollection->getValue()); // 1 + 2 + 4 + 8 = 15
        $this->assertTrue($newCollection->has('read', 'write', 'delete', 'admin'));
    }

    public function test_get_flags()
    {
        $flags = $this->collection->getFlags();
        $this->assertCount(3, $flags);
        $this->assertInstanceOf(BitwiseFlag::class, $flags[0]);
    }

    public function test_get_flag_names()
    {
        $names = $this->collection->getFlagNames();
        $expected = ['read', 'write', 'delete'];
        $this->assertEquals($expected, $names);
    }

    public function test_is_empty()
    {
        $emptyCollection = new BitwiseCollection(0, $this->flags);
        $this->assertTrue($emptyCollection->isEmpty());
        $this->assertFalse($emptyCollection->isNotEmpty());

        $this->assertFalse($this->collection->isEmpty());
        $this->assertTrue($this->collection->isNotEmpty());
    }

    public function test_to_array()
    {
        $expected = ['read', 'write', 'delete'];
        $this->assertEquals($expected, $this->collection->toArray());
    }

    public function test_to_string()
    {
        $expected = 'read, write, delete';
        $this->assertEquals($expected, (string) $this->collection);
    }

    public function test_array_access()
    {
        $this->assertTrue($this->collection['read']);
        $this->assertTrue($this->collection['write']);
        $this->assertFalse($this->collection['admin']);

        $this->assertTrue(isset($this->collection['read']));
        $this->assertFalse(isset($this->collection['admin']));
    }

    public function test_array_access_immutability()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->collection['test'] = true;
    }

    public function test_array_access_unset_immutability()
    {
        $this->expectException(\BadMethodCallException::class);
        unset($this->collection['read']);
    }

    public function test_countable()
    {
        $this->assertEquals(3, count($this->collection));

        $emptyCollection = new BitwiseCollection(0, $this->flags);
        $this->assertEquals(0, count($emptyCollection));
    }

    public function test_iterable()
    {
        $flags = [];
        foreach ($this->collection as $flag) {
            $flags[] = $flag->name;
        }

        $expected = ['read', 'write', 'delete'];
        $this->assertEquals($expected, $flags);
    }

    public function test_debug_info()
    {
        $debugInfo = $this->collection->__debugInfo();
        $expected = [
            'value' => 7,
            'binary' => '0b111',
            'flags' => ['read', 'write', 'delete'],
        ];
        $this->assertEquals($expected, $debugInfo);
    }
}
