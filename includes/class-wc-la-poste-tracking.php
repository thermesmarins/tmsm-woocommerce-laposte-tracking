<?php

/**
 * # WooCommerce La Poste Tracking Actions
 *
 * @since 1.0
 */

class WC_La_Poste_Tracking_Actions {

	/**
	 * Constructor
	 */
	public function __construct() {
		
		// hook into cron event to check if shipments status are upated
		add_action( 'wc_la_poste_tracking_update_check', array( $this, 'check_for_shipments_statuses_to_update' ), 10 );
		
	}

	/**
	 * Instance of this class.
	 *
	 * @var object Class Instance
	 */
    private static $instance;

	/**
	 * Absolute plugin path.
	 *
	 * @var string
	 */
	private $plugin_path;

	/**
     * Get the class instance
	 *
	 * @return WC_La_Poste_Tracking_Actions
	 */
    public static function get_instance() {
        return null === self::$instance ? ( self::$instance = new self ) : self::$instance;
    }

	/**
	 * Localisation.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'tmsm-woocommerce-laposte-tracking', false, dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/' );
	}

	public function admin_styles() {
		wp_enqueue_style( 'la_poste_tracking_styles', plugins_url( basename( dirname( dirname( __FILE__ ) ) ) ) . '/assets/css/admin.css' );
	}

	/**
	 * Define La Poste tracking column in admin orders list.
	 *
	 * @since 1.0
	 *
	 * @param array $columns Existing columns
	 *
	 * @return array Altered columns
	 */
	public function shop_order_columns( $columns ) {
		$columns['la_poste_tracking'] = __( 'La Poste Tracking', 'tmsm-woocommerce-laposte-tracking' );
		return $columns;
	}

	/**
	 * Render La Poste tracking in custom column.
	 *
	 * @since 1.0
	 *
	 * @param string $column Current column
	 */
	public function render_shop_order_columns( $column ) {
		global $post;

		if ( 'la_poste_tracking' === $column ) {
			echo $this->get_la_poste_tracking_column( $post->ID );
		}
	}

	/**
	 * Get content for La Poste tracking column.
	 *
	 * @since 1.0
	 *
	 * @param int $order_id Order ID
	 *
	 * @return string Column content to render
	 */
	public function get_la_poste_tracking_column( $order_id ) {
		ob_start();

		$tracking_items = $this->get_tracking_items( $order_id );

		if ( count( $tracking_items ) > 0 ) {
			foreach( $tracking_items as $tracking_item ) {
				if ( $tracking_item === end( $tracking_items )) {
					$formatted = $this->get_formatted_tracking_item( $order_id, $tracking_item );
					printf(
						'<a href="%s" target="_blank">%s</a><br />%s',
						esc_url( $formatted['formatted_tracking_link' ] ),
						esc_html( $tracking_item[ 'tracking_number' ] ), 
						$this->get_formatted_response( esc_html( $tracking_item[ 'tracking_status' ] ) )
					);
				}
			}
		} else {
			echo '–';
		}

		return apply_filters( 'woocommerce_la_poste_tracking_get_la_poste_tracking_column', ob_get_clean(), $order_id, $tracking_items );
	}

	/**
	 * Add the meta box for La Poste info on the order page
	 *
	 * @access public
	 */
	public function add_meta_box() {
		add_meta_box( 'la-poste-tracking-for-woocommerce', __( 'La Poste Tracking', 'tmsm-woocommerce-laposte-tracking' ), array( $this, 'meta_box' ), 'shop_order', 'side', 'high' );
	}

	/**
	 * Returns a HTML node for a tracking item for the admin meta box
	 *
	 * @param int $order_id
	 * @param array $item
	 *
	 * @access public
	 */

