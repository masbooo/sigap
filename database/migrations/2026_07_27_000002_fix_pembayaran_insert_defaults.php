<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE pembayaran MODIFY id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY');
        DB::statement('ALTER TABLE pembayaran MODIFY reservation_id INT(11) NULL DEFAULT NULL');
        DB::statement("ALTER TABLE pembayaran MODIFY payment_method ENUM('VA','QRIS') NULL DEFAULT NULL");
        DB::statement('ALTER TABLE pembayaran MODIFY payment_code VARCHAR(255) NULL DEFAULT NULL');
        DB::statement('ALTER TABLE pembayaran MODIFY expired_at DATETIME NULL DEFAULT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE pembayaran MODIFY expired_at DATETIME NOT NULL');
        DB::statement('ALTER TABLE pembayaran MODIFY payment_code VARCHAR(255) NOT NULL');
        DB::statement("ALTER TABLE pembayaran MODIFY payment_method ENUM('VA','QRIS') NOT NULL");
        DB::statement('ALTER TABLE pembayaran MODIFY reservation_id INT(11) NOT NULL');
        DB::statement('ALTER TABLE pembayaran MODIFY id INT(11) NOT NULL');
    }
};
