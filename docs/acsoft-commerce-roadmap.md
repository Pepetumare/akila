# Roadmap de generalización e integración de ACSoft Commerce

Fecha: 2026-10-26. **Propuesta, ninguna fase implementada por esta auditoría.** Dependencias y riesgos parten del working tree actual de Akila y del código leído de ACSoft. Alcance: catálogo, venta, personalización, pedido, pago y entrega para PYME; reservas/CRM/contabilidad avanzada fuera.

## Decisión de orden

Generalizar primero el Core en Akila conservando checkout/historia; integrarlo después como módulo interno ACSoft. Antes de tocar branding o tenancy, acordar identidad de Producto y User con el Negocio existente. No trasladar primero todo a ACSoft ni construir otro sistema de tenancy en Akila. Validar el aislamiento con la integración antes de exponer el segundo negocio.

**Nueve fases, G0–G8**, incluida esta auditoría. Primera fase de implementación recomendada: **G1, contrato de integración y protección de compatibilidad**. G2 es el primer avance comercial neutral; G3 hace visible la reutilización mediante administración. No se necesita un package ni API para completar este roadmap.

## G0 — Auditoría y baseline (esta fase)

- **Objetivo:** conocer acoplamientos, inventario, esquema y diferencia real con ACSoft.
- **Archivos/módulos:** cuatro informes `docs/acsoft-commerce-*.md`; lectura de app/database/resources/routes/config/tests y ACSoft.
- **Migraciones esperadas:** ninguna.
- **Riesgo:** bajo; respetar working tree previo y pruebas aisladas.
- **Dependencias:** acceso de lectura a proyectos y documentación Laravel 12.
- **Tests/checks:** suite actual, migrate:status, route:list, build temporal, git diff --check y huellas de archivos/BD.
- **Terminado:** inventario A–F, 26 tablas, riesgos, integración y baseline documentados, solo documentación nueva, cero commit/escrituras comerciales.

## G1 — Contratos con ACSoft y estrategia de conservación

- **Objetivo:** resolver colisiones antes de generalizar: Negocio como owner único, identidad de catálogo, usuarios/clientes y referencias históricas.
- **Archivos/módulos afectados posteriormente:** `app/Models/{Producto,User,Order,OrderItem,Promocion}.php`, servicios de acceso/snapshot/pago; en entorno de integración ACSoft: `Negocio`, `Producto`, `StockMovimiento`, `User`, `Cliente`, `negocio_user`, `negocio_modulo` y flujos Gestion de venta/compra/stock.
- **Migraciones esperadas:** ninguna sobre producción en el spike; diseñar y ensayar esquema de extensión 1:1 Commerce y tabla de mapeo de IDs si se aprueba. No copiar historial de migraciones Akila a ACSoft.
- **Riesgo:** HIGH, especialmente dos tablas productos, dos User y external_reference numérica. El entregable debe fijar una autoridad de precio/stock/publicación y política de borrado.
- **Dependencias:** G0; copia de integración aislada con esquema ACSoft y fixtures sin PII.
- **Tests obligatorios:** contratos de cotización/snapshot v1/v2; prueba de importación con IDs colisionantes y mapeo de FKs; conservación de referencias MP/PDF; regresión ACSoft ventas/compras/stock, roles y branding.
- **Terminado:** decisión ejecutable documentada sobre identidad única Producto/extensión o puente transitorio explícito; mapeo cliente administrativo vs customer; owner futuro `negocio_id`; reglas de retención y cuenta legacy Sushi; ensayo sin pérdida. Ninguna entidad StoreTenant paralela.

## G2 — Núcleo de catálogo/configuración neutral

