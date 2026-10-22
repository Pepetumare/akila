# Auditoría Base de Datos Sushi Akila

Fecha: 21 de octubre de 2026. Auditoría del árbol de trabajo actual y de la base local accesible; no se presume que esta base sea la de producción. Solo lectura, excepto este informe. Los cambios previos presentes en Git no fueron realizados por esta auditoría.

## 1. Resumen ejecutivo

Aplicación Laravel 12 con catálogo relacional sencillo y personalización específica para sushi. Hay **18 tablas reales**, 17 creadas por archivos de migración y una tabla técnica `migrations`. Se revisaron los **34 archivos de migraciones**, todos registrados como ejecutados. Hay siete modelos Eloquent. No existen modelos ni tablas Cart, CartItem, Payment, inventario o recetas.

La conexión llamada `mysql` apunta a **MariaDB 10.4.32**, base **akila**, local. Las nueve tablas del dominio comercial están vacías; users tiene 1 registro, sessions 4 y migrations 34. Las demás tablas tienen 0. Se verificó estructura, no identidad ni contenido de usuarios/sesiones. No es posible validar ejemplos históricos reales o combinaciones efectivamente vendidas en esta base vacía.

El carrito vive en sesión, el servidor calcula precios y el checkout crea snapshots parciales de las líneas. Mercado Pago verifica pago contra el total del pedido, sin recalcular desde el catálogo. El principal riesgo de conservación histórica es la cascada categoría → producto → líneas de pedido. Otros problemas: reglas de selección hardcodeadas, significado ambiguo del pivote, promociones desconectadas y ausencia de un registro persistente de intentos de pago.

## 2. Entorno

| Dato | Resultado observado |
|---|---|
| Laravel instalado | 12.70.1 (`php artisan --version`); composer exige ^12.0 |
| PHP CLI | 8.2.18, NTS, Windows; composer exige ^8.2 |
| Conexión predeterminada efectiva | `mysql` |
| Driver Laravel/PDO | mysql / pdo_mysql |
| Motor real | MariaDB 10.4.32, InnoDB en las 18 tablas |
| Base configurada y accesible | `akila` |
| Host/puerto | 127.0.0.1:3306 |
| Charset/collation | utf8mb4 / utf8mb4_unicode_ci |
| Configuración cacheada | No |
| Modo Laravel | strict=true; engine=null (usa predeterminado del servidor) |
| Sesión/caché efectivos | database / database |
| PDO disponible en este proceso | mysql; SQLite no cargado en el CLI actual |
| Pruebas, según phpunit.xml | SQLite :memory:, sesión/caché array; no se ejecutaron |

`config/database.php` declara sqlite, mysql, mariadb, pgsql y sqlsrv. El fallback de `database.default` es sqlite, pero **no es la conexión activa**. SQLite declara foreign_key_constraints=true por defecto; MySQL/MariaDB tienen prefijos vacíos, prefix_indexes=true, configuración de URL/socket y SSL opcional. El repositorio de migraciones es `migrations`. Redis se configura como servicio adicional, no como catálogo relacional. No se imprimieron contraseñas, usuarios de conexión, tokens, claves ni URL de conexión con credenciales.

La inspección utilizó PDO desde el bootstrap de Laravel, configuración con lista explícita de campos públicos, `SELECT VERSION()`, `SHOW TABLES`, `SHOW CREATE TABLE`, conteos y lectura del registro de migraciones. El primer recorrido del esquema se realizó en una transacción marcada READ ONLY y cerrada sin escrituras. No se hicieron llamadas a Mercado Pago ni peticiones HTTP de compra.

## 3. Tablas actuales

Conteos exactos al momento de lectura; se presentan como referencia temporal, no como volumen productivo.

| Tabla | Propósito | Modelo | Registros aprox. | Observaciones |
|---|---|---|---:|---|
| categorias | Categorías vendibles | Categoria | 0 | Nombre y slug únicos |
| productos | Catálogo comprable | Producto | 0 | FK obligatoria a categoría |
| ingredientes | Opciones de composición | Ingrediente | 0 | Tipo enum; costo ambiguo |
| ingrediente_producto | Composición/cantidades | Pivote Eloquent | 0 | Sin unicidad de pareja |
| producto_swappable_ingredient | Sustituciones permitidas | Pivote Eloquent | 0 | tipo no expuesto como pivot del modelo |
| promociones | Promociones heredadas | Promocion | 0 | Sin flujo de compra conectado |
| promocion_handroll | Productos de una promoción | Pivote Eloquent | 0 | cantidad; sin unicidad de pareja |
| orders | Pedido, cliente, entrega y pago actual | Order | 0 | Estado operativo separado del pago |
| order_items | Líneas con snapshot | OrderItem | 0 | FK de producto con cascada peligrosa |
| users | Cuentas y perfil | User | 1 | Una dirección plana; rol booleano |
| password_reset_tokens | Recuperación de acceso | Laravel, sin modelo propio | 0 | PK email, sin FK |
| sessions | Sesiones y carrito serializado | Laravel, sin modelo propio | 4 | user_id indexado sin FK |
| cache | Caché de aplicación | Laravel | 0 | PK key |
| cache_locks | Locks de caché | Laravel | 0 | Importante para bloqueo de checkout |
| jobs | Cola persistente | Laravel | 0 | Sin modelo propio |
| job_batches | Lotes de cola | Laravel | 0 | Sin modelo propio |
| failed_jobs | Errores de cola | Laravel | 0 | uuid único |
| migrations | Historial de migraciones | Laravel | 34 | 33 batch 1; última batch 2 |

## 4. Esquema detallado

### Convenciones de lectura

Esquema final reconstruido de todos los `up()` y cotejado con `SHOW CREATE TABLE`. No se ejecutó ningún `up()` ni `down()`.

- `R`: NOT NULL, sin DEFAULT explícito salvo que se indique. `N`: nullable, DEFAULT NULL en la BD real.
- `id BAI`: BIGINT UNSIGNED AUTO_INCREMENT, PK, R. `V`: VARCHAR(255), salvo longitud indicada.
- `TS`: created_at y updated_at TIMESTAMP N. **Ninguna tabla usa soft deletes/deleted_at**.
- Todos los índices son no únicos salvo PK o UNIQUE. Todas las FKs tienen ON UPDATE implícito RESTRICT/NO ACTION; ninguna migración declara cascada de actualización. Cada FK real tiene índice de soporte indicado debajo.
- MariaDB representa `json()` como LONGTEXT utf8mb4_bin con CHECK json_valid; no es una columna extra ni una discrepancia.
- No hay FLOAT/DOUBLE en columnas. No hay CHECK monetario no negativo, ni restricciones SQL de límites de ingredientes o cantidades positivas. Los únicos CHECK observados son los tres JSON de order_items.

### users

Creada por `0001_01_01_000000_create_users_table.php`.

| Columna | Tipo y restricciones |
|---|---|
| id | BAI |
| name | V R |
| email | V R |
| phone | V N |
| address | TEXT N |
| birthdate | DATE N |
| avatar | V N |
| is_admin | TINYINT(1) R DEFAULT 0 |
| email_verified_at | TIMESTAMP N |
| password | V R |
| remember_token | VARCHAR(100) N |
| created_at, updated_at | TS |

PK(id); UNIQUE `users_email_unique`(email); sin FKs. Modificada por `2025_05_12_172710_add_is_admin_to_users_table`, `2025_05_24_192030_add_phone_to_users_table`, `2025_05_24_192121_add_profile_fields_to_users_table` (todos .php).

### password_reset_tokens

