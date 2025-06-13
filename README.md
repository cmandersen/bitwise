# Laravel Bitwise Package

A Laravel package that makes it easy to work with bitwise flags in Eloquent models. Store multiple boolean flags efficiently in a single integer database column with natural query syntax.

## Requirements

- PHP 8.0 or higher
- Laravel 9.x, 10.x, 11.x, or 12.x

## Installation

Install the package via Composer:

```bash
composer require cmandersen/bitwise
```

The package will automatically register itself via Laravel's package discovery.

## Setup Your Database

Create your database column as an unsigned integer:

```php
// In your migration
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->unsignedInteger('permissions')->default(0);
    $table->unsignedInteger('features')->default(0);
    $table->timestamps();
});
```

## Cast Your Model Attributes

Add bitwise casting to your Eloquent model attributes:

```php
use Illuminate\Database\Eloquent\Model;
use Cmandersen\Bitwise\AsBitwise;

class User extends Model
{
    protected function casts(): array
    {
        return [
            // Method 1: Auto-generate flags from names
            'permissions' => AsBitwise::auto(['read', 'write', 'delete', 'admin']),
            
            // Method 2: Define flags with explicit values
            'features' => AsBitwise::flags(['notifications' => 1, 'dark_mode' => 2, 'beta' => 4]),
            
            // Method 3: Shorthand syntax
            'roles' => 'bitwise:user,moderator,admin',
            'settings' => 'bitwise:email_notifications,push_notifications,sms_notifications',
        ];
    }
}
```

## Working with Flags

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

## Querying Models by Flags

The package automatically adds powerful query methods to all your models. No traits or additional setup required!

### Basic Queries

```php
// Find users who have 'read' permission
$readUsers = User::whereBitwise('permissions', 'read')->get();

// Find users who have both 'read' AND 'write' permissions
$readWriteUsers = User::whereBitwise('permissions', ['read', 'write'])->get();

// Find users who have ANY of the specified permissions
$moderatorUsers = User::whereHasAnyBitwise('permissions', ['write', 'delete'])->get();

// Find users who DON'T have admin permissions
$regularUsers = User::whereDoesntHaveBitwise('permissions', 'admin')->get();

// Find users with EXACTLY the 'read' permission (no other permissions)
$readOnlyUsers = User::whereBitwiseEquals('permissions', 'read')->get();
```

### Combining with Other Conditions

```php
// Multiple flag combinations
$multiplePermissions = User::whereInBitwise('permissions', ['read', 'admin'])->get();
$mixedValues = User::whereInBitwise('permissions', [
    'read',              // Single permission
    ['read', 'write'],   // Multiple permissions (has both)
])->get();

// Exclude specific permissions
$excludePermissions = User::whereNotInBitwise('permissions', ['admin', 'delete'])->get();

// OR conditions
$readOrWrite = User::whereBitwise('permissions', 'read')
    ->orWhereBitwise('permissions', 'write')
    ->get();

// Complex queries with other conditions
$complexQuery = User::where('name', 'like', '%admin%')
    ->whereHasBitwise('permissions', 'admin')
    ->whereDoesntHaveBitwise('features', 'beta')
    ->orderBy('created_at', 'desc')
    ->get();
```

### Complete Query Reference

All methods support OR variations (prefix with `or`):

- `whereBitwise($column, $flags)` - Has specified flags
- `whereHasBitwise($column, $flags)` - Alias for whereBitwise
- `whereHasAnyBitwise($column, $flags)` - Has any of the flags
- `whereDoesntHaveBitwise($column, $flags)` - Doesn't have flags
- `whereBitwiseEquals($column, $flags)` - Has exactly these flags
- `whereInBitwise($column, $values)` - Bitwise equivalent of whereIn
- `whereNotInBitwise($column, $values)` - Bitwise equivalent of whereNotIn

## Array-like Access

`BitwiseCollection` implements `ArrayAccess`, so you can use array syntax:

```php
// Check if flag is set (read-only)
if ($user->permissions['read']) {
    // User has read permission
}

// Note: Direct assignment is not allowed - use add()/remove() methods instead
// $user->permissions['read'] = true; // This will throw an exception
```

## Setting Flags During Creation/Update

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

For more advanced use cases, you can work with individual flags and utilities:

### Manual Flag Generation

```php
use Cmandersen\Bitwise\Bitwise;

// Auto-generate flags from names
$flags = Bitwise::generateFlags(['read', 'write', 'delete', 'admin']);
// Results in: ['read' => 1, 'write' => 2, 'delete' => 4, 'admin' => 8]

// Generate from associative array with mixed values
$flags = Bitwise::generateFromAssoc([
    'read' => null,      // Auto-assigned: 1
    'write' => 16,       // Manual value: 16
    'delete' => null,    // Auto-assigned: 32 (next available)
    'admin' => true,     // Auto-assigned: 64
]);
```

### Working with Individual Flags

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

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).