# DECISIONS

Variante: **Nómade Outdoor** (PDF v1)

---

### 1. Una sola API para las seis: Frankfurter

Situación — Elena tiene seis monedas. El brief pide una API pública sin clave y una segunda implementación para el Excel del banco. Frankfurter v2 cubre las seis (comprobado 2026-09-02). Su VES (795,64) no es la mesa BCV (801,1752): es un promedio de otros bancos.

Decisión — Una fuente por sync, las seis monedas iguales. Primaria: Frankfurter, un GET, sin clave. Segunda: CSV local. No se mezclan APIs en la misma corrida. El visitante no pega a nadie. El desvío del VES se dice aquí y en la ficha (fecha + fuente).

Alternativas descartadas — Ligar Frankfurter + bcv.today: es el extra “segunda API” y dos puntos de fallo. ExchangeRate-API: VES = mesa, pero pide clave. Scrapear bcv.org.ve: no es API.

Qué sacrifiqué — El VES de la mesa venezolana. A cambio el revisor levanta el repo sin keys y el modelo es uno solo.

Qué rompe esto a escala 100× — Un HTTP por sync. Se rompe si alguien convierte en el front o inventa un VES “oficial” distinto por producto.

Qué haría con una semana más — El extra: segunda API solo para VES, sin N+1, y un aviso “este precio VE no es mesa BCV”.

---

### 2. Tema hijo de Blocksy, no de un tema del core

Situación — Piden un tema hijo que cambie el bloque de precio sin tocar el plugin, y que se vea que entiendes la jerarquía de plantillas. El texto habla primero de un tema del core.

Decisión — Parent: Blocksy (free). Child: el oficial de Creative Themes. El plugin sigue siendo dueño del markup (`templates/price-block.php` + `locate_template`). El child solo overridea esa plantilla y encola ≤50 líneas de CSS (Blocksy no carga `style.css` del child solo: hay que encolarlo). Sin Companion, sin Pro, sin Customizer como “solución” del bloque de precio.

Alternativas descartadas — Twenty Twenty-Five child: encaja literal con “tema del core” y es más fácil de reproducir, pero no es el stack con el que voy a trabajar el look. Usar opciones/Companion de Blocksy para el precio: esconde la frontera plugin/tema y parece page-builder light, que ellos prohibieron.

Qué sacrifiqué — Los revisores tienen que tener Blocksy instalado (LocalWP o `wp-env`). Más una dependencia de terceros que un hijo de Twenty Twenty-Five.

Qué rompe esto a escala 100× — Nada en el cálculo de precios (eso no vive en el tema). Lo que se rompe es un override de plantilla de Blocksy que no actualizas cuando ellos cambian el parent. Por eso no overrideamos archivos de Blocksy, solo la plantilla del plugin.

Qué haría con una semana más — Blueprint de Playground/wp-env que instale Blocksy + child + plugin en un comando.

---

### 3. Sync diario, no cada hora; domingo = último tipo

Situación — Elena pidió update cada hora y “precio exacto” también el domingo. Frankfurter publica tipos de días hábiles, no un ticker horario. Hosting sin cron de SO.

Decisión — WP-Cron una vez al día + botón manual. Se persiste el lote de tipos (MXN COP CLP PEN EUR VES) con `rate_date`. El domingo se muestra el último tipo, con fecha a la vista. Eso es el precio correcto: no hay otro oficial.

Alternativas descartadas — Cron cada hora: no cambia el número y pega de más a un servicio ajeno. Pegar a la API en la ficha: rompe “abre instantánea si el servicio está caído”.

Qué sacrifiqué — El “siempre exacto” de la reunión. A cambio, Elena deja de pegar Excel y el visitante no espera.

Qué rompe esto a escala 100× — 5.000 productos no multiplican HTTP: un fetch, muchos cálculos. Lo que rompe es disparar sync en el front o solapar corridas sin lock.

Qué haría con una semana más — WP-CLI `wp nomade sync --dry-run` para cron real del hosting.

---

### 4. Tipos persistidos, precio al vuelo; override de Elena visible

Situación — Hay que elegir si se guardan 6 precios por producto o se calcula con el último tipo. Elena quiere redondear (51,37 queda mal) y a la vez que el precio “refleje el tipo del día”.

Decisión — Se guardan tipos, no 6 metas de precio. `precio_local = usd * rate` (o el override si existe). El override es excepción marcada: se ve el tipo del día y que Elena fijó el número. Seed: 10 productos por CSV. Si ella deja el número calculado y guarda, no se persiste override.

Alternativas descartadas — Precalcular y guardar 6 precios: el botón “recalcular todo” se vuelve un rewrite de 5.000 × 8 filas y se desincroniza si cambia el tipo. Convertir en JavaScript: dos fuentes de verdad.

Qué sacrifiqué — Un admin donde Elena “ve” todos los precios locales ya escritos. A cambio, un solo lugar para el tipo y un override puntual.

Qué rompe esto a escala 100× — 5.000 × 8 lecturas de meta si filtráramos por precio guardado. Al vuelo + tipos en options aguanta. Se rompe si el archive ordena por precio local sin índice.

Qué haría con una semana más — Redondeo por moneda (0 decimales COP/CLP/VES, 2 MXN/PEN/EUR) como default, override solo para excepciones.

---

### 5. La ficha calcula en PHP; el REST es para quien integre

Situación — Piden un endpoint propio y un selector. Si la ficha pega al REST, el visitante depende de JS y hay dos sitios que calculan.

Decisión — `GET /wp-json/nomade/v1/products/{id}/price?currency=MXN` lee solo lo persistido. Público: el precio ya está en la ficha. El selector es un GET (`?currency=`) + cookie. Sin JS el formulario recarga. 404 si no hay producto o no hay tipo: no se inventa un precio. API caída no afecta al endpoint.

Alternativas descartadas — Front que llama al REST para pintar el precio: sin JS no hay precio. Convertir en JS: segunda fuente de verdad.

Qué sacrifiqué — Un selector que cambia el número sin recargar. A cambio, el precio existe aunque falle el script.

Qué rompe esto a escala 100× — El REST no pega a Frankfurter. Se rompe si alguien usa el endpoint para disparar un sync.

Qué haría con una semana más — JS que lea el REST solo como mejora, con el HTML ya pintado.

---

### Pieza frágil

El parser de Frankfurter: la v2 a veces manda una lista de filas y a veces `{ date, rates }`. Si ese parse falla en silencio, el sync “ok” no trae COP y el visitante ve “sin tipo” o, peor, un merge viejo. A 5.000 productos no se rompe el cálculo (un option, muchos quotes); se rompe si alguien guarda 6 precios por producto o pega a la API en el REST.

Primer test que escribiría: un fixture de cada forma de JSON + un payload sin COP. Esperado: no se inventa COP; los tipos buenos anteriores no se pisan si la corrida falla.
