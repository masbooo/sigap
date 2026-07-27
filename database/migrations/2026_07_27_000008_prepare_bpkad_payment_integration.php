<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayaran', function (Blueprint $table): void {
            if (!Schema::hasColumn('pembayaran', 'last_checked_at')) {
                $table->dateTime('last_checked_at')->nullable()->after('cancelled_at');
            }

            if (!Schema::hasColumn('pembayaran', 'callback_payload')) {
                $table->longText('callback_payload')->nullable()->after('raw_response');
            }

            if (!Schema::hasColumn('pembayaran', 'callback_received_at')) {
                $table->dateTime('callback_received_at')->nullable()->after('callback_payload');
            }
        });

        if (!Schema::hasTable('payment_callback_logs')) {
            Schema::create('payment_callback_logs', function (Blueprint $table): void {
                $table->id();
                $table->string('provider')->default('BPKAD');
                $table->string('external_id')->nullable()->index();
                $table->string('payment_method', 20)->nullable();
                $table->string('status', 50)->nullable();
                $table->decimal('amount', 14, 2)->nullable();
                $table->json('headers')->nullable();
                $table->longText('payload')->nullable();
                $table->boolean('signature_valid')->default(false);
                $table->string('process_status', 30)->default('received');
                $table->text('process_message')->nullable();
                $table->foreignId('payment_id')->nullable()->index();
                $table->foreignId('reservation_id')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_callback_logs');

        Schema::table('pembayaran', function (Blueprint $table): void {
            foreach (['callback_received_at', 'callback_payload', 'last_checked_at'] as $column) {
                if (Schema::hasColumn('pembayaran', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
