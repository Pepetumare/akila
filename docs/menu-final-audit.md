# Auditoría final integral del catálogo Sushi Akila

Fecha: 22 de octubre de 2026. Base local MariaDB, árbol de trabajo sin commit. Las fuentes comerciales son los cuatro documentos previos y Batch01Data–Batch05Data, con precedencia de lote/ficha específica. No se inspeccionaron imágenes originales nuevas ni una base productiva remota.

## Dictamen y límites

El catálogo coincide con las decisiones documentadas: 37 productos, 35 disponibles y 2 Gohan inactivos, 7 categorías y 23 ingredientes. No hay discrepancias de precio, cantidad, categoría o estado respecto a los lotes; tampoco duplicados ni huérfanos en las comprobaciones detalladas abajo. Los 35 activos cotizan y generan snapshot v2 con una configuración válida; los dos inactivos son rechazados.

A) Listo en el contrato comercial modelado: compra de 35 productos activos, configuraciones explícitas, precios de servidor, carrito y snapshots; delivery local de San José. No equivale a una certificación operativa de cocina ni a un despliegue de producción.

B) Correcciones técnicas aplicadas y probadas: T01 (N+1 de administración) y T02 (500 en retiro con campos de delivery residuales). Permanecen mejoras/deuda de administración y rendimiento detalladas en hallazgos; no se cambian reglas ambiguas.

C) Se requiere confirmación humana para los dos Gohan, envolturas Nikkei y demás pendientes consolidados. La matriz identifica seis productos con pendiente específico (cuatro activos y dos inactivos), no seis errores técnicos. Los 31 restantes están OK respecto del contrato cargado; sus notas y los pendientes comunes siguen siendo aplicables.

## Alcance y metodología

Lectura completa de docs/database-audit-sushi-akila.md, menu-database-architecture.md, menu-import-batches.md y menu-commercial-integration.md antes de revisar código. La auditoría antigua refleja etapas anteriores; sus incidencias se contrastan con el esquema/código actual, no se repiten como vigentes automáticamente. Revisión de los cinco BatchData, seeders principal/de lote, importador/identidades, modelos, servicios comerciales, controladores, rutas, frontend, componentes de pedido/PDF y pruebas.

Inspección real mediante consultas SELECT/SHOW en transacción READ ONLY; ningún pedido persistente creado. Una consulta amplia de information_schema perdió conexión (2006; reintento inmediato 2002); la comprobación fuera del sandbox y la inspección acotada con SHOW CREATE TABLE terminaron correctamente. Se registra como incidencia transitoria de conexión/metadatos, sin atribuir sin evidencia una caída permanente al motor ni un bug comercial.

Los únicos cambios de datos autorizados son dos ejecuciones del seeder principal para verificar idempotencia; pruebas de checkout en SQLite en memoria con pago simulado. Chrome utiliza contexto de navegador aislado y solo carrito de sesión. Las pruebas reconstruyen únicamente SQLite :memory: con su setup existente. No se ejecutaron migraciones ni comandos destructivos sobre MariaDB; no hubo pagos reales, cambios de .env ni commit.

## Conteos e idempotencia

| Tabla | Antes | Primera | Segunda | Valores/IDs sin timestamps |
|---|---|---|---|---|
| categorias | 7 | 7 | 7 | ESTABLE |
| productos | 37 | 37 | 37 | ESTABLE |
| ingredientes | 23 | 23 | 23 | ESTABLE |
| promociones | 26 | 26 | 26 | ESTABLE |
| bloques_promocion | 130 | 130 | 130 | ESTABLE |
| grupos_opciones | 29 | 29 | 29 | ESTABLE |
| opciones | 115 | 115 | 115 | ESTABLE |
| reglas_promocion | 479 | 479 | 479 | ESTABLE |
| ingrediente_producto | 166 | 166 | 166 | ESTABLE |
| grupo_opcion_producto | 29 | 29 | 29 | ESTABLE |
| bloque_grupo_opcion | 187 | 187 | 187 | ESTABLE |
| producto_swappable_ingredient | 0 | 0 | 0 | ESTABLE |
| promocion_handroll | 0 | 0 | 0 | ESTABLE |

Dos ejecuciones reales de php artisan db:seed --class=SushiAkilaMenuSeeder: código 0, Batch01→02→03→04→05. Igualdad completa por tabla: **Sí**; usuarios, pedidos y líneas históricas sin cambio: **Sí** (huellas completas, sin imprimir datos personales). La inspección adicional de cierre tras Chrome conserva catálogo e historia: **Sí**.

| Tabla | SHA-256 comercial antes = primera = segunda |
|---|---|
| categorias | 11a3f438bbbd6daa9250d77b6d83e65841a0ada17116b5db0910775514c2c78b |
| productos | 72fdf98e0f7b9e3101b7e4ba09cb3128396fdf39eb7d08293492d95c90f391aa |
| ingredientes | a8484018fe311a751f358aa8a45a1fee872f9ea4ca6679c1eef9f1282e06e330 |
| promociones | 9606adbd66c6708c92cc2016d37da0075e579317a00102cd8b36f5fdd40ab1b9 |
| bloques_promocion | d8dd0d7e2d10c3f66c27866c61cafe94f3f5c888318edbbe524c9bc02da90891 |
| grupos_opciones | c289ae37bafefc6e32c9e0231a9f5f9c07cf2db229d7b19e8e953d6ad227fa29 |
| opciones | 72fa889ca0e95b9b79607c5d7e64d45ffe9a89b9dc37fd39977311430a06472e |
| reglas_promocion | c3372619d759bae0c46c88e98e4d6fa3dfa23d1593cdee9ef0ef4be1fd78ffe3 |
| ingrediente_producto | 039140394dda4cbf360fedd801340217831a5c0918ec99e01692ac41b1612af0 |
| grupo_opcion_producto | f56fe27741958072c6582aa304cf1e1c166fa54adcc2b7a4cd95ad7751d8a4f9 |
| bloque_grupo_opcion | 35cc2f34007d96008ba39efbe7824c01bfcd034bebff4de889bcc8a70aaa33b6 |
| producto_swappable_ingredient | 4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945 |
| promocion_handroll | 4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945 |

## Categorías

| ID | Nombre | Slug | Orden | Activa | Total | Públicos |
|---|---|---|---|---|---|---|
| 1 | Promociones | promociones | 1 | Sí | 17 | 17 |
| 2 | Promociones a elección | promociones-a-eleccion | 2 | Sí | 5 | 5 |
| 4 | Hand Rolls | hand-rolls | 3 | Sí | 6 | 6 |
| 3 | Especialidades | especialidades | 4 | Sí | 2 | 2 |
| 5 | Nikkei | nikkei | 5 | Sí | 4 | 4 |
| 6 | Acompañamientos | acompanamientos | 6 | Sí | 1 | 1 |
| 7 | Gohan | gohan | 7 | Sí | 2 | 0 |

## Inventario final de los 37 productos

Precios CLP DECIMAL. P = grupo asignado al producto; B = grupo asignado a sus bloques; G = ID del catálogo de grupos de la sección siguiente, donde constan todas las opciones y sus recargos. R = conjunto de reglas del producto en el anexo de reglas. Las cantidades de ingredientes indican cortes comerciales solo cuando constan en metadata, nunca gramos.

| ID | Nombre | Slug | Categoría | Precio | Activo | Unidad | Cantidad | Configuración | Bloques | Grupos/límites | Reglas | Ingredientes fijos | Opciones | Recargos | Salsas incluidas | Observaciones |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| 1 | Goloso | goloso | Promociones | 10.000,00 | Sí | pieza | 20 | Fijo | 2 | P G28 [0..2]; B G6 ×2 [1..1]; B G7 ×2 [1..1]; B G14 ×2 [1..1] | R1: 18 activas/0 inactivas | Pollo [20 cortes relleno]; Palta [20 cortes relleno]; Queso crema; Cebollín | Véase G y R del producto | Soya +500; agridulce +800; cambios solo condicionales | 1 Salsa de soya ($0); 1 Salsa agridulce ($0) | Conforme a la configuración documentada. |
| 2 | Proteínas | proteinas | Promociones | 14.990,00 | Sí | pieza | 30 | Fijo | 3 | P G28 [0..2]; B G6 ×3 [1..1]; B G14 ×3 [1..1] | R2: 18 activas/0 inactivas | Pollo [30 cortes relleno]; Queso crema; Cebollín | Véase G y R del producto | Soya +500; agridulce +800; cambios solo condicionales | 2 Salsa de soya ($0); 1 Salsa agridulce ($0) | Conforme a la configuración documentada. |
| 3 | Noche de Sushi | noche-de-sushi | Promociones | 17.990,00 | Sí | pieza | 40 | Fijo | 4 | P G28 [0..2]; B G6 ×2 [1..1]; B G8 [1..1]; B G7 [1..1] | R3: 18 activas/1 inactivas | Pollo [20 cortes relleno]; Camarón [10 cortes relleno]; Palta [10 cortes relleno]; Queso crema; Cebollín | Véase G y R del producto | Soya +500; agridulce +800; cambios solo condicionales | 2 Salsa de soya ($0); 1 Salsa agridulce ($0) | Preparación por bloque sin confirmar; regla anterior de 20 envueltas conservada inactiva por prioridad Foto15. |
| 4 | Mi Favorita | mi-favorita | Promociones | 18.990,00 | Sí | pieza | 50 | Fijo | 5 | P G28 [0..2]; B G6 ×2 [1..1]; B G8 [1..1]; B G9 [1..1]; B G10 [1..1] | R4: 19 activas/0 inactivas | Pollo [20 cortes relleno]; Kanikama [10 cortes relleno]; Camarón [10 cortes relleno]; Choclo [10 cortes relleno]; Queso crema; Cebollín | Véase G y R del producto | Soya +500; agridulce +800; cambios solo condicionales | 2 Salsa de soya ($0); 1 Salsa agridulce ($0) | 50 piezas y 30 envueltas/20 fritas confirmadas; envolturas declaradas sin reparto por bloque. |
| 5 | Promo Hot | promo-hot | Promociones | 19.990,00 | Sí | pieza | 60 | Fijo | 6 | P G28 [0..2]; B G6 ×2 [1..1]; B G14 ×6 [1..1]; B G8 [1..1]; B G9 [1..1]; B G10 [1..1]; B G11 [1..1] | R5: 28 activas/0 inactivas | Pollo [20 cortes relleno]; Kanikama [10 cortes relleno]; Camarón [10 cortes relleno]; Choclo [10 cortes relleno]; Champiñón [10 cortes relleno]; Queso crema; Cebollín | Véase G y R del producto | Soya +500; agridulce +800; cambios solo condicionales | 2 Salsa de soya ($0); 2 Salsa agridulce ($0) | Precio definitivo Foto11: 19990; no el resumen antiguo 20990. |
| 6 | Lo Nuevo | lo-nuevo | Promociones | 25.990,00 | Sí | pieza | 70 | Fijo | 7 | P G28 [0..2]; B G6 ×2 [1..1]; B G8 [1..1]; B G9 ×2 [1..1]; B G10 [1..1]; B G11 [1..1] | R6: 29 activas/0 inactivas | Pollo [20 cortes relleno]; Kanikama [20 cortes relleno]; Camarón [10 cortes relleno]; Choclo [10 cortes relleno]; Champiñón [10 cortes relleno]; Queso crema; Cebollín | Véase G y R del producto | Soya +500; agridulce +800; cambios solo condicionales | 2 Salsa de soya ($0); 2 Salsa agridulce ($0) | 30 frías/40 fritas globales, sin reparto por bloque. |
| 7 | 30 Piezas a Elección | 30-piezas-a-eleccion | Promociones a elección | 14.900,00 | Sí | pieza | 30 | Personalizable | 3 | P G28 [0..2]; B G4 ×3 [1..1]; B G3 ×3 [0..1] | R7: 23 activas/0 inactivas | Queso crema; Cebollín | Véase G y R del producto | Soya +500; agridulce +800; cambios solo condicionales | 2 Salsa de soya ($0); 1 Salsa agridulce ($0) | Conforme a la configuración documentada. |
| 8 | Costanera | costanera | Promociones | 14.900,00 | Sí | pieza | 30 | Fijo | 3 | P G28 [0..2]; B G6 [1..1]; B G14 [1..1]; B G8 [1..1]; B G15 [1..1]; B G7 [1..1]; B G16 [1..1] | R8: 19 activas/0 inactivas | Pollo [10 cortes relleno]; Camarón [10 cortes relleno]; Palta [10 cortes relleno]; Queso crema; Cebollín | Véase G y R del producto | Soya +500; agridulce +800; cambios solo condicionales | 2 Salsa de soya ($0); 1 Salsa agridulce ($0) | Conforme a la configuración documentada. |
| 9 | Monumento | monumento | Promociones | 14.900,00 | Sí | pieza | 30 | Fijo | 3 | P G28 [0..2]; B G6 [1..1]; B G14 [1..1]; B G11 [1..1]; B G15 [1..1]; B G7 [1..1]; B G16 [1..1] | R9: 19 activas/0 inactivas | Pollo [10 cortes relleno]; Palta [10 cortes relleno]; Champiñón [10 cortes relleno]; Queso crema; Cebollín | Véase G y R del producto | Soya +500; agridulce +800; cambios solo condicionales | 2 Salsa de soya ($0); 1 Salsa agridulce ($0) | Conforme a la configuración documentada. |
| 10 | Las Ruinas | las-ruinas | Promociones | 14.900,00 | Sí | pieza | 30 | Fijo | 3 | P G28 [0..2]; B G6 [1..1]; B G14 [1..1]; B G9 [1..1]; B G15 [1..1]; B G7 [1..1]; B G16 [1..1] | R10: 19 activas/0 inactivas | Pollo [10 cortes relleno]; Kanikama [10 cortes relleno]; Palta [10 cortes relleno]; Queso crema; Cebollín | Véase G y R del producto | Soya +500; agridulce +800; cambios solo condicionales | 2 Salsa de soya ($0); 1 Salsa agridulce ($0) | Conforme a la configuración documentada. |
| 11 | La Playa | la-playa | Promociones | 14.900,00 | Sí | pieza | 30 | Fijo | 3 | P G28 [0..2]; B G8 [1..1]; B G14 [1..1]; B G9 [1..1]; B G16 [1..1]; B G12 [1..1]; B G15 [1..1] | R11: 19 activas/0 inactivas | Kanikama [10 cortes relleno]; Salmón [10 cortes relleno]; Camarón [10 cortes relleno]; Palta; Queso crema; Cebollín | Véase G y R del producto | Soya +500; agridulce +800; cambios solo condicionales | 2 Salsa de soya ($0); 1 Salsa agridulce ($0) | Conforme a la configuración documentada. |
| 12 | Código Verde | codigo-verde | Promociones | 14.900,00 | Sí | pieza | 30 | Fijo | 3 | P G28 [0..2]; B G11 [1..1]; B G14 [1..1]; B G10 [1..1]; B G16 [1..1]; B G13 [1..1]; B G15 [1..1] | R12: 19 activas/0 inactivas | Choclo [10 cortes relleno]; Palmito [10 cortes relleno]; Palta; Champiñón [10 cortes relleno]; Queso crema; Cebollín | Véase G y R del producto | Soya +500; agridulce +800; cambios solo condicionales | 2 Salsa de soya ($0); 1 Salsa agridulce ($0) | Conforme a la configuración documentada. |
| 13 | El Ciclón | el-ciclon | Promociones | 24.990,00 | Sí | pieza | 60 | Fijo | 6 | P G28 [0..2]; B G6 [1..1]; B G8 [1..1]; B G9 [1..1]; B G10 [1..1]; B G11 [1..1]; B G13 [1..1] | R13: 18 activas/0 inactivas | Pollo [10 cortes relleno]; Kanikama [10 cortes relleno]; Camarón [10 cortes relleno]; Choclo [10 cortes relleno]; Palmito [10 cortes relleno]; Champiñón [10 cortes relleno]; Queso crema; Cebollín | Véase G y R del producto | Soya +500; agridulce +800; cambios solo condicionales | 2 Salsa de soya ($0); 2 Salsa agridulce ($0) | 30 fritas confirmadas; otras preparaciones y reparto pendientes de fuente. |
| 14 | 60 Piezas a Elección | 60-piezas-a-eleccion | Promociones a elección | 25.990,00 | Sí | pieza | 60 | Personalizable | 6 | P G28 [0..2]; B G17 ×6 [1..1]; B G18 ×6 [0..1] | R14: 22 activas/0 inactivas | Queso crema; Cebollín | Véase G y R del producto | Soya +500; agridulce +800; cambios solo condicionales | 2 Salsa de soya ($0); 2 Salsa agridulce ($0) | 30 fritas globales, sin asignación a bloques. |
| 15 | 50 Piezas a Elección | 50-piezas-a-eleccion | Promociones a elección | 20.990,00 | Sí | pieza | 50 | Personalizable | 5 | P G28 [0..2]; B G17 ×5 [1..1]; B G18 ×5 [0..1] | R15: 22 activas/0 inactivas | Queso crema; Cebollín | Véase G y R del producto | Soya +500; agridulce +800; cambios solo condicionales | 2 Salsa de soya ($0); 1 Salsa agridulce ($0) | 20 fritas globales, sin asignación a bloques; Foto30 confirma mismo ID. |
| 16 | Tu Media Naranja | tu-media-naranja | Promociones | 17.990,00 | Sí | pieza | 40 | Fijo | 4 | P G28 [0..2]; B G6 [1..1]; B G14 ×2 [1..1]; B G8 [1..1]; B G15 [1..1]; B G7 [1..1]; B G16 [1..1]; B G9 [1..1] | R16: 19 activas/0 inactivas | Pollo [10 cortes relleno]; Kanikama [10 cortes relleno]; Camarón [10 cortes relleno]; Palta [10 cortes relleno]; Queso crema; Cebollín | Véase G y R del producto | Soya +500; agridulce +800; cambios solo condicionales | 2 Salsa de soya ($0); 1 Salsa agridulce ($0) | Conforme a la configuración documentada. |
| 17 | 40 Piezas a Elección | 40-piezas-a-eleccion | Promociones a elección | 17.900,00 | Sí | pieza | 40 | Personalizable | 4 | P G28 [0..2]; B G17 ×4 [1..1] | R17: 19 activas/0 inactivas | Queso crema; Cebollín | Véase G y R del producto | Soya +500; agridulce +800; cambios solo condicionales | 2 Salsa de soya ($0); 1 Salsa agridulce ($0) | 20 fritas globales; ficha no confirma opciones de envoltura. |
| 18 | El Emperador | el-emperador | Promociones | 49.990,00 | Sí | pieza | 150 | Fijo | 15 | P G28 [0..2]; B G6 ×3 [1..1]; B G8 ×2 [1..1]; B G9 ×2 [1..1]; B G10 ×2 [1..1]; B G11 ×2 [1..1]; B G12 [1..1]; B G13 [1..1]; B G7 ×2 [1..1] | R18: 17 activas/0 inactivas | Pollo [30 cortes relleno]; Kanikama [20 cortes relleno]; Salmón [10 cortes relleno]; Camarón [20 cortes relleno]; Choclo [20 cortes relleno]; Palmito [10 cortes relleno]; Palta [20 cortes relleno]; Champiñón [20 cortes relleno]; Queso crema; Cebollín | Véase G y R del producto | Soya +500; agridulce +800; cambios solo condicionales | Sin regla | 150 piezas: salsas incluidas pendientes. No hay regla inventada; adicionales sí comprables. |
| 19 | Para la Familia | para-la-familia | Promociones | 36.990,00 | Sí | pieza | 100 | Fijo | 10 | P G28 [0..2]; B G6 ×2 [1..1]; B G8 [1..1]; B G9 ×2 [1..1]; B G10 [1..1]; B G11 [1..1]; B G12 [1..1]; B G13 [1..1]; B G7 [1..1] | R19: 19 activas/0 inactivas | Pollo [20 cortes relleno]; Kanikama [20 cortes relleno]; Salmón [10 cortes relleno]; Camarón [10 cortes relleno]; Choclo [10 cortes relleno]; Palmito [10 cortes relleno]; Palta [10 cortes relleno]; Champiñón [10 cortes relleno]; Queso crema; Cebollín | Véase G y R del producto | Soya +500; agridulce +800; cambios solo condicionales | 5 Salsa de soya ($0); 3 Salsa agridulce ($0) | 40 frías/60 fritas globales; reparto por bloque no declarado. |
| 20 | De las que no Sobran | de-las-que-no-sobran | Promociones | 31.990,00 | Sí | pieza | 90 | Fijo | 9 | P G28 [0..2]; B G6 ×2 [1..1]; B G8 [1..1]; B G9 ×2 [1..1]; B G10 [1..1]; B G11 [1..1]; B G12 [1..1]; B G13 [1..1] | R20: 19 activas/0 inactivas | Pollo [20 cortes relleno]; Kanikama [20 cortes relleno]; Salmón [10 cortes relleno]; Camarón [10 cortes relleno]; Choclo [10 cortes relleno]; Palmito [10 cortes relleno]; Champiñón [10 cortes relleno]; Queso crema; Cebollín | Véase G y R del producto | Soya +500; agridulce +800; cambios solo condicionales | 4 Salsa de soya ($0); 3 Salsa agridulce ($0) | 40 frías/50 fritas globales; reparto por bloque no declarado. |
| 21 | Las Mismas de Siempre | las-mismas-de-siempre | Promociones | 27.900,00 | Sí | pieza | 80 | Fijo | 8 | P G28 [0..2]; B G6 ×2 [1..1]; B G8 [1..1]; B G9 [1..1]; B G10 [1..1]; B G11 [1..1]; B G12 [1..1]; B G13 [1..1] | R21: 19 activas/0 inactivas | Pollo [20 cortes relleno]; Kanikama [10 cortes relleno]; Salmón [10 cortes relleno]; Camarón [10 cortes relleno]; Choclo [10 cortes relleno]; Palmito [10 cortes relleno]; Champiñón [10 cortes relleno]; Queso crema; Cebollín | Véase G y R del producto | Soya +500; agridulce +800; cambios solo condicionales | 4 Salsa de soya ($0); 3 Salsa agridulce ($0) | 40 frías/40 fritas globales; reparto por bloque no declarado. |
| 22 | Muchupleto | muchupleto | Especialidades | 6.500,00 | Sí | unidad | 1 | Personalizable | 0 | P G20 [1..1] | R22: 0 activas/0 inactivas | Pollo; Palta; Queso crema; Cebollín | Véase G y R del producto | Sin recargo de composición fija | Sin regla | Base fija completa incluye Pollo; elección adicional O, sin recargo premium. |
| 23 | Hand Roll Tradicional | hand-roll-tradicional | Hand Rolls | 3.990,00 | Sí | hand_roll | 1 | Personalizable | 0 | P G19 [1..1] | R23: 2 activas/0 inactivas | Queso crema; Cebollín | Véase G y R del producto | Salmón/Atún +1000 | Sin regla | Salmón/Atún +1000 solo aquí. Carne no cargada; pendiente de confirmar. |
| 24 | Hand Roll Relleno Doble | hand-roll-relleno-doble | Hand Rolls | 4.990,00 | Sí | hand_roll | 1 | Personalizable | 0 | P G1 [1..1]; P G2 [1..1] | R24: 0 activas/0 inactivas | Queso crema; Cebollín | Véase G y R del producto | Sin recargo de composición fija | Sin regla | Conforme a la configuración documentada. |
| 25 | 3 Hand Rolls | 3-hand-rolls | Hand Rolls | 11.000,00 | Sí | hand_roll | 3 | Personalizable | 3 | B G19 ×3 [1..1] | R25: 11 activas/0 inactivas | Queso crema; Cebollín | Véase G y R del producto | Sin recargo de composición fija | Sin regla | Conforme a la configuración documentada. |
| 26 | 4 Hand Rolls | 4-hand-rolls | Hand Rolls | 14.990,00 | Sí | hand_roll | 4 | Personalizable | 4 | B G19 ×4 [1..1] | R26: 11 activas/0 inactivas | Queso crema; Cebollín | Véase G y R del producto | Sin recargo de composición fija | Sin regla | Conforme a la configuración documentada. |
| 27 | 5 Hand Rolls | 5-hand-rolls | Hand Rolls | 18.000,00 | Sí | hand_roll | 5 | Personalizable | 5 | B G19 ×5 [1..1]; B G21 ×5 [0..1] | R27: 11 activas/0 inactivas | Queso crema; Cebollín | Véase G y R del producto | Segundo ingrediente +1000/unidad | Sin regla | Segundo ingrediente opcional +1000 por unidad; distinto del primero y sujeto a repeticiones. |
| 28 | 4 Hand Rolls Pollo Palta | 4-hand-rolls-pollo-palta | Hand Rolls | 18.000,00 | Sí | hand_roll | 4 | Fijo | 4 | B G6 ×4 [1..1]; B G7 ×4 [1..1] | R28: 0 activas/0 inactivas | Pollo; Palta; Queso crema; Cebollín | Véase G y R del producto | Sin recargo de composición fija | Sin regla | Conforme a la configuración documentada. |
| 29 | Nikkei Jamón Serrano | nikkei-jamon-serrano | Nikkei | 6.990,00 | Sí | pieza | 10 | Fijo | 0 | — | R29: 0 activas/0 inactivas | Palta; Queso crema; Pollo apanado; Jamón serrano | Véase G y R del producto | Sin recargo de composición fija | Sin regla | Conforme a la configuración documentada. |
| 30 | Nikkei Pollo Crispy | nikkei-pollo-crispy | Nikkei | 6.990,00 | Sí | pieza | 10 | Fijo | 0 | — | R30: 0 activas/0 inactivas | Palta; Queso crema; Pollo apanado | Véase G y R del producto | Sin recargo de composición fija | Sin regla | Envoltura a elección declarada, sin lista específica confirmada; se vende con composición fija, sin inventar envoltura. |
| 31 | Nikkei Camarón Apanado | nikkei-camaron-apanado | Nikkei | 6.990,00 | Sí | pieza | 10 | Fijo | 0 | P G25 [1..1] | R31: 0 activas/0 inactivas | Palta; Queso crema; Camarón apanado; Almendras | Véase G y R del producto | Sin recargo de composición fija | Sin regla | Almendras singleton automática a $0; no se extrapola a otros Nikkei. |
| 32 | Nikkei Salmón Ahumado | nikkei-salmon-ahumado | Nikkei | 6.990,00 | Sí | pieza | 10 | Fijo | 0 | — | R32: 0 activas/0 inactivas | Palta; Queso crema; Salmón ahumado | Véase G y R del producto | Sin recargo de composición fija | Sin regla | Envoltura a elección declarada, sin lista específica confirmada; salmón ahumado conserva identidad distinta. |
| 33 | 20 Piezas a Elección | 20-piezas-a-eleccion | Promociones a elección | 10.000,00 | Sí | pieza | 20 | Personalizable | 2 | P G28 [0..2]; B G22 ×2 [1..1]; B G23 ×2 [0..1] | R33: 21 activas/0 inactivas | Queso crema; Cebollín | Véase G y R del producto | Soya +500; agridulce +800; cambios solo condicionales | 1 Salsa de soya ($0); 1 Salsa agridulce ($0) | Conforme a la configuración documentada. |
| 34 | AkilaBurger | akilaburger | Especialidades | 7.500,00 | Sí | unidad | 1 | Personalizable | 0 | P G26 [1..1] | R34: 0 activas/0 inactivas | Palta; Queso crema; Cebollín | Véase G y R del producto | Sin recargo de composición fija | Sin regla | Base fija; elección adicional O, Salmón/Atún al mismo precio. |
| 35 | Arrollados | arrollados | Acompañamientos | 5.000,00 | Sí | unidad | 8 | Personalizable | 0 | P G29 [1..2] | R35: 0 activas/0 inactivas | Sin receta física declarada | Véase G y R del producto | Sin recargo de composición fija | Sin regla | Mixto permitido a $5000; proporción operativa/manual, sin cantidades por sabor. |
| 36 | Gohan Flecha | gohan-flecha | Gohan | 7.500,00 | No | unidad | 1 | Fijo | 0 | — | R36: 0 activas/0 inactivas | Palta; Queso crema; Sésamo; Ciboulette; Arroz | Véase G y R del producto | Sin recargo de composición fija | Sin regla | Composición diferencial y definición vegetariana pendientes; mantener inactivo. |
| 37 | Gohan Orion | gohan-orion | Gohan | 7.500,00 | No | unidad | 1 | Fijo | 0 | — | R37: 0 activas/0 inactivas | Palta; Queso crema; Sésamo; Ciboulette; Arroz | Véase G y R del producto | Sin recargo de composición fija | Sin regla | Composición diferencial y definición vegetariana pendientes; mantener inactivo. |

