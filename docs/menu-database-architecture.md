# Infraestructura del catálogo Sushi Akila

Fecha: 2026-10-21. Esta fase crea estructura y protecciones históricas. No carga el menú, categorías, ingredientes, promociones ni reglas comerciales reales. No implementa inventario ni un nuevo cotizador.

## 1. Arquitectura anterior y decisiones

Se releyó `database-audit-sushi-akila.md` y se contrastaron migraciones, modelos, consumidores y esquema real. La base local akila (conexión mysql, MariaDB 10.4.32) tenía 18 tablas y 34 migraciones aplicadas. Antes de cambiarla se comprobaron las nueve tablas comerciales vacías, ausencia de pares duplicados en los tres pivotes y ausencia de líneas de pedido huérfanas.

Producto sigue siendo la única identidad vendible. Categoria organiza comercialmente; Ingrediente representa ingredientes; Promocion conserva su entidad y componentes legacy. No hay tablas separadas de roll, hand roll, Nikkei, burger o Gohan.

Se reutiliza **productos.unidades** para las unidades/piezas incluidas en una presentación: el formulario existente dice “Unidades incluidas” y los fixtures usan 10 para un roll. No se agrega cantidad_piezas a productos ni se deduce unidades desde rolls_total. En carrito/order_items, unidades sigue siendo cantidad de presentaciones compradas: son conceptos distintos en tablas distintas. El futuro seeder debe usar 20/30/40/etc. en Producto.unidades para promociones de piezas y el número de hand rolls para sus combos. No inventar conversiones entre ambos.

`personalizable` ya existe y se mantiene independiente de tipo: una promoción o combo puede ser fijo o personalizable. `tipo` es VARCHAR(30), controlado con enum PHP TipoProducto (`simple`, `promocion`, `combo`), no enum SQL; las familias culinarias pertenecen a categorías/nombres de productos. No crear un tipo por cada nombre de carta.

## 2. Arquitectura resultante

```text
Categoria [categorias]
  hasMany Producto [productos]
Producto
  belongsTo Categoria
  belongsToMany Ingrediente [ingrediente_producto extendido]
  belongsToMany Ingrediente como swappables [pivote legacy]
  belongsToMany GrupoOpcion [grupo_opcion_producto]
  hasMany BloquePromocion [bloques_promocion]
  hasMany ReglaPromocion [reglas_promocion]
  hasOne Promocion [promociones.producto_id único y nullable]
  hasMany OrderItem [order_items.product_id RESTRICT]

GrupoOpcion [grupos_opciones]
  hasMany Opcion [opciones]
  belongsToMany Producto
  belongsToMany BloquePromocion [bloque_grupo_opcion]
Opcion
  belongsTo GrupoOpcion
  belongsTo Ingrediente opcional
Ingrediente
  belongsToMany Producto
  hasMany Opcion
BloquePromocion
  belongsTo Producto
  belongsToMany GrupoOpcion
ReglaPromocion
  belongsTo Producto
  belongsTo Ingrediente O belongsTo Opcion (un único sujeto)

Promocion
  belongsTo Producto (producto vendible de la promoción)
  belongsToMany Producto [promocion_handroll: componentes fijos, cantidad]

Order hasMany OrderItem
OrderItem belongsTo Order y Producto
OrderItem.snapshot: JSON histórico autocontenido, sin consultas al catálogo para leerlo
```

## 3. Tablas reutilizadas y columnas

| Tabla existente | Cambio |
|---|---|
| categorias | activo BOOLEAN default true; orden UNSIGNED INT default 0 |
| productos | tipo VARCHAR(30) default simple; activo BOOLEAN default true; orden UNSIGNED INT default 0; FK categoria_id pasa a RESTRICT |
| ingredientes | Se amplía el enum tipo existente, sin renombrar/reclasificar datos |
| ingrediente_producto | cantidad DECIMAL(12,3) nullable; unidad VARCHAR(20) nullable; obligatorio BOOLEAN true; reemplazable BOOLEAN false; orden UNSIGNED INT 0; metadata JSON nullable; UNIQUE pareja |
| producto_swappable_ingredient | UNIQUE(producto_id, ingrediente_id) |
| promocion_handroll | UNIQUE(promocion_id, producto_id) |
| promociones | producto_id nullable UNIQUE FK productos.id DELETE RESTRICT |
| order_items | snapshot JSON nullable; product_id DELETE RESTRICT |

