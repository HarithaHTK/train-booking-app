<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE `trains` DROP FOREIGN KEY `trains_route_id_foreign`');
        DB::statement('ALTER TABLE `trains` MODIFY `route_id` BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE `trains` ADD CONSTRAINT `trains_route_id_foreign` FOREIGN KEY (`route_id`) REFERENCES `routes`(`id`) ON DELETE SET NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `trains` DROP FOREIGN KEY `trains_route_id_foreign`');
        DB::statement('ALTER TABLE `trains` MODIFY `route_id` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `trains` ADD CONSTRAINT `trains_route_id_foreign` FOREIGN KEY (`route_id`) REFERENCES `routes`(`id`) ON DELETE RESTRICT');
    }
};
