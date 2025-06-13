<?php

namespace Cmandersen\Bitwise\Tests;

use Cmandersen\Bitwise\AsBitwise;
use PHPUnit\Framework\TestCase;

class HasBitwiseQueriesTest extends TestCase
{
    public function test_shorthand_cast_syntax()
    {
        // Test the shorthand cast syntax support
        $cast = $this->parseShorthandCast('bitwise:read,write,delete,admin');
        $this->assertInstanceOf(AsBitwise::class, $cast);

        // Test that it generates the correct flag values
        $this->assertEquals(1, $cast->set(null, 'permissions', 'read', []));
        $this->assertEquals(2, $cast->set(null, 'permissions', 'write', []));
        $this->assertEquals(4, $cast->set(null, 'permissions', 'delete', []));
        $this->assertEquals(8, $cast->set(null, 'permissions', 'admin', []));
    }

    public function test_shorthand_with_spaces()
    {
        // Test handling of spaces in shorthand syntax
        $cast = $this->parseShorthandCast('bitwise: read , write , delete ');
        $this->assertInstanceOf(AsBitwise::class, $cast);

        $this->assertEquals(1, $cast->set(null, 'permissions', 'read', []));
        $this->assertEquals(2, $cast->set(null, 'permissions', 'write', []));
        $this->assertEquals(4, $cast->set(null, 'permissions', 'delete', []));
    }

    private function parseShorthandCast(string $castType): ?AsBitwise
    {
        // Simulate the logic from the service provider
        if (str_starts_with($castType, 'bitwise:')) {
            $flags = explode(',', substr($castType, 8));

            return AsBitwise::auto(array_map('trim', $flags));
        }

        return null;
    }
}
