# AI.md

Usé un asistente (Cursor) para el esqueleto del plugin: autoload, CPT, taxonomía de país, capa `EventSource`, Nager v3 + CSV, sync con lock y log, REST `agora/v1/events` y el calendario anual. Las cinco decisiones las cerré yo; están en `DECISIONS.md`.

Qué reescribí y por qué. El asistente apuntó a `nagerholidays.com/api/v4`. Esa versión solo manda el nombre en inglés. El PDF pide `date.nager.at/api/v3`, que trae `localName` (Año Nuevo, Viernes Santo). Me quedé en v3. El primer listado era una pila de cajas: no se leía como calendario. Lo pasé a una cuadrícula de 12 meses. El sync no borra lo que Marta corrigió: si el nombre que guarda es el de la API, no se persiste override.

Propuesta que rechacé: filtrar por ciudad, o llamar a Nager al abrir la página. Motivo: Nager no tiene ciudad (`counties` es región) y el brief exige listado instantáneo si el servicio está caído. Con un fetch en el front, sin red no hay calendario.

Lo que no salió de la IA: comprobar v3 contra v4 el 2026-09-02, y el criterio de “coincidir = fecha y país, no el inglés”.
