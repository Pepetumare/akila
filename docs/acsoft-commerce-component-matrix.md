# ACSoft Commerce — matriz de componentes

Fecha: 2026-10-26. Inventario del working tree, incluidos archivos sin seguimiento. Estado A–F según informe principal. Clasificar B no significa seguro para multi-tenant sin aislamiento. Prioridad P0=antes de exponer dos negocios; P1=generalización; P2=posterior.

| Componente | Ruta | Tipo | Estado | Acoplamiento | Destino futuro | Prioridad | Notas |
|---|---|---|---|---|---|---|---|
| PrivatizeInvoices.php | `app/Console/Commands/PrivatizeInvoices.php` | app | B | Infraestructura/contrato existente; sin tenancy | Core/adaptación integración | P1 | Ver hallazgos y riesgos del informe. |
| TipoProducto.php | `app/Enums/TipoProducto.php` | app | B | Simple/promocion/combo, sin tipo por cocina | Core/tipo comercial | P2 | Ver hallazgos y riesgos del informe. |
| TipoReglaPromocion.php | `app/Enums/TipoReglaPromocion.php` | app | C | min_piezas entre reglas generales | Core/reglas + reglas de módulo | P1 | Ver hallazgos y riesgos del informe. |
| UnidadComercial.php | `app/Enums/UnidadComercial.php` | app | C | pieza implica Roll; hand_roll en enum core | Unidad comercial configurable + template | P1 | Ver hallazgos y riesgos del informe. |
| CategoriaController.php | `app/Http/Controllers/Admin/CategoriaController.php` | Controlador | B | CRUD/compra reutilizables; consultas sin aislamiento | Core controllers con autorización por negocio | P0 | Ver hallazgos y riesgos del informe. |
| DashboardController.php | `app/Http/Controllers/Admin/DashboardController.php` | Controlador | D | Mezcla resumen comercial con carga culinaria | Resumen Core + panel cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| IngredienteController.php | `app/Http/Controllers/Admin/IngredienteController.php` | Controlador | D | Tipos culinarios y slug ingredientes-extras | Módulo ingredientes por negocio | P1 | Ver hallazgos y riesgos del informe. |
| OperationsController.php | `app/Http/Controllers/Admin/OperationsController.php` | Controlador | C | Personalización read-only, clientes globales, settings singleton | Admin genérico por negocio | P0 | Ver hallazgos y riesgos del informe. |
| OrderController.php | `app/Http/Controllers/Admin/OrderController.php` | Controlador | B | CRUD/compra reutilizables; consultas sin aislamiento | Core controllers con autorización por negocio | P0 | Ver hallazgos y riesgos del informe. |
| ProductoController.php | `app/Http/Controllers/Admin/ProductoController.php` | Controlador | C | No permite construir configuración; publica/copias con referencias compartidas | Editor genérico validado por negocio | P0 | Ver hallazgos y riesgos del informe. |
| ProfileController.php | `app/Http/Controllers/Admin/ProfileController.php` | Controlador | E | Archivo Admin declara namespace público/clase ProfileController duplicada; no es ruta actual | Retirar tras comprobar autoload/consumidores | P2 | Ver hallazgos y riesgos del informe. |
| AuthenticatedSessionController.php | `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | Controlador | B | Autenticación estándar; unificar identidad ACSoft | Identidad compartida | P1 | Ver hallazgos y riesgos del informe. |
| ConfirmablePasswordController.php | `app/Http/Controllers/Auth/ConfirmablePasswordController.php` | Controlador | B | Autenticación estándar; unificar identidad ACSoft | Identidad compartida | P1 | Ver hallazgos y riesgos del informe. |
| NewPasswordController.php | `app/Http/Controllers/Auth/NewPasswordController.php` | Controlador | B | Autenticación estándar; unificar identidad ACSoft | Identidad compartida | P1 | Ver hallazgos y riesgos del informe. |
| PasswordController.php | `app/Http/Controllers/Auth/PasswordController.php` | Controlador | B | Autenticación estándar; unificar identidad ACSoft | Identidad compartida | P1 | Ver hallazgos y riesgos del informe. |
| PasswordResetLinkController.php | `app/Http/Controllers/Auth/PasswordResetLinkController.php` | Controlador | B | Autenticación estándar; unificar identidad ACSoft | Identidad compartida | P1 | Ver hallazgos y riesgos del informe. |
| RegisteredUserController.php | `app/Http/Controllers/Auth/RegisteredUserController.php` | Controlador | B | Autenticación estándar; unificar identidad ACSoft | Identidad compartida | P1 | Ver hallazgos y riesgos del informe. |
| CartController.php | `app/Http/Controllers/CartController.php` | Controlador | B | Cotización sólida; exists/find y sesión globales | Carrito y revalidación con owner | P0 | Ver hallazgos y riesgos del informe. |
| CategoriaController.php | `app/Http/Controllers/CategoriaController.php` | Controlador | E | Sin ruta pública actual; referencia vistas categorias ausentes | Verificar consumidores antes de retirar | P2 | Ver hallazgos y riesgos del informe. |
| CheckoutController.php | `app/Http/Controllers/CheckoutController.php` | Controlador | C | Entrega San José, cuenta global y sesión/find sin negocio | Core checkout + entrega por negocio | P0 | Ver hallazgos y riesgos del informe. |
| Controller.php | `app/Http/Controllers/Controller.php` | Controlador | B | CRUD/compra reutilizables; consultas sin aislamiento | Core controllers con autorización por negocio | P0 | Ver hallazgos y riesgos del informe. |
| MercadoPagoController.php | `app/Http/Controllers/MercadoPagoController.php` | Controlador | B | CRUD/compra reutilizables; consultas sin aislamiento | Core controllers con autorización por negocio | P0 | Ver hallazgos y riesgos del informe. |
| ProductoController.php | `app/Http/Controllers/ProductoController.php` | Controlador | C | Catálogo global, modal legacy ingredientes | Catálogo por negocio + configuración neutral | P0 | Ver hallazgos y riesgos del informe. |
| ProfileController.php | `app/Http/Controllers/ProfileController.php` | Controlador | B | CRUD/compra reutilizables; consultas sin aislamiento | Core controllers con autorización por negocio | P0 | Ver hallazgos y riesgos del informe. |
| AdminMiddleware.php | `app/Http/Middleware/AdminMiddleware.php` | app | C | is_admin concede toda la tienda | Roles/membresías ACSoft | P0 | Ver hallazgos y riesgos del informe. |
| LoginRequest.php | `app/Http/Requests/Auth/LoginRequest.php` | Request | B | Autenticación estándar; unificar identidad ACSoft | Identidad compartida | P1 | Ver hallazgos y riesgos del informe. |
| ProfileUpdateRequest.php | `app/Http/Requests/ProfileUpdateRequest.php` | Request | B | Autenticación estándar; unificar identidad ACSoft | Identidad compartida | P1 | Ver hallazgos y riesgos del informe. |
| AdminChange.php | `app/Models/AdminChange.php` | Modelo | B | Auditoría genérica sin propietario de negocio | Core/auditoría | P0 | Ver hallazgos y riesgos del informe. |
| AsignacionGrupo.php | `app/Models/AsignacionGrupo.php` | Modelo | B | Cardinalidad genérica; lookup global de grupo | Core/configuración | P0 | Ver hallazgos y riesgos del informe. |
| BloquePromocion.php | `app/Models/BloquePromocion.php` | Modelo | C | Bloque reutilizable; default piezas/label culinario | Core/configuración de componentes | P1 | Ver hallazgos y riesgos del informe. |
| Categoria.php | `app/Models/Categoria.php` | Modelo | B | Slug/nombre únicos globales | Core/catálogo | P0 | Ver hallazgos y riesgos del informe. |
| GrupoOpcion.php | `app/Models/GrupoOpcion.php` | Modelo | B | single/multiple y límites; sin tenancy | Core valioso | P0 | Ver hallazgos y riesgos del informe. |
| Ingrediente.php | `app/Models/Ingrediente.php` | Modelo | D | Entidad gastronómica opcional | Módulo ingredientes | P1 | Ver hallazgos y riesgos del informe. |
| IngredienteProducto.php | `app/Models/IngredienteProducto.php` | Modelo | D | Composición física y compatibilidad legacy | Módulo recetas/composición | P1 | Ver hallazgos y riesgos del informe. |
| Opcion.php | `app/Models/Opcion.php` | Modelo | B | Ingrediente opcional, recargo y estado genéricos | Core valioso | P0 | Ver hallazgos y riesgos del informe. |
| Order.php | `app/Models/Order.php` | Modelo | B | Cliente/importes genéricos, zona local y estados cocina | Core/pedidos | P0 | Ver hallazgos y riesgos del informe. |
| OrderItem.php | `app/Models/OrderItem.php` | Modelo | B | Snapshot inmutable por Eloquent; falta ownership | Core/historia | P0 | Ver hallazgos y riesgos del informe. |
| Producto.php | `app/Models/Producto.php` | Modelo | C | Identidad vendible valiosa con rolls/free extras y unidad default | Core/catálogo | P0 | Ver hallazgos y riesgos del informe. |
| Promocion.php | `app/Models/Promocion.php` | Modelo | E | Complemento legacy duplica precio; no vender separado | Compatibilidad/template; deprecación posterior | P2 | Ver hallazgos y riesgos del informe. |
| ReglaPromocion.php | `app/Models/ReglaPromocion.php` | Modelo | C | Reglas por sujeto reutilizables; min_piezas/metadata culinaria | Core/reglas + extensión gastronómica | P1 | Ver hallazgos y riesgos del informe. |
| User.php | `app/Models/User.php` | Modelo | F | Identidad administrativa y cliente compartida | Identidad ACSoft + cliente Commerce | P0 | Ver hallazgos y riesgos del informe. |
| AppServiceProvider.php | `app/Providers/AppServiceProvider.php` | app | B | Infraestructura/contrato existente; sin tenancy | Core/adaptación integración | P1 | Ver hallazgos y riesgos del informe. |
| AdminClock.php | `app/Services/AdminClock.php` | Servicio | B | Zona Santiago global | Core/reloj por negocio | P1 | Ver hallazgos y riesgos del informe. |
| CheckoutSessionService.php | `app/Services/CheckoutSessionService.php` | Servicio | B | Limpieza segura, claves globales de sesión | Core/sesión por negocio | P0 | Ver hallazgos y riesgos del informe. |
| IngredientImpact.php | `app/Services/IngredientImpact.php` | Servicio | D | Impacto de agotados; queries globales | Módulo ingredientes | P1 | Ver hallazgos y riesgos del informe. |
| KitchenMetrics.php | `app/Services/KitchenMetrics.php` | Servicio | D | Piezas/hand rolls y frit/frio por texto | Módulo cocina Sushi | P2 | Ver hallazgos y riesgos del informe. |
| KitchenWorkflow.php | `app/Services/KitchenWorkflow.php` | Servicio | D | Transiciones de cocina; locks reutilizables | Módulo cocina/fulfillment | P1 | Ver hallazgos y riesgos del informe. |
| MenuConfigurationService.php | `app/Services/MenuConfigurationService.php` | Servicio | C | Slug roles, salsas, bases, piezas y suma de bloques | Core/configuración + adaptador Sushi | P1 | Ver hallazgos y riesgos del informe. |
| MenuMoney.php | `app/Services/MenuMoney.php` | Servicio | A | Operaciones exactas con BigDecimal, sin nombres sushi | Core/dinero | P1 | Ver hallazgos y riesgos del informe. |
| MercadoPagoPaymentService.php | `app/Services/MercadoPagoPaymentService.php` | Servicio | B | Verificación sólida; Order::find global | Core/verificación + contexto de cuenta | P0 | Ver hallazgos y riesgos del informe. |
| MercadoPagoService.php | `app/Services/MercadoPagoService.php` | Servicio | C | Token global/SDK estático/descriptor Sushi Akila | Adaptador pago por cuenta | P0 | Ver hallazgos y riesgos del informe. |
| MercadoPagoWebhookSignature.php | `app/Services/MercadoPagoWebhookSignature.php` | Servicio | B | HMAC/timestamp; secreto global | Adaptador webhook por cuenta | P0 | Ver hallazgos y riesgos del informe. |
| OperationSettings.php | `app/Services/OperationSettings.php` | Servicio | C | Singleton SQL key operation y controles informativos | Configuración por Negocio | P0 | Ver hallazgos y riesgos del informe. |
| OrderAccessService.php | `app/Services/OrderAccessService.php` | Servicio | B | Propiedad/firma correctas para una tienda; falta negocio | Core/autorización por negocio | P0 | Ver hallazgos y riesgos del informe. |
| OrderInvoiceService.php | `app/Services/OrderInvoiceService.php` | Servicio | B | PDF privado; path global boletas/id | Core/documentos con namespace tenant | P0 | Ver hallazgos y riesgos del informe. |
| OrderItemSnapshotService.php | `app/Services/OrderItemSnapshotService.php` | Servicio | B | Contrato histórico genérico, CLP y detalles culinarios | Core/snapshots versionados | P0 | Ver hallazgos y riesgos del informe. |
| ProductPricingService.php | `app/Services/ProductPricingService.php` | Servicio | C | Dispatcher valioso; fallback salm/1000/rolls de diez | Core/precio + adaptador legacy | P0 | Ver hallazgos y riesgos del informe. |
| PromotionRuleEvaluator.php | `app/Services/PromotionRuleEvaluator.php` | Servicio | C | Roles por slug y semántica relleno/envoltura | Core/reglas + módulo Sushi | P1 | Ver hallazgos y riesgos del informe. |
| AppLayout.php | `app/View/Components/AppLayout.php` | app | B | Infraestructura/contrato existente; sin tenancy | Core/adaptación integración | P1 | Ver hallazgos y riesgos del informe. |
| GuestLayout.php | `app/View/Components/GuestLayout.php` | app | B | Infraestructura/contrato existente; sin tenancy | Core/adaptación integración | P1 | Ver hallazgos y riesgos del informe. |
| MenuCardSummary.php | `app/View/MenuCardSummary.php` | app | C | Rolls/piezas_relleno en resumen | Presentación por unidad y módulo | P1 | Ver hallazgos y riesgos del informe. |
| app.php | `bootstrap/app.php` | bootstrap | B | Infraestructura/contrato existente; sin tenancy | Core/adaptación integración | P1 | Ver hallazgos y riesgos del informe. |
| providers.php | `bootstrap/providers.php` | bootstrap | B | Infraestructura/contrato existente; sin tenancy | Core/adaptación integración | P1 | Ver hallazgos y riesgos del informe. |
| admin.php | `config/admin.php` | Config | C | Zona Santiago para todos | Zona por Negocio | P1 | Ver hallazgos y riesgos del informe. |
| app.php | `config/app.php` | Config | B | Infraestructura Laravel; secretos pagos se separan | Config infraestructura + settings de negocio | P1 | Ver hallazgos y riesgos del informe. |
| auth.php | `config/auth.php` | Config | B | Infraestructura Laravel; secretos pagos se separan | Config infraestructura + settings de negocio | P1 | Ver hallazgos y riesgos del informe. |
| cache.php | `config/cache.php` | Config | B | Infraestructura Laravel; secretos pagos se separan | Config infraestructura + settings de negocio | P1 | Ver hallazgos y riesgos del informe. |
| database.php | `config/database.php` | Config | B | Infraestructura Laravel; secretos pagos se separan | Config infraestructura + settings de negocio | P1 | Ver hallazgos y riesgos del informe. |
| delivery.php | `config/delivery.php` | Config | D | San José/2500 y exterior manual | DB/configuración del negocio | P1 | Ver hallazgos y riesgos del informe. |
| filesystems.php | `config/filesystems.php` | Config | B | Infraestructura Laravel; secretos pagos se separan | Config infraestructura + settings de negocio | P1 | Ver hallazgos y riesgos del informe. |
| logging.php | `config/logging.php` | Config | B | Infraestructura Laravel; secretos pagos se separan | Config infraestructura + settings de negocio | P1 | Ver hallazgos y riesgos del informe. |
| mail.php | `config/mail.php` | Config | B | Infraestructura Laravel; secretos pagos se separan | Config infraestructura + settings de negocio | P1 | Ver hallazgos y riesgos del informe. |
| queue.php | `config/queue.php` | Config | B | Infraestructura Laravel; secretos pagos se separan | Config infraestructura + settings de negocio | P1 | Ver hallazgos y riesgos del informe. |
| services.php | `config/services.php` | Config | B | Infraestructura Laravel; secretos pagos se separan | Config infraestructura + settings de negocio | P1 | Ver hallazgos y riesgos del informe. |
| session.php | `config/session.php` | Config | B | Infraestructura Laravel; secretos pagos se separan | Config infraestructura + settings de negocio | P1 | Ver hallazgos y riesgos del informe. |
| UserFactory.php | `database/factories/UserFactory.php` | database | B | Infraestructura/contrato existente; sin tenancy | Core/adaptación integración | P1 | Ver hallazgos y riesgos del informe. |
| 0001_01_01_000000_create_users_table.php | `database/migrations/0001_01_01_000000_create_users_table.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 0001_01_01_000001_create_cache_table.php | `database/migrations/0001_01_01_000001_create_cache_table.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 0001_01_01_000002_create_jobs_table.php | `database/migrations/0001_01_01_000002_create_jobs_table.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_12_130330_create_productos_table.php | `database/migrations/2025_05_12_130330_create_productos_table.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_12_140724_create_promocion_table.php | `database/migrations/2025_05_12_140724_create_promocion_table.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_12_144710_create_orders_table.php | `database/migrations/2025_05_12_144710_create_orders_table.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_12_144800_create_order_items_table.php | `database/migrations/2025_05_12_144800_create_order_items_table.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_12_152605_create_categorias_table.php | `database/migrations/2025_05_12_152605_create_categorias_table.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_12_153040_create_ingredientes_table.php | `database/migrations/2025_05_12_153040_create_ingredientes_table.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_12_171456_add_categoria_id_to_productos_table.php | `database/migrations/2025_05_12_171456_add_categoria_id_to_productos_table.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_12_172133_add_slug_to_productos_table.php | `database/migrations/2025_05_12_172133_add_slug_to_productos_table.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_12_172710_add_is_admin_to_users_table.php | `database/migrations/2025_05_12_172710_add_is_admin_to_users_table.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_12_180452_add_unidades_to_productos_table.php | `database/migrations/2025_05_12_180452_add_unidades_to_productos_table.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_12_185020_create_producto_swappable_ingredient_table.php | `database/migrations/2025_05_12_185020_create_producto_swappable_ingredient_table.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_12_195457_add_status_to_orders_table.php | `database/migrations/2025_05_12_195457_add_status_to_orders_table.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_14_022752_create_ingrediente_producto_table.php | `database/migrations/2025_05_14_022752_create_ingrediente_producto_table.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_14_062607_add_details_to_order_items_table.php | `database/migrations/2025_05_14_062607_add_details_to_order_items_table.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_14_063520_drop_price_from_order_items_table.php | `database/migrations/2025_05_14_063520_drop_price_from_order_items_table.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_17_223901_add_rolls_to_productos_table.php | `database/migrations/2025_05_17_223901_add_rolls_to_productos_table.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_21_175653_add_free_extras_to_productos_table.php | `database/migrations/2025_05_21_175653_add_free_extras_to_productos_table.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_21_215300_add_free_extras_by_type_to_productos.php | `database/migrations/2025_05_21_215300_add_free_extras_by_type_to_productos.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_21_215505_update_tipo_enum_on_ingredientes.php | `database/migrations/2025_05_21_215505_update_tipo_enum_on_ingredientes.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_23_154917_rename_precio_base_in_order_items.php | `database/migrations/2025_05_23_154917_rename_precio_base_in_order_items.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_23_160029_add_total_to_order_items_table.php | `database/migrations/2025_05_23_160029_add_total_to_order_items_table.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_23_160217_add_detalle_to_order_items_table.php | `database/migrations/2025_05_23_160217_add_detalle_to_order_items_table.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_23_160344_drop_subtotal_from_order_items.php | `database/migrations/2025_05_23_160344_drop_subtotal_from_order_items.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_24_191748_add_user_id_to_orders_table.php | `database/migrations/2025_05_24_191748_add_user_id_to_orders_table.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_24_192030_add_phone_to_users_table.php | `database/migrations/2025_05_24_192030_add_phone_to_users_table.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_24_192121_add_profile_fields_to_users_table.php | `database/migrations/2025_05_24_192121_add_profile_fields_to_users_table.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_26_033755_add_tipo_to_producto_swappable_ingredient_table.php | `database/migrations/2025_05_26_033755_add_tipo_to_producto_swappable_ingredient_table.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_27_000000_update_orders_checkout_fields.php | `database/migrations/2025_05_27_000000_update_orders_checkout_fields.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_05_27_000100_adjust_order_items_amount_precision.php | `database/migrations/2025_05_27_000100_adjust_order_items_amount_precision.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2025_10_03_144455_add_mp_payment_id_to_orders.php | `database/migrations/2025_10_03_144455_add_mp_payment_id_to_orders.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2026_10_21_000001_add_payment_verification_to_orders.php | `database/migrations/2026_10_21_000001_add_payment_verification_to_orders.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2026_10_21_000100_add_catalog_metadata.php | `database/migrations/2026_10_21_000100_add_catalog_metadata.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2026_10_21_000110_extend_ingredient_types.php | `database/migrations/2026_10_21_000110_extend_ingredient_types.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2026_10_21_000120_extend_product_composition.php | `database/migrations/2026_10_21_000120_extend_product_composition.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2026_10_21_000130_create_option_groups.php | `database/migrations/2026_10_21_000130_create_option_groups.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2026_10_21_000140_create_product_option_groups.php | `database/migrations/2026_10_21_000140_create_product_option_groups.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2026_10_21_000150_create_promotion_blocks.php | `database/migrations/2026_10_21_000150_create_promotion_blocks.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2026_10_21_000160_create_promotion_rules.php | `database/migrations/2026_10_21_000160_create_promotion_rules.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2026_10_21_000170_link_promotions_to_products.php | `database/migrations/2026_10_21_000170_link_promotions_to_products.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2026_10_21_000180_add_order_item_snapshots.php | `database/migrations/2026_10_21_000180_add_order_item_snapshots.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2026_10_21_000190_protect_historical_product_references.php | `database/migrations/2026_10_21_000190_protect_historical_product_references.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2026_10_22_000100_add_commercial_units_to_catalog.php | `database/migrations/2026_10_22_000100_add_commercial_units_to_catalog.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| 2026_10_23_000100_add_admin_operations.php | `database/migrations/2026_10_23_000100_add_admin_operations.php` | Migración | B | Historia de esquema; conservar archivos, evoluciones aditivas | Migración compatible; ver matriz tenancy | P0 | No renombrar ni reejecutar en esta fase. |
| DatabaseSeeder.php | `database/seeders/DatabaseSeeder.php` | Seeder/datos | D | Carga demo/legacy; no requisito del Core | Template opcional Sushi Akila | P1 | Ver hallazgos y riesgos del informe. |
| IngredienteProductoSeeder.php | `database/seeders/IngredienteProductoSeeder.php` | Seeder/datos | D | Carga demo/legacy; no requisito del Core | Template opcional Sushi Akila | P1 | Ver hallazgos y riesgos del informe. |
| ProductoSeeder.php | `database/seeders/ProductoSeeder.php` | Seeder/datos | D | Carga demo/legacy; no requisito del Core | Template opcional Sushi Akila | P1 | Ver hallazgos y riesgos del informe. |
| SushiAkilaMenuBatch01Seeder.php | `database/seeders/SushiAkilaMenuBatch01Seeder.php` | Seeder/datos | D | Carga demo/legacy; no requisito del Core | Template opcional Sushi Akila | P1 | Ver hallazgos y riesgos del informe. |
| SushiAkilaMenuBatch02Seeder.php | `database/seeders/SushiAkilaMenuBatch02Seeder.php` | Seeder/datos | D | Carga demo/legacy; no requisito del Core | Template opcional Sushi Akila | P1 | Ver hallazgos y riesgos del informe. |
| SushiAkilaMenuBatch03Seeder.php | `database/seeders/SushiAkilaMenuBatch03Seeder.php` | Seeder/datos | D | Carga demo/legacy; no requisito del Core | Template opcional Sushi Akila | P1 | Ver hallazgos y riesgos del informe. |
| SushiAkilaMenuBatch04Seeder.php | `database/seeders/SushiAkilaMenuBatch04Seeder.php` | Seeder/datos | D | Carga demo/legacy; no requisito del Core | Template opcional Sushi Akila | P1 | Ver hallazgos y riesgos del informe. |
| SushiAkilaMenuBatch05Seeder.php | `database/seeders/SushiAkilaMenuBatch05Seeder.php` | Seeder/datos | D | Carga demo/legacy; no requisito del Core | Template opcional Sushi Akila | P1 | Ver hallazgos y riesgos del informe. |
| SushiAkilaMenuSeeder.php | `database/seeders/SushiAkilaMenuSeeder.php` | Seeder/datos | D | Carga demo/legacy; no requisito del Core | Template opcional Sushi Akila | P1 | Ver hallazgos y riesgos del informe. |
| Batch01Data.php | `database/seeders/SushiAkila/Batch01Data.php` | Seeder/datos | D | Carga demo/legacy; no requisito del Core | Template opcional Sushi Akila | P1 | Ver hallazgos y riesgos del informe. |
| Batch02Data.php | `database/seeders/SushiAkila/Batch02Data.php` | Seeder/datos | D | Carga demo/legacy; no requisito del Core | Template opcional Sushi Akila | P1 | Ver hallazgos y riesgos del informe. |
| Batch03Data.php | `database/seeders/SushiAkila/Batch03Data.php` | Seeder/datos | D | Carga demo/legacy; no requisito del Core | Template opcional Sushi Akila | P1 | Ver hallazgos y riesgos del informe. |
| Batch04Data.php | `database/seeders/SushiAkila/Batch04Data.php` | Seeder/datos | D | Carga demo/legacy; no requisito del Core | Template opcional Sushi Akila | P1 | Ver hallazgos y riesgos del informe. |
| Batch05Data.php | `database/seeders/SushiAkila/Batch05Data.php` | Seeder/datos | D | Carga demo/legacy; no requisito del Core | Template opcional Sushi Akila | P1 | Ver hallazgos y riesgos del informe. |
| MenuBatchImporter.php | `database/seeders/SushiAkila/MenuBatchImporter.php` | Seeder/datos | D | Carga demo/legacy; no requisito del Core | Template opcional Sushi Akila | P1 | Ver hallazgos y riesgos del informe. |
| MenuIdentity.php | `database/seeders/SushiAkila/MenuIdentity.php` | Seeder/datos | D | Carga demo/legacy; no requisito del Core | Template opcional Sushi Akila | P1 | Ver hallazgos y riesgos del informe. |
| admin.css | `resources/css/admin.css` | resources | C | Variables Akila/admin + colores literales | Theme CSS custom properties | P1 | Ver hallazgos y riesgos del informe. |
| app.css | `resources/css/app.css` | resources | C | Variables Akila/admin + colores literales | Theme CSS custom properties | P1 | Ver hallazgos y riesgos del informe. |
| custom.css | `resources/css/custom.css` | resources | C | Variables Akila/admin + colores literales | Theme CSS custom properties | P1 | Ver hallazgos y riesgos del informe. |
| admin.js | `resources/js/admin.js` | resources | C | Contrato UI mixto genérico/legacy; revisar módulos activos | UI Core + compatibilidad | P1 | Ver hallazgos y riesgos del informe. |
| app.js | `resources/js/app.js` | resources | C | Contrato UI mixto genérico/legacy; revisar módulos activos | UI Core + compatibilidad | P1 | Ver hallazgos y riesgos del informe. |
| bootstrap.js | `resources/js/bootstrap.js` | resources | C | Contrato UI mixto genérico/legacy; revisar módulos activos | UI Core + compatibilidad | P1 | Ver hallazgos y riesgos del informe. |
| cart.js | `resources/js/modules/cart.js` | resources | C | Contrato UI mixto genérico/legacy; revisar módulos activos | UI Core + compatibilidad | P1 | Ver hallazgos y riesgos del informe. |
| formValidation.js | `resources/js/modules/formValidation.js` | resources | C | Contrato UI mixto genérico/legacy; revisar módulos activos | UI Core + compatibilidad | P1 | Ver hallazgos y riesgos del informe. |
| index.js | `resources/js/modules/index.js` | resources | C | Contrato UI mixto genérico/legacy; revisar módulos activos | UI Core + compatibilidad | P1 | Ver hallazgos y riesgos del informe. |
| menuPersonalization.js | `resources/js/modules/menuPersonalization.js` | resources | C | Contrato UI mixto genérico/legacy; revisar módulos activos | UI Core + compatibilidad | P1 | Ver hallazgos y riesgos del informe. |
| productModal.js | `resources/js/modules/productModal.js` | resources | C | Contrato UI mixto genérico/legacy; revisar módulos activos | UI Core + compatibilidad | P1 | Ver hallazgos y riesgos del informe. |
| storePersonalization.js | `resources/js/modules/storePersonalization.js` | resources | C | Contrato UI mixto genérico/legacy; revisar módulos activos | UI Core + compatibilidad | P1 | Ver hallazgos y riesgos del informe. |
| _form.blade.php | `resources/views/admin/categorias/_form.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| create.blade.php | `resources/views/admin/categorias/create.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| edit.blade.php | `resources/views/admin/categorias/edit.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| index.blade.php | `resources/views/admin/categorias/index.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| customers.blade.php | `resources/views/admin/customers.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| dashboard.blade.php | `resources/views/admin/dashboard.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| _form.blade.php | `resources/views/admin/ingredientes/_form.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| create.blade.php | `resources/views/admin/ingredientes/create.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| edit.blade.php | `resources/views/admin/ingredientes/edit.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| index.blade.php | `resources/views/admin/ingredientes/index.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| inventory.blade.php | `resources/views/admin/inventory.blade.php` | Blade/componente | D | Módulo cocina/insumos; inventario solo informativo | Módulo opcional | P1 | Ver hallazgos y riesgos del informe. |
| kitchen-board.blade.php | `resources/views/admin/kitchen-board.blade.php` | Blade/componente | D | Módulo cocina/insumos; inventario solo informativo | Módulo opcional | P1 | Ver hallazgos y riesgos del informe. |
| kitchen.blade.php | `resources/views/admin/kitchen.blade.php` | Blade/componente | D | Módulo cocina/insumos; inventario solo informativo | Módulo opcional | P1 | Ver hallazgos y riesgos del informe. |
| layout.blade.php | `resources/views/admin/layout.blade.php` | Blade/componente | C | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| action.blade.php | `resources/views/admin/orders/action.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| detail.blade.php | `resources/views/admin/orders/detail.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| index.blade.php | `resources/views/admin/orders/index.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| print.blade.php | `resources/views/admin/orders/print.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| show.blade.php | `resources/views/admin/orders/show.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| personalization.blade.php | `resources/views/admin/personalization.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| _form.blade.php | `resources/views/admin/productos/_form.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| create.blade.php | `resources/views/admin/productos/create.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| edit.blade.php | `resources/views/admin/productos/edit.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| group.blade.php | `resources/views/admin/productos/group.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| index.blade.php | `resources/views/admin/productos/index.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| reports.blade.php | `resources/views/admin/reports.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| settings.blade.php | `resources/views/admin/settings.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| confirm-password.blade.php | `resources/views/auth/confirm-password.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| forgot-password.blade.php | `resources/views/auth/forgot-password.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| login.blade.php | `resources/views/auth/login.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| register.blade.php | `resources/views/auth/register.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| reset-password.blade.php | `resources/views/auth/reset-password.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| index.blade.php | `resources/views/cart/index.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| index.blade.php | `resources/views/checkout/index.blade.php` | Blade/componente | C | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| thankyou.blade.php | `resources/views/checkout/thankyou.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| badge.blade.php | `resources/views/components/admin/badge.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| icon.blade.php | `resources/views/components/admin/icon.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| pagination.blade.php | `resources/views/components/admin/pagination.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| application-logo.blade.php | `resources/views/components/application-logo.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| auth-session-status.blade.php | `resources/views/components/auth-session-status.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| danger-button.blade.php | `resources/views/components/danger-button.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| dropdown-link.blade.php | `resources/views/components/dropdown-link.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| dropdown.blade.php | `resources/views/components/dropdown.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| input-error.blade.php | `resources/views/components/input-error.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| input-label.blade.php | `resources/views/components/input-label.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| menu-option-group.blade.php | `resources/views/components/menu-option-group.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| menu-product-modal.blade.php | `resources/views/components/menu-product-modal.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| menu-selection-summary.blade.php | `resources/views/components/menu-selection-summary.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| modal-producto.blade.php | `resources/views/components/modal-producto.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| modal.blade.php | `resources/views/components/modal.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| nav-link.blade.php | `resources/views/components/nav-link.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| primary-button.blade.php | `resources/views/components/primary-button.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| responsive-nav-link.blade.php | `resources/views/components/responsive-nav-link.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| secondary-button.blade.php | `resources/views/components/secondary-button.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| cart-link.blade.php | `resources/views/components/store/cart-link.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| catalog-image.blade.php | `resources/views/components/store/catalog-image.blade.php` | Blade/componente | C | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| category-card.blade.php | `resources/views/components/store/category-card.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| footer.blade.php | `resources/views/components/store/footer.blade.php` | Blade/componente | C | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| form-button.blade.php | `resources/views/components/store/form-button.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| form-input.blade.php | `resources/views/components/store/form-input.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| header.blade.php | `resources/views/components/store/header.blade.php` | Blade/componente | C | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| icon.blade.php | `resources/views/components/store/icon.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| menu-pagination.blade.php | `resources/views/components/store/menu-pagination.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| mobile-navigation.blade.php | `resources/views/components/store/mobile-navigation.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| order-status.blade.php | `resources/views/components/store/order-status.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| price.blade.php | `resources/views/components/store/price.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| product-card.blade.php | `resources/views/components/store/product-card.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| section-heading.blade.php | `resources/views/components/store/section-heading.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| text-input.blade.php | `resources/views/components/text-input.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| dashboard.blade.php | `resources/views/dashboard.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| home.blade.php | `resources/views/home.blade.php` | Blade/componente | C | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| app.blade.php | `resources/views/layouts/app.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| guest.blade.php | `resources/views/layouts/guest.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| legacy-public.blade.php | `resources/views/layouts/legacy-public.blade.php` | Blade/componente | C | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| navigation.blade.php | `resources/views/layouts/navigation.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| index.blade.php | `resources/views/menu/index.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| invoice.blade.php | `resources/views/pdf/invoice.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| direcciones.blade.php | `resources/views/profile/direcciones.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| edit.blade.php | `resources/views/profile/edit.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| layout.blade.php | `resources/views/profile/layout.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| pedidos.blade.php | `resources/views/profile/pedidos.blade.php` | Blade/componente | B | Layout reutilizable; labels, branding y contexto tenant por revisar | Blade Core parametrizado; cocina opcional | P1 | Ver hallazgos y riesgos del informe. |
| auth.php | `routes/auth.php` | routes | B | Infraestructura/contrato existente; sin tenancy | Core/adaptación integración | P1 | Ver hallazgos y riesgos del informe. |
| console.php | `routes/console.php` | routes | B | Infraestructura/contrato existente; sin tenancy | Core/adaptación integración | P1 | Ver hallazgos y riesgos del informe. |
| web.php | `routes/web.php` | routes | B | Infraestructura/contrato existente; sin tenancy | Core/adaptación integración | P1 | Ver hallazgos y riesgos del informe. |
| test-admin-ui.mjs | `scripts/test-admin-ui.mjs` | scripts | D | Recorridos UI Sushi específicos | Herramienta demo/regresión | P2 | Ver hallazgos y riesgos del informe. |
| test-menu-ui.mjs | `scripts/test-menu-ui.mjs` | scripts | D | Recorridos UI Sushi específicos | Herramienta demo/regresión | P2 | Ver hallazgos y riesgos del informe. |
| AdminOperationsTest.php | `tests/Feature/AdminOperationsTest.php` | Test/soporte | D | Fixture/contrato actual; no demuestra multi-tenant | Suite Core + suite demo + aislamiento futuro | P1 | Ver hallazgos y riesgos del informe. |
| AuthenticationTest.php | `tests/Feature/Auth/AuthenticationTest.php` | Test/soporte | B | Fixture/contrato actual; no demuestra multi-tenant | Suite Core + suite demo + aislamiento futuro | P1 | Ver hallazgos y riesgos del informe. |
| EmailVerificationTest.php | `tests/Feature/Auth/EmailVerificationTest.php` | Test/soporte | B | Fixture/contrato actual; no demuestra multi-tenant | Suite Core + suite demo + aislamiento futuro | P1 | Ver hallazgos y riesgos del informe. |
| PasswordConfirmationTest.php | `tests/Feature/Auth/PasswordConfirmationTest.php` | Test/soporte | B | Fixture/contrato actual; no demuestra multi-tenant | Suite Core + suite demo + aislamiento futuro | P1 | Ver hallazgos y riesgos del informe. |
| PasswordResetTest.php | `tests/Feature/Auth/PasswordResetTest.php` | Test/soporte | B | Fixture/contrato actual; no demuestra multi-tenant | Suite Core + suite demo + aislamiento futuro | P1 | Ver hallazgos y riesgos del informe. |
| PasswordUpdateTest.php | `tests/Feature/Auth/PasswordUpdateTest.php` | Test/soporte | B | Fixture/contrato actual; no demuestra multi-tenant | Suite Core + suite demo + aislamiento futuro | P1 | Ver hallazgos y riesgos del informe. |
| RegistrationTest.php | `tests/Feature/Auth/RegistrationTest.php` | Test/soporte | B | Fixture/contrato actual; no demuestra multi-tenant | Suite Core + suite demo + aislamiento futuro | P1 | Ver hallazgos y riesgos del informe. |
| CommerceSecurityTest.php | `tests/Feature/CommerceSecurityTest.php` | Test/soporte | B | Fixture/contrato actual; no demuestra multi-tenant | Suite Core + suite demo + aislamiento futuro | P1 | Ver hallazgos y riesgos del informe. |
| ExampleTest.php | `tests/Feature/ExampleTest.php` | Test/soporte | B | Fixture/contrato actual; no demuestra multi-tenant | Suite Core + suite demo + aislamiento futuro | P1 | Ver hallazgos y riesgos del informe. |
| MenuArchitectureTest.php | `tests/Feature/MenuArchitectureTest.php` | Test/soporte | B | Fixture/contrato actual; no demuestra multi-tenant | Suite Core + suite demo + aislamiento futuro | P1 | Ver hallazgos y riesgos del informe. |
| MenuCommercialIntegrationTest.php | `tests/Feature/MenuCommercialIntegrationTest.php` | Test/soporte | B | Fixture/contrato actual; no demuestra multi-tenant | Suite Core + suite demo + aislamiento futuro | P1 | Ver hallazgos y riesgos del informe. |
| MenuFinalAuditTest.php | `tests/Feature/MenuFinalAuditTest.php` | Test/soporte | D | Fixture/contrato actual; no demuestra multi-tenant | Suite Core + suite demo + aislamiento futuro | P1 | Ver hallazgos y riesgos del informe. |
| MenuUiRedesignTest.php | `tests/Feature/MenuUiRedesignTest.php` | Test/soporte | D | Fixture/contrato actual; no demuestra multi-tenant | Suite Core + suite demo + aislamiento futuro | P1 | Ver hallazgos y riesgos del informe. |
| ProfileTest.php | `tests/Feature/ProfileTest.php` | Test/soporte | B | Fixture/contrato actual; no demuestra multi-tenant | Suite Core + suite demo + aislamiento futuro | P1 | Ver hallazgos y riesgos del informe. |
| SushiAkilaMenuBatch01Test.php | `tests/Feature/SushiAkilaMenuBatch01Test.php` | Test/soporte | D | Fixture/contrato actual; no demuestra multi-tenant | Suite Core + suite demo + aislamiento futuro | P1 | Ver hallazgos y riesgos del informe. |
| SushiAkilaMenuBatch02Test.php | `tests/Feature/SushiAkilaMenuBatch02Test.php` | Test/soporte | D | Fixture/contrato actual; no demuestra multi-tenant | Suite Core + suite demo + aislamiento futuro | P1 | Ver hallazgos y riesgos del informe. |
| SushiAkilaMenuBatch03Test.php | `tests/Feature/SushiAkilaMenuBatch03Test.php` | Test/soporte | D | Fixture/contrato actual; no demuestra multi-tenant | Suite Core + suite demo + aislamiento futuro | P1 | Ver hallazgos y riesgos del informe. |
| SushiAkilaMenuBatch04Test.php | `tests/Feature/SushiAkilaMenuBatch04Test.php` | Test/soporte | D | Fixture/contrato actual; no demuestra multi-tenant | Suite Core + suite demo + aislamiento futuro | P1 | Ver hallazgos y riesgos del informe. |
| SushiAkilaMenuBatch05Test.php | `tests/Feature/SushiAkilaMenuBatch05Test.php` | Test/soporte | D | Fixture/contrato actual; no demuestra multi-tenant | Suite Core + suite demo + aislamiento futuro | P1 | Ver hallazgos y riesgos del informe. |
| Pest.php | `tests/Pest.php` | Test/soporte | B | Fixture/contrato actual; no demuestra multi-tenant | Suite Core + suite demo + aislamiento futuro | P1 | Ver hallazgos y riesgos del informe. |
| MenuAuditConfiguration.php | `tests/Support/MenuAuditConfiguration.php` | Test/soporte | B | Fixture/contrato actual; no demuestra multi-tenant | Suite Core + suite demo + aislamiento futuro | P1 | Ver hallazgos y riesgos del informe. |
| TestCase.php | `tests/TestCase.php` | Test/soporte | B | Fixture/contrato actual; no demuestra multi-tenant | Suite Core + suite demo + aislamiento futuro | P1 | Ver hallazgos y riesgos del informe. |
| ExampleTest.php | `tests/Unit/ExampleTest.php` | Test/soporte | B | Fixture/contrato actual; no demuestra multi-tenant | Suite Core + suite demo + aislamiento futuro | P1 | Ver hallazgos y riesgos del informe. |

