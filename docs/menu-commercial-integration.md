# Integración comercial del menú — Batch01

Fecha: 22 de octubre de 2026.

## 1. Alcance y causa del problema

Se integra el catálogo Batch01 existente, sin ejecutar su seeder sobre la base de desarrollo, cambiar sus datos ni incorporar Batch02. Se mantienen los modelos, tablas, rutas de carrito y checkout existentes. No hay migraciones ni cambios en la confirmación de Mercado Pago.

El modal anterior obtenía proteínas/vegetales de `Producto::ingredientes()` (`ingrediente_producto`). En el producto a elección ese pivote contiene la base; las elecciones reales están en `bloques_promocion → bloque_grupo_opcion → grupos_opciones → opciones`. Por eso existían encabezados sin controles. El carrito anterior solo normalizaba `Proteínas`, `vegetales`, envoltura y exclusiones de base legacy; no interpretaba bloques.

Referencias revisadas: `database-audit-sushi-akila.md`, `menu-database-architecture.md` y `menu-import-batches.md`, modelos, pivotes, enums, seeder Batch01, precios, checkout, snapshots, rutas y frontend existentes.

## 2. Flujo frontend

La carta carga tarjetas y categorías. Al abrir una tarjeta se solicita únicamente el modal de ese producto; no se incrusta el catálogo completo con sus configuraciones. Se conserva la disposición de imagen/información a la izquierda y configuración/precio a la derecha; en móvil se apilan.

El modal comercial se genera desde las relaciones actuales. La cantidad de rolls, sus piezas, grupos, límites, nombres, opciones y recargos vienen de Laravel. Proteínas y vegetales son secciones visuales dentro del mismo grupo de relleno de Batch01, con radios de nombre compartido: una proteína **o** un vegetal, nunca uno de cada uno simultáneamente. No existen IDs, ingredientes ni cantidades de bloques codificados en JavaScript.

Cada roll muestra estado pendiente/completo, selección marcada y resumen legible. Los grupos de envoltura se muestran solo si están asignados. La base se informa separadamente y solo permite las exclusiones marcadas como removibles en el pivote; no permite reemplazarla.

El botón permanece deshabilitado con elecciones incompletas, cotización pendiente o error. Cada cambio invalida el precio anterior y vuelve a cotizar, con debounce y cancelación de solicitudes anteriores. Antes de agregar se cotiza nuevamente y `cart.add` hace una validación independiente. No se agregan importes al payload.

## 3. Endpoints y serialización

Se reutilizan las rutas existentes:

| Método / ruta | Función |
| --- | --- |
| GET `/producto/modal/{id}` | HTML de un producto, con esquema público mínimo en `data-menu-config` |
| POST `/cart/quote` | Validación y cotización sin modificar carrito |
| POST `/cart/add` | Validación independiente y escritura en sesión |
| POST `/cart/update` | Cambio de cantidad con recálculo completo |
| POST `/cart/remove` | Eliminación por hash de línea |

Son las rutas públicas del flujo existente, bajo middleware web/CSRF. La cotización conserva `throttle:60,1`. El modal y las cotizaciones usan `Cache-Control: no-store`. No se crea una API alternativa ni una ruta de pago.

El esquema del modal contiene identidad del producto, piezas, `customizable`, base y permisos de remoción, bloques y grupos con límites/selección/opciones activas y sus recargos mostrables. También incluye cantidades de preparación publicadas. No se serializa la colección interna de reglas ni sus condiciones.

Las relaciones se precargan para un solo producto: categoría, ingredientes, bloques/grupos/opciones/ingrediente, grupos del producto y reglas. Para cantidades publicadas de preparación solo se consultan las opciones referenciadas que faltan en esas relaciones. La carta precarga categoría; el refresco del carrito carga únicamente sus productos y relaciones.

## 4. Payload

Ejemplo conceptual; los números son IDs recibidos del backend, nunca constantes del cliente:

```json
{
  "product_id": 123,
  "unidades": 1,
  "version": 2,
  "blocks": [
    {"block_id": 201, "option_ids": [301]},
    {"block_id": 202, "option_ids": [302]},
    {"block_id": 203, "option_ids": [301]}
  ],
  "option_ids": [],
  "removed_base_ids": []
}
```

