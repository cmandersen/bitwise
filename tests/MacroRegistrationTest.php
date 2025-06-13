<?php

namespace Cmandersen\Bitwise\Tests;

use Cmandersen\Bitwise\BitwiseServiceProvider;
use PHPUnit\Framework\TestCase;

class MacroRegistrationTest extends TestCase
{
    protected function setUp(): void
    {
        // Register the service provider to test macro registration
        $serviceProvider = new BitwiseServiceProvider(null);
        $serviceProvider->boot();
    }

    public function test_macros_can_be_registered()
    {
        // Test that macros can be registered without errors
        // The fact that boot() completes without throwing exceptions means
        // the Builder classes are available and macros can be registered

        $this->expectNotToPerformAssertions();

        // This would throw an exception if Builder classes weren't available
        $serviceProvider = new BitwiseServiceProvider(null);
        $serviceProvider->boot();
    }

    public function test_service_provider_can_instantiate()
    {
        // Test that the service provider can be instantiated without errors
        $serviceProvider = new BitwiseServiceProvider(null);
        $this->assertInstanceOf(BitwiseServiceProvider::class, $serviceProvider);
    }

    public function test_boot_method_executes_without_errors()
    {
        // Test that the boot method can execute without throwing exceptions
        $serviceProvider = new BitwiseServiceProvider(null);

        $this->expectNotToPerformAssertions();
        $serviceProvider->boot();
    }
}