No se modifican columnas ni esquema de orders, users, pagos o delivery. No se borran ni renombran tablas/columnas existentes. Las únicas modificaciones de definición existente son la ampliación compatible del enum de ingrediente y las FKs autorizadas para proteger historia.

**Clasificación de ingredientes:** se conservan envoltura, proteina y vegetal; se agregan base, salsa, topping y otros. Se amplía el enum SQL existente porque la administración actual obtiene sus valores mediante SHOW COLUMNS: crear otra clasificación paralela duplicaría el concepto y dejaría dos fuentes de verdad. No se introdujo un enum SQL nuevo para productos ni reglas. Si más adelante se necesita clasificación administrable sin despliegue, será otra fase. Los ingredientes premium se distinguen por recargos configurables de opciones/reglas, no por un catálogo premium duplicado.

No se interpreta ingredientes.costo como recargo o costo físico. Se mantiene intacto. Las etiquetas antiguas “base/extra” de algunos formularios administrativos permanecen como deuda previa, sin rediseño en esta fase.

## 4. Tablas nuevas

Todas llevan id y created_at/updated_at. Hay **seis tablas nuevas**, total **24** en la base local.

| Tabla | Campos relevantes y restricciones |
|---|---|
| grupos_opciones | nombre, slug UNIQUE, tipo_seleccion VARCHAR(20) single/multiple; min_selections UNSIGNED SMALLINT 0; max_selections UNSIGNED SMALLINT 1; activo true; orden 0 |
| opciones | grupo_opcion_id FK CASCADE, ingrediente_id nullable FK RESTRICT, nombre, slug, recargo DECIMAL(10,2) 0, activo true, orden 0; UNIQUE(grupo_opcion_id,slug) |
| grupo_opcion_producto | producto_id FK CASCADE; grupo_opcion_id FK CASCADE; min_selections/max_selections nullable; obligatorio nullable; orden 0; UNIQUE(producto_id,grupo_opcion_id) |
| bloques_promocion | producto_id FK CASCADE; nombre; cantidad_piezas UNSIGNED SMALLINT default 10; orden obligatorio; metadata JSON nullable; UNIQUE(producto_id,orden) |
| bloque_grupo_opcion | bloque_promocion_id/grupo_opcion_id FK CASCADE; overrides min/max/obligatorio nullable, orden 0; UNIQUE(bloque_promocion_id,grupo_opcion_id) |
| reglas_promocion | producto_id FK CASCADE; codigo; tipo VARCHAR(30); ingrediente_id nullable FK RESTRICT; opcion_id nullable FK RESTRICT; valor DECIMAL(12,2); activo true; metadata JSON nullable; UNIQUE(producto_id,codigo) |

Las FKs de configuración en cascada solo eliminan configuración perteneciente a un catálogo sin referencias históricas; los snapshots no referencian estas filas mediante FK. Ingredientes/opciones usados por reglas no se pueden borrar accidentalmente. No hay IDs polimórficos sin integridad referencial.

## 5. Composición fija y preparación para recetas

Se extiende **ingrediente_producto** y se introduce el custom pivot IngredienteProducto. No se crea product_recipes, recetas ni otro pivote redundante. La pareja producto/ingrediente es única y los campos nuevos tienen casts de cantidad, booleanos y metadata.

- `cantidad_permitida` conserva exactamente el significado/lector legacy usado por ProductPricingService; no se convierte a gramos ni se cambia su valor.
- `cantidad` y `unidad` son la futura cantidad física, inicialmente NULL cuando se desconoce. No inferir gramajes a partir de cortes, rolls o límites comerciales.
- `obligatorio`, `reemplazable` y `orden` describen composición futura. metadata contiene notas excepcionales, no todas las relaciones.
- El modelo rechaza cantidad negativa. La definición de unidades, conversiones, rendimiento, versionado de recetas y consumo efectivo se reserva para la fase de cocina/inventario.
- Las composiciones de productos componentes de packs fijos siguen disponibles mediante Promocion.productos y promocion_handroll.cantidad.

No se implementan stock, movimientos, proveedores, compras, reservas ni costos de receta. La ampliación del pivote es suficiente para preparar cantidades físicas sin construir ahora ese sistema.