	public function display_html_tracking_item_for_meta_box( $order_id, $item ) {
			$formatted = $this->get_formatted_tracking_item( $order_id, $item );
			?>
			<div class="tracking-item" id="tracking-item-<?php echo esc_attr( $item[ 'tracking_id' ] ); ?>">
				<p class="tracking-content">
					<strong><?php _e( 'Code:', 'tmsm-woocommerce-laposte-tracking' ); ?> </strong><?php echo esc_html( $item[ 'tracking_number' ] ); ?>
					<?php if( $item[ 'tracking_status' ] != '') { ?>
					<br />
					<strong><?php _e( 'Status:', 'tmsm-woocommerce-laposte-tracking' ); ?> </strong><?php echo esc_html( $item[ 'tracking_status' ] ); ?>
					<?php } ?>
					<?php if( $item[ 'tracking_type' ] != '') { ?>
					<br />
					<strong><?php _e( 'Type:', 'tmsm-woocommerce-laposte-tracking' ); ?> </strong><?php echo esc_html( $this->get_formatted_response( $item[ 'tracking_type' ] ) ); ?>
					<?php } ?>
					<?php if( $item[ 'tracking_date' ] != '') { ?>
					<br />
					<strong><?php _e( 'Date:', 'tmsm-woocommerce-laposte-tracking' ); ?> </strong><?php
					echo sprintf(__( '%1$s at %2$s', 'tmsm-woocommerce-laposte-tracking'), esc_html( date_i18n( get_option( 'date_format' ), $this->get_formatted_response( $item[ 'tracking_date' ] ) ) ), esc_html( date_i18n( get_option( 'time_format' ), $this->get_formatted_response( $item[ 'tracking_date' ] ) ) )) ;
					?>

					<?php } ?>
					<?php if( $item[ 'tracking_message' ] != '') { ?>
					<br />
					<strong><?php _e( 'Message:', 'tmsm-woocommerce-laposte-tracking' ); ?> </strong><em><?php echo esc_html( $this->get_formatted_response( $item[ 'tracking_message' ] ) ); ?></em>
					<?php } ?>
				</p>
				<p class="meta">
					<?php echo esc_html( sprintf( __( 'Shipped on %s', 'tmsm-woocommerce-laposte-tracking' ), date_i18n( get_option( 'date_format' ), $item[ 'date_shipped' ] ) ) ); ?>
					<?php if( strlen( $item[ 'tracking_link' ] ) > 0 ) : ?>
						| <?php echo sprintf( '<a href="%s" target="_blank" title="' . esc_attr( __( 'Click here to track your shipment', 'tmsm-woocommerce-laposte-tracking' ) ) . '">' . __( 'Track', 'tmsm-woocommerce-laposte-tracking' ) . '</a>', $item[ 'tracking_link' ] ); ?>
					<?php endif; ?>
					- <a href="#" class="delete-tracking" rel="<?php echo esc_attr( $item[ 'tracking_id' ] ); ?>"><?php _e( 'Delete', 'tmsm-woocommerce-laposte-tracking' ); ?></a>
				</p>
			</div>
			<?php
	}

	/**
	 * Show the meta box for shipment info on the order page
	 *
	 * @access public
	 */
	public function meta_box() {
		
		global $woocommerce, $post;

		$tracking_items = $this->get_tracking_items( $post->ID );

		echo '<div id="tracking-items">';

		if ( count( $tracking_items ) > 0 ) {
			foreach( $tracking_items as $tracking_item ) {
				$this->display_html_tracking_item_for_meta_box( $post->ID, $tracking_item );
			}
		}

		echo '</div>';

		echo '<button class="button button-show-form" type="button">' . __( 'Add Tracking Number', 'tmsm-woocommerce-laposte-tracking' ) . '</button>';

		echo '<div id="la-poste-tracking-form">';

		woocommerce_wp_hidden_input( array(
			'id'    => 'WC_La_Poste_Tracking_delete_nonce',
			'value' => wp_create_nonce( 'delete-tracking-item' )
		) );

		woocommerce_wp_hidden_input( array(
			'id'    => 'WC_La_Poste_Tracking_create_nonce',
			'value' => wp_create_nonce( 'create-tracking-item' )
		) );

		woocommerce_wp_text_input( array(
			'id'          => 'tracking_number',
			'label'       => __( 'La Poste Tracking Number:', 'tmsm-woocommerce-laposte-tracking' ),
			'placeholder' => '',
			'description' => '',
			'value'       => ''
		) );
		
		woocommerce_wp_text_input( array(
			'id'          => 'date_shipped',
			'label'       => __( 'Date shipped:', 'tmsm-woocommerce-laposte-tracking' ),
			'placeholder' => date_i18n( __( 'Y-m-d', 'tmsm-woocommerce-laposte-tracking' ), time() ),
			'description' => '',
			'class'       => 'date-picker-field',
			'value'       => date_i18n( __( 'Y-m-d', 'tmsm-woocommerce-laposte-tracking' ), current_time( 'timestamp' ) )
		) );
		
		woocommerce_wp_hidden_input( array(
			'id'          => 'tracking_status',
			'value'       => '',
		) );
		
		woocommerce_wp_hidden_input( array(
			'id'          => 'tracking_type',
			'value'       => '',
		) );
		
		woocommerce_wp_hidden_input( array(
			'id'          => 'tracking_date',
			'value'       => '',
		) );
		
		woocommerce_wp_hidden_input( array(
			'id'          => 'tracking_message',
			'value'       => '',
		) );
		
		woocommerce_wp_hidden_input( array(
			'id'          => 'tracking_link',
			'value'       => '',
		) );
		

		echo '<button class="button button-primary button-save-form">' . __( 'Save Tracking Number', 'tmsm-woocommerce-laposte-tracking' ) . '</button>';

		echo '</div>';

		$js = "

			jQuery('input#tracking_number').change(function(){

				var tracking = jQuery('input#tracking_number').val();


			}).change();";

		if ( function_exists( 'wc_enqueue_js' ) ) {
			wc_enqueue_js( $js );
		} else {
			$woocommerce->add_inline_js( $js );
		}

		wp_enqueue_script( 'wc-la-poste-tracking-js', $GLOBALS['WC_La_Poste_Tracking']->plugin_url . '/assets/js/admin.min.js' );

	}

