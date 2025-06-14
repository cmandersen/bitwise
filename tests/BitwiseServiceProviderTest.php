<?php

namespace Cmandersen\Bitwise\Tests;

use Cmandersen\Bitwise\AsBitwise;
use Cmandersen\Bitwise\BitwiseServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class BitwiseServiceProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('permissions')->default(0);
            $table->unsignedInteger('features')->default(0);
            $table->unsignedInteger('status')->default(0);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('test_models');
        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [BitwiseServiceProvider::class];
    }

    public function test_service_provider_registers()
    {
        $this->assertTrue(class_exists(BitwiseServiceProvider::class));
    }

    public function test_service_provider_methods_exist()
    {
        $provider = new BitwiseServiceProvider($this->app);
        $this->assertTrue(method_exists($provider, 'boot'));
        $this->assertTrue(method_exists($provider, 'register'));
        $this->assertTrue(method_exists($provider, 'registerQueryBuilderMacros'));
    }

    public function test_service_provider_boot_method()
    {
        $provider = new BitwiseServiceProvider($this->app);

        // Call boot method to register macros
        $provider->boot();

        // Verify the service provider booted without errors
        $this->assertTrue(true);
    }

    public function test_cast_detection_with_class_parameters()
    {
        $model = new TestModel;
        $casts = $model->getCasts();

        // Verify the cast is properly defined
        $this->assertArrayHasKey('permissions', $casts);
        $this->assertStringContainsString('AsBitwise', $casts['permissions']);
        $this->assertStringContainsString('read,write,delete,admin', $casts['permissions']);
    }

    public function test_cast_detection_with_shorthand_syntax()
    {
        $model = new TestModelWithShorthand;
        $casts = $model->getCasts();

        // Verify the shorthand cast is properly defined
        $this->assertArrayHasKey('status', $casts);
        $this->assertEquals('bitwise:active,pending,archived', $casts['status']);
    }

    public function test_cast_instantiation_from_string()
    {
        // Test that AsBitwise can be instantiated with string parameters
        $cast = new AsBitwise('read,write,delete,admin');
        $this->assertInstanceOf(AsBitwise::class, $cast);

        // Test the cast works correctly
        $result = $cast->get(null, 'test', 3, []); // read (1) + write (2) = 3
        $this->assertEquals(['read', 'write'], $result->getFlagNames());
    }

    public function test_model_cast_definition_format()
    {
        $model = new TestModel;

        // Test the cast definition uses the correct format
        $casts = $model->getCasts();
        $this->assertArrayHasKey('permissions', $casts);

        // Verify it's using the class:parameters format
        $castDefinition = $casts['permissions'];
        $this->assertStringStartsWith('Cmandersen\Bitwise\AsBitwise:', $castDefinition);
        $this->assertStringContainsString('read,write,delete,admin', $castDefinition);
    }

    public function test_auto_cast_functionality()
    {
        $model = new TestModelWithAuto;
        $cast = AsBitwise::auto(['feature1', 'feature2', 'feature3']);

        $result = $cast->get(null, 'features', 5, []); // feature1 (1) + feature3 (4) = 5
        $this->assertEquals(['feature1', 'feature3'], $result->getFlagNames());
    }

    public function test_shorthand_cast_creation()
    {
        // Test the shorthand syntax creates proper flags
        $cast = new AsBitwise('active,pending,archived');

        $result = $cast->get(null, 'status', 1, []);
        $this->assertEquals(['active'], $result->getFlagNames());

        $result = $cast->get(null, 'status', 3, []); // active (1) + pending (2) = 3
        $this->assertEquals(['active', 'pending'], $result->getFlagNames());
    }
}

class TestModel extends Model
{
    protected $guarded = [];

    protected $casts = [
        'permissions' => AsBitwise::class . ':read,write,delete,admin',
        'features' => AsBitwise::class . ':feature1,feature2,feature3',
    ];
}

class TestModelWithAuto extends Model
{
    protected $table = 'test_models';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'features' => AsBitwise::auto(['feature1', 'feature2', 'feature3']),
        ];
    }
}

class TestModelWithShorthand extends Model
{
    protected $table = 'test_models';

    protected $guarded = [];

    protected $casts = [
        'status' => 'bitwise:active,pending,archived',
    ];
}