## Catálogo de grupos y opciones

Una misma opción/ingrediente puede pertenecer a grupos comerciales distintos sin constituir un ingrediente duplicado. La asignación por producto/bloque, no la mera existencia global, determina disponibilidad. El grupo estructural pendiente vacío no está asignado: no se confunde con una configuración imposible vendible.

| Grupo | Slug / nombre | Tipo | Límite global | Opciones: ID, nombre, ingrediente, recargo | Asignación |
|---|---|---|---|---|---|
| G1 | proteina / Proteína | single | 1..1 | O1 Pollo → Pollo +0,00; O2 Kanikama → Kanikama +0,00; O3 Salmón → Salmón +0,00; O4 Atún → Atún +0,00; O5 Camarón → Camarón +0,00 | 24 |
| G2 | vegetal / Vegetal | single | 1..1 | O6 Choclo → Choclo +0,00; O7 Palmito → Palmito +0,00; O8 Palta → Palta +0,00; O9 Champiñón → Champiñón +0,00; O10 Morrón → Morrón +0,00; O59 Pepino → Pepino +0,00 | 24 |
| G3 | envoltura / Envoltura | single | 1..1 | O11 Queso crema → Queso crema +0,00; O12 Palta → Palta +0,00; O13 Sésamo → Sésamo +0,00; O14 Ciboulette → Ciboulette +0,00; O15 Frito → comercial sin ingrediente +0,00 | 7 |
| G4 | relleno-30-piezas-a-eleccion / Relleno — 30 Piezas a Elección | single | 1..1 | O16 Pollo → Pollo +0,00; O17 Camarón → Camarón +0,00; O18 Kanikama → Kanikama +0,00; O19 Salmón → Salmón +0,00; O20 Palmito → Palmito +0,00; O21 Champiñón → Champiñón +0,00; O22 Choclo → Choclo +0,00; O23 Palta → Palta +0,00 | 7 |
| G5 | preparacion / Preparación | single | 1..1 | O24 Envuelto → comercial sin ingrediente +0,00; O25 Frío → comercial sin ingrediente +0,00 | Sin asignación efectiva |
| G6 | relleno-fijo-pollo / Relleno fijo: Pollo | single | 1..1 | O26 Pollo → Pollo +0,00 | 1, 2, 3, 4, 5, 6, 8, 9, 10, 13, 16, 18, 19, 20, 21, 28 |
| G7 | relleno-fijo-palta / Relleno fijo: Palta | single | 1..1 | O27 Palta → Palta +0,00 | 1, 3, 8, 9, 10, 16, 18, 19, 28 |
| G8 | relleno-fijo-camaron / Relleno fijo: Camarón | single | 1..1 | O28 Camarón → Camarón +0,00 | 3, 4, 5, 6, 8, 11, 13, 16, 18, 19, 20, 21 |
| G9 | relleno-fijo-kanikama / Relleno fijo: Kanikama | single | 1..1 | O29 Kanikama → Kanikama +0,00 | 4, 5, 6, 10, 11, 13, 16, 18, 19, 20, 21 |
| G10 | relleno-fijo-choclo / Relleno fijo: Choclo | single | 1..1 | O30 Choclo → Choclo +0,00 | 4, 5, 6, 12, 13, 18, 19, 20, 21 |
| G11 | relleno-fijo-champinon / Relleno fijo: Champiñón | single | 1..1 | O31 Champiñón → Champiñón +0,00 | 5, 6, 9, 12, 13, 18, 19, 20, 21 |
| G12 | relleno-fijo-salmon / Relleno fijo: Salmón | single | 1..1 | O32 Salmón → Salmón +0,00 | 11, 18, 19, 20, 21 |
| G13 | relleno-fijo-palmito / Relleno fijo: Palmito | single | 1..1 | O33 Palmito → Palmito +0,00 | 12, 13, 18, 19, 20, 21 |
| G14 | envoltura-fija-frito / Envoltura fija: Frito | single | 1..1 | O34 Frito → comercial sin ingrediente +0,00 | 1, 2, 5, 8, 9, 10, 11, 12, 16 |
| G15 | envoltura-fija-palta / Envoltura fija: Palta | single | 1..1 | O35 Palta → Palta +0,00 | 8, 9, 10, 11, 12, 16 |
| G16 | envoltura-fija-queso-crema / Envoltura fija: Queso crema | single | 1..1 | O36 Queso crema → Queso crema +0,00 | 8, 9, 10, 11, 12, 16 |
| G17 | relleno-promociones-a-eleccion / Relleno | single | 1..1 | O37 Pollo → Pollo +0,00; O38 Kanikama → Kanikama +0,00; O39 Camarón → Camarón +0,00; O40 Salmón → Salmón +0,00; O41 Choclo → Choclo +0,00; O42 Palmito → Palmito +0,00; O43 Champiñón → Champiñón +0,00; O44 Palta → Palta +0,00 | 14, 15, 17 |
| G18 | envoltura-promociones-mixtas / Envoltura | single | 1..1 | O45 Queso crema → Queso crema +0,00; O46 Palta → Palta +0,00; O47 Sésamo → Sésamo +0,00 | 14, 15 |
| G19 | relleno-hand-roll / Relleno | single | 1..1 | O48 Pollo → Pollo +0,00; O49 Kanikama → Kanikama +0,00; O50 Camarón → Camarón +0,00; O51 Salmón → Salmón +0,00; O52 Atún → Atún +0,00; O53 Palta → Palta +0,00; O54 Champiñón → Champiñón +0,00; O55 Palmito → Palmito +0,00; O56 Choclo → Choclo +0,00; O57 Pepino → Pepino +0,00; O58 Morrón → Morrón +0,00 | 23, 25, 26, 27 |
| G20 | relleno-muchupleto / Elección adicional | single | 1..1 | O60 Pollo → Pollo +0,00; O61 Kanikama → Kanikama +0,00; O62 Camarón → Camarón +0,00; O63 Salmón → Salmón +0,00; O64 Atún → Atún +0,00; O65 Choclo → Choclo +0,00; O66 Palmito → Palmito +0,00; O67 Palta → Palta +0,00; O68 Champiñón → Champiñón +0,00 | 22 |
| G21 | segundo-ingrediente / Segundo ingrediente | single | 1..1 | O69 Pollo → Pollo +1.000,00; O70 Kanikama → Kanikama +1.000,00; O71 Camarón → Camarón +1.000,00; O72 Salmón → Salmón +1.000,00; O73 Atún → Atún +1.000,00; O74 Palta → Palta +1.000,00; O75 Champiñón → Champiñón +1.000,00; O76 Palmito → Palmito +1.000,00; O77 Choclo → Choclo +1.000,00; O78 Pepino → Pepino +1.000,00; O79 Morrón → Morrón +1.000,00 | 27 |
| G22 | relleno-20-piezas-a-eleccion / Relleno | single | 1..1 | O80 Pollo → Pollo +0,00; O81 Camarón → Camarón +0,00; O82 Kanikama → Kanikama +0,00; O83 Salmón → Salmón +0,00; O84 Palmito → Palmito +0,00; O85 Champiñón → Champiñón +0,00; O86 Choclo → Choclo +0,00; O87 Palta → Palta +0,00 | 33 |
| G23 | envoltura-20-piezas-a-eleccion / Envoltura | single | 1..1 | O88 Frito → comercial sin ingrediente +0,00; O89 Queso crema → Queso crema +0,00; O90 Palta → Palta +0,00 | 33 |
| G24 | envoltura-nikkei-pendiente / Envoltura pendiente de confirmación | single | 1..1 | Sin opciones, pendiente sin asignación | Sin asignación efectiva |
| G25 | envoltura-nikkei-almendras / Envoltura | single | 1..1 | O91 Almendras → Almendras +0,00 | 31 |
| G26 | relleno-akilaburger / Elección adicional | single | 1..1 | O92 Pollo → Pollo +0,00; O93 Kanikama → Kanikama +0,00; O94 Salmón → Salmón +0,00; O95 Atún → Atún +0,00; O96 Camarón → Camarón +0,00; O97 Choclo → Choclo +0,00; O98 Palmito → Palmito +0,00; O99 Palta → Palta +0,00; O100 Champiñón → Champiñón +0,00 | 34 |
| G27 | ingredientes-extras / Ingredientes extras | multiple | 0..11 | O101 Camarón → Camarón +1.000,00; O102 Pollo → Pollo +1.000,00; O103 Kanikama → Kanikama +1.000,00; O104 Choclo → Choclo +1.000,00; O105 Palmito → Palmito +1.000,00; O106 Palta → Palta +1.000,00; O107 Champiñón → Champiñón +1.000,00; O108 Salmón → Salmón +2.000,00; O109 Pollo apanado → Pollo apanado +2.000,00; O110 Camarón apanado → Camarón apanado +2.000,00; O111 Atún → Atún +2.000,00 | Sin asignación efectiva |
| G28 | salsas-adicionales / Salsas adicionales | multiple | 0..2 | O112 Salsa de soya adicional → Salsa de soya +500,00; O113 Salsa agridulce adicional → Salsa agridulce +800,00 | 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 33 |
| G29 | sabor-arrollados / Sabores | multiple | 1..2 | O114 Primavera → comercial sin ingrediente +0,00; O115 Jamón Queso → comercial sin ingrediente +0,00 | 35 |

Todos los productos activos son alcanzables por tarjeta/modal y carrito en Chrome. Los grupos sin asignación directa son preparacion, envoltura-nikkei-pendiente, ingredientes-extras: Preparación sirve como sujeto de reglas globales; extras y envoltura pendiente son catálogos deliberadamente no habilitados. No se asignaron para ocultar un dato faltante.

## Precios, cantidades y reglas comerciales

La comparación de los 37 slugs con los cinco BatchData, tomando la última definición, devuelve **0 diferencias** en precio/cantidad/tipo de unidad/categoría/activo/bloques. Coinciden los 37 precios enumerados en el encargo; ningún precio nulo, cero o negativo. Los Gohan equivalentes en base confirmada son dos identidades intencionales de la fuente, no duplicados accidentales.

Las promociones a elección tienen 2/3/4/5/6 bloques para 20/30/40/50/60 piezas. Cada bloque exige un solo relleno de su grupo específico: proteína O vegetal. El backend rechaza dos rellenos, Salmón repetido y Pollo tres veces cuando hay al menos tres bloques. En 20, Pollo+Pollo es válido por máximo dos. Las reglas por ingrediente no cuentan envolturas/salsas/sabores como relleno. Fijos con tres Pollo, como Proteínas, no se invalidan.

| Producto | Bloques | Grupo relleno | Envolturas asignadas | Máximos activos |
|---|---|---|---|---|
| 30 Piezas a Elección | 3 | Pollo, Camarón, Kanikama, Salmón, Palmito, Champiñón, Choclo, Palta | Queso crema, Palta, Sésamo, Ciboulette, Frito | Pollo: 2.00; Camarón: 2.00; Kanikama: 2.00; Salmón: 1.00; Palmito: 2.00; Champiñón: 2.00; Choclo: 2.00; Palta: 2.00 |
| 60 Piezas a Elección | 6 | Pollo, Kanikama, Camarón, Salmón, Choclo, Palmito, Champiñón, Palta | Queso crema, Palta, Sésamo | Pollo: 2.00; Kanikama: 2.00; Camarón: 2.00; Salmón: 1.00; Choclo: 2.00; Palmito: 2.00; Champiñón: 2.00; Palta: 2.00 |
| 50 Piezas a Elección | 5 | Pollo, Kanikama, Camarón, Salmón, Choclo, Palmito, Champiñón, Palta | Queso crema, Palta, Sésamo | Pollo: 2.00; Kanikama: 2.00; Camarón: 2.00; Salmón: 1.00; Choclo: 2.00; Palmito: 2.00; Champiñón: 2.00; Palta: 2.00 |
| 40 Piezas a Elección | 4 | Pollo, Kanikama, Camarón, Salmón, Choclo, Palmito, Champiñón, Palta | Ninguna confirmada | Pollo: 2.00; Kanikama: 2.00; Camarón: 2.00; Salmón: 1.00; Choclo: 2.00; Palmito: 2.00; Champiñón: 2.00; Palta: 2.00 |
| 20 Piezas a Elección | 2 | Pollo, Camarón, Kanikama, Salmón, Palmito, Champiñón, Choclo, Palta | Frito, Queso crema, Palta | Pollo: 2.00; Camarón: 2.00; Kanikama: 2.00; Salmón: 1.00; Palmito: 2.00; Champiñón: 2.00; Choclo: 2.00; Palta: 2.00 |

Reglas activas: 478; inactivas: 1. Duplicados conceptuales activos por producto/tipo/sujeto/condición/uso: 0. Sujetos inválidos (ambos o ninguno): 0. Asignaciones con mínimo imposible: 0. Promociones/combo sin entidad complementaria requerida: 0.