Total: **259 archivos** en el inventario fuente. No incluye vendor, node_modules, compilados ni imágenes binarias.

## Ausencias verificadas

No hay directorios de aplicación Policies, Events, Listeners, Jobs ni Notifications ni clases propias equivalentes encontradas en app. Hay eventos de autenticación del framework. Comando propio: PrivatizeInvoices; comando closure: inspire. AppServiceProvider no registra bindings/policies propios. No existe Cart/CartItem/Payment ni Recipe/Variant como modelo.

## Documentación previa y artefactos

- `docs/auth-stabilization.md`
- `docs/commerce-security.md`
- `docs/database-audit-sushi-akila.md`
- `docs/menu-commercial-integration.md`
- `docs/menu-database-architecture.md`
- `docs/menu-final-audit.md`
- `docs/menu-import-batches.md`
- `docs/menu-ui-redesign.md`
- `docs/ui-reference/CODEX_PROMPT.txt`
- `docs/ui-reference/README.md`
- `docs/ui-reference/verification/comparison.html`

No existen `docs/admin-2-redesign.md` ni `docs/product-ai-images.md`. Existen PNG de referencia/admin y capturas; son evidencia visual, no especificación ejecutable nueva. `database/migrations.zip` es archivo auxiliar, no migración activa. Assets bajo public/img, public/storage/productos y storage/app/public/productos: branding/demo; PDFs privados bajo storage/app/private/boletas: historia protegida.

