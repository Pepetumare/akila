# Importación progresiva del menú Sushi Akila

Los lotes se ejecutan por orden de fotos. Este documento no autoriza cargar fichas de lotes posteriores. El menú se consolida por slug: una ficha nueva actualiza la misma identidad, no crea otra presentación por un cambio de nombre o precio.

# Batch 01 — Fotos 1 a 10

## Alcance y fuentes

Implementación del encargo final de Fase 1/5. Fuente: transcripción explícita de las fotos lógicas 1–10 suministrada por el usuario en ese encargo. No se afirma haber inspeccionado imágenes originales que no están adjuntas/disponibles en el repositorio. Los fragmentos anteriores de otras fichas no se utilizaron como datos de este lote.

No se cargan fotos 11–42, otras categorías, salsas, extras ni productos futuros. La foto 2 contiene condiciones, **no un producto**. La base tiene doce productos provenientes de este lote; registros ajenos existentes se conservan y no forman parte de ese conteo.

## Productos consolidados

| Foto de origen/autoridad | Nombre | Slug estable | Categoría | Precio CLP | Piezas | Bloques de 10 |
|---|---|---|---|---:|---:|---:|
| 1 | Goloso | goloso | Promociones | 10000.00 | 20 | 2 |
| 1, confirmada por 4 | Proteínas | proteinas | Promociones | 14990.00 | 30 | 3 |
| 1 | Noche de Sushi | noche-de-sushi | Promociones | 17990.00 | 40 | 4 |
| 1 | Mi Favorita | mi-favorita | Promociones | 18990.00 | 50 | 5 |
| 1 | Promo Hot | promo-hot | Promociones | **20990.00** | 60 | 6 |
| 1, resuelta por 10 | Lo Nuevo | lo-nuevo | Promociones | 25990.00 | 70 | 7 |
| 3 | 30 Piezas a Elección | 30-piezas-a-eleccion | Promociones a elección | 14900.00 | 30 | 3 |
| 5 | Costanera | costanera | Promociones | 14900.00 | 30 | 3 |
| 6 | Monumento | monumento | Promociones | 14900.00 | 30 | 3 |
| 7 | Las Ruinas | las-ruinas | Promociones | 14900.00 | 30 | 3 |
| 8 | La Playa | la-playa | Promociones | 14900.00 | 30 | 3 |
| 9 | Código Verde | codigo-verde | Promociones | 14900.00 | 30 | 3 |

Categorías activas, con orden 1 y 2: **Promociones**, **Promociones a elección**. No se crean Hand Rolls, Nikkei, Especialidades, Gohan o Acompañamientos en este lote.

Cada producto está enlazado uno a uno con Promocion como metadata comercial complementaria, con precio coherente y cantidad_rolls=piezas/10. No se generan productos internos de diez piezas para simular los bloques ni un segundo catálogo vendible. promocion_handroll no se rellena con referencias ficticias a componentes que no existen como productos en estas fotos.

## Ingredientes normalizados

Solo catorce ingredientes:

- Proteínas: Pollo, Kanikama, Salmón, Atún, Camarón.
- Vegetales: Choclo, Palmito, Palta, Champiñón, Morrón.
- Base: Queso crema, Cebollín.
- Toppings/uso como envoltura: Sésamo, Ciboulette.

Queso crema y Palta no se duplican al usarlos como envoltura. Su clasificación física principal permanece base/vegetal; Opcion referencia la misma fila desde el grupo de envoltura. **Frito no es Ingrediente**, sino opción sin ingrediente asociado. Frío y Envuelto tampoco se agregan como ingredientes.

MenuIdentity compara nombres mediante normalización de tildes/capitalización/espacios; reconoce palmitos→palmito, champiñones→champiñón y camarones→camarón. Reutiliza IDs existentes y normaliza su nombre canónico. Si ya hay dos filas equivalentes, detiene y revierte el lote completo: no borra ni fusiona automáticamente referencias ambiguas. Se preserva ingredientes.costo existente; no se inventan costos/gramajes.

## Composición y bloques fijos

Todos los productos llevan Queso crema y Cebollín en ingrediente_producto, con cantidad/unidad físicas **NULL**, reemplazable=false y metadata.removible=true. obligatorio=false expresa que la base puede quitarse. Los rellenos establecidos llevan cantidades comerciales conocidas en metadata.piezas_relleno y el contador legacy cantidad_permitida en rolls; eso no es una receta física. Las cantidades de envoltura se registran por separado como piezas_envoltura, sin volver a contar Palta como relleno cuando solo es envoltura.

Bloques fijos de fichas específicas:

| Producto | Bloque 1 | Bloque 2 | Bloque 3 |
|---|---|---|---|
| Costanera | Pollo, Frito | Camarón, Palta | Palta, Queso crema |
| Monumento | Pollo, Frito | Champiñón, Palta | Palta, Queso crema |
| Las Ruinas | Pollo, Frito | Kanikama, Palta | Palta, Queso crema |
| La Playa | Camarón, Frito | Kanikama, Queso crema | Salmón, Palta |
| Código Verde | Champiñón, Frito | Choclo, Queso crema | Palmito, Palta |

En cada celda se indica relleno y preparación/envoltura, diez piezas. Se representan mediante relaciones bloque→grupo→opción, usando grupos reutilizables **de una sola opción fija**. No se guarda toda la composición en un JSON. Goloso tiene dos bloques con Pollo y Palta simultáneos y preparación Frito. Proteínas tiene tres bloques de Pollo/Frito. Promo Hot tiene seis bloques con la composición indicada y Frito en todos.

Para Noche de Sushi, Mi Favorita y Lo Nuevo, los bloques conservan cantidades conocidas de rellenos, pero **no tienen grupo de envoltura/preparación asignado**. Su orden es una identidad estable de importación, no una afirmación del orden físico ni de cuál roll es frito. No se infiere la distribución faltante.

## Grupos y opciones

Se crean/reutilizan 16 grupos y 36 opciones:

1. Proteína: cinco opciones generales de la foto 1.
2. Vegetal: cinco opciones generales de la foto 1.
3. Envoltura: Queso crema, Palta, Sésamo, Ciboulette y Frito.
4. Relleno — 30 Piezas a Elección: **Pollo, Camarón, Kanikama, Salmón, Palmito, Champiñón, Choclo, Palta**.
5. Preparación: Envuelto y Frío, para sujetos de reglas globales, sin asignarlos como elecciones inventadas.
6. Ocho grupos de relleno fijo reutilizables: Pollo, Palta, Camarón, Kanikama, Choclo, Champiñón, Salmón, Palmito.
7. Tres grupos de envoltura fija: Frito, Palta y Queso crema.

