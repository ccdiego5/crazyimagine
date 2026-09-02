# AI.md

Usé un asistente (Cursor) para el esqueleto PSR-4, el primer metabox, el endpoint REST y el CSS corto del child. El modelado y las cinco decisiones las escribí yo: Frankfurter para las seis, tipos persistidos, override solo si Elena cambia el número, ficha en PHP, child que overridea la plantilla del plugin.

Reescribí tres cosas que el asistente dejó mal o que el skill de dominio contradecía. (1) El skill apuntaba a ExchangeRate-API con clave; el PDF pide fuente pública sin key y Frankfurter v2 ya cubría MXN COP CLP PEN EUR VES el 2026-09-02. (2) El metabox de override iba vacío: Elena no veía el precio del día para redondear. Lo rellené con el calculado y, al guardar, borro el meta si el número es el mismo que el tipo. (3) El child oficial no encola `style.css`; sin ese enqueue el bloque seguía viéndose como un post.

Propuesta que rechacé: un admin CRUD oscuro, tipo panel propio (tabla, SKU, stock, PVP). El brief prohíbe reescribir lo que WordPress ya resuelve y los frameworks de admin. Elena crea productos en el CPT nativo. La misma negativa para WooCommerce: el cliente dijo que no lo van a instalar. También rechacé mezclar Frankfurter + bcv.today en el mismo sync: es el extra “segunda API”, dos puntos de fallo, y el VES oficial no era el núcleo.

Lo que no generó la IA: comprobar Frankfurter en vivo frente a la mesa BCV, el criterio de “si lo deja igual no se congela”, y el texto de `DECISIONS.md`.
