<?php
/**
 * La Poste Tracking
 *
 * Shows tracking information in the plain text order email
 *
 * @author  Nicolas Mollet
 * @author  Remi Corson
 * @version 1.0.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( $tracking_items ) : 
	
	echo apply_filters( 'woocommerce_la_poste_tracking_my_orders_title', __( 'TRACKING INFORMATION', 'tmsm-woocommerce-laposte-tracking' ) );

		echo  "\n";

		$tracking_item = $tracking_items[0] ?? null;
		if( ! empty( $tracking_item ) ) {
			
			echo esc_html( $tracking_item[ 'tracking_number' ] ) . "\n";
			echo esc_html( $tracking_item[ 'tracking_message' ] ) . "\n";
			echo 'https://www.laposte.fr/particulier/outils/suivre-vos-envois?code='.esc_url( $tracking_item[ 'tracking_number' ] ) . "\n\n";
			
		}

	echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= \n\n";

endif;

?>
