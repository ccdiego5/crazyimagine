# Nómade Prices

Plugin de WordPress para Nómade Outdoor (prueba Crazy Imagine, PDF v1). Precio base en USD, tipos persistidos, conversión al vuelo. **Sin WooCommerce.**

Esta rama es solo Nómade. El mapa de las dos variantes está en [`main`](https://github.com/ccdiego5/crazyimagine). Ágora vive en la rama `agora`.

## Requisitos

- WordPress 6+ (la prueba pide 7.x)
- PHP **8.3+**. La demo en LocalWP corre **PHP 8.5.3**
- Tema: [Blocksy](https://wordpress.org/themes/blocksy/) (free) + el child de este repo. Sin Companion, sin Pro

## Arranque (LocalWP)

1. Copiar este directorio a `wp-content/plugins/nomade-prices/`
2. Copiar `themes/blocksy-child/` a `wp-content/themes/blocksy-child/`
3. Instalar y activar Blocksy, luego activar **Blocksy Child**
4. Activar **Nómade Prices**
5. Productos Nómade → Tipos de cambio → Sincronizar ahora
6. Ajustes → Enlaces permanentes → Guardar
7. Ficha: `/catalogo/kit-reparacion/?currency=COP`

## Qué hace

- Fuentes: Frankfurter (primaria, sin clave) + CSV local
- CPT `nomade_product`, meta USD, override de redondeo
- Sync diario (WP-Cron) + botón, lock, log
- REST: `GET /wp-json/nomade/v1/products/{id}/price?currency=MXN`
- Shortcodes: `[nomade_price]` `[nomade_catalog]`
- El child overridea `templates/price-block.php` vía `locate_template`. Desactivar el child vuelve al markup del plugin

Monedas: MXN, COP, CLP, PEN, EUR, VES.

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

- El visitante no pega a Frankfurter. Si el sync falla, se quedan los tipos anteriores
- VES de Frankfurter no es mesa BCV. Se muestra fecha y fuente
- Domingo = último tipo hábil, con fecha a la vista

Decisiones: [`DECISIONS.md`](DECISIONS.md). Uso de IA: [`AI.md`](AI.md).
