<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredientes', function (Blueprint $table) {
            $table->boolean('is_available')->default(true);
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('preparing_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->index(['status', 'created_at']);
        });
        Schema::create('operation_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value');
            $table->timestamps();
        });
        Schema::create('admin_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('action');
            $table->json('changes');
            $table->timestamps();
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_changes');
        Schema::dropIfExists('operation_settings');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropColumn(['preparing_at', 'ready_at', 'delivered_at', 'cancelled_at']);
        });
        Schema::table('ingredientes', fn (Blueprint $table) => $table->dropColumn('is_available'));
    }
};
