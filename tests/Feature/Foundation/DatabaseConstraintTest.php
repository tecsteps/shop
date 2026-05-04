<?php

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

test('sqlite enum columns are created with check constraints', function (): void {
    $tableSql = collect(DB::select(
        "select name, sql from sqlite_master where type = 'table' and name not like 'sqlite_%'"
    ))->mapWithKeys(fn (object $table): array => [$table->name => $table->sql]);

    expect($tableSql['stores'])->toContain('"status" varchar check ("status" in (\'active\', \'suspended\'))')
        ->and($tableSql['store_users'])->toContain('"role" varchar check ("role" in (\'owner\', \'admin\', \'staff\', \'support\'))')
        ->and($tableSql['products'])->toContain('"status" varchar check ("status" in (\'draft\', \'active\', \'archived\'))')
        ->and($tableSql['orders'])->toContain('"financial_status" varchar check ("financial_status" in (\'pending\', \'authorized\', \'paid\', \'partially_refunded\', \'refunded\', \'voided\'))')
        ->and($tableSql['tax_settings'])->toContain('"provider" varchar check ("provider" in (\'stripe_tax\', \'none\'))')
        ->and($tableSql['data_exports'])->toContain('"status" varchar check ("status" in (\'queued\', \'processing\', \'completed\', \'failed\'))');
});

test('personal access tokens table matches the api token schema', function (): void {
    expect(Schema::hasTable('personal_access_tokens'))->toBeTrue()
        ->and(Schema::hasColumns('personal_access_tokens', [
            'id',
            'store_id',
            'tokenable_type',
            'tokenable_id',
            'name',
            'token',
            'abilities',
            'last_used_at',
            'expires_at',
            'created_at',
            'updated_at',
        ]))->toBeTrue()
        ->and(Schema::hasIndex('personal_access_tokens', ['token'], 'unique'))->toBeTrue()
        ->and(Schema::hasIndex('personal_access_tokens', ['tokenable_type', 'tokenable_id']))->toBeTrue()
        ->and(Schema::hasIndex('personal_access_tokens', ['store_id', 'tokenable_type', 'tokenable_id']))->toBeTrue();
});

test('users table tracks platform administrators separately from store roles', function (): void {
    expect(Schema::hasColumn('users', 'is_platform_admin'))->toBeTrue()
        ->and(Schema::hasIndex('users', ['is_platform_admin']))->toBeTrue();
});
