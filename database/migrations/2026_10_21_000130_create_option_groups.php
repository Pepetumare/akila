<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupos_opciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->string('tipo_seleccion', 20)->default('single');
            $table->unsignedSmallInteger('min_selections')->default(0);
            $table->unsignedSmallInteger('max_selections')->default(1);
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });
        Schema::create('opciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_opcion_id')->constrained('grupos_opciones')->cascadeOnDelete();
            $table->foreignId('ingrediente_id')->nullable()->constrained('ingredientes')->restrictOnDelete();
            $table->string('nombre');
            $table->string('slug');
            $table->decimal('recargo', 10, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
            $table->unique(['grupo_opcion_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opciones');
        Schema::dropIfExists('grupos_opciones');
    }
};