Creación: `0001_01_01_000000_create_users_table.php`. email V R PK; token V R; created_at TIMESTAMP N. Sin updated_at, FK ni otros índices. Sin modificaciones posteriores.

### sessions

Misma migración de users. id V R PK; user_id BIGINT UNSIGNED N; ip_address VARCHAR(45) N; user_agent TEXT N; payload LONGTEXT R; last_activity INT R. Índices `sessions_user_id_index`(user_id), `sessions_last_activity_index`(last_activity). No FK a users; no timestamps convencionales ni modificaciones posteriores.

### cache y cache_locks

Creación: `0001_01_01_000001_create_cache_table.php`. cache: key V R PK; value MEDIUMTEXT R; expiration INT R. cache_locks: key V R PK; owner V R; expiration INT R. Sin timestamps, FKs, otros índices ni modificaciones posteriores.

### jobs, job_batches y failed_jobs

Creación: `0001_01_01_000002_create_jobs_table.php`; sin modificaciones posteriores.

- jobs: id BAI; queue V R; payload LONGTEXT R; attempts TINYINT UNSIGNED R; reserved_at INT UNSIGNED N; available_at INT UNSIGNED R; created_at INT UNSIGNED R. PK(id), `jobs_queue_index`(queue). Sin updated_at/FKs.
- job_batches: id V R PK; name V R; total_jobs/pending_jobs/failed_jobs INT R; failed_job_ids LONGTEXT R; options MEDIUMTEXT N; cancelled_at INT N; created_at INT R; finished_at INT N. Sin FKs/otros índices/updated_at. `options` no es JSON SQL.
- failed_jobs: id BAI; uuid V R; connection TEXT R; queue TEXT R; payload LONGTEXT R; exception LONGTEXT R; failed_at TIMESTAMP R DEFAULT CURRENT_TIMESTAMP. PK(id), UNIQUE `failed_jobs_uuid_unique`(uuid). Sin TS convencionales/FKs.

### categorias

Creación: `2025_05_12_152605_create_categorias_table.php`. id BAI; nombre V R; slug V R; TS. PK(id), UNIQUE `categorias_nombre_unique`(nombre), UNIQUE `categorias_slug_unique`(slug). Sin modificaciones posteriores ni FK saliente.

### productos

Creación: `2025_05_12_130330_create_productos_table.php`.

| Columna final | Tipo y restricciones |
|---|---|
| id | BAI |
| nombre | V R |
| descripcion | TEXT R |
| categoria_id | BIGINT UNSIGNED R, FK |
| precio | DECIMAL(8,2) R, precio de venta |
| imagen | V N, ruta de archivo |
| personalizable | TINYINT(1) R DEFAULT 0 |
| unidades | INT R DEFAULT 1 |
| free_extras | INT R DEFAULT 0 |
| free_extras_Proteínas | INT R DEFAULT 0; nombre con mayúscula/acento |
| free_extras_vegetales | INT R DEFAULT 0 |
| slug | V R UNIQUE |
| rolls_total | INT UNSIGNED R DEFAULT 0 |
| rolls_envueltos | INT UNSIGNED R DEFAULT 0 |
| rolls_fritos | INT UNSIGNED R DEFAULT 0 |
| created_at, updated_at | TS |

PK(id); UNIQUE `productos_slug_unique`(slug); índice/FK `productos_categoria_id_foreign`(categoria_id) → categorias.id ON DELETE CASCADE. Nombre no único. Sin stock, estado activo, orden comercial, SKU, moneda, tamaño/variante ni costo de receta.

Historial posterior (prefijo completo bajo database/migrations, extensión .php):

1. `2025_05_12_171456_add_categoria_id_to_productos_table`: agrega FK y elimina categoria string original.
2. `2025_05_12_172133_add_slug_to_productos_table`: slug único.
3. `2025_05_12_180452_add_unidades_to_productos_table`: unidades.
4. `2025_05_17_223901_add_rolls_to_productos_table`: tres contadores rolls.
5. `2025_05_21_175653_add_free_extras_to_productos_table`: free_extras.
6. `2025_05_21_215300_add_free_extras_by_type_to_productos`: dos contadores por tipo.

### ingredientes

Creación: `2025_05_12_153040_create_ingredientes_table.php`. Final: id BAI; nombre V R; tipo ENUM('envoltura','proteina','vegetal') R DEFAULT 'vegetal'; costo DECIMAL(8,2) N; TS. Solo PK(id); nombre no tiene UNIQUE aunque el controlador lo valida. costo no tiene unidad física, moneda ni vigencia.

`2025_05_21_215505_update_tipo_enum_on_ingredientes.php` reemplaza enum original base/extra: base se convierte a proteina, extra a vegetal; otros casos caen en el default nuevo. Borra columna temporal. Transformación histórica semántica, no clasificación física de inventario.

### ingrediente_producto

Creación: `2025_05_14_022752_create_ingrediente_producto_table.php`. id BAI; producto_id BIGINT UNSIGNED R; ingrediente_id BIGINT UNSIGNED R; cantidad_permitida TINYINT UNSIGNED N; TS. PK(id). Índices/FKs `ingrediente_producto_producto_id_foreign` → productos.id y `ingrediente_producto_ingrediente_id_foreign` → ingredientes.id; ambas DELETE CASCADE. Sin UNIQUE(producto_id, ingrediente_id), sin receta física ni cambios posteriores.

### producto_swappable_ingredient

Creación: `2025_05_12_185020_create_producto_swappable_ingredient_table.php`. id BAI; producto_id/ingrediente_id BIGINT UNSIGNED R; tipo V R; TS. PK(id). Índices/FKs `producto_swappable_ingredient_producto_id_foreign` → productos.id y `producto_swappable_ingredient_ingrediente_id_foreign` → ingredientes.id; DELETE CASCADE en ambas. Sin UNIQUE de pareja. `tipo` agregado por `2025_05_26_033755_add_tipo_to_producto_swappable_ingredient_table.php`, sin default; el modelo no usa withPivot('tipo').

### promociones y promocion_handroll

Creación de ambas: `2025_05_12_140724_create_promocion_table.php`; sin alteraciones posteriores.

- promociones: id BAI; nombre V R; descripcion TEXT N; precio DECIMAL(8,2) R; cantidad_rolls INT R DEFAULT 1; personalizable TINYINT(1) R DEFAULT 1; categoria V R DEFAULT 'promocion'; TS. Solo PK(id); sin slug, imagen, categoría FK ni relación al producto vendible principal.
- promocion_handroll: id BAI; promocion_id/producto_id BIGINT UNSIGNED R; cantidad INT R DEFAULT 1; TS. PK(id). Índices/FKs `promocion_handroll_promocion_id_foreign` → promociones.id y `promocion_handroll_producto_id_foreign` → productos.id; ambas DELETE CASCADE. Sin unicidad de pareja.

### orders

Creación: `2025_05_12_144710_create_orders_table.php`.

| Columna final | Tipo y restricciones |
|---|---|
| id | BAI |
| cliente_nombre, cliente_telefono | V R |
| cliente_direccion | V N |
| metodo_entrega | VARCHAR(20) R DEFAULT 'pickup' |
| zona_delivery | VARCHAR(20) N |
| kms_fuera | SMALLINT UNSIGNED N |
| subtotal, delivery_cost | DECIMAL(10,2) R DEFAULT 0.00 |
| cliente_comentarios | TEXT N |
| total | DECIMAL(10,2) R |
| status | V R DEFAULT 'pendiente', sin enum SQL |
| mp_payment_id | V N; sin UNIQUE/índice dedicado |
| user_id | BIGINT UNSIGNED N |
| payment_status | VARCHAR(30) N, sin enum SQL |
| payment_updated_at | TIMESTAMP N |
| created_at, updated_at | TS |