- **Objetivo:** producto simple sin ingredientes y configuración genérica, conservando los bloques y grupos existentes.
- **Archivos/módulos afectados:** `ProductPricingService`, `MenuConfigurationService`, `PromotionRuleEvaluator`, `MenuMoney`, `OrderItemSnapshotService`, `Producto`, `BloquePromocion`, `GrupoOpcion`, `Opcion`, `ReglaPromocion`, `UnidadComercial`; presenters `MenuCardSummary` y componentes/modulos de personalización.
- **Migraciones esperadas:** aditivas solo donde exista necesidad semántica: política/rol explícito de grupo/bloque, unidad/etiqueta comercial o extensión de publicación; mantener columnas pieces/rolls y lectores antiguos. No renombrar tablas masivamente. Reglas incompatibles X/Y solo si entran al MVP aprobado.
- **Riesgo:** HIGH: cambiar hash, unidades incluidas, recargos o selección fija puede cambiar ventas Sushi.
- **Dependencias:** contrato G1, fixtures de comparación representativos del catálogo actual.
- **Tests obligatorios:** figura Luffy $19.990 sin ninguna fila ingrediente/grupo/bloque; hamburguesa con carne/queso/extras; café con tamaño/leche; misma cotización/hash ante orden distinto de opciones; manipulación de precios/IDs; combo heterogéneo; legado Sushi, CLP y snapshots intactos.
- **Terminado:** caminos simple y configurable neutrales pasan; semántica Sushi queda como política/template o adaptador legacy versionado; opciones sin ingrediente funcionan; límites min/max conservados; pedidos antiguos nunca se recalculan desde catálogo.

## G3 — Administración cero código y publicación

- **Objetivo:** permitir construir completamente Hamburguesa Doble desde interfaces normales, sin seeders ni edición PHP.
- **Archivos/módulos afectados:** controladores `Admin/ProductoController`, `Admin/OperationsController`, nuevos endpoints de grupos/opciones/asignaciones/bloques/reglas, requests/policies; vistas `admin/productos/*`, `admin/personalization`, componentes admin y rutas. Aprovechar el panel operativo presente; no ejecutar el prompt Admin 2.0 previo.
- **Migraciones esperadas:** validación/publicación/versionado mínimo y auditoría si se necesita; las relaciones base ya existen. No recrear grupos/opciones/bloques con otras tablas.
- **Riesgo:** HIGH: grupos compartidos pueden modificar muchos productos; opciones mal asignadas hacen configuración imposible.
- **Dependencias:** G2; las operaciones nuevas aceptan owner explícito del contrato G1 aunque aún no se exponga multi-tenant.
- **Tests obligatorios:** CRUD de grupo/opción, overrides heredados, rechazo de mínimo imposible y sujeto ajeno, transacciones/rollback, impacto de cambios en grupos compartidos, borrador/publicar/desactivar, duplicación segura; descripcion nullable request contra NOT NULL corregido; validación integral de catálogo antes de publicar.
- **Terminado:** pasos 5–12 y 16 de Pepe funcionan por UI/HTTP; personalizable se establece coherentemente; no existe dependencia de BatchData para crear producto; controles no permiten dejar publicado un producto imposible de cotizar.

## G4 — Branding, contenido y operación por negocio

- **Objetivo:** tienda blanca con settings comerciales reales, sin configuración del dueño en .env.
- **Archivos/módulos afectados:** `OperationSettings`, `config/delivery.php`, `config/admin.php`, `CheckoutController`, `home`, layouts, store header/footer/catalog-image, checkout, PDF/print; CSS custom/admin y presenters. Integrar con contrato de Negocio.logo/color_primario/color_secundario, sin recrear BrandSettings paralelo incompatible.
- **Migraciones esperadas:** extensión settings comerciales vinculada a Negocio o esquema acordado; horarios/zonas/métodos/configuración visual tipada. La FK a Negocio definitivo se aplica en entorno ACSoft G5 si Akila aún es harness de transición; no crear una segunda entidad Negocio productiva.
- **Riesgo:** HIGH en cierre/entrega/precio; MEDIUM visual. Cambiar branding no debe reemitir PDFs antiguos.
- **Dependencias:** G1 y G3; contrato de identidad ya acordado.
- **Tests obligatorios:** logo/color/hero/SEO por negocio, defaults sin logo, escape/URL/color seguros, modo retiro/delivery/no disponible, tarifa fija/umbral según alcance, fuera de zona, tienda cerrada bloquea checkout en servidor, horarios nocturnos y zona horaria, histórico conserva emisor/importes.
- **Terminado:** pasos 3–4, 13–14 y horarios configurables; checkout respeta disponibilidad real. Ningún nombre/contacto/tarifa Sushi obligatorio en nueva tienda. Estructura responsive y accesibilidad conservadas.