`option_ids` superior contiene grupos asignados directamente al producto; los del bloque pertenecen a sus propios grupos. Los productos fijos pueden enviarse con solo `product_id` y `unidades`: Laravel reconstruye su composición.

Respuesta de cotización comercial: `valid`, `hash`, `producto_id`, `nombre`, `unidades`, `precio_base`, `recargo`, `precio_unit`, `total`, `configuration`, `detalle` y `charges`. Son valores calculados por servidor. Los campos de cliente `price`, `extra_price`, `subtotal`, `total` y `discount` no intervienen en el cálculo.

## 5. Validación backend

`ProductPricingService` conserva su implementación legacy y delega la rama de productos con bloques/grupos en `MenuConfigurationService`. La selección de rama depende del catálogo, no de un indicador enviado por el navegador.

La configuración comercial valida:

- Producto y categoría activos; cantidad entera entre 1 y 50, como en el carrito existente.
- Estructura acotada de arrays/listas e IDs enteros; bloques sin duplicados.
- Conjunto exacto de bloques del producto, sin aceptar bloques extranjeros, inexistentes ni omitir rolls.
- Opciones sin duplicados dentro de cada bloque, activas y pertenecientes a grupos activos asignados a ese bloque/producto. Un ID válido de otro grupo/producto se rechaza.
- Mínimos/máximos efectivos de `GrupoOpcion::limites()`, incluidos overrides del pivote y `obligatorio`.
- Total de piezas de bloques consistente con `Producto::unidades`.
- Remociones únicamente de ingredientes asignados con `metadata.removible`.
- Reglas comerciales antes de devolver cualquier precio válido.

En Batch01 el grupo `relleno-30-piezas-a-eleccion` contiene las ocho opciones permitidas y tiene min=max=1. Así se cumple la restricción conjunta proteína/vegetal en servidor, sin sumar dos grupos independientes.

## 6. Evaluación de reglas

`PromotionRuleEvaluator` interpreta las reglas activas ya estructuradas:

- `max_repeticiones`: en productos personalizables cuenta elecciones de relleno por ingrediente u opción, según el sujeto de la regla. El máximo 2 general y el máximo 1 de salmón se leen del catálogo; no se identifica salmón por una cadena hardcodeada.
- `excluir`: impide seleccionar el sujeto prohibido.
- `min_piezas`: valida piezas asociadas al sujeto seleccionado.
- `recargo`: aplica importes explícitos de opción y reglas compatibles con su condición.
- `cantidad_incluida` con `metadata.uso=preparacion_global`: muestra las cantidades publicadas de preparación; no inventa su distribución entre rolls.

Un ingrediente como palta puede ser relleno y envoltura. Las repeticiones por ingrediente de relleno no cuentan nuevamente una envoltura de palta. La identificación del rol usa las claves de grupos actuales (`envoltura`, `preparacion`, `envoltura-fija-*`); no depende de nombres de ingredientes. Si una fase futura agrega nuevos roles, deberá formalizar/ampliar esa clasificación.

Las recetas fijas no se invalidan por contar como elecciones sus ingredientes establecidos: por ejemplo, Proteínas contiene tres rolls de pollo y se vende sin elecciones.

No se implementa la expresión ambigua «ingredientes que no cumplen», ni un máximo global de tres que contradiga las fichas específicas. Las condiciones desconocidas no generan cobros automáticos.

## 7. Precios y recargos

`MenuMoney` utiliza `Brick\Math\BigDecimal` para convertir valores DECIMAL a centavos enteros. Rechaza precios negativos o configuraciones monetarias inválidas. El cálculo comercial es:

```text
precio_unit = precio base actual del Producto + recargos unitarios válidos
total línea = precio_unit × cantidad
```

Cada recargo incluye origen (`option`/`rule`), ID, nombre e importe decimal. `Opcion.recargo` se suma una vez por elección. Las reglas de modificación de relleno/envoltura requieren una referencia inicial inequívoca y un cambio respecto a ella. Una elección inicial sin referencia no se interpreta como modificación pagada.

Por tanto, los tres rellenos válidos de 30 Piezas a Elección cuestan $14.900. Sus envolturas opcionales tienen los recargos actualmente configurados; no se añade $1.000 ficticio por elegir la primera envoltura. La edición pagada de composición fija requiere modelar qué sustituciones están permitidas y su referencia; no se abre arbitrariamente el conjunto de opciones.