## 6. Grupos, opciones y validación de selección

GrupoOpcion agrupa opciones; cada opción puede referenciar Ingrediente sin crear otro ingrediente. nombre/slug son etiqueta e identidad comercial dentro de su grupo; permiten nombres de presentación diferentes del ingrediente. Opciones sin ingrediente soportan selecciones puramente comerciales.

`Producto::gruposOpciones()` usa grupo_opcion_producto. Los límites globales pueden sobrescribirse localmente mediante min_selections/max_selections; NULL hereda. obligatorio=true eleva mínimo a uno; obligatorio=false hace mínimo cero; NULL conserva mínimo resuelto. orden pertenece a la asignación.

Los bloques usan el mismo contrato de overrides en bloque_grupo_opcion. Se reutiliza AsignacionGrupo como clase Pivot para ambas tablas. Los grupos asignados al producto son de ámbito producto; los asignados a un bloque son de ámbito bloque. **No hay herencia implícita** de todos los grupos del producto hacia cada bloque: el seeder debe asignar cada relación que corresponda.

`GrupoOpcion::limites($pivot)` resuelve límites y rechaza inconsistencias. single no admite máximo mayor a uno; multiple admite varios. Se validan en servidor min≥0 y max≥min y, al editar el grupo, la compatibilidad de overrides existentes. `validarSelecciones($ids, $pivot)` verifica cardinalidad, IDs enteros distintos, pertenencia de las opciones al grupo y estado activo. Un grupo activo no acepta opciones inactivas o ajenas. No confía en límites enviados por frontend.

La validación se ejecuta en los eventos de guardado de los modelos/pivotes y mediante el método explícito para una selección. Escrituras SQL directas/query builder omiten eventos; no deben usarse para importar configuración sin validación previa. Las FKs y UNIQUE protegen integridad de referencias/duplicados incluso ante SQL directo. La verificación de que el grupo esté asignado al producto/bloque se realiza cargando esa relación antes de llamar al validador; no se debe aceptar un grupo global arbitrario enviado por cliente.

`Opcion.recargo` usa DECIMAL y cast decimal:2, valida no negativo y máximo dos decimales. No es precio final del producto. Aún no se suma al precio público automáticamente.

## 7. Bloques de promociones

BloquePromocion pertenece a **Producto**, no requiere crear Promocion para existir. nombre, cantidad_piezas y orden representan cada bloque; orden es único por producto. Permite cuatro bloques de diez para una presentación de cuarenta y también otras cantidades sin límite artificial de 100 piezas.

Cada bloque asigna sus propios grupos, con cardinalidad independiente. La selección del cliente deberá identificar bloque_id, grupo_id y opción_id, no listas globales de proteínas que pierdan el vínculo con cada bloque. El catálogo contiene configuración, no elecciones de un pedido específico.

Metadata queda para anotaciones excepcionales. El seeder deberá verificar que la suma de bloques concuerde con Producto.unidades cuando se mida en piezas. No se impone esa suma al guardar cada bloque individual: impediría construir una promoción incrementalmente. La validación de una promoción completa y la futura cotización se implementarán en la siguiente fase comercial.

## 8. Reglas genéricas y salsas

ReglaPromocion pertenece a Producto y usa codigo único por producto como identidad idempotente del seeder. Tiene un solo sujeto: Ingrediente **o** Opcion; los dos nullable permiten alternativas, pero el evento de guardado exige exactamente uno. Los IDs tienen FKs reales. No se crean max_salmon/max_pollo/etc.

TipoReglaPromocion es un enum PHP almacenado en VARCHAR:

| Tipo | Valor y alcance previsto |
|---|---|
| max_repeticiones | Entero máximo de apariciones del sujeto en la configuración completa del producto, sumando bloques |
| excluir | Valor cero; sujeto no participa |
| recargo | Importe monetario DECIMAL no negativo por aparición según evaluador futuro |
| min_piezas | Entero mínimo de piezas asociadas al sujeto, p.ej. una envoltura de preparación frita |
| cantidad_incluida | Entero de unidades incluidas del sujeto, p.ej. salsa |

El modelo valida tipo, sujeto y valor; las reglas no monetarias exigen entero. metadata admite notas/parámetros excepcionales, no sustituye identidad ni el valor. Las reglas son consultables mediante `$producto->reglas()->where('activo', true)`.

