<?php

use App\Commerce\Compatibility\SushiCatalogPolicyUpgrade;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', fn (Blueprint $table) => $table->json('catalog_policy')->nullable());
        Schema::table('grupos_opciones', fn (Blueprint $table) => $table->json('catalog_policy')->nullable());
        Schema::table('opciones', fn (Blueprint $table) => $table->boolean('disponible')->default(true));
        // One-time data conversion; never invoked during quoting or publication validation.
        app(SushiCatalogPolicyUpgrade::class)->run();
    }

    public function down(): void
    {
        // Removing policy data would silently restore ambiguous behavior. Use a forward migration.
        throw new LogicException('Catalog policies must be preserved; rollback requires an explicit reviewed forward migration.');
    }
};
