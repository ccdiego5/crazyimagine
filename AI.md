# AI.md

Usé un asistente (Cursor) para el esqueleto del plugin (autoload, CPT, capa `RateSource`, Frankfurter + CSV, sync con lock y log, REST `nomade/v1`, plantilla `price-block.php` y el CSS corto del child). Las decisiones de negocio las cerré yo y están en `DECISIONS.md`.

Qué reescribí y por qué. El asistente (y un skill de dominio) proponía ExchangeRate-API v6 con clave en `wp-config.php`. El PDF pide una API pública **sin clave**; el 2026-09-02 Frankfurter v2 ya devolvía MXN, COP, CLP, PEN, EUR y VES. Cambié la fuente primaria a Frankfurter. El metabox de redondeo salía vacío: Elena no veía el precio del día. Lo rellené con el calculado y, si guarda el mismo número, no persisto override — si no, el precio se congelaba y dejaba de seguir el tipo. El child oficial de Blocksy no encola `style.css`; sin eso el bloque de precio se veía como un post. El front no llama al REST: el precio lo pinta PHP con `?currency=`; el endpoint es para quien integre, y no pega a Frankfurter.

Propuesta que rechacé: convertir en el navegador (o que la ficha pida el REST para mostrar el monto). Motivo: el brief exige ficha instantánea si el servicio de tipos está caído, y una sola fuente de verdad. Con JS o un fetch al REST, sin script no hay precio y aparecen dos sitios que calculan. Otra que rechacé: un GET a Frankfurter en cada ficha. Eso hace esperar al visitante y rompe el caché que piden.

Lo que no salió de la IA: comprobar en vivo Frankfurter frente a la mesa BCV (VES 795,64 vs 801,1752), el criterio de “si Elena deja el calculado, no se congela”, y el texto de las cinco decisiones.