PK(id), índice/FK `orders_user_id_foreign` → users.id DELETE SET NULL. Índice `orders_payment_status_index`(payment_status). Sin FK de pago, preference_id persistido, external_reference persistida separada, moneda o historial de estados.

Cambios posteriores:

1. `2025_05_12_195457_add_status_to_orders_table`: status.
2. `2025_05_24_191748_add_user_id_to_orders_table`: user_id nullable con SET NULL.
3. `2025_05_27_000000_update_orders_checkout_fields`: comentarios → cliente_comentarios; agrega dirección, método, zona, kms, subtotal y delivery_cost condicionalmente.
4. `2025_10_03_144455_add_mp_payment_id_to_orders`: identificador de pago.
5. `2026_10_21_000001_add_payment_verification_to_orders`: estado verificado, índice y fecha. NULL significa histórico/no verificado, no pago aprobado.

### order_items

Creación: `2025_05_12_144800_create_order_items_table.php`.

| Columna final | Tipo y restricciones |
|---|---|
| id | BAI |
| order_id, product_id | BIGINT UNSIGNED R |
| nombre | V R, snapshot |
| unidades | INT R DEFAULT 1, cantidad comprada |
| precio_unit, total | DECIMAL(10,2) N en la BD real |
| detalle | JSON lógico/LONGTEXT CHECK json_valid, R |
| removed_bases, extras | JSON lógico/LONGTEXT CHECK json_valid, N |
| created_at, updated_at | TS |

PK(id); índice `order_items_product_id_foreign`(product_id); índice `order_items_order_id_product_id_index`(order_id,product_id). FKs `order_items_order_id_foreign` → orders.id y `order_items_product_id_foreign` → productos.id; DELETE CASCADE ambas. El compuesto soporta la FK order_id; no se observa un índice simple adicional de order_id en el esquema final. Sin UNIQUE por línea, sin descripción snapshot ni configuración de IDs versionada.

Cambios posteriores, en orden:

1. `2025_05_14_062607_add_details_to_order_items_table`: nombre, unidades, precio_base DECIMAL(10,2), subtotal DECIMAL(10,2).
2. `2025_05_14_063520_drop_price_from_order_items_table`: elimina price original.
3. `2025_05_23_154917_rename_precio_base_in_order_items`: precio_base → precio_unit, cambia temporalmente a DECIMAL(10,0), agrega índice compuesto.
4. `2025_05_23_160029_add_total_to_order_items_table`: total DECIMAL(10,0), si falta.
5. `2025_05_23_160217_add_detalle_to_order_items_table`: detalle JSON obligatorio, si falta.
6. `2025_05_23_160344_drop_subtotal_from_order_items`: elimina subtotal.
7. `2025_05_27_000100_adjust_order_items_amount_precision`: precio_unit y total a DECIMAL(10,2). En MySQL/MariaDB usa SQL MODIFY sin NOT NULL: quedan nullable. En SQLite copia a columnas temporales explícitamente nullable. Es consecuencia del archivo, no prueba de cambio manual.

### migrations

Tabla técnica creada por el repositorio de migraciones de Laravel, sin archivo Schema::create propio: id INT UNSIGNED AUTO_INCREMENT PK R; migration V R; batch INT R. Sin TS, FKs ni UNIQUE(migration). 34 filas: archivos hasta octubre 2025 batch 1, archivo de verificación de pagos 2026 batch 2.

### Comparación con BD real y límites de la reconstrucción

- 34 archivos = 34 nombres registrados; **cero pendientes**, cero registros sin archivo y cero tablas declaradas ausentes.
- Las 17 tablas declaradas existen. La única tabla adicional al listado de Schema::create es `migrations`, esperada por Laravel.
- No se detectaron columnas, índices, FKs, defaults o restricciones ajenas al resultado de las migraciones revisadas. No se detectaron cambios manuales de esquema; esta conclusión no es una certificación de ausencia de cambios de datos o de cambios transitorios revertidos.
- JSON como LONGTEXT con CHECK y booleans como TINYINT(1) son representaciones normales de MariaDB. La nulabilidad monetaria final de order_items también está explicada por el SQL de migración.
- El registro de migraciones no guarda hashes; no demuestra que los archivos actuales sean idénticos a los ejecutados históricamente. No se ensayó reconstrucción mediante migraciones porque esta tarea lo prohíbe.
- El archivo `database/migrations.zip` es un archivo auxiliar, no una migración ejecutable del directorio; no se extrajo ni se usó como fuente oficial del esquema activo.

## 5. Modelos Eloquent

Se revisó todo app/Models y se buscaron modelos fuera de ese directorio. Solo se encontraron los siete siguientes. Todos usan timestamps convencionales e id incremental heredado; el cast implícito de id es int. No declaran conexión alternativa, scopes, observers, SoftDeletes ni mutators propios de precio. AppServiceProvider no registra observers.

| Modelo → tabla | Asignación masiva | Casts explícitos | Relaciones y comportamiento |
|---|---|---|---|
| Categoria → categorias | fillable nombre, slug | Ninguno | hasMany productos por categoria_id; creating/updating regeneran slug con Str::slug |
| Producto → productos | guarded=[]; fillable vacío | personalizable boolean | belongsTo categoria; belongsToMany ingredientes con cantidad_permitida; belongsToMany swappables mediante tabla explícita; HasFactory |
| Ingrediente → ingredientes | fillable nombre, tipo, costo | Ninguno | belongsToMany productos con cantidad_permitida y withTimestamps |
| Promocion → promociones | fillable nombre, descripcion, precio, cantidad_rolls, personalizable, categoria | Ninguno | belongsToMany productos por promocion_handroll, withPivot cantidad |
| Order → orders | fillable cliente_nombre, cliente_telefono, cliente_direccion, cliente_comentarios, metodo_entrega, zona_delivery, kms_fuera, subtotal, delivery_cost, total, status, mp_payment_id, payment_status | payment_updated_at immutable_datetime; subtotal/delivery_cost/total decimal:2; kms_fuera integer | hasMany items; belongsTo user; appends envio, accessor devuelve delivery_cost o 0 |
| OrderItem → order_items | fillable order_id, product_id, nombre, unidades, precio_unit, total, detalle | removed_bases/extras/detalle array; precio_unit/total decimal:2 | belongsTo producto con product_id; no belongsTo order declarado |
| User → users | fillable name, email, phone, address, birthdate, avatar, password | email_verified_at datetime; password hashed; is_admin boolean; birthdate date | hasMany orders; HasFactory, Notifiable; isAdmin() devuelve boolean |

Excepto Producto, los modelos conservan guarded=['*'] heredado y permiten su lista fillable. Solo User tiene hidden explícito: password y remember_token. Los demás tienen hidden y appends vacíos salvo Order.appends=['envio']. Esto no significa que todos sean expuestos por endpoints públicos.

Producto crea slugs con sufijos al detectar colisiones; al cambiar nombre regenera el slug. La consulta previa no evita una carrera concurrente: UNIQUE es la protección final. Categoria no genera sufijos; dos nombres diferentes que normalicen al mismo slug pueden producir error de unicidad. Los slugs no son route keys personalizados: compra usa IDs.

`Producto::envolturas()` existe como método vacío: **no es una relación funcional**. Las envolturas reales se filtran desde ingredientes por tipo. swappables no expone pivot.tipo ni timestamps; Producto.ingredientes tampoco usa withTimestamps aunque la relación inversa sí. Ningún modelo ofrece scopes de activo/disponible o descuento de stock.

