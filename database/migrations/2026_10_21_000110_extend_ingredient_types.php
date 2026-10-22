<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredientes', function (Blueprint $table) {
            $table->enum('tipo', ['envoltura', 'proteina', 'vegetal', 'base', 'salsa', 'topping', 'otros'])
                ->default('vegetal')->change();
        });
    }

    public function down(): void
    {
        // Never coerce or discard classifications introduced after this migration.
        if (DB::table('ingredientes')->whereNotIn('tipo', ['envoltura', 'proteina', 'vegetal'])->exists()) {
            throw new RuntimeException('No se puede reducir tipo: existen ingredientes con clasificaciones nuevas.');
        }
        Schema::table('ingredientes', function (Blueprint $table) {
            $table->enum('tipo', ['envoltura', 'proteina', 'vegetal'])->default('vegetal')->change();
        });
    }
};
