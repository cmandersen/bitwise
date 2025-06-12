<?php

namespace Cmandersen\Bitwise\Tests;

use Cmandersen\Bitwise\AsBitwise;
use Cmandersen\Bitwise\BitwiseCollection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BitwiseCastTest extends TestCase
{
    private array $testFlags;
    
    protected function setUp(): void
    {
        $this->testFlags = [
            'read' => 1,
            'write' => 2,
            'delete' => 4,
            'admin' => 8,
        ];
    }
    
    public function testConstructorWithArray()
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
    
    public function testConstructorWithStringFlags()
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
    
    public function testStaticFlagsMethod()
    {
        $cast = AsBitwise::flags($this->testFlags);
        $this->assertInstanceOf(AsBitwise::class, $cast);
        
        // Validate that flag bits are correctly preserved
        $result = $cast->get(null, 'test', 8, []);
        $this->assertEquals(['admin'], $result->getFlagNames());
        
        $result = $cast->get(null, 'test', 3, []);
        $this->assertEquals(['read', 'write'], $result->getFlagNames());
    }
    
    public function testStaticAutoMethod()
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
    
    public function testGetMethodWithNullValue()
    {
        $cast = new AsBitwise($this->testFlags);
        $result = $cast->get(null, 'test', null, []);
        $this->assertInstanceOf(BitwiseCollection::class, $result);
        $this->assertEquals([], $result->getFlagNames());
        $this->assertTrue($result->isEmpty());
    }
    
    public function testGetMethodWithSingleFlag()
    {
        $cast = new AsBitwise($this->testFlags);
        $result = $cast->get(null, 'test', 1, []);
        $this->assertInstanceOf(BitwiseCollection::class, $result);
        $this->assertEquals(['read'], $result->getFlagNames());
    }
    
    public function testGetMethodWithMultipleFlags()
    {
        $cast = new AsBitwise($this->testFlags);
        $result = $cast->get(null, 'test', 7, []);
        $this->assertInstanceOf(BitwiseCollection::class, $result);
        $this->assertEquals(['read', 'write', 'delete'], $result->getFlagNames());
    }
    
    public function testSetMethodWithNullValue()
    {
        $cast = new AsBitwise($this->testFlags);
        $result = $cast->set(null, 'test', null, []);
        $this->assertEquals(0, $result);
    }
    
    public function testSetMethodWithArrayValue()
    {
        $cast = new AsBitwise($this->testFlags);
        $result = $cast->set(null, 'test', ['read', 'write'], []);
        $this->assertEquals(3, $result);
    }
    
    public function testSetMethodWithStringValue()
    {
        $cast = new AsBitwise($this->testFlags);
        $result = $cast->set(null, 'test', 'admin', []);
        $this->assertEquals(8, $result);
    }
    
    public function testSetMethodWithIntegerValue()
    {
        $cast = new AsBitwise($this->testFlags);
        $result = $cast->set(null, 'test', 15, []);
        $this->assertEquals(15, $result);
    }
    
    public function testSetMethodThrowsExceptionForUnknownFlag()
    {
        $cast = new AsBitwise($this->testFlags);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown flag: unknown');
        
        $cast->set(null, 'test', ['unknown'], []);
    }
    
    public function testValidationThrowsExceptionForInvalidFlagValue()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Flag value must be a power of 2: 3');
        
        new AsBitwise(['invalid' => 3]);
    }
    
    public function testValidationThrowsExceptionForEmptyFlagName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Flag name must be a non-empty string.');
        
        new AsBitwise(['' => 1]);
    }
    
    public function testStringFlagParsingThrowsExceptionForInvalidFormat()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid flag format: invalid. Expected \'name=value\'.');
        
        new AsBitwise('invalid');
    }
    
    public function testStringFlagParsingThrowsExceptionForInvalidValue()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Flag value must be a power of 2: 3');
        
        new AsBitwise('read=3');
    }
}