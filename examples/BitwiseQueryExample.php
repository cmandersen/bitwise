<?php

namespace Cmandersen\Bitwise\Examples;

use Cmandersen\Bitwise\AsBitwise;
use Illuminate\Database\Eloquent\Model;

/**
 * Example demonstrating how to use bitwise queries - NO TRAIT REQUIRED!
 */
class User extends Model
{
    protected $fillable = ['name', 'permissions', 'features'];

    protected function casts(): array
    {
        return [
            // Object syntax
            'permissions' => AsBitwise::auto(['read', 'write', 'delete', 'admin']),
            'features' => AsBitwise::flags(['notifications' => 1, 'dark_mode' => 2, 'beta' => 4]),

            // Shorthand syntax (NEW!)
            'roles' => 'bitwise:user,moderator,admin',
            'settings' => 'bitwise:email_notifications,push_notifications,sms_notifications',
        ];
    }
}

/**
 * Usage Examples:
 */

// 1. NATURAL SYNTAX - Simple and clean
// Find users who have 'read' permission
$readUsers = User::whereBitwise('permissions', 'read')->get();

// Find users who have both 'read' AND 'write' permissions
$readWriteUsers = User::whereBitwise('permissions', ['read', 'write'])->get();

// 2. EXPLICIT BITWISE MACROS - More specific control
// Find users who have the 'admin' permission
$adminUsers = User::whereBitwise('permissions', 'admin')->get();
// Alternative method name (more explicit)
$adminUsers = User::whereHasBitwise('permissions', 'admin')->get();

// Find users who have ANY of the specified permissions
$moderatorUsers = User::whereHasAnyBitwise('permissions', ['write', 'delete'])->get();

// Find users who DON'T have admin permissions
$regularUsers = User::whereDoesntHaveBitwise('permissions', 'admin')->get();

// Find users with EXACTLY the 'read' permission (no other permissions)
$readOnlyUsers = User::whereBitwiseEquals('permissions', 'read')->get();

// 3. WHERE IN BITWISE SUPPORT - Specialized whereIn for bitwise
$multiplePermissions = User::whereInBitwise('permissions', ['read', 'admin'])->get();
$mixedValues = User::whereInBitwise('permissions', [
    'read',              // Single permission
    ['read', 'write'],    // Multiple permissions (has both)
])->get();
$excludePermissions = User::whereNotInBitwise('permissions', ['admin', 'delete'])->get();

// 4. OR CONDITIONS - All bitwise macros support OR variations
$readOrWrite = User::whereBitwise('permissions', 'read')
    ->orWhereBitwise('permissions', 'write')
    ->get();

$complexOr = User::whereHasBitwise('permissions', 'admin')
    ->orWhereInBitwise('features', ['beta', 'dark_mode'])
    ->get();

// 5. MULTIPLE COLUMNS - Works with any bitwise-cast column
$betaUsers = User::whereBitwise('features', 'beta')->get();
$darkModeUsers = User::whereHasBitwise('features', 'dark_mode')->get();

// 6. CHAINING - Can be combined with other query methods
$complexQuery = User::where('name', 'like', '%admin%')
    ->whereHasBitwise('permissions', 'admin')
    ->whereDoesntHaveBitwise('features', 'beta')
    ->whereNotInBitwise('permissions', ['delete'])
    ->orderBy('created_at', 'desc')
    ->get();

/**
 * How it works:
 *
 * 1. Cast your integer columns using AsBitwise::auto(), AsBitwise::flags(), or shorthand syntax
 * 2. Use the bitwise query macros that are automatically registered
 * 3. The macros automatically detect bitwise columns and convert flag names to proper SQL
 * 4. NO TRAIT REQUIRED! Just cast your columns and use the query methods.
 *
 * Behind the scenes, ->whereBitwise('permissions', 'read') becomes:
 * ->whereRaw('(permissions & ?) = ?', [1, 1])
 *
 * Advantages of this approach:
 * - Zero configuration - just cast your columns and go!
 * - No traits to remember or configure
 * - Clean, simple method names without unnecessary words
 * - Works with all existing Laravel query builder features
 * - Explicit method names make intent clear
 * - Full IDE support with proper method signatures
 * - Shorthand syntax for quick setup
 *
 * Supported cast formats:
 * - AsBitwise::auto(['read', 'write'])     // Auto-generates powers of 2
 * - AsBitwise::flags(['read' => 1])        // Custom flag values
 * - 'bitwise:read,write,delete'            // Shorthand auto-generation
 */
