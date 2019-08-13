<?php

/**
 * Class BeeketingSDK_Api_BeeketingShop.
 *
 * @class		BeeketingSDK_Api_BeeketingShop
 * @version		1.0.0
 * @author		Beeketing
 */
class BeeketingSDK_Api_BeeketingShop
{
    /**
     * Constructor.
     *
     * @since 2.0.0
     */
    public function __construct()
    {

        add_action( 'init', array( $this, 'init_shop_info' ) );
    }

    /**
     * Update shop info to beeketing
     *
     * @since 2.0.0
     */
    public function update_shop_info_to_beeketing()
    {
        $formattedPrice = wc_price( 11.11 );
        $formattedPrice = str_replace( '11.11', '{{amount}}', $formattedPrice );

        $args = [
            'absolute_path'     => get_site_url(),
            'currency_format'   => $formattedPrice,
            'currency'          => get_woocommerce_currency(),
        ];

        $response 	= BeeketingWooCommerce()->api->apiPut( 'shops', $args );

        return $response;
    }

    /**
     * Init shop info
     *
     * @since 2.0.0
     */
    public function init_shop_info()
    {
        // Update shop domain
        if ( get_option( 'beeketing_completed_update_shop_info', 0 ) != 1 ) {

            $response 	= $this->update_shop_info_to_beeketing();
            if ( in_array( $response['response']['code'], array( '200', '201' ) ) ) {
                update_option( 'beeketing_completed_update_shop_info', 1 );
            }
        }
    }
}