No existen modelos Product/Category en inglés, Cart/CartItem, Payment/MercadoPago, Address/Direccion, variante, extra, receta, proveedor, compra o movimiento. Una dirección se almacena en users.address y se copia la dirección de entrega en orders, sin libreta relacional.

## 6. Diagrama lógico

```text
User [users]
  hasMany Order [orders]                      FK orders.user_id, DELETE SET NULL
Order
  belongsTo User
  hasMany OrderItem [order_items]             FK order_id, DELETE CASCADE
OrderItem
  belongsTo Producto [productos]              FK product_id, DELETE CASCADE
  -- falta el belongsTo Order inverso en el modelo

Categoria [categorias]
  hasMany Producto                           FK categoria_id, DELETE CASCADE
Producto
  belongsTo Categoria
  belongsToMany Ingrediente [ingredientes]
    via ingrediente_producto                 dos FK, DELETE CASCADE
    pivot cantidad_permitida
  belongsToMany Ingrediente como swappables
    via producto_swappable_ingredient        dos FK, DELETE CASCADE
Ingrediente
  belongsToMany Producto via ingrediente_producto

Promocion [promociones]
  belongsToMany Producto
    via promocion_handroll                   dos FK, DELETE CASCADE
    pivot cantidad

sessions.user_id -- asociación lógica a users.id sin FK
password_reset_tokens.email -- asociación lógica a users.email sin FK
Order -- pago actual embebido, SIN hasOne Payment
Sesión -- cart/configuration, SIN tablas Cart/CartItem
```

Las relaciones Eloquent funcionales encontradas sí tienen soporte de FK. Faltan inversas opcionales: Producto.orderItems, Producto.promociones, Ingrediente.productosSustituibles y OrderItem.order. Los pivotes no tienen modelo propio. Las dos asociaciones técnicas sin FK no están declaradas como relaciones en User. `envolturas()` no puede considerarse relación con integridad referencial porque no devuelve una relación Eloquent.

## 7. Catálogo actual

El catálogo comprable es **productos**, no promociones. Cada producto pertenece a una categoría obligatoria y tiene nombre, descripción TEXT, precio DECIMAL, imagen, slug único y personalizable. Carece de activo/inactivo, orden configurable, SKU, disponibilidad horaria, variantes, unidad física o versión de receta. La imagen es ruta a almacenamiento público; hay fallback visual. Las categorías tienen nombre/slug únicos, sin imagen propia (Home deriva una desde productos), orden ni estado.

El menú filtra por categoria.slug con parámetro `filter`, ordena por precio/nombre y pagina 12; precarga ingredientes y swappables. Blade enlaza categorías ya cargadas para evitar una consulta por tarjeta. Home consulta categorías y productos directamente desde Blade, elige cuatro por nombre/id y el primer personalizable; no hay productos destacados/popularidad persistidos.

Personalización: ingrediente.tipo es envoltura/proteina/vegetal. ingrediente_producto determina envolturas permitidas y composición inicial; cantidad_permitida se utiliza como cantidad por defecto, pese a su nombre de límite. Las cantidades heredadas múltiplos de 10 se normalizan a rolls. producto_swappable_ingredient restringe sustituciones cuando contiene registros; si está vacío, se permite el conjunto global del tipo correspondiente. Solo se elige **una envoltura por configuración completa**, no una por bloque de promoción.

No hay tamaño o variante relacional. unidades y rolls_* son metadatos numéricos, no una arquitectura de tamaños. En carrito/order_items, unidades significa cantidad comprada, distinta del campo homónimo del producto.

Promocion y promocion_handroll existen pero ningún flujo de carrito/checkout consume Promocion. Los enlaces de navegación `section=promociones` no aplican un filtro comercial en ProductoController (solo procesa filter/sort). Home explícitamente no muestra promociones desconectadas. No hay una carta completa hardcodeada de Nikkei, AkilaBurger, Muchupleto, Gohan, salsas o arrollados. Con tablas vacías, tampoco hay evidencia de esas familias cargadas en la BD actual.

## 8. Carrito

Rutas POST cart/quote, add, update, remove, clear; GET cart. Quote tiene throttle 60/min. CartController valida IDs existentes y cantidad entera 1–50. El carrito es `session('cart')`, persistido indirectamente en sessions por el driver database; no se consulta una tabla de líneas de carrito.

Cada línea contiene:

```text
hash, producto_id, nombre, unidades,
precio_base, recargo, precio_unit, total,
configuration = {envoltura_id, Proteínas:[ids repetidos], vegetales:[ids repetidos],
                 cream_cheese:boolean, scallions:boolean},
detalle = {Base:nombre, Proteínas:[nombres], Vegetales:[nombres],
           "Sin queso":boolean, "Sin cebollín":boolean}
```

El hash SHA-256 usa producto_id y configuración normalizada, sin cantidad/precio. Se ordenan arrays para fusionar configuraciones equivalentes. Agregar suma cantidades si existe el hash y vuelve a cotizar, con máximo 50 por línea y 100 configuraciones. Update cambia cantidad y refreshCart recalcula todo; remove elimina por hash; clear olvida cart. La página de carrito suma los totales guardados (también lo repite Blade); no refresca el catálogo por el solo hecho de mostrarla. El checkout sí lo refresca.

ProductPricingService es la autoridad de precio. Acepta IDs y booleanos, no confía en precio, recargo o total enviado por el navegador. El navegador puede técnicamente enviar cualquier campo, pero no se utiliza como autoridad monetaria. Valida tipo, existencia, envoltura asignada, opciones permitidas, cantidad de selecciones igual a composición base y personalizable. Un producto no personalizable exige configuración idéntica a sus defaults.

Precio actual: precio BD normalizado mediante round(precio*100); por cada aparición de proteína/vegetal que exceda su cantidad base cobra **$1.000**, independientemente de ingrediente.costo. Límite por ingrediente: si nombre contiene `salm`, 1; otros, 2; se amplía al número base ya configurado. No es una regla por promoción, no hay recargo de envoltura ni precio individual de extra. free_extras* no participa en este cálculo. cream_cheese/scallions significan excluir queso/cebollín y no generan precio propio.

Frontend activo: app.js importa storePersonalization.js; inicializa la composición desde el modal, manda POST /cart/quote y muestra precio del servidor antes de enviar el formulario a cart.add. cart.js es placeholder; index.js/productModal.js/formValidation.js no aportan lógica activa de precio. El selector de sustitución lista ingredientes por tipo global y puede ofrecer opciones que después rechace el servidor cuando haya swappables explícitos.

Contratos a conservar: rutas/nombres, claves con acentos y mayúsculas, hash estable o transición versionada, configuration obligatoria, shape de detalle, contador por unidades y recalculado del servidor. refreshCart rechaza sesiones antiguas sin configuration: una ampliación necesita lector por versión o estrategia de revisión, no interpretar JSON histórico silenciosamente como nuevo.

## 9. Pedidos

Flujo actual: carrito → GET checkout (recalcula) → POST checkout (valida y recalcula) → transacción orders+items → preferencia Mercado Pago → retorno/webhook verificado → detalle/historial/admin/PDF.

El POST checkout usa bloqueo de sesión de ruta `block(30,30)`. Si cambia el precio unitario/hash respecto a sesión, sustituye carrito y pide revisión; no crea pedido en ese paso. Subtotal suma líneas del servidor. Delivery: retiro 0; dentro $2.500; fuera $2.500 + $500/km, distancia validada 1–100. El cliente declara zona/distancia; no hay cálculo geográfico ni tabla de tarifas.