**Todavía no existe evaluador agregado de estas reglas en ProductPricingService.** Al implementarlo deberá definir precedencia, unidad de repetición, acumulación/descuentos y conversión piezas/opciones sin depender de nombres. Repeticiones por opción dentro de un grupo no se representan duplicando el ID en esa selección: se cuentan selecciones independientes entre bloques. No se ejecuta código ni fórmulas arbitrarias desde metadata.

Salsas: no hay tabla específica nueva. Ingrediente.tipo=salsa identifica la salsa; cantidad_incluida por producto representa la cantidad por tamaño. El futuro seeder puede expandir las franjas 20/30–50/etc. en reglas de los productos correspondientes sin guardar una matriz rígida de columnas. Salsas adicionales se representan con opciones de recargo o Producto vendible si se compran de forma independiente. No se han cargado cantidades/reglas reales.

## 9. Responsabilidad de Promocion

Se elige **entidad complementaria de Producto**. promociones.producto_id es nullable para conservar filas legacy y único para relación uno a uno. La relación `producto()` es la presentación vendible; `productos()` sigue siendo el conjunto de componentes fijos en promocion_handroll. Producto.promocion es la inversa nueva.

No se borra ni absorbe destructivamente Promocion. Se mantienen nombre/descripcion/precio/cantidad_rolls/categoria/personalizable legacy. Para nuevas ventas **Producto.precio es la autoridad** y no se debe calcular precio desde Promocion.precio en paralelo. El futuro seeder mantendrá coherentes los campos heredados cuando cree metadata complementaria; no los usará como segundo catálogo comprable.

Bloques y reglas pertenecen directamente al producto para cubrir también combos, hand rolls y productos personalizables sin crear promociones ficticias. No hay un carrito o checkout adicional para Promocion.

## 10. Snapshots históricos

Se añade order_items.snapshot JSON nullable. Los pedidos existentes siguen leyendo nombre, unidades, precio_unit, total y detalle; no se rellenan datos históricos inventados ni se cambian vistas.

OrderItemSnapshotService.fromQuote recibe exclusivamente una **cotización recién recalculada por servidor**. El checkout lo llama al crear la línea después de refreshCart, con una carga agrupada de productos para la descripción. No recibe snapshot ni importes del request del cliente.

Formato inicial:

```text
version: 1
currency: CLP
product: {id, name, description, slug}
quantity
amounts: {base_unit, surcharge_unit, final_unit, line_total}
configuration: configuración normalizada por servidor
selections: detalle con nombres y exclusiones
```

Los importes son strings de dos decimales, independientes de futuras modificaciones del producto. El precio final sigue en precio_unit y total para Mercado Pago/PDFs; no se duplican columnas monetarias adicionales. La serialización normaliza la cotización actual; no reemplaza el algoritmo monetario legacy, que aún usa floats internamente. La futura cotización de opciones deberá aportar nombres/IDs/recargos por bloque y versionar el snapshot si cambia su estructura.

Una línea cuyo snapshot ya existe rechaza su reemplazo mediante actualización Eloquent. Esto no es un trigger SQL: actualizaciones directas de BD podrían omitir esa protección y deben restringirse operativamente. Una línea legacy sin snapshot permanece válida; no se reconstruye su historia consultando el producto actual.

## 11. Foreign keys históricas y administración

| FK | Antes | Después | Razón |
|---|---|---|---|
| order_items.product_id → productos.id | DELETE CASCADE | DELETE RESTRICT | Un producto comprado no puede borrar líneas históricas |
| productos.categoria_id → categorias.id | DELETE CASCADE | DELETE RESTRICT | Eliminar una categoría no debe retirar productos en cascada |

Se mantienen columnas obligatorias, IDs y referencias; no se convierte product_id a nullable ni se eliminan filas. orders.user_id conserva SET NULL y order_items.order_id conserva CASCADE, pues no son cascadas del catálogo y la línea sigue perteneciendo a su pedido.

Los controles de eliminar en administración ahora retornan error de validación si el producto tiene líneas de pedido/promoción vinculada o si la categoría tiene productos. La FK es la protección final ante otras escrituras. La imagen de un producto se borra del almacenamiento **después** de eliminar exitosamente su fila, preservándola si la integridad impide borrar. No se modifica el diseño de formularios.

