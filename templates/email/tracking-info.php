<?php
/**
 * La Poste Tracking
 *
 * Shows tracking information in the HTML order email
 *
 * @author  Nicolas Mollet
 * @version 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( $tracking_items ) : ?>
	<h2><?php echo apply_filters( 'woocommerce_la_poste_tracking_my_orders_title',
			__( 'Tracking Information', 'tracking-la-poste-for-woocommerce' ) ); ?></h2>
	<?php
	foreach ( $tracking_items as $tracking_item ) {

		?>
		<p><?php printf(__( 'Your product has been shipped. The tracking number is: %s', 'tmsm-woocommerce-laposte-tracking' ), '<a href="'.esc_url( $tracking_item[ 'formatted_tracking_link' ] ).'">'.esc_html( $tracking_item['tracking_number'] ).'</a>'); ?>
		</p>

		<?php
	}
	?>

<?php
endif;