Pedido y líneas se crean en DB::transaction. Para usuarios autenticados asocia user_id explícitamente con user()->associate; no es fillable. Invitados conservan guest_orders en sesión. Se guardan datos de contacto/dirección del pedido, separados del perfil.

| Snapshot | ¿Se conserva? | Alcance |
|---|---|---|
| Nombre de producto | Sí | order_items.nombre |
| Cantidad comprada | Sí | order_items.unidades |
| Precio unitario final/total | Sí | precio_unit/total, con recargo ya incluido |
| Descripción del producto | **No** | No hay columna snapshot |
| Envoltura e ingredientes elegidos | Parcialmente | detalle con nombres; sin IDs de configuración ni bloque |
| Exclusiones queso/cebollín | Sí | Booleanos de detalle |
| Extras comerciales independientes | **No** | extras JSON existe pero checkout no lo llena |
| Desglose precio base/recargo/opción | **No** | Solo precio final; no copia recargo/configuration |
| Delivery y total pedido | Sí | orders.subtotal/delivery_cost/total |
| Receta/costos/unidad física/reglas vigentes | **No** | No están modelados |

Cambiar nombre/precio de producto no cambia snapshots, preferencia o PDF generado desde esos campos. **Eliminar producto/categoría sí elimina order_items por FK**; no elimina necesariamente orders ni su total, dejando un pedido monetariamente inconsistente y un PDF regenerado incompleto. Ningún modelo usa SoftDeletes.

Historial: ProfileController.orders obtiene todos los pedidos propios latest()->get(), sin paginación. Admin lista 20 por página, detalle carga items.producto y permite estados pendiente/en preparación/entregado, separados de payment_status. El dashboard suma todos los pedidos para ventas, sin filtrar aprobados; ranking usa product_id y consulta nombre actual del producto.

OrderAccessService permite propietario; invitado necesita sesión guest_orders o firma temporal. Admin usa sus rutas protegidas por auth+admin. PDF usa OrderInvoiceService, disco privado local, nombre boletas/boleta-{id}.pdf, reutiliza copia pública histórica si existe y genera desde items si falta. El PDF usa nombre/precio/detalle snapshots, más alias Order.envio. Documento ya guardado no se recalcula automáticamente. No se ejecutó su generación ni el comando invoices:privatize.

## 10. Pagos

No hay tabla payments ni modelo Payment. Estado actual embebido en orders: mp_payment_id, payment_status, payment_updated_at; status representa preparación/entrega. Payment_status permitido por servicio: pending, in_process, authorized, approved, rejected, cancelled, refunded, charged_back. El SQL no impone esos valores. NULL es histórico/no verificado, no aprobado.

MercadoPagoService construye preferencia desde **Order.items snapshots**: product_id como id externo del ítem, nombre, unidades y precio_unit. Agrega delivery como ítem independiente para que el total cobrado incluya envío. Moneda CLP hardcodeada; external_reference = string(order.id). Firma back_urls por dos días y preferencia expira en un día. Configuración de secretos está en config/services.php, sin divulgar valores.

El retorno success/failure/pending no prueba pago. MercadoPagoPaymentService consulta API, valida id, external_reference, pedido esperado, moneda CLP, monto igual a orders.total, estado permitido y fecha del proveedor. Bloquea fila con lockForUpdate dentro de transacción. Guarda estado/ID/fecha; no modifica status operativo. Ignora repeticiones idénticas, eventos más antiguos y degradaciones de aprobado/terminal desde otro intento. Maneja refunded/charged_back y no convierte luego a approved. Mantiene una protección de compatibilidad para status histórico pagado sin payment_status.

Webhook exige firma HMAC con timestamp dentro de 600 segundos y coherencia del ID recibido. Consulta API; errores de validación devuelven 422 y temporales 503. Solo la ruta webhook está exceptuada de CSRF; no se desactiva el resto. Hay logs de verificación, pero no un historial relacional consultable de eventos ni payload completo del proveedor.

Reintentos: huella SHA-256 de datos checkout+carrito+usuario; sesión checkout_attempts asocia huella con order.id. Reutiliza pedido no terminal y preferencia vigente desde checkout_preferences; si falla la API conserva pedido/carrito. Reembolsados/contracargos crean nuevo intento. Al completar un pago aprobado, CheckoutSessionService borra carrito solo si coincide con el snapshot checkout_cart y libera intento/preferencia. No depende de volver al sitio para persistir pago: webhook también actualiza orders. Limpiar sesión/pasar de dispositivo pierde la deduplicación, porque no existe registro durable de intentos/preferencias.

Cambiar Producto afecta nuevas cotizaciones y carritos activos, pero la verificación de pago no lee su precio actual. Cambiar Order/OrderItem, total, nombres de relaciones o columnas puede romper preferencia, verificación, accesos y PDFs. El product_id del payload sigue asociado al catálogo y sus líneas deben sobrevivir a su retiro. No se verificaron credenciales reales ni pagos reales en esta auditoría.

## 11. Seeders y factories

Se leyeron los tres seeders completos; ninguno fue ejecutado.

| Seeder | Operaciones y tablas | Dependencias/idempotencia |
|---|---|---|
| DatabaseSeeder | Categoria::firstOrCreate por slug hand-roll; llama ProductoSeeder | Parte categoría repetible mientras nombre/slug sean coherentes; conjunto NO idempotente |
| ProductoSeeder | Producto::create, Hand Roll de Salmón, precio 10000, descripción e imagen sushi.jpg | Necesita categoría hand-roll existente; repetición crea duplicados de nombre con nuevos slugs; no carga ingredientes |
| IngredienteProductoSeeder | Busca siete slugs y tipos; Ingrediente::firstWhere/find; ingredientes()->sync | No llamado por DatabaseSeeder. Repetible lógicamente con entradas iguales, pero sync reemplaza asociaciones y cantidades; puede alterar configuraciones existentes |

No usan insert/updateOrCreate/upsert. No hay IDs numéricos de catálogo fijos: recuperan IDs por slug/nombre/tipo. Sí hay identificadores semánticos hardcodeados y comparaciones de nombre sensibles a variantes. IngredienteProductoSeeder aplica null a queso crema/cebollín, 2 a vegetales y proteínas, 1 a Salmon/Atún/Carne por coincidencia literal. Hace consultas por ingrediente dentro de bucles. Repetir sync no garantiza preservar IDs/timestamps de pivotes ni decisiones administrativas.

Única factory: **UserFactory**, para User, con nombre/email Faker, contraseña hasheada cacheada, remember_token y email_verified_at; estado unverified(). Reutilizable para pruebas de cuenta/pedido. Producto usa HasFactory pero **no existe ProductoFactory**. No hay factories para categoría/ingrediente/pedido/línea/promoción. CommerceSecurityTest contiene helpers akilaProduct/akilaOrder y fixtures creadas en pruebas, no una carga oficial de menú. No se ejecutó la suite, pues usa RefreshDatabase y esta tarea solo autoriza diagnóstico de lectura.

## 12. Inventario

**No existe sistema de inventario**, ni por producto ni por ingrediente. No hay stock actual, reservas, unidades de medida, depósitos, movimientos, lotes, compras, proveedores, recetas cuantificadas, mermas, producción o costo de receta.

Existe ingredientes.costo DECIMAL nullable, presentado todavía como costo de extras en admin, pero no es costo unitario físico y no alimenta ProductPricingService. ingrediente_producto es composición/selección, sin gramos/ml/unidades ni rendimiento. rolls_* y unidades de productos no son stock. No se descuenta ni reserva nada al agregar carrito, crear pedido, aprobar pago, entregar o reembolsar.

## 13. Datos hardcodeados

Referencias aproximadas del árbol auditado; no son propuestas de cambios.