Tradicional: Pollo/vegetales $3990; Salmón/Atún $4990. Relleno Doble: una proteína Y un vegetal, $4990. Combos Hand Roll 3/4/5 son unidades hand_roll, nunca 30/40/50 cortes; máximo general 2 y Salmón/Atún 1. Segundo ingrediente solo en 5: +$1000 por unidad, distinto del primero y cuenta en límites. Combo fijo Pollo Palta se resuelve sin elecciones. No existe Carne en los 23 ingredientes (el modelo Ingrediente no tiene campo activo; se verificó ausencia total).

Muchupleto conserva queso crema/cebollín/palta/pollo + exactamente una elección, $6500. AkilaBurger conserva queso crema/cebollín/palta + exactamente una elección, $7500 incluso Salmón/Atún. No se aplica catálogo de extras a sus elecciones incluidas.

Los cuatro Nikkei cuestan $6990 e incluyen 10 piezas. Jamón Serrano: pollo apanado/palta/queso crema/jamón serrano, sin selección; Camarón Apanado: Almendras singleton a $0. Pollo Crispy no pide proteína. Salmón ahumado y Salmón normal tienen IDs distintos. Ninguna composición fija premium genera +$2000. Las envolturas no confirmadas permanecen pendientes.

### Extras, salsas y delivery

| Columna monetaria real | Tipo SQL |
|---|---|
| opciones.recargo | decimal(10,2) |
| productos.precio | decimal(8,2) |
| reglas_promocion.valor | decimal(12,2) |

| Extra | Recargo DECIMAL |
|---|---|
| Camarón | 1.000,00 |
| Pollo | 1.000,00 |
| Kanikama | 1.000,00 |
| Choclo | 1.000,00 |
| Palmito | 1.000,00 |
| Palta | 1.000,00 |
| Champiñón | 1.000,00 |
| Salmón | 2.000,00 |
| Pollo apanado | 2.000,00 |
| Camarón apanado | 2.000,00 |
| Atún | 2.000,00 |

Asignaciones efectivas del catálogo de extras: **0 productos**. Está guardado para uso explícito futuro, sin habilitación masiva. No existen productos Extra Pollo/Delivery. El precio de cliente no interviene.

| Piezas | Soya | Agridulce | Precio incluido |
|---|---|---|---|
| 20 | 1 | 1 | 0 |
| 30–50 | 2 | 1 | 0 |
| 60–70 | 2 | 2 | 0 |
| 80–90 | 4 | 3 | 0 |
| 100 | 5 | 3 | 0 |
| 150 | Sin confirmar | Sin confirmar | Sin regla inventada |

Foto31 conserva autoridad frente a Foto37. Incluidas no intercambiables; adicionales soya $500/agridulce $800, máximo una de cada tipo por presentación según configuración actual (no cantidades arbitrarias por opción). Delivery San José $2500; exterior NULL/cotización manual, rechazado antes de crear pedido/preferencia. No hay precio/km ni producto Delivery.

Arrollados: 8 unidades, un grupo multiple 1..2 de Primavera/Jamón Queso; ambos sin ingrediente_id, sin recargos. El snapshot guarda nombres e indicador mixto sin 4+4. Gohan Flecha/Orion: $7500, inactivos, base confirmada Arroz/Queso crema/Ciboulette/Palta/Sésamo. La metadata vegetariana descriptiva vive con Arroz en el pivote; sin sustitución seleccionable ni recargo.

## Integridad referencial

| Comprobación | Resultado |
|---|---|
| Duplicados productos | 0 |
| Duplicados categorias | 0 |
| Duplicados grupos_opciones | 0 |
| Duplicados opciones | 0 |
| Duplicados bloques_promocion | 0 |
| Duplicados reglas_promocion | 0 |
| Duplicados ingrediente_producto | 0 |
| Duplicados grupo_opcion_producto | 0 |
| Duplicados bloque_grupo_opcion | 0 |
| Duplicados promociones | 0 |
| Duplicados producto_swappable_ingredient | 0 |
| Duplicados promocion_handroll | 0 |
| Duplicados ingredient_normalized | 0 |
| Duplicados product_name_normalized | 0 |
| Huérfanos productos.categoria_id | 0 |
| Huérfanos promociones.producto_id | 0 |
| Huérfanos bloques_promocion.producto_id | 0 |
| Huérfanos opciones.grupo_opcion_id | 0 |
| Huérfanos opciones.ingrediente_id | 0 |
| Huérfanos reglas_promocion.ingrediente_id | 0 |
| Huérfanos reglas_promocion.opcion_id | 0 |
| Huérfanos reglas_promocion.producto_id | 0 |
| Huérfanos ingrediente_producto.ingrediente_id | 0 |
| Huérfanos ingrediente_producto.producto_id | 0 |
| Huérfanos grupo_opcion_producto.grupo_opcion_id | 0 |
| Huérfanos grupo_opcion_producto.producto_id | 0 |
| Huérfanos bloque_grupo_opcion.bloque_promocion_id | 0 |
| Huérfanos bloque_grupo_opcion.grupo_opcion_id | 0 |
| Huérfanos producto_swappable_ingredient.ingrediente_id | 0 |
| Huérfanos producto_swappable_ingredient.producto_id | 0 |
| Huérfanos promocion_handroll.producto_id | 0 |
| Huérfanos promocion_handroll.promocion_id | 0 |
| Huérfanos orders.user_id | 0 |
| Huérfanos order_items.order_id | 0 |
| Huérfanos order_items.product_id | 0 |

| FK | Borrado real | Evaluación |
|---|---|---|
| productos.categoria_id → categorias.id | RESTRICT | RESTRICT protege historia |
| promociones.producto_id → productos.id | RESTRICT | Referencia protegida / nullable según contrato |
| bloques_promocion.producto_id → productos.id | CASCADE | Configuración dependiente o línea de su pedido; no cascada de catálogo hacia ventas |
| opciones.grupo_opcion_id → grupos_opciones.id | CASCADE | Configuración dependiente o línea de su pedido; no cascada de catálogo hacia ventas |
| opciones.ingrediente_id → ingredientes.id | RESTRICT | Referencia protegida / nullable según contrato |
| reglas_promocion.ingrediente_id → ingredientes.id | RESTRICT | Referencia protegida / nullable según contrato |
| reglas_promocion.opcion_id → opciones.id | RESTRICT | Referencia protegida / nullable según contrato |
| reglas_promocion.producto_id → productos.id | CASCADE | Configuración dependiente o línea de su pedido; no cascada de catálogo hacia ventas |
| ingrediente_producto.ingrediente_id → ingredientes.id | CASCADE | Configuración dependiente o línea de su pedido; no cascada de catálogo hacia ventas |
| ingrediente_producto.producto_id → productos.id | CASCADE | Configuración dependiente o línea de su pedido; no cascada de catálogo hacia ventas |
| grupo_opcion_producto.grupo_opcion_id → grupos_opciones.id | CASCADE | Configuración dependiente o línea de su pedido; no cascada de catálogo hacia ventas |
| grupo_opcion_producto.producto_id → productos.id | CASCADE | Configuración dependiente o línea de su pedido; no cascada de catálogo hacia ventas |
| bloque_grupo_opcion.bloque_promocion_id → bloques_promocion.id | CASCADE | Configuración dependiente o línea de su pedido; no cascada de catálogo hacia ventas |
| bloque_grupo_opcion.grupo_opcion_id → grupos_opciones.id | CASCADE | Configuración dependiente o línea de su pedido; no cascada de catálogo hacia ventas |
| producto_swappable_ingredient.ingrediente_id → ingredientes.id | CASCADE | Configuración dependiente o línea de su pedido; no cascada de catálogo hacia ventas |
| producto_swappable_ingredient.producto_id → productos.id | CASCADE | Configuración dependiente o línea de su pedido; no cascada de catálogo hacia ventas |
| promocion_handroll.producto_id → productos.id | CASCADE | Configuración dependiente o línea de su pedido; no cascada de catálogo hacia ventas |
| promocion_handroll.promocion_id → promociones.id | CASCADE | Configuración dependiente o línea de su pedido; no cascada de catálogo hacia ventas |
| orders.user_id → users.id | SET NULL | Referencia protegida / nullable según contrato |
| order_items.order_id → orders.id | CASCADE | Configuración dependiente o línea de su pedido; no cascada de catálogo hacia ventas |
| order_items.product_id → productos.id | RESTRICT | RESTRICT protege historia |

Los riesgos históricos de cascada categoría→producto→línea de venta ya están corregidos en el esquema real. Las cascadas de configuración y order→items son intencionales; no se realizó borrado de prueba en MariaDB. El registro Promocion es complementario: precio de venta autoritativo en Producto.

## Frontend, carrito, checkout, snapshots y pagos

Chrome local verificó **35/35** productos activos: tarjeta en categoría correcta, modal, precio, imagen/fallback cargada, selección según configuración, ancho móvil 390 px sin exceso y agregado al carrito. Los fijos no muestran bloques editables; los personalizables inician con agregar bloqueado. Excepciones JavaScript: 0.

La prueba de catálogo completo recorre los 37 productos: dos rechazados, 35 cotizados/agregados y persistidos juntos en Order/OrderItem, comparando configuración, detalle, salsas y cargos del snapshot v2 contra cotización. Cada producto cambia después de la compra en SQLite y se comprueba que el snapshot y nombre histórico permanecen iguales. PDF comparte el resumen histórico.

E2E representativos: 30 elección, Promo Hot, Tradicional Salmón, 5 Hand Rolls con segundo ingrediente, Muchupleto, AkilaBurger, Nikkei Jamón Serrano, Arrollados mixtos y El Emperador; Gohan inactivo en la prueba general. Las nueve rutas vendibles comprueban fusión de misma configuración, actualización de cantidad sin pérdida, eliminación, checkout local y payload del SDK real con transporte simulado: suma de ítems + delivery = Order.total. No se contacta el proveedor. Configuraciones diferentes/líneas separadas y manipulación de IDs/precios también están cubiertas por integración comercial y Batch02–05 existentes.

No se modificó MercadoPagoService ni verificación de pagos. Se certifica compatibilidad del contrato monetario con SDK simulado y las pruebas de seguridad, no credenciales, red, cuenta comercial ni un pago real. La idempotencia de pago sigue siendo por sesión, sin registro durable de intentos entre dispositivos.


Rutas de fotografía definidas en productos activos: 0. No se cargan nuevas imágenes en esta auditoría. La revisión de imagen/fallback verifica el recurso presentado, no atribuye fotos originales a productos que no las tienen.

## Consultas y eager loading

Perfiles HTTP internos con sesión/cache array: excluyen consultas de persistencia de sesión del navegador y no representan latencia de producción.

| Ruta/operación | Consultas observadas | Observación |
|---|---|---|
| home | 5 | HTTP 200 |
| menu | 4 | HTTP 200 |
| modal_30 | 11 | HTTP 200 |
| modal_emperor | 12 | HTTP 200 |
| cart_view | 0 | HTTP 200 |
| refresh_1 | 14 | 1 consultas de nombres de ingredientes incluidos |
| refresh_5 | 22 | 5 consultas de nombres de ingredientes incluidos |
| refresh_35 | 67 | 21 consultas de nombres de ingredientes incluidos |

Home y menú no tienen consulta por tarjeta; el modal carga solo un producto. Carrito GET presenta sesión (medición vacía: 0 consultas; el componente no consulta catálogo). El refresco de checkout sí crece con líneas: nombres de salsas incluidas y preparaciones se consultan dentro de cotizaciones; 14/22/67 consultas para 1/5/35 líneas de este catálogo. Se reporta MEDIA de rendimiento, sin modificar evaluación de reglas en esta auditoría. Admin listado: corregido a dos consultas de categoría (eager + catálogo global) y una de ingrediente_producto. Dashboard conserva posible N+1 de ranking hasta cinco productos y suma ventas sin filtrar pago; fuera de las decisiones del catálogo. Pedido carga items.producto; PDF usa detalle histórico y carga agrupada de items.

## Hallazgos, severidad y correcciones

| ID | Severidad | Antes / evidencia | Después / decisión |
|---|---|---|---|
| T01 | MEDIA | Admin diez filas: 11 consultas a categorías; relación ingredientes lazy por fila. Regresión falló antes. | Eager loading categoria/ingredientes. Prueba pasa con <=2 categorías y 1 consulta de composición. Sin cambios de datos. |
| T02 | MEDIA | Retiro + zona_delivery=fuera sin kms_fuera: HTTP500, Undefined array key kms_fuera. Reproducido por nueva prueba. | Distancia solo se lee si el método es delivery y existe el dato. Retiro guarda zona/distancia NULL, costo0, total correcto. Regresión pasa. |
| T03 | MEDIA | refreshCart 35 líneas: 67 consultas, 21 búsquedas de nombres de ingredientes; preparaciones también generan consultas repetidas. | Reportado para optimización posterior con preservación de snapshots; sin alterar reglas. |
| T04 | ALTA | Admin sigue usando edición legacy de ingredientes; store/update no atómicos entre producto y sync. No administra grupos/bloques/reglas/activo ni todos los tipos modernos. | Evitar editar composición de importados mediante formulario legacy; seeder principal es la fuente de reconstrucción actual. Requiere fase de administración compatible, no inferencia comercial. |
| T05 | MEDIA | Fallback legacy conserva detección salm por nombre y recargo1000; seeders demo antiguos siguen existiendo. | Ninguno de los 37 depende de esa rama para venta. Documentar/retirar en fase posterior; no ejecutar DatabaseSeeder demo para reconstruir menú. |
| T06 | MEDIA | Dashboard puede disparar hasta cinco lecturas de producto y suma pedidos sin filtrar pago aprobado. | Reportado; no se cambia contabilidad o ranking durante certificación del catálogo. |
| T07 | BAJA | Texto de checkout dice Según zona y distancia aunque no hay cálculo automático exterior; tarifa local repetida en Blade además de config. | No altera cobro; usar config como fuente de presentación en futura mejora. |
| D01 | DATO PENDIENTE | Dos Gohan sin composición diferencial; vegetariano no definido. | Inactivos y bloqueados, sin inventar sustitución. |
| D02 | DATO PENDIENTE | Envolturas Nikkei Pollo/Salmón sin lista específica; salsas150; proporción mixta. | Conservar decisiones documentadas y pedir confirmación operativa. |

No se encontraron errores técnicos CRÍTICOS en las rutas comerciales verificadas. ALTA T04 es una limitación vigente de mantenimiento del catálogo, no un fallo demostrado del checkout actual. No se hicieron correcciones comerciales por intuición. Las dos correcciones de código son las únicas productivas de esta tarea.

## Pendientes humanos consolidados

| Pendiente | Estado / pregunta a resolver |
|---|---|
| Gohan Flecha | Confirmar ingrediente/proteína diferencial antes de activar. |
| Gohan Orion | Confirmar ingrediente/proteína diferencial antes de activar. |
| Vegetariano Gohan | Qué se sustituye/elimina/agrega y precio aplicable; descripción informativa, sin operación inventada. |
| Arrollados mixtos | Proporción exacta no declarada; preparación manual sin 50/50 obligatorio. |
| Carne Hand Rolls | Confirmar existencia, ingrediente, producto y precio/recargo; no cargada. |
| Nikkei | Lista de envolturas por producto; no extrapolar general ni Almendras a todos. |
| El Emperador | Cantidades de potes incluidos para 150 piezas; no definidas. |
| Delivery exterior | Tarifa/cotización y flujo operativo; no precio/km supuesto. |
| Foto2 | Máximo general3 vs específicos2; excepciones no cumplen; recargo por ingrediente extra/exceso de repetición. |
| Sustituciones pagadas de fijos | Lista de cambios permitidos y referencia inicial para aplicar1000. No abrir toda la carta. |
| Distribución de preparaciones | Fritas/frías/envueltas por bloque donde solo hay totales; El Ciclón resto no especificado; envolturas40 elección. |
| Premium combos | Confirmar si se pretende otro recargo futuro; actualmente incluido a precio de ficha. |
| Salsas adicionales | Actualmente una por tipo y presentación. Cantidades mayores requieren definición de selección/cantidad. |

## Matriz final

OK significa coherencia técnica con el contrato modelado, no receta física completa. PENDIENTE identifica una confirmación específica; en Gohan la venta permanece bloqueada. Los pendientes comunes anteriores pueden afectar funcionalidades opcionales de productos con estado OK.

| Producto | Precio | Activo | Configurable | Bloques/Unidades | Validación | Carrito | Snapshot | Estado |
|---|---|---|---|---|---|---|---|---|
| Goloso | 10.000,00 | Sí | No | 2 bloques / 20 pieza | OK | OK Chrome + E2E | v2 verificado | OK |
| Proteínas | 14.990,00 | Sí | No | 3 bloques / 30 pieza | OK | OK Chrome + E2E | v2 verificado | OK |
| Noche de Sushi | 17.990,00 | Sí | No | 4 bloques / 40 pieza | OK | OK Chrome + E2E | v2 verificado | OK |
| Mi Favorita | 18.990,00 | Sí | No | 5 bloques / 50 pieza | OK | OK Chrome + E2E | v2 verificado | OK |
| Promo Hot | 19.990,00 | Sí | No | 6 bloques / 60 pieza | OK | OK Chrome + E2E | v2 verificado | OK |
| Lo Nuevo | 25.990,00 | Sí | No | 7 bloques / 70 pieza | OK | OK Chrome + E2E | v2 verificado | OK |
| 30 Piezas a Elección | 14.900,00 | Sí | Sí | 3 bloques / 30 pieza | OK | OK Chrome + E2E | v2 verificado | OK |
| Costanera | 14.900,00 | Sí | No | 3 bloques / 30 pieza | OK | OK Chrome + E2E | v2 verificado | OK |
| Monumento | 14.900,00 | Sí | No | 3 bloques / 30 pieza | OK | OK Chrome + E2E | v2 verificado | OK |
| Las Ruinas | 14.900,00 | Sí | No | 3 bloques / 30 pieza | OK | OK Chrome + E2E | v2 verificado | OK |
| La Playa | 14.900,00 | Sí | No | 3 bloques / 30 pieza | OK | OK Chrome + E2E | v2 verificado | OK |
| Código Verde | 14.900,00 | Sí | No | 3 bloques / 30 pieza | OK | OK Chrome + E2E | v2 verificado | OK |
| El Ciclón | 24.990,00 | Sí | No | 6 bloques / 60 pieza | OK | OK Chrome + E2E | v2 verificado | OK |
| 60 Piezas a Elección | 25.990,00 | Sí | Sí | 6 bloques / 60 pieza | OK | OK Chrome + E2E | v2 verificado | OK |
| 50 Piezas a Elección | 20.990,00 | Sí | Sí | 5 bloques / 50 pieza | OK | OK Chrome + E2E | v2 verificado | OK |
| Tu Media Naranja | 17.990,00 | Sí | No | 4 bloques / 40 pieza | OK | OK Chrome + E2E | v2 verificado | OK |
| 40 Piezas a Elección | 17.900,00 | Sí | Sí | 4 bloques / 40 pieza | OK | OK Chrome + E2E | v2 verificado | OK |
| El Emperador | 49.990,00 | Sí | No | 15 bloques / 150 pieza | OK | OK Chrome + E2E | v2 verificado | PENDIENTE |
| Para la Familia | 36.990,00 | Sí | No | 10 bloques / 100 pieza | OK | OK Chrome + E2E | v2 verificado | OK |
| De las que no Sobran | 31.990,00 | Sí | No | 9 bloques / 90 pieza | OK | OK Chrome + E2E | v2 verificado | OK |
| Las Mismas de Siempre | 27.900,00 | Sí | No | 8 bloques / 80 pieza | OK | OK Chrome + E2E | v2 verificado | OK |
| Muchupleto | 6.500,00 | Sí | Sí | 0 bloques / 1 unidad | OK | OK Chrome + E2E | v2 verificado | OK |
| Hand Roll Tradicional | 3.990,00 | Sí | Sí | 0 bloques / 1 hand_roll | OK | OK Chrome + E2E | v2 verificado | OK |
| Hand Roll Relleno Doble | 4.990,00 | Sí | Sí | 0 bloques / 1 hand_roll | OK | OK Chrome + E2E | v2 verificado | OK |
| 3 Hand Rolls | 11.000,00 | Sí | Sí | 3 bloques / 3 hand_roll | OK | OK Chrome + E2E | v2 verificado | OK |
| 4 Hand Rolls | 14.990,00 | Sí | Sí | 4 bloques / 4 hand_roll | OK | OK Chrome + E2E | v2 verificado | OK |
| 5 Hand Rolls | 18.000,00 | Sí | Sí | 5 bloques / 5 hand_roll | OK | OK Chrome + E2E | v2 verificado | OK |
| 4 Hand Rolls Pollo Palta | 18.000,00 | Sí | No | 4 bloques / 4 hand_roll | OK | OK Chrome + E2E | v2 verificado | OK |
| Nikkei Jamón Serrano | 6.990,00 | Sí | No | 0 bloques / 10 pieza | OK | OK Chrome + E2E | v2 verificado | OK |
| Nikkei Pollo Crispy | 6.990,00 | Sí | No | 0 bloques / 10 pieza | OK | OK Chrome + E2E | v2 verificado | PENDIENTE |
| Nikkei Camarón Apanado | 6.990,00 | Sí | No | 0 bloques / 10 pieza | OK | OK Chrome + E2E | v2 verificado | OK |
| Nikkei Salmón Ahumado | 6.990,00 | Sí | No | 0 bloques / 10 pieza | OK | OK Chrome + E2E | v2 verificado | PENDIENTE |
| 20 Piezas a Elección | 10.000,00 | Sí | Sí | 2 bloques / 20 pieza | OK | OK Chrome + E2E | v2 verificado | OK |
| AkilaBurger | 7.500,00 | Sí | Sí | 0 bloques / 1 unidad | OK | OK Chrome + E2E | v2 verificado | OK |
| Arrollados | 5.000,00 | Sí | Sí | 0 bloques / 8 unidad | OK | OK Chrome + E2E | v2 verificado | PENDIENTE |
| Gohan Flecha | 7.500,00 | No | No | 0 bloques / 1 unidad | Rechazo correcto | Bloqueado | No comprable; no inventado | PENDIENTE |
| Gohan Orion | 7.500,00 | No | No | 0 bloques / 1 unidad | Rechazo correcto | Bloqueado | No comprable; no inventado | PENDIENTE |

