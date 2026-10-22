# Estabilización de autenticación y perfil — 2026-10-21

## Comportamiento oficial

- Login: autentica, regenera sesión y respeta el destino solicitado; sin destino previo va a `profile.edit`.
- Registro: guarda contraseña con hash, autentica y redirige a `profile.edit`.
- Usuarios autenticados que visitan login/registro: el middleware los envía a `profile.edit`.
- Logout: cierra autenticación, invalida sesión, regenera token CSRF y vuelve al inicio.
- Confirmación de contraseña: mantiene validación de Laravel y marca temporal de confirmación; respeta el destino solicitado y usa el perfil como fallback.

## Decisiones de producto

La verificación de correo no forma parte del flujo actual: User no implementa MustVerifyEmail, no hay middleware verified ni enlaces de verificación en el perfil o registro. Se retiran los tres endpoints, sus tres controladores y la vista de Breeze. No se introduce verificación obligatoria ni se cambia User. Se conserva email_verified_at y su invalidación al cambiar el correo para no alterar datos históricos.

Se adopta exclusivamente PATCH /profile. La búsqueda en app, routes, resources y tests encontró un solo consumidor del update: el formulario Blade, sin clientes JavaScript ni API adicionales. Conserva method POST multipart y CSRF, agregando @method('PATCH'); así PHP sigue procesando los archivos. Una prueba envía un avatar mediante este mismo mecanismo. ProfileController conserva su comportamiento de volver al formulario; las pruebas ahora incluyen el origen /profile.

No existe botón, ruta ni método de eliminación de cuenta en el flujo actual. No se incorpora esa función. La prueba comprueba que DELETE /profile devuelve 405 y conserva usuario y autenticación. Una futura implementación requeriría confirmación de contraseña.

## Causas de los ocho fallos reportados

1. AuthenticationTest, login válido: esperaba la ruta inexistente dashboard, aunque el controlador ya usaba profile.edit.
2. RegistrationTest, registro válido: misma expectativa obsoleta de dashboard.
3. EmailVerificationTest, verificación válida: flujo generado por Breeze terminaba en dashboard; no representa una función del producto.
4. PasswordConfirmationTest, contraseña válida: el controlador resolvía dashboard después de confirmar, causando error.
5. ProfileTest, actualizar información: enviaba PATCH a una ruta exclusivamente POST (405). También faltaba el origen que necesita back().
6. ProfileTest, correo sin cambios: misma incompatibilidad PATCH/POST y ausencia de origen.
7. ProfileTest, borrar cuenta: esperaba DELETE /profile y borrado, función inexistente (405).
8. ProfileTest, contraseña incorrecta al borrar: esperaba errores de una función de borrado inexistente (405).

Las referencias genéricas a dashboard se reemplazan por profile.edit en confirmación, navegación heredada y expectativas de login/registro; las de los controladores de verificación se eliminan con dichos restos. admin.dashboard es real y se conserva.

## Archivos de esta fase

Modificados:
- app/Http/Controllers/Auth/ConfirmablePasswordController.php
- bootstrap/app.php
- routes/auth.php
- routes/web.php
- resources/views/profile/edit.blade.php
- resources/views/layouts/navigation.blade.php
- tests/Feature/Auth/AuthenticationTest.php
- tests/Feature/Auth/RegistrationTest.php
- tests/Feature/Auth/EmailVerificationTest.php
- tests/Feature/Auth/PasswordConfirmationTest.php
- tests/Feature/ProfileTest.php

Eliminados:
- app/Http/Controllers/Auth/EmailVerificationNotificationController.php
- app/Http/Controllers/Auth/EmailVerificationPromptController.php
- app/Http/Controllers/Auth/VerifyEmailController.php
- resources/views/auth/verify-email.blade.php

Nuevo: este documento. El build regenera public/build/manifest.json y el CSS compilado app-BQyVDEPg.css. No se editan fuentes CSS ni diseños. El árbol ya contenía cambios extensos anteriores a esta fase; no se revierten ni se atribuyen a esta estabilización.

## Validación

- php artisan route:list: correcto, 58 rutas; perfil GET/PATCH, sin dashboard genérico ni DELETE de perfil.
- php artisan test --filter=CommerceSecurityTest: 59 aprobadas, 183 aserciones. Archivo intacto durante esta fase.
- php artisan test: 87 aprobadas, 277 aserciones, cero fallos.
- npm run build: correcto (Vite, 65 módulos).
- git diff --check: correcto.

El PHP CLI carga C:\php\php.ini sin SQLite activo. Para ejecutar artisan test y su subproceso se usó PHP_INI_SCAN_DIR apuntando a un directorio temporal con extension=pdo_sqlite y extension=sqlite3. No se modificó php.ini ni la conexión del proyecto. Las pruebas usan SQLite en memoria. La ejecución sin estas extensiones falla por falta de driver; para repetirlas hay que habilitarlas en el proceso.

No quedan fallos funcionales detectados en el alcance. No se hace commit ni se ejecutan comandos destructivos de base de datos.
