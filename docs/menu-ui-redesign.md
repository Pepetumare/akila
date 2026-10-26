# Carta pública: implementación de las referencias aprobadas

22 de octubre de 2026. Segunda iteración visual, basada directamente en los archivos del proyecto. Sustituye la propuesta visual libre anterior; conserva la estabilidad y las validaciones comerciales existentes.

## Referencias y comparación previa

Se leyó completamente [ui-reference/README.md](ui-reference/README.md) y se abrieron y analizaron:

- [reference-01-overview.png](ui-reference/reference-01-overview.png): siete paneles de carta, modal, cards y filtros móviles, carrito y tablet.
- [reference-02-detailed.png](ui-reference/reference-02-detailed.png): ocho paneles con detalle de cards, modal desktop/móvil, producto fijo, resumen y carrito.

Antes de modificar se abrió `/menu` real en Chrome a 1366×768 y el modal de 30 Piezas a Elección. Se probaron cotizaciones repetidas. No se partió de una maqueta independiente.

Diferencias concretas identificadas:

| Elemento | Antes de esta iteración | Implementación respecto de las referencias |
|---|---|---|
| Header y hero | Header de 80 px; margen superior de 48 px; hero crema con logo en un bloque separado | Header de 64 px desktop/56 móvil; hero oscuro continuo de 190 px, título blanco y banner a la derecha, sin margen superior vacío |
| Banner | Repetición del logo, aunque existía un asset gastronómico local | Se inspeccionó y reutilizó `public/img/slide3.jpg` únicamente como banner genérico; no se atribuye a ningún producto |
| Categorías | Chips sueltos con activo negro | Caja blanca, título pequeño, píldoras, conteos entre paréntesis y activo rojo |
| Cards | Ya existían resúmenes y estructura flex; relleno, título y botones se percibían grandes y separados | Cards más compactas, radio de 12 px, cuerpo de 14 px, metadata con iconos, precio destacado y CTA de 42–44 px |
| Fallback | Superficie carbón repetida con logo y círculos | Fondo cálido, plato/palillos abstractos mediante CSS y logo oscuro local; mantiene la misma superficie de imagen |
| CTA | Negro para todas las cards del menú | Rojo para personalizables; negro para fijos, como las referencias |
| Modal | Varios encabezados y condiciones antes de las opciones; opciones de proteínas y vegetales apiladas | Header oscuro, columnas 33/67, pasos por bloque, pestañas oscuras y tarjetas compactas; reglas en la columna informativa |
| Bloque activo | Envoltura opcional siempre desplegada, con controles de navegación repetidos | Envolturas/segundos ingredientes plegables; estado verde/naranja; controles Anterior/Siguiente compactos en móvil y navegación superior en desktop |
| Resumen | Información y selección menos diferenciadas; salsas también al final de configuración | Nombre/facts, selección por bloque, caja beige de salsas y desglose de precios en la izquierda |
| Carrito | Tabla en desktop y otro marcado distinto para móvil; CTA verde | Una sola lista adaptable con miniatura, elecciones, −/+, eliminación, subtotal y CTA rojo |

En la captura de escritorio a 1366 px la primera fila pasó aproximadamente de y=555 a y=462. La reducción proviene de la nueva distribución, no de ocultar contenido comercial.

## Correspondencia con reference-01

| Panel | Aplicación real |
|---|---|
| 1. Página de carta | Header negro, hero gastronómico compacto, categorías en blanco, resultados/búsqueda/orden, cards uniformes y paginación española |
| 2. Modal grande | Columna informativa y de selección a la izquierda; bloques, progreso y opciones a la derecha; compra en footer interno |
| 3. Card móvil | Una card por fila, imagen/fallback uniforme, badge, nombre, metadata, precio y CTA rojo o negro según configuración |
| 4. Filtros móviles | Chips horizontales con conteo real, búsqueda y orden compactos. Se conserva la navegación existente, sin añadir una pantalla ficticia de filtros |
| 5. Modal móvil | Casi fullscreen, header compacto, bloques plegables y compra siempre visible |
| 6. Carrito | Filas con miniatura, selección legible, controles −/+ y eliminación; subtotal y acceso a checkout |
| 7. Tablet | Dos columnas a 768 px y tres a 1024 px; categorías desplazables y modal adaptado a una columna |