## Anexo de reglas por producto

Cada R corresponde al ID de Producto del inventario. Se incluyen reglas inactivas por trazabilidad. Los importes1000 de modificación son condicionales: sin una referencia de sustitución no se cobra la primera selección. Las cantidades incluidas/preparaciones no son recargos monetarios.

| Conjunto | ID regla | Código | Tipo | Sujeto | Valor | Activo | Condición / uso / fuente |
|---|---|---|---|---|---|---|---|
| R1 Goloso | 1 | cambio-relleno-pollo | recargo | Pollo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R1 Goloso | 2 | cambio-relleno-kanikama | recargo | Kanikama | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R1 Goloso | 3 | cambio-relleno-salmon | recargo | Salmón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R1 Goloso | 4 | cambio-relleno-atun | recargo | Atún | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R1 Goloso | 5 | cambio-relleno-camaron | recargo | Camarón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R1 Goloso | 6 | cambio-relleno-choclo | recargo | Choclo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R1 Goloso | 7 | cambio-relleno-palmito | recargo | Palmito | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R1 Goloso | 8 | cambio-relleno-palta | recargo | Palta | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R1 Goloso | 9 | cambio-relleno-champinon | recargo | Champiñón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R1 Goloso | 10 | cambio-relleno-morron | recargo | Morrón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R1 Goloso | 11 | cambio-envoltura-ciboulette | recargo | O14 Ciboulette | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R1 Goloso | 12 | cambio-envoltura-frito | recargo | O15 Frito | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R1 Goloso | 13 | cambio-envoltura-palta | recargo | O12 Palta | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R1 Goloso | 14 | cambio-envoltura-queso-crema | recargo | O11 Queso crema | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R1 Goloso | 15 | cambio-envoltura-sesamo | recargo | O13 Sésamo | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R1 Goloso | 16 | piezas-frito | cantidad_incluida | O15 Frito | 20.00 | Sí | preparacion_global; Foto38 |
| R1 Goloso | 438 | incluida-salsa-de-soya | cantidad_incluida | Salsa de soya | 1.00 | Sí | salsa_incluida; Foto31 |
| R1 Goloso | 439 | incluida-salsa-agridulce | cantidad_incluida | Salsa agridulce | 1.00 | Sí | salsa_incluida; Foto31 |
| R2 Proteínas | 17 | cambio-relleno-pollo | recargo | Pollo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R2 Proteínas | 18 | cambio-relleno-kanikama | recargo | Kanikama | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R2 Proteínas | 19 | cambio-relleno-salmon | recargo | Salmón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R2 Proteínas | 20 | cambio-relleno-atun | recargo | Atún | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R2 Proteínas | 21 | cambio-relleno-camaron | recargo | Camarón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R2 Proteínas | 22 | cambio-relleno-choclo | recargo | Choclo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R2 Proteínas | 23 | cambio-relleno-palmito | recargo | Palmito | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R2 Proteínas | 24 | cambio-relleno-palta | recargo | Palta | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R2 Proteínas | 25 | cambio-relleno-champinon | recargo | Champiñón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R2 Proteínas | 26 | cambio-relleno-morron | recargo | Morrón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R2 Proteínas | 27 | cambio-envoltura-ciboulette | recargo | O14 Ciboulette | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R2 Proteínas | 28 | cambio-envoltura-frito | recargo | O15 Frito | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R2 Proteínas | 29 | cambio-envoltura-palta | recargo | O12 Palta | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R2 Proteínas | 30 | cambio-envoltura-queso-crema | recargo | O11 Queso crema | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R2 Proteínas | 31 | cambio-envoltura-sesamo | recargo | O13 Sésamo | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R2 Proteínas | 32 | piezas-frito | cantidad_incluida | O15 Frito | 30.00 | Sí | preparacion_global; Foto4 |
| R2 Proteínas | 440 | incluida-salsa-de-soya | cantidad_incluida | Salsa de soya | 2.00 | Sí | salsa_incluida; Foto31 |
| R2 Proteínas | 441 | incluida-salsa-agridulce | cantidad_incluida | Salsa agridulce | 1.00 | Sí | salsa_incluida; Foto31 |
| R3 Noche de Sushi | 33 | cambio-relleno-pollo | recargo | Pollo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R3 Noche de Sushi | 34 | cambio-relleno-kanikama | recargo | Kanikama | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R3 Noche de Sushi | 35 | cambio-relleno-salmon | recargo | Salmón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R3 Noche de Sushi | 36 | cambio-relleno-atun | recargo | Atún | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R3 Noche de Sushi | 37 | cambio-relleno-camaron | recargo | Camarón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R3 Noche de Sushi | 38 | cambio-relleno-choclo | recargo | Choclo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R3 Noche de Sushi | 39 | cambio-relleno-palmito | recargo | Palmito | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R3 Noche de Sushi | 40 | cambio-relleno-palta | recargo | Palta | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R3 Noche de Sushi | 41 | cambio-relleno-champinon | recargo | Champiñón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R3 Noche de Sushi | 42 | cambio-relleno-morron | recargo | Morrón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R3 Noche de Sushi | 43 | cambio-envoltura-ciboulette | recargo | O14 Ciboulette | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R3 Noche de Sushi | 44 | cambio-envoltura-frito | recargo | O15 Frito | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R3 Noche de Sushi | 45 | cambio-envoltura-palta | recargo | O12 Palta | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R3 Noche de Sushi | 46 | cambio-envoltura-queso-crema | recargo | O11 Queso crema | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R3 Noche de Sushi | 47 | cambio-envoltura-sesamo | recargo | O13 Sésamo | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R3 Noche de Sushi | 48 | piezas-envuelto | cantidad_incluida | O24 Envuelto | 20.00 | No | preparacion_global; Foto1 |
| R3 Noche de Sushi | 49 | piezas-frito | cantidad_incluida | O15 Frito | 20.00 | Sí | preparacion_global; Foto15 |
| R3 Noche de Sushi | 442 | incluida-salsa-de-soya | cantidad_incluida | Salsa de soya | 2.00 | Sí | salsa_incluida; Foto31 |
| R3 Noche de Sushi | 443 | incluida-salsa-agridulce | cantidad_incluida | Salsa agridulce | 1.00 | Sí | salsa_incluida; Foto31 |
| R4 Mi Favorita | 50 | cambio-relleno-pollo | recargo | Pollo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R4 Mi Favorita | 51 | cambio-relleno-kanikama | recargo | Kanikama | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R4 Mi Favorita | 52 | cambio-relleno-salmon | recargo | Salmón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R4 Mi Favorita | 53 | cambio-relleno-atun | recargo | Atún | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R4 Mi Favorita | 54 | cambio-relleno-camaron | recargo | Camarón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R4 Mi Favorita | 55 | cambio-relleno-choclo | recargo | Choclo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R4 Mi Favorita | 56 | cambio-relleno-palmito | recargo | Palmito | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R4 Mi Favorita | 57 | cambio-relleno-palta | recargo | Palta | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R4 Mi Favorita | 58 | cambio-relleno-champinon | recargo | Champiñón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R4 Mi Favorita | 59 | cambio-relleno-morron | recargo | Morrón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R4 Mi Favorita | 60 | cambio-envoltura-ciboulette | recargo | O14 Ciboulette | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R4 Mi Favorita | 61 | cambio-envoltura-frito | recargo | O15 Frito | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R4 Mi Favorita | 62 | cambio-envoltura-palta | recargo | O12 Palta | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R4 Mi Favorita | 63 | cambio-envoltura-queso-crema | recargo | O11 Queso crema | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R4 Mi Favorita | 64 | cambio-envoltura-sesamo | recargo | O13 Sésamo | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R4 Mi Favorita | 65 | piezas-envuelto | cantidad_incluida | O24 Envuelto | 30.00 | Sí | preparacion_global; Foto32 |
| R4 Mi Favorita | 66 | piezas-frito | cantidad_incluida | O15 Frito | 20.00 | Sí | preparacion_global; Foto32 |
| R4 Mi Favorita | 444 | incluida-salsa-de-soya | cantidad_incluida | Salsa de soya | 2.00 | Sí | salsa_incluida; Foto31 |
| R4 Mi Favorita | 445 | incluida-salsa-agridulce | cantidad_incluida | Salsa agridulce | 1.00 | Sí | salsa_incluida; Foto31 |
| R5 Promo Hot | 67 | cambio-relleno-pollo | recargo | Pollo | 1000.00 | Sí | modificacion_relleno; Foto11 |
| R5 Promo Hot | 68 | cambio-relleno-kanikama | recargo | Kanikama | 1000.00 | Sí | modificacion_relleno; Foto11 |
| R5 Promo Hot | 69 | cambio-relleno-salmon | recargo | Salmón | 1000.00 | Sí | modificacion_relleno; Foto11 |
| R5 Promo Hot | 70 | cambio-relleno-atun | recargo | Atún | 1000.00 | Sí | modificacion_relleno; Foto11 |
| R5 Promo Hot | 71 | cambio-relleno-camaron | recargo | Camarón | 1000.00 | Sí | modificacion_relleno; Foto11 |
| R5 Promo Hot | 72 | cambio-relleno-choclo | recargo | Choclo | 1000.00 | Sí | modificacion_relleno; Foto11 |
| R5 Promo Hot | 73 | cambio-relleno-palmito | recargo | Palmito | 1000.00 | Sí | modificacion_relleno; Foto11 |
| R5 Promo Hot | 74 | cambio-relleno-palta | recargo | Palta | 1000.00 | Sí | modificacion_relleno; Foto11 |
| R5 Promo Hot | 75 | cambio-relleno-champinon | recargo | Champiñón | 1000.00 | Sí | modificacion_relleno; Foto11 |
| R5 Promo Hot | 76 | cambio-relleno-morron | recargo | Morrón | 1000.00 | Sí | modificacion_relleno; Foto11 |
| R5 Promo Hot | 77 | cambio-envoltura-ciboulette | recargo | O14 Ciboulette | 1000.00 | Sí | modificacion_envoltura; Foto11 |
| R5 Promo Hot | 78 | cambio-envoltura-frito | recargo | O15 Frito | 1000.00 | Sí | modificacion_envoltura; Foto11 |
| R5 Promo Hot | 79 | cambio-envoltura-palta | recargo | O12 Palta | 1000.00 | Sí | modificacion_envoltura; Foto11 |
| R5 Promo Hot | 80 | cambio-envoltura-queso-crema | recargo | O11 Queso crema | 1000.00 | Sí | modificacion_envoltura; Foto11 |
| R5 Promo Hot | 81 | cambio-envoltura-sesamo | recargo | O13 Sésamo | 1000.00 | Sí | modificacion_envoltura; Foto11 |
| R5 Promo Hot | 82 | piezas-frito | cantidad_incluida | O15 Frito | 60.00 | Sí | preparacion_global; Foto11 |
| R5 Promo Hot | 216 | repeticion-pollo | max_repeticiones | Pollo | 2.00 | Sí | selecciones_personalizadas; Foto11 |
| R5 Promo Hot | 217 | repeticion-kanikama | max_repeticiones | Kanikama | 2.00 | Sí | selecciones_personalizadas; Foto11 |
| R5 Promo Hot | 218 | repeticion-salmon | max_repeticiones | Salmón | 1.00 | Sí | selecciones_personalizadas; Foto11 |
| R5 Promo Hot | 219 | repeticion-atun | max_repeticiones | Atún | 2.00 | Sí | selecciones_personalizadas; Foto11 |
| R5 Promo Hot | 220 | repeticion-camaron | max_repeticiones | Camarón | 2.00 | Sí | selecciones_personalizadas; Foto11 |
| R5 Promo Hot | 221 | repeticion-choclo | max_repeticiones | Choclo | 2.00 | Sí | selecciones_personalizadas; Foto11 |
| R5 Promo Hot | 222 | repeticion-palmito | max_repeticiones | Palmito | 2.00 | Sí | selecciones_personalizadas; Foto11 |
| R5 Promo Hot | 223 | repeticion-palta | max_repeticiones | Palta | 2.00 | Sí | selecciones_personalizadas; Foto11 |
| R5 Promo Hot | 224 | repeticion-champinon | max_repeticiones | Champiñón | 2.00 | Sí | selecciones_personalizadas; Foto11 |
| R5 Promo Hot | 225 | repeticion-morron | max_repeticiones | Morrón | 2.00 | Sí | selecciones_personalizadas; Foto11 |
| R5 Promo Hot | 446 | incluida-salsa-de-soya | cantidad_incluida | Salsa de soya | 2.00 | Sí | salsa_incluida; Foto31 |
| R5 Promo Hot | 447 | incluida-salsa-agridulce | cantidad_incluida | Salsa agridulce | 2.00 | Sí | salsa_incluida; Foto31 |
| R6 Lo Nuevo | 83 | repeticion-pollo | max_repeticiones | Pollo | 2.00 | Sí | selecciones_personalizadas; Foto10 |
| R6 Lo Nuevo | 84 | repeticion-kanikama | max_repeticiones | Kanikama | 2.00 | Sí | selecciones_personalizadas; Foto10 |
| R6 Lo Nuevo | 85 | repeticion-salmon | max_repeticiones | Salmón | 1.00 | Sí | selecciones_personalizadas; Foto10 |
| R6 Lo Nuevo | 86 | repeticion-atun | max_repeticiones | Atún | 2.00 | Sí | selecciones_personalizadas; Foto10 |
| R6 Lo Nuevo | 87 | repeticion-camaron | max_repeticiones | Camarón | 2.00 | Sí | selecciones_personalizadas; Foto10 |
| R6 Lo Nuevo | 88 | repeticion-choclo | max_repeticiones | Choclo | 2.00 | Sí | selecciones_personalizadas; Foto10 |
| R6 Lo Nuevo | 89 | repeticion-palmito | max_repeticiones | Palmito | 2.00 | Sí | selecciones_personalizadas; Foto10 |
| R6 Lo Nuevo | 90 | repeticion-palta | max_repeticiones | Palta | 2.00 | Sí | selecciones_personalizadas; Foto10 |
| R6 Lo Nuevo | 91 | repeticion-champinon | max_repeticiones | Champiñón | 2.00 | Sí | selecciones_personalizadas; Foto10 |
| R6 Lo Nuevo | 92 | repeticion-morron | max_repeticiones | Morrón | 2.00 | Sí | selecciones_personalizadas; Foto10 |
| R6 Lo Nuevo | 93 | cambio-relleno-pollo | recargo | Pollo | 1000.00 | Sí | modificacion_relleno; Foto10 |
| R6 Lo Nuevo | 94 | cambio-relleno-kanikama | recargo | Kanikama | 1000.00 | Sí | modificacion_relleno; Foto10 |
| R6 Lo Nuevo | 95 | cambio-relleno-salmon | recargo | Salmón | 1000.00 | Sí | modificacion_relleno; Foto10 |
| R6 Lo Nuevo | 96 | cambio-relleno-atun | recargo | Atún | 1000.00 | Sí | modificacion_relleno; Foto10 |
| R6 Lo Nuevo | 97 | cambio-relleno-camaron | recargo | Camarón | 1000.00 | Sí | modificacion_relleno; Foto10 |
| R6 Lo Nuevo | 98 | cambio-relleno-choclo | recargo | Choclo | 1000.00 | Sí | modificacion_relleno; Foto10 |
| R6 Lo Nuevo | 99 | cambio-relleno-palmito | recargo | Palmito | 1000.00 | Sí | modificacion_relleno; Foto10 |
| R6 Lo Nuevo | 100 | cambio-relleno-palta | recargo | Palta | 1000.00 | Sí | modificacion_relleno; Foto10 |
| R6 Lo Nuevo | 101 | cambio-relleno-champinon | recargo | Champiñón | 1000.00 | Sí | modificacion_relleno; Foto10 |
| R6 Lo Nuevo | 102 | cambio-relleno-morron | recargo | Morrón | 1000.00 | Sí | modificacion_relleno; Foto10 |
| R6 Lo Nuevo | 103 | cambio-envoltura-ciboulette | recargo | O14 Ciboulette | 1000.00 | Sí | modificacion_envoltura; Foto10 |
| R6 Lo Nuevo | 104 | cambio-envoltura-frito | recargo | O15 Frito | 1000.00 | Sí | modificacion_envoltura; Foto10 |
| R6 Lo Nuevo | 105 | cambio-envoltura-palta | recargo | O12 Palta | 1000.00 | Sí | modificacion_envoltura; Foto10 |
| R6 Lo Nuevo | 106 | cambio-envoltura-queso-crema | recargo | O11 Queso crema | 1000.00 | Sí | modificacion_envoltura; Foto10 |
| R6 Lo Nuevo | 107 | cambio-envoltura-sesamo | recargo | O13 Sésamo | 1000.00 | Sí | modificacion_envoltura; Foto10 |
| R6 Lo Nuevo | 108 | piezas-frio | cantidad_incluida | O25 Frío | 30.00 | Sí | preparacion_global; Foto10 |
| R6 Lo Nuevo | 109 | piezas-frito | cantidad_incluida | O15 Frito | 40.00 | Sí | preparacion_global; Foto10 |
| R6 Lo Nuevo | 448 | incluida-salsa-de-soya | cantidad_incluida | Salsa de soya | 2.00 | Sí | salsa_incluida; Foto31 |
| R6 Lo Nuevo | 449 | incluida-salsa-agridulce | cantidad_incluida | Salsa agridulce | 2.00 | Sí | salsa_incluida; Foto31 |
| R7 30 Piezas a Elección | 110 | repeticion-pollo | max_repeticiones | Pollo | 2.00 | Sí | selecciones_personalizadas; Foto3 |
| R7 30 Piezas a Elección | 111 | repeticion-camaron | max_repeticiones | Camarón | 2.00 | Sí | selecciones_personalizadas; Foto3 |
| R7 30 Piezas a Elección | 112 | repeticion-kanikama | max_repeticiones | Kanikama | 2.00 | Sí | selecciones_personalizadas; Foto3 |
| R7 30 Piezas a Elección | 113 | repeticion-salmon | max_repeticiones | Salmón | 1.00 | Sí | selecciones_personalizadas; Foto3 |
| R7 30 Piezas a Elección | 114 | repeticion-palmito | max_repeticiones | Palmito | 2.00 | Sí | selecciones_personalizadas; Foto3 |
| R7 30 Piezas a Elección | 115 | repeticion-champinon | max_repeticiones | Champiñón | 2.00 | Sí | selecciones_personalizadas; Foto3 |
| R7 30 Piezas a Elección | 116 | repeticion-choclo | max_repeticiones | Choclo | 2.00 | Sí | selecciones_personalizadas; Foto3 |
| R7 30 Piezas a Elección | 117 | repeticion-palta | max_repeticiones | Palta | 2.00 | Sí | selecciones_personalizadas; Foto3 |
| R7 30 Piezas a Elección | 118 | cambio-relleno-pollo | recargo | Pollo | 1000.00 | Sí | modificacion_relleno; Foto3 |
| R7 30 Piezas a Elección | 119 | cambio-relleno-camaron | recargo | Camarón | 1000.00 | Sí | modificacion_relleno; Foto3 |
| R7 30 Piezas a Elección | 120 | cambio-relleno-kanikama | recargo | Kanikama | 1000.00 | Sí | modificacion_relleno; Foto3 |
| R7 30 Piezas a Elección | 121 | cambio-relleno-salmon | recargo | Salmón | 1000.00 | Sí | modificacion_relleno; Foto3 |
| R7 30 Piezas a Elección | 122 | cambio-relleno-palmito | recargo | Palmito | 1000.00 | Sí | modificacion_relleno; Foto3 |
| R7 30 Piezas a Elección | 123 | cambio-relleno-champinon | recargo | Champiñón | 1000.00 | Sí | modificacion_relleno; Foto3 |
| R7 30 Piezas a Elección | 124 | cambio-relleno-choclo | recargo | Choclo | 1000.00 | Sí | modificacion_relleno; Foto3 |
| R7 30 Piezas a Elección | 125 | cambio-relleno-palta | recargo | Palta | 1000.00 | Sí | modificacion_relleno; Foto3 |
| R7 30 Piezas a Elección | 126 | cambio-envoltura-ciboulette | recargo | O14 Ciboulette | 1000.00 | Sí | modificacion_envoltura; Foto3 |
| R7 30 Piezas a Elección | 127 | cambio-envoltura-frito | recargo | O15 Frito | 1000.00 | Sí | modificacion_envoltura; Foto3 |
| R7 30 Piezas a Elección | 128 | cambio-envoltura-palta | recargo | O12 Palta | 1000.00 | Sí | modificacion_envoltura; Foto3 |
| R7 30 Piezas a Elección | 129 | cambio-envoltura-queso-crema | recargo | O11 Queso crema | 1000.00 | Sí | modificacion_envoltura; Foto3 |
| R7 30 Piezas a Elección | 130 | cambio-envoltura-sesamo | recargo | O13 Sésamo | 1000.00 | Sí | modificacion_envoltura; Foto3 |
| R7 30 Piezas a Elección | 470 | incluida-salsa-de-soya | cantidad_incluida | Salsa de soya | 2.00 | Sí | salsa_incluida; Foto31 |
| R7 30 Piezas a Elección | 471 | incluida-salsa-agridulce | cantidad_incluida | Salsa agridulce | 1.00 | Sí | salsa_incluida; Foto31 |
| R8 Costanera | 131 | cambio-relleno-pollo | recargo | Pollo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R8 Costanera | 132 | cambio-relleno-kanikama | recargo | Kanikama | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R8 Costanera | 133 | cambio-relleno-salmon | recargo | Salmón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R8 Costanera | 134 | cambio-relleno-atun | recargo | Atún | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R8 Costanera | 135 | cambio-relleno-camaron | recargo | Camarón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R8 Costanera | 136 | cambio-relleno-choclo | recargo | Choclo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R8 Costanera | 137 | cambio-relleno-palmito | recargo | Palmito | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R8 Costanera | 138 | cambio-relleno-palta | recargo | Palta | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R8 Costanera | 139 | cambio-relleno-champinon | recargo | Champiñón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R8 Costanera | 140 | cambio-relleno-morron | recargo | Morrón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R8 Costanera | 141 | cambio-envoltura-ciboulette | recargo | O14 Ciboulette | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R8 Costanera | 142 | cambio-envoltura-frito | recargo | O15 Frito | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R8 Costanera | 143 | cambio-envoltura-palta | recargo | O12 Palta | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R8 Costanera | 144 | cambio-envoltura-queso-crema | recargo | O11 Queso crema | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R8 Costanera | 145 | cambio-envoltura-sesamo | recargo | O13 Sésamo | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R8 Costanera | 146 | piezas-frito | cantidad_incluida | O15 Frito | 10.00 | Sí | preparacion_global; Foto5 |
| R8 Costanera | 147 | piezas-envuelto | cantidad_incluida | O24 Envuelto | 20.00 | Sí | preparacion_global; Foto5 |
| R8 Costanera | 450 | incluida-salsa-de-soya | cantidad_incluida | Salsa de soya | 2.00 | Sí | salsa_incluida; Foto31 |
| R8 Costanera | 451 | incluida-salsa-agridulce | cantidad_incluida | Salsa agridulce | 1.00 | Sí | salsa_incluida; Foto31 |
| R9 Monumento | 148 | cambio-relleno-pollo | recargo | Pollo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R9 Monumento | 149 | cambio-relleno-kanikama | recargo | Kanikama | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R9 Monumento | 150 | cambio-relleno-salmon | recargo | Salmón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R9 Monumento | 151 | cambio-relleno-atun | recargo | Atún | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R9 Monumento | 152 | cambio-relleno-camaron | recargo | Camarón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R9 Monumento | 153 | cambio-relleno-choclo | recargo | Choclo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R9 Monumento | 154 | cambio-relleno-palmito | recargo | Palmito | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R9 Monumento | 155 | cambio-relleno-palta | recargo | Palta | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R9 Monumento | 156 | cambio-relleno-champinon | recargo | Champiñón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R9 Monumento | 157 | cambio-relleno-morron | recargo | Morrón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R9 Monumento | 158 | cambio-envoltura-ciboulette | recargo | O14 Ciboulette | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R9 Monumento | 159 | cambio-envoltura-frito | recargo | O15 Frito | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R9 Monumento | 160 | cambio-envoltura-palta | recargo | O12 Palta | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R9 Monumento | 161 | cambio-envoltura-queso-crema | recargo | O11 Queso crema | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R9 Monumento | 162 | cambio-envoltura-sesamo | recargo | O13 Sésamo | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R9 Monumento | 163 | piezas-frito | cantidad_incluida | O15 Frito | 10.00 | Sí | preparacion_global; Foto6 |
| R9 Monumento | 164 | piezas-envuelto | cantidad_incluida | O24 Envuelto | 20.00 | Sí | preparacion_global; Foto6 |
| R9 Monumento | 452 | incluida-salsa-de-soya | cantidad_incluida | Salsa de soya | 2.00 | Sí | salsa_incluida; Foto31 |
| R9 Monumento | 453 | incluida-salsa-agridulce | cantidad_incluida | Salsa agridulce | 1.00 | Sí | salsa_incluida; Foto31 |
| R10 Las Ruinas | 165 | cambio-relleno-pollo | recargo | Pollo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R10 Las Ruinas | 166 | cambio-relleno-kanikama | recargo | Kanikama | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R10 Las Ruinas | 167 | cambio-relleno-salmon | recargo | Salmón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R10 Las Ruinas | 168 | cambio-relleno-atun | recargo | Atún | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R10 Las Ruinas | 169 | cambio-relleno-camaron | recargo | Camarón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R10 Las Ruinas | 170 | cambio-relleno-choclo | recargo | Choclo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R10 Las Ruinas | 171 | cambio-relleno-palmito | recargo | Palmito | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R10 Las Ruinas | 172 | cambio-relleno-palta | recargo | Palta | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R10 Las Ruinas | 173 | cambio-relleno-champinon | recargo | Champiñón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R10 Las Ruinas | 174 | cambio-relleno-morron | recargo | Morrón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R10 Las Ruinas | 175 | cambio-envoltura-ciboulette | recargo | O14 Ciboulette | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R10 Las Ruinas | 176 | cambio-envoltura-frito | recargo | O15 Frito | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R10 Las Ruinas | 177 | cambio-envoltura-palta | recargo | O12 Palta | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R10 Las Ruinas | 178 | cambio-envoltura-queso-crema | recargo | O11 Queso crema | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R10 Las Ruinas | 179 | cambio-envoltura-sesamo | recargo | O13 Sésamo | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R10 Las Ruinas | 180 | piezas-frito | cantidad_incluida | O15 Frito | 10.00 | Sí | preparacion_global; Foto7 |
| R10 Las Ruinas | 181 | piezas-envuelto | cantidad_incluida | O24 Envuelto | 20.00 | Sí | preparacion_global; Foto7 |
| R10 Las Ruinas | 454 | incluida-salsa-de-soya | cantidad_incluida | Salsa de soya | 2.00 | Sí | salsa_incluida; Foto31 |
| R10 Las Ruinas | 455 | incluida-salsa-agridulce | cantidad_incluida | Salsa agridulce | 1.00 | Sí | salsa_incluida; Foto31 |
| R11 La Playa | 182 | cambio-relleno-pollo | recargo | Pollo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R11 La Playa | 183 | cambio-relleno-kanikama | recargo | Kanikama | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R11 La Playa | 184 | cambio-relleno-salmon | recargo | Salmón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R11 La Playa | 185 | cambio-relleno-atun | recargo | Atún | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R11 La Playa | 186 | cambio-relleno-camaron | recargo | Camarón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R11 La Playa | 187 | cambio-relleno-choclo | recargo | Choclo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R11 La Playa | 188 | cambio-relleno-palmito | recargo | Palmito | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R11 La Playa | 189 | cambio-relleno-palta | recargo | Palta | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R11 La Playa | 190 | cambio-relleno-champinon | recargo | Champiñón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R11 La Playa | 191 | cambio-relleno-morron | recargo | Morrón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R11 La Playa | 192 | cambio-envoltura-ciboulette | recargo | O14 Ciboulette | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R11 La Playa | 193 | cambio-envoltura-frito | recargo | O15 Frito | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R11 La Playa | 194 | cambio-envoltura-palta | recargo | O12 Palta | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R11 La Playa | 195 | cambio-envoltura-queso-crema | recargo | O11 Queso crema | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R11 La Playa | 196 | cambio-envoltura-sesamo | recargo | O13 Sésamo | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R11 La Playa | 197 | piezas-frito | cantidad_incluida | O15 Frito | 10.00 | Sí | preparacion_global; Foto8 |
| R11 La Playa | 198 | piezas-envuelto | cantidad_incluida | O24 Envuelto | 20.00 | Sí | preparacion_global; Foto8 |
| R11 La Playa | 456 | incluida-salsa-de-soya | cantidad_incluida | Salsa de soya | 2.00 | Sí | salsa_incluida; Foto31 |
| R11 La Playa | 457 | incluida-salsa-agridulce | cantidad_incluida | Salsa agridulce | 1.00 | Sí | salsa_incluida; Foto31 |
| R12 Código Verde | 199 | cambio-relleno-pollo | recargo | Pollo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R12 Código Verde | 200 | cambio-relleno-kanikama | recargo | Kanikama | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R12 Código Verde | 201 | cambio-relleno-salmon | recargo | Salmón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R12 Código Verde | 202 | cambio-relleno-atun | recargo | Atún | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R12 Código Verde | 203 | cambio-relleno-camaron | recargo | Camarón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R12 Código Verde | 204 | cambio-relleno-choclo | recargo | Choclo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R12 Código Verde | 205 | cambio-relleno-palmito | recargo | Palmito | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R12 Código Verde | 206 | cambio-relleno-palta | recargo | Palta | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R12 Código Verde | 207 | cambio-relleno-champinon | recargo | Champiñón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R12 Código Verde | 208 | cambio-relleno-morron | recargo | Morrón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R12 Código Verde | 209 | cambio-envoltura-ciboulette | recargo | O14 Ciboulette | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R12 Código Verde | 210 | cambio-envoltura-frito | recargo | O15 Frito | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R12 Código Verde | 211 | cambio-envoltura-palta | recargo | O12 Palta | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R12 Código Verde | 212 | cambio-envoltura-queso-crema | recargo | O11 Queso crema | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R12 Código Verde | 213 | cambio-envoltura-sesamo | recargo | O13 Sésamo | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R12 Código Verde | 214 | piezas-frito | cantidad_incluida | O15 Frito | 10.00 | Sí | preparacion_global; Foto9 |
| R12 Código Verde | 215 | piezas-envuelto | cantidad_incluida | O24 Envuelto | 20.00 | Sí | preparacion_global; Foto9 |
| R12 Código Verde | 458 | incluida-salsa-de-soya | cantidad_incluida | Salsa de soya | 2.00 | Sí | salsa_incluida; Foto31 |
| R12 Código Verde | 459 | incluida-salsa-agridulce | cantidad_incluida | Salsa agridulce | 1.00 | Sí | salsa_incluida; Foto31 |
| R13 El Ciclón | 226 | cambio-relleno-pollo | recargo | Pollo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R13 El Ciclón | 227 | cambio-relleno-kanikama | recargo | Kanikama | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R13 El Ciclón | 228 | cambio-relleno-salmon | recargo | Salmón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R13 El Ciclón | 229 | cambio-relleno-atun | recargo | Atún | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R13 El Ciclón | 230 | cambio-relleno-camaron | recargo | Camarón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R13 El Ciclón | 231 | cambio-relleno-choclo | recargo | Choclo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R13 El Ciclón | 232 | cambio-relleno-palmito | recargo | Palmito | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R13 El Ciclón | 233 | cambio-relleno-palta | recargo | Palta | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R13 El Ciclón | 234 | cambio-relleno-champinon | recargo | Champiñón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R13 El Ciclón | 235 | cambio-relleno-morron | recargo | Morrón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R13 El Ciclón | 236 | cambio-envoltura-ciboulette | recargo | O14 Ciboulette | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R13 El Ciclón | 237 | cambio-envoltura-frito | recargo | O15 Frito | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R13 El Ciclón | 238 | cambio-envoltura-palta | recargo | O12 Palta | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R13 El Ciclón | 239 | cambio-envoltura-queso-crema | recargo | O11 Queso crema | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R13 El Ciclón | 240 | cambio-envoltura-sesamo | recargo | O13 Sésamo | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R13 El Ciclón | 241 | piezas-frito | cantidad_incluida | O15 Frito | 30.00 | Sí | preparacion_global; Foto12 |
| R13 El Ciclón | 460 | incluida-salsa-de-soya | cantidad_incluida | Salsa de soya | 2.00 | Sí | salsa_incluida; Foto31 |
| R13 El Ciclón | 461 | incluida-salsa-agridulce | cantidad_incluida | Salsa agridulce | 2.00 | Sí | salsa_incluida; Foto31 |
| R14 60 Piezas a Elección | 242 | repeticion-pollo | max_repeticiones | Pollo | 2.00 | Sí | selecciones_personalizadas; Foto13 |
| R14 60 Piezas a Elección | 243 | repeticion-kanikama | max_repeticiones | Kanikama | 2.00 | Sí | selecciones_personalizadas; Foto13 |
| R14 60 Piezas a Elección | 244 | repeticion-camaron | max_repeticiones | Camarón | 2.00 | Sí | selecciones_personalizadas; Foto13 |
| R14 60 Piezas a Elección | 245 | repeticion-salmon | max_repeticiones | Salmón | 1.00 | Sí | selecciones_personalizadas; Foto13 |
| R14 60 Piezas a Elección | 246 | repeticion-choclo | max_repeticiones | Choclo | 2.00 | Sí | selecciones_personalizadas; Foto13 |
| R14 60 Piezas a Elección | 247 | repeticion-palmito | max_repeticiones | Palmito | 2.00 | Sí | selecciones_personalizadas; Foto13 |
| R14 60 Piezas a Elección | 248 | repeticion-champinon | max_repeticiones | Champiñón | 2.00 | Sí | selecciones_personalizadas; Foto13 |
| R14 60 Piezas a Elección | 249 | repeticion-palta | max_repeticiones | Palta | 2.00 | Sí | selecciones_personalizadas; Foto13 |
| R14 60 Piezas a Elección | 250 | cambio-relleno-pollo | recargo | Pollo | 1000.00 | Sí | modificacion_relleno; Foto13 |
| R14 60 Piezas a Elección | 251 | cambio-relleno-kanikama | recargo | Kanikama | 1000.00 | Sí | modificacion_relleno; Foto13 |
| R14 60 Piezas a Elección | 252 | cambio-relleno-camaron | recargo | Camarón | 1000.00 | Sí | modificacion_relleno; Foto13 |
| R14 60 Piezas a Elección | 253 | cambio-relleno-salmon | recargo | Salmón | 1000.00 | Sí | modificacion_relleno; Foto13 |
| R14 60 Piezas a Elección | 254 | cambio-relleno-choclo | recargo | Choclo | 1000.00 | Sí | modificacion_relleno; Foto13 |
| R14 60 Piezas a Elección | 255 | cambio-relleno-palmito | recargo | Palmito | 1000.00 | Sí | modificacion_relleno; Foto13 |
| R14 60 Piezas a Elección | 256 | cambio-relleno-champinon | recargo | Champiñón | 1000.00 | Sí | modificacion_relleno; Foto13 |
| R14 60 Piezas a Elección | 257 | cambio-relleno-palta | recargo | Palta | 1000.00 | Sí | modificacion_relleno; Foto13 |
| R14 60 Piezas a Elección | 258 | cambio-envoltura-palta | recargo | O46 Palta | 1000.00 | Sí | modificacion_envoltura; Foto13 |
| R14 60 Piezas a Elección | 259 | cambio-envoltura-queso-crema | recargo | O45 Queso crema | 1000.00 | Sí | modificacion_envoltura; Foto13 |
| R14 60 Piezas a Elección | 260 | cambio-envoltura-sesamo | recargo | O47 Sésamo | 1000.00 | Sí | modificacion_envoltura; Foto13 |
| R14 60 Piezas a Elección | 261 | piezas-frito | cantidad_incluida | O15 Frito | 30.00 | Sí | preparacion_global; Foto13 |
| R14 60 Piezas a Elección | 472 | incluida-salsa-de-soya | cantidad_incluida | Salsa de soya | 2.00 | Sí | salsa_incluida; Foto31 |
| R14 60 Piezas a Elección | 473 | incluida-salsa-agridulce | cantidad_incluida | Salsa agridulce | 2.00 | Sí | salsa_incluida; Foto31 |
| R15 50 Piezas a Elección | 262 | repeticion-pollo | max_repeticiones | Pollo | 2.00 | Sí | selecciones_personalizadas; Foto30 |
| R15 50 Piezas a Elección | 263 | repeticion-kanikama | max_repeticiones | Kanikama | 2.00 | Sí | selecciones_personalizadas; Foto30 |
| R15 50 Piezas a Elección | 264 | repeticion-camaron | max_repeticiones | Camarón | 2.00 | Sí | selecciones_personalizadas; Foto30 |
| R15 50 Piezas a Elección | 265 | repeticion-salmon | max_repeticiones | Salmón | 1.00 | Sí | selecciones_personalizadas; Foto30 |
| R15 50 Piezas a Elección | 266 | repeticion-choclo | max_repeticiones | Choclo | 2.00 | Sí | selecciones_personalizadas; Foto30 |
| R15 50 Piezas a Elección | 267 | repeticion-palmito | max_repeticiones | Palmito | 2.00 | Sí | selecciones_personalizadas; Foto30 |
| R15 50 Piezas a Elección | 268 | repeticion-champinon | max_repeticiones | Champiñón | 2.00 | Sí | selecciones_personalizadas; Foto30 |
| R15 50 Piezas a Elección | 269 | repeticion-palta | max_repeticiones | Palta | 2.00 | Sí | selecciones_personalizadas; Foto30 |
| R15 50 Piezas a Elección | 270 | cambio-relleno-pollo | recargo | Pollo | 1000.00 | Sí | modificacion_relleno; Foto30 |
| R15 50 Piezas a Elección | 271 | cambio-relleno-kanikama | recargo | Kanikama | 1000.00 | Sí | modificacion_relleno; Foto30 |
| R15 50 Piezas a Elección | 272 | cambio-relleno-camaron | recargo | Camarón | 1000.00 | Sí | modificacion_relleno; Foto30 |
| R15 50 Piezas a Elección | 273 | cambio-relleno-salmon | recargo | Salmón | 1000.00 | Sí | modificacion_relleno; Foto30 |
| R15 50 Piezas a Elección | 274 | cambio-relleno-choclo | recargo | Choclo | 1000.00 | Sí | modificacion_relleno; Foto30 |
| R15 50 Piezas a Elección | 275 | cambio-relleno-palmito | recargo | Palmito | 1000.00 | Sí | modificacion_relleno; Foto30 |
| R15 50 Piezas a Elección | 276 | cambio-relleno-champinon | recargo | Champiñón | 1000.00 | Sí | modificacion_relleno; Foto30 |
| R15 50 Piezas a Elección | 277 | cambio-relleno-palta | recargo | Palta | 1000.00 | Sí | modificacion_relleno; Foto30 |
| R15 50 Piezas a Elección | 278 | cambio-envoltura-palta | recargo | O46 Palta | 1000.00 | Sí | modificacion_envoltura; Foto30 |
| R15 50 Piezas a Elección | 279 | cambio-envoltura-queso-crema | recargo | O45 Queso crema | 1000.00 | Sí | modificacion_envoltura; Foto30 |
| R15 50 Piezas a Elección | 280 | cambio-envoltura-sesamo | recargo | O47 Sésamo | 1000.00 | Sí | modificacion_envoltura; Foto30 |
| R15 50 Piezas a Elección | 281 | piezas-frito | cantidad_incluida | O15 Frito | 20.00 | Sí | preparacion_global; Foto30 |
| R15 50 Piezas a Elección | 474 | incluida-salsa-de-soya | cantidad_incluida | Salsa de soya | 2.00 | Sí | salsa_incluida; Foto31 |
| R15 50 Piezas a Elección | 475 | incluida-salsa-agridulce | cantidad_incluida | Salsa agridulce | 1.00 | Sí | salsa_incluida; Foto31 |
| R16 Tu Media Naranja | 282 | cambio-relleno-pollo | recargo | Pollo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R16 Tu Media Naranja | 283 | cambio-relleno-kanikama | recargo | Kanikama | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R16 Tu Media Naranja | 284 | cambio-relleno-salmon | recargo | Salmón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R16 Tu Media Naranja | 285 | cambio-relleno-atun | recargo | Atún | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R16 Tu Media Naranja | 286 | cambio-relleno-camaron | recargo | Camarón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R16 Tu Media Naranja | 287 | cambio-relleno-choclo | recargo | Choclo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R16 Tu Media Naranja | 288 | cambio-relleno-palmito | recargo | Palmito | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R16 Tu Media Naranja | 289 | cambio-relleno-palta | recargo | Palta | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R16 Tu Media Naranja | 290 | cambio-relleno-champinon | recargo | Champiñón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R16 Tu Media Naranja | 291 | cambio-relleno-morron | recargo | Morrón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R16 Tu Media Naranja | 292 | cambio-envoltura-ciboulette | recargo | O14 Ciboulette | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R16 Tu Media Naranja | 293 | cambio-envoltura-frito | recargo | O15 Frito | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R16 Tu Media Naranja | 294 | cambio-envoltura-palta | recargo | O12 Palta | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R16 Tu Media Naranja | 295 | cambio-envoltura-queso-crema | recargo | O11 Queso crema | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R16 Tu Media Naranja | 296 | cambio-envoltura-sesamo | recargo | O13 Sésamo | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R16 Tu Media Naranja | 297 | piezas-frito | cantidad_incluida | O15 Frito | 20.00 | Sí | preparacion_global; Foto16 |
| R16 Tu Media Naranja | 298 | piezas-envuelto | cantidad_incluida | O24 Envuelto | 20.00 | Sí | preparacion_global; Foto16 |
| R16 Tu Media Naranja | 462 | incluida-salsa-de-soya | cantidad_incluida | Salsa de soya | 2.00 | Sí | salsa_incluida; Foto31 |
| R16 Tu Media Naranja | 463 | incluida-salsa-agridulce | cantidad_incluida | Salsa agridulce | 1.00 | Sí | salsa_incluida; Foto31 |
| R17 40 Piezas a Elección | 299 | repeticion-pollo | max_repeticiones | Pollo | 2.00 | Sí | selecciones_personalizadas; Foto17 |
| R17 40 Piezas a Elección | 300 | repeticion-kanikama | max_repeticiones | Kanikama | 2.00 | Sí | selecciones_personalizadas; Foto17 |
| R17 40 Piezas a Elección | 301 | repeticion-camaron | max_repeticiones | Camarón | 2.00 | Sí | selecciones_personalizadas; Foto17 |
| R17 40 Piezas a Elección | 302 | repeticion-salmon | max_repeticiones | Salmón | 1.00 | Sí | selecciones_personalizadas; Foto17 |
| R17 40 Piezas a Elección | 303 | repeticion-choclo | max_repeticiones | Choclo | 2.00 | Sí | selecciones_personalizadas; Foto17 |
| R17 40 Piezas a Elección | 304 | repeticion-palmito | max_repeticiones | Palmito | 2.00 | Sí | selecciones_personalizadas; Foto17 |
| R17 40 Piezas a Elección | 305 | repeticion-champinon | max_repeticiones | Champiñón | 2.00 | Sí | selecciones_personalizadas; Foto17 |
| R17 40 Piezas a Elección | 306 | repeticion-palta | max_repeticiones | Palta | 2.00 | Sí | selecciones_personalizadas; Foto17 |
| R17 40 Piezas a Elección | 307 | cambio-relleno-pollo | recargo | Pollo | 1000.00 | Sí | modificacion_relleno; Foto17 |
| R17 40 Piezas a Elección | 308 | cambio-relleno-kanikama | recargo | Kanikama | 1000.00 | Sí | modificacion_relleno; Foto17 |
| R17 40 Piezas a Elección | 309 | cambio-relleno-camaron | recargo | Camarón | 1000.00 | Sí | modificacion_relleno; Foto17 |
| R17 40 Piezas a Elección | 310 | cambio-relleno-salmon | recargo | Salmón | 1000.00 | Sí | modificacion_relleno; Foto17 |
| R17 40 Piezas a Elección | 311 | cambio-relleno-choclo | recargo | Choclo | 1000.00 | Sí | modificacion_relleno; Foto17 |
| R17 40 Piezas a Elección | 312 | cambio-relleno-palmito | recargo | Palmito | 1000.00 | Sí | modificacion_relleno; Foto17 |
| R17 40 Piezas a Elección | 313 | cambio-relleno-champinon | recargo | Champiñón | 1000.00 | Sí | modificacion_relleno; Foto17 |
| R17 40 Piezas a Elección | 314 | cambio-relleno-palta | recargo | Palta | 1000.00 | Sí | modificacion_relleno; Foto17 |
| R17 40 Piezas a Elección | 315 | piezas-frito | cantidad_incluida | O15 Frito | 20.00 | Sí | preparacion_global; Foto17 |
| R17 40 Piezas a Elección | 476 | incluida-salsa-de-soya | cantidad_incluida | Salsa de soya | 2.00 | Sí | salsa_incluida; Foto31 |
| R17 40 Piezas a Elección | 477 | incluida-salsa-agridulce | cantidad_incluida | Salsa agridulce | 1.00 | Sí | salsa_incluida; Foto31 |
| R18 El Emperador | 316 | cambio-relleno-pollo | recargo | Pollo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R18 El Emperador | 317 | cambio-relleno-kanikama | recargo | Kanikama | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R18 El Emperador | 318 | cambio-relleno-salmon | recargo | Salmón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R18 El Emperador | 319 | cambio-relleno-atun | recargo | Atún | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R18 El Emperador | 320 | cambio-relleno-camaron | recargo | Camarón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R18 El Emperador | 321 | cambio-relleno-choclo | recargo | Choclo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R18 El Emperador | 322 | cambio-relleno-palmito | recargo | Palmito | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R18 El Emperador | 323 | cambio-relleno-palta | recargo | Palta | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R18 El Emperador | 324 | cambio-relleno-champinon | recargo | Champiñón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R18 El Emperador | 325 | cambio-relleno-morron | recargo | Morrón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R18 El Emperador | 326 | cambio-envoltura-ciboulette | recargo | O14 Ciboulette | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R18 El Emperador | 327 | cambio-envoltura-frito | recargo | O15 Frito | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R18 El Emperador | 328 | cambio-envoltura-palta | recargo | O12 Palta | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R18 El Emperador | 329 | cambio-envoltura-queso-crema | recargo | O11 Queso crema | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R18 El Emperador | 330 | cambio-envoltura-sesamo | recargo | O13 Sésamo | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R18 El Emperador | 331 | piezas-frio | cantidad_incluida | O25 Frío | 60.00 | Sí | preparacion_global; Foto18 |
| R18 El Emperador | 332 | piezas-frito | cantidad_incluida | O15 Frito | 90.00 | Sí | preparacion_global; Foto18 |
| R19 Para la Familia | 333 | cambio-relleno-pollo | recargo | Pollo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R19 Para la Familia | 334 | cambio-relleno-kanikama | recargo | Kanikama | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R19 Para la Familia | 335 | cambio-relleno-salmon | recargo | Salmón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R19 Para la Familia | 336 | cambio-relleno-atun | recargo | Atún | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R19 Para la Familia | 337 | cambio-relleno-camaron | recargo | Camarón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R19 Para la Familia | 338 | cambio-relleno-choclo | recargo | Choclo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R19 Para la Familia | 339 | cambio-relleno-palmito | recargo | Palmito | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R19 Para la Familia | 340 | cambio-relleno-palta | recargo | Palta | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R19 Para la Familia | 341 | cambio-relleno-champinon | recargo | Champiñón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R19 Para la Familia | 342 | cambio-relleno-morron | recargo | Morrón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R19 Para la Familia | 343 | cambio-envoltura-ciboulette | recargo | O14 Ciboulette | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R19 Para la Familia | 344 | cambio-envoltura-frito | recargo | O15 Frito | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R19 Para la Familia | 345 | cambio-envoltura-palta | recargo | O12 Palta | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R19 Para la Familia | 346 | cambio-envoltura-queso-crema | recargo | O11 Queso crema | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R19 Para la Familia | 347 | cambio-envoltura-sesamo | recargo | O13 Sésamo | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R19 Para la Familia | 348 | piezas-frio | cantidad_incluida | O25 Frío | 40.00 | Sí | preparacion_global; Foto19 |
| R19 Para la Familia | 349 | piezas-frito | cantidad_incluida | O15 Frito | 60.00 | Sí | preparacion_global; Foto19 |
| R19 Para la Familia | 464 | incluida-salsa-de-soya | cantidad_incluida | Salsa de soya | 5.00 | Sí | salsa_incluida; Foto31 |
| R19 Para la Familia | 465 | incluida-salsa-agridulce | cantidad_incluida | Salsa agridulce | 3.00 | Sí | salsa_incluida; Foto31 |
| R20 De las que no Sobran | 350 | cambio-relleno-pollo | recargo | Pollo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R20 De las que no Sobran | 351 | cambio-relleno-kanikama | recargo | Kanikama | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R20 De las que no Sobran | 352 | cambio-relleno-salmon | recargo | Salmón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R20 De las que no Sobran | 353 | cambio-relleno-atun | recargo | Atún | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R20 De las que no Sobran | 354 | cambio-relleno-camaron | recargo | Camarón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R20 De las que no Sobran | 355 | cambio-relleno-choclo | recargo | Choclo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R20 De las que no Sobran | 356 | cambio-relleno-palmito | recargo | Palmito | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R20 De las que no Sobran | 357 | cambio-relleno-palta | recargo | Palta | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R20 De las que no Sobran | 358 | cambio-relleno-champinon | recargo | Champiñón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R20 De las que no Sobran | 359 | cambio-relleno-morron | recargo | Morrón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R20 De las que no Sobran | 360 | cambio-envoltura-ciboulette | recargo | O14 Ciboulette | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R20 De las que no Sobran | 361 | cambio-envoltura-frito | recargo | O15 Frito | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R20 De las que no Sobran | 362 | cambio-envoltura-palta | recargo | O12 Palta | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R20 De las que no Sobran | 363 | cambio-envoltura-queso-crema | recargo | O11 Queso crema | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R20 De las que no Sobran | 364 | cambio-envoltura-sesamo | recargo | O13 Sésamo | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R20 De las que no Sobran | 365 | piezas-frio | cantidad_incluida | O25 Frío | 40.00 | Sí | preparacion_global; Foto20 |
| R20 De las que no Sobran | 366 | piezas-frito | cantidad_incluida | O15 Frito | 50.00 | Sí | preparacion_global; Foto20 |
| R20 De las que no Sobran | 466 | incluida-salsa-de-soya | cantidad_incluida | Salsa de soya | 4.00 | Sí | salsa_incluida; Foto31 |
| R20 De las que no Sobran | 467 | incluida-salsa-agridulce | cantidad_incluida | Salsa agridulce | 3.00 | Sí | salsa_incluida; Foto31 |
| R21 Las Mismas de Siempre | 367 | cambio-relleno-pollo | recargo | Pollo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R21 Las Mismas de Siempre | 368 | cambio-relleno-kanikama | recargo | Kanikama | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R21 Las Mismas de Siempre | 369 | cambio-relleno-salmon | recargo | Salmón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R21 Las Mismas de Siempre | 370 | cambio-relleno-atun | recargo | Atún | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R21 Las Mismas de Siempre | 371 | cambio-relleno-camaron | recargo | Camarón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R21 Las Mismas de Siempre | 372 | cambio-relleno-choclo | recargo | Choclo | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R21 Las Mismas de Siempre | 373 | cambio-relleno-palmito | recargo | Palmito | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R21 Las Mismas de Siempre | 374 | cambio-relleno-palta | recargo | Palta | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R21 Las Mismas de Siempre | 375 | cambio-relleno-champinon | recargo | Champiñón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R21 Las Mismas de Siempre | 376 | cambio-relleno-morron | recargo | Morrón | 1000.00 | Sí | modificacion_relleno; Foto2 |
| R21 Las Mismas de Siempre | 377 | cambio-envoltura-ciboulette | recargo | O14 Ciboulette | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R21 Las Mismas de Siempre | 378 | cambio-envoltura-frito | recargo | O15 Frito | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R21 Las Mismas de Siempre | 379 | cambio-envoltura-palta | recargo | O12 Palta | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R21 Las Mismas de Siempre | 380 | cambio-envoltura-queso-crema | recargo | O11 Queso crema | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R21 Las Mismas de Siempre | 381 | cambio-envoltura-sesamo | recargo | O13 Sésamo | 1000.00 | Sí | modificacion_envoltura; Foto2 |
| R21 Las Mismas de Siempre | 382 | piezas-frio | cantidad_incluida | O25 Frío | 40.00 | Sí | preparacion_global; Foto21 |
| R21 Las Mismas de Siempre | 383 | piezas-frito | cantidad_incluida | O15 Frito | 40.00 | Sí | preparacion_global; Foto21 |
| R21 Las Mismas de Siempre | 468 | incluida-salsa-de-soya | cantidad_incluida | Salsa de soya | 4.00 | Sí | salsa_incluida; Foto31 |
| R21 Las Mismas de Siempre | 469 | incluida-salsa-agridulce | cantidad_incluida | Salsa agridulce | 3.00 | Sí | salsa_incluida; Foto31 |
| R23 Hand Roll Tradicional | 384 | premium-salmon | recargo | Salmón | 1000.00 | Sí | Foto23 |
| R23 Hand Roll Tradicional | 385 | premium-atun | recargo | Atún | 1000.00 | Sí | Foto23 |
| R25 3 Hand Rolls | 386 | repeticion-pollo | max_repeticiones | Pollo | 2.00 | Sí | selecciones_personalizadas; Foto26 |
| R25 3 Hand Rolls | 387 | repeticion-kanikama | max_repeticiones | Kanikama | 2.00 | Sí | selecciones_personalizadas; Foto26 |
| R25 3 Hand Rolls | 388 | repeticion-camaron | max_repeticiones | Camarón | 2.00 | Sí | selecciones_personalizadas; Foto26 |
| R25 3 Hand Rolls | 389 | repeticion-salmon | max_repeticiones | Salmón | 1.00 | Sí | selecciones_personalizadas; Foto26 |
| R25 3 Hand Rolls | 390 | repeticion-atun | max_repeticiones | Atún | 1.00 | Sí | selecciones_personalizadas; Foto26 |
| R25 3 Hand Rolls | 391 | repeticion-palta | max_repeticiones | Palta | 2.00 | Sí | selecciones_personalizadas; Foto26 |
| R25 3 Hand Rolls | 392 | repeticion-champinon | max_repeticiones | Champiñón | 2.00 | Sí | selecciones_personalizadas; Foto26 |
| R25 3 Hand Rolls | 393 | repeticion-palmito | max_repeticiones | Palmito | 2.00 | Sí | selecciones_personalizadas; Foto26 |
| R25 3 Hand Rolls | 394 | repeticion-choclo | max_repeticiones | Choclo | 2.00 | Sí | selecciones_personalizadas; Foto26 |
| R25 3 Hand Rolls | 395 | repeticion-pepino | max_repeticiones | Pepino | 2.00 | Sí | selecciones_personalizadas; Foto26 |
| R25 3 Hand Rolls | 396 | repeticion-morron | max_repeticiones | Morrón | 2.00 | Sí | selecciones_personalizadas; Foto26 |
| R26 4 Hand Rolls | 397 | repeticion-pollo | max_repeticiones | Pollo | 2.00 | Sí | selecciones_personalizadas; Foto25 |
| R26 4 Hand Rolls | 398 | repeticion-kanikama | max_repeticiones | Kanikama | 2.00 | Sí | selecciones_personalizadas; Foto25 |
| R26 4 Hand Rolls | 399 | repeticion-camaron | max_repeticiones | Camarón | 2.00 | Sí | selecciones_personalizadas; Foto25 |
| R26 4 Hand Rolls | 400 | repeticion-salmon | max_repeticiones | Salmón | 1.00 | Sí | selecciones_personalizadas; Foto25 |
| R26 4 Hand Rolls | 401 | repeticion-atun | max_repeticiones | Atún | 1.00 | Sí | selecciones_personalizadas; Foto25 |
| R26 4 Hand Rolls | 402 | repeticion-palta | max_repeticiones | Palta | 2.00 | Sí | selecciones_personalizadas; Foto25 |
| R26 4 Hand Rolls | 403 | repeticion-champinon | max_repeticiones | Champiñón | 2.00 | Sí | selecciones_personalizadas; Foto25 |
| R26 4 Hand Rolls | 404 | repeticion-palmito | max_repeticiones | Palmito | 2.00 | Sí | selecciones_personalizadas; Foto25 |
| R26 4 Hand Rolls | 405 | repeticion-choclo | max_repeticiones | Choclo | 2.00 | Sí | selecciones_personalizadas; Foto25 |
| R26 4 Hand Rolls | 406 | repeticion-pepino | max_repeticiones | Pepino | 2.00 | Sí | selecciones_personalizadas; Foto25 |
| R26 4 Hand Rolls | 407 | repeticion-morron | max_repeticiones | Morrón | 2.00 | Sí | selecciones_personalizadas; Foto25 |
| R27 5 Hand Rolls | 408 | repeticion-pollo | max_repeticiones | Pollo | 2.00 | Sí | selecciones_personalizadas; Foto29 |
| R27 5 Hand Rolls | 409 | repeticion-kanikama | max_repeticiones | Kanikama | 2.00 | Sí | selecciones_personalizadas; Foto29 |
| R27 5 Hand Rolls | 410 | repeticion-camaron | max_repeticiones | Camarón | 2.00 | Sí | selecciones_personalizadas; Foto29 |
| R27 5 Hand Rolls | 411 | repeticion-salmon | max_repeticiones | Salmón | 1.00 | Sí | selecciones_personalizadas; Foto29 |
| R27 5 Hand Rolls | 412 | repeticion-atun | max_repeticiones | Atún | 1.00 | Sí | selecciones_personalizadas; Foto29 |
| R27 5 Hand Rolls | 413 | repeticion-palta | max_repeticiones | Palta | 2.00 | Sí | selecciones_personalizadas; Foto29 |
| R27 5 Hand Rolls | 414 | repeticion-champinon | max_repeticiones | Champiñón | 2.00 | Sí | selecciones_personalizadas; Foto29 |
| R27 5 Hand Rolls | 415 | repeticion-palmito | max_repeticiones | Palmito | 2.00 | Sí | selecciones_personalizadas; Foto29 |
| R27 5 Hand Rolls | 416 | repeticion-choclo | max_repeticiones | Choclo | 2.00 | Sí | selecciones_personalizadas; Foto29 |
| R27 5 Hand Rolls | 417 | repeticion-pepino | max_repeticiones | Pepino | 2.00 | Sí | selecciones_personalizadas; Foto29 |
| R27 5 Hand Rolls | 418 | repeticion-morron | max_repeticiones | Morrón | 2.00 | Sí | selecciones_personalizadas; Foto29 |
| R33 20 Piezas a Elección | 419 | repeticion-pollo | max_repeticiones | Pollo | 2.00 | Sí | selecciones_personalizadas; Foto39 |
| R33 20 Piezas a Elección | 420 | repeticion-camaron | max_repeticiones | Camarón | 2.00 | Sí | selecciones_personalizadas; Foto39 |
| R33 20 Piezas a Elección | 421 | repeticion-kanikama | max_repeticiones | Kanikama | 2.00 | Sí | selecciones_personalizadas; Foto39 |
| R33 20 Piezas a Elección | 422 | repeticion-salmon | max_repeticiones | Salmón | 1.00 | Sí | selecciones_personalizadas; Foto39 |
| R33 20 Piezas a Elección | 423 | repeticion-palmito | max_repeticiones | Palmito | 2.00 | Sí | selecciones_personalizadas; Foto39 |
| R33 20 Piezas a Elección | 424 | repeticion-champinon | max_repeticiones | Champiñón | 2.00 | Sí | selecciones_personalizadas; Foto39 |
| R33 20 Piezas a Elección | 425 | repeticion-choclo | max_repeticiones | Choclo | 2.00 | Sí | selecciones_personalizadas; Foto39 |
| R33 20 Piezas a Elección | 426 | repeticion-palta | max_repeticiones | Palta | 2.00 | Sí | selecciones_personalizadas; Foto39 |
| R33 20 Piezas a Elección | 427 | cambio-relleno-pollo | recargo | Pollo | 1000.00 | Sí | modificacion_relleno; Foto39 |
| R33 20 Piezas a Elección | 428 | cambio-relleno-camaron | recargo | Camarón | 1000.00 | Sí | modificacion_relleno; Foto39 |
| R33 20 Piezas a Elección | 429 | cambio-relleno-kanikama | recargo | Kanikama | 1000.00 | Sí | modificacion_relleno; Foto39 |
| R33 20 Piezas a Elección | 430 | cambio-relleno-salmon | recargo | Salmón | 1000.00 | Sí | modificacion_relleno; Foto39 |
| R33 20 Piezas a Elección | 431 | cambio-relleno-palmito | recargo | Palmito | 1000.00 | Sí | modificacion_relleno; Foto39 |
| R33 20 Piezas a Elección | 432 | cambio-relleno-champinon | recargo | Champiñón | 1000.00 | Sí | modificacion_relleno; Foto39 |
| R33 20 Piezas a Elección | 433 | cambio-relleno-choclo | recargo | Choclo | 1000.00 | Sí | modificacion_relleno; Foto39 |
| R33 20 Piezas a Elección | 434 | cambio-relleno-palta | recargo | Palta | 1000.00 | Sí | modificacion_relleno; Foto39 |
| R33 20 Piezas a Elección | 435 | cambio-envoltura-frito | recargo | O88 Frito | 1000.00 | Sí | modificacion_envoltura; Foto39 |
| R33 20 Piezas a Elección | 436 | cambio-envoltura-palta | recargo | O90 Palta | 1000.00 | Sí | modificacion_envoltura; Foto39 |
| R33 20 Piezas a Elección | 437 | cambio-envoltura-queso-crema | recargo | O89 Queso crema | 1000.00 | Sí | modificacion_envoltura; Foto39 |
| R33 20 Piezas a Elección | 478 | incluida-salsa-de-soya | cantidad_incluida | Salsa de soya | 1.00 | Sí | salsa_incluida; Foto31 |
| R33 20 Piezas a Elección | 479 | incluida-salsa-agridulce | cantidad_incluida | Salsa agridulce | 1.00 | Sí | salsa_incluida; Foto31 |

