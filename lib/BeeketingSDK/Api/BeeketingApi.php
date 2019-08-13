<?php

/**
 * Class BeeketingSDK_Api_BeeketingApi.
 *
 * @class		BeeketingSDK_Api_BeeketingApi
 * @version		1.0.0
 * @author		Beeketing
 */
class BeeketingSDK_Api_BeeketingApi
{
    const CART_TOKEN_LIFE_TIME = 31536000;

    const METHOD_GET = 'GET';

    const METHOD_POST = 'POST';

    const METHOD_DELETE = 'DELETE';

    const METHOD_PUT = 'PUT';

    const BEEKETING_SOURCE = 'webhook';

    /**
     * Beeketing Api Key
     *
     * @since 2.0.0
     * @var string $apiKey
     */
    protected $apiKey;

    /**
     * Beeketing Api URL
     *
     * @since 2.0.0
     * @var string $appUrl
     */
    protected $apiUrl;

    /**
     * @since 2.0.0
     * @var $client
     */
    protected $client;

    /**
     * Constructor
     *
     * @since 2.0.0
     * @param $apiKey
     */
    public function __construct( $apiKey )
    {
        $this->apiUrl = BEEKETING_PATH . '/rest-api/v1/';
        $this->apiKey = $apiKey;
        add_action( 'init', array( $this, 'listenCommand' ) );

        add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'listenCartActions' ) );
    }

    /**
     * Set Api Key
     *
     * @since 2.0.0
     * @param $apiKey
     * @return $this
     */
    public function setApiKey( $apiKey )
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Get Api Key
     *
     * @since 2.0.0
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Set api url
     *
     * @since 2.0.0
     * @param $url
     * @return $this
     */
    public function setApiUrl( $url )
    {
        $this->apiUrl = $url;

        return $this;
    }

    /**
     * Get api url
     *
     * @since 2.0.0
     * @return string
     */
    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    /**
     * Set http client
     *
     * @since 2.0.0
     * @param $client
     * @return $this
     */
    public function setHttpClient( $client )
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get http client
     *
     * @since 2.0.0
     * @return mixed
     */
    public function getHttpClient()
    {
        return $this->client;
    }

    /**
     * Listen command
     *
     * @since 2.0.0
     * Listen to requests from Beeketing
     */
    public function listenCommand()
    {
        if ( ! isset( $_GET['bk_send_action'] ) ) {
            return;
        }

        $command = $_GET['bk_send_action'];

        if ( ! isset( $_GET['access_token'] ) ) {
            wp_send_json_error( [ 'message' => 'Access token not found' ] );
            die;
        }

        $access_token = $_GET['access_token'];

        if ( $access_token != $this->apiKey ) {
            wp_send_json_error( [ 'message' => 'Invalid access token' ] );
            die;
        }

        error_reporting(0);


        switch ( $command ) {
            case 'test-request':
                wp_send_json_success([
                    'apache_request_headers'    => function_exists('apache_request_headers'),
                    'getallheaders'             => function_exists('getallheaders'),
                    'header'                    => getallheaders(),
                    'post'                      => $_POST,
                    'get'                       => $_GET
                ]);

                die;
                break;

            case 'sync-products':
                if ( 1 == get_option( 'beeketing_completed_initial_product_sync', 0 ) ) {
                    // Reset complete syncing flag to wake it up
                    update_option( 'beeketing_completed_initial_product_sync', 0 );
                    update_option( 'beeketing_current_sync_time', time() );
                }
                wp_send_json( [ 'success' => true ] );
                die;
                break;

            case 'sync-collections':
                if ( 1 == get_option( 'beeketing_completed_initial_collection_sync', 0 ) ) {
                    // Reset complete syncing flag to wake it up
                    update_option( 'beeketing_completed_initial_collection_sync', 0 );
                    update_option( 'beeketing_current_sync_time', time() );
                }
                wp_send_json( [ 'success' => true ] );
                die;
                break;

            case 'sync-orders':
                if ( 1 == get_option( 'beeketing_completed_initial_order_sync', 0 ) ) {
                    // Reset complete syncing flag to wake it up
                    update_option( 'beeketing_completed_initial_order_sync', 0 );
                    update_option( 'beeketing_current_sync_time', time() );
                }
                wp_send_json( [ 'success' => true ] );
                die;
                break;

            case 'sync-collects':
                if ( 1 == get_option( 'beeketing_completed_initial_order_sync', 0 ) ) {
                    // Reset complete syncing flag to wake it up
                    update_option( 'beeketing_completed_initial_order_sync', 0 );
                    update_option( 'beeketing_current_sync_time', time() );
                }
                wp_send_json( [ 'success' => true ] );
                die;
                break;

            case 'sync-customers':
                if ( 1 == get_option( 'beeketing_completed_initial_customer_sync', 0 ) ) {
                    // Reset complete syncing flag to wake it up
                    update_option( 'beeketing_completed_initial_customer_sync', 0 );
                    update_option( 'beeketing_current_sync_time', time() );
                }
                wp_send_json( [ 'success' => true ] );
                die;
                break;

            case 'create-variation':
                if ( ! isset( $_GET['id'] ) ) {
                    wp_send_json_error( [ 'message' => 'Product id not found' ] );
                    die;
                }

                $header = getallheaders();

                if ( isset( $_POST['bk_data'] ) ) {
                    $data = $_POST['bk_data'];
                } elseif ( isset( $header['X-BK-DATA'] ) ) {
                    $data = json_decode( $header['X-BK-DATA'], true );
                } else {
                    $data = [];
                }

                $variation_data = BeeketingWooCommerce()->products->create_gift_variation( $_GET['id'], $data );
                if ( isset( $variation_data['success'] ) && $variation_data['success'] ) {
                    wp_send_json_success( $variation_data['data'] );
                    die;
                }

                wp_send_json( $variation_data );

                die;
                break;

            case 'update-variation':
                if ( ! isset( $_GET['variation_id'] ) ) {
                    wp_send_json_error( [ 'message' => 'Variation id not found' ] );
                    die;
                }

                $header = getallheaders();

                if ( isset( $_POST['bk_data'] ) ) {
                    $data = $_POST['bk_data'];
                } elseif ( isset( $header['X-BK-DATA'] ) ) {
                    $data = json_decode( $header['X-BK-DATA'], true );
                } else {
                    $data = [];
                }

                $variation_data = BeeketingWooCommerce()->products->update_variation( $_GET['variation_id'], $data );
                if ( isset( $variation_data['success'] ) && $variation_data['success'] ) {
                    wp_send_json_success( $variation_data['data'] );
                    die;
                }

                wp_send_json( $variation_data );

                die;
                break;

            case 'get-variation':
                if ( ! isset( $_GET['id'] ) ) {
                    wp_send_json_error( [ 'message' => 'Variation id not found' ] );
                    die;
                }
                $product_variation = $product_variation = get_post( $_GET['id'] );
                if ( $product_variation->post_type != 'product_variation' && $product_variation->post_parent != 0 ) {
                    wp_send_json_error( [ 'message' => 'Not a variant' ] );
                    die;
                }

                if ( $product_variation->post_parent == 0 ) {
                    $product = wc_get_product( $_GET['id'] );
                    $variation_data = BeeketingWooCommerce()->products->get_formatted_variant( $product, $product );
                } else {
                    $variation_data = BeeketingWooCommerce()->products->get_variant_data( $product_variation );
                }
                wp_send_json_success( $variation_data );

                die;
                break;

            case 'delete-variation':
                if ( ! isset( $_GET['variation_id'] ) ) {
                    wp_send_json_error( [ 'message' => 'Variant id not found' ] );
                    die;
                }
                $response = BeeketingWooCommerce()->products->beeketing_remove_variation( $_GET['variation_id'] );
                if ( isset( $response['success'] ) && $response['success'] ) {
                    wp_send_json( [ 'success' => true ] );
                    die;
                }

                wp_send_json( $response );

                die;
                break;

            case 'get-products':
                $params     = $_GET;
                $params     = $this->cleanUpParams( $params );
                $response   = BeeketingWooCommerce()->products->get_products( null, null, $params );

                wp_send_json_success( $response['products'] );
                die;
                break;

            case 'get-product':
                if ( ! isset( $_GET['id'] ) ) {
                    wp_send_json_error( [ 'message' => 'Id not found' ] );
                    die;
                }
                $product = BeeketingWooCommerce()->products->get_formatted_product( (int) $_GET['id'] );
                if (!$product) {
                    wp_send_json_error( [ 'Product not found' ] );
                    die;
                }

                wp_send_json_success( $product['product'] );
                die;
                break;

            case 'get-collections':
                $params     = $_GET;
                $params     = $this->cleanUpParams($params);
                $response   = BeeketingWooCommerce()->collections->get_product_categories( null, $params );

                wp_send_json_success( $response['product_categories'] );
                die;
                break;

            case 'update-site-url':
                BeeketingWooCommerce()->shop->update_shop_info_to_beeketing();
                wp_send_json_success();
                die;
                break;

            case 'share-facebook':
                if ( ! isset( $_GET['offer_id'] ) || ! isset( $_GET['post_id'] ) ) {
                    wp_send_json_error( [ 'message' => 'Id not found' ] );
                    die;
                }

                $headers = array( 'Content-Type' => 'application/json', 'X-Beeketing-Key' => $this->apiKey, 'X-Beeketing-Source' => self::BEEKETING_SOURCE );

                $apiResponse = wp_remote_post( BEEKETING_PATH . '/cboost/offers/shared?offer_id=' . $_GET['offer_id'] . '&post_id=' . $_GET['post_id'], array(
                        'method'        => self::METHOD_PUT,
                        'timeout'		=> 45,
                        'redirection'	=> 5,
                        'httpversion'	=> '1.0',
                        'blocking'		=> true,
                        'headers'		=> $headers,
                        'body'			=> json_encode( [] ),
                        'cookies'		=> array()
                    )
                );

                if ( is_wp_error( $apiResponse ) ) {
                    return $apiResponse;
                } else {
                    $response['response']	= $apiResponse['response'];
                    $response['body']		= $apiResponse['body'];
                    echo ( json_decode( $apiResponse['body'], true ) );
                }
                die;
                break;

            default:
                wp_send_json_error( [ 'message' => 'Action not found' ] );
                die;
                break;
        }
    }

    /**
     * Remove cart
     *
     * @since 2.0.0
     */
    public function listenCartActions()
    {
        if ( ! isset( $_GET['bk_cart_action'] ) ) {
            return;
        }

        $command = $_GET['bk_cart_action'];

        if ( ! isset( $_GET['access_token'] ) ) {
            wp_send_json_error( [ 'message' => 'Access token not found' ] );
            die;
        }

        $access_token = $_GET['access_token'];

        if ( $access_token != $this->apiKey ) {
            wp_send_json_error( [ 'message' => 'Invalid access token' ] );
            die;
        }

        error_reporting(0);

        switch ( $command ) {
            case 'add':
                if ( ! isset( $_POST['product_id'] ) ) {
                    wp_send_json_error( [ 'message' => 'product id not found' ] );
                    die;
                }

                if ( is_array( $_POST['product_id'] ) ) {
                    foreach ( $_POST['product_id'] as $key =>  $product_id ) {
                        $data = [
                            'product_id'    => (int) $product_id,
                            'variation_id'  => isset( $_POST['id'][ $key ] ) ? (int) $_POST['id'][ $key ] : null,
                            'quantity'      => isset( $_POST['qty'][ $key ]) ? (int) $_POST['qty'][ $key ] : 1,
                            'attributes'    => isset( $_POST['attributes'][ $key ]) ? $_POST['attributes'][ $key ] : []
                        ];

                        BeeketingWooCommerce()->cart->beeketing_add_to_cart( $data );
                    }

                    $cartData = BeeketingWooCommerce()->cart->get_formatted_cart_data();
                    wp_send_json( $cartData );
                    die;
                } else {
                    $data = [
                        'product_id'    => (int) $_POST['product_id'],
                        'variation_id'  => isset( $_POST['id'] ) ? (int) $_POST['id'] : null,
                        'quantity'      => isset( $_POST['qty'] ) ? (int) $_POST['qty'] : 1,
                        'attributes'    => isset( $_POST['attributes'] ) ? $_POST['attributes'] : []
                    ];

                    $variation = BeeketingWooCommerce()->cart->beeketing_add_to_cart( $data );
                    if ( $variation ) {
                        wp_send_json( $variation );
                        die;
                    }
                }

                wp_send_json_error();
                die;
                break;

            case 'remove':
                if ( ! isset( $_GET['cart_item_key'] ) ) {
                    wp_send_json_error( [ 'message' => 'Cart item key not found' ] );
                    die;
                }

                $result = BeeketingWooCommerce()->cart->remove_from_cart( $_GET['cart_item_key'] );
                if ( $result ) {
                    wp_send_json_success();
                    die;
                }
                wp_send_json_error();
                break;

            default:
                wp_send_json_error( [ 'message' => 'Action not found' ] );
                die;
                break;
        }


        if ( ! isset( $_GET['bk_remove_cart'] ) ) {
            return;
        }

        if ( ! isset( $_GET['access_token'] ) ) {
            wp_send_json_error( [ 'message' => 'Access token not found' ] );
            die;
        }

        $access_token = $_GET['access_token'];

        if ( $access_token != $this->apiKey ) {
            wp_send_json_error( [ 'message' => 'Invalid access token' ] );
            die;
        }

        $result = BeeketingWooCommerce()->cart->remove_from_cart( $_GET['bk_remove_cart'] );
        if ( $result ) {
            wp_send_json_success();
            die;
        }
        wp_send_json_error();
        die;
    }

    /**
     * Clean up params
     *
     * @param array $params
     * @return array
     */
    public function cleanUpParams( $params = [] )
    {
        if ( isset( $params['bk_send_action'] ) ) {
            unset( $params['bk_send_action'] );
        }
        if ( isset( $params['access_token'] ) ) {
            unset( $params['access_token'] );
        }

        return $params;
    }

    /**
     * API Get
     *
     * @since 2.0.0
     * @param $apiMethod
     * @param array $args
     * @return mixed
     */
    public function apiGet( $apiMethod, $args = array() )
    {
        $headers = array( 'Content-Type' => 'application/json', 'X-Beeketing-Key' => $this->apiKey, 'X-Beeketing-Source' => self::BEEKETING_SOURCE );

        $apiResponse = wp_remote_get( $this->apiUrl . $apiMethod . '.json', array(
                'timeout'		=> 5,
                'redirection'	=> 5,
                'httpversion'	=> '1.0',
                'blocking'		=> true,
                'headers'		=> $headers,
                'body'			=> json_encode( $args ),
                'cookies'		=> array()
            )
        );

        if ( is_wp_error( $apiResponse ) ) {
            return $apiResponse;
        } else {
            $response['response']	= $apiResponse['response'];
            $response['body']		= $apiResponse['body'];
            return $response;
        }
    }

    /**
     * API Post
     *
     * @since 2.0.0
     * @param $apiMethod
     * @param array $args
     * @return mixed
     */
    public function apiPost( $apiMethod, $args = array() )
    {
        $headers = array( 'Content-Type' => 'application/json', 'X-Beeketing-Key' => $this->apiKey, 'X-Beeketing-Source' => self::BEEKETING_SOURCE );

        $apiResponse = wp_remote_post( $this->apiUrl . $apiMethod . '.json', array(
                'method'		=> self::METHOD_POST,
                'timeout'		=> 45,
                'redirection'	=> 5,
                'httpversion'	=> '1.0',
                'blocking'		=> true,
                'headers'		=> $headers,
                'body'			=> json_encode( $args ),
                'cookies'		=> array()
            )
        );

        if ( is_wp_error( $apiResponse ) ) {
            return $apiResponse;
        } else {
            $response['response']	= $apiResponse['response'];
            $response['body']		= $apiResponse['body'];
            return $response;
        }
    }

    /**
     * API Put
     *
     * @since 2.0.0
     * @param $apiMethod
     * @param array $args
     * @return mixed
     */
    public function apiPut( $apiMethod, $args = array() )
    {
        $headers = array( 'Content-Type' => 'application/json', 'X-Beeketing-Key' => $this->apiKey, 'X-Beeketing-Source' => self::BEEKETING_SOURCE );

        $apiResponse = wp_remote_post( $this->apiUrl . $apiMethod . '.json', array(
                'method'        => self::METHOD_PUT,
                'timeout'		=> 45,
                'redirection'	=> 5,
                'httpversion'	=> '1.0',
                'blocking'		=> true,
                'headers'		=> $headers,
                'body'			=> json_encode( $args ),
                'cookies'		=> array()
            )
        );

        if ( is_wp_error( $apiResponse ) ) {
            return $apiResponse;
        } else {
            $response['response']	= $apiResponse['response'];
            $response['body']		= $apiResponse['body'];
            return $response;
        }
    }

    /**
     * API Delete
     *
     * @since 2.0.0
     * @param $apiMethod
     * @param array $args
     * @return mixed
     */
    public function apiDelete( $apiMethod, $args = array() )
    {
        $headers = array( 'Content-Type' => 'application/json', 'X-Beeketing-Key' => $this->apiKey, 'X-Beeketing-Source' => self::BEEKETING_SOURCE );

        $apiResponse = wp_remote_post( $this->apiUrl . $apiMethod . '.json', array(
                'method'        => self::METHOD_DELETE,
                'timeout'		=> 45,
                'redirection'	=> 5,
                'httpversion'	=> '1.0',
                'blocking'		=> true,
                'headers'		=> $headers,
                'body'			=> json_encode( $args ),
                'cookies'		=> array()
            )
        );

        if ( is_wp_error( $apiResponse ) ) {
            return $apiResponse;
        } else {
            $response['response']	= $apiResponse['response'];
            $response['body']		= $apiResponse['body'];
            return $response;
        }
    }

    /**
     * Check and add cart token
     *
     * @since 2.0.0
     */
    public function checkAndAddCartToken()
    {
        if ( ! isset( $_COOKIE['beeketing-cart-token'] ) ) {
            setcookie( 'beeketing-cart-token', uniqid( $this->apiKey, true ), time() + self::CART_TOKEN_LIFE_TIME, '/' );
        }
    }
}