La existencia de una opción general no la hace disponible en todos los productos. El relleno de la foto 3 usa un **solo grupo single, min=1/max=1 por bloque** para representar proteína **O** vegetal. No se asignan dos grupos obligatorios que exijan ambos. Atún y Morrón no aparecen en ese grupo ni en las reglas de cambio/repetición de ese producto.

El grupo Envoltura se ofrece como disponibilidad opcional por bloque a elección, min=0/max=1: no se inventa una envoltura inicial o elección obligatoria ausente de la transcripción. Los grupos se asignan directamente a cada bloque, sin duplicar la misma obligación a nivel producto; grupo_opcion_producto queda sin filas de este lote. Las relaciones a nivel bloque son las autoritativas para esta configuración.

Todos los recargos globales de opciones están en cero: los $1000 son **condicionales a cambios**, no un costo automático de seleccionar cualquier ingrediente por primera vez. Se guardan en ReglaPromocion como DECIMAL, con sujeto y condición.

## Reglas almacenadas y condiciones de la foto 2

215 reglas totales, sin ejecutarlas en ProductPricingService:

- **178 reglas de recargo** por cambio de relleno o envoltura. Importe 1000.00, condición `modificacion_relleno`/`modificacion_envoltura`, unidad roll. Las específicas de fotos 3 y 10 llevan esa procedencia; las establecidas usan las condiciones de foto 2. Cambiar un relleno y una envoltura no se convierte aquí en una fórmula nueva: la futura evaluación deberá definir los eventos cobrables.
- **18 límites de repetición**: ocho para la foto 3, diez para las selecciones/cambios de Lo Nuevo (foto 10). Salmón máximo 1; los demás máximo 2. Ámbito promoción completa. No se impone max=2 sobre recetas fijas como las tres repeticiones de Pollo de Proteínas; no se confunde composición establecida con elección del cliente.
- **19 cantidades globales de preparación**, tipo `cantidad_incluida`, sujeto Opcion, metadata.uso=`preparacion_global` y unidad pieza. Expresan cantidades exactas incluidas de esa preparación; no se etiquetan falsamente como un mero mínimo de piezas.

Preparaciones globales:

| Producto | Cantidades confirmadas |
|---|---|
| Goloso | 20 fritas |
| Proteínas | 30 fritas |
| Noche de Sushi | 20 envueltas, 20 fritas |
| Mi Favorita | 30 envueltas, 20 fritas |
| Promo Hot | 60 fritas |
| Lo Nuevo | **30 frías, 40 fritas** |
| Costanera, Monumento, Las Ruinas, La Playa, Código Verde | 10 fritas, 20 envueltas, además de sus bloques exactos |

Para frías no se inventa sinónimo “envueltas”: Lo Nuevo conserva una regla piezas-frio, no un dato envuelto deducido. productos.rolls_total y rolls_fritos guardan los múltiplos conocidos; rolls_envueltos solo se completa con cantidades explícitamente envueltas. Su cero por defecto no sustituye una regla positiva de preparación ni afirma otra distribución.

Las condiciones adicionales de la foto 2 se conservan documentalmente:

- Más de un ingrediente por roll: +$1000 por ingrediente adicional.
- Superar el límite general de repetición: +$1000 adicional.
- Base Queso crema/Cebollín no reemplazable, pero removible.

**No se activan reglas ejecutables falsas** para “ingrediente adicional” o “exceder repetición”: falta resolver su coexistencia con el single de la ficha, los máximos específicos y las excepciones ambiguas. La arquitectura puede almacenar un recargo por sujeto y metadata condicional, pero todavía no tiene semántica de evaluación de esas condiciones. No se usa un recargo incondicional que altere el precio base, ni se amplía max_selections sin autorización. Este punto requiere la interpretación humana indicada abajo antes del evaluador comercial.

No se cargan salsas ni cantidades incluidas de salsas: no están en las fotos 1–10 de este encargo.

## Contradicciones y valores temporales

| Dato | Alternativas/fuentes | Valor utilizado en Batch01 | Razón | ¿Confirmación humana? |
|---|---|---|---|---|
| Promo Hot | Foto 1: 20990; se advierte que habrá ficha posterior | **20990.00**, slug promo-hot | Reproducción progresiva de estas fotos, sin anticipar lotes futuros | No para este lote; susceptible de actualización posterior |
| Nombre Lo Nuevo | Foto 1: “Nuevo cortes”; foto 10: “Lo Nuevo” | Lo Nuevo, slug lo-nuevo | Ficha específica dentro del mismo lote | No; alias documentado, no producto adicional |
| Preparación Lo Nuevo | Línea inconsistente en resumen; foto 10: 30 frías/40 fritas | 30/40, sin mapa por bloque | Prioridad de ficha específica | No para totales; distribución queda desconocida |
| Repeticiones | Foto 2: máximo 3; fichas 3/10: máximo 2, salmón 1 | Máximo 2 y salmón 1 en los ámbitos específicos | Ficha individual tiene prioridad; no se crea max=3 global | Sí para productos/condiciones sin ficha clara |
| Excepciones “no cumplen” | Salmón, palta, camarón, atún en foto 2 | Sin exclusión, recargo especial o permiso inventado | Significado insuficiente para determinar comportamiento | **Sí** |
| Extra ingrediente/exceso de repetición | +1000 en condiciones; ficha 3 exige uno y límites propios | Texto preservado, sin habilitar extras o excepciones nuevas | Falta interpretación del conflicto y evaluador | **Sí** |
| Preparación individual | Noche/Mi Favorita/Lo Nuevo solo dan totales | Totales globales; grupos de envoltura ausentes en sus bloques | No inventar asociaciones relleno→preparación | Sí si se necesita distribución exacta |
| Fotos/gramajes | No hay receta operacional confirmada | cantidad/unidad NULL | Las piezas son cantidad comercial, no gramos | Sí en futura fase de cocina |

No se importaron precios, nombres ni productos de mensajes previos fuera del alcance final. En particular **no se adelantó otra tarifa de Promo Hot**. Fotos posteriores actualizarán el producto con slug promo-hot, no crearán “Promo Hot 2”.

## Idempotencia, alcance de escritura y orden futuro

