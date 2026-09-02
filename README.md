# Crazy Imagine — Prueba Full Stack Senior

Diego Cárdenas. Las dos variantes del examen, cada una en su rama. Este `main` no instala nada: es el mapa.

| Rama | Variante | Plugin |
|---|---|---|
| [`nomade`](https://github.com/ccdiego5/crazyimagine/tree/nomade) | PDF v1 — Nómade Outdoor | `nomade-prices` |
| [`agora`](https://github.com/ccdiego5/crazyimagine/tree/agora) | PDF v2 — Ágora Cultural | `agora-calendar` |

Cómo revisar una: `git checkout nomade` o `git checkout agora`, copiar esa carpeta a `wp-content/plugins/` y seguir el README de la rama. No mezclar los dos plugins en el mismo checkout.

WordPress 6+ (la prueba pide 7.x) · PHP 8.3+ (esta Local corre 8.5.3) · tema [Blocksy](https://wordpress.org/themes/blocksy/) free + el child que va en cada rama. Sin WooCommerce, sin page builders, sin ACF.

---

## Nómade Outdoor (v1)

Elena vende el mismo producto en seis países con un precio en dólares. No hay WooCommerce y no lo van a instalar. El visitante tiene que ver su moneda sin esperar a un servicio de fuera.

**Qué se hizo**

- Una API sin clave (Frankfurter v2) y un CSV de respaldo. Un contrato (`RateSource`). Las seis monedas en el mismo GET.
- Los tipos se guardan. El precio se calcula en PHP. El visitante no pega a Frankfurter.
- CPT `nomade_product`, override solo si Elena cambia el número. Si deja el calculado, el precio sigue al tipo.
- Sync diario (WP-Cron) + **Sincronizar ahora**. No cada hora: el tipo no cambia así y el hosting no tiene cron de sistema.
- REST: `GET /wp-json/nomade/v1/products/{id}/price?currency=COP`. Lee tipos locales.
- Front: `?currency=` + cookie. El selector funciona sin JavaScript.
- Child de Blocksy: cambia `templates/price-block.php` sin editar el plugin.

**Cómo se decidió** (y qué se recortó) está en la rama:

- Arranque: [`README.md`](https://github.com/ccdiego5/crazyimagine/blob/nomade/README.md)
- Decisiones: [`DECISIONS.md`](https://github.com/ccdiego5/crazyimagine/blob/nomade/DECISIONS.md)
- Qué hizo la IA y qué reescribí: [`AI.md`](https://github.com/ccdiego5/crazyimagine/blob/nomade/AI.md)

Ficha de prueba: `/catalogo/kit-reparacion/?currency=COP`.

---

## Ágora Cultural (v2)

Marta no puede seguir pegando un Excel cada enero. Quiere el calendario base de festivos en seis países, y poder corregir nombres sin que el sync se lo borre.

**Qué se hizo**

- Nager.Date **v3** (`date.nager.at`) + CSV. La v4 solo manda el nombre en inglés; la v3 trae `localName`.
- No hay filtro por ciudad: Nager no lo tiene (`counties` es región, no sede). El visitante filtra por país.
- Sync diario + botón. Upsert por país + fecha + nombre oficial. Las correcciones de Marta no se pisan.
- CPT `agora_event` + taxonomía de país. Solo el año en curso.
- REST: `GET /wp-json/agora/v1/events?country=ES&page=1&per_page=20`. Lee posts locales. No llama a Nager.
- Front: calendario de 12 meses en PHP (`?country=ES`). Sin JS recarga.
- Child de Blocksy: overridea `templates/calendar.php` y `templates/event-card.php`.

**Cómo se decidió** (y qué se recortó) está en la rama:

- Arranque: [`README.md`](https://github.com/ccdiego5/crazyimagine/blob/agora/README.md)
- Decisiones: [`DECISIONS.md`](https://github.com/ccdiego5/crazyimagine/blob/agora/DECISIONS.md)
- Qué hizo la IA y qué reescribí: [`AI.md`](https://github.com/ccdiego5/crazyimagine/blob/agora/AI.md)

Página de prueba: `/calendario-agora/?country=ES`.

---

## Lo que no está en GitHub

Vídeo y correo van por fuera (Loom + mail a Gaby). No hay `.env` ni el WordPress de LocalWP.
