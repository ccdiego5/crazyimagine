# DECISIONS

Variante: **Ágora Cultural** (PDF v2). Comprobado en Nager.Date v3 el 2026-09-02.

Países: ES, MX, CO, CL, PE, VE. Fuente: **v3** `https://date.nager.at/api/v3/PublicHolidays/{year}/{countryCode}`. No usamos `nagerholidays.com/api/v4`: solo trae el nombre en inglés. La v3 trae `localName`. Ninguna de las dos filtra por ciudad.

---

### 1. No hay filtro por ciudad: el listado es por país

Situación — Marta dijo que “esa API ya filtra por ciudad”. Nager tiene festivos por país y año. El campo `counties` es región (ejemplo: `ES-AN`), no una sede.

Decisión — El visitante filtra por país (`?country=ES`). Si hay `counties`, se muestra como ámbito, no como filtro de sede. Las 40 casas quedan fuera del núcleo.

Alternativas descartadas — Inventar un endpoint de ciudad. Mapear regiones a sedes a mano.

Qué sacrifiqué — El filtro por ciudad de la reunión. A cambio, el listado dice la verdad.

Qué rompe esto a escala 100× — 20.000 eventos filtrados por una “ciudad” inventada. País en taxonomía aguanta.

Qué haría con una semana más — Un CSV de sedes → país, sin fingir que Nager lo trae.

---

### 2. Sync diario, no cada cinco minutos

Situación — Marta pidió update cada 5 minutos. Los festivos no cambian así. Hosting sin cron de sistema.

Decisión — WP-Cron una vez al día + botón. Año en curso. Lock y log. El botón no borra las correcciones de Marta.

Alternativas descartadas — Cron cada 5 min. Llamar a Nager al abrir el listado.

Qué sacrifiqué — El “siempre al minuto”. A cambio, Marta deja de pegar Excel y el visitante no espera.

Qué rompe esto a escala 100× — Seis países × un año. Se rompe sincronizar diez años en cada clic o sync desde el front.

Qué haría con una semana más — `wp agora sync --country=ES --dry-run`.

---

### 3. Oficial + override de Marta; “coincidir” es fecha y país

Situación — Marta corrige nombres y añade descripción. A la vez “tiene que coincidir siempre con la fuente oficial”.

Decisión — Se guarda el payload oficial. El título público es `localName`. Si Marta edita, va a meta. El sync no lo pisa. Reset restaura. “Coincidir” = misma fecha y país.

Alternativas descartadas — Pisar el título de Marta en cada sync. No dejarla editar.

Qué sacrifiqué — El inglés de `name` en la ficha. A cambio, Marta trabaja y el origen sigue ahí.

Qué rompe esto a escala 100× — Se rompe si el sync borra y vuelve a crear. Upsert por `país + fecha + name`.

Qué haría con una semana más — Aviso si Nager cambió el nombre oficial.

---

### 4. CPT + taxonomía de país; no se trae la agenda desde 2016

Situación — El encargo es el calendario base, no conciertos ni talleres desde 2016.

Decisión — CPT `agora_event` + taxonomía `agora_country`. Un festivo = un post. Solo el año corriente. Segunda fuente: CSV.

Alternativas descartadas — Tabla SQL sin CPT. Importar diez años el primer día.

Qué sacrifiqué — El histórico automático. El dolor real es el enero de Marta.

Qué rompe esto a escala 100× — Pedir “trae todo desde 2016”. Upsert + taxonomía aguanta el listado.

Qué haría con una semana más — Comando para un año concreto, en seco.

---

### 5. El listado lee posts locales; el REST no llama a Nager

Situación — El listado tiene que abrir si Nager está caído. Piden REST con paginación.

Decisión — `GET /wp-json/agora/v1/events?country=ES&page=1&per_page=20` (techo 50). Lee el CPT. 400 si el país no está activo. El front es un calendario anual en PHP (`?country=`). Sin JS recarga.

Alternativas descartadas — Pintar el calendario pidiendo Nager o el REST.

Qué sacrifiqué — Un filtro que no recarga. A cambio, el calendario existe aunque falle el script.

Qué rompe esto a escala 100× — Sync en el GET o `per_page` sin techo.

Qué haría con una semana más — JS que refine, con el HTML ya pintado.

---

### Pieza frágil

Si Nager cambia el `name` inglés, el upsert puede crear un duplicado y el override de Marta queda en el post viejo. Primer test: dos sync seguidos = mismos IDs.
