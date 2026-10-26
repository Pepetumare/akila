# Sushi Akila — Referencia visual UX/UI

Estas imágenes son referencias visuales aprobadas para rediseñar la carta pública de Sushi Akila.

## Archivos

- `reference-01-overview.png`: referencia general de carta, cards, modal, carrito, móvil y tablet.
- `reference-02-detailed.png`: referencia detallada de cards, modal de personalización, producto fijo, resumen, móvil y carrito.

## Regla principal

No copiar datos ficticios ni fotografías de las referencias. Adaptar únicamente:

- composición visual;
- jerarquía;
- espaciado;
- densidad de información;
- estructura de cards;
- distribución del modal;
- navegación por bloques;
- resumen de selección;
- comportamiento responsive;
- estados visuales.

La base de datos, precios, reglas, categorías, opciones, recargos, salsas, unidades comerciales y validaciones reales del proyecto siguen siendo la autoridad.

## Objetivo visual

La implementación final debe parecerse claramente a estas referencias, no solo "inspirarse" de forma genérica.

### Carta

- Hero compacto y gastronómico.
- Categorías en chips.
- Resultados y ordenamiento compactos.
- Grid de cards uniforme.
- Cards con imagen/fallback, badge, categoría, nombre, metadata breve, precio y CTA.
- No mostrar descripciones largas truncadas como contenido principal de la card.

### Cards

- Alturas uniformes.
- Imagen superior consistente.
- Máximo 3–4 metadatos breves.
- Precio y CTA alineados en la parte inferior.
- Personalizable: CTA `Personalizar`.
- Fijo: CTA `Ver y agregar`.

### Modal personalizable

- Header fijo.
- Body interno scrolleable.
- Footer fijo dentro del panel.
- Desktop: columna de resumen + columna de configuración.
- Bloques configurables en tarjetas/acordeones.
- Mostrar progreso y estados `Completado` / `Pendiente`.
- Proteínas y vegetales en controles compactos.
- Resumen y precio siempre visibles.
- Nunca dejar grandes áreas blancas vacías.

### Modal producto fijo

- Mostrar composición y preparación claramente.
- No mostrar controles que no correspondan.
- CTA directo para agregar al carrito.

### Móvil

- Una sola columna.
- Modal casi a pantalla completa.
- Header compacto.
- Footer de compra siempre visible dentro del modal.
- Sin scroll horizontal.

## Problema funcional prioritario

Corregir el bug donde, tras muchas selecciones/cotizaciones, el modal salta hacia arriba y deja un área blanca grande. Identificar la causa real de layout/scroll/re-render y corregirla, no ocultarla con un parche.
