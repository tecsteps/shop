<?php

namespace App\Support\Database;

use Illuminate\Support\Facades\DB;

/**
 * Helper for enforcing TEXT-with-CHECK enum constraints on SQLite.
 *
 * SQLite cannot add a CHECK constraint via ALTER TABLE, so we enforce the
 * allowed-value set with BEFORE INSERT / BEFORE UPDATE triggers instead. This
 * keeps the constraint at the database layer (per the schema spec) while still
 * letting migrations build their tables with the fluent Blueprint API.
 */
class SqliteEnumCheck
{
    /**
     * Add a database-level allowed-value constraint for an enum column.
     *
     * @param  list<string>  $allowed
     */
    public static function add(string $table, string $column, array $allowed): void
    {
        $list = collect($allowed)
            ->map(fn (string $value): string => "'".str_replace("'", "''", $value)."'")
            ->implode(', ');

        foreach (['INSERT', 'UPDATE'] as $event) {
            $trigger = "chk_{$table}_{$column}_".strtolower($event);

            DB::statement("DROP TRIGGER IF EXISTS {$trigger}");
            DB::statement(
                "CREATE TRIGGER {$trigger} BEFORE {$event} ON {$table}
                WHEN NEW.{$column} NOT IN ({$list})
                BEGIN SELECT RAISE(ABORT, 'invalid {$table}.{$column}'); END"
            );
        }
    }
}
