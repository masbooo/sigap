<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayaran', function (Blueprint $table): void {
            if (!Schema::hasColumn('pembayaran', 'reservasi_id')) {
                $table->unsignedBigInteger('reservasi_id')->nullable()->after('id')->index();
            }

            if (!Schema::hasColumn('pembayaran', 'nominal')) {
                $table->decimal('nominal', 15, 2)->nullable()->after('reservasi_id');
            }

            if (!Schema::hasColumn('pembayaran', 'metode')) {
                $table->string('metode', 100)->nullable()->after('nominal');
            }

            if (!Schema::hasColumn('pembayaran', 'bukti_pembayaran')) {
                $table->string('bukti_pembayaran')->nullable()->after('metode');
            }

            if (!Schema::hasColumn('pembayaran', 'tanggal_bayar')) {
                $table->dateTime('tanggal_bayar')->nullable()->after('bukti_pembayaran');
            }

            if (!Schema::hasColumn('pembayaran', 'status_verifikasi')) {
                $table->string('status_verifikasi', 50)->nullable()->default('PENDING')->after('tanggal_bayar');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pembayaran', function (Blueprint $table): void {
            $columns = [
                'status_verifikasi',
                'tanggal_bayar',
                'bukti_pembayaran',
                'metode',
                'nominal',
                'reservasi_id',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('pembayaran', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
