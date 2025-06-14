<?php

namespace Cmandersen\Bitwise\Tests;

use Cmandersen\Bitwise\AsBitwise;
use Cmandersen\Bitwise\BitwiseServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class BitwiseQueryMacrosTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app)
    {
        return [BitwiseServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('permissions')->default(0);
            $table->unsignedInteger('features')->default(0);
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedInteger('status')->default(0);
            $table->timestamps();
        });
    }

    public function test_where_bitwise_macro_works()
    {
        // Create test data
        User::create(['name' => 'John', 'permissions' => 1]); // read only
        User::create(['name' => 'Jane', 'permissions' => 3]); // read + write
        User::create(['name' => 'Bob', 'permissions' => 7]);  // read + write + delete
        User::create(['name' => 'Alice', 'permissions' => 8]); // admin only

        // Test whereBitwise
        $users = User::whereBitwise('permissions', 'read')->get();
        $this->assertCount(3, $users);
        $this->assertEquals(['Bob', 'Jane', 'John'], $users->pluck('name')->sort()->values()->toArray());

        $users = User::whereBitwise('permissions', 'write')->get();
        $this->assertCount(2, $users);
        $this->assertEquals(['Bob', 'Jane'], $users->pluck('name')->sort()->values()->toArray());

        $users = User::whereBitwise('permissions', 'admin')->get();
        $this->assertCount(1, $users);
        $this->assertEquals('Alice', $users->first()->name);
    }

    public function test_where_has_bitwise_macro_works()
    {
        User::create(['name' => 'John', 'permissions' => 1]); // read only
        User::create(['name' => 'Jane', 'permissions' => 3]); // read + write

        // Test whereHasBitwise (alias for whereBitwise)
        $users = User::whereHasBitwise('permissions', 'read')->get();
        $this->assertCount(2, $users);

        $users = User::whereHasBitwise('permissions', 'write')->get();
        $this->assertCount(1, $users);
        $this->assertEquals('Jane', $users->first()->name);
    }

    public function test_where_has_any_bitwise_macro_works()
    {
        User::create(['name' => 'John', 'permissions' => 1]);  // read only
        User::create(['name' => 'Jane', 'permissions' => 2]);  // write only
        User::create(['name' => 'Bob', 'permissions' => 4]);   // delete only
        User::create(['name' => 'Alice', 'permissions' => 8]); // admin only

        // Test whereHasAnyBitwise - should find users with ANY of the specified flags
        $users = User::whereHasAnyBitwise('permissions', ['read', 'write'])->get();
        $this->assertCount(2, $users);
        $this->assertEquals(['Jane', 'John'], $users->pluck('name')->sort()->values()->toArray());

        $users = User::whereHasAnyBitwise('permissions', ['admin', 'delete'])->get();
        $this->assertCount(2, $users);
        $this->assertEquals(['Alice', 'Bob'], $users->pluck('name')->sort()->values()->toArray());
    }

    public function test_where_doesnt_have_bitwise_macro_works()
    {
        User::create(['name' => 'John', 'permissions' => 1]);  // read only
        User::create(['name' => 'Jane', 'permissions' => 3]);  // read + write
        User::create(['name' => 'Bob', 'permissions' => 8]);   // admin only

        // Test whereDoesntHaveBitwise - should find users WITHOUT the specified flag
        $users = User::whereDoesntHaveBitwise('permissions', 'admin')->get();
        $this->assertCount(2, $users);
        $this->assertEquals(['Jane', 'John'], $users->pluck('name')->sort()->values()->toArray());

        $users = User::whereDoesntHaveBitwise('permissions', 'write')->get();
        $this->assertCount(2, $users);
        $this->assertEquals(['Bob', 'John'], $users->pluck('name')->sort()->values()->toArray());
    }

    public function test_where_bitwise_equals_macro_works()
    {
        User::create(['name' => 'John', 'permissions' => 1]);  // read only
        User::create(['name' => 'Jane', 'permissions' => 3]);  // read + write
        User::create(['name' => 'Bob', 'permissions' => 7]);   // read + write + delete

        // Test whereBitwiseEquals - should find users with EXACTLY the specified flags
        $users = User::whereBitwiseEquals('permissions', ['read'])->get();
        $this->assertCount(1, $users);
        $this->assertEquals('John', $users->first()->name);

        $users = User::whereBitwiseEquals('permissions', ['read', 'write'])->get();
        $this->assertCount(1, $users);
        $this->assertEquals('Jane', $users->first()->name);

        $users = User::whereBitwiseEquals('permissions', ['read', 'write', 'delete'])->get();
        $this->assertCount(1, $users);
        $this->assertEquals('Bob', $users->first()->name);
    }

    public function test_or_where_bitwise_macros_work()
    {
        User::create(['name' => 'John', 'permissions' => 1]);  // read only
        User::create(['name' => 'Jane', 'permissions' => 2]);  // write only
        User::create(['name' => 'Bob', 'permissions' => 8]);   // admin only

        // Test orWhereBitwise
        $users = User::where('name', 'NonExistent')
            ->orWhereBitwise('permissions', 'admin')
            ->get();
        $this->assertCount(1, $users);
        $this->assertEquals('Bob', $users->first()->name);

        // Test orWhereHasBitwise
        $users = User::where('name', 'John')
            ->orWhereHasBitwise('permissions', 'admin')
            ->get();
        $this->assertCount(2, $users);
        $this->assertEquals(['Bob', 'John'], $users->pluck('name')->sort()->values()->toArray());
    }

    public function test_where_in_bitwise_macro_works()
    {
        User::create(['name' => 'John', 'permissions' => 1]);  // read only
        User::create(['name' => 'Jane', 'permissions' => 2]);  // write only
        User::create(['name' => 'Bob', 'permissions' => 3]);   // read + write
        User::create(['name' => 'Alice', 'permissions' => 8]); // admin only

        // Test whereInBitwise - should find users with permissions matching ANY of the specified combinations
        $users = User::whereInBitwise('permissions', [
            ['read'],           // permissions = 1
            ['write'],          // permissions = 2
            ['read', 'write'],   // permissions = 3
        ])->get();

        $this->assertCount(3, $users);
        $this->assertEquals(['Bob', 'Jane', 'John'], $users->pluck('name')->sort()->values()->toArray());
    }

    public function test_where_not_in_bitwise_macro_works()
    {
        // Clear any existing data
        User::truncate();

        User::create(['name' => 'John', 'permissions' => 1]);  // read only
        User::create(['name' => 'Jane', 'permissions' => 2]);  // write only
        User::create(['name' => 'Bob', 'permissions' => 3]);   // read + write
        User::create(['name' => 'Alice', 'permissions' => 8]); // admin only

        // Test whereNotInBitwise - should find users with permissions NOT matching ANY of the specified combinations
        $users = User::whereNotInBitwise('permissions', [
            ['read'],    // permissions != 1
            ['admin'],    // permissions != 8
        ])->get();

        $this->assertCount(2, $users);
        $this->assertEquals(['Bob', 'Jane'], $users->pluck('name')->sort()->values()->toArray());
    }

    public function test_chained_bitwise_queries_work()
    {
        User::create(['name' => 'John', 'permissions' => 1, 'features' => 1]);   // read, feature1
        User::create(['name' => 'Jane', 'permissions' => 3, 'features' => 3]);   // read+write, feature1+feature2
        User::create(['name' => 'Bob', 'permissions' => 1, 'features' => 2]);    // read, feature2
        User::create(['name' => 'Alice', 'permissions' => 8, 'features' => 1]);  // admin, feature1

        // Test chaining multiple bitwise queries
        $users = User::whereBitwise('permissions', 'read')
            ->whereBitwise('features', 'feature1')
            ->get();

        $this->assertCount(2, $users);
        $this->assertEquals(['Jane', 'John'], $users->pluck('name')->sort()->values()->toArray());

        // Test chaining with different operators
        $users = User::whereHasBitwise('permissions', 'read')
            ->whereDoesntHaveBitwise('features', 'feature2')
            ->get();

        $this->assertCount(1, $users);
        $this->assertEquals('John', $users->first()->name);
    }

    public function test_bitwise_queries_with_different_models()
    {
        // Test with a different model using different cast format
        Post::create(['title' => 'Draft Post', 'status' => 1]);     // active
        Post::create(['title' => 'Published Post', 'status' => 2]); // pending
        Post::create(['title' => 'Archived Post', 'status' => 4]);  // archived

        $posts = Post::whereBitwise('status', 'active')->get();
        $this->assertCount(1, $posts);
        $this->assertEquals('Draft Post', $posts->first()->title);

        $posts = Post::whereHasAnyBitwise('status', ['active', 'pending'])->get();
        $this->assertCount(2, $posts);
    }

    public function test_complex_bitwise_queries()
    {
        User::create(['name' => 'SuperAdmin', 'permissions' => 15]); // read+write+delete+admin
        User::create(['name' => 'Editor', 'permissions' => 7]);      // read+write+delete
        User::create(['name' => 'Writer', 'permissions' => 3]);      // read+write
        User::create(['name' => 'Reader', 'permissions' => 1]);      // read

        // Test complex combinations
        $users = User::whereBitwise('permissions', 'read')
            ->whereBitwise('permissions', 'write')
            ->whereDoesntHaveBitwise('permissions', 'admin')
            ->get();

        $this->assertCount(2, $users);
        $this->assertEquals(['Editor', 'Writer'], $users->pluck('name')->sort()->values()->toArray());

        // Test with OR conditions
        $users = User::whereBitwiseEquals('permissions', ['read'])
            ->orWhereBitwiseEquals('permissions', ['read', 'write', 'delete', 'admin'])
            ->get();

        $this->assertCount(2, $users);
        $this->assertEquals(['Reader', 'SuperAdmin'], $users->pluck('name')->sort()->values()->toArray());
    }

    public function test_macro_error_handling()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Column 'invalid_column' is not cast as bitwise");

        User::whereBitwise('invalid_column', 'read')->get();
    }
}

class User extends Model
{
    protected $guarded = [];

    protected $casts = [
        'permissions' => AsBitwise::class . ':read,write,delete,admin',
        'features' => AsBitwise::class . ':feature1,feature2,feature3',
    ];
}

class Post extends Model
{
    protected $guarded = [];

    protected $casts = [
        'status' => AsBitwise::class . ':active,pending,archived',
    ];
}
