<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promociones', function (Blueprint $table) {
            $table->foreignId('producto_id')->nullable()->unique()->constrained('productos')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('promociones', function (Blueprint $table) {
            $table->dropForeign(['producto_id']);
            $table->dropUnique(['producto_id']);
            $table->dropColumn('producto_id');
        });
    }
};