| Archivo y líneas | Dato/regla |
|---|---|
| app/Services/ProductPricingService.php:14–35 | Tipos y claves de configuración; primer wrapper; cantidad_permitida como composición; normalización de 10 cortes; límites 100 |
| mismo:12,41–46,103 | Máximo 50 unidades/línea, 100 selecciones y 100 líneas |
| mismo:66–81 | Fallback global de sustituciones; nombre contiene salm → 1, resto 2; recargo fijo 1000 |
| mismo:92–97 | Nombres de claves detalle, exclusiones fijas queso y cebollín |
| app/Http/Controllers/CheckoutController.php:49–56,163–178 | pickup/delivery, dentro/fuera, kms 1–100, base 2500 + 500/km |
| resources/views/checkout/index.blade.php:26–36 | Repite tarifas, zonas y métodos en textos/frontend |
| app/Services/MercadoPagoService.php:39–66 | CLP, delivery, descriptor Sushi Akila, referencia order.id, vencimientos |
| app/Services/MercadoPagoPaymentService.php:33–57 | Lista de estados y reglas terminales; legado pagado |
| resources/views/components/store/order-status.blade.php:3–8 | Etiquetas y clasificación visual de estados |
| resources/js/modules/storePersonalization.js:27–34,79–85 | Solo dos listas proteína/vegetal, etiqueta ×10, endpoint /cart/quote |
| resources/views/components/modal-producto.blade.php:1–5,36–41,85–89 | Defaults vía servicio dentro de Blade; texto rolls de diez; exclusiones fijas |
| database/seeders/DatabaseSeeder.php:16–21 | hand-roll/Hand Roll y único seeder llamado |
| database/seeders/ProductoSeeder.php:14–23 | Hand Roll de Salmón, descripción, 10000, sushi.jpg |
| database/seeders/IngredienteProductoSeeder.php:16–40 | 3-hand-rolls, 4-hand-rolls, 5-hand-rolls, tradicional, relleno-doble, xl, lo-nuevo; queso crema, cebollín, Salmon/Atún/Carne; límites 1/2 |
| resources/views/home.blade.php:7–17,94 | Consultas catálogo en Blade; cuatro productos por nombre y primer personalizable; no promociones conectadas |
| resources/views/components/store/header.blade.php:8,15,25; footer.blade.php:6 | Enlace section=promociones sin implementación de filtro de compra |
| app/Http/Controllers/Admin/IngredienteController.php:54,81; resources/views/admin/ingredientes/_form.blade.php:27–29 | Tipos antiguos base/extra aún usados en lógica/etiqueta |
| resources/views/cart/index.blade.php:4–7,50–64,130–145 | Suma y agrupación de detalle en Blade; texto Rolls en móvil |
| resources/views/pdf/invoice.blade.php:103–132 | Estructura fija Base/Proteínas/Vegetales/Sin queso/Sin cebollín |

Los importes en tests/Feature/CommerceSecurityTest.php:18–56 son fixtures de pruebas, no menú productivo. No se encontraron listas completas con precios del futuro menú en configuración/controladores/Blade/JS. Los textos institucionales de Home no equivalen a productos comprables. No existen enums PHP de catálogo; tipo ingrediente es enum SQL y otras reglas son arrays/condiciones PHP.

## 14. Problemas encontrados

Severidad según impacto al existir ventas; las tablas comerciales locales hoy están vacías. No se corrigió ninguno.

### CRÍTICO

- **Destrucción de líneas históricas por cascada**: categorias → productos → order_items. Admin permite eliminar categoría/producto directamente. Borrar producto deja orders.total sin sus líneas, altera historial/PDF regenerado y puede impedir crear preferencia correcta. Migraciones de FK y destroy de ambos controladores lo permiten. No se probó mediante borrado.

### ALTO

- **Composición y reglas confundidas**: cantidad_permitida se interpreta como cantidad base y, si ≥10, como cortes; no es límite configurable real. Seeder la trata como límite. No sirve como receta de inventario ni regla por bloque.
- **Reglas de negocio insuficientes para el nuevo menú**: recargo único, detección por nombre salm, una envoltura global, sin grupos/bloques/salsas. costo y free_extras no gobiernan el precio actual.
- **Snapshots incompletos**: faltan descripción, IDs/versión de opciones, bloques, desglose de extras/recargos y receta efectiva. JSON extras existe pero no se alimenta. Mantener detalle evita cambios de nombre pero no permite reconstruir consumo físico con certeza.
- **Reintentos de pago solo por sesión**: no hay tabla de intentos/preferencias, clave de idempotencia durable ni historial de pagos. Perder sesión o cambiar datos puede crear pedidos/preferencias adicionales; el bloqueo actual cubre un POST por sesión, no todos los dispositivos.
- **Administración de composición no atómica**: Producto create/update y sync no están en una transacción conjunta. Se validan IDs de ingredientes sin asegurar que correspondan al tipo del campo; cantidades sin tope coherente con TINYINT/servicio. Un dato parcialmente guardado puede hacer fallar la cotización pública.

### MEDIO

- No hay activo/archivado: el mecanismo disponible para retirar catálogo es borrar, que agrava la cascada.
- Falta UNIQUE en parejas de los tres pivotes; Eloquent sync no sustituye integridad SQL ante otras escrituras/concurrencia.
- Sin restricciones SQL de positivos; order_items precio_unit/total nullable. No hay floats en BD, pero sí conversiones y sumas float en PHP; conviene una política monetaria única antes de nuevos descuentos.
- productos.guardeds vacío amplía superficie de asignación futura; controladores actuales seleccionan campos al guardar. No se afirma explotación actual.
- costo de ingredientes ambiguo, no único por nombre en SQL; validación de controlador no elimina carreras. Nombre de producto duplicable; slug de producto sí es único.
- Categoría regenera slug sin sufijos; producto consulta antes de generar sufijo, con carrera posible. Renombrar categoría cambia URLs de filtros.
- Promociones es segundo catálogo de nombre/descripcion/precio desconectado y su categoria es string; duplicar ventas ahí aumentaría ambigüedad.
- **N+1 estáticos evidentes**: Admin.ProductoController.index pagina sin precargar ingredientes y Blade accede prod.ingredientes; dashboard ranking accede item.producto sin eager loading (hasta cinco consultas extra). Menú público sí precarga y Blade reutiliza categorías; no se le atribuye ese N+1.
- Dashboard suma ventas y ranking sin filtrar pago aprobado: pedidos pendientes/rechazados inflan métricas. Ranking muestra nombre actual, no snapshot.
- ProfileController.orders carga todo sin paginar. Cotizador carga todos los ingredientes y el frontend los ofrece aunque swappables pueda restringirlos.
- Precios CLP se presentan redondeados a cero decimales en componentes aunque BD permite dos; decidir fracciones antes de descuentos. No confundir formato con pérdida de almacenamiento.
- SQL de administración de tipos (`SHOW COLUMNS`) y migración de precisión (`MODIFY`) acoplados a MySQL/MariaDB. Pruebas SQLite no garantizan cobertura del admin ni equivalencia de enums/decimales.
- Rollbacks heredados incompletos: create_promocion.down apunta a promocions y no revierte las dos tablas; add_details, add_user_id y add_free_extras tienen down vacío. Auditado por lectura, nunca ejecutado.
- Migraciones antiguas añaden FK/slug/columnas obligatorias sin backfill explícito; no asumir que pueden reproducirse sobre cualquier BD poblada o como upgrade incremental desde cualquier snapshot.

### BAJO