activo/orden se incorporan como metadata estructural, sin global scopes que oculten productos históricos ni cambios de consultas/vistas públicas en esta fase. Todavía falta conectarlos a filtrado y controles administrativos: el mensaje de conservar/desactivar indica la estrategia futura de retiro lógico, no una pantalla nueva implementada aquí.

## 12. Servicio de precios y compatibilidad

**ProductPricingService no se modifica.** Cotiza las configuraciones legacy con los mismos límites/recargos y rechaza precios de navegador. Mercado Pago y sus servicios tampoco se modifican. No se cambian estados, referencias de pago, idempotencia de sesiones o contrato de importes de pedidos.

Los nuevos grupos/bloques/reglas no están activados como una nueva modalidad de venta. No publicar productos que dependan de ellos hasta integrar el evaluador en servidor y su UI de selección. Crear relaciones no hace que el checkout legacy valide/cobre automáticamente opciones nuevas. Esta separación es intencional en la fase estructural.

Delivery existente ya soporta pickup, delivery, dentro/fuera, kms, subtotal y delivery_cost, con tarifa fija dentro y cálculo fuera. Se conserva sin cambios. Tarificación configurable/geolocalización puede desarrollarse luego; no requiere modificar ahora esquema ni construir un motor nuevo.

## 13. Migraciones y reversibilidad

Diez archivos nuevos, todos bajo database/migrations/ y con prefijo `2026_10_21_`:

| Archivo | Responsabilidad |
|---|---|
| 000100_add_catalog_metadata.php | Categorías/productos |
| 000110_extend_ingredient_types.php | Ampliar clasificación existente |
| 000120_extend_product_composition.php | Pivote de composición y tres UNIQUE legacy |
| 000130_create_option_groups.php | Grupos y opciones |
| 000140_create_product_option_groups.php | Asignación de grupos al producto |
| 000150_create_promotion_blocks.php | Bloques y asignación de grupos |
| 000160_create_promotion_rules.php | Reglas |
| 000170_link_promotions_to_products.php | Vínculo complementario |
| 000180_add_order_item_snapshots.php | Snapshot JSON |
| 000190_protect_historical_product_references.php | Dos FKs históricas |

Cada archivo tiene down inverso. Se probaron down/up en SQLite en memoria, manteniendo filas legacy. Ningún rollback se ejecutó en MariaDB. El down del enum se detiene sin modificar datos si existen ingredientes con tipos nuevos, en vez de convertirlos/perder clasificación silenciosamente. Retornar entonces al enum antiguo exige resolver explícitamente esos datos. Revertir columnas/tablas nuevas descarta su nueva configuración por definición; hacer respaldo antes de un rollback real. El down de la protección histórica restaura las cascadas anteriores, por lo que no debe usarse como solución de un borrado bloqueado.

La migración de UNIQUE comprueba duplicados antes de modificar las tablas y se detiene si encuentra alguno, sin limpiar filas automáticamente. Los tres pivotes actuales estaban vacíos y pasaron el control. No se modificó ninguna de las 34 migraciones previas.

Se revisó migrate:status antes de ejecutar `php artisan migrate`. Se aplicaron las diez normalmente en batch 3, sobre MariaDB. No se ejecutó migrate:fresh/reset, db:wipe, TRUNCATE ni seeder alguno.

## 14. Modelos y archivos de esta fase

Modelos nuevos: GrupoOpcion, Opcion, BloquePromocion, ReglaPromocion, IngredienteProducto (pivot), AsignacionGrupo (pivot compartido). Sus relaciones tienen tipos de retorno. Modelos extendidos: Categoria, Producto, Ingrediente, Promocion y OrderItem. No se modificaron Order ni User durante esta fase.

Otros archivos:

- app/Enums/TipoProducto.php y TipoReglaPromocion.php.
- app/Services/OrderItemSnapshotService.php.
- app/Http/Controllers/CheckoutController.php (escritura snapshot).
- app/Http/Controllers/Admin/ProductoController.php y CategoriaController.php (rechazo de borrados referenciados).
- Las diez migraciones anteriores.
- tests/Feature/MenuArchitectureTest.php.
- docs/menu-database-architecture.md (este documento).

