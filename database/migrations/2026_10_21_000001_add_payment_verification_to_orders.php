<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Null means historical/unverified; do not infer payment from old status.
            $table->string('payment_status', 30)->nullable()->index();
            $table->timestamp('payment_updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);
            $table->dropColumn(['payment_status', 'payment_updated_at']);
        });
    }
};