- Condiciones/etiquetas base y extra quedaron obsoletas frente a enum envoltura/proteina/vegetal.
- tipo obligatorio del pivote swappable no lo expone la relación y attach/sync sin ese campo puede fallar en MariaDB strict.
- Ingrediente.productos escribe timestamps de pivote y Producto.ingredientes no; consistencia de metadata desigual.
- Producto.envolturas vacío, inversas ausentes y columnas free_extras*/rolls_* sin uso en pricing actual dificultan mantenimiento.
- Doble almacenamiento de imagen en Admin.ProductoController.store: vuelve a guardar el archivo después de persistir producto; segunda ruta queda solo en variable local. Puede dejar archivo adicional huérfano.
- Lógica de consultas en Home y transformación de relaciones en Blade, duplicación de suma del carrito y repeticiones del pivote en admin.
- Índices de fechas/estados de operación y mp_payment_id no están presentes; evaluar con datos/planes reales, no crearlos automáticamente sobre tablas vacías.

## 15. Qué podemos reutilizar

- **categorias/Categoria**: identidad y slugs; añadir estado, orden y metadata sin reemplazar IDs.
- **productos/Producto**: única identidad vendible para todas las familias, incluso promociones, salsas y extras vendidos por separado; conservar precio/imagen/slug y FK histórica.
- **ingredientes/Ingrediente**: identidad de ingredientes y envolturas; extender con unidad física y política de inventario, sin convertir costo actual automáticamente en costo de compra.
- **ingrediente_producto**: mantener como relación legacy/selección durante transición. No reinterpretarla silenciosamente como gramos o receta.
- **producto_swappable_ingredient**: usar como fuente de backfill de opciones permitidas, conservar lectura legacy hasta migrar consumidores.
- **promociones/promocion_handroll**: reutilizar metadata/composición de packs establecidos si se integra mediante un producto vendible; no abrir otro camino paralelo de checkout.
- **orders/order_items**: conservar IDs, importes y snapshots; añadir versión y desglose sin reescribir los pedidos antiguos.
- Sesión/cart, cotizador del servidor, validación checkout, verificación de pago y servicios de acceso/PDF: conservar contratos mientras evoluciona su representación interna.

## 16. Qué falta implementar

Faltan: tipo de producto/familia, activo/orden, grupos/opciones con cardinalidad, opciones por bloque, reglas parametrizadas, precios por opción con ámbito, envolturas por bloque, extras, salsas incluidas por tamaño, recetas con cantidades y unidades, inventario/movimientos/reservas, tarifas delivery administrables, versiones de configuración y snapshots completos. También falta un registro durable de pagos/intentos si se requiere trazabilidad y deduplicación entre sesiones.

El menú futuro necesita representar promociones fijas y a elección, rolls, hand rolls, Nikkei, AkilaBurger, Muchupleto, Gohan, arrollados, extras y salsas como datos administrables. La familia/categoría organiza visualmente; las reglas/grupos controlan composición sin condiciones por nombre.

## 17. Propuesta de arquitectura

**Propuesta solamente; no hay tablas, migraciones ni código nuevos implementados.** Mantener nombres españoles existentes y usar nombres consistentes para extensiones. Los nombres nuevos son tentativos.

```text
categorias
  └─ productos                         identidad vendible única, tipo/activo/orden
       ├─ producto_grupo_opcion        grupo aplicable, orden, min/max, ámbito
       │    └─ grupos_opciones         proteína, vegetal, envoltura, salsa, extra
       │         └─ opciones          etiqueta/código; ingrediente_id opcional
       │              └─ producto_opcion   disponibilidad/recargo por producto
       ├─ recetas                     versión/rendimiento/estado
       │    └─ receta_ingredientes
       │         └─ ingredientes      cantidades físicas con unidad_medida
       └─ promociones                 producto_id único NUEVO, metadata de bundle
            ├─ promocion_handroll     componentes fijos existentes
            ├─ promocion_bloques      posición, piezas=10 configurable, grupos
            └─ promocion_reglas       ámbito, ingrediente/opción, límite y recargo

ingredientes
  ├─ unidades_medida / conversiones explícitas
  ├─ movimientos_inventario           entradas/salidas/mermas/ajustes/consumo
  ├─ reservas_inventario              vínculo pedido, caducidad/idempotencia
  └─ saldos_inventario                proyección por ubicación; no fuente única

orders
  ├─ order_items                      snapshots actuales + version/configuración
  │    └─ order_item_opciones         bloques/opciones y nombres/importes snapshot
  └─ intentos_pago                    proveedor, payment/preference/idempotencia

zonas_delivery ─ tarifas_delivery     vigencia; importe copiado al pedido
proveedores ─ compras ─ compra_items  entrada física/costos, fase posterior
```

1. No crear categories/products/ingredients paralelas. Una salsa o extra comprable es Producto con receta; una selección incluida es Opción que puede apuntar a ingrediente o a un producto componente cuando corresponda. Envoltura es ingrediente/opción, no otro catálogo independiente con precio duplicado.
2. Separar **receta física** de **configuración comercial**. ingrediente_producto conserva significado legacy; receta_ingredientes lleva gramos/ml/unidades, rendimiento y versión. Una opción ajusta composición efectiva para consumo, sin usar cantidad_permitida como stock.
3. Grupos reutilizables con min/max y restricciones por producto/bloque. Preferir reglas estructuradas con columnas de ámbito y cantidades; JSON versionado solo para parámetros excepcionales, validado por tipo. No introducir un lenguaje arbitrario de fórmulas ejecutables.
4. Promociones existentes se enlazan de forma aditiva a productos como metadata: un único precio base autoritativo para nuevas ventas. Mantener precio heredado sin borrarlo inicialmente, marcándolo como legacy y evitando que dos servicios decidan el precio. Packs fijos pueden reutilizar promocion_handroll; bloques elegibles requieren estructura propia.
5. Ejemplo futuro: promoción 30 piezas = tres bloques de diez; cada bloque aplica grupos proteína/vegetal/envoltura. Regla global de repetición cuenta opciones en los tres bloques; regla salmón máximo uno usa ID/código estable, no texto. Recargos por ingrediente/envoltura/extras se cotizan desde producto_opcion/reglas. Salsas incluidas se determinan por tamaño/regla y quedan snapshot, no derivadas del nombre del pedido.
6. Stock: ingredientes son entidades físicas cuando corresponda, marcadas stockeable. Registrar movimientos inmutables e idempotentes y unidades explícitas; recetas componen consumo. Añadir insumos separados solo si existe una razón de identidad (empaque/no alimentario); no duplicar ingredientes por defecto. Una opción puede ser puramente comercial y no tener consumo físico directo.
7. Dinero: elegir política CLP única y montos exactos (enteros en unidad mínima o decimal con operaciones exactas), sin cambiar valores históricos ni reducir precisión. Costos de compra, recargos y precio de venta son conceptos distintos.
8. Pedido nuevo guarda descripción, nombre, moneda, precio base, recargos/descuentos, opciones/IDs/nombres por bloque, receta efectiva y versión de reglas. JSON snapshot inmutable sirve de contrato histórico; filas normalizadas permiten cocina, inventario y análisis. No exigir FK destructiva a opciones activas para interpretar un pedido viejo.

No conviene crear tablas independientes para cada familia del menú ni un sistema de carrito separado para promociones. Todas deben terminar en líneas de pedido con los mismos contratos de cantidad/precio total que hoy recibe Mercado Pago.

## 18. Plan de migración

Plan futuro, sin ejecución en esta fase:

