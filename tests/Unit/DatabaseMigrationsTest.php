<?php

namespace Tests\Unit;

use Tests\TestCase;

class DatabaseMigrationsTest extends TestCase
{
    public function test_migrations_do_not_create_the_same_table_twice(): void
    {
        $created = [];

        foreach (glob(database_path('migrations/*.php')) as $file) {
            $contents = (string) file_get_contents($file);

            preg_match_all("/Schema::create\(\s*['\"]([^'\"]+)['\"]/", $contents, $matches);

            foreach ($matches[1] as $table) {
                $created[$table][] = basename($file);
            }
        }

        $duplicates = array_filter($created, fn (array $files): bool => count($files) > 1);

        $this->assertSame([], $duplicates, 'Tables created more than once: '.json_encode($duplicates));
    }

    public function test_legacy_shop_admin_tables_are_not_part_of_the_schema(): void
    {
        foreach (glob(database_path('migrations/*.php')) as $file) {
            $contents = (string) file_get_contents($file);

            $this->assertStringNotContainsString("'shop_admins'", $contents, basename($file));
            $this->assertStringNotContainsString('admin_id', $contents, basename($file));
            $this->assertStringNotContainsString('shop_admin_audit_logs', $contents, basename($file));
        }
    }
}