## Búsquedas reproducibles y conteos

Unidad = línea coincidente; ocurrencias = coincidencias de expresión. Categorías solapadas, no sumarlas como bugs. Incluye docs anteriores; excluye estos cuatro informes y compilados. Los términos logo/delivery/promoción no implican acoplamiento por sí solos. Regex también encuentra nombres de entidades y comentarios.

### marca_literal

Patrón: `akila|san\s+jos[eé]|mariquina|Fernando\s+Luis\s+Manss|4505\s*3594|45053594`. 1084 líneas, 1426 coincidencias, 74 archivos.

| Archivo | Líneas | Coincidencias | Ubicación de líneas |
|---|---|---|---|
| `app/Http/Controllers/CheckoutController.php` | 1 | 1 | 174 |
| `app/Services/MercadoPagoService.php` | 1 | 1 | 60 |
| `database/seeders/SushiAkila/Batch01Data.php` | 1 | 1 | 3 |
| `database/seeders/SushiAkila/Batch02Data.php` | 1 | 1 | 3 |
| `database/seeders/SushiAkila/Batch03Data.php` | 1 | 1 | 3 |
| `database/seeders/SushiAkila/Batch04Data.php` | 5 | 5 | 3, 41, 68, 69, 73 |
| `database/seeders/SushiAkila/Batch05Data.php` | 1 | 1 | 3 |
| `database/seeders/SushiAkila/MenuBatchImporter.php` | 1 | 1 | 3 |
| `database/seeders/SushiAkila/MenuIdentity.php` | 1 | 1 | 3 |
| `database/seeders/SushiAkilaMenuBatch01Seeder.php` | 3 | 3 | 5, 6, 9 |
| `database/seeders/SushiAkilaMenuBatch02Seeder.php` | 3 | 3 | 5, 6, 9 |
| `database/seeders/SushiAkilaMenuBatch03Seeder.php` | 3 | 3 | 5, 6, 9 |
| `database/seeders/SushiAkilaMenuBatch04Seeder.php` | 7 | 7 | 9, 10, 11, 12, 13, 14, 19 |
| `database/seeders/SushiAkilaMenuBatch05Seeder.php` | 3 | 3 | 6, 7, 11 |
| `database/seeders/SushiAkilaMenuSeeder.php` | 6 | 6 | 8, 14, 15, 16, 17, 18 |
| `resources/css/custom.css` | 364 | 469 | 4, 5, 6, 9, 14, 18, 23, 24, 30, 31, 40, 41, 42, 43, 44, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 65, 66, 69, 74, 75, 76, 77, 85, 89, 92, 93, 94, 97, 98, 99, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122, 123, 124, 125, 126, 127, 128, 129, 130, 131, 132, 133, 134, 135, 136, 137, 138, 139, 140, 141, 142, 143, 144, 145, 146, 147, 148, 149, 150, 151, 152, 153, 154, 155, 156, 157, 158, 159, 162, 163, 164, 165, 166, 167, 168, 169, 170, 171, 172, 173, 174, 175, 176, 177, 178, 179, 180, 181, 182, 183, 184, 185, 186, 187, 188, 189, 190, 191, 192, 193, 194, 195, 196, 197, 198, 199, 200, 201, 202, 203, 204, 205, 207, 208, 209, 210, 212, 213, 214, 215, 216, 217, 218, 219, 220, 221, 222, 223, 224, 225, 226, 227, 228, 229, 230, 231, 232, 233, 234, 235, 236, 237, 238, 239, 240, 241, 242, 243, 244, 245, 246, 247, 248, 249, 250, 251, 252, 253, 254, 255, 256, 257, 258, 259, 260, 261, 262, 263, 264, 265, 266, 267, 268, 269, 270, 271, 272, 273, 274, 275, 276, 277, 278, 279, 280, 281, 282, 283, 284, 285, 286, 287, 288, 290, 292, 293, 294, 295, 296, 297, 300, 301, 302, 303, 304, 305, 306, 307, 308, 309, 310, 311, 312, 313, 314, 315, 316, 317, 318, 319, 320, 321, 324, 325, 326, 327, 328, 329, 330, 331, 332, 333, 334, 335, 336, 337, 338, 339, 340, 341, 342, 343, 344, 345, 346, 347, 348, 349, 350, 351, 352, 353, 354, 355, 356, 357, 358, 359, 360, 361, 362, 363, 364, 365, 366, 367, 368, 369, 370, 371, 372, 373, 374, 375, 379, 380, 381, 382, 383, 384, 385, 386, 387, 388, 389, 390, 391, 392, 393, 394, 395, 396, 397, 398, 399, 400, 401, 402, 403, 404, 405, 406, 407, 408, 409, 410, 411, 412, 413, 414, 415, 416, 417, 419, 420, 423, 424, 425, 426, 427, 428, 429, 430, 431, 432, 433, 434, 435, 436 |
| `resources/js/app.js` | 4 | 4 | 17, 18, 19, 20 |
| `resources/js/modules/menuPersonalization.js` | 4 | 4 | 129, 139, 194, 378 |
| `resources/views/admin/layout.blade.php` | 4 | 4 | 4, 19, 25, 31 |
| `resources/views/admin/orders/print.blade.php` | 1 | 1 | 1 |
| `resources/views/admin/productos/create.blade.php` | 1 | 1 | 23 |
| `resources/views/auth/login.blade.php` | 5 | 8 | 2, 4, 18, 20, 22 |
| `resources/views/auth/register.blade.php` | 3 | 6 | 2, 4, 13 |
| `resources/views/cart/index.blade.php` | 19 | 23 | 4, 5, 6, 7, 9, 11, 12, 18, 19, 20, 22, 23, 31, 40, 41, 43, 50, 54, 56 |
| `resources/views/checkout/index.blade.php` | 22 | 31 | 4, 13, 14, 18, 22, 23, 26, 27, 33, 34, 36, 43, 44, 52, 53, 54, 58, 59, 60, 63, 64, 65 |
| `resources/views/checkout/thankyou.blade.php` | 7 | 18 | 5, 7, 8, 10, 11, 12, 14 |
| `resources/views/components/menu-option-group.blade.php` | 10 | 15 | 10, 11, 14, 17, 19, 27, 28, 30, 32, 38 |
| `resources/views/components/menu-product-modal.blade.php` | 34 | 40 | 12, 16, 17, 19, 21, 22, 23, 24, 25, 26, 28, 30, 35, 37, 44, 45, 47, 48, 51, 53, 55, 59, 62, 65, 74, 75, 76, 81, 93, 100, 105, 107, 108, 109 |
| `resources/views/components/modal-producto.blade.php` | 19 | 27 | 8, 14, 16, 23, 27, 30, 31, 32, 36, 41, 44, 47, 49, 50, 55, 67, 83, 84, 85 |
| `resources/views/components/store/cart-link.blade.php` | 2 | 2 | 2, 3 |
| `resources/views/components/store/catalog-image.blade.php` | 3 | 4 | 7, 12, 15 |
| `resources/views/components/store/category-card.blade.php` | 1 | 4 | 5 |
| `resources/views/components/store/footer.blade.php` | 8 | 16 | 1, 2, 3, 4, 5, 6, 8, 9 |
| `resources/views/components/store/form-button.blade.php` | 1 | 3 | 2 |
| `resources/views/components/store/form-input.blade.php` | 1 | 4 | 2 |
| `resources/views/components/store/header.blade.php` | 18 | 21 | 2, 3, 4, 5, 6, 7, 8, 11, 12, 16, 19, 22, 23, 24, 25, 26, 27, 28 |
| `resources/views/components/store/menu-pagination.blade.php` | 1 | 1 | 1 |
| `resources/views/components/store/mobile-navigation.blade.php` | 2 | 4 | 2, 4 |
| `resources/views/components/store/order-status.blade.php` | 1 | 3 | 10 |
| `resources/views/components/store/price.blade.php` | 1 | 1 | 6 |
| `resources/views/components/store/product-card.blade.php` | 10 | 16 | 8, 11, 13, 15, 17, 18, 21, 27, 33, 43 |
| `resources/views/components/store/section-heading.blade.php` | 2 | 2 | 3, 5 |
| `resources/views/home.blade.php` | 31 | 58 | 3, 30, 31, 32, 33, 34, 36, 38, 42, 45, 47, 54, 55, 61, 67, 72, 78, 79, 81, 83, 86, 89, 90, 97, 99, 100, 101, 102, 105, 106, 107 |
| `resources/views/layouts/app.blade.php` | 4 | 8 | 11, 15, 16, 20 |
| `resources/views/layouts/guest.blade.php` | 12 | 19 | 8, 12, 13, 14, 15, 16, 19, 21, 23, 24, 26, 28 |
| `resources/views/layouts/legacy-public.blade.php` | 7 | 7 | 9, 23, 98, 112, 113, 121, 124 |
| `resources/views/menu/index.blade.php` | 10 | 14 | 10, 11, 12, 13, 15, 17, 25, 27, 38, 42 |
| `resources/views/pdf/invoice.blade.php` | 1 | 2 | 73 |
| `resources/views/profile/edit.blade.php` | 7 | 9 | 8, 9, 12, 14, 16, 24, 25 |
| `resources/views/profile/layout.blade.php` | 5 | 12 | 4, 6, 9, 11, 13 |
| `resources/views/profile/pedidos.blade.php` | 6 | 14 | 3, 5, 9, 10, 11, 12 |
| `config/delivery.php` | 1 | 1 | 4 |
| `tests/Feature/AdminOperationsTest.php` | 3 | 3 | 13, 39, 145 |
| `tests/Feature/CommerceSecurityTest.php` | 64 | 64 | 19, 33, 45, 51, 67, 72, 80, 85, 91, 99, 103, 107, 115, 123, 132, 137, 145, 151, 158, 165, 174, 181, 190, 196, 197, 203, 204, 210, 211, 217, 227, 228, 229, 230, 234, 236, 237, 242, 252, 268, 279, 304, 313, 320, 329, 332, 340, 341, 342, 349, 351, 352, 353, 354, 359, 371, 385, 394, 395, 424, 426, 427, 439, 453 |
| `tests/Feature/MenuCommercialIntegrationTest.php` | 2 | 2 | 8, 15 |
| `tests/Feature/MenuFinalAuditTest.php` | 9 | 9 | 8, 21, 35, 84, 93, 94, 136, 141, 164 |
| `tests/Feature/MenuUiRedesignTest.php` | 6 | 7 | 6, 11, 26, 80, 104, 106 |
| `tests/Feature/SushiAkilaMenuBatch01Test.php` | 16 | 16 | 12, 28, 49, 56, 68, 71, 82, 92, 97, 118, 138, 156, 166, 170, 187, 188 |
| `tests/Feature/SushiAkilaMenuBatch02Test.php` | 21 | 33 | 13, 14, 15, 47, 50, 71, 74, 79, 83, 85, 87, 92, 120, 138, 157, 161, 189, 201, 211, 212, 219 |
| `tests/Feature/SushiAkilaMenuBatch03Test.php` | 17 | 38 | 12, 13, 14, 63, 65, 81, 85, 89, 91, 98, 119, 133, 153, 173, 202, 222, 245 |
| `tests/Feature/SushiAkilaMenuBatch04Test.php` | 29 | 64 | 9, 10, 11, 12, 13, 14, 29, 30, 53, 55, 62, 64, 66, 76, 101, 108, 111, 122, 123, 124, 129, 130, 131, 132, 133, 137, 153, 168, 180 |
| `tests/Feature/SushiAkilaMenuBatch05Test.php` | 21 | 23 | 12, 13, 14, 15, 16, 17, 18, 19, 48, 49, 52, 58, 60, 62, 67, 84, 104, 129, 150, 170, 182 |
| `scripts/test-admin-ui.mjs` | 5 | 7 | 2, 4, 5, 7, 9 |
| `scripts/test-menu-ui.mjs` | 20 | 26 | 3, 5, 6, 35, 50, 57, 71, 84, 95, 105, 130, 161, 162, 163, 164, 165, 166, 203, 208, 209 |
| `docs/commerce-security.md` | 1 | 1 | 1 |
| `docs/database-audit-sushi-akila.md` | 8 | 9 | 1, 9, 22, 314, 394, 414, 488, 584 |
| `docs/menu-commercial-integration.md` | 2 | 2 | 11, 178 |
| `docs/menu-database-architecture.md` | 2 | 3 | 1, 7 |
| `docs/menu-final-audit.md` | 139 | 142 | 1, 9, 17, 41, 110, 146, 171, 208, 284, 381, 907, 908, 912, 913, 914, 915, 916, 917, 920, 921, 922, 923, 924, 925, 926, 927, 928, 929, 930, 931, 932, 933, 934, 935, 936, 937, 938, 939, 940, 941, 942, 943, 944, 945, 946, 947, 948, 949, 950, 951, 952, 953, 954, 955, 956, 957, 958, 959, 960, 961, 962, 963, 964, 965, 966, 967, 968, 969, 970, 971, 972, 973, 974, 975, 976, 977, 978, 979, 980, 981, 982, 983, 984, 985, 986, 987, 988, 989, 990, 991, 992, 993, 994, 995, 996, 997, 998, 1000, 1001, 1002, 1003, 1004, 1005, 1006, 1007, 1008, 1009, 1010, 1011, 1012, 1013, 1014, 1015, 1016, 1017, 1018, 1019, 1020, 1021, 1022, 1023, 1024, 1025, 1026, 1027, 1028, 1029, 1030, 1031, 1032, 1033, 1034, 1050, 1072, 1080, 1081, 1082, 1083, 1084 |
| `docs/menu-import-batches.md` | 42 | 54 | 1, 132, 133, 134, 135, 154, 188, 194, 204, 267, 280, 281, 333, 334, 350, 480, 482, 511, 526, 556, 560, 564, 566, 576, 595, 604, 611, 613, 625, 626, 627, 628, 631, 647, 689, 716, 718, 728, 729, 730, 731, 732 |
| `docs/menu-ui-redesign.md` | 2 | 4 | 126, 135 |
| `docs/ui-reference/CODEX_PROMPT.txt` | 2 | 2 | 3, 36 |
| `docs/ui-reference/README.md` | 2 | 2 | 1, 3 |
| `docs/ui-reference/verification/comparison.html` | 1 | 1 | 5 |

