# Nómade Prices

Plugin de WordPress para el catálogo de Nómade Outdoor: precio base en USD, tipos persistidos y conversión al vuelo. Sin WooCommerce.

Este repo es **solo el plugin**. El tema hijo y los entregables (`DECISIONS.md`, `AI.md`, vídeo) van al cierre.

## Requisitos

- WordPress 6+ (la prueba pide 7.x)
- PHP **8.3+** (LocalWP de esta demo corre 8.5.3)
- Tema: Blocksy + child (el child no vive aquí todavía)

## Arranque (LocalWP)

1. Copiar esta carpeta a `wp-content/plugins/nomade-prices/`
2. Activar **Nómade Prices**
3. Productos Nómade → Tipos de cambio → Sincronizar ahora
4. Ajustes → Enlaces permanentes → Guardar
5. Ficha: `/catalogo/kit-reparacion/?currency=COP`

## Qué hay ahora

- Fuentes: Frankfurter (primaria, sin clave) + CSV local
- CPT `nomade_product`, meta USD, override de redondeo
- Sync diario + botón, lock, log
- REST: `GET /wp-json/nomade/v1/products/{id}/price?currency=MXN`
- Shortcodes: `[nomade_price]` `[nomade_catalog]`

## Hooks

```php
// Filtrar monedas activas.
add_filter( 'nomade_active_currencies', function ( array $codes ): array {
	return $codes;
} );

// Tras un sync que persistió tipos.
add_action( 'nomade_sync_completed', function ( $payload, bool $manual, string $source_id ): void {
	// $payload = option nomade_rates
}, 10, 3 );
```

## Límites

- El visitante no pega a Frankfurter. Si el sync falla, se quedan los tipos anteriores.
- VES de Frankfurter no es mesa BCV. Se muestra fecha y fuente.
