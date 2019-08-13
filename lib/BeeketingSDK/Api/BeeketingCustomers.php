<?php
/**
 * Class BeeketingSDK_Api_BeeketingCustomers.
 *
 * @class		BeeketingSDK_Api_BeeketingCustomers
 * @version		1.0.0
 * @author		Beeketing
 */

/**
 * Class BeeketingSDK_Api_BeeketingCustomers.
 *
 * @class		BeeketingSDK_Api_BeeketingCustomers
 * @version		1.0.0
 * @author		Beeketing
 */
class BeeketingSDK_Api_BeeketingCustomers
{
    /**
     * Constructor.
     *
     * @since 2.0.0
     */
    public function __construct()
    {
        // User register
        add_action( 'user_register', array( $this, 'beeketing_add_customer' ), 10, 2 );
        // User Update
        add_action( 'profile_update', array( $this, 'beeketing_update_customer' ), 10, 2 );
        // User Delete
        add_action( 'delete_user', array( $this, 'beeketing_delete_customer' ), 10, 2 );
    }

    /**
     * Processes customer queue.
     *
     * Process the customers that are in the queue.
     *
     * @since 2.0.0
     */
    public function process_queue() {

        $queue = get_option( '_beeketing_queue', array() );

        // Process products
        if ( isset( $queue['customers'] ) && is_array( $queue['customers'] ) ) {
            foreach ( array_slice( $queue['customers'], 0, 225, true ) as $key => $customer ) {

                if ( 'delete' == $customer['action'] ) {
                    $response = $this->beeketing_delete_customer( $customer['id'], null );
                } elseif( 'add' == $customer['action'] ) {
                    $response = $this->beeketing_add_customer( $customer['id'], null );
                } else {
                    $response = $this->beeketing_update_customer( $customer['id'], null );
                }

                if ( ! is_wp_error( $response ) && in_array( $response['response']['code'], array( '200', '201', '204', '400', '404' ) ) ) { // Unset from queue when appropiate
                    unset( $queue['products'][ $key ] );
                }

            }
        }

        update_option( '_beeketing_queue', $queue );

    }

    /**
     * Add customer
     *
     * @since 2.0.0
     * @param $user_id
     * @param $old_user_data
     * @return mixed
     */
    public function beeketing_add_customer( $user_id, $old_user_data )
    {
        $user_data  = $this->get_customer( $user_id );
        $response 	= BeeketingWooCommerce()->api->apiPost( 'customers/create_update', $user_data['customer'] );

        if ( is_wp_error( $response ) || in_array( $response['response']['code'], array( '401', '500', '503' ) ) ) {
            $queue 							= get_option( '_beeketing_queue', array() );
            $queue['customers'][$user_id] 	= array( 'id' => $user_id, 'action' => 'add' );
            update_option( '_beeketing_queue', $queue );
        } elseif ( in_array( $response['response']['code'], array( '200', '201' ) ) ) {
            update_user_meta( $user_id, '_beeketing_last_update', time() );
        }

        return $response;
    }

    /**
     * Update customer
     *
     * @since 2.0.0
     * @param $user_id
     * @param $old_user_data
     * @return mixed
     */
    public function beeketing_update_customer( $user_id, $old_user_data )
    {
        $user_data  = $this->get_customer( $user_id );
        $response 	= BeeketingWooCommerce()->api->apiPost( 'customers/create_update', $user_data['customer'] );

        if ( is_wp_error( $response ) || in_array( $response['response']['code'], array( '401', '500', '503' ) ) ) {
            $queue 							= get_option( '_beeketing_queue', array() );
            $queue['customers'][$user_id] 	= array( 'id' => $user_id, 'action' => 'update' );
            update_option( '_beeketing_queue', $queue );
        } elseif ( in_array( $response['response']['code'], array( '200', '201' ) ) ) {
            update_user_meta( $user_id, '_beeketing_last_update', time() );
        }

        return $response;
    }

    /**
     * Delete customer
     *
     * @since 2.0.0
     * @param $user_id
     * @return mixed
     */
    public function beeketing_delete_customer( $user_id, $reassign )
    {
        $response = BeeketingWooCommerce()->api->apiDelete( 'customers/' . $user_id );

        if ( is_wp_error( $response ) || in_array( $response['response']['code'], array( '401', '500', '503' ) ) ) {
            $queue 							= get_option( '_beeketing_queue', array() );
            $queue['customers'][$user_id] 	= array( 'id' => $user_id, 'action' => 'delete' );
            update_option( '_beeketing_queue', $queue );
        }

        return $response;
    }

    /**
     * Get all customers
     *
     * @since 2.0.0
     * @param array $fields
     * @param array $filter
     * @param int $page
     * @return array
     */
    public function get_customers( $fields = null, $filter = array(), $page = 1 ) {
        $query = beeketing_query_customers( $filter );
        $customers = array();
        foreach ( $query->get_results() as $user_id ) {
            $customers[] =  $this->get_customer( $user_id, $fields );
        }

        return array( 'customers' => $customers );
    }

    /**
     * Get the customer for the given ID
     *
     * @since 2.0.0
     * @param int $id the customer ID
     * @param array $fields
     * @return array
     */
    public function get_customer( $id, $fields = null ) {
        global $wpdb;

        $customer = new WP_User( $id );

        $customer_data = [
            'ref_id'            => $customer->ID,
            'email'             => $customer->user_email,
            'first_name'        => $customer->first_name,
            'last_name'         => $customer->last_name,
            'signed_up_at'      => beeketing_format_datetime( $customer->user_registered, true ),
            'address1'          => $customer->billing_address_1,
            'address2'          => $customer->billing_address_2,
            'city'              => $customer->billing_city,
            'company'           => $customer->billing_company,
            'province'          => $customer->billing_state,
            'zip'               => $customer->billing_postcode,
            'country'           => $customer->billing_country,
        ];
        return array( 'customer' => $customer_data );
    }

}