### branding_contacto

Patrón: `\blogo\b|favicon|tel:|wa\.me|instagram|facebook|delivery|promocion`. 484 líneas, 630 coincidencias, 68 archivos.

| Archivo | Líneas | Coincidencias | Ubicación de líneas |
|---|---|---|---|
| `app/Enums/TipoProducto.php` | 1 | 2 | 8 |
| `app/Enums/TipoReglaPromocion.php` | 1 | 1 | 5 |
| `app/Http/Controllers/Admin/OperationsController.php` | 3 | 3 | 9, 26, 64 |
| `app/Http/Controllers/Admin/OrderController.php` | 1 | 1 | 21 |
| `app/Http/Controllers/Admin/ProductoController.php` | 5 | 5 | 126, 147, 148, 152, 166 |
| `app/Http/Controllers/CheckoutController.php` | 16 | 22 | 57, 59, 60, 81, 83, 85, 87, 100, 104, 109, 110, 114, 166, 168, 174, 178 |
| `app/Models/BloquePromocion.php` | 3 | 3 | 11, 13, 45 |
| `app/Models/GrupoOpcion.php` | 1 | 2 | 56 |
| `app/Models/Order.php` | 4 | 4 | 16, 19, 33, 52 |
| `app/Models/Producto.php` | 4 | 4 | 129, 134, 137, 139 |
| `app/Models/Promocion.php` | 3 | 3 | 9, 11, 19 |
| `app/Models/ReglaPromocion.php` | 7 | 7 | 5, 11, 13, 17, 24, 30, 33 |
| `app/Services/MenuConfigurationService.php` | 3 | 3 | 5, 198, 209 |
| `app/Services/MercadoPagoService.php` | 5 | 6 | 47, 48, 49, 50, 65 |
| `app/Services/OperationSettings.php` | 1 | 1 | 10 |
| `app/Services/PromotionRuleEvaluator.php` | 5 | 5 | 5, 32, 37, 40, 53 |
| `database/migrations/2025_05_12_140724_create_promocion_table.php` | 5 | 6 | 14, 21, 25, 27, 40 |
| `database/migrations/2025_05_27_000000_update_orders_checkout_fields.php` | 9 | 9 | 30, 31, 39, 48, 49, 59, 60, 71, 72 |
| `database/migrations/2026_10_21_000120_extend_product_composition.php` | 1 | 2 | 13 |
| `database/migrations/2026_10_21_000150_create_promotion_blocks.php` | 4 | 5 | 11, 23, 30, 37 |
| `database/migrations/2026_10_21_000160_create_promotion_rules.php` | 2 | 2 | 11, 28 |
| `database/migrations/2026_10_21_000170_link_promotions_to_products.php` | 2 | 2 | 11, 18 |
| `database/migrations/2026_10_22_000100_add_commercial_units_to_catalog.php` | 3 | 3 | 15, 25, 28 |
| `database/seeders/SushiAkila/Batch02Data.php` | 2 | 2 | 73, 79 |
| `database/seeders/SushiAkila/Batch03Data.php` | 1 | 1 | 72 |
| `database/seeders/SushiAkila/Batch04Data.php` | 2 | 2 | 72, 87 |
| `database/seeders/SushiAkila/Batch05Data.php` | 1 | 1 | 23 |
| `database/seeders/SushiAkila/MenuBatchImporter.php` | 9 | 11 | 7, 11, 48, 125, 131, 132, 135, 200, 238 |
| `database/seeders/SushiAkilaMenuBatch04Seeder.php` | 2 | 3 | 35, 36 |
| `resources/views/admin/dashboard.blade.php` | 2 | 4 | 11, 14 |
| `resources/views/admin/kitchen-board.blade.php` | 1 | 3 | 8 |
| `resources/views/admin/layout.blade.php` | 2 | 4 | 4, 19 |
| `resources/views/admin/orders/detail.blade.php` | 2 | 5 | 1, 4 |
| `resources/views/admin/orders/index.blade.php` | 2 | 5 | 6, 7 |
| `resources/views/admin/orders/print.blade.php` | 1 | 2 | 1 |
| `resources/views/admin/productos/edit.blade.php` | 1 | 2 | 19 |
| `resources/views/admin/productos/index.blade.php` | 1 | 2 | 7 |
| `resources/views/admin/settings.blade.php` | 1 | 3 | 6 |
| `resources/views/checkout/index.blade.php` | 11 | 19 | 10, 22, 23, 27, 31, 33, 34, 35, 37, 58, 59 |
| `resources/views/checkout/thankyou.blade.php` | 1 | 3 | 12 |
| `resources/views/components/menu-product-modal.blade.php` | 1 | 1 | 4 |
| `resources/views/components/store/catalog-image.blade.php` | 1 | 2 | 13 |
| `resources/views/components/store/footer.blade.php` | 3 | 6 | 3, 6, 8 |
| `resources/views/components/store/header.blade.php` | 5 | 11 | 5, 8, 14, 15, 25 |
| `resources/views/home.blade.php` | 4 | 6 | 22, 98, 106, 107 |
| `resources/views/layouts/app.blade.php` | 1 | 2 | 12 |
| `resources/views/layouts/guest.blade.php` | 2 | 4 | 9, 15 |
| `resources/views/layouts/legacy-public.blade.php` | 7 | 9 | 10, 21, 23, 106, 107, 115, 116 |
| `resources/views/layouts/navigation.blade.php` | 2 | 2 | 6, 9 |
| `resources/views/pdf/invoice.blade.php` | 4 | 5 | 70, 72, 73, 79 |
| `tests/Feature/AdminOperationsTest.php` | 1 | 1 | 30 |
| `tests/Feature/CommerceSecurityTest.php` | 2 | 3 | 36, 396 |
| `tests/Feature/MenuArchitectureTest.php` | 11 | 11 | 4, 11, 55, 56, 126, 143, 146, 168, 179, 187, 188 |
| `tests/Feature/MenuCommercialIntegrationTest.php` | 2 | 3 | 243, 251 |
| `tests/Feature/MenuFinalAuditTest.php` | 5 | 6 | 20, 28, 30, 31, 110 |
| `tests/Feature/MenuUiRedesignTest.php` | 1 | 2 | 81 |
| `tests/Feature/SushiAkilaMenuBatch01Test.php` | 10 | 12 | 3, 19, 20, 38, 40, 43, 62, 64, 80, 108 |
| `tests/Feature/SushiAkilaMenuBatch02Test.php` | 11 | 11 | 3, 21, 22, 23, 37, 61, 98, 103, 110, 141, 192 |
| `tests/Feature/SushiAkilaMenuBatch03Test.php` | 6 | 7 | 49, 50, 259, 264, 269, 271 |
| `tests/Feature/SushiAkilaMenuBatch04Test.php` | 8 | 11 | 39, 40, 72, 154, 179, 184, 187, 188 |
| `tests/Feature/SushiAkilaMenuBatch05Test.php` | 2 | 3 | 34, 35 |
| `docs/commerce-security.md` | 4 | 4 | 30, 52, 58, 86 |
| `docs/database-audit-sushi-akila.md` | 53 | 83 | 7, 11, 31, 42, 46, 47, 166, 168, 170, 171, 183, 185, 201, 254, 255, 291, 293, 302, 304, 306, 314, 331, 345, 359, 372, 380, 392, 412, 414, 422, 423, 428, 448, 454, 460, 476, 480, 486, 488, 504, 505, 506, 507, 520, 524, 527, 533, 545, 555, 556, 562, 566, 572 |
| `docs/menu-commercial-integration.md` | 11 | 11 | 7, 9, 15, 66, 85, 110, 129, 166, 170, 174, 184 |
| `docs/menu-database-architecture.md` | 40 | 56 | 1, 3, 9, 11, 13, 25, 26, 27, 33, 40, 43, 47, 49, 53, 65, 66, 69, 71, 84, 85, 86, 88, 98, 116, 118, 120, 126, 128, 144, 146, 148, 150, 181, 193, 220, 224, 238, 245, 246, 248 |
| `docs/menu-final-audit.md` | 90 | 110 | 1, 7, 9, 11, 30, 31, 34, 39, 41, 48, 49, 52, 57, 63, 64, 73, 77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 109, 115, 137, 138, 157, 167, 171, 175, 181, 197, 208, 220, 221, 225, 227, 231, 232, 235, 236, 237, 242, 246, 247, 255, 256, 257, 259, 260, 261, 262, 263, 264, 265, 266, 267, 268, 269, 270, 271, 273, 276, 282, 284, 306, 313, 317, 322, 335, 906, 999, 1047, 1051, 1062 |
| `docs/menu-import-batches.md` | 61 | 69 | 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 30, 32, 81, 136, 147, 164, 169, 171, 180, 240, 242, 244, 273, 291, 296, 298, 344, 358, 366, 381, 382, 383, 389, 413, 437, 448, 453, 455, 482, 494, 513, 521, 526, 538, 542, 548, 562, 564, 583, 588, 590, 611, 631, 641, 665, 696, 701, 703, 751 |
| `docs/menu-ui-redesign.md` | 5 | 6 | 18, 19, 22, 64, 149 |