## G5 — Módulo interno y aislamiento en ACSoft

- **Objetivo:** integrar Core generalizado con Negocio/User/roles/módulos reales, en despliegue aislado antes de habilitar múltiples tiendas.
- **Archivos/módulos afectados:** namespace/módulo Commerce y provider/rutas de integración; ACSoft `bootstrap/app.php`, `routes/web.php`, Negocio/Modulo/negocio_user; todos los modelos/queries de las 17 tablas comerciales de la matriz, Cart/Checkout/Profile/admin, Home/layout queries, access/invoice, imports y storage.
- **Migraciones esperadas:** crear/extender únicamente esquema Commerce acordado en G1; negocio_id con backfill revisado, índices locales, FKs compuestas y PK settings; no modificar las tablas globales por costumbre. Extensión de catálogo ACSoft y mapa legacy conforme contrato. Aditivas/reversión funcional sin eliminar historia.
- **Riesgo:** CRITICAL: aislamiento, importación de IDs, roles, archivos, cadena CASCADE de Cliente/Negocio y regresión ventas/stock.
- **Dependencias:** G1–G4; entorno de integración y restauración ensayada. No exponer nuevas cuentas pagadoras todavía.
- **Tests obligatorios:** CommerceTenantIsolationTest de lecturas/escrituras/bindings/validación/pivotes/clientes/PDF/uploads/sesión/settings/cache; queries sin contexto fallan; mismas slugs en A/B; FK compuesta en MariaDB aislado; toda suite ACSoft existente y Core/demos.
- **Terminado:** Commerce habilitado mediante Negocio.tieneModulo; rutas `/gestion/{negocio}/commerce` y tienda pública resuelven Negocio validado; superadmin/manager/staff/customer separados; imágenes/documentos con ownership; carrito A no contamina B; sin lecturas globales accidentales ni catálogo duplicado ambiguo.

## G6 — Pagos por cuenta, webhooks y dominios verificables

- **Objetivo:** cobros de cada negocio a su cuenta con reconciliación verificable y referencias durables.
- **Archivos/módulos afectados:** `MercadoPagoService`, `MercadoPagoPaymentService`, `MercadoPagoWebhookSignature`, `MercadoPagoController`, checkout/session/access, settings de pagos, tenant resolver/dominios; jobs/eventos solo si se justifican.
- **Migraciones esperadas:** cuentas de pago cifradas, intentos/referencias/idempotencia, relación pedido-cuenta/ambiente; estado de dominio verificado si campos existentes no bastan. No trasladar credenciales automáticamente sin revisión segura.
- **Riesgo:** CRITICAL: cuenta errónea, pago cruzado, firma/replay, secretos y SDK estático persistente.
- **Dependencias:** G5; decisión de aplicación/cuenta/OAuth Mercado Pago y control DNS/TLS posterior autorizado. CLP inicial, sin multicurrency.
- **Tests obligatorios:** firma/cuenta/receptor/referencia/monto/moneda cruzados y concurrentes; A/B mismo monto, reintentos sin sesión, devolución/contracargo, SDK sin contaminación entre ciclos; dominio desconocido/no verificado/conflicto; retorno firmado y PDF tenant; conciliación e idempotencia durable con gateway simulado.
- **Terminado:** paso15 Pepe desde administración segura; webhook resuelve cuenta/pedido/negocio sin confiar en body; intento existente nunca cambia receptor; rutas legacy Sushi siguen conciliando. Verificación de proveedor real en sandbox se realiza en una fase autorizada de integración, sin sustituir pruebas aisladas.