Se aplicó Pint a los archivos PHP de esta fase. npm run build genera los mismos nombres de assets que antes; no se modificaron fuentes frontend, CSS, vistas, rutas, seeders, .env, servicios Mercado Pago ni CommerceSecurityTest. El árbol Git ya tenía cambios extensos de fases previas y se conservaron; no confundir todo el diff desde HEAD con esta implementación. No se hizo commit.

## 15. Seeder futuro y validación

El próximo seeder deberá:

1. Cargar catálogo mediante identidades estables y updateOrCreate/firstOrCreate, conservando IDs. Los eventos actuales generan slugs de Producto/Categoria; definir esos nombres de forma estable y controlar colisiones.
2. Crear categorías y productos con tipo/unidades/personalizable coherentes, sin duplicar productos por familias técnicas.
3. Reutilizar ingredientes por identidad normalizada y tipo, no por IDs numéricos hardcodeados. No inventar gramos ni reutilizar costo como recargo.
4. Sincronizar composición con cantidad/unidad NULL mientras no haya receta física fiable. No cambiar cantidad_permitida arbitrariamente: el cotizador legacy todavía la lee.
5. Crear grupos/opciones y sus asignaciones usando Eloquent para ejecutar validaciones; usar slugs únicos globales para grupos y locales para opciones.
6. Crear bloques idempotentes por producto/orden, asignar grupos explícitos por bloque, comprobar suma de piezas y cargar reglas por producto/codigo.
7. Usar cantidad_incluida para salsas y recargos DECIMAL para opciones premium, sin aplicar aún una venta nueva antes del evaluador. No duplicar políticas monetarias en frontend.
8. Enlazar Promocion cuando aporte composición/metadata; mantener Producto como identidad de compra y precio autoritativo.
9. Usar transacciones y validación previa; no truncar ni borrar registros administrativos ajenos al catálogo importado. No actualizar pedidos/PDFs históricos ni asignarles snapshots inventados.

Pruebas agregadas: relaciones e inversas, metadata legacy, siete tipos de ingredientes, composición sin gramajes y con decimales, límites globales/overrides/opciones activas, bloques independientes, reglas con sujeto exclusivo, importes exactos, duplicados de nueve identidades, vínculo Promocion, snapshot inmutable y checkout contra precio falsificado, preservación histórica por FK, imagen administrativa, compatibilidad de líneas legacy y reversibilidad aislada sin perder sus filas. También se comprueba la negativa segura a reducir el enum con clasificaciones nuevas.

Las pruebas usan SQLite :memory: mediante phpunit.xml y las extensiones SQLite del PHP CLI se cargan solo para el proceso con PHP_INI_SCAN_DIR temporal, sin cambiar php.ini/.env. La base activa MariaDB solo recibió migraciones estructurales, no fixtures de pruebas. Su esquema final se inspeccionó por lectura: 24 tablas, recargos DECIMAL y FKs RESTRICT correctas; las tablas comerciales continúan vacías.

Resultados finales:

| Validación | Resultado |
|---|---|
| php artisan migrate | Diez migraciones aplicadas correctamente en MariaDB, batch 3 |
| php artisan migrate:status | 44 aplicadas; cero pendientes |
| php artisan test --filter=CommerceSecurityTest | 59 aprobadas, 183 aserciones; archivo intacto |
| php artisan test --filter=MenuArchitectureTest | 33 aprobadas, 98 aserciones |
| php artisan test | 120 aprobadas, 375 aserciones, cero fallos |
| php artisan route:list | Correcto, 58 rutas |
| npm run build | Correcto, 65 módulos; mismos assets resultantes |
| git diff --check | Correcto |
| Estado Git | Revisado; cambios de esta fase sin commit, junto a cambios previos conservados |

La prueba de reversibilidad con datos usa una conexión SQLite en memoria separada, fuera de la transacción automática de RefreshDatabase, porque SQLite reconstruye tablas al alterar FKs y no puede desactivar foreign_keys dentro de una transacción abierta. Además verifica PRAGMA foreign_key_check al terminar. No se deshabilita integridad de la base MariaDB.

Quedan pendientes deliberadamente el seeder real, evaluador de reglas/cotizador nuevo, selección frontend por bloques, exposición de activo/orden en administración y consultas públicas, e inventario. No hay cambios de menú ni pagos reales en esta fase.