## Correspondencia con reference-02

| Panel | Aplicación real |
|---|---|
| 1. Carta general | Hero oscuro continuo, caja blanca de categorías, jerarquía compacta y grid de cuatro columnas en escritorio amplio |
| 2. Card de detalle | Badge real, categoría pequeña, nombre limitado, hasta cuatro atributos, precio/CLP y CTA alineados al pie |
| 3. Modal desktop | Header oscuro, layout 33/67, tabs negras para tipos de ingrediente, opciones con borde/fondo rojo y check, estados Completado/Pendiente y footer estable |
| 4. Carta móvil | Una columna, chips horizontales y filtros adaptados; no hay overflow de página |
| 5. Modal móvil | Acordeones, tabs accesibles, configuración primero y resumen después; footer dentro del panel |
| 6. Producto fijo | Composición y preparación en cajas separadas, sin elecciones obligatorias de bloques. Los adicionales opcionales reales permanecen disponibles |
| 7. Resumen de selección | Filas por bloque, sabores/adicionales reales, caja de salsas y precio base/adicionales/total provenientes de cotización |
| 8. Carrito | Selección, cantidades, precio histórico de la línea, subtotal y CTA rojo; funciona en la ruta real `/cart` |

La [comparación visual navegable](ui-reference/verification/comparison.html) muestra las referencias junto a capturas de la aplicación real. Los recortes son solo ventanas CSS sobre las imágenes originales; no se modificaron las referencias.

Al terminar se abrieron ambas referencias originales en una ventana de Chrome y `/menu`/el modal real en otra, colocadas lado a lado. Se revisaron nuevamente hero, chips, cards, opciones, resumen y footer. También se abrió el comparador y se verificó que todas las imágenes locales cargaran.

## Adaptaciones necesarias: diseño sin datos ficticios

- No se copiaron fotografías de las referencias ni se descargaron imágenes. Las cards siguen sin fotografía específica: usan un fallback genérico. El banner gastronómico ya estaba en el repositorio y no identifica ningún producto concreto.
- No se añadieron corazones/favoritos sin funcionalidad, popularidad, «Más vendido», «Nuevo» ni recomendaciones ficticias.
- Se mantienen los nombres, precios y orden del catálogo real. Por ejemplo, 20 Piezas a Elección cuesta $10.000, aunque una ilustración muestre otro importe.
- Los conteos de categorías son reales. Gohan no aparece porque tiene cero productos activos; no se añadió una excepción por nombre.
- El progreso representa los bloques reales —2 a 6 rolls o unidades Hand Roll—. No se inventaron etapas obligatorias «Extras» o «Resumen» para productos que no las tienen.
- Las opciones se agrupan por los tipos reales del schema. Las pestañas no duplican grupos, no desactivan inputs ocultos ni permiten elegir proteína más vegetal cuando el grupo exige una sola opción.
- Las cards muestran «3 rolls de 10» únicamente cuando los agregados mínimo/máximo de sus bloques confirman que la cantidad es uniforme. No se deducen reglas de selección desde nombres o descripciones.
- El resumen de salsas aparece con la cotización válida; no se simulan cantidades mientras faltan selecciones.
- La referencia ilustra un botón de compra activo con bloques pendientes. La aplicación mantiene el botón deshabilitado hasta completar y validar la configuración.
- Los productos fijos pueden tener salsas adicionales y remoción de bases ya autorizadas por sus reglas. Se conservaron esos controles, aunque no aparezcan en la ilustración.
- El modal real puede necesitar más alto/scroll para seis bloques, ingredientes adicionales o textos auténticos. Se mantiene el reparto de columnas y el footer; no se ocultan datos para forzar la proporción de una imagen ilustrativa de tres rolls.
- El carrito continúa como página real y usa los mismos endpoints POST. No se añadió un drawer o checkout paralelo solo por imitar la ilustración.
- En móvil, como en el panel 4 de reference-02, el hero se reduce al título y los filtros ocupan una sola fila. La imagen uniforme de las cards pasa a 16:10 para que nombre, metadata, precio y CTA de la primera card sean visibles antes; en desktop se conserva 4:3. El banner y el texto completo del hero siguen visibles en tablet/escritorio.

## Causa del área blanca y verificación de estabilidad

### Hallazgo histórico comprobado en la iteración anterior

