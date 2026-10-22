<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bloques_promocion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('nombre');
            $table->unsignedSmallInteger('cantidad_piezas')->default(10);
            $table->unsignedInteger('orden');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['producto_id', 'orden']);
        });
        Schema::create('bloque_grupo_opcion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bloque_promocion_id')->constrained('bloques_promocion')->cascadeOnDelete();
            $table->foreignId('grupo_opcion_id')->constrained('grupos_opciones')->cascadeOnDelete();
            $table->unsignedSmallInteger('min_selections')->nullable();
            $table->unsignedSmallInteger('max_selections')->nullable();
            $table->boolean('obligatorio')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
            $table->unique(['bloque_promocion_id', 'grupo_opcion_id'], 'bloque_grupo_pair_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bloque_grupo_opcion');
        Schema::dropIfExists('bloques_promocion');
    }
};
