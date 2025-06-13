<?php

namespace Cmandersen\Bitwise;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class BitwiseServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        $this->registerQueryBuilderMacros();
    }

    protected function registerQueryBuilderMacros()
    {
        // Helper function to get bitwise cast for a column
        $getBitwiseCastForColumn = function ($model, string $column) {
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
                // Check if it's a custom cast class
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
        EloquentBuilder::macro('whereBitwise', function (string $column, string|array $flags, string $boolean = 'and') {
            return $this->whereBitwiseLogic($column, $flags, $boolean, 'has');
        });

        EloquentBuilder::macro('whereHasBitwise', function (string $column, string|array $flags, string $boolean = 'and') {
            return $this->whereBitwiseLogic($column, $flags, $boolean, 'has');
        });

        EloquentBuilder::macro('whereHasAnyBitwise', function (string $column, string|array $flags, string $boolean = 'and') {
            return $this->whereBitwiseLogic($column, $flags, $boolean, 'hasAny');
        });

        EloquentBuilder::macro('whereDoesntHaveBitwise', function (string $column, string|array $flags, string $boolean = 'and') {
            return $this->whereBitwiseLogic($column, $flags, $boolean, 'doesntHave');
        });

        EloquentBuilder::macro('whereBitwiseEquals', function (string $column, string|array $flags, string $boolean = 'and') {
            return $this->whereBitwiseLogic($column, $flags, $boolean, 'equals');
        });

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
        EloquentBuilder::macro('orWhereBitwise', function (string $column, string|array $flags) {
            return $this->whereBitwise($column, $flags, 'or');
        });

        EloquentBuilder::macro('orWhereHasBitwise', function (string $column, string|array $flags) {
            return $this->whereHasBitwise($column, $flags, 'or');
        });

        EloquentBuilder::macro('orWhereHasAnyBitwise', function (string $column, string|array $flags) {
            return $this->whereHasAnyBitwise($column, $flags, 'or');
        });

        EloquentBuilder::macro('orWhereDoesntHaveBitwise', function (string $column, string|array $flags) {
            return $this->whereDoesntHaveBitwise($column, $flags, 'or');
        });

        EloquentBuilder::macro('orWhereBitwiseEquals', function (string $column, string|array $flags) {
            return $this->whereBitwiseEquals($column, $flags, 'or');
        });

        // WhereIn variations
        EloquentBuilder::macro('whereInBitwise', function (string $column, array $values, string $boolean = 'and', bool $not = false) use ($getBitwiseCastForColumn) {
            $model = $this->getModel();
            $cast = $getBitwiseCastForColumn($model, $column);

            if (! $cast) {
                throw new InvalidArgumentException("Column '{$column}' is not cast as bitwise on model " . get_class($model));
            }

            $conditions = [];
            $bindings = [];

            foreach ($values as $value) {
                if (is_string($value)) {
                    // Single flag
                    $flagValue = $cast->set(null, $column, $value, []);
                    $conditions[] = "({$column} & ? = ?)";
                    $bindings[] = $flagValue;
                    $bindings[] = $flagValue;
                } elseif (is_array($value)) {
                    // Multiple flags (has all)
                    $flagValue = 0;
                    foreach ($value as $flag) {
                        $flagValue |= $cast->set(null, $column, $flag, []);
                    }
                    $conditions[] = "({$column} & ? = ?)";
                    $bindings[] = $flagValue;
                    $bindings[] = $flagValue;
                }
            }

            if (empty($conditions)) {
                return $this;
            }

            $sql = '(' . implode($not ? ' AND ' : ' OR ', $conditions) . ')';
            if ($not) {
                $sql = 'NOT ' . $sql;
            }

            return $this->whereRaw($sql, $bindings, $boolean);
        });

        EloquentBuilder::macro('whereNotInBitwise', function (string $column, array $values, string $boolean = 'and') {
            return $this->whereInBitwise($column, $values, $boolean, true);
        });

        EloquentBuilder::macro('orWhereInBitwise', function (string $column, array $values) {
            return $this->whereInBitwise($column, $values, 'or', false);
        });

        EloquentBuilder::macro('orWhereNotInBitwise', function (string $column, array $values) {
            return $this->whereInBitwise($column, $values, 'or', true);
        });
    }
}