El evento `change` guardaba `pendingAdvance`. Al completar un bloque, `navigate()` cerraba el anterior, movía el foco y ejecutaba `scrollIntoView({behavior:'instant'})`. Después de la última cotización, si no había otro bloque pendiente, se ejecutaba `block.open = false`: los seis acordeones quedaban cerrados dentro de un panel de altura fija. La medición histórica fue **cero bloques abiertos**, scroll de panel de 535 px y precio $25.990 confirmado.

Además había dos responsabilidades de scroll: body con `overflow:hidden` y columna configuradora con `overflow-y:auto`. Los cambios de alto, foco y scroll encadenado producían pérdida de contexto y superficie vacía. No se reemplazaba el HTML exterior completo en cada cotización.

### Estado encontrado y mantenido en esta iteración

Se intentó reproducir de nuevo al comenzar sobre la aplicación real; no reapareció con la corrección anterior ya instalada. No se presenta ese hallazgo histórico como un bug nuevo encontrado hoy. Se mantuvo la eliminación del avance/cierre automático y se volvió a probar 60 piezas con múltiples cambios.

La estructura sigue siendo:

```text
modal-panel: flex column; max-height basado en 100dvh
  header: flex 0 0 auto
  body: flex 1 1 auto; min-height 0; overflow-y auto
    layout desktop 33% / 67%
  footer: flex 0 0 auto
```

Solo el body realiza scroll. `overflow:hidden` en el panel recorta sus esquinas; no se usa para esconder el área de configuración. La columna derecha no tiene scroll independiente.

No se cambian el foco, el bloque abierto ni el nodo desplazable al cotizar. Solo una acción explícita en navegación de bloques puede desplazar el body interno. Se conserva el último precio mientras aparece «Actualizando precio…».

El debounce, AbortController y contador de revisión impiden que respuestas antiguas sobrescriban selecciones recientes. Cerrar cancela/invalida peticiones; reabrir no añade listeners. Se limpia también un debounce pendiente cuando comienza una cotización explícita.

Las pestañas mantienen el mismo fieldset y los mismos IDs. Cuentan con `role=tablist/tab/tabpanel`, controles asociados, `aria-selected`, tabindex alternante y teclas Izquierda/Derecha/Home/End. Cambiar pestaña conserva la selección y no recalcula precios por sí solo.

Se conservan Bootstrap focus trap, Escape, backdrop y compensación de scrollbar. El scroll de documento se bloquea y restaura; el foco vuelve al disparador con `preventScroll`. Las selecciones usan borde, fondo y check; los límites accesibles proceden del backend.

## Responsive y rendimiento

Se comprueban 1920×1080, 1440×900, 1366×768, 1024×768, 768×1024, 430×932, 390×844 y 360×800. Grid: 4 columnas desde 1200, 3 desde 992, 2 desde 576, 1 por debajo. En móvil el modal ocupa 100dvh y el footer permanece dentro del flex.

La página sigue paginada a 12 y el detalle se descarga al abrir. Para el resumen «rolls de N» se añadieron dos agregados SQL, sin cargar opciones de bloques en las cards. Categorías/conteos conservan `whereHas`/`withCount`. El carrito obtiene rutas de imagen mediante una sola consulta para todos los IDs, sin modificar nombres, importes ni configuración almacenados en sesión.

Los botones −/+ envían cantidades a la misma acción `cart.update`, con CSRF y límites del servidor. No existe cálculo de precios en esos controles. Se eliminó el marcado duplicado desktop/móvil y sus envíos automáticos concurrentes al cambiar/perder foco.

## Validación

`tests/Feature/MenuUiRedesignTest.php` tiene ahora diez casos: categorías públicas, resumen breve, unidades y sabores, producto fijo, paginator español, búsqueda/orden, consultas acotadas, cotizaciones repetidas, banner/fallback locales, pestañas con IDs únicos y carrito con formulario único por línea. Los tres últimos casos se incorporaron en esta iteración.

`scripts/test-menu-ui.mjs` controla Chrome real mediante CDP y un contexto aislado. No crea pedidos ni contacta pasarelas externas. Requiere Node 22+ y la aplicación/Chrome locales:

```powershell
node scripts/test-menu-ui.mjs
```

