<?php

namespace Cmandersen\Bitwise;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class BitwiseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerQueryBuilderMacros();
    }

    protected function registerQueryBuilderMacros(): void
    {
        // Helper function to get bitwise cast for a column
        $getBitwiseCastForColumn = function ($model, string $column): ?AsBitwise {
            $casts = $model->getCasts();

            if (! isset($casts[$column])) {
                return null;
            }

            $castType = $casts[$column];

            // Handle object-based cast definitions
            if ($castType instanceof AsBitwise) {
                return $castType;
            }

            // Handle string-based cast definitions
            if (is_string($castType)) {
                // Check for Laravel's parameterized cast syntax: ClassName::class . ':param1,param2'
                if (str_contains($castType, ':')) {
                    [$className, $parameters] = explode(':', $castType, 2);

                    // Check if it's the AsBitwise class with parameters
                    if ($className === AsBitwise::class || (class_exists($className) && is_subclass_of($className, AsBitwise::class))) {
                        // Parse the parameters as flag names for auto-generation
                        $flags = explode(',', $parameters);

                        return AsBitwise::auto(array_map('trim', $flags));
                    }
                }

                // Check if it's a custom cast class without parameters
                if (class_exists($castType) && is_subclass_of($castType, AsBitwise::class)) {
                    return new $castType;
                }

                // Check for shorthand cast syntax like 'bitwise:read,write,delete'
                if (str_starts_with($castType, 'bitwise:')) {
                    $flags = explode(',', substr($castType, 8));

                    return AsBitwise::auto(array_map('trim', $flags));
                }
            }

            return null;
        };

        // Explicit bitwise query methods
        EloquentBuilder::macro('whereBitwise', fn (string $column, string|array $flags, string $boolean = 'and') => $this->whereBitwiseLogic($column, $flags, $boolean, 'has'));

        EloquentBuilder::macro('whereHasBitwise', fn (string $column, string|array $flags, string $boolean = 'and') => $this->whereBitwiseLogic($column, $flags, $boolean, 'has'));

        EloquentBuilder::macro('whereHasAnyBitwise', fn (string $column, string|array $flags, string $boolean = 'and') => $this->whereBitwiseLogic($column, $flags, $boolean, 'hasAny'));

        EloquentBuilder::macro('whereDoesntHaveBitwise', fn (string $column, string|array $flags, string $boolean = 'and') => $this->whereBitwiseLogic($column, $flags, $boolean, 'doesntHave'));

        EloquentBuilder::macro('whereBitwiseEquals', fn (string $column, string|array $flags, string $boolean = 'and') => $this->whereBitwiseLogic($column, $flags, $boolean, 'equals'));

        // Helper macro for bitwise logic
        EloquentBuilder::macro('whereBitwiseLogic', function (string $column, string|array $flags, string $boolean, string $operator) use ($getBitwiseCastForColumn) {
            $model = $this->getModel();
            $cast = $getBitwiseCastForColumn($model, $column);

            if (! $cast) {
                throw new InvalidArgumentException("Column '{$column}' is not cast as bitwise on model " . get_class($model));
            }

            $flags = is_array($flags) ? $flags : [$flags];
            $flagValue = 0;

            foreach ($flags as $flag) {
                $flagValue |= $cast->set(null, $column, $flag, []);
            }

            switch ($operator) {
                case 'has':
                    return $this->whereRaw("({$column} & ?) = ?", [$flagValue, $flagValue], $boolean);

                case 'hasAny':
                    return $this->whereRaw("({$column} & ?) > 0", [$flagValue], $boolean);

                case 'doesntHave':
                    return $this->whereRaw("({$column} & ?) = 0", [$flagValue], $boolean);

                case 'equals':
                    return $this->where($column, $flagValue, null, $boolean);

                default:
                    throw new InvalidArgumentException("Unsupported bitwise operator: {$operator}");
            }
        });

        // Or variations
        EloquentBuilder::macro('orWhereBitwise', fn (string $column, string|array $flags) => $this->whereBitwise($column, $flags, 'or'));

        EloquentBuilder::macro('orWhereHasBitwise', fn (string $column, string|array $flags) => $this->whereHasBitwise($column, $flags, 'or'));

        EloquentBuilder::macro('orWhereHasAnyBitwise', fn (string $column, string|array $flags) => $this->whereHasAnyBitwise($column, $flags, 'or'));

        EloquentBuilder::macro('orWhereDoesntHaveBitwise', fn (string $column, string|array $flags) => $this->whereDoesntHaveBitwise($column, $flags, 'or'));

        EloquentBuilder::macro('orWhereBitwiseEquals', fn (string $column, string|array $flags) => $this->whereBitwiseEquals($column, $flags, 'or'));

        // WhereIn variations
        EloquentBuilder::macro('whereInBitwise', function (string $column, array $values, string $boolean = 'and', bool $not = false) use ($getBitwiseCastForColumn) {
            $model = $this->getModel();
            $cast = $getBitwiseCastForColumn($model, $column);

            if (! $cast) {
                throw new InvalidArgumentException("Column '{$column}' is not cast as bitwise on model " . get_class($model));
            }

            $flagValues = [];

            foreach ($values as $value) {
                if (is_string($value)) {
                    // Single flag
                    $flagValues[] = $cast->set(null, $column, $value, []);
                } elseif (is_array($value)) {
                    // Multiple flags (has all)
                    $flagValue = 0;
                    foreach ($value as $flag) {
                        $flagValue |= $cast->set(null, $column, $flag, []);
                    }
                    $flagValues[] = $flagValue;
                }
            }

            if (empty($flagValues)) {
                return $this;
            }

            // Use Laravel's native whereIn/whereNotIn methods
            return $not
                ? $this->whereNotIn($column, $flagValues, $boolean)
                : $this->whereIn($column, $flagValues, $boolean);
        });

        EloquentBuilder::macro('whereNotInBitwise', fn (string $column, array $values, string $boolean = 'and') => $this->whereInBitwise($column, $values, $boolean, true));

        EloquentBuilder::macro('orWhereInBitwise', fn (string $column, array $values) => $this->whereInBitwise($column, $values, 'or', false));

        EloquentBuilder::macro('orWhereNotInBitwise', fn (string $column, array $values) => $this->whereInBitwise($column, $values, 'or', true));
    }
}
