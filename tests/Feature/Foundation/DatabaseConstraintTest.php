<?php

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

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