Las pruebas verifican un recargo de $1.000,25, su multiplicación por cantidad y la coincidencia entre subtotal, total con delivery, snapshots y payload del SDK de Mercado Pago. La lógica de Mercado Pago no se modifica.

## 8. Carrito y clave de línea

Se conserva `session('cart')`, indexado por hash. La configuración normalizada comercial tiene:

```json
{
  "version": 2,
  "blocks": [{"block_id": 201, "pieces": 10, "option_ids": [301]}],
  "option_ids": [],
  "removed_base_ids": []
}
```

Los bloques se ordenan por ID; los IDs de opciones y remociones se ordenan numéricamente. El hash SHA-256 combina ID de producto y configuración normalizada. Reordenar arrays/JSON no genera otra línea; cambiar una elección de cualquier roll sí. La misma configuración incrementa cantidad hasta el límite vigente.

La línea contiene además precios calculados, cargos y `detalle` legible con nombres/IDs de grupos y opciones, base, preparación, piezas y exclusiones. Cambiar cantidad recotiza desde la configuración sin perderla; eliminar sigue usando el hash.

El checkout refresca todas las líneas desde catálogo actual. Si cambió el precio solicita revisar el carrito antes de crear el pedido; si desapareció/desactivó una elección rechaza el checkout. Nunca acepta como autoridad el precio guardado en sesión ni el enviado por cliente.

## 9. Snapshot histórico

`OrderItemSnapshotService` sigue siendo el único constructor desde cotizaciones confiables. Las líneas comerciales nuevas usan snapshot versión 2 y las legacy conservan versión 1:

```text
version, currency
product: id, name, description, slug
quantity
amounts: base_unit, surcharge_unit, final_unit, line_total
configuration: version, blocks (IDs/piezas/options), option_ids, removed_base_ids
selections: Base, Bloques (ID/nombre/piezas/grupos/opciones/nombres/recargos),
            Grupos, Sin base, Preparación
charges: origen, IDs, nombre, importe
```

Se mantienen las columnas históricas `nombre`, `unidades`, `precio_unit`, `total` y `detalle`. Las vistas leen esos valores históricos y no consultan el producto para reconstruir elecciones. Cambiar nombre/precio del producto o nombres de opciones no altera el pedido existente. Carrito, checkout, confirmación, detalle administrativo y PDF comparten el resumen legible; el historial enlaza la confirmación existente. Pedidos antiguos sin bloques/snapshot siguen sus ramas legacy.

## 10. Productos fijos y personalizables

Los once productos fijos muestran composición. Los grupos singleton obligatorios de cada bloque se reconstruyen en Laravel; no se presentan como selectores. Costanera mantiene pollo/frito, camarón/palta y palta/queso crema. Enviar una composición fija alterada se rechaza.

30 Piezas a Elección presenta sus tres bloques independientes, ocho rellenos admitidos y envolturas opcionales asignadas. Se exige un relleno por roll. La base sigue las marcas de removible/no reemplazable cargadas en Batch01.

## 11. Errores

Laravel devuelve HTTP 422 con mensajes de validación; el modal los muestra en un bloque `role=alert`, sin stack traces. Ejemplos comprobados:

- «Pollo puede repetirse como máximo 2 veces en esta promoción.»
- «Salmón puede repetirse como máximo 1 vez en esta promoción.»
- «La opción seleccionada no está disponible para Roll 2.»

Si falta una elección se identifica el primer roll pendiente. Errores de apertura/cotización impiden agregar y permiten reintentar. La protección backend funciona también si se omite totalmente JavaScript.

## 12. Compatibilidad y fases siguientes

Los productos sin bloques ni grupos mantienen `ProductPricingService` y modal legacy. No cambian rutas, tablas ni modelos. Se conserva checkout, delivery fijo existente, autorización de pedidos, validación de pagos e importes calculados desde servidor.

El número de bloques es dinámico y puede representar dos, cuatro o más en fases posteriores. Nuevos datos deberán mantener claves estables, grupos correctos, límites y roles definidos. La infraestructura no importa Batch02 ni referencias a clases de seeders futuras.