- Seeder de lote: `database/seeders/SushiAkilaMenuBatch01Seeder.php`.
- Principal: `database/seeders/SushiAkilaMenuSeeder.php`, hoy solo llama Batch01. Se añadirán Batch02…05 cuando existan, siempre en orden; no hay referencias a clases futuras inexistentes.
- Datos de este lote: `database/seeders/SushiAkila/Batch01Data.php`.
- Identidades reutilizables: `database/seeders/SushiAkila/MenuIdentity.php`.
- Operación completa en DB::transaction. Grupos/opciones/reglas/bloques y promociones usan updateOrCreate; asignaciones/composición usan syncWithoutDetaching. No se borran asociaciones o datos ajenos.
- Claves naturales: producto.slug; categoría.slug; ingrediente.nombre normalizado; grupo.slug; opción(grupo,slug); bloque(producto,orden); regla(producto,codigo); promoción.producto_id. Nunca IDs numéricos hardcodeados.
- El helper preserva el slug de importación frente al observer legacy de Producto que lo regenera al cambiar nombre. “Nuevo cortes” preexistente se reutiliza si es el candidato inequívoco de lo-nuevo.
- El helper no cambia imagen del producto, costo del ingrediente, usuarios, pedidos, snapshots, PDFs ni relaciones ajenas que no figuran en sus claves de escritura.
- Repetir Batch01 actualiza sus valores de foto 1–10 y no crea duplicados. **Ejecutar Batch01 solo después de un lote posterior puede restaurar valores anteriores que le pertenecen**; para reconstruir la carta vigente se debe ejecutar el principal completo, con lotes de menor a mayor autoridad/orden. Las actualizaciones sucesivas conservan IDs y relaciones históricas.
- No se cambió DatabaseSeeder heredado: para evitar su producto de demostración se utiliza explícitamente el seeder de menú indicado.

## Compatibilidad y limitación comercial actual

No se modifican frontend, ProductPricingService, Mercado Pago, modelos, rutas o migraciones. Las tablas/reglas se cargan como configuración de una modalidad futura. El cotizador legacy **no evalúa estas reglas ni las selecciones por bloque**. La carga no constituye prueba de que esos pedidos ya puedan personalizarse correctamente en el carrito actual.

Las categorías/productos se guardan activos conforme al encargo. Las consultas públicas actuales no usan activo como filtro ni tienen una bandera de publicación de lotes: cargar en la base usada por el sitio puede hacer que los productos aparezcan en el catálogo legacy. No se implementó un flujo de venta nuevo ni una falsa validación. Antes de una venta real de la modalidad nueva debe integrarse su evaluador y selección por bloques. No presentar la importación como activación comercial completa.

## Ejecución y verificación

Antes se comprobó migrate:status: 44 aplicadas, ninguna pendiente. No se ejecutan migraciones ni seeders destructivos. Comando del lote, dos veces:

```text
php artisan db:seed --class=SushiAkilaMenuBatch01Seeder
```

Ambas ejecuciones sobre la conexión mysql/MariaDB local finalizaron con código 0. Conteos de lectura reales:

| Tabla | Antes | Primera ejecución | Segunda ejecución |
|---|---:|---:|---:|
| categorias | 0 | 2 | 2 |
| ingredientes | 0 | 14 | 14 |
| productos | 0 | 12 | 12 |
| promociones | 0 | 12 | 12 |
| ingrediente_producto | 0 | 61 | 61 |
| grupos_opciones | 0 | 16 | 16 |
| opciones | 0 | 36 | 36 |
| grupo_opcion_producto | 0 | 0 | 0 |
| bloques_promocion | 0 | 45 | 45 |
| bloque_grupo_opcion | 0 | 76 | 76 |
| reglas_promocion | 0 | 215 | 215 |
| users | 1 | 1 | 1 |
| orders | 0 | 0 | 0 |
| order_items | 0 | 0 | 0 |

También se compararon todos los IDs ordenados de esas tablas, no solo sus conteos: el hash SHA-256 del conjunto fue idéntico después de ambas ejecuciones (`852e645a92933791b3c9ede09e1abe7c46630e0b6ff0883d448c7a8323cdd517`). Los timestamps de actualización de configuración pueden cambiar al reejecutar; idempotencia aquí conserva identidades y valores declarados, sin filas nuevas.

La lectura final de productos confirmó los doce precios DECIMAL esperados. Lo Nuevo conserva reglas piezas-frio=30.00 y piezas-frito=40.00, foto 10 y distribucion_por_bloque=false.

Las pruebas específicas usan SQLite en memoria, sin fixtures sobre la base MariaDB de la aplicación. No se cambian .env, inventario ni delivery, y no se hace commit.

Validaciones finales:

| Comprobación | Resultado |
|---|---|
| CommerceSecurityTest | 59 aprobadas, 183 aserciones; archivo intacto |
| MenuArchitectureTest | 33 aprobadas, 98 aserciones; archivo intacto |
| SushiAkilaMenuBatch01Test | 15 aprobadas, 241 aserciones |
| Suite completa | **135 aprobadas, 616 aserciones, cero fallos** |
| migrate:status antes/después | 44 aplicadas, ninguna pendiente; sin migraciones nuevas |
| npm run build | Correcto, 65 módulos; mismos assets resultantes |
| git diff --check | Correcto |

Solo se agregaron los dos seeders, los dos archivos de datos/identidad, tests/Feature/SushiAkilaMenuBatch01Test.php y este documento. Los cambios de fases previas ya presentes en Git se conservaron. No se modificaron pruebas existentes para obtener estos resultados.

# Batch 02 — Fotos 11 a 20

## Alcance y prioridad

Fuente: transcripción explícita de las fotos lógicas 11–20 del encargo de Fase 2/5. No se afirma haber inspeccionado imágenes originales no adjuntas. No se cargan fotos 21–42, categorías adicionales, ingredientes nuevos, inventario ni gramajes. Los datos y decisiones de Batch01 anteriores se conservan documentalmente como estado de esa fase.

El seeder principal ahora ejecuta Batch01 y después Batch02, dentro de una transacción, sin clases de lotes futuros. Una ejecución completa deja la ficha específica como autoridad, aunque Batch01 haya restaurado antes su precio de resumen. No se debe ejecutar Batch01 solo para reconstruir el estado vigente.

Se extrajo el mecanismo existente de Batch01 a `SushiAkila/MenuBatchImporter.php`; ambos lotes lo reutilizan. Se mantienen claves por slug, ingrediente normalizado, grupo/slug de opción, producto/orden de bloque, producto/código de regla y promoción/producto. `updateOrCreate`, `syncWithoutDetaching` y transacciones preservan IDs y asociaciones ajenas. No se borran registros.

