# ACSoft Commerce — matriz tenancy

Fecha: 2026-10-26. **26 tablas reales**, comprobadas con SHOW TABLES/SHOW CREATE TABLE y lectura de conteos en transacción READ ONLY. 46 migraciones aplicadas. No se agregan columnas. Convención recomendada: `negocio_id`, por compatibilidad con ACSoft; equivale al business_id conceptual.

## Matriz completa

| Tabla | Filas observadas | Propósito | Global/Tenant | Necesita business_id | Dependencias reales | Riesgo | Notas |
|---|---:|---|---|---|---|---|---|
| admin_changes | 11 | Auditoría de cambios | Tenant (propiedad directa) | Sí: negocio_id recomendado | users; entity_type/entity_id sin FK | CRITICAL | Entidad polimórfica necesita comprobar propietario; no inferir negocio desde user_id. |
| bloque_grupo_opcion | 187 | Grupo asignado a bloque | Tenant (propiedad también indirecta) | Sí: negocio_id recomendado | bloques_promocion; grupos_opciones | CRITICAL | Ambos extremos del mismo negocio; FK compuesta para cada extremo. |
| bloques_promocion | 130 | Componentes configurables | Tenant (propiedad directa) | Sí: negocio_id recomendado | productos | HIGH | Owner derivable; columna explícita facilita binding y FK compuesta al producto. |
| cache | 4 | Cache compartida | Global | No | Ninguna FK | MEDIUM | Claves commerce:{negocio_id}:..., incluidas invalidaciones y locks. |
| cache_locks | 0 | Locks técnicos | Global | No | Ninguna FK | HIGH | Namespace por negocio/cuenta/pedido cuando el recurso es tenant. |
| categorias | 7 | Clasificación del catálogo | Tenant (propiedad directa) | Sí: negocio_id recomendado | productos la referencia | HIGH | Cambiar UNIQUE nombre y slug globales por negocio. |
| failed_jobs | 0 | Fallos de worker | Global | No | payload/uuid | HIGH | Payload explícito negocio_id; restringir visibilidad de PII/secrets. |
| grupo_opcion_producto | 29 | Asignación comercial | Tenant (propiedad también indirecta) | Sí: negocio_id recomendado | productos; grupos_opciones | CRITICAL | Pivote no se vuelve seguro filtrando solo Producto. |
| grupos_opciones | 29 | Cardinalidad reutilizable | Tenant (propiedad directa) | Sí: negocio_id recomendado | opciones; dos pivotes | HIGH | Slug único por negocio. |
| ingrediente_producto | 166 | Composición y receta preliminar | Tenant (propiedad también indirecta) | Sí: negocio_id recomendado | productos; ingredientes | HIGH | Ambos extremos tenant; cantidad_permitida sigue legacy. |
| ingredientes | 23 | Insumos opcionales | Tenant (propiedad directa) | Sí: negocio_id recomendado | opciones; reglas; pivotes | HIGH | No catálogo global de insumos de clientes diferentes. |
| job_batches | 0 | Control de lotes | Global | No | IDs lógicos de jobs | MEDIUM | Infraestructura global; políticas para ver batches por negocio si se exponen. |
| jobs | 0 | Cola de trabajos | Global | No | payload serializado | HIGH | Resolver negocio explícito en handle y limpiar contexto al terminar. |
| migrations | 46 | Versiones de esquema | Global | No | Infraestructura | LOW | No duplicar historial al integrar ni copiar migraciones antiguas sobre ACSoft. |
| opciones | 115 | Valores y recargos | Tenant (propiedad directa) | Sí: negocio_id recomendado | grupos_opciones; ingrediente nullable | HIGH | FK grupo y FK ingrediente con negocio coincidente. |
| operation_settings | 1 | Configuración operativa | Tenant (propiedad directa) | Sí: negocio_id recomendado | key operation sin negocio | CRITICAL | PK actual key: futura unique(negocio_id,key); evitar singleton global. |
| order_items | 5 | Historia/snapshot de venta | Tenant (propiedad también indirecta) | Sí: negocio_id recomendado | orders CASCADE; productos RESTRICT | CRITICAL | Garantizar producto y order del mismo negocio; no editar snapshots viejos. |
| orders | 3 | Cliente/importes/pago/fulfillment | Tenant (propiedad directa) | Sí: negocio_id recomendado | users nullable SET NULL; order_items | CRITICAL | Dueño principal; toda búsqueda, pago, PDF y sesión deben validar negocio. |
| password_reset_tokens | 0 | Recuperación identidad global | Global | No | email (sin FK) | HIGH | Usar broker/usuarios de ACSoft; no duplicar tabla ni scope por tienda arbitrario. |
| producto_swappable_ingredient | 0 | Compatibilidad de sustituciones | Tenant (propiedad también indirecta) | Sí: negocio_id recomendado | productos; ingredientes | HIGH | Cero filas actual; preservar lector legacy hasta retiro seguro. |
| productos | 37 | única identidad vendible Akila | Tenant (propiedad directa) | Sí: negocio_id recomendado | categorias; configuración; historia | CRITICAL | Colisión con productos de ACSoft: resolver identidad antes de importación. |
| promocion_handroll | 0 | Componentes fijos legacy | Tenant (propiedad también indirecta) | Sí: negocio_id recomendado | promociones; productos | HIGH | Cero filas actual; ambos extremos con mismo negocio. |
| promociones | 26 | Complemento legacy de producto | Tenant (propiedad directa) | Sí: negocio_id recomendado | producto_id nullable RESTRICT | HIGH | Filas sin producto_id requieren asignación explícita de origen, no deducción. |
| reglas_promocion | 479 | Restricciones/recargos | Tenant (propiedad directa) | Sí: negocio_id recomendado | productos; ingrediente u opción | CRITICAL | Sujeto debe pertenecer al negocio y ser aplicable al producto. |
| sessions | 17 | Sesión identidad global | Global | No, contexto dentro del payload | user_id sin FK | HIGH | Claves comerciales por negocio; cookie host-only; evitar compartir sesiones entre dominios propios. |
| users | 1 | Identidad global propuesta | Global | No; membresía negocio_user y perfil cliente tenant | orders; admin_changes | CRITICAL | Reconciliar usuarios de Akila con ACSoft; email coincidente no autoriza fusionar privilegios. |

## Decisión de ownership e integridad

17 tablas comerciales/operativas necesitan tenant awareness. Se recomienda negocio_id explícito en las 17 para consultas directas, auditoría, locks, bindings y FKs compuestas. Las cinco tablas pivote y order_items podrían derivarlo por JOIN, pero habría que impedir lecturas/escrituras sin ese JOIN y aun así verificar ambos extremos. El costo de una columna repetida se justifica aquí por relaciones cruzadas reales y SQL directo en OperationSettings/GrupoOpcion. No se propone una columna en las nueve tablas globales.

Añadir negocio_id por sí solo no garantiza integridad: índice único (negocio_id,id) en cada padre y FK compuesta (negocio_id,padre_id) desde hijos/pivotes; confirmar sintaxis/índices en MariaDB aislado. Nunca dar a negocio_id un default global ni aceptar su valor desde un formulario público. Poblarlo desde la procedencia comprobada de Akila antes de exigir NOT NULL.

Relaciones críticas: producto→categoría; producto→grupo; bloque→producto; bloque→grupo; opción→grupo→ingrediente opcional; regla→producto→opción/ingrediente; promoción→producto y sus componentes; línea→pedido→producto. Una regla que referencia una opción del mismo negocio pero no asignada al producto también necesita validación de aplicabilidad.

## Unicidad actual y cambio futuro

| Restricción actual | Recomendación |
|---|---|
| categorias.nombre UNIQUE; categorias.slug UNIQUE | unique(negocio_id,nombre), unique(negocio_id,slug) |
| productos.slug UNIQUE | unique(negocio_id,slug), generador de slug limitado al mismo negocio |
| grupos_opciones.slug UNIQUE | unique(negocio_id,slug) |
| opciones(grupo_opcion_id,slug) | Ya es local al grupo globalmente identificado; conservar pareja y garantizar owner por FK compuesta |
| bloques_promocion(producto_id,orden) | Ya local al producto; conservar más integridad tenant |
| reglas_promocion(producto_id,codigo) | Ya local al producto; conservar más integridad tenant |
| promociones.producto_id UNIQUE nullable | Seguir 1:1 con producto global; no sustituir por precio paralelo |
| Cinco pivotes UNIQUE pareja | Con IDs globales conservar pareja; tenant FK asegura homogeneidad |
| operation_settings.key PK | unique(negocio_id,key), definir PK compatible |
| users.email UNIQUE | Global si se unifica identidad ACSoft; decidir antes de importar |
| failed_jobs.uuid; cache.key; cache_locks.key; sessions.id; job_batches.id | Infraestructura global, namespaces de recursos tenant cuando corresponda |

Ingredientes.nombre no tiene UNIQUE SQL aunque el controller exige unique; no inventar que existe. Concurrencia: validación unique no sustituye índice. admin_changes.entity_id no tiene FK; control de propietario y tipos permitidos en aplicación.

## Tablas futuras esperadas (no existen hoy)

Reutilizar negocios, negocio_user, modulos, negocio_modulo de ACSoft. Posibles extensiones Commerce por negocio: settings comerciales tipados, dominios verificados (si se necesitan varios por negocio), cuentas de pago cifradas, intentos de pago durables, perfiles de cliente y variantes/SKU. StockMovimiento ya existe en ACSoft: adaptar su uso antes de crear otro ledger de retail. Recetas e insumos se agregan solo si el módulo se habilita.

## FKs y plan blank conceptual

No deshabilitar FKs ni vaciar historial para crear otro negocio. Crear un nuevo Negocio sin filas comerciales es la vía recomendada. Una copia desechable de Akila podría vaciarse en este orden lógico: referencias de sesión/cart/PDF identificadas → order_items → orders → reglas_promocion → bloque_grupo_opcion y grupo_opcion_producto → bloques_promocion → promocion_handroll → promociones → ingrediente_producto y producto_swappable_ingredient → opciones → grupos_opciones → productos → ingredientes/categorias. Los grupos/pivotes deben retirarse antes que sus padres; las reglas deben retirarse antes que opciones/ingredientes por RESTRICT. admin_changes requiere política de retención y filtro por entidad/tenant; no tiene FK al catálogo. operation_settings puede reinicializarse por negocio con defaults seguros.

Ese orden describe dependencias, **no autoriza borrar pedidos Sushi Akila**. Los 3 orders y 5 order_items reales se conservan con sus IDs y snapshots. Un blank de producción se crea en otro negocio o instalación, nunca limpiando la historia del tenant Sushi. Permanecen migraciones, identidades/membresías válidas, módulos, llaves de cifrado, infraestructura y los datos históricos de otros tenants. No se entregan comandos de borrado.