1. **Base y respaldo:** inventariar el entorno productivo por separado, respaldar BD/PDFs, verificar restauración aislada y registrar ejemplos de pedidos/sesiones reales anonimizados. Resolver semántica unidades/cortes/cantidad_permitida con menú oficial.
2. **Protección histórica:** añadir activo/archivado y política de no borrado comercial; usar retiro lógico antes de cargar nuevos datos. La FK CASCADE existente no se neutraliza solo añadiendo una columna: cualquier cambio de FK necesita una fase explícita de revisión y despliegue. No prometer protección SQL hasta realizarla.
3. **Esquema aditivo:** crear grupos/opciones/reglas/recetas y campos nullable/versionados. Mantener columnas/lectores legacy y no modificar ni reemplazar migraciones antiguas. Elegir índices/UNIQUE después de comprobar duplicados reales.
4. **Carga controlada futura:** importar menú con códigos estables e idempotencia, fuera de esta auditoría; vincular categorías/productos/ingredientes existentes por identidad revisada. Backfill de relaciones sin inferir cantidades físicas a partir de límites comerciales. No cambiar snapshot de pedidos previos.
5. **Cotización dual:** implementar evaluador versionado detrás del contrato ProductPricingService; comparar resultados para productos legacy sin activar nueva venta. Añadir escenarios de bloques, repetición global, salmón, recargos/salsas y concurrencia.
6. **Carrito/admin nuevos:** habilitar por familia/producto, conservar claves y rutas o traductor versionado. Revalidar sesiones existentes y pedir revisión de precios/opciones cuando cambien, con aviso explícito. Admin escribe relaciones de manera atómica.
7. **Pedidos/PDF:** escribir nuevos snapshots y leer ambos formatos; mantener nombre/precio_unit/total/detalle y alias envio hasta retirar dependencias. Nunca recalcular pedidos aprobados desde catálogo actual. Probar PDFs históricos y regenerados por separado.
8. **Pago durable:** incorporar intentos/preferencias e idempotencia persistentes manteniendo orders.id como external_reference y orders.total como importe aprobado. Migrar estados con cautela: NULL histórico no se convierte automáticamente en approved.
9. **Inventario por fases:** empezar con ledger/costos/entradas, luego recetas efectivas, reservas y consumo idempotente. Definir cuándo reservar/descontar (creación/pago/preparación), caducidad y tratamiento de rechazo, reembolso y merma. Pruebas de concurrencia antes de bloquear ventas por disponibilidad.
10. **Despliegue y observación:** pruebas en BD MariaDB aislada además de SQLite, monitoreo de cotización/pago/consumo y reversión funcional por bandera. Retirar lectores/columnas legacy solo en una fase posterior aprobada; el alcance inicial es aditivo.

## 19. Riesgos de compatibilidad

| Área | Contrato que debe conservarse | Riesgo y mitigación futura |
|---|---|---|
| Carrito | configuration, hash, unidades, detalle, IDs y rutas | Cambio de configuración altera hashes y sesiones; introducir versión/traductor y revisión, no confiar en precios anteriores |
| Pedidos existentes | nombre/precio_unit/total/detalle, subtotal/delivery_cost/total | No backfill ficticio de opciones/descripción/recetas; lector legacy permanente mientras haya pedidos viejos |
| Mercado Pago | external_reference=orders.id, CLP, suma de items+delivery=total | No cambiar monto después de crear preferencia/pago; trabajar desde snapshot, mantener firma/validación/locks |
| Administración | forms Proteínas/vegetales/envolturas, sync y rutas | Nueva estructura necesita escritura atómica y retiro lógico; no perder relaciones legacy por sync |
| PDFs | Order.items, campos de snapshot y envio | PDFs privados ya generados pueden tener formato viejo; no sobrescribirlos ni regenerarlos desde productos actuales |
| Historial | user_id y política de acceso | Mantener propiedad/firma de invitado y estados separados; paginar sin cambiar autorización |
| Inventario | No existía consumo previo | No descontar retroactivamente pedidos sin receta fiable; registrar fecha de inicio y saldos iniciales auditados |
| FKs | Producto/categoría actualmente borran líneas | Bloquear retiro destructivo desde aplicación y planificar cambio de integridad explícito |
| Promociones | Solo Producto llega al checkout | Enlazar bundle a Producto; no usar ID de promociones como product_id de order_items |

## 20. Recomendación final

Extender el catálogo existente con **una identidad vendible Producto**, grupos/opciones comerciales reutilizables y recetas físicas versionadas separadas. Integrar promociones como composición/reglas de un producto comprable, con bloques de piezas parametrizados; ingredientes/envueltas/salsas deben referenciar identidades estables y recargos configurables. Implementar inventario como ledger de cantidades físicas y reservas, no como reutilización de cantidad_permitida ni de unidades del carrito.

Primero proteger la persistencia de las líneas históricas y definir el contrato de snapshots. Después ampliar esquema de forma aditiva y cotización versionada, manteniendo checkout y Mercado Pago sobre los importes del pedido. No es necesario rehacer el flujo de pago para soportar las familias del nuevo menú; sí requiere conservar su contrato monetario y fortalecer trazabilidad si se introducen reservas/intentos entre sesiones.

### Alcance y trazabilidad de esta auditoría

Archivos inspeccionados: los 34 PHP de database/migrations (inventariados en sección 4 y migrate:status), los siete app/Models/*.php, los tres database/seeders/*.php y UserFactory; config/database.php, services.php, filesystems.php; composer.json; phpunit.xml; bootstrap/app.php; AppServiceProvider; routes/web.php y referencias de rutas; CartController, CheckoutController, ProductoController, CategoriaController, ProfileController; controladores Admin de Producto/Categoria/Ingrediente/Order/Dashboard; los siete servicios ProductPricingService, CheckoutSessionService, MercadoPagoService, MercadoPagoPaymentService, MercadoPagoWebhookSignature, OrderAccessService y OrderInvoiceService; comando PrivatizeInvoices (lectura); resources/js/app.js y modules; vistas Home/menu/modal/cart/checkout/perfil/admin, componentes store de producto/precio/estado/navegación y pdf/invoice; referencias y helpers de CommerceSecurityTest. Búsquedas transversales cubrieron app, database, routes, resources y config para inventario, recetas, modelos, promociones, reglas y campos heredados.

Diagnósticos ejecutados:

- Get-Location/lectura del árbol mediante rg --files/Get-ChildItem, Get-Content/Select-Object y búsquedas rg -n (sin ejecutar archivos leídos).
- git status --porcelain/--short para observar cambios previos; git diff --check sobre este informe para formato.
- php -v; php artisan --version.
- php artisan migrate:status: **solo consulta de estado**, no ejecución de migraciones.
- php artisan route:list --except-vendor: 55 rutas de aplicación; no invoca controladores de compra.
- PHP por entrada estándar, sin guardar script: bootstrap/config con lista segura de campos, PDO::getAvailableDrivers, PDO de la conexión configurada, SELECT VERSION(), SHOW TABLES, SHOW CREATE TABLE, SELECT COUNT(*) por tabla, SELECT migration,batch FROM migrations ORDER BY migration y SELECT migration FROM migrations; comparación de nombres con los 34 archivos y reflexión de metadata de modelos sin guardarlos.
- En la inspección inicial, SET SESSION TRANSACTION READ ONLY y apertura/cierre de una transacción de lectura: no DML ni DDL de modificación. No se ejecutaron métodos de controlador, eventos de creación o actualización, consultas de datos personales, peticiones de pago, builds ni tests.

No se modificó la base de datos; no se ejecutaron migraciones, seeders ni comandos destructivos; no se cambió .env ni código productivo. No se hizo commit. El único archivo creado/modificado por esta tarea es **docs/database-audit-sushi-akila.md**.