## Anexo de ingredientes

| ID | Nombre | Tipo |
|---|---|---|
| 1 | Pollo | proteina |
| 2 | Kanikama | proteina |
| 3 | Salmón | proteina |
| 4 | Atún | proteina |
| 5 | Camarón | proteina |
| 6 | Choclo | vegetal |
| 7 | Palmito | vegetal |
| 8 | Palta | vegetal |
| 9 | Champiñón | vegetal |
| 10 | Morrón | vegetal |
| 11 | Queso crema | base |
| 12 | Cebollín | base |
| 13 | Sésamo | topping |
| 14 | Ciboulette | topping |
| 15 | Pepino | vegetal |
| 16 | Pollo apanado | proteina |
| 17 | Camarón apanado | proteina |
| 18 | Salsa de soya | salsa |
| 19 | Salsa agridulce | salsa |
| 20 | Jamón serrano | envoltura |
| 21 | Almendras | topping |
| 22 | Salmón ahumado | proteina |
| 23 | Arroz | base |

## Apariciones de nombres e importes comerciales

Búsqueda rg en app/, resources/, routes/, config/, public/ y database/, excluyendo ZIP y sourcemaps binarios. Cada línea coincidente se clasifica abajo; artefactos minificados se agrupan por línea y se acorta su extracto, sin perder referencia. Las pruebas y documentos son fuentes de comprobación, no lógica comercial ejecutable. Los seeders de importación y sus alias son correctos, no hardcode problemático.