## Productos nuevos y actualizados

| Foto | Producto / slug | Operación | Precio CLP | Piezas | Bloques de 10 | Preparación global confirmada |
|---|---|---|---:|---:|---:|---|
| 11 | Promo Hot / promo-hot | Actualizar | **19990.00** | 60 | 6 fijos | 60 fritas; todos los bloques fritos |
| 12 | El Ciclón / el-ciclon | Nuevo | 24990.00 | 60 | 6 fijos | 30 fritas; resto mixto/no frito sin distribución especificada |
| 13 | 60 Piezas a Elección / 60-piezas-a-eleccion | Nuevo | 25990.00 | 60 | 6 editables | 30 fritas, sin asignación a rolls concretos |
| 14 | 50 Piezas a Elección / 50-piezas-a-eleccion | Nuevo | 20990.00 | 50 | 5 editables | 20 fritas, sin asignación a rolls concretos |
| 15 | Noche de Sushi / noche-de-sushi | Actualizar | 17990.00 | 40 | 4 fijos | 20 fritas, sin asignación a rolls concretos |
| 16 | Tu Media Naranja / tu-media-naranja | Nuevo | 17990.00 | 40 | 4 fijos | 20 fritas / 20 envueltas; distribución exacta en composición |
| 17 | 40 Piezas a Elección / 40-piezas-a-eleccion | Nuevo | 17900.00 | 40 | 4 editables | 20 fritas, sin asignación a rolls concretos |
| 18 | El Emperador / el-emperador | Nuevo | 49990.00 | 150 | 15 fijos | 60 frías / 90 fritas |
| 19 | Para la Familia / para-la-familia | Nuevo | 36990.00 | 100 | 10 fijos | 40 frías / 60 fritas |
| 20 | De las que no Sobran / de-las-que-no-sobran | Nuevo | 31990.00 | 90 | 9 fijos | 40 frías / 50 fritas |

**Promo Hot: 20990.00 → 19990.00**, por prioridad de ficha específica 11 sobre resumen 1. Conserva slug, ID y composición. La promoción complementaria se actualiza al mismo precio. La transcripción de la ficha 11 coincide con su composición previa: 20 pollo, 10 camarón, 10 kanikama, 10 choclo y 10 champiñón, todo frito. Sus límites de repetición se guardan para elecciones/cambios; no invalidan su receta fija.

## Composición establecida

Todas llevan base Queso crema y Cebollín, removible/no reemplazable conforme a las condiciones previas. Cantidad y unidad física continúan NULL.

| Producto | Piezas por ingrediente de relleno |
|---|---|
| El Ciclón | Pollo 10; Camarón 10; Kanikama 10; Choclo 10; Champiñón 10; Palmito 10 = 60 |
| Noche de Sushi | Pollo 20; Camarón 10; Palta 10 = 40 |
| Tu Media Naranja | Roll 1 Pollo/Frito; Roll 2 Camarón/Palta; Roll 3 Palta/Queso crema; Roll 4 Kanikama/Frito; 10 piezas cada uno |
| El Emperador | Pollo 30; Camarón 20; Kanikama 20; Choclo 20; Champiñón 20; Salmón 10; Palmito 10; Palta 20 = 150 |
| Para la Familia | Pollo 20; Camarón 10; Kanikama 20; Choclo 10; Champiñón 10; Salmón 10; Palmito 10; Palta 10 = 100 |
| De las que no Sobran | Pollo 20; Camarón 10; Kanikama 20; Choclo 10; Champiñón 10; Salmón 10; Palmito 10 = 90 |

Los bloques fijos usan grupos singleton existentes. Solo Promo Hot y Tu Media Naranja asignan preparación/envoltura individual porque la ficha la declara de forma inequívoca. El orden de los demás bloques representa identidad de importación, no una distribución física de preparaciones.

## Elecciones, envolturas y reglas

40, 50 y 60 piezas comparten `relleno-promociones-a-eleccion`: Pollo, Kanikama, Camarón, Salmón, Choclo, Palmito, Champiñón y Palta. Cada bloque exige exactamente una opción entre proteína o vegetal, min=max=1. No se agregan Atún ni Morrón desde la carta general.

50 y 60 piezas comparten `envoltura-promociones-mixtas`, con Queso crema, Palta y Sésamo, opcional min=0/max=1. No se asigna Frito como elección individual para simular las cantidades globales. A 40 piezas no se le agregan envolturas seleccionables: la transcripción 17 solo confirma el total de fritas. Sus cuatro rellenos sí son configurables inmediatamente.

Envolturas declaradas sin distribución individual en promociones fijas se conservan en descripción y metadata de las reglas globales, sin abrir selectores ficticios: El Ciclón (queso crema, palta, sésamo), Noche (queso crema, palta), Emperador/Familia/De las que no Sobran (queso crema, palta, sésamo, ciboulette).

Se reutiliza `cantidad_incluida`, sujeto Opcion, unidad pieza y `uso=preparacion_global`, con `distribucion_por_bloque=false` cuando se desconoce. No se interpreta frío como envuelto ni se crean ingredientes «Frito», «30 piezas fritas» o «pollo frito». No se identifica arbitrariamente qué roll del cliente saldrá frito; la cantidad global forma parte de su detalle y snapshot.

Los tres productos personalizables tienen ocho reglas `max_repeticiones`: máximo 2 para rellenos y máximo 1 para salmón. Los recargos de cambio siguen siendo 1000.00 condicionados a modificación; una selección inicial no se cobra como sustitución. No se activan las ambigüedades pendientes de Foto 2.

Se amplía mínimamente la clasificación del evaluador existente: todo grupo con slug `envoltura-*` es envoltura, además del grupo `envoltura` original. Esto evita contar palta elegida como envoltura dentro del máximo de rellenos. Una prueba comercial con seis envolturas de palta y solo dos rellenos de palta cubre el caso. No cambia ProductPricingService, Mercado Pago ni frontend.

## Discrepancias y decisiones