	/**
	 * Get shipment tracking via the API
	 *
	 * Function to get the shipment tracking
	 *
	 * @param string $code
	 * @param int $order_id
	 *
	 * @return array|mixed|WP_Error
	 */
	public function get_shipment_tracking( string $code, int $order_id ) {

		$options = get_option( 'woocommerce_la_poste_tracking_settings', array() );

		/*
		// v1
		$endpoint = 'https://api.laposte.fr/suivi/v1';
		$request  = "code=" . $code;
		$headers  = array( 
							'X-Okapi-Key' => $options['api_key'],
							'Content-Type' => 'application/json',
							'Accept' => 'application/json'
							);

		$response      = wp_remote_get( $endpoint . '?' . $request, array(
			'timeout' => 70,
			'headers' => $headers,
		) );*/

		// v2
		$endpoint = 'https://api.laposte.fr/suivi/v2/idships';
		$headers  = array(
			'X-Okapi-Key' => $options['api_key'],
			'Content-Type' => 'application/json',
			'Accept' => 'application/json'
		);

		$response = wp_remote_get( $endpoint . '/' . $code, array(
			'timeout' => 70,
			'headers' => $headers,
		) );


		$response_code = wp_remote_retrieve_response_code( $response );

		// Check for valid API request
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( wp_remote_retrieve_response_code( $response ), wp_remote_retrieve_response_message( $response ) );
		}

		$response = json_decode( $response['body'] );

		if( !empty($response) && !empty($response->returnCode)){
			if($response->returnCode === 200){

				$tracking_statuses = [
					'DR1' => __( 'Information Received', 'tmsm-woocommerce-laposte-tracking'),
					'PC1' => __( 'Acceptance', 'tmsm-woocommerce-laposte-tracking'),
					'PC2' => __( 'Acceptance in Shipping Country', 'tmsm-woocommerce-laposte-tracking'),
					'ET1' => __( 'Being Processed', 'tmsm-woocommerce-laposte-tracking'),
					'ET2' => __( 'Being Processed in Shipping Country', 'tmsm-woocommerce-laposte-tracking'),
					'ET3' => __( 'Being Processed in Final Country', 'tmsm-woocommerce-laposte-tracking'),
					'ET4' => __( 'Being Processed in Transit Country', 'tmsm-woocommerce-laposte-tracking'),
					'EP1' => __( 'Awaiting Delivery', 'tmsm-woocommerce-laposte-tracking'),
					'DO1' => __( 'Into Customs', 'tmsm-woocommerce-laposte-tracking'),
					'DO2' => __( 'Released by Customs', 'tmsm-woocommerce-laposte-tracking'),
					'DO3' => __( 'Retained by Customs', 'tmsm-woocommerce-laposte-tracking'),
					'PB1' => __( 'Ongoing Problem', 'tmsm-woocommerce-laposte-tracking'),
					'PB2' => __( 'Problem Resolved', 'tmsm-woocommerce-laposte-tracking'),
					'MD2' => __( 'Out for Delivery', 'tmsm-woocommerce-laposte-tracking'),
					'ND1' => __( 'Failed Attempt', 'tmsm-woocommerce-laposte-tracking'),
					'AG1' => __( 'Available for Pickup', 'tmsm-woocommerce-laposte-tracking'),
					'RE1' => __( 'Returned to Sender', 'tmsm-woocommerce-laposte-tracking'),
					'DI1' => __( 'Delivered', 'tmsm-woocommerce-laposte-tracking'),
					'DI2' => __( 'Delivered to Sender', 'tmsm-woocommerce-laposte-tracking'),
				];

				$response->code = $response->shipment->idShip;
				$response->date = $response->shipment->event[0]->date;
				$response->status = $tracking_statuses[$response->shipment->event[0]->code];
				$response->message = $response->shipment->event[0]->label;
				$response->link = $response->shipment->url;
				$response->type = $response->shipment->product;
			}
		}

