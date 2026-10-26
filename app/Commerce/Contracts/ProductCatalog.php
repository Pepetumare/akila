<?php

namespace App\Commerce\Contracts;

use App\Commerce\Catalog\ProductDefinition;
use App\Models\Producto;

/** Boundary to the current catalog; Producto remains the Commerce identity in this project. */
interface ProductCatalog
{
    public function definition(Producto $product): ProductDefinition;
}