| Dato | Fuentes | Decisión en Batch02 | Pendiente |
|---|---|---|---|
| Precio Promo Hot | Resumen 1: 20990; ficha 11: 19990 | Ficha 11, mismo producto a 19990 | Ninguno |
| Noche, 20 envueltas | Resumen 1 las indicaba; ficha 15 solo confirma 20 fritas y envolturas disponibles | Regla anterior `piezas-envuelto` conservada **inactiva**, con `superada_por=batch02` y foto de autoridad 15; no se borra ni se infiere otra distribución | Confirmar distribución física si se necesita |
| Tu Media Naranja | Lateral incompleto frente a composición principal | Prioridad de composición principal, cuatro bloques exactos | Ninguno para lo transcrito |
| «30/20/90 piezas fritas» | Cantidad global en ficha | Regla global, no ingrediente ni opción individual ficticia | Distribución entre rolls no declarada |
| El Ciclón, otras 30 piezas | «Mixtas», solo 30 fritas explícitas | No se inventa una regla de 30 frías ni su reparto | Detalle operativo si se requiere |
| Envolturas de 40 piezas | No enumeradas en transcripción 17 | Sin elecciones de envoltura añadidas | Confirmar antes de habilitarlas |

La regla inactiva de Noche se reactiva transitoriamente si se ejecuta Batch01; Batch02 vuelve a desactivarla dentro de la transacción del principal. Los snapshots históricos no se reescriben ni dependen de esa activación actual.

## Pruebas y archivos

Nuevos: `database/seeders/SushiAkilaMenuBatch02Seeder.php`, `database/seeders/SushiAkila/Batch02Data.php`, `database/seeders/SushiAkila/MenuBatchImporter.php` y `tests/Feature/SushiAkilaMenuBatch02Test.php`.

Modificados: wrappers Batch01/principal, `PromotionRuleEvaluator.php`, este documento y una prueba de alcance de Batch01. Esa prueba llamaba al principal y esperaba únicamente doce productos y el precio de resumen; ahora llama explícitamente a Batch01 con las mismas aserciones. La precedencia y estado final del principal se prueban por separado en Batch02, incluyendo reejecución tras restaurar temporalmente el precio anterior. No se altera CommerceSecurityTest, MenuArchitectureTest ni la integración comercial existente.

Las pruebas nuevas cubren precios, piezas, identidades, estado completo sin timestamps tras reejecución, preservación histórica y datos ajenos, preparaciones, límites, grupos de opciones, ausencia de fotos posteriores, configuraciones válidas/inválidas, IDs ajenos, snapshots y checkout de producto fijo y personalizable. SQLite en memoria; nunca fixtures sobre la base de la aplicación.

Los recargos generales de promociones establecidas conservan como fuente la foto 2 cuando la ficha nueva no declara esa condición; las fichas 11/13/14/17 identifican explícitamente su propia procedencia de reglas. El campo foto de los bloques/composición sigue identificando la ficha individual del producto.

## Ejecuciones reales e idempotencia

Se verificó `php artisan migrate:status` antes de importar: 44 aplicadas, ninguna pendiente. Se ejecutó el principal completo repetidamente, con un par final de comprobación después de ajustar la procedencia documental de las reglas:

```text
php artisan db:seed --class=SushiAkilaMenuSeeder
php artisan db:seed --class=SushiAkilaMenuSeeder
```

Ambas ejecuciones del par final terminaron con código 0, Batch01 seguido de Batch02. Sobre MariaDB local se observaron:

| Tabla | Antes de Batch02 | Primera ejecución final | Segunda ejecución final |
|---|---:|---:|---:|
| categorias | 2 | 2 | 2 |
| ingredientes | 14 | 14 | 14 |
| productos | 12 | 20 | 20 |
| promociones | 12 | 20 | 20 |
| ingrediente_producto | 61 | 110 | 110 |
| grupos_opciones | 16 | 18 | 18 |
| opciones | 36 | 47 | 47 |
| grupo_opcion_producto | 0 | 0 | 0 |
| bloques_promocion | 45 | 104 | 104 |
| bloque_grupo_opcion | 76 | 150 | 150 |
| reglas_promocion | 215 | 366 | 366 |
| users | 1 | 1 | 1 |
| orders | 1 | 1 | 1 |
| order_items | 1 | 1 | 1 |

De las 366 reglas, 365 quedan activas; la regla anterior de 20 envueltas de Noche permanece conservada/inactiva. No hay slugs, parejas de pivotes, códigos de regla ni posiciones de bloque duplicados. Se conservan todos los IDs previos, incluidos los doce productos originales.

Promo Hot: **ID 5 → 5**, precio **20990.00 → 19990.00**. Noche de Sushi: **ID 3 → 3**, precio 17990.00. Sus bloques mantienen sus IDs; se actualiza su procedencia a fotos 11/15, respectivamente.

La comparación del par final comprobó igualdad de conteos, de todos los IDs y de todos los valores comerciales excluyendo created_at/updated_at:

- SHA-256 de IDs: `0c3dd5d68b9084efa424f7bfe4d74a4772b23d3c38d58d0e34471b4bfdc83860` en ambas ejecuciones.
- SHA-256 de valores comerciales sin timestamps: `3af63dd67ed89babbb6816e5fb1bc21d15473d0594ced429d4a579a8060c62b5` en ambas ejecuciones.
- Se compararon además los contenidos completos de users/orders/order_items antes y después mediante huella, sin imprimir datos personales: sin cambios. El pedido y la línea que ya existían antes de esta tarea se preservaron íntegramente, no solo en conteo.

## Comprobación en Chrome local

Se abrió la aplicación local con Chrome headless/CDP y se inspeccionó captura de escritorio. Sin cambiar vistas ni JavaScript:

- 40 Piezas a Elección: cuatro rolls, controles de relleno, selección Pollo/Camarón/Pollo/Palta, cotización **17900**, agregado y resumen correcto en carrito.
- Pollo/Pollo/Pollo/Palta: HTTP de validación rechazado, mensaje de máximo 2 y botón deshabilitado.
- Salmón/Salmón/Pollo/Palta: rechazado, mensaje de máximo 1 y botón deshabilitado.
- 60 Piezas a Elección: **seis** encabezados de roll, cada uno de diez piezas. En móvil 390 px no hay desbordamiento horizontal (documento 390; contenido del modal 374/374).
- Tu Media Naranja: cuatro bloques de composición exacta, cero selectores editables, cotización **17990**, agregado sin elección manual.
- El Emperador: **150 piezas**, quince bloques, cero selectores editables, cotización **49990**, agregado sin elección manual y composición visible.
- Carrito combinado de 40 piezas + Tu Media Naranja + Emperador: **85880**, tres líneas con composición.
- Promo Hot: seis bloques fijos y precio **19990**, sin el precio temporal anterior.
- Sin excepciones JavaScript registradas. No se creó ningún pedido ni se hizo pago real en esta comprobación; solo se utilizó la sesión del navegador de pruebas.