### sushi_concepto

Patrón: `\b(?:roll|rolls|sushi|piezas?|hand_roll|prote[ií]na|prote[ií]nas|vegetal|vegetales|salm[oó]n|camar[oó]n|kanikama|palta|frito|fr[ií]o|envoltura|salsas?|soya|agridulce|gohan|nikkei|arrollados)\b`. 1641 líneas, 3752 coincidencias, 77 archivos.

| Archivo | Líneas | Coincidencias | Ubicación de líneas |
|---|---|---|---|
| `app/Enums/UnidadComercial.php` | 5 | 11 | 7, 8, 14, 15, 23 |
| `app/Http/Controllers/Admin/IngredienteController.php` | 1 | 4 | 15 |
| `app/Models/BloquePromocion.php` | 5 | 8 | 17, 25, 26, 27, 35 |
| `app/Models/Producto.php` | 2 | 2 | 40, 99 |
| `app/Services/KitchenMetrics.php` | 5 | 7 | 18, 24, 25, 31, 33 |
| `app/Services/MenuConfigurationService.php` | 7 | 7 | 46, 52, 64, 69, 92, 178, 219 |
| `app/Services/MercadoPagoService.php` | 1 | 1 | 60 |
| `app/Services/ProductPricingService.php` | 13 | 25 | 20, 21, 22, 25, 50, 51, 60, 71, 72, 74, 76, 102, 103 |
| `app/Services/PromotionRuleEvaluator.php` | 4 | 4 | 17, 18, 20, 41 |
| `app/View/MenuCardSummary.php` | 2 | 3 | 14, 16 |
| `database/migrations/2025_05_21_215505_update_tipo_enum_on_ingredientes.php` | 7 | 9 | 19, 20, 25, 26, 45, 46, 47 |
| `database/migrations/2026_10_21_000110_extend_ingredient_types.php` | 4 | 12 | 13, 14, 21, 25 |
| `database/migrations/2026_10_22_000100_add_commercial_units_to_catalog.php` | 4 | 4 | 13, 16, 24, 25 |
| `database/seeders/DatabaseSeeder.php` | 2 | 2 | 17, 18 |
| `database/seeders/IngredienteProductoSeeder.php` | 9 | 14 | 15, 16, 17, 18, 29, 30, 33, 34, 36 |
| `database/seeders/ProductoSeeder.php` | 5 | 7 | 13, 14, 17, 18, 20 |
| `database/seeders/SushiAkila/Batch01Data.php` | 53 | 110 | 8, 10, 14, 16, 21, 22, 33, 34, 35, 36, 38, 39, 40, 41, 42, 44, 45, 46, 47, 48, 51, 52, 53, 54, 57, 58, 59, 60, 63, 64, 65, 66, 68, 69, 70, 76, 77, 78, 81, 82, 83, 86, 87, 88, 91, 92, 93, 96, 97, 98, 111, 112, 113 |
| `database/seeders/SushiAkila/Batch02Data.php` | 33 | 70 | 16, 17, 18, 19, 20, 21, 22, 24, 25, 27, 28, 29, 30, 31, 33, 35, 36, 37, 39, 40, 41, 43, 44, 45, 58, 59, 62, 70, 71, 73, 74, 76, 79 |
| `database/seeders/SushiAkila/Batch03Data.php` | 34 | 61 | 10, 12, 19, 20, 21, 28, 31, 36, 37, 38, 39, 43, 45, 47, 48, 49, 51, 52, 54, 55, 56, 57, 58, 59, 60, 61, 63, 70, 73, 78, 79, 82, 83, 85 |
| `database/seeders/SushiAkila/Batch04Data.php` | 28 | 77 | 12, 13, 14, 19, 20, 39, 40, 41, 49, 50, 55, 56, 57, 58, 60, 61, 62, 64, 65, 66, 73, 74, 79, 84, 85, 86, 88, 89 |
| `database/seeders/SushiAkila/Batch05Data.php` | 11 | 15 | 10, 12, 16, 26, 27, 29, 32, 33, 37, 40, 42 |
| `database/seeders/SushiAkila/MenuBatchImporter.php` | 35 | 44 | 62, 63, 64, 65, 66, 75, 87, 88, 115, 116, 117, 127, 128, 134, 141, 142, 143, 170, 175, 187, 204, 207, 213, 214, 231, 234, 237, 245, 247, 252, 254, 261, 263, 264, 266 |
| `database/seeders/SushiAkila/MenuIdentity.php` | 5 | 28 | 18, 20, 21, 28, 29 |
| `database/seeders/SushiAkilaMenuBatch03Seeder.php` | 1 | 1 | 13 |
| `database/seeders/SushiAkilaMenuBatch04Seeder.php` | 5 | 12 | 30, 31, 35, 44, 45 |
| `database/seeders/SushiAkilaMenuBatch05Seeder.php` | 1 | 2 | 18 |
| `resources/js/app.js` | 9 | 9 | 45, 46, 74, 80, 81, 86, 87, 120, 121 |
| `resources/js/modules/menuPersonalization.js` | 1 | 1 | 264 |
| `resources/js/modules/storePersonalization.js` | 7 | 12 | 8, 9, 27, 79, 80, 81, 82 |
| `resources/views/admin/dashboard.blade.php` | 2 | 3 | 6, 10 |
| `resources/views/admin/inventory.blade.php` | 1 | 1 | 4 |
| `resources/views/admin/kitchen-board.blade.php` | 2 | 3 | 9, 10 |
| `resources/views/admin/layout.blade.php` | 4 | 4 | 4, 19, 25, 31 |
| `resources/views/admin/orders/print.blade.php` | 1 | 1 | 1 |
| `resources/views/admin/personalization.blade.php` | 1 | 2 | 6 |
| `resources/views/admin/productos/create.blade.php` | 3 | 3 | 24, 25, 26 |
| `resources/views/admin/productos/_form.blade.php` | 11 | 12 | 104, 106, 110, 115, 120, 128, 130, 134, 135, 139, 144 |
| `resources/views/admin/reports.blade.php` | 1 | 1 | 6 |
| `resources/views/admin/settings.blade.php` | 1 | 4 | 7 |
| `resources/views/cart/index.blade.php` | 1 | 2 | 34 |
| `resources/views/checkout/index.blade.php` | 3 | 4 | 49, 50, 54 |
| `resources/views/checkout/thankyou.blade.php` | 1 | 1 | 5 |
| `resources/views/components/menu-option-group.blade.php` | 2 | 8 | 7, 32 |
| `resources/views/components/menu-product-modal.blade.php` | 4 | 10 | 26, 35, 55, 62 |
| `resources/views/components/menu-selection-summary.blade.php` | 3 | 6 | 9, 16, 18 |
| `resources/views/components/modal-producto.blade.php` | 8 | 9 | 3, 4, 5, 35, 36, 40, 41, 62 |
| `resources/views/components/store/footer.blade.php` | 2 | 3 | 3, 9 |
| `resources/views/components/store/header.blade.php` | 1 | 2 | 5 |
| `resources/views/home.blade.php` | 12 | 16 | 3, 21, 22, 30, 31, 33, 42, 79, 85, 100, 101, 106 |
| `resources/views/layouts/app.blade.php` | 1 | 1 | 11 |
| `resources/views/layouts/guest.blade.php` | 5 | 6 | 8, 15, 21, 23, 28 |
| `resources/views/layouts/legacy-public.blade.php` | 5 | 5 | 9, 23, 98, 121, 124 |
| `resources/views/menu/index.blade.php` | 1 | 2 | 12 |
| `resources/views/pdf/invoice.blade.php` | 4 | 4 | 121, 122, 123, 124 |
| `tests/Feature/AdminOperationsTest.php` | 22 | 28 | 54, 82, 87, 92, 107, 112, 114, 115, 116, 117, 122, 125, 126, 127, 130, 136, 142, 143, 145, 159, 160, 170 |
| `tests/Feature/CommerceSecurityTest.php` | 20 | 30 | 21, 22, 24, 25, 26, 73, 74, 86, 87, 92, 93, 94, 95, 100, 106, 108, 111, 124, 126, 384 |
| `tests/Feature/MenuArchitectureTest.php` | 8 | 12 | 62, 88, 142, 167, 172, 248, 251, 265 |
| `tests/Feature/MenuCommercialIntegrationTest.php` | 29 | 46 | 16, 21, 23, 31, 39, 40, 45, 48, 73, 78, 79, 110, 114, 121, 122, 127, 134, 149, 158, 169, 185, 186, 199, 200, 222, 225, 228, 234, 244 |
| `tests/Feature/MenuFinalAuditTest.php` | 13 | 18 | 78, 80, 87, 88, 89, 92, 95, 96, 135, 136, 137, 142, 145 |
| `tests/Feature/MenuUiRedesignTest.php` | 15 | 19 | 19, 20, 27, 29, 37, 44, 51, 52, 53, 68, 86, 89, 93, 95, 99 |
| `tests/Feature/SushiAkilaMenuBatch01Test.php` | 32 | 65 | 30, 31, 77, 78, 79, 84, 89, 90, 91, 98, 101, 104, 111, 130, 131, 132, 133, 134, 139, 140, 141, 145, 149, 159, 160, 161, 162, 163, 164, 165, 166, 171 |
| `tests/Feature/SushiAkilaMenuBatch02Test.php` | 33 | 53 | 55, 56, 57, 67, 91, 101, 103, 106, 112, 114, 115, 116, 130, 135, 142, 148, 152, 153, 156, 157, 160, 162, 164, 165, 166, 169, 172, 186, 190, 191, 192, 220, 221 |
| `tests/Feature/SushiAkilaMenuBatch03Test.php` | 53 | 121 | 32, 70, 71, 72, 80, 81, 87, 97, 99, 106, 108, 112, 125, 126, 127, 128, 129, 141, 142, 143, 144, 145, 146, 148, 149, 160, 161, 162, 163, 164, 165, 166, 167, 168, 169, 174, 188, 191, 196, 197, 203, 210, 211, 214, 215, 223, 224, 228, 237, 241, 246, 247, 267 |
| `tests/Feature/SushiAkilaMenuBatch04Test.php` | 37 | 68 | 26, 28, 72, 75, 81, 82, 84, 86, 89, 94, 96, 97, 102, 105, 107, 109, 110, 113, 116, 117, 118, 125, 126, 131, 139, 140, 141, 146, 148, 149, 154, 157, 169, 170, 173, 176, 177 |
| `tests/Feature/SushiAkilaMenuBatch05Test.php` | 17 | 25 | 24, 26, 66, 68, 80, 103, 106, 117, 128, 131, 147, 149, 151, 152, 159, 166, 169 |
| `scripts/test-admin-ui.mjs` | 1 | 1 | 75 |
| `scripts/test-menu-ui.mjs` | 24 | 33 | 93, 94, 96, 104, 106, 107, 111, 120, 122, 123, 125, 134, 147, 154, 160, 161, 162, 163, 164, 165, 166, 171, 176, 203 |
| `docs/commerce-security.md` | 6 | 8 | 1, 45, 49, 50, 51, 53 |
| `docs/database-audit-sushi-akila.md` | 36 | 85 | 1, 7, 148, 154, 156, 310, 314, 325, 327, 333, 335, 355, 388, 389, 392, 414, 417, 418, 419, 420, 421, 425, 426, 441, 465, 476, 486, 488, 498, 506, 524, 528, 543, 557, 566, 584 |
| `docs/menu-commercial-integration.md` | 28 | 60 | 9, 11, 17, 19, 37, 72, 75, 79, 85, 87, 89, 91, 93, 106, 108, 125, 127, 140, 141, 150, 152, 159, 160, 162, 174, 190, 191, 193 |
| `docs/menu-database-architecture.md` | 17 | 30 | 1, 7, 9, 11, 71, 95, 118, 120, 122, 124, 135, 136, 140, 142, 150, 243, 244 |
| `docs/menu-final-audit.md` | 665 | 1823 | 1, 7, 13, 17, 65, 67, 69, 75, 77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 121, 122, 123, 124, 125, 127, 128, 129, 132, 134, 135, 136, 137, 138, 139, 140, 141, 142, 143, 144, 145, 146, 147, 148, 149, 151, 155, 157, 161, 162, 163, 164, 165, 169, 171, 173, 175, 185, 187, 190, 192, 194, 199, 208, 210, 282, 284, 306, 319, 320, 328, 329, 330, 331, 332, 333, 334, 340, 344, 348, 349, 350, 351, 352, 353, 354, 355, 356, 357, 358, 359, 360, 361, 362, 363, 364, 365, 366, 367, 368, 370, 371, 372, 373, 374, 375, 376, 377, 378, 379, 380, 382, 383, 384, 393, 394, 396, 399, 402, 403, 404, 405, 406, 407, 408, 409, 410, 411, 412, 413, 414, 415, 416, 417, 418, 419, 420, 421, 422, 423, 424, 425, 426, 427, 428, 429, 430, 431, 432, 433, 434, 435, 436, 437, 438, 439, 440, 441, 442, 443, 444, 445, 446, 448, 449, 451, 454, 457, 458, 459, 460, 461, 462, 463, 464, 465, 467, 468, 470, 473, 476, 477, 478, 479, 480, 481, 483, 484, 486, 489, 492, 493, 495, 496, 498, 501, 505, 506, 508, 511, 514, 515, 516, 517, 518, 519, 520, 521, 522, 523, 524, 525, 526, 527, 528, 529, 530, 531, 532, 533, 534, 535, 536, 537, 538, 539, 540, 541, 542, 543, 544, 545, 547, 548, 550, 553, 556, 557, 558, 559, 560, 561, 562, 563, 564, 566, 567, 569, 572, 575, 576, 577, 578, 579, 580, 581, 582, 583, 585, 586, 588, 591, 594, 595, 596, 597, 598, 599, 600, 601, 602, 604, 605, 607, 610, 613, 614, 615, 616, 617, 618, 619, 620, 621, 623, 624, 626, 629, 632, 633, 634, 635, 636, 637, 638, 639, 640, 642, 643, 645, 648, 651, 652, 653, 654, 655, 656, 657, 658, 659, 660, 661, 662, 663, 664, 665, 666, 667, 668, 669, 670, 671, 672, 673, 674, 675, 676, 677, 678, 679, 680, 681, 682, 683, 684, 685, 686, 687, 688, 689, 690, 691, 692, 693, 694, 695, 696, 697, 698, 699, 700, 701, 702, 704, 705, 707, 710, 713, 714, 715, 716, 717, 718, 719, 720, 721, 722, 723, 724, 725, 726, 727, 728, 729, 730, 731, 732, 733, 734, 735, 736, 737, 738, 739, 740, 742, 743, 745, 748, 751, 752, 753, 754, 755, 756, 757, 759, 760, 762, 765, 768, 769, 770, 771, 772, 773, 774, 775, 776, 778, 779, 781, 784, 787, 788, 789, 790, 791, 792, 793, 794, 795, 797, 798, 800, 803, 806, 807, 808, 809, 810, 811, 812, 813, 814, 815, 816, 817, 818, 819, 820, 821, 822, 823, 824, 825, 826, 827, 828, 829, 830, 831, 832, 833, 834, 835, 836, 837, 838, 839, 840, 841, 842, 843, 844, 845, 846, 847, 848, 849, 850, 851, 852, 853, 854, 855, 856, 857, 858, 859, 860, 861, 862, 863, 864, 865, 866, 867, 868, 869, 870, 876, 877, 878, 879, 880, 881, 882, 883, 884, 885, 890, 891, 892, 893, 894, 895, 897, 907, 908, 912, 914, 915, 916, 917, 920, 921, 923, 924, 927, 928, 929, 930, 931, 932, 933, 934, 935, 936, 937, 938, 939, 940, 941, 942, 943, 944, 945, 946, 947, 948, 949, 950, 951, 953, 954, 955, 956, 957, 958, 959, 960, 961, 962, 963, 964, 965, 966, 967, 968, 969, 970, 971, 972, 973, 974, 975, 976, 977, 978, 979, 980, 981, 982, 983, 984, 985, 986, 987, 988, 989, 990, 991, 992, 993, 994, 995, 997, 998, 1000, 1001, 1003, 1004, 1005, 1006, 1007, 1008, 1009, 1010, 1011, 1012, 1013, 1014, 1015, 1016, 1017, 1018, 1019, 1020, 1021, 1022, 1023, 1024, 1025, 1026, 1027, 1028, 1029, 1030, 1031, 1032, 1033, 1034, 1035, 1036, 1037, 1038, 1040, 1041, 1045, 1046, 1047, 1048, 1049, 1063, 1064, 1065, 1066, 1067, 1072 |
| `docs/menu-import-batches.md` | 174 | 514 | 1, 11, 15, 18, 19, 23, 30, 32, 38, 39, 41, 43, 45, 49, 55, 56, 57, 58, 59, 61, 63, 69, 70, 71, 72, 73, 74, 75, 77, 79, 87, 88, 89, 96, 97, 103, 107, 113, 122, 123, 125, 126, 178, 208, 211, 212, 213, 214, 216, 221, 227, 229, 230, 231, 232, 233, 234, 236, 240, 242, 244, 246, 248, 250, 257, 259, 260, 261, 271, 305, 317, 318, 319, 320, 322, 323, 350, 358, 360, 361, 362, 363, 364, 365, 366, 368, 370, 372, 374, 380, 381, 382, 383, 385, 389, 393, 395, 397, 399, 401, 402, 403, 409, 411, 417, 419, 428, 429, 431, 432, 433, 460, 468, 469, 470, 471, 472, 473, 474, 475, 504, 505, 506, 507, 508, 509, 510, 511, 513, 517, 519, 523, 524, 526, 528, 530, 538, 540, 542, 544, 546, 548, 551, 552, 554, 556, 558, 560, 572, 595, 603, 604, 605, 606, 613, 631, 645, 647, 661, 662, 663, 665, 667, 669, 673, 675, 677, 679, 685, 706, 712, 735, 751, 753 |
| `docs/menu-ui-redesign.md` | 21 | 25 | 12, 24, 25, 26, 53, 64, 65, 66, 67, 68, 69, 71, 72, 86, 112, 130, 131, 132, 133, 136, 137 |
| `docs/ui-reference/CODEX_PROMPT.txt` | 4 | 4 | 3, 34, 35, 36 |
| `docs/ui-reference/README.md` | 4 | 5 | 1, 3, 25, 57 |
| `docs/ui-reference/verification/comparison.html` | 3 | 4 | 5, 18, 26 |

