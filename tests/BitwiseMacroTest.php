<?php

namespace Cmandersen\Bitwise\Tests;

use Cmandersen\Bitwise\AsBitwise;
use PHPUnit\Framework\TestCase;

class BitwiseMacroTest extends TestCase
{
    public function test_auto_generated_flag_values()
    {
        $cast = AsBitwise::auto(['read', 'write', 'delete', 'admin']);

        // Test auto-generated flag values (powers of 2)
        $this->assertEquals(1, $cast->set(null, 'permissions', 'read', []));
        $this->assertEquals(2, $cast->set(null, 'permissions', 'write', []));
        $this->assertEquals(4, $cast->set(null, 'permissions', 'delete', []));
        $this->assertEquals(8, $cast->set(null, 'permissions', 'admin', []));

        // Test combined flags
        $readWriteValue = $cast->set(null, 'permissions', ['read', 'write'], []);
        $this->assertEquals(3, $readWriteValue); // 1 | 2 = 3
    }

    public function test_custom_flag_values()
    {
        $cast = AsBitwise::flags(['notifications' => 1, 'dark_mode' => 2, 'beta' => 4]);

        // Test custom flag values
        $this->assertEquals(1, $cast->set(null, 'features', 'notifications', []));
        $this->assertEquals(2, $cast->set(null, 'features', 'dark_mode', []));
        $this->assertEquals(4, $cast->set(null, 'features', 'beta', []));
    }

    public function test_bitwise_logic_validation()
    {
        // Test the core bitwise operations that the macros will generate

        // Test "has" operation (all specified flags must be present)
        $storedValue = 7; // Binary: 111 (read + write + delete)
        $readFlag = 1;    // Binary: 001
        $writeFlag = 2;   // Binary: 010
        $deleteFlag = 4;  // Binary: 100
        $adminFlag = 8;   // Binary: 1000

        // Test individual flag checks
        $this->assertEquals($readFlag, $storedValue & $readFlag, 'Should have read flag');
        $this->assertEquals($writeFlag, $storedValue & $writeFlag, 'Should have write flag');
        $this->assertEquals($deleteFlag, $storedValue & $deleteFlag, 'Should have delete flag');
        $this->assertEquals(0, $storedValue & $adminFlag, 'Should not have admin flag');

        // Test combined flag checks (has ALL flags)
        $readWriteCombo = $readFlag | $writeFlag; // 3
        $this->assertEquals($readWriteCombo, $storedValue & $readWriteCombo, 'Should have both read and write');

        // Test "hasAny" operation (at least one flag must be present)
        $adminDeleteCombo = $adminFlag | $deleteFlag; // 12
        $this->assertGreaterThan(0, $storedValue & $adminDeleteCombo, 'Should have at least one of admin or delete');

        // Test "doesn't have" operation
        $this->assertEquals(0, $storedValue & $adminFlag, 'Should not have admin flag');
    }

    public function test_shorthand_syntax_parsing()
    {
        // Test the shorthand syntax parsing logic that will be used in macros
        $flags = explode(',', substr('bitwise:read,write,delete,admin', 8));
        $cast = AsBitwise::auto(array_map('trim', $flags));

        $this->assertInstanceOf(AsBitwise::class, $cast);

        // Test that it generates the correct flag values
        $this->assertEquals(1, $cast->set(null, 'permissions', 'read', []));
        $this->assertEquals(2, $cast->set(null, 'permissions', 'write', []));
        $this->assertEquals(4, $cast->set(null, 'permissions', 'delete', []));
        $this->assertEquals(8, $cast->set(null, 'permissions', 'admin', []));
    }
}
