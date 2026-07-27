<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE menu_peran MODIFY id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY');
        DB::statement('ALTER TABLE menu_peran ADD UNIQUE KEY uniq_peran_menu (peran_id, menu_id)');
        DB::statement('ALTER TABLE menu_peran ADD KEY idx_menu_peran_peran (peran_id)');
        DB::statement('ALTER TABLE menu_peran ADD KEY idx_menu_peran_menu (menu_id)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE menu_peran DROP KEY idx_menu_peran_menu');
        DB::statement('ALTER TABLE menu_peran DROP KEY idx_menu_peran_peran');
        DB::statement('ALTER TABLE menu_peran DROP KEY uniq_peran_menu');
        DB::statement('ALTER TABLE menu_peran MODIFY id INT(11) NOT NULL');
    }
};