		return $response;

	}

	/**
	 * Order Tracking Save
	 *
	 * Function for saving tracking items
	 *
	 * @param int $order_id
	 * @param WP_Post $post
	 */
	public function save_meta_box( $order_id, $post ) {

		if ( isset( $_POST['tracking_number'] ) && strlen( $_POST['tracking_number'] ) > 0 ) {

			$tracking = $this->get_shipment_tracking( $_POST['tracking_number'], $order_id );
			$args = array(
				'tracking_number'          => wc_clean( $_POST[ 'tracking_number' ] ),
				'tracking_status'          => $tracking->status ?? null,
				'tracking_type'            => $tracking->type ?? null,
				'tracking_date'            => $tracking->date ?? null,
				'tracking_message'         => $tracking->message ?? null,
				'tracking_link'            => $tracking->link ?? 'https://www.laposte.fr/outils/suivre-vos-envois?code='.wc_clean( $_POST[ 'tracking_number' ] ),
				'date_shipped'             => wc_clean( $_POST[ 'date_shipped' ] )
			);

			$this->add_tracking_item( $order_id, $args );
		}
	}

	/**
	 * Order Tracking Save AJAX
	 *
	 * Function for saving tracking items via AJAX
	 */
	public function save_meta_box_ajax() {

		check_ajax_referer( 'create-tracking-item', 'security', true );

		if ( isset( $_POST['tracking_number'] ) && strlen( $_POST['tracking_number'] ) > 0 ) {
			$order_id = wc_clean( $_POST[ 'order_id' ] );
			
			$tracking = $this->get_shipment_tracking( $_POST['tracking_number'], $order_id );

			$args = array(
				'tracking_number'          => wc_clean( $_POST[ 'tracking_number' ] ),
				'tracking_status'          => $tracking->status ?? null,
				'tracking_type'            => $tracking->type ?? null,
				'tracking_date'            => $tracking->date ?? null,
				'tracking_message'         => $tracking->message ?? null,
				'tracking_link'            => $tracking->link ?? 'https://www.laposte.fr/outils/suivre-vos-envois?code='.wc_clean( $_POST[ 'tracking_number' ] ),
				'date_shipped'             => wc_clean( $_POST[ 'date_shipped' ] )
			);
			$tracking_item = $this->add_tracking_item( $order_id, $args );

			$this->display_html_tracking_item_for_meta_box( $order_id, $tracking_item );
		}

		die();
	}

	/**
	 * Order Tracking Delete
	 *
	 * Function to delete a tracking item
	 */
	public function meta_box_delete_tracking() {

		check_ajax_referer( 'delete-tracking-item', 'security', true );

		$order_id = wc_clean( $_POST[ 'order_id' ] );
		$tracking_id = wc_clean( $_POST[ 'tracking_id' ] );

		$this->delete_tracking_item( $order_id, $tracking_id );
	}

	/**
	 * Display Shipment info in the frontend (order view/tracking page).
	 *
	 * @access public
	 */
	public function display_tracking_info( $order_id ) {
		wc_get_template( 'myaccount/view-order.php', array( 'tracking_items' => $this->get_tracking_items( $order_id, true ) ), 'tracking-la-poste-for-woocommerce/', $this->get_plugin_path() . '/templates/' );
	}

	/**
	 * Display shipment info in customer emails.
	 *
	 * @param      $order WC_Order
	 * @param      $sent_to_admin bool
	 * @param null $plain_text
	 *
	 * @return void
	 */
	public function email_display( $order, $sent_to_admin, $plain_text = null ) {

		if ( $plain_text === true ) {
			wc_get_template( 'email/plain/tracking-info.php', array( 'tracking_items' => $this->get_tracking_items( $order->get_id(), true ) ), 'tracking-la-poste-for-woocommerce/', $this->get_plugin_path() . '/templates/' );
		}
		else {
			wc_get_template( 'email/tracking-info.php', array( 'tracking_items' => $this->get_tracking_items( $order->get_id(), true ) ), 'tracking-la-poste-for-woocommerce/', $this->get_plugin_path() . '/templates/' );
		}
	}
	
	/**
	 * Called via wp-cron every 1 hour to check if there are shipments statuses to update
	 *
	 * @since 1.0
	 */
	public function check_for_shipments_statuses_to_update() {

		do_action( 'wc_la_poste_tracking_before_automatic_update_check' );
		
		$args = array(
			'fields'      => 'ids',
			'post_type'   => 'shop_order',
			'post_status' 	 => array('wc-completed', 'wc-processed'),
			'date_query' => array(
				array(
					'column' => 'post_modified_gmt',
					'after'     => apply_filters( 'wc_la_poste_tracking_status_check_period', '1 week ago'),
					'inclusive' => true,
				),
			),
			'meta_query' => array(
				array(
					'key'   	=> '_WC_La_Poste_Tracking_items',
					'compare' 	=> 'EXISTS',
				),
			),
		);
		
		$query = new WP_Query( $args );

		if ( empty( $query->posts ) ) {
			return;
		}
		
		foreach ( $query->posts as $order_post ) {


			$order = new WC_Order( $order_post );
			$order_id = $order->get_id();
			$shipments = get_post_meta( $order_id, '_WC_La_Poste_Tracking_items', true );
			
			foreach( $shipments as $shipment ) {
				
				// Check last status & last message only
				if ( $shipment === end( $shipments )) {
					$new_shipment_data = $this->get_shipment_tracking( $shipment[ 'tracking_number' ], $order_id );

					$new_shipment_status = null;
					$new_shipment_message = null;

					$current_shipment_status = $shipment['tracking_status'];
					if ( ! empty( $new_shipment_data ) && ! empty( $new_shipment_data->status ) ) {
						$new_shipment_status = $new_shipment_data->status;
					}

					$current_shipment_message = $shipment['tracking_message'];
					if ( ! empty( $new_shipment_data ) && ! empty( $new_shipment_data->message ) ) {
						$new_shipment_message = $new_shipment_data->message;
					}

					// If status and message are different, saving the new shipment into database
					if( $new_shipment_status == $current_shipment_status && $new_shipment_message == $current_shipment_message ) {
						return;
					} else {
						$args = array(
							'tracking_number'          => wc_clean( $shipment[ 'tracking_number' ] ),
							'tracking_status'          => wc_clean( $new_shipment_status ),
							'tracking_type'            => $new_shipment_data->type,
							'tracking_date'            => $new_shipment_data->date,
							'tracking_message'         => $new_shipment_data->message,
							'tracking_link'            => $new_shipment_data->link,
							'date_shipped'             => wc_clean( $shipment[ 'tracking_date' ] )
						);
			
						$this->add_tracking_item( $order_id, $args );
						
					}
				}
				
			}
			
		}		
		
		do_action( 'wc_la_poste_tracking_after_automatic_update_check' );
	
	}

	/**
	 * Prevents data being copied to subscription renewals
	 */
	public function woocommerce_subscriptions_renewal_order_meta_query( $order_meta_query, $original_order_id, $renewal_order_id, $new_order_role ) {
		$order_meta_query .= " AND `meta_key` NOT IN ( '_WC_La_Poste_Tracking_items' )";

		return $order_meta_query;
	}

	/*
	 * Works out the final tracking provider and tracking link and appends then to the returned tracking item
	 *
	*/
	public function get_formatted_tracking_item( $order_id, $tracking_item ) {

		$formatted = array();
		
		$formatted[ 'formatted_tracking_link' ] = $tracking_item[ 'tracking_link' ];

		return $formatted;

	}
	
	/*
	 * Format shipment API response
	 *
	*/
	public function get_formatted_response( $response ) {

		return ( ( str_replace( '_', ' ', $response ) ) );

	}

	/**
	 * Deletes a tracking item from post_meta array
	 *
	 * @param int    $order_id    Order ID
	 * @param string $tracking_id Tracking ID
	 *
	 * @return bool True if tracking item is deleted successfully
	 */
	public function delete_tracking_item( $order_id, $tracking_id ) {

		$tracking_items = $this->get_tracking_items( $order_id );

		$is_deleted = false;
		if ( count( $tracking_items ) > 0 ) {
			foreach( $tracking_items as $key => $item ) {
				if ( $item[ 'tracking_id' ] == $tracking_id ) {
					unset( $tracking_items[ $key ] );
					$is_deleted = true;
					break;
				}
			}
			$this->save_tracking_items( $order_id, $tracking_items );
		}

		return $is_deleted;
	}

	/*
	 * Adds a tracking item to the post_meta array
	 *
	 * @param int   $order_id    Order ID
	 * @param array $tracking_items List of tracking item
	 *
	 * @return array Tracking item
	 */
	public function add_tracking_item( $order_id, $args ) {

		$tracking_item = array();

		$tracking_item[ 'tracking_number' ]          = wc_clean( $args[ 'tracking_number' ] );
		$tracking_item[ 'date_shipped' ]             = wc_clean( strtotime( $args[ 'date_shipped' ] ) );
		$tracking_item[ 'tracking_status' ]          = wc_clean( $args[ 'tracking_status' ] );
		$tracking_item[ 'tracking_type' ]          	 = wc_clean( $args[ 'tracking_type' ] );
		$tracking_item[ 'tracking_date' ]            = wc_clean( $args[ 'tracking_date' ] );
		$tracking_item[ 'tracking_message' ]         = wc_clean( $args[ 'tracking_message' ] );
		$tracking_item[ 'tracking_link' ]            = wc_clean( $args[ 'tracking_link' ] );

		if ( (int) $tracking_item[ 'date_shipped' ] == 0 ) {
			 $tracking_item[ 'date_shipped' ] = time();
		}

		$tracking_item[ 'tracking_id' ] = md5( "{$tracking_item[ 'tracking_number' ]}-{$tracking_item[ 'tracking_status' ]}-{$tracking_item[ 'tracking_type' ]}-{$tracking_item[ 'tracking_date' ]}-{$tracking_item[ 'tracking_message' ]}-{$tracking_item[ 'tracking_link' ]}" . microtime() );

		$tracking_items = $this->get_tracking_items( $order_id );

		$tracking_items[] = $tracking_item;

		$this->save_tracking_items( $order_id, $tracking_items );

		return $tracking_item;

	}

	/**
	 * Saves the tracking items array to post_meta.
	 *
	 * @param int   $order_id       Order ID
	 * @param array $tracking_items List of tracking item
	 *
	 * @return void
	 */
	public function save_tracking_items( $order_id, $tracking_items ) {
		update_post_meta( $order_id, '_WC_La_Poste_Tracking_items', $tracking_items );
	}

	/**
	 * Gets a single tracking item from the post_meta array for an order.
	 *
	 * @param int    $order_id    Order ID
	 * @param string $tracking_id Tracking ID
	 * @param bool   $formatted   Wether or not to reslove the final tracking
	 *                            link and provider in the returned tracking item.
	 *                            Default to false.
	 *
	 * @return null|array Null if not found, otherwise array of tracking item will be returned
	 */
	public function get_tracking_item( $order_id, $tracking_id, $formatted = false ) {
		$tracking_items = $this->get_tracking_items( $order_id, $formatted );

		if ( count( $tracking_items ) ) {
			foreach( $tracking_items as $item ) {
				if ( $item['tracking_id'] === $tracking_id ) {
					return $item;
				}
			}
		}

		return null;
	}

	/*
	 * Gets all tracking items fron the post meta array for an order
	 *
	 * @param int  $order_id  Order ID
	 * @param bool $formatted Wether or not to reslove the final tracking link
	 *                        and provider in the returned tracking item.
	 *                        Default to false.
	 *
	 * @return array List of tracking items
	 */
	public function get_tracking_items( $order_id, $formatted = false ) {

		$tracking_items = get_post_meta( $order_id, '_WC_La_Poste_Tracking_items', true );

		if ( is_array( $tracking_items ) ) {
			if ( $formatted ) {
				foreach( $tracking_items as &$item ) {
					$formatted_item = $this->get_formatted_tracking_item( $order_id, $item );
					$item = array_merge( $item, $formatted_item );
				}
			}
			return $tracking_items;
		}
		else {
			return array();
		}
	}

	/**
	* Gets the absolute plugin path without a trailing slash, e.g.
	* /path/to/wp-content/plugins/plugin-directory
	*
	* @return string plugin path
	*/
	public function get_plugin_path() {
		return $this->plugin_path = untrailingslashit( plugin_dir_path( dirname( __FILE__ ) ) );
	}
}
