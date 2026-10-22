<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $pairs = [
        'ingrediente_producto' => ['producto_id', 'ingrediente_id'],
        'producto_swappable_ingredient' => ['producto_id', 'ingrediente_id'],
        'promocion_handroll' => ['promocion_id', 'producto_id'],
    ];

    public function up(): void
    {
        foreach ($this->pairs as $table => $columns) {
            if (DB::table($table)->select($columns)->groupBy($columns)->havingRaw('COUNT(*) > 1')->exists()) {
                throw new RuntimeException("Existen pares duplicados en {$table}; revisarlos sin eliminar datos automáticamente.");
            }
        }
        Schema::table('ingrediente_producto', function (Blueprint $table) {
            $table->decimal('cantidad', 12, 3)->nullable();
            $table->string('unidad', 20)->nullable();
            $table->boolean('obligatorio')->default(true);
            $table->boolean('reemplazable')->default(false);
            $table->unsignedInteger('orden')->default(0);
            $table->json('metadata')->nullable();
        });
        foreach ($this->pairs as $table => $columns) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->unique($columns, $table.'_pair_unique'));
        }
    }

    public function down(): void
    {
        foreach ($this->pairs as $table => $columns) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropUnique($table.'_pair_unique'));
        }
        Schema::table('ingrediente_producto', fn (Blueprint $table) => $table->dropColumn([
            'cantidad', 'unidad', 'obligatorio', 'reemplazable', 'orden', 'metadata',
        ]));
    }
};