## G7 — Blank y templates con aceptación completa

- **Objetivo:** instalación y alta sin demo; Restaurante/Sushi/Hamburguesería/Retail/Vacío como configuración inicial opcional.
- **Archivos/módulos afectados:** onboarding ACSoft, bootstrap técnico, `DatabaseSeeder`, seeders/demo importers, `MenuIdentity`, `MenuBatchImporter`, settings de publicación; tests de aceptación. Los archivos Sushi existentes se conservan y adaptan a owner explícito cuando corresponda.
- **Migraciones esperadas:** registro/versionado de instalación de template si hace falta; no se requieren productos o grupos obligatorios. Variantes/SKU solo si se aprueba como requisito del primer retail; eso exige extensión y pruebas de stock, no esconderlo en extras JSON.
- **Riesgo:** HIGH: reimportar puede sobrescribir cambios del dueño o contaminar otro tenant.
- **Dependencias:** G5–G6 y editor G3; bootstrap completamente separado del seeding comercial.
- **Tests obligatorios:** BlankBusinessCommerceTest completo por HTTP/servicios normales, 17 pasos de Pepe, cero filas ingredientes; tienda anime simple y cafetería; plantilla idempotente acotada a tenant; principal Sushi mantiene valores/IDs; plantilla no sobrescribe ediciones sin política.
- **Terminado:** crear Negocio vacío y completar catálogo/marca/entrega/pago/publicación/pedido sin código/seeders. Pantallas vacías sin errores. Instalación vacía no muestra marca Sushi ni categoría hand-roll.

## G8 — Incorporar Sushi Akila y validar entrega de integración

- **Objetivo:** conservar la implementación real Akila como tenant/template, con su historia y operación; dejar el módulo preparado para una integración/despliegue autorizado.
- **Archivos/módulos afectados:** herramientas de importación/mapeo y compatibilidad, cuentas legacy/referencias MP, archivos y PDF; branding/settings/template Sushi; documentación operativa, tests Core y ACSoft. No retirar lectores antiguos hasta demostrar que ningún histórico depende de ellos.
- **Migraciones esperadas:** solo ajustes aditivos derivados del ensayo; importación/backfill de propiedad y mapeos aprobados, nunca limpieza de historia. No se renombran PK por estética.
- **Riesgo:** CRITICAL para historia/pagos; requiere respaldo/restauración ensayada y comparación de contenido.
- **Dependencias:** G1–G7, validación de integridad y ensayo con copia autorizada de datos.
- **Tests obligatorios:** comparar conteos/hashes/importe/identidad por mapa, snapshots v1/v2, PDFs existentes y descargas, referencias de pago y reintentos, catálogo Sushi completo, escenarios Pepe/café/anime, regresión ACSoft venta/compra/caja/stock, aislamiento simétrico y build.
- **Terminado:** Sushi Akila es un Negocio, no fallback global; historial/archivos/cobros preservados; negocio vacío y dos negocios diferentes funcionan; runbook de importación/rollback funcional y lista de pendientes no bloqueantes revisados. Extracción de package o API queda fuera.

## Puertas de seguridad y entregable final

No habilitar dos negocios antes de G5 ni múltiples cuentas antes de G6. No certificar cero código antes de G7. No borrar legacy a cambio de pasar tests. Cocina, ingredientes, recetas e inventario de insumos pueden evolucionar aparte; stock retail debe integrarse con ACSoft si el primer negocio lo requiere.

El destino de estas fases es un módulo interno probado, con contratos claros, pruebas de aislamiento, conservación histórica y onboarding administrativo. La publicación y el traslado de datos reales requerirán su encargo posterior; este documento no los ejecuta.

**ACSoft Commerce listo para integrarse.**