## Validaciones finales

| Comprobación | Resultado |
|---|---|
| CommerceSecurityTest | 59 aprobadas / 183 aserciones; sin cambios |
| MenuArchitectureTest | 33 / 98; sin cambios |
| SushiAkilaMenuBatch01Test | 15 / 241; ajuste de alcance del principal explicado arriba |
| SushiAkilaMenuBatch02Test | 22 / 373, incluidos casos comerciales nuevos |
| MenuCommercialIntegrationTest | 31 / 186; sin cambios |
| Suite completa | **188 aprobadas / 1175 aserciones / 0 fallos** |
| migrate:status | 44 aplicadas, ninguna pendiente; no se ejecutaron migraciones |
| route:list | 58 rutas, correcto |
| npm run build | Correcto, 66 módulos; mismos assets, sin cambios de frontend |
| Pint de PHP de esta fase | Correcto |
| git diff --check | Correcto |
| Git | Estado revisado; cambios previos conservados, sin commit |

No se modifica `.env`, ProductPricingService, snapshots, checkout o Mercado Pago. No se incorporan fotos 21–42, inventario, delivery por distancia ni gramajes. Quedan pendientes las ambigüedades comerciales y distribuciones físicas expresamente señaladas; no impiden vender las composiciones globales confirmadas.

# Batch 03 — Fotos 21 a 30

## Alcance y fuentes

Se utiliza exclusivamente la transcripción completa de fotos lógicas 21–30 del encargo de Fase 3/5. Las apariciones repetidas se consolidan por slug, sin productos adicionales. No se importan fotos 31–42 ni datos anticipados de Nikkei, AkilaBurger, Arrollados, Gohan, inventario o recetas físicas. No se afirma haber inspeccionado imágenes originales no adjuntas.

El principal ejecuta **Batch01 → Batch02 → Batch03**, dentro de su transacción existente. Batch03 reutiliza `MenuBatchImporter` y `MenuIdentity`; no crea otro importador/cotizador ni un segundo carrito. El importador admite ingredientes y definiciones de grupos adicionales, categorías explícitas, opciones a nivel producto y cantidades comerciales por bloque.

## Productos consolidados

| Fotos | Producto / slug | Operación | Categoría | Precio CLP | Cantidad y configuración |
|---|---|---|---|---:|---|
| 21 | Las Mismas de Siempre / las-mismas-de-siempre | Nuevo | Promociones | 27900.00 | 80 piezas, 8 bloques fijos de 10 |
| 22, 27 | Muchupleto / muchupleto | Nuevo, repetición confirma identidad | Especialidades | 6500.00 | 1 unidad; base fija y elección adicional a nivel producto |
| 23, 28 | Hand Roll Tradicional / hand-roll-tradicional | Nuevo, repetición confirma identidad | Hand Rolls | 3990.00 | 1 Hand Roll; un relleno |
| 23, 28 | Hand Roll Relleno Doble / hand-roll-relleno-doble | Nuevo, repetición confirma identidad | Hand Rolls | 4990.00 | 1 Hand Roll; una proteína Y un vegetal |
| 24, 26 | 3 Hand Rolls / 3-hand-rolls | Nuevo, ficha confirma | Hand Rolls | 11000.00 | 3 unidades configurables independientes |
| 24, 25 | 4 Hand Rolls / 4-hand-rolls | Nuevo, ficha confirma | Hand Rolls | 14990.00 | 4 unidades configurables independientes |
| 24, 29 | 5 Hand Rolls / 5-hand-rolls | Nuevo, ficha amplía segundo ingrediente | Hand Rolls | 18000.00 | 5 unidades; segundo ingrediente opcional por unidad |
| 24 | 4 Hand Rolls Pollo Palta / 4-hand-rolls-pollo-palta | Nuevo fijo | Hand Rolls | 18000.00 | 4 unidades de composición establecida |
| 30 | 50 Piezas a Elección / 50-piezas-a-eleccion | Confirmación del existente | Promociones a elección | 20990.00 | Mismo ID, 50 piezas y 5 bloques de 10 |

Son **ocho productos nuevos**, total 28; cuatro categorías. Hand Rolls tiene orden 3 y Especialidades orden 4. Se reutilizan las dos categorías previas. **Pepino** (vegetal) es el único ingrediente nuevo: total 15. No se crean Carne, Primavera, Frito, Hand Roll ni Relleno doble como ingredientes.

Las Mismas de Siempre: 20 pollo; 10 camarón, kanikama, choclo, champiñón, salmón y palmito = **80**. Base queso crema/cebollín. Reglas globales `piezas-frio=40`, `piezas-frito=40`, sin asignación individual a bloques. Envolturas declaradas: queso crema, palta, sésamo y ciboulette. El producto es fijo y se reconstruye sin elecciones manuales.

Muchupleto: composición fija **queso crema + cebollín + palta + pollo**, no reemplazable. El grupo de producto «Elección adicional» exige exactamente una opción de Pollo/Kanikama/Salmón/Atún/Camarón/Choclo/Palmito/Palta/Champiñón. No se añaden Pepino ni Morrón a este grupo. Su precio es 6500 con cualquier elección admitida. No tiene bloques artificiales. La composición fija completa se muestra en base y se conserva en detalle/snapshot.

4 Hand Rolls Pollo Palta: cuatro bloques comerciales fijos, con queso crema/cebollín y relleno establecido pollo/palta por unidad. Ninguno se transforma en un selector editable.

## Unidades comerciales: cambio de esquema compatible

Migración nueva: `2026_10_22_000100_add_commercial_units_to_catalog.php`. Se comprobó migrate:status antes y se ejecutó únicamente `php artisan migrate` normal. Se aplica una migración, total **45**. No se reemplazan tablas ni se modifican pedidos.

- `productos.unidad_tipo`: VARCHAR(30), default `pieza`, controlado/casteado por enum PHP `UnidadComercial` (`pieza`, `hand_roll`, `unidad`). **productos.unidades sigue siendo la cantidad incluida**, sin duplicar esa responsabilidad con otra columna.
- `bloques_promocion.unidad_tipo`: mismo contrato, default pieza.
- `bloques_promocion.cantidad_unidades`: UNSIGNED SMALLINT nullable, para unidades comerciales no medidas en piezas.
- `bloques_promocion.cantidad_piezas`: se amplía a nullable, manteniendo default 10 y los valores previos. En un bloque Hand Roll se guarda **NULL**, nunca diez ni una pieza ficticia.

