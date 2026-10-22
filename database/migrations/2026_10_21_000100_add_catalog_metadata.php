<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('orden')->default(0);
        });
        Schema::table('productos', function (Blueprint $table) {
            $table->string('tipo', 30)->default('simple');
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('orden')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('productos', fn (Blueprint $table) => $table->dropColumn(['tipo', 'activo', 'orden']));
        Schema::table('categorias', fn (Blueprint $table) => $table->dropColumn(['activo', 'orden']));
    }
};