### color_literal

Patrón: `#[0-9a-f]{3,8}\b`. 143 líneas, 336 coincidencias, 8 archivos.

| Archivo | Líneas | Coincidencias | Ubicación de líneas |
|---|---|---|---|
| `resources/css/admin.css` | 4 | 128 | 2, 3, 4, 5 |
| `resources/css/custom.css` | 131 | 186 | 5, 6, 32, 41, 42, 43, 44, 57, 105, 107, 109, 111, 113, 114, 117, 119, 120, 123, 126, 127, 130, 132, 134, 136, 137, 138, 140, 142, 143, 144, 145, 146, 147, 148, 149, 150, 152, 155, 157, 158, 163, 164, 167, 168, 170, 174, 178, 179, 180, 183, 184, 186, 188, 189, 190, 194, 196, 198, 199, 200, 203, 206, 207, 210, 211, 212, 213, 214, 215, 218, 223, 226, 227, 230, 233, 235, 236, 237, 238, 241, 242, 244, 245, 247, 248, 249, 250, 251, 252, 253, 254, 256, 258, 261, 262, 266, 267, 269, 270, 272, 276, 278, 280, 281, 282, 283, 285, 286, 288, 306, 314, 316, 317, 325, 382, 383, 385, 386, 392, 393, 396, 402, 403, 405, 406, 407, 410, 411, 415, 416, 417 |
| `resources/views/admin/orders/print.blade.php` | 1 | 1 | 1 |
| `resources/views/layouts/app.blade.php` | 1 | 1 | 10 |
| `resources/views/layouts/guest.blade.php` | 1 | 1 | 7 |
| `resources/views/pdf/invoice.blade.php` | 3 | 3 | 28, 34, 43 |
| `docs/menu-final-audit.md` | 1 | 8 | 918 |
| `docs/ui-reference/verification/comparison.html` | 1 | 8 | 7 |

