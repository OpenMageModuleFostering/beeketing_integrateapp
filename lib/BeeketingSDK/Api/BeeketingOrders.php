<?php

/**
 * Class BeeketingSDK_Api_BeeketingOrders.
 *
 * @class		BeeketingSDK_Api_BeeketingOrders
 * @version		1.0.0
 * @author		Beeketing
 */
class BeeketingSDK_Api_BeeketingOrders
{
    /**
     * Constructor.
     *
     * @since 2.0.0
     */
    public function __construct()
    {
        // Save user token on checkout
        add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'order_save_user_token' ), 10, 2 );

        // Check product stock, if empty update product
        add_action( 'woocommerce_reduce_order_stock', array( $this, 'update_products_in_order' ), 10, 1 );

    }

    /**
     * Processes order queue.
     *
     * Process the orders that are in the queue.
     *
     * @since 2.0.0
     */
    public function process_queue() {

        $queue = get_option( '_beeketing_queue', array() );

        // Process products
        if ( isset( $queue['orders'] ) && is_array( $queue['orders'] ) ) {
            foreach ( array_slice( $queue['orders'], 0, 225, true ) as $key => $order ) {

                if ( 'add' == $order['action'] ) {
                    $response = $this->add_order_to_beeketing( $order['id'] );
                }

                if ( isset( $response ) && ! is_wp_error( $response ) && in_array( $response['response']['code'], array( '200', '201', '204', '400', '404' ) ) ) { // Unset from queue when appropiate
                    unset( $queue['products'][ $key ] );
                }

            }
        }

        update_option( '_beeketing_queue', $queue );

    }


    /**
     * Save user token.
     *
     * Save the user token from the beeketing cookie at checkout.
     * After save it will immediately be deleted. When deleted it will
     * automatically re-generate a new one to track the new purchase flow.
     *
     * @since 2.0.0
     *
     * @param int 	$order_id	ID of the order that is being processed.
     * @param array	$posted		List of $_POST values.
     */
    public function order_save_user_token( $order_id, $posted )
    {
        if ( isset( $_COOKIE['beeketing-cart-token'] ) ) {
            update_post_meta( $order_id, '_beeketing_cart_token', $_COOKIE['beeketing-cart-token'] );
            // Delete cart token
            setcookie( 'beeketing-cart-token', '', time() - BeeketingSDK_Api_BeeketingApi::CART_TOKEN_LIFE_TIME + 1, '/' );
        }

        $this->add_order_to_beeketing( $order_id );
    }

    /**
     * Update order
     *
     * @since 2.0.0
     * @param $order_id
     * @return mixed
     */
    public function add_order_to_beeketing( $order_id )
    {
        $order_data = $this->get_formatted_order( $order_id );

        $response = BeeketingWooCommerce()->api->apiPost( 'orders/create_update', $order_data['order'] );

        if ( is_wp_error( $response ) || in_array( $response['response']['code'], array( '401', '500', '503' ) ) ) {
            $queue 							= get_option( '_beeketing_queue', array() );
            $queue['orders'][ $order_id ] 	= array( 'id' => $order_id, 'action' => 'add' );
            update_option( '_beeketing_queue', $queue );
        } elseif ( in_array( $response['response']['code'], array( '200', '201' ) ) ) {
            update_post_meta( $order_id, '_beeketing_last_update', time() );
        }

        return $response;
    }


    /**
     * Update products.
     *
     * Maybe send a update to Beeketing. Check if the product is stock-managed,
     * when it is, a update will be send to Beeketing to make sure the product
     * is up to date.
     *
     * @since 2.0.0
     *
     * @param	WC_Order $order Order object.
     */
    public function update_products_in_order( $order )
    {
        foreach ( $order->get_items() as $item ) {

            if ( $item['product_id'] > 0 ) {
                $_product = $order->get_product_from_item( $item );

                if ( $_product && $_product->exists() && $_product->managing_stock() ) {
                    // Sync this product
                    BeeketingWooCommerce()->products->update_product_to_beeketing( $_product->id );

                }

            }

        }
    }

    /**
     * Get all orders
     *
     * @since 2.0.0
     * @param string $fields
     * @param array $filter
     * @param string $status
     * @param int $page
     * @return array
     */
    public function get_orders( $fields = null, $filter = array(), $status = null, $page = 1 ) {
        if ( ! empty( $status ) ) {
            $filter['status'] = $status;
        }
        $filter['page'] = $page;
        $query          = beeketing_query_orders( $filter );
        $orders         = array();
        foreach ( $query->posts as $order_id ) {

            $orders[] = $this->get_formatted_order( $order_id, $fields, $filter );
        }

        return array( 'orders' => $orders );
    }

    /**
     * Get the order for the given ID
     *
     * @since 2.0.0
     * @param int $id the order ID
     * @param array $fields
     * @param array $filter
     * @return array
     */
    public function get_formatted_order( $id, $fields = null, $filter = array() )
    {
        // Get the decimal precession
        $dp         = ( isset( $filter['dp'] ) ? intval( $filter['dp'] ) : 2 );
        $order      = wc_get_order( $id );
        $order_data = [
            'ref_id'            => $order->id,
            'contact_ref_id'    => $order->get_user_id(),
            'financial_status'  => $order->get_status(),
            'line_items'        => [],
            'cart_token'        => get_post_meta( $id, '_beeketing_cart_token', true ),
            'total_price'       => $order->get_total(),
            'subtotal_price'    => $order->get_subtotal()
        ];

        // add line items
        foreach ( $order->get_items() as $item_id => $item ) {
            $product     = $order->get_product_from_item( $item );
            $product_id  = null;
            $product_sku = null;
            // Check if the product exists.
            if ( is_object( $product ) ) {
                $product_sku = $product->get_sku();
            }


            $order_data['line_items'][] = [
                'ref_id'                => $item_id,
                'price'                 => wc_format_decimal( $order->get_item_total( $item, false, false ), $dp ),
                'sku'                   => $product_sku,
                'product_ref_id'        => $product->id,
                'variant_id'            => ( isset( $product->variation_id ) ) ? $product->variation_id : null,
                'fulfillable_quantity'  => wc_stock_amount( $item['qty'] ),
            ];
        }

        return array( 'order' => $order_data );
    }
}