| Archivo/línea | Clasificación | Extracto |
|---|---|---|
| config/delivery.php:5 | Configuración comercial central: correcta | 'local_price' => 2500, |
| database/seeders/SushiAkilaMenuBatch04Seeder.php:31 | Seeder/identidad de importación: correcta | foreach (['Salsa de soya' => '500.00', 'Salsa agridulce' => '800.00'] as $name => $price) { |
| database/seeders/SushiAkilaMenuBatch04Seeder.php:44 | Seeder/identidad de importación: correcta | foreach (array_combine(['Salsa de soya', 'Salsa agridulce'], $counts) as $name => $count) { |
| app/Services/ProductPricingService.php:84 | Hardcode comercial legacy pendiente: detección por nombre | $limit = str_contains(mb_strtolower($ingredient->nombre), 'salm') ? 1 : 2; |
| app/Services/ProductPricingService.php:86 | Hardcode comercial legacy pendiente: recargo fijo | $surcharge += max(0, $count - ($baseCounts[$id] ?? 0)) * 1000; |
| app/Services/ProductPricingService.php:102 | Correcta: etiqueta, estructura o compatibilidad histórica | 'Sin queso' => $config['cream_cheese'], 'Sin cebollín' => $config['scallions'], |
| database/seeders/SushiAkila/MenuIdentity.php:18 | Seeder/identidad de importación: correcta | 'camarones' => 'camaron', 'proteinas' => 'proteina', |
| database/seeders/SushiAkila/MenuIdentity.php:19 | Seeder/identidad de importación: correcta | 'pollo-apanada' => 'pollo-apanado', 'pollo-crispy' => 'pollo-apanado', |
| database/seeders/SushiAkila/MenuIdentity.php:20 | Seeder/identidad de importación: correcta | 'soya' => 'salsa-de-soya', 'soja' => 'salsa-de-soya', |
| database/seeders/SushiAkila/MenuIdentity.php:21 | Seeder/identidad de importación: correcta | 'salsa-soja' => 'salsa-de-soya', 'salsa-de-soja' => 'salsa-de-soya', 'salsa-soya' => 'salsa-de-soya', |
| database/seeders/SushiAkila/MenuIdentity.php:28 | Seeder/identidad de importación: correcta | 'pollo-apanado' => 'Pollo apanado', 'camaron-apanado' => 'Camarón apanado', |
| database/seeders/SushiAkila/MenuIdentity.php:29 | Seeder/identidad de importación: correcta | 'salsa-de-soya' => 'Salsa de soya', 'salsa-agridulce' => 'Salsa agridulce', |
| public/build/assets/app-HZdVjPll.css:5 | Compilado derivado de fuentes; no autoridad comercial | */:root,[data-bs-theme=light]{--bs-blue:#0d6efd;--bs-indigo:#6610f2;--bs-purple:#6f42c1;--bs-pink:#d63384;--bs-red:#dc3545;--bs-orange:#fd7e14;--bs-yellow:#ffc107;--bs-green:#19875… |
| app/Services/MercadoPagoWebhookSignature.php:29 | Conversión milisegundos/segundos: correcta, no precio | $seconds = strlen($ts) > 10 ? (int) floor((float) $ts / 1000) : (int) $ts; |
| database/seeders/SushiAkila/MenuBatchImporter.php:87 | Seeder/identidad de importación: correcta | foreach (['Frito', 'Palta', 'Queso crema'] as $name) { |
| database/seeders/SushiAkila/MenuBatchImporter.php:237 | Seeder/identidad de importación: correcta | ['ingrediente_id' => $this->ingredients[$name]->id], $data['limites_repeticion'][$name] ?? ($name === 'Salmón' ? 1 : 2), |
| database/seeders/SushiAkila/MenuBatchImporter.php:243 | Seeder/identidad de importación: correcta | ['ingrediente_id' => $this->ingredients[$name]->id], '1000.00', |
| database/seeders/SushiAkila/MenuBatchImporter.php:252 | Seeder/identidad de importación: correcta | $this->rule($product, 'cambio-envoltura-'.$option->slug, 'recargo', ['opcion_id' => $option->id], '1000.00', |
| database/seeders/SushiAkila/Batch05Data.php:10 | Seeder/identidad de importación: correcta | public const GOHAN_BASE = ['Arroz', 'Queso crema', 'Ciboulette', 'Palta', 'Sésamo']; |
| database/seeders/SushiAkila/Batch05Data.php:17 | Seeder/identidad de importación: correcta | 'min_selections' => 1, 'max_selections' => 2, 'opciones' => ['Primavera', 'Jamón Queso']]]; |
| database/seeders/SushiAkila/Batch05Data.php:30 | Seeder/identidad de importación: correcta | 'descripcion' => '8 unidades. Sabores Primavera y Jamón Queso. Se pueden pedir mixtos, sin recargo. La proporción por sabor se coordina al preparar; no se establece una distribució… |
| database/seeders/SushiAkila/Batch05Data.php:42 | Seeder/identidad de importación: correcta | 'descripcion' => 'Base confirmada: arroz, queso crema, ciboulette, palta y sésamo. '.self::GOHAN_PENDING.'. Disponible opción vegetariana; pendiente de definición operativa, sin su… |
| database/seeders/SushiAkila/Batch04Data.php:12 | Seeder/identidad de importación: correcta | return ['Pollo apanado' => 'proteina', 'Camarón apanado' => 'proteina', |
| database/seeders/SushiAkila/Batch04Data.php:13 | Seeder/identidad de importación: correcta | 'Salsa de soya' => 'salsa', 'Salsa agridulce' => 'salsa', |
| database/seeders/SushiAkila/Batch04Data.php:14 | Seeder/identidad de importación: correcta | 'Jamón serrano' => 'envoltura', 'Almendras' => 'topping', 'Salmón ahumado' => 'proteina']; |
| database/seeders/SushiAkila/Batch04Data.php:19 | Seeder/identidad de importación: correcta | return array_fill_keys(['Camarón', 'Pollo', 'Kanikama', 'Choclo', 'Palmito', 'Palta', 'Champiñón'], '1000.00') |
| database/seeders/SushiAkila/Batch04Data.php:20 | Seeder/identidad de importación: correcta | + array_fill_keys(['Salmón', 'Pollo apanado', 'Camarón apanado', 'Atún'], '2000.00'); |
| database/seeders/SushiAkila/Batch04Data.php:41 | Seeder/identidad de importación: correcta | 'relleno-akilaburger' => ['nombre' => 'Elección adicional', 'opciones' => ['Pollo', 'Kanikama', 'Salmón', 'Atún', 'Camarón', 'Choclo', 'Palmito', 'Palta', 'Champiñón']], |
| database/seeders/SushiAkila/Batch04Data.php:49 | Seeder/identidad de importación: correcta | 'envolturas_declaradas' => ['Queso crema', 'Palta', 'Sésamo'], |
| database/seeders/SushiAkila/Batch04Data.php:50 | Seeder/identidad de importación: correcta | 'descripcion' => $prior['mi-favorita']['descripcion'].' Envolturas declaradas: queso crema, palta y sésamo.']); |
| database/seeders/SushiAkila/Batch04Data.php:55 | Seeder/identidad de importación: correcta | 'nikkei-jamon-serrano' => self::nikkei('Nikkei Jamón Serrano', 'Pollo apanado', 33, ['Jamón serrano']), |
| database/seeders/SushiAkila/Batch04Data.php:56 | Seeder/identidad de importación: correcta | 'nikkei-pollo-crispy' => self::nikkei('Nikkei Pollo Crispy', 'Pollo apanado', 34), |
| database/seeders/SushiAkila/Batch04Data.php:57 | Seeder/identidad de importación: correcta | 'nikkei-camaron-apanado' => self::nikkei('Nikkei Camarón Apanado', 'Camarón apanado', 35, ['Almendras']) + ['grupos_producto' => ['envoltura-nikkei-almendras']], |
| database/seeders/SushiAkila/Batch04Data.php:58 | Seeder/identidad de importación: correcta | 'nikkei-salmon-ahumado' => self::nikkei('Nikkei Salmón Ahumado', 'Salmón ahumado', 36), |
| database/seeders/SushiAkila/Batch04Data.php:61 | Seeder/identidad de importación: correcta | 'nombre' => '20 Piezas a Elección', 'precio' => '10000.00', 'foto' => 39, 'foto_reglas' => 39, 'orden' => 39, |
| database/seeders/SushiAkila/Batch04Data.php:65 | Seeder/identidad de importación: correcta | 'grupo_envoltura' => 'envoltura-20-piezas-a-eleccion', 'envolturas_disponibles' => ['Frito', 'Queso crema', 'Palta'], |
| database/seeders/SushiAkila/Batch04Data.php:66 | Seeder/identidad de importación: correcta | 'descripcion' => '2 rolls de 10 piezas. Una proteína o un vegetal por roll. Base de queso crema y cebollín. Envolturas disponibles: frito, queso crema y palta. Máximo dos repeticio… |
| database/seeders/SushiAkila/Batch04Data.php:73 | Seeder/identidad de importación: correcta | 'composicion_fija' => ['Queso crema', 'Cebollín', 'Palta'], 'grupos_producto' => ['relleno-akilaburger'], |
| database/seeders/SushiAkila/Batch04Data.php:74 | Seeder/identidad de importación: correcta | 'descripcion' => 'Base fija de queso crema, cebollín y palta, más una proteína o un vegetal a elección.', |
| database/seeders/SushiAkila/Batch04Data.php:85 | Seeder/identidad de importación: correcta | 'piezas' => 10, 'bloques' => [], 'preparacion' => [], 'base' => ['Queso crema'], |
| database/seeders/SushiAkila/Batch04Data.php:86 | Seeder/identidad de importación: correcta | 'composicion_fija' => array_merge([$protein, 'Palta', 'Queso crema'], $wrappers), |
| database/seeders/SushiAkila/Batch04Data.php:88 | Seeder/identidad de importación: correcta | 'descripcion' => '10 piezas de '.mb_strtolower($protein).', palta y queso crema. '.($pending |
| database/seeders/SushiAkila/Batch03Data.php:10 | Seeder/identidad de importación: correcta | public const PROTEINS = ['Pollo', 'Kanikama', 'Camarón', 'Salmón', 'Atún']; |
| database/seeders/SushiAkila/Batch03Data.php:12 | Seeder/identidad de importación: correcta | public const VEGETABLES = ['Palta', 'Champiñón', 'Palmito', 'Choclo', 'Pepino', 'Morrón']; |
| database/seeders/SushiAkila/Batch03Data.php:20 | Seeder/identidad de importación: correcta | 'relleno-hand-roll' => ['nombre' => 'Relleno', 'opciones' => $fillings], |
| database/seeders/SushiAkila/Batch03Data.php:21 | Seeder/identidad de importación: correcta | 'relleno-muchupleto' => ['nombre' => 'Elección adicional', 'opciones' => array_merge(self::PROTEINS, ['Choclo', 'Palmito', 'Palta', 'Champiñón'])], |
| database/seeders/SushiAkila/Batch03Data.php:22 | Seeder/identidad de importación: correcta | 'segundo-ingrediente' => ['nombre' => 'Segundo ingrediente', 'opciones' => $fillings, 'recargo' => '1000.00'], |
| database/seeders/SushiAkila/Batch03Data.php:31 | Seeder/identidad de importación: correcta | $fixedFillings = ['Pollo', 'Pollo', 'Camarón', 'Kanikama', 'Choclo', 'Champiñón', 'Salmón', 'Palmito']; |
| database/seeders/SushiAkila/Batch03Data.php:38 | Seeder/identidad de importación: correcta | 'envolturas_declaradas' => ['Queso crema', 'Palta', 'Sésamo', 'Ciboulette'], |
| database/seeders/SushiAkila/Batch03Data.php:39 | Seeder/identidad de importación: correcta | 'descripcion' => '80 piezas: 20 pollo; 10 camarón, kanikama, choclo, champiñón, salmón y palmito. 40 frías y 40 fritas, sin distribución individual confirmada. Envolturas declarada… |
| database/seeders/SushiAkila/Batch03Data.php:43 | Seeder/identidad de importación: correcta | 'composicion_fija' => ['Queso crema', 'Cebollín', 'Palta', 'Pollo'], |
| database/seeders/SushiAkila/Batch03Data.php:45 | Seeder/identidad de importación: correcta | 'descripcion' => 'Base fija de queso crema, cebollín, palta y pollo, más una proteína o un vegetal a elección.', |
| database/seeders/SushiAkila/Batch03Data.php:47 | Seeder/identidad de importación: correcta | 'hand-roll-tradicional' => self::individual('Hand Roll Tradicional', '3990.00', 28, 23) + [ |
| database/seeders/SushiAkila/Batch03Data.php:48 | Seeder/identidad de importación: correcta | 'grupos_producto' => ['relleno-hand-roll'], 'foto_reglas' => 23, |
| database/seeders/SushiAkila/Batch03Data.php:49 | Seeder/identidad de importación: correcta | 'recargos_ingredientes' => ['Salmón' => '1000.00', 'Atún' => '1000.00'], |
| database/seeders/SushiAkila/Batch03Data.php:51 | Seeder/identidad de importación: correcta | 'hand-roll-relleno-doble' => self::individual('Hand Roll Relleno Doble', '4990.00', 28, 24) + [ |
| database/seeders/SushiAkila/Batch03Data.php:54 | Seeder/identidad de importación: correcta | '3-hand-rolls' => self::combo(3, '11000.00', 26, 25), |
| database/seeders/SushiAkila/Batch03Data.php:55 | Seeder/identidad de importación: correcta | '4-hand-rolls' => self::combo(4, '14990.00', 25, 26), |
| database/seeders/SushiAkila/Batch03Data.php:56 | Seeder/identidad de importación: correcta | '5-hand-rolls' => self::combo(5, '18000.00', 29, 27) + ['segundo_ingrediente' => 'segundo-ingrediente'], |
| database/seeders/SushiAkila/Batch03Data.php:57 | Seeder/identidad de importación: correcta | '4-hand-rolls-pollo-palta' => array_replace(self::combo(4, '18000.00', 24, 28), [ |
| database/seeders/SushiAkila/Batch03Data.php:58 | Seeder/identidad de importación: correcta | 'nombre' => '4 Hand Rolls Pollo Palta', 'personalizable' => false, 'repeticiones' => false, |
| database/seeders/SushiAkila/Batch03Data.php:59 | Seeder/identidad de importación: correcta | 'composicion_fija' => ['Queso crema', 'Cebollín', 'Pollo', 'Palta'], |
| database/seeders/SushiAkila/Batch03Data.php:60 | Seeder/identidad de importación: correcta | 'bloques' => array_fill(0, 4, ['relleno' => ['Pollo', 'Palta']]), |
| database/seeders/SushiAkila/Batch03Data.php:61 | Seeder/identidad de importación: correcta | 'descripcion' => '4 Hand Rolls de composición fija: queso crema, cebollín, pollo y palta por unidad.', |
| database/seeders/SushiAkila/Batch03Data.php:70 | Seeder/identidad de importación: correcta | 'categoria' => 'Hand Rolls', 'categoria_orden' => 3, 'unidad_tipo' => 'hand_roll', |
| database/seeders/SushiAkila/Batch03Data.php:73 | Seeder/identidad de importación: correcta | 'descripcion' => $name.'. Base de queso crema y cebollín. '.($name === 'Hand Roll Relleno Doble' ? 'Una proteína y un vegetal obligatorios.' : 'Una proteína o un vegetal a elección… |
| database/seeders/SushiAkila/Batch03Data.php:78 | Seeder/identidad de importación: correcta | return ['nombre' => $units.' Hand Rolls', 'precio' => $price, 'foto' => $photo, 'orden' => $order, |
| database/seeders/SushiAkila/Batch03Data.php:79 | Seeder/identidad de importación: correcta | 'categoria' => 'Hand Rolls', 'categoria_orden' => 3, 'unidad_tipo' => 'hand_roll', 'unidades' => $units, |
| database/seeders/SushiAkila/Batch03Data.php:82 | Seeder/identidad de importación: correcta | 'grupo_relleno' => 'relleno-hand-roll', 'rellenos_disponibles' => array_merge(self::PROTEINS, self::VEGETABLES), |
| database/seeders/SushiAkila/Batch03Data.php:83 | Seeder/identidad de importación: correcta | 'envolturas_disponibles' => [], 'repeticiones' => true, 'limites_repeticion' => ['Salmón' => 1, 'Atún' => 1], |
| database/seeders/SushiAkila/Batch03Data.php:85 | Seeder/identidad de importación: correcta | 'descripcion' => $units.' Hand Rolls. Base de queso crema y cebollín, más una proteína o un vegetal por unidad. Máximo dos repeticiones; salmón y atún máximo una vez.']; |
| database/seeders/SushiAkila/Batch02Data.php:16 | Seeder/identidad de importación: correcta | 'Pollo' => 10, 'Camarón' => 10, 'Kanikama' => 10, 'Choclo' => 10, 'Champiñón' => 10, 'Palmito' => 10, |
| database/seeders/SushiAkila/Batch02Data.php:17 | Seeder/identidad de importación: correcta | ], ['frito' => 30], ['Queso crema', 'Palta', 'Sésamo']), |
| database/seeders/SushiAkila/Batch02Data.php:18 | Seeder/identidad de importación: correcta | '60-piezas-a-eleccion' => self::choice(60, '25990.00', 13, 14, 30, ['Queso crema', 'Palta', 'Sésamo']), |
| database/seeders/SushiAkila/Batch02Data.php:19 | Seeder/identidad de importación: correcta | '50-piezas-a-eleccion' => self::choice(50, '20990.00', 14, 15, 20, ['Queso crema', 'Palta', 'Sésamo']), |
| database/seeders/SushiAkila/Batch02Data.php:21 | Seeder/identidad de importación: correcta | 'Pollo' => 20, 'Camarón' => 10, 'Palta' => 10, |
| database/seeders/SushiAkila/Batch02Data.php:22 | Seeder/identidad de importación: correcta | ], ['frito' => 20], ['Queso crema', 'Palta']) + ['reglas_superadas' => ['piezas-envuelto']], |
| database/seeders/SushiAkila/Batch02Data.php:25 | Seeder/identidad de importación: correcta | 'descripcion' => '10 piezas de pollo fritas; 10 de camarón envueltas en palta; 10 de palta envueltas en queso crema; 10 de kanikama fritas. Base de queso crema y cebollín.', |
| database/seeders/SushiAkila/Batch02Data.php:27 | Seeder/identidad de importación: correcta | ['relleno' => ['Pollo'], 'envoltura' => 'Frito'], |
| database/seeders/SushiAkila/Batch02Data.php:28 | Seeder/identidad de importación: correcta | ['relleno' => ['Camarón'], 'envoltura' => 'Palta'], |
| database/seeders/SushiAkila/Batch02Data.php:29 | Seeder/identidad de importación: correcta | ['relleno' => ['Palta'], 'envoltura' => 'Queso crema'], |
| database/seeders/SushiAkila/Batch02Data.php:35 | Seeder/identidad de importación: correcta | 'Pollo' => 30, 'Camarón' => 20, 'Kanikama' => 20, 'Choclo' => 20, 'Champiñón' => 20, |
| database/seeders/SushiAkila/Batch02Data.php:36 | Seeder/identidad de importación: correcta | 'Salmón' => 10, 'Palmito' => 10, 'Palta' => 20, |
| database/seeders/SushiAkila/Batch02Data.php:37 | Seeder/identidad de importación: correcta | ], ['frio' => 60, 'frito' => 90], ['Queso crema', 'Palta', 'Sésamo', 'Ciboulette']), |
| database/seeders/SushiAkila/Batch02Data.php:39 | Seeder/identidad de importación: correcta | 'Pollo' => 20, 'Camarón' => 10, 'Kanikama' => 20, 'Choclo' => 10, 'Champiñón' => 10, |
| database/seeders/SushiAkila/Batch02Data.php:40 | Seeder/identidad de importación: correcta | 'Salmón' => 10, 'Palmito' => 10, 'Palta' => 10, |
| database/seeders/SushiAkila/Batch02Data.php:41 | Seeder/identidad de importación: correcta | ], ['frio' => 40, 'frito' => 60], ['Queso crema', 'Palta', 'Sésamo', 'Ciboulette']), |
| database/seeders/SushiAkila/Batch02Data.php:43 | Seeder/identidad de importación: correcta | 'Pollo' => 20, 'Camarón' => 10, 'Kanikama' => 20, 'Choclo' => 10, 'Champiñón' => 10, |
| database/seeders/SushiAkila/Batch02Data.php:44 | Seeder/identidad de importación: correcta | 'Salmón' => 10, 'Palmito' => 10, |
| database/seeders/SushiAkila/Batch02Data.php:45 | Seeder/identidad de importación: correcta | ], ['frio' => 40, 'frito' => 50], ['Queso crema', 'Palta', 'Sésamo', 'Ciboulette']), |
| database/seeders/SushiAkila/Batch02Data.php:64 | Seeder/identidad de importación: correcta | 'descripcion' => $text.'. '.$counts.', sin distribución individual confirmada. Envolturas declaradas: '.implode(', ', $wrappers).'. Base de queso crema y cebollín.']; |
| database/seeders/SushiAkila/Batch02Data.php:74 | Seeder/identidad de importación: correcta | 'rellenos_disponibles' => ['Pollo', 'Kanikama', 'Camarón', 'Salmón', 'Choclo', 'Palmito', 'Champiñón', 'Palta'], |
| database/seeders/SushiAkila/Batch02Data.php:76 | Seeder/identidad de importación: correcta | 'descripcion' => intdiv($pieces, 10).' rolls de 10 piezas. Un ingrediente por roll: proteína o vegetal. Base de queso crema y cebollín, removible y no reemplazable. '.$fried.' piez… |
| app/Models/Promocion.php:19 | Correcta: etiqueta, estructura o compatibilidad histórica | return $this->belongsToMany(Producto::class, 'promocion_handroll')->withPivot('cantidad'); |
| database/seeders/SushiAkila/Batch01Data.php:8 | Seeder/identidad de importación: correcta | public const PROTEINS = ['Pollo', 'Kanikama', 'Salmón', 'Atún', 'Camarón']; |
| database/seeders/SushiAkila/Batch01Data.php:10 | Seeder/identidad de importación: correcta | public const VEGETABLES = ['Choclo', 'Palmito', 'Palta', 'Champiñón', 'Morrón']; |
| database/seeders/SushiAkila/Batch01Data.php:12 | Seeder/identidad de importación: correcta | public const BASE = ['Queso crema', 'Cebollín']; |
| database/seeders/SushiAkila/Batch01Data.php:14 | Seeder/identidad de importación: correcta | public const WRAPPERS = ['Queso crema', 'Palta', 'Sésamo', 'Ciboulette', 'Frito']; |
| database/seeders/SushiAkila/Batch01Data.php:16 | Seeder/identidad de importación: correcta | public const CHOICE_FILLINGS = ['Pollo', 'Camarón', 'Kanikama', 'Salmón', 'Palmito', 'Champiñón', 'Choclo', 'Palta']; |
| database/seeders/SushiAkila/Batch01Data.php:33 | Seeder/identidad de importación: correcta | 'nombre' => 'Goloso', 'precio' => '10000.00', 'piezas' => 20, 'foto' => 1, |
| database/seeders/SushiAkila/Batch01Data.php:34 | Seeder/identidad de importación: correcta | 'descripcion' => '20 piezas de pollo y palta fritas. Base de queso crema y cebollín.', |
| database/seeders/SushiAkila/Batch01Data.php:35 | Seeder/identidad de importación: correcta | 'bloques' => array_fill(0, 2, ['relleno' => ['Pollo', 'Palta'], 'envoltura' => 'Frito']), |
| database/seeders/SushiAkila/Batch01Data.php:40 | Seeder/identidad de importación: correcta | 'descripcion' => '30 piezas de pollo fritas. Base de queso crema y cebollín.', |
| database/seeders/SushiAkila/Batch01Data.php:41 | Seeder/identidad de importación: correcta | 'bloques' => array_fill(0, 3, ['relleno' => ['Pollo'], 'envoltura' => 'Frito']), |
| database/seeders/SushiAkila/Batch01Data.php:46 | Seeder/identidad de importación: correcta | 'descripcion' => '20 piezas de pollo, 10 de camarón y 10 de palta. 20 envueltas y 20 fritas, sin distribución individual confirmada. Base de queso crema y cebollín.', |
| database/seeders/SushiAkila/Batch01Data.php:47 | Seeder/identidad de importación: correcta | 'bloques' => self::fillings(['Pollo', 'Pollo', 'Camarón', 'Palta']), |
| database/seeders/SushiAkila/Batch01Data.php:52 | Seeder/identidad de importación: correcta | 'descripcion' => '20 piezas de pollo, 10 de camarón, 10 de kanikama y 10 de choclo. 30 envueltas y 20 fritas, sin distribución individual confirmada. Base de queso crema y cebollín… |
| database/seeders/SushiAkila/Batch01Data.php:53 | Seeder/identidad de importación: correcta | 'bloques' => self::fillings(['Pollo', 'Pollo', 'Camarón', 'Kanikama', 'Choclo']), |
| database/seeders/SushiAkila/Batch01Data.php:57 | Seeder/identidad de importación: correcta | 'nombre' => 'Promo Hot', 'precio' => '20990.00', 'piezas' => 60, 'foto' => 1, |
| database/seeders/SushiAkila/Batch01Data.php:58 | Seeder/identidad de importación: correcta | 'descripcion' => '20 piezas de pollo, 10 de camarón, 10 de kanikama, 10 de choclo y 10 de champiñón. 60 piezas fritas. Base de queso crema y cebollín.', |
| database/seeders/SushiAkila/Batch01Data.php:59 | Seeder/identidad de importación: correcta | 'bloques' => array_map(fn ($block) => $block + ['envoltura' => 'Frito'], self::fillings(['Pollo', 'Pollo', 'Camarón', 'Kanikama', 'Choclo', 'Champiñón'])), |
| database/seeders/SushiAkila/Batch01Data.php:64 | Seeder/identidad de importación: correcta | 'descripcion' => '20 piezas de pollo, 10 de camarón, 20 de kanikama, 10 de choclo y 10 de champiñón. 30 frías y 40 fritas, sin distribución individual confirmada. Base de queso cre… |
| database/seeders/SushiAkila/Batch01Data.php:65 | Seeder/identidad de importación: correcta | 'bloques' => self::fillings(['Pollo', 'Pollo', 'Camarón', 'Kanikama', 'Kanikama', 'Choclo', 'Champiñón']), |
| database/seeders/SushiAkila/Batch01Data.php:70 | Seeder/identidad de importación: correcta | 'descripcion' => '3 rolls de 10 piezas. Un ingrediente por roll: pollo, camarón, kanikama, salmón, palmito, champiñón, choclo o palta. Base de queso crema y cebollín, removible y n… |
| database/seeders/SushiAkila/Batch01Data.php:76 | Seeder/identidad de importación: correcta | ['relleno' => ['Pollo'], 'envoltura' => 'Frito'], |
| database/seeders/SushiAkila/Batch01Data.php:77 | Seeder/identidad de importación: correcta | ['relleno' => ['Camarón'], 'envoltura' => 'Palta'], |
| database/seeders/SushiAkila/Batch01Data.php:78 | Seeder/identidad de importación: correcta | ['relleno' => ['Palta'], 'envoltura' => 'Queso crema'], |
| database/seeders/SushiAkila/Batch01Data.php:81 | Seeder/identidad de importación: correcta | ['relleno' => ['Pollo'], 'envoltura' => 'Frito'], |
| database/seeders/SushiAkila/Batch01Data.php:82 | Seeder/identidad de importación: correcta | ['relleno' => ['Champiñón'], 'envoltura' => 'Palta'], |
| database/seeders/SushiAkila/Batch01Data.php:83 | Seeder/identidad de importación: correcta | ['relleno' => ['Palta'], 'envoltura' => 'Queso crema'], |
| database/seeders/SushiAkila/Batch01Data.php:86 | Seeder/identidad de importación: correcta | ['relleno' => ['Pollo'], 'envoltura' => 'Frito'], |
| database/seeders/SushiAkila/Batch01Data.php:87 | Seeder/identidad de importación: correcta | ['relleno' => ['Kanikama'], 'envoltura' => 'Palta'], |
| database/seeders/SushiAkila/Batch01Data.php:88 | Seeder/identidad de importación: correcta | ['relleno' => ['Palta'], 'envoltura' => 'Queso crema'], |
| database/seeders/SushiAkila/Batch01Data.php:91 | Seeder/identidad de importación: correcta | ['relleno' => ['Camarón'], 'envoltura' => 'Frito'], |
| database/seeders/SushiAkila/Batch01Data.php:92 | Seeder/identidad de importación: correcta | ['relleno' => ['Kanikama'], 'envoltura' => 'Queso crema'], |
| database/seeders/SushiAkila/Batch01Data.php:93 | Seeder/identidad de importación: correcta | ['relleno' => ['Salmón'], 'envoltura' => 'Palta'], |
| database/seeders/SushiAkila/Batch01Data.php:97 | Seeder/identidad de importación: correcta | ['relleno' => ['Choclo'], 'envoltura' => 'Queso crema'], |
| database/seeders/SushiAkila/Batch01Data.php:98 | Seeder/identidad de importación: correcta | ['relleno' => ['Palmito'], 'envoltura' => 'Palta'], |
| database/seeders/SushiAkila/Batch01Data.php:112 | Seeder/identidad de importación: correcta | 'descripcion' => implode('; ', array_map(fn ($b) => '10 piezas de '.implode(' y ', $b['relleno']).($b['envoltura'] === 'Frito' ? ' fritas' : ' envueltas en '.$b['envoltura']), $blo… |
| database/seeders/ProductoSeeder.php:13 | Seeder demo/legacy: pendiente de retirar; no usar para menú real | // Obtener la categoría “Hand Roll” |
| database/seeders/ProductoSeeder.php:14 | Seeder demo/legacy: pendiente de retirar; no usar para menú real | $categoria = Categoria::where('slug','hand-roll')->first(); |
| database/seeders/ProductoSeeder.php:17 | Seeder demo/legacy: pendiente de retirar; no usar para menú real | 'nombre'         => 'Hand Roll de Salmón', |
| database/seeders/ProductoSeeder.php:18 | Seeder demo/legacy: pendiente de retirar; no usar para menú real | 'descripcion'    => 'Delicioso hand roll con salmón fresco, queso crema y cebollín.', |
| database/seeders/ProductoSeeder.php:19 | Seeder demo/legacy: pendiente de retirar; no usar para menú real | 'precio'         => 10000, |
| database/seeders/IngredienteProductoSeeder.php:15 | Seeder demo/legacy: pendiente de retirar; no usar para menú real | // Ejemplo para los Hand Rolls (puedes extender a todos los productos según su tipo) |
| database/seeders/IngredienteProductoSeeder.php:16 | Seeder demo/legacy: pendiente de retirar; no usar para menú real | $handRolls = Producto::whereIn('slug', ['3-hand-rolls','4-hand-rolls','5-hand-rolls','tradicional','relleno-doble','xl','lo-nuevo'])->get(); |
| database/seeders/IngredienteProductoSeeder.php:20 | Seeder demo/legacy: pendiente de retirar; no usar para menú real | foreach ($handRolls as $prod) { |
| database/seeders/IngredienteProductoSeeder.php:23 | Seeder demo/legacy: pendiente de retirar; no usar para menú real | // Base queso crema y cebollín sin límite (null) |
| database/seeders/IngredienteProductoSeeder.php:24 | Seeder demo/legacy: pendiente de retirar; no usar para menú real | foreach (['queso crema','cebollín'] as $base) { |
| database/seeders/IngredienteProductoSeeder.php:33 | Seeder demo/legacy: pendiente de retirar; no usar para menú real | // Proteínas: max 2, pero salmón/atún/carne sólo 1 |
| database/seeders/IngredienteProductoSeeder.php:36 | Seeder demo/legacy: pendiente de retirar; no usar para menú real | $limite = in_array($ing->nombre, ['Salmon','Atún','Carne']) ? 1 : 2; |
| app/Models/BloquePromocion.php:25 | Correcta: etiqueta, estructura o compatibilidad histórica | 'nombre' => 'required\|string\|max:255', 'unidad_tipo' => 'required\|in:pieza,hand_roll,unidad', |
| database/seeders/DatabaseSeeder.php:17 | Seeder demo/legacy: pendiente de retirar; no usar para menú real | ['slug' => 'hand-roll'], |
| database/seeders/DatabaseSeeder.php:18 | Seeder demo/legacy: pendiente de retirar; no usar para menú real | ['nombre' => 'Hand Roll'] |
| resources/views/checkout/index.blade.php:54 | Correcta: etiqueta, estructura o compatibilidad histórica | @if(isset($detail['Bloques']))<x-menu-selection-summary :detail="$detail" />@else<ul class="m-0 list-none space-y-1 break-words p-0 text-xs leading-5 text-[var(--akila-gray-700)]">… |
| database/migrations/2026_10_21_000120_extend_product_composition.php:13 | Esquema/compatibilidad: correcta | 'promocion_handroll' => ['promocion_id', 'producto_id'], |
| resources/views/cart/index.blade.php:102 | Correcta: etiqueta, estructura o compatibilidad histórica | @if ($d['Sin queso'] ?? false) |
| resources/views/cart/index.blade.php:103 | Correcta: etiqueta, estructura o compatibilidad histórica | <li>Sin queso crema</li> |
| resources/views/cart/index.blade.php:105 | Correcta: etiqueta, estructura o compatibilidad histórica | @if ($d['Sin cebollín'] ?? false) |
| resources/views/cart/index.blade.php:106 | Correcta: etiqueta, estructura o compatibilidad histórica | <li>Sin cebollín</li> |
| resources/views/cart/index.blade.php:188 | Correcta: etiqueta, estructura o compatibilidad histórica | @if ($d['Sin queso'] ?? false) |
| resources/views/cart/index.blade.php:189 | Correcta: etiqueta, estructura o compatibilidad histórica | <li>Sin queso crema</li> |
| resources/views/cart/index.blade.php:191 | Correcta: etiqueta, estructura o compatibilidad histórica | @if ($d['Sin cebollín'] ?? false) |
| resources/views/cart/index.blade.php:192 | Correcta: etiqueta, estructura o compatibilidad histórica | <li>Sin cebollín</li> |
| resources/views/components/modal-producto.blade.php:76 | Correcta: etiqueta, estructura o compatibilidad histórica | <label class="inline-flex min-h-12 cursor-pointer items-center gap-3 text-sm"><input type="checkbox" name="cream_cheese" value="1" class="h-5 w-5 rounded border-gray-300 text-red-6… |
| resources/views/components/modal-producto.blade.php:77 | Correcta: etiqueta, estructura o compatibilidad histórica | <label class="inline-flex min-h-12 cursor-pointer items-center gap-3 text-sm"><input type="checkbox" name="scallions" value="1" class="h-5 w-5 rounded border-gray-300 text-red-600"… |
| database/migrations/2025_05_12_140724_create_promocion_table.php:25 | Esquema/compatibilidad: correcta | Schema::create('promocion_handroll', function (Blueprint $table) { |
| app/Http/Controllers/Admin/ProductoController.php:109 | Comentario legacy inerte; no regla ejecutada | //     $max   = in_array(strtolower($ing->nombre), ['salmón', 'salmon', 'carne', 'atún', 'atun']) ? 1 : 2; |
| app/Http/Controllers/Admin/ProductoController.php:217 | Comentario legacy inerte; no regla ejecutada | //     $max = in_array(strtolower($ing->nombre), ['salmón', 'salmon', 'carne', 'atún', 'atun']) ? 1 : 2; |
| app/Enums/UnidadComercial.php:8 | Correcta: etiqueta, estructura o compatibilidad histórica | case HandRoll = 'hand_roll'; |
| app/Enums/UnidadComercial.php:15 | Correcta: etiqueta, estructura o compatibilidad histórica | self::HandRoll => $quantity === 1 ? 'Hand Roll' : 'Hand Rolls', |
| app/Enums/UnidadComercial.php:23 | Correcta: etiqueta, estructura o compatibilidad histórica | self::Pieza => 'Roll', self::HandRoll => 'Hand Roll', self::Unidad => 'Unidad', |
| resources/views/pdf/invoice.blade.php:125 | Correcta: etiqueta, estructura o compatibilidad histórica | @if ($d->get('Sin queso')) |
| resources/views/pdf/invoice.blade.php:126 | Correcta: etiqueta, estructura o compatibilidad histórica | <div class="small">Sin queso crema</div> |
| resources/views/pdf/invoice.blade.php:128 | Correcta: etiqueta, estructura o compatibilidad histórica | @if ($d->get('Sin cebollín')) |
| resources/views/pdf/invoice.blade.php:129 | Correcta: etiqueta, estructura o compatibilidad histórica | <div class="small">Sin cebollín</div> |
| resources/views/home.blade.php:79 | Texto alternativo institucional: correcto | <div class="relative min-w-0 bg-[var(--akila-charcoal)]"><img src="{{ asset('img/slide3.jpg') }}" alt="Rolls envueltos en salmón, servidos con jengibre y wasabi" width="1920" heigh… |

## Validaciones ejecutadas

| Suite específica | Pruebas/aserciones aprobadas |
|---|---|
| CommerceSecurityTest | 59 / 183 |
| MenuArchitectureTest | 33 / 98 |
| SushiAkilaMenuBatch01Test | 15 / 241 |
| SushiAkilaMenuBatch02Test | 22 / 373 |
| SushiAkilaMenuBatch03Test | 38 / 252 |
| SushiAkilaMenuBatch04Test | 34 / 409 |
| SushiAkilaMenuBatch05Test | 14 / 219 |
| MenuCommercialIntegrationTest | 31 / 186 |
| MenuFinalAuditTest | 17 / 799 |

Suite completa: **291 aprobadas, 2854 aserciones, 0 fallos**, 543.73 segundos.

Build: correcto, 66 módulos. Pint --test aprobado sobre CheckoutController, Admin/ProductoController y las dos pruebas/helpers nuevas. migrate:status: 45 aplicadas, ninguna pendiente; route:list:58. git diff --check y git status revisados al cierre.

Archivos de esta auditoría: docs/menu-final-audit.md; tests/Feature/MenuFinalAuditTest.php; tests/Support/MenuAuditConfiguration.php; modificaciones acotadas en app/Http/Controllers/Admin/ProductoController.php y app/Http/Controllers/CheckoutController.php. Se conservan todos los cambios previos sin atribuirlos a esta fase. No se hizo commit.