## Inventario completo de rutas observadas

72 rutas, incluidas rutas técnicas/framework. Tener ruta registrada no comprueba que cada método de controlador exista. Riesgo refleja futura exposición multi-tenant; auth genérica sigue siendo identidad global.

| Método | URI real | Nombre | Acción | Middleware | Destino/riesgo futuro |
|---|---|---|---|---|---|
| GET/HEAD | `/` | home | Illuminate\Routing\ViewController | web | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD/POST/PUT/PATCH/DELETE/OPTIONS | `admin` | admin. | Illuminate\Routing\RedirectController | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `admin/categorias` | admin.categorias.index | App\Http\Controllers\Admin\CategoriaController@index | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| POST | `admin/categorias` | admin.categorias.store | App\Http\Controllers\Admin\CategoriaController@store | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `admin/categorias/create` | admin.categorias.create | App\Http\Controllers\Admin\CategoriaController@create | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `admin/categorias/{categoria}` | admin.categorias.show | App\Http\Controllers\Admin\CategoriaController@show | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| PUT/PATCH | `admin/categorias/{categoria}` | admin.categorias.update | App\Http\Controllers\Admin\CategoriaController@update | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| DELETE | `admin/categorias/{categoria}` | admin.categorias.destroy | App\Http\Controllers\Admin\CategoriaController@destroy | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `admin/categorias/{categoria}/edit` | admin.categorias.edit | App\Http\Controllers\Admin\CategoriaController@edit | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `admin/customers` | admin.customers | App\Http\Controllers\Admin\OperationsController@customers | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `admin/dashboard` | admin.dashboard | App\Http\Controllers\Admin\DashboardController@index | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `admin/ingredientes` | admin.ingredientes.index | App\Http\Controllers\Admin\IngredienteController@index | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| POST | `admin/ingredientes` | admin.ingredientes.store | App\Http\Controllers\Admin\IngredienteController@store | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `admin/ingredientes/create` | admin.ingredientes.create | App\Http\Controllers\Admin\IngredienteController@create | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `admin/ingredientes/{ingrediente}` | admin.ingredientes.show | App\Http\Controllers\Admin\IngredienteController@show | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| PUT/PATCH | `admin/ingredientes/{ingrediente}` | admin.ingredientes.update | App\Http\Controllers\Admin\IngredienteController@update | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| DELETE | `admin/ingredientes/{ingrediente}` | admin.ingredientes.destroy | App\Http\Controllers\Admin\IngredienteController@destroy | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| PATCH | `admin/ingredientes/{ingrediente}/availability` | admin.ingredientes.availability | App\Http\Controllers\Admin\IngredienteController@availability | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `admin/ingredientes/{ingrediente}/edit` | admin.ingredientes.edit | App\Http\Controllers\Admin\IngredienteController@edit | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `admin/inventory` | admin.inventory | Illuminate\Routing\ViewController | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `admin/kitchen` | admin.kitchen | App\Http\Controllers\Admin\OrderController@kitchen | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `admin/orders` | admin.orders.index | App\Http\Controllers\Admin\OrderController@index | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `admin/orders/{order}` | admin.orders.show | App\Http\Controllers\Admin\OrderController@show | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| PUT/PATCH | `admin/orders/{order}` | admin.orders.update | App\Http\Controllers\Admin\OrderController@update | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `admin/orders/{order}/boleta` | admin.orders.download | App\Http\Controllers\Admin\OrderController@download | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `admin/orders/{order}/print` | admin.orders.print | App\Http\Controllers\Admin\OrderController@print | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `admin/personalization` | admin.personalization | App\Http\Controllers\Admin\OperationsController@personalization | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `admin/productos` | admin.productos.index | App\Http\Controllers\Admin\ProductoController@index | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| POST | `admin/productos` | admin.productos.store | App\Http\Controllers\Admin\ProductoController@store | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `admin/productos/create` | admin.productos.create | App\Http\Controllers\Admin\ProductoController@create | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| PUT/PATCH | `admin/productos/{producto}` | admin.productos.update | App\Http\Controllers\Admin\ProductoController@update | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| DELETE | `admin/productos/{producto}` | admin.productos.destroy | App\Http\Controllers\Admin\ProductoController@destroy | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| PATCH | `admin/productos/{producto}/availability` | admin.productos.availability | App\Http\Controllers\Admin\ProductoController@availability | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| POST | `admin/productos/{producto}/duplicate` | admin.productos.duplicate | App\Http\Controllers\Admin\ProductoController@duplicate | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `admin/productos/{producto}/edit` | admin.productos.edit | App\Http\Controllers\Admin\ProductoController@edit | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `admin/reports` | admin.reports | App\Http\Controllers\Admin\OperationsController@reports | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `admin/settings` | admin.settings | App\Http\Controllers\Admin\OperationsController@settings | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| PUT | `admin/settings` | admin.settings.update | App\Http\Controllers\Admin\OperationsController@saveSettings | web, auth, admin | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `cart` | cart.index | App\Http\Controllers\CartController@index | web | Commerce con negocio; HIGH/CRITICAL según informe |
| POST | `cart/add` | cart.add | App\Http\Controllers\CartController@add | web | Commerce con negocio; HIGH/CRITICAL según informe |
| POST | `cart/clear` | cart.clear | App\Http\Controllers\CartController@clear | web | Commerce con negocio; HIGH/CRITICAL según informe |
| POST | `cart/quote` | cart.quote | App\Http\Controllers\CartController@quote | web, throttle:60,1 | Commerce con negocio; HIGH/CRITICAL según informe |
| POST | `cart/remove` | cart.remove | App\Http\Controllers\CartController@remove | web | Commerce con negocio; HIGH/CRITICAL según informe |
| POST | `cart/update` | cart.update | App\Http\Controllers\CartController@update | web | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `checkout` | checkout.index | App\Http\Controllers\CheckoutController@index | web | Commerce con negocio; HIGH/CRITICAL según informe |
| POST | `checkout` | checkout.store | App\Http\Controllers\CheckoutController@store | web | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `checkout/boleta/{order}` | checkout.download | App\Http\Controllers\CheckoutController@download | web | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `checkout/failure` | checkout.failure | App\Http\Controllers\MercadoPagoController@failure | web | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `checkout/pending` | checkout.pending | App\Http\Controllers\MercadoPagoController@pending | web | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `checkout/success` | checkout.success | App\Http\Controllers\MercadoPagoController@success | web | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `checkout/thank-you/{order}` | checkout.thankyou | App\Http\Controllers\CheckoutController@thankYou | web | Commerce con negocio; HIGH/CRITICAL según informe |
| POST | `checkout/webhook` | checkout.webhook | App\Http\Controllers\MercadoPagoController@webhook | web | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `confirm-password` | password.confirm | App\Http\Controllers\Auth\ConfirmablePasswordController@show | web, auth | Infraestructura/identidad; revisar integración |
| POST | `confirm-password` |  | App\Http\Controllers\Auth\ConfirmablePasswordController@store | web, auth | Infraestructura/identidad; revisar integración |
| GET/HEAD | `forgot-password` | password.request | App\Http\Controllers\Auth\PasswordResetLinkController@create | web, guest | Infraestructura/identidad; revisar integración |
| POST | `forgot-password` | password.email | App\Http\Controllers\Auth\PasswordResetLinkController@store | web, guest | Infraestructura/identidad; revisar integración |
| GET/HEAD | `login` | login | App\Http\Controllers\Auth\AuthenticatedSessionController@create | web, guest | Infraestructura/identidad; revisar integración |
| POST | `login` |  | App\Http\Controllers\Auth\AuthenticatedSessionController@store | web, guest | Infraestructura/identidad; revisar integración |
| POST | `logout` | logout | App\Http\Controllers\Auth\AuthenticatedSessionController@destroy | web, auth | Infraestructura/identidad; revisar integración |
| GET/HEAD | `menu` | menu | App\Http\Controllers\ProductoController@index | web | Commerce con negocio; HIGH/CRITICAL según informe |
| PUT | `password` | password.update | App\Http\Controllers\Auth\PasswordController@update | web, auth | Infraestructura/identidad; revisar integración |
| GET/HEAD | `producto/modal/{id}` | producto.modal | App\Http\Controllers\ProductoController@modal | web | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `profile` | profile.edit | App\Http\Controllers\ProfileController@edit | web, auth | Infraestructura/identidad; revisar integración |
| PATCH | `profile` | profile.update | App\Http\Controllers\ProfileController@update | web, auth | Infraestructura/identidad; revisar integración |
| GET/HEAD | `profile/pedidos` | profile.orders | App\Http\Controllers\ProfileController@orders | web, auth | Commerce con negocio; HIGH/CRITICAL según informe |
| GET/HEAD | `register` | register | App\Http\Controllers\Auth\RegisteredUserController@create | web, guest | Infraestructura/identidad; revisar integración |
| POST | `register` |  | App\Http\Controllers\Auth\RegisteredUserController@store | web, guest | Infraestructura/identidad; revisar integración |
| POST | `reset-password` | password.store | App\Http\Controllers\Auth\NewPasswordController@store | web, guest | Infraestructura/identidad; revisar integración |
| GET/HEAD | `reset-password/{token}` | password.reset | App\Http\Controllers\Auth\NewPasswordController@create | web, guest | Infraestructura/identidad; revisar integración |
| GET/HEAD | `storage/{path}` | storage.local | Closure |  | Infraestructura/identidad; revisar integración |
| PUT | `storage/{path}` | storage.local.upload | Closure |  | Infraestructura/identidad; revisar integración |
| GET/HEAD | `up` |  | Closure |  | Infraestructura/identidad; revisar integración |

Rutas de catálogo/pedidos/admin son B/C según controlador; cocina D; auth B/decisión identidad F; storage/up infraestructura B. No hay rutas CRUD de grupos/opciones/bloques/reglas. Resource categorias/ingredientes registra show aunque sus controllers actuales no implementan show: deuda MEDIUM de ruta inutilizable, no cobertura por route:list.
