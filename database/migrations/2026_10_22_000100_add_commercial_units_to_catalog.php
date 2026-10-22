<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('unidad_tipo', 30)->default('pieza');
        });
        Schema::table('bloques_promocion', function (Blueprint $table) {
            $table->string('unidad_tipo', 30)->default('pieza');
            $table->unsignedSmallInteger('cantidad_unidades')->nullable();
            $table->unsignedSmallInteger('cantidad_piezas')->nullable()->default(10)->change();
        });
    }

    public function down(): void
    {
        if (DB::table('productos')->where('unidad_tipo', '!=', 'pieza')->exists()
            || DB::table('bloques_promocion')->where('unidad_tipo', '!=', 'pieza')->orWhereNull('cantidad_piezas')->exists()) {
            throw new RuntimeException('No se puede retirar la unidad comercial mientras existan productos o bloques que la requieren.');
        }
        Schema::table('bloques_promocion', function (Blueprint $table) {
            $table->dropColumn(['unidad_tipo', 'cantidad_unidades']);
            $table->unsignedSmallInteger('cantidad_piezas')->nullable(false)->default(10)->change();
        });
        Schema::table('productos', fn (Blueprint $table) => $table->dropColumn('unidad_tipo'));
    }
};
