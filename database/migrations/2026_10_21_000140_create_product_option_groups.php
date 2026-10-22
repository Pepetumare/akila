<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupo_opcion_producto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('grupo_opcion_id')->constrained('grupos_opciones')->cascadeOnDelete();
            $table->unsignedSmallInteger('min_selections')->nullable();
            $table->unsignedSmallInteger('max_selections')->nullable();
            $table->boolean('obligatorio')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
            $table->unique(['producto_id', 'grupo_opcion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grupo_opcion_producto');
    }
};
