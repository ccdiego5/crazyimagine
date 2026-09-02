# Ágora Calendar

Calendario base de festivos para Ágora Cultural (PDF v2). Fuente: Nager.Date **v3** (`date.nager.at`), sin clave. Segunda fuente: CSV.

No mezclar con el repo de Nómade.

## Arranque (LocalWP + Blocksy)

1. Copiar este directorio a `wp-content/plugins/agora-calendar/`
2. Copiar `themes/blocksy-child/` a `wp-content/themes/blocksy-child/` (o sus `templates/` si ya tienes el child de Nómade)
3. Activar Blocksy, el child y **Ágora Calendar**
4. Festivos Ágora → Sincronizar → Sincronizar ahora
5. Página: `/calendario-agora/?country=ES`

El child overridea `templates/calendar.php` y `templates/event-card.php`. Al desactivarlo vuelve el markup del plugin.

## REST

`GET /wp-json/agora/v1/events?country=ES&page=1&per_page=20`

## Hooks

```php
add_filter( 'agora_active_countries', function ( array $codes ): array {
	return $codes;
} );

add_action( 'agora_sync_completed', function ( int $upserted, bool $manual, string $source_id ): void {}, 10, 3 );
```

PHP 8.3+ (esta Local corre 8.5.3).

Decisiones: [`DECISIONS.md`](DECISIONS.md). Uso de IA: [`AI.md`](AI.md).
