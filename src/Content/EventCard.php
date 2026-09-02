<?php

namespace Agora\Content;

defined( 'ABSPATH' ) || exit;

final class EventCard {

	public static function render( int $event_id ): string {
		if ( $event_id <= 0 || get_post_type( $event_id ) !== EventType::SLUG ) {
			return '';
		}

		$theme = locate_template( 'templates/event-card.php' );
		$path  = $theme !== '' ? $theme : AGORA_CALENDAR_DIR . 'templates/event-card.php';

		ob_start();
		$agora_event = EventType::view( $event_id );
		require $path;
		return (string) ob_get_clean();
	}
}
