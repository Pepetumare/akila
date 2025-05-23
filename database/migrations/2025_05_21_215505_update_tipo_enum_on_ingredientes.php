<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateTipoEnumOnIngredientes extends Migration
{
    public function up()
    {
        // Primero renombramos la columna actual a temporal
        Schema::table('ingredientes', function (Blueprint $table) {
            $table->renameColumn('tipo', 'tipo_old');
        });

        // Creamos la nueva columna con el enum correcto
        Schema::table('ingredientes', function (Blueprint $table) {
            $table->enum('tipo', ['envoltura','proteina','vegetal'])
                  ->default('vegetal')
                  ->after('nombre');
        });

        // Pasamos valores de la vieja a la nueva (puedes personalizar la lógica)
        DB::table('ingredientes')->where('tipo_old', 'extra')->update(['tipo' => 'vegetal']);
        DB::table('ingredientes')->where('tipo_old', 'base')->update(['tipo' => 'proteina']);

        // Eliminamos la columna temporal
        Schema::table('ingredientes', function (Blueprint $table) {
            $table->dropColumn('tipo_old');
        });
    }

    public function down()
    {
        // Revertir a enum('base','extra')
        Schema::table('ingredientes', function (Blueprint $table) {
            $table->renameColumn('tipo', 'tipo_new');
        });

        Schema::table('ingredientes', function (Blueprint $table) {
            $table->enum('tipo', ['base','extra'])->default('extra')->after('nombre');
        });

        DB::table('ingredientes')->where('tipo_new', 'vegetal')->update(['tipo' => 'extra']);
        DB::table('ingredientes')->where('tipo_new', 'proteina')->update(['tipo' => 'base']);
        DB::table('ingredientes')->where('tipo_new', 'envoltura')->update(['tipo' => 'base']);

        Schema::table('ingredientes', function (Blueprint $table) {
            $table->dropColumn('tipo_new');
        });
    }
}