Los bloques legacy siguen siendo pieza/10. Cada bloque Hand Roll es hand_roll/1 unidad, con cantidad_piezas=NULL. Muchupleto es unidad/1 a nivel producto. El modelo valida exclusión entre las dos cantidades; el cotizador comprueba tipos coincidentes y suma de cantidades contra Producto.unidades. Los campos rolls_* legacy no se reutilizan para afirmar piezas de Hand Rolls.

La migración tiene down inverso. Se prueba up/down/up con datos legacy en una conexión SQLite aislada. El down se detiene antes de modificar nada si existen productos/bloques que necesitan las unidades nuevas, para evitar pérdida semántica. No se ejecutaron rollbacks sobre la base de la aplicación.

Se mantienen Producto como entidad vendible y Promocion complementaria para promociones/combos. Muchupleto y Hand Rolls individuales son productos simples sin promociones ficticias; los combos tienen metadatos complementarios y cantidad_rolls cuenta Hand Rolls, sin convertirlos a cortes.

## Grupos reutilizados y elecciones

Se reutilizan Proteína y Vegetal para Relleno Doble; Vegetal se amplía con Pepino. Cada grupo tiene min=max=1. El servidor rechaza dos proteínas, dos vegetales o cualquiera de las dos elecciones ausente.

`relleno-hand-roll` reúne las cinco proteínas y seis vegetales confirmados: un único grupo single min=max=1 para Tradicional y cada unidad de los combos. Los combos comparten ese mismo grupo, no uno por producto o unidad. `relleno-muchupleto` tiene el subconjunto de nueve ingredientes de su ficha. `segundo-ingrediente` tiene los once ingredientes permitidos y recargo propio; su responsabilidad y precio difieren del relleno incluido.

La ficha 24 es autoridad de la lista completa de proteínas de combos. Se mantienen Salmón/Atún pese a las listas resumidas de 25/26; la nota de no repetición de esas mismas fichas no se interpreta como exclusión.

## Salmón, Atún, Carne y recargos

- Tradicional: base 3990; reglas de recargo **incondicionales**, por ingrediente y exclusivamente de ese producto, Salmón +1000 y Atún +1000. Precio final de cualquiera: **4990**. El grupo de relleno compartido conserva opciones a recargo cero, para no propagar el recargo premium a otros productos.
- Relleno Doble: 4990, también con Salmón/Atún; sin otro +1000 inferido.
- Combos 3/4/5: Salmón y Atún disponibles a precio incluido; ambos máximo una elección en la promoción. Los demás ingredientes máximo dos. Se cuentan rellenos elegidos, no queso crema/cebollín fijos. No se agrega una regla artificial a individuales.
- **Carne queda pendiente**: solo aparece en una nota, sin ingrediente formal confirmado. No se crea ingrediente, opción ni regla.
- No se infiere recargo premium en combos; requiere confirmación comercial si se quisiera incorporarlo después. Los importes del navegador siguen sin autoridad.

## Segundo ingrediente, Foto 29

Implementado con el motor existente: solo 5 Hand Rolls asigna `segundo-ingrediente` a cada bloque, min=0/max=1. Sus opciones referencian los ingredientes permitidos y tienen recargo DECIMAL **1000.00**. Tres y cuatro Hand Rolls no asignan el grupo y rechazan sus IDs.

El servidor comprueba pertenencia, actividad y cardinalidad. El ingrediente adicional debe ser distinto del relleno de esa misma unidad; se valida de forma genérica por IDs de ingredientes de relleno dentro de un ámbito, sin condiciones por slug de producto. Los máximos de repetición se cuentan entre rellenos principales y adicionales de toda la promoción. No se inventa una exigencia proteína+vegetal para el segundo ingrediente de esta ficha; sí se exige en Relleno Doble, donde está expresamente declarada.

Precios verificados: sin adicionales 18000; uno 19000; dos 20000. La cantidad comprada multiplica el precio final de la presentación: dos promociones con dos adicionales cuestan 40000. No se cobra una tarifa global por pedido. ProductPricingService se conserva y continúa delegando la cotización comercial; no se reemplaza ni se cambia Mercado Pago.

## Frontend, carrito y snapshots

Se conserva el diseño; solo se amplía el modal dinámico y su resumen para unidades y grupos a nivel producto. El backend entrega etiquetas derivadas del tipo configurado: Hand Roll 1..N y cantidad comercial, frente a Roll 1..N/piezas para sushi. JavaScript no decide por slugs ni asume diez cortes. Muchupleto/individuales no muestran cero rolls ficticios; presentan sus grupos directos y resumen de elección.

La configuración sigue en versión 2. Para bloques no medidos en piezas se añaden `unit_type` y `units`, con `pieces=null`. Se conserva sin cambios el formato canónico previo de bloques en piezas, evitando alterar sus hashes por la ampliación. Los hashes siguen separando selecciones distintas de un mismo producto.

El detalle legible guarda etiquetas, tipo, cantidad y opciones; el snapshot añade tipo comercial/cantidad incluida del producto. Carrito, checkout, confirmación, administración y PDF leen ese detalle histórico. El componente de resumen mantiene fallback para snapshots antiguos sin etiquetas de unidad. No se reescriben snapshots previos ni se consulta el producto actual para reconstruirlos.

## Discrepancias documentadas y alcance de pruebas

| Dato | Decisión |
|---|---|
| Fotos 22/27 y 23/28 repetidas | Misma identidad Muchupleto/Tradicional/Doble, no registros adicionales |
| Fotos 25/26 omiten visualmente Salmón/Atún | Lista completa de 24 y nota de repetición conservan ambas opciones |
| Nota «Salmón, atún y Carne $4990» | Premium del Tradicional confirmado para Salmón/Atún; Carne pendiente |
| Precio premium de combos | Sin recargo inferido; pendiente de confirmación comercial |
| Segundo ingrediente | Solo 5 Hand Rolls, tarifa por unidad; ingredientes permitidos, distintos y sujetos a límites |
| Preparación de Las Mismas | Solo cantidades globales 40 frías/40 fritas; sin mapa inventado por roll |
| Foto 30 | Confirma el mismo 50 Piezas a Elección, ID 15 y 20990, con procedencia actualizada a 30 |

