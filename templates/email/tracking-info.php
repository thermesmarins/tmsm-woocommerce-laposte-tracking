<?php
/**
 * La Poste Tracking
 *
 * Shows tracking information in the HTML order email
 *
 * @author  Nicolas Mollet
 * @author  Remi Corson
 * @version 1.0.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( $tracking_items ) : ?>
	<h2><?php echo apply_filters( 'woocommerce_la_poste_tracking_my_orders_title',
			__( 'Tracking Information', 'tmsm-woocommerce-laposte-tracking' ) ); ?></h2>
	<?php

	$tracking_item = $tracking_items[0] ?? null;
	if( ! empty( $tracking_item ) ) {

		?>
		<p><?php echo __( 'Your package has been shipped. Your tracking code is:', 'tmsm-woocommerce-laposte-tracking' );?>
			 <a href="https://www.laposte.fr/particulier/outils/suivre-vos-envois?code=<?php echo esc_html( $tracking_item[ 'tracking_number' ] ); ?>"><?php echo esc_html( $tracking_item[ 'tracking_number' ] ); ?></a></p>

		<?php
	}
	?>

<?php
endif;