Por defecto usa app `http://127.0.0.1:8000` y Chrome `http://127.0.0.1:9223`. Variables de proceso opcionales: `AKILA_URL`, `AKILA_CDP`, `AKILA_CAPTURE_DIR`. No se edita `.env`. Las capturas se guardan por defecto en TEMP; las de revisión se conservaron en `docs/ui-reference/verification/`.

| Recorrido Chrome | Comprobaciones |
|---|---|
| 20 Piezas a Elección | 2 bloques, $10.000, cotización y carrito |
| 30 Piezas a Elección | 3 bloques, $14.900, selección y resumen; captura desktop/móvil |
| 60 Piezas a Elección | 6 bloques, $25.990, cambios repetidos, mismo formulario/body, foco y scroll conservados, pestañas por teclado |
| 5 Hand Rolls | 5 unidades, $23.000 con cinco segundos ingredientes; quitar/agregar uno cambia $22.000/$23.000 correctamente |
| Muchupleto | Grupo a nivel producto, $6.500, sin bloques artificiales |
| AkilaBurger | Grupo a nivel producto, $7.500, sin bloques artificiales |
| Arrollados | 8 unidades, uno o ambos sabores a $5.000; cantidad 2 cobra $10.000, volver a 1 conserva Mixto |
| Noche de Sushi | $17.990, composición/preparación, sin elecciones obligatorias de bloques |

Stress adicional: 21 cambios/cotizaciones, fallo de red simulado, respuesta antigua retrasada que ignora cancelación, veinte aperturas/cierres con una sola cotización por apertura, Escape, foco y posición de página. Se inspeccionan DOM y geometría; nunca debe desaparecer la configuración ni aparecer scroll horizontal.

La comprobación usa Chrome controlado y revisión visual de sus capturas; no se afirma una sesión humana adicional de aceptación.

Resultados finales:

- **301 pruebas aprobadas, 2941 aserciones, cero fallos** en `php artisan test` (415,82 s). Incluye seguridad, arquitectura, Batch01–05, integración comercial, auditoría final y UI.
- Chrome: ocho recorridos completos, 21 cambios, veinte aperturas/cierres, ocho tamaños, pestañas por teclado, fallo simulado y respuesta antigua. Cero excepciones JavaScript.
- Revisión adicional del carrito compacto en siete tamaños: sin overflow horizontal; los botones mantienen las selecciones y los importes del servidor.
- Build Vite aprobado; Pint aprobado en los cuatro archivos PHP de esta iteración; sintaxis JavaScript aprobada; `git diff --check` limpio; `git status` revisado.
- Hashes comerciales e históricos antes/después **idénticos**. Se mantienen 37 productos, 35 activos, 7 categorías, 23 ingredientes, 26 promociones, 130 bloques, 29 grupos, 115 opciones y 479 reglas.

## Archivos de esta iteración

- `app/Http/Controllers/ProductoController.php`: agregados de cantidad para las cards.
- `app/Http/Controllers/CartController.php`: consulta única de miniaturas.
- `app/View/MenuCardSummary.php`: metadata confirmada y etiquetas de composición.
- `resources/views/layouts/app.blade.php`: scope de página para espaciados/header.
- `resources/views/menu/index.blade.php`: banner y estructura de categorías/resultados.
- `resources/views/components/store/{product-card,catalog-image,icon}.blade.php`.
- `resources/views/components/{menu-product-modal,menu-option-group}.blade.php`.
- `resources/views/cart/index.blade.php`.
- `resources/css/custom.css`: estilos de referencia sin acumular las variantes del rediseño anterior.
- `resources/js/modules/menuPersonalization.js`: presentación de tabs, estado completado y actualización de ambos desgloses.
- `tests/Feature/MenuUiRedesignTest.php`, `scripts/test-menu-ui.mjs`, este documento, capturas/comparador y manifest/assets de Vite.

## Datos protegidos y pendientes

Se preservan los cambios previos del workspace. Esta iteración no modifica productos, precios, ingredientes, límites, reglas, seeders, snapshots, checkout ni Mercado Pago. No ejecuta seeders en la base persistente. Los tests usan SQLite en memoria.

Pendiente: fotografías específicas reales de productos/ingredientes; no se usan los recortes de las referencias como sustituto. Los pendientes comerciales de la auditoría anterior continúan sin interpretación adicional. No se hizo commit ni se modificó `.env`.