Pendientes deliberados: interpretar las ambigüedades de Foto 2; definir referencias y opciones de sustituciones pagadas antes de habilitarlas en productos fijos; ampliar el evaluador cuando futuras fases incorporen nuevos tipos/condiciones confirmadas. No se implementan inventario, gramajes, delivery por distancia ni nuevas reglas supuestas.

## 13. Pruebas automatizadas

`MenuCommercialIntegrationTest` cubre 31 casos: configuraciones de proteínas/vegetales válidas, IDs extranjeros/inexistentes, opciones/grupos/productos inactivos, bloques faltantes/repetidos, min/max, opción duplicada, repeticiones y salmón, importes falsificados, envolturas y límites independientes de relleno, recargos decimales, condiciones ambiguas, hashes canónicos, separación/fusión/eliminación de líneas, cantidad, los once productos fijos, serialización y consultas del modal, snapshots históricos, checkout con cambios posteriores y payload de Mercado Pago con delivery.

Las pruebas usan SQLite aislado y ejecutan el seeder solo en esa base de pruebas. No se modifica la prueba de seguridad ni las pruebas de arquitectura/importación para ocultar regresiones.

Comprobaciones específicas: CommerceSecurityTest 59/183; MenuArchitectureTest 33/98; SushiAkilaMenuBatch01Test 15/241; MenuCommercialIntegrationTest 31/186 (pruebas/aserciones). Todas aprobadas.

Suite completa final: **166 pruebas aprobadas, 802 aserciones, 0 fallos**.

`php artisan route:list`: 58 rutas, sin errores. `php artisan migrate:status`: 44 migraciones aplicadas, ninguna pendiente; no se ejecutan migraciones contra la base de desarrollo. `npm run build`: correcto (66 módulos). Pint de los archivos PHP de esta fase y `git diff --check`: correctos.

La lectura final de los conteos comerciales confirma que se conservan 2 categorías, 14 ingredientes, 12 productos, 12 promociones, 45 bloques, 16 grupos, 36 opciones y 215 reglas.

## 14. Comprobación en navegador local

Se abrió la aplicación servida en `http://127.0.0.1:8000` con Chrome headless, controlando el navegador real mediante CDP. Se inspeccionaron capturas de escritorio (1440 px) y móvil (390 px). El modal mantiene dos columnas en escritorio, se apila en móvil y no tiene desbordamiento horizontal (ancho de documento 390; contenido del modal sin exceso de scroll horizontal). La selección se distingue con fondo rojo y texto blanco.

- Aparecen Roll 1, Roll 2, Roll 3, proteínas, vegetales y envolturas realmente asignadas.
- Pollo / Camarón / Pollo: cotización $14.900, botón habilitado y carrito con los tres rolls y nombres.
- Pollo / Pollo / Pollo: backend rechaza con mensaje de máximo 2; botón deshabilitado.
- Salmón / Salmón / Pollo: backend rechaza con mensaje de máximo 1; botón deshabilitado.
- Costanera: ningún selector abierto, composición fija correcta, agregado sin elección manual por $14.900.
- Carrito combinado: dos líneas, total $29.800. Sin excepciones JavaScript registradas.

Las comprobaciones locales modifican únicamente la sesión del navegador; no generan pedidos ni pagos. Los cobros se verifican con el SDK simulado en pruebas, sin transacciones reales.

## 15. Archivos de esta integración

Servicios nuevos: `MenuConfigurationService`, `PromotionRuleEvaluator`, `MenuMoney`. Servicios extendidos: `ProductPricingService`, `OrderItemSnapshotService`.

Controladores: `CartController` y `ProductoController`. Frontend: entrada `resources/js/app.js`, módulos `menuPersonalization.js` y `storePersonalization.js`, tarjeta `components/store/product-card`, carta `menu/index`, componentes nuevos `menu-product-modal`, `menu-option-group`, `menu-selection-summary`, vistas de carrito/checkout/confirmación/PDF y detalle administrativo. Se regeneran manifest y assets de Vite.

Prueba nueva: `tests/Feature/MenuCommercialIntegrationTest.php`. Documentación nueva: este archivo. No se modifican seeders, modelos, migraciones, `.env` ni el servicio de Mercado Pago en esta fase. El estado de Git contiene además cambios previos de otras fases, conservados sin commit.
