# Laravel Bitwise Package

A Laravel package that makes it easy to work with bitwise flags in Eloquent models. Store multiple boolean flags efficiently in a single integer database column.

## Installation

Install the package via Composer:

```bash
composer require cmandersen/bitwise
```

The package will automatically register itself via Laravel's package discovery.

## Basic Usage

### 1. Define Your Flags

First, define your bitwise flags. You can do this in several ways:

```php
use Cmandersen\Bitwise\Bitwise;

// Method 1: Auto-generate flags from names
$flags = Bitwise::generateFlags(['read', 'write', 'delete', 'admin']);
// Results in: ['read' => 1, 'write' => 2, 'delete' => 4, 'admin' => 8]

// Method 2: Generate from associative array with mixed values
$flags = Bitwise::generateFromAssoc([
    'read' => null,      // Auto-assigned: 1
    'write' => 16,       // Manual value: 16
    'delete' => null,    // Auto-assigned: 32 (next available)
    'admin' => true,     // Auto-assigned: 64
]);
```

### 2. Use in Your Eloquent Models

Add bitwise casting to your Eloquent models:

```php
use Illuminate\Database\Eloquent\Model;
use Cmandersen\Bitwise\AsBitwise;

class User extends Model
{
    protected $casts = [
        // Method 1: Auto-generate flags
        'permissions' => AsBitwise::class . ':read,write,delete,admin',
        
        // Method 2: Use predefined flags
        'settings' => AsBitwise::class,
    ];
    
    // Or define flags with explicit values
    protected function casts(): array
    {
        return [
            'permissions' => AsBitwise::flags([
                'read' => 1,
                'write' => 2,
                'delete' => 4,
                'admin' => 8,
            ]),
        ];
    }
}
```

### 3. Working with Flags

Once cast, your attribute becomes a `BitwiseCollection` with many helpful methods:

```php
$user = User::find(1);

// Check if flags are set
if ($user->permissions->has('read')) {
    // User has read permission
}

if ($user->permissions->has('read', 'write')) {
    // User has both read AND write permissions
}

if ($user->permissions->hasAny('read', 'admin')) {
    // User has read OR admin permissions (or both)
}

// Add flags
$user->permissions = $user->permissions->add('write');
$user->permissions = $user->permissions->add('delete', 'admin');

// Remove flags
$user->permissions = $user->permissions->remove('delete');

// Toggle flags
$user->permissions = $user->permissions->toggle('admin');

// Get only specific flags
$readWrite = $user->permissions->only('read', 'write');

// Get all except specific flags
$withoutAdmin = $user->permissions->except('admin');

// Clear all flags
$user->permissions = $user->permissions->clear();

// Set all available flags
$user->permissions = $user->permissions->all();

// Save changes
$user->save();
```

### 4. Array-like Access

`BitwiseCollection` implements `ArrayAccess`, so you can use array syntax:

```php
// Check if flag is set (read-only)
if ($user->permissions['read']) {
    // User has read permission
}

// Note: Direct assignment is not allowed - use add()/remove() methods instead
// $user->permissions['read'] = true; // This will throw an exception
```

### 5. Setting Flags During Creation/Update

You can set flags in several ways:

```php
// Using array of flag names
User::create([
    'name' => 'John Doe',
    'permissions' => ['read', 'write'],
]);

// Using a BitwiseCollection
$permissions = (new BitwiseCollection(0, ['read' => 1, 'write' => 2]))
    ->add('read', 'write');
    
User::create([
    'name' => 'Jane Doe',
    'permissions' => $permissions,
]);

// Using direct integer value (if you know the bit values)
User::create([
    'name' => 'Admin User',
    'permissions' => 15, // 1 + 2 + 4 + 8 = all flags
]);
```

## Advanced Usage

### Working with Individual Flags

You can create and work with individual `BitwiseFlag` objects:

```php
use Cmandersen\Bitwise\BitwiseFlag;

$readFlag = new BitwiseFlag('read', 1);
$writeFlag = new BitwiseFlag('write', 2);

// Check flag properties
echo $readFlag->name;    // 'read'
echo $readFlag->value;   // 1
echo $readFlag;          // 'read' (string conversion)

// Flag operations
$readFlag->isSetIn(5);           // true (5 = 1 + 4, so read flag is set)
$readFlag->setBit(4);            // 5 (4 | 1 = 5)
$readFlag->unsetBit(5);          // 4 (5 & ~1 = 4)
$readFlag->toggleBit(4);         // 5 (4 ^ 1 = 5)

// Combine flags
$combined = $readFlag->combine($writeFlag); // 3 (1 | 2 = 3)
```

### Utility Methods

The `Bitwise` class provides several utility methods:

```php
use Cmandersen\Bitwise\Bitwise;

// Check if a number is a power of 2
Bitwise::isPowerOfTwo(8);  // true
Bitwise::isPowerOfTwo(6);  // false

// Get the next power of 2
Bitwise::nextPowerOfTwo(5);  // 8
Bitwise::nextPowerOfTwo(8);  // 8

// Create a cast instance
$cast = Bitwise::createCast(['read', 'write', 'delete']);
```

### String-based Flag Definition

You can define flags using a string format:

```php
// In your model cast
protected $casts = [
    'permissions' => AsBitwise::class . ':read=1,write=2,delete=4,admin=8',
];
```

## Database Schema

Create your database column as an unsigned integer:

```php
// In your migration
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->unsignedInteger('permissions')->default(0);
    $table->timestamps();
});
```

## Methods Reference

### BitwiseCollection Methods

| Method | Description | Returns |
|--------|-------------|---------|
| `has(...$flags)` | Check if all specified flags are set | `bool` |
| `hasAny(...$flags)` | Check if any specified flags are set | `bool` |
| `add(...$flags)` | Add flags (returns new instance) | `BitwiseCollection` |
| `remove(...$flags)` | Remove flags (returns new instance) | `BitwiseCollection` |
| `toggle(...$flags)` | Toggle flags (returns new instance) | `BitwiseCollection` |
| `only(...$flags)` | Keep only specified flags | `BitwiseCollection` |
| `except(...$flags)` | Remove specified flags (alias for remove) | `BitwiseCollection` |
| `clear()` | Remove all flags | `BitwiseCollection` |
| `all()` | Set all available flags | `BitwiseCollection` |
| `getValue()` | Get integer value | `int` |
| `getFlags()` | Get array of BitwiseFlag objects | `array` |
| `getFlagNames()` | Get array of active flag names | `array` |
| `isEmpty()` | Check if no flags are set | `bool` |
| `isNotEmpty()` | Check if any flags are set | `bool` |
| `toArray()` | Get array of active flag names | `array` |
| `count()` | Count active flags | `int` |

### BitwiseFlag Methods

| Method | Description | Returns |
|--------|-------------|---------|
| `is($flag)` | Check if this flag matches another | `bool` |
| `hasValue($value)` | Check if flag has specific value | `bool` |
| `isPowerOfTwo()` | Check if value is power of 2 | `bool` |
| `combine(...$flags)` | Combine with other flags | `int` |
| `isSetIn($value)` | Check if flag is set in value | `bool` |
| `setBit($value)` | Set this flag in value | `int` |
| `unsetBit($value)` | Unset this flag in value | `int` |
| `toggleBit($value)` | Toggle this flag in value | `int` |

## Requirements

- PHP 8.0 or higher
- Laravel 9.x, 10.x, 11.x, or 12.x

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).