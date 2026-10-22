# Seguridad de compra — Sushi Akila

## Despliegue

1. Respaldar base de datos y almacenamiento antes de desplegar.
2. Aplicar la migración aditiva `2026_10_21_000001_add_payment_verification_to_orders`.
   Ya fue aplicada en la base local durante esta implementación. No se modificaron filas históricas.
3. Configurar `MERCADOPAGO_ACCESS_TOKEN` y `MERCADOPAGO_WEBHOOK_SECRET` con las credenciales del mismo ambiente/aplicación. No guardar sus valores en Git.
4. Usar `APP_URL` HTTPS público. Configurar notificaciones **payment** en Mercado Pago hacia `/checkout/webhook`.
5. Ejecutar `php artisan invoices:privatize --dry-run` y luego `php artisan invoices:privatize` donde estén las boletas históricas. El comando verifica SHA-256, conserva una copia privada y archiva el original en privado; no borra contenido. Se puede repetir. Si encuentra documentos distintos con el mismo nombre, se detiene sin sobrescribirlos.
6. Apache: mantener la regla de `public/.htaccess` que bloquea `/storage/boletas`. Nginx: agregar `location ^~ /storage/boletas/ { deny all; }`. Esta protección debe estar activa durante la transición; después del traslado ya no hay PDFs históricos en el directorio público. Revisar también copias publicadas en CDN u otros directorios.
7. Ejecutar `npm run build` y desplegar los assets junto al manifest. Verificar permisos de escritura en `storage/app/private` y el enlace de imágenes de `public/storage`.

La clave de aplicación debe permanecer estable: los enlaces firmados dependen de ella. No usar `migrate:fresh`, `db:wipe` ni seeders para este despliegue.

## Pagos

`status` conserva su valor y su uso en preparación. No se migran valores históricos a pagos aprobados.
`payment_status` es nullable: null significa histórico/sin verificar. Pedidos nuevos comienzan en `pending`.
Se conservan los estados del proveedor: `pending`, `in_process`, `authorized`, `approved`, `rejected`, `cancelled`, `refunded`, `charged_back`.

Los retornos success/failure/pending comparten una vista que informa el estado persistido. Ningún estado de URL es evidencia de pago. El payment ID solo localiza el recurso que se consulta mediante el SDK. Se valida el ID devuelto, referencia externa, pedido esperado cuando es un retorno, monto exacto a centavos, moneda CLP, estado y fecha de actualización.

El webhook exige firma HMAC SHA-256 (`id:...;request-id:...;ts:...;`), timestamp dentro de diez minutos y coincidencia del ID del cuerpo si está presente. Solo la ruta del webhook está exenta de CSRF. Sin secreto configurado responde 503; firma inválida 401; recurso inválido 400/422; indisponibilidad de API 503 para permitir reintentos.

Referencia oficial de firma: https://www.mercadopago.cl/developers/en/docs/wallet-connect/notifications

La actualización se serializa mediante transacción y bloqueo de fila. Notificaciones iguales no generan otra escritura/evento. Se ignoran actualizaciones anteriores y no se degrada un pago aprobado a pendiente/rechazado. Un reembolso/contracargo no se revierte con una aprobación antigua. Una aprobación de otro intento no genera efectos duplicados en el mismo pedido.

No se envían direcciones fabricadas de Costa Rica. La moneda es CLP; la dirección real queda en el pedido para el despacho del restaurante. Delivery se incluye como ítem explícito para que el monto cobrado sea exactamente el total registrado.

## Acceso y documentos

- Pedidos de usuarios: solo su propietario puede acceder por rutas públicas, incluso si otro usuario obtiene un enlace firmado.
- Administradores: acceden a pedidos y boletas ajenas exclusivamente mediante rutas administrativas protegidas por `auth` + `admin`.
- Invitados: la sesión de compra conserva acceso; los retornos usan URLs firmadas por dos días y los enlaces de descarga expiran en 24 horas. La firma es un permiso de portador: no compartir estos enlaces.
- Un ID por sí solo no autoriza una consulta o descarga.
- Los PDFs nuevos se generan bajo demanda en el disco privado. Los históricos se leen de forma compatible y se copian en privado; el comando de despliegue retira sus originales del acceso público conservándolos en un archivo privado.
- Una boleta es un resumen del pedido; no constituye por sí misma prueba de que el pago esté aprobado.

## Precios y personalización

`ProductPricingService` valida y normaliza la configuración y calcula todos los importes. El navegador envía elecciones, nunca importes autorizados. `price`, `price_adjustment`, `subtotal` y `total` no intervienen en el cálculo. El endpoint de cotización permite mostrar el resultado del servidor sin duplicar tarifas/límites en JavaScript.

Contrato normalizado: `envoltura_id`, `Proteínas[]`, `vegetales[]`, `cream_cheese`, `scallions`. Se acepta `base_id` como alias de compatibilidad; si ambos IDs discrepan se rechaza. Los dos booleanos conservan el significado histórico de exclusión (true = sin ese ingrediente).

Reglas conservadoras basadas en el código existente:

- La envoltura debe pertenecer a los ingredientes asignados al producto. Si se omite se usa la primera asignada, también en agregado rápido.
- La cantidad original por ingrediente proviene de `ingrediente_producto.cantidad_permitida`: valores menores de diez son cantidades de rolls; múltiplos de diez son registros históricos expresados en cortes. Valores mayores incompatibles se rechazan para revisión, sin modificar la base.
- No se agregan slots nuevos: se conserva el número original de proteínas y vegetales.
- Si hay `swappables` explícitos, las opciones son esos ingredientes más los originales, siempre del tipo correcto. Sin esa lista se conserva el comportamiento previo de ofrecer el catálogo global del mismo tipo.
- Límite de una aparición de salmón y dos de otros ingredientes, conservando composiciones originales que ya superaban esos valores. No se aumentan esos excesos históricos.
- Recargo: $1.000 por sustitución efectiva respecto de la composición original, sumando los excedentes por ingrediente. Volver a la composición original elimina el recargo; no se cobra por contar clics.
- Exclusiones solo en productos personalizables. No se habilitan extras adicionales ni se reinterpretan campos históricos `free_extras` que no participaban del flujo activo.
- Cantidad comprada: 1–50 por configuración; máximo 100 configuraciones por carrito. `productos.unidades` continúa representando el contenido del producto, no stock.

Estas reglas deben contrastarse con el catálogo comercial antes de producción, especialmente registros antiguos y productos especiales.

## Checkout y reintentos

Agregar/actualizar/cotizar y checkout usan el mismo servicio. Checkout vuelve a cargar productos y configuraciones.
Si cambió el precio, actualiza el carrito y vuelve a su pantalla con un aviso: el cliente debe revisar y confirmar de nuevo. No se cobra un importe nuevo silenciosamente.

Carritos anteriores sin configuración estructurada no pueden revalidarse de forma segura: se mantienen en sesión pero se pide volver a agregar sus productos. No se interpretan descripciones libres como elecciones autorizadas.

Un fingerprint de datos, configuración y usuario reutiliza el pedido del intento de la misma sesión. La creación de checkout bloquea la sesión para serializar envíos repetidos. Una preferencia exitosa se reutiliza hasta su expiración (un día). Si falla su creación, el carrito permanece y el mismo intento puede reintentarse sin insertar otro pedido.

Al completar un pago se libera la clave de ese intento: una compra posterior idéntica puede generar un pedido nuevo. Un pedido reembolsado o con contracargo tampoco se reutiliza para cobrar otra compra.

El carrito se limpia únicamente al retornar con un pedido aprobado verificado, y solo si coincide con la copia que originó la compra. No se borra un carrito posterior con otros productos. Si el comprador no retorna, el webhook confirma el pago sin depender de su sesión; el carrito puede seguir visible hasta su próximo retorno.

## Validación y limitaciones

Las pruebas están en `tests/Feature/CommerceSecurityTest.php`, con SDK/gateway simulado y SQLite en memoria. No realizan pagos reales.
El PHP CLI carga `C:\php\php.ini`, sin `pdo_sqlite`/`sqlite3` activados. Las DLL ya existen; se pueden cargar solo para el proceso:

```powershell
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/pest tests/Feature/CommerceSecurityTest.php --compact
```

No se modificó la configuración global de PHP ni se instalaron paquetes. La suite anterior conserva discrepancias de rutas `dashboard`, PATCH/DELETE de perfil; no se añadieron rutas de eliminación para satisfacer pruebas obsoletas.

Resultados finales: 59 pruebas nuevas aprobadas (183 aserciones); suite completa mediante `php artisan test --compact` con carga temporal de las DLL: 76 aprobadas y 8 fallidas (224 aserciones). Las ocho fallidas corresponden a las discrepancias anteriores de autenticación/perfil. `npm run build`, `artisan about`, `artisan route:list` (61 rutas), lint PHP/JavaScript, Pint y `git diff --check` finalizaron correctamente.

Quedan fuera de esta fase: inventario (no existe aún), validación geográfica de kilómetros/zona de delivery, conciliación programada para notificaciones nunca recibidas y recuperación de acceso de invitados cuando expiraron sesión y firmas. Los pedidos históricos marcados pagados siguen sin verificar hasta recuperar su pago real; no se asume que los antiguos retornos eran confiables.

## Inventario de archivos de esta fase

Modificados:

- `.env.example`
- `app/Http/Controllers/Admin/OrderController.php`
- `app/Http/Controllers/CartController.php`
- `app/Http/Controllers/CheckoutController.php`
- `app/Http/Controllers/MercadoPagoController.php`
- `app/Http/Controllers/ProductoController.php`
- `app/Http/Controllers/ProfileController.php`
- `app/Models/Order.php`
- `app/Models/User.php`
- `app/Services/MercadoPagoService.php`
- `bootstrap/app.php`
- `config/services.php`
- `public/.htaccess`
- `resources/js/app.js`
- `resources/js/modules/cart.js`
- `resources/views/cart/index.blade.php`
- `resources/views/checkout/thankyou.blade.php`
- `resources/views/components/modal-producto.blade.php`
- `resources/views/layouts/app.blade.php` (contador; se conservaron ediciones ajenas del archivo)
- `resources/views/menu/index.blade.php`
- `resources/views/profile/pedidos.blade.php`
- `routes/web.php`
- `public/build/manifest.json` y assets generados por Vite, reemplazados por el build.

Nuevos:

- `app/Console/Commands/PrivatizeInvoices.php`
- `app/Services/CheckoutSessionService.php`
- `app/Services/MercadoPagoPaymentService.php`
- `app/Services/MercadoPagoWebhookSignature.php`
- `app/Services/OrderAccessService.php`
- `app/Services/OrderInvoiceService.php`
- `app/Services/ProductPricingService.php`
- `database/migrations/2026_10_21_000001_add_payment_verification_to_orders.php`
- `resources/js/modules/storePersonalization.js`
- `resources/views/admin/orders/index.blade.php`
- `resources/views/admin/orders/show.blade.php`
- `tests/Feature/CommerceSecurityTest.php`
- `docs/commerce-security.md`

Los cambios que ya estaban en `composer.lock` y `package-lock.json` no fueron creados por esta fase. No se hicieron commits.