Las pruebas nuevas cubren datos/identidades, repeticiones de importación, tipos de unidad y migración reversible aislada, configuraciones válidas/inválidas, premium específico, grupos de producto, segundo ingrediente por unidad, IDs ajenos, precios falsos, snapshots, checkout y PDF. Se mantienen sin cambios las pruebas de seguridad, arquitectura, Batch01 e integración comercial anterior.

Las pruebas de Batch02 que dependían del principal completo se acotan a componer Batch01+Batch02 explícitamente, conservando sus aserciones de fase (20 productos/14 ingredientes/precio de resumen frente a ficha). El principal completo con tres lotes se verifica en Batch03, incluida precedencia de precio de Promo Hot y confirmación de foto 30. Esto evita que pruebas de una fase anterior impongan un catálogo cerrado de veinte a fases posteriores.

## Importación real e idempotencia

Se ejecutó el principal completo dos veces y se repitió el par final después de retirar referencias a fotos de las descripciones públicas (la procedencia queda en metadata/documentación). Ambas ejecuciones finales finalizaron con código 0, en orden Batch01/02/03.

| Tabla | Antes | Primera ejecución final | Segunda ejecución final |
|---|---:|---:|---:|
| categorias | 2 | 4 | 4 |
| ingredientes | 14 | 15 | 15 |
| productos | 20 | 28 | 28 |
| promociones | 20 | 25 | 25 |
| ingrediente_producto | 110 | 137 | 137 |
| grupos_opciones | 18 | 21 | 21 |
| opciones | 47 | 79 | 79 |
| grupo_opcion_producto | 0 | 4 | 4 |
| bloques_promocion | 104 | 128 | 128 |
| bloque_grupo_opcion | 150 | 183 | 183 |
| reglas_promocion | 366 | 418 | 418 |
| users | 1 | 1 | 1 |
| orders | 1 | 1 | 1 |
| order_items | 1 | 1 | 1 |

50 Piezas a Elección: **ID 15 → 15**, precio 20990.00. Todos los veinte IDs de productos previos se conservaron, junto con sus referencias de configuración. El seeder no toca usuarios, pedidos ni líneas históricas; su huella completa antes/después coincide. No se modifican PDFs históricos.

Igualdad entre las ejecuciones finales: conteos, todos los IDs y valores comerciales completos excluyendo timestamps. Huella SHA-256 de IDs `33d990593449c0e7ee039443eddb58a4bae3aa635d7662311018d0bf289cf6b2`; huella de valores `a6779e088f643c67c987dfff8d61241f110df1fcbeb9c6aae9092135b69f7fc5`. Se preservan claves únicas de slugs, posiciones, grupos, pivotes y reglas; no hay duplicados.

## Prueba en Chrome local

Se abrió el sitio servido en `http://127.0.0.1:8000` con Chrome headless/CDP, controlando DOM y peticiones reales, e inspeccionando capturas de escritorio y móvil. No se realizaron pagos ni pedidos sobre la base de la aplicación; solo carrito de sesión.

- Tradicional: Pollo **3990**, Salmón **4990**, Atún **4990**; resumen de producto visible y sin bloques ficticios.
- Relleno Doble: Pollo + Palta, **4990**, dos grupos obligatorios.
- 3 Hand Rolls: tres unidades etiquetadas; Pollo/Pollo/Camarón válido a **11000**, agregado y composición correcta en carrito. Pollo tres veces y Salmón dos veces rechazados con mensaje backend.
- 5 Hand Rolls: cinco unidades, base **18000**; un segundo ingrediente **19000** y dos **20000**. En móvil 390 px, documento 390 y ancho de contenido del modal 374/374, sin scroll horizontal.
- 4 Hand Rolls Pollo Palta: cuatro unidades fijas, cero selectores editables, **18000**.
- Muchupleto: base completa Pollo/Palta/Queso crema/Cebollín visible; cero bloques; botón deshabilitado sin elección. Kanikama habilita agregar por **6500**.
- Las Mismas de Siempre: **27900**, **80 piezas**, ocho bloques fijos, cero selectores, agregado sin configuración manual.
- 50 Piezas a Elección: ID **15**, cinco bloques de diez; Pollo/Camarón/Pollo/Palta/Champiñón cotiza **20990**.
- Sin excepciones JavaScript registradas.

## Archivos de esta fase

Nuevos: enum `app/Enums/UnidadComercial.php`; migración `2026_10_22_000100_add_commercial_units_to_catalog.php`; `database/seeders/SushiAkila/Batch03Data.php`; `database/seeders/SushiAkilaMenuBatch03Seeder.php`; `tests/Feature/SushiAkilaMenuBatch03Test.php`.

Modificados: `Producto.php`, `BloquePromocion.php`, `MenuConfigurationService.php`, `OrderItemSnapshotService.php`, `MenuBatchImporter.php`, `SushiAkilaMenuSeeder.php`, `SushiAkilaMenuBatch02Test.php`, `resources/js/modules/menuPersonalization.js`, componentes Blade `menu-product-modal` y `menu-selection-summary`, este documento y assets/manifest regenerados de Vite. No se cambia ProductPricingService ni servicios de Mercado Pago. Los cambios anteriores de otras fases se conservan; no confundirlos con todos los archivos mostrados por Git desde HEAD.

## Comprobaciones específicas

CommerceSecurityTest **59/183**, MenuArchitectureTest **33/98**, Batch01 **15/241**, Batch02 **22/373**, Batch03 **38/252** (incluye integración comercial nueva), MenuCommercialIntegrationTest **31/186**, todos aprobados. Pruebas y aserciones, respectivamente.

Suite completa final: **226 pruebas aprobadas, 1427 aserciones y 0 fallos**. Estado de Git revisado; no se hace commit.

`migrate:status`: 45 aplicadas, ninguna pendiente. `route:list`: 58 rutas, correcto. `npm run build`: correcto, 66 módulos. `git diff --check`: correcto.

El comando solicitado `php artisan pint --test` devuelve «Command pint is not defined»: este proyecto usa el binario de Composer, no un comando Artisan registrado. Se ejecutó el equivalente **`php vendor/bin/pint --test` sobre los doce archivos PHP nuevos/modificados de esta fase**, resultado passed. No se formatean archivos ajenos para alterar artificialmente el resultado.

Pendientes de datos: Carne y eventual recargo premium en combos requieren confirmación; las preparaciones individuales no declaradas de Las Mismas no se inventan. No se incorporaron fotos 31–42, inventario, gramajes o delivery nuevo. `.env` intacto; sin commit.
