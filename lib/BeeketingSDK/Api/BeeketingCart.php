<?php


/**
 * Class BeeketingSDK_Api_BeeketingCart.
 *
 * @class		BeeketingSDK_Api_BeeketingCart
 * @version		1.0.0
 * @author		Beeketing
 */
class BeeketingSDK_Api_BeeketingCart
{
    /**
     * Constructor.
     *
     * @since 2.0.0
     */
    public function __construct()
    {
        add_filter( 'add_to_cart_fragments', array( $this, 'header_add_to_cart_fragment' ), 10, 1 );

        add_action( 'woocommerce_add_to_cart', array( $this, 'listen_add_cart' ), 10, 6 );

        add_action( 'woocommerce_cart_item_removed', array( $this, 'listen_cart_item_removed' ), 10, 2 );

        add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'listen_cart_item_quantity_update' ), 10, 3 );
    }

    /**
     * Listen add to cart event
     *
     * @since 2.0.0
     * @param $cart_item_key
     * @param $product_id
     * @param $quantity
     * @param $variation_id
     * @param $variation
     * @param $cart_item_data
     */
    public function listen_add_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data )
    {
        $item_details = [
            'cart_item_key'     => $cart_item_key,
            'product_id'        => $product_id,
            'quantity'          => $quantity,
            'variation_id'      => $variation_id,
            'cart_item_data'    => $cart_item_data,
        ];
        if (!isset($_COOKIE['beeketing-last-added-items'])) {
            setcookie('beeketing-last-added-items', json_encode($item_details), time() + 3600, '/');
        }
    }

    /**
     * Listen cart item removed
     *
     * @since 2.0.0
     * @param $cart_item_key
     * @param $cart
     */
    public function listen_cart_item_removed( $cart_item_key, $cart )
    {
        if ( ! isset( $cart->removed_cart_contents[ $cart_item_key ] ) ) {
            return;
        }

        $removed_item = $cart->removed_cart_contents[ $cart_item_key ];

        $_product = $removed_item['data']->post;
        $price = $removed_item['data']->price;
        unset( $removed_item['data'] );

        $item_details = array_merge([
            'product_token' => $cart_item_key,
            'url'           => $_product->guid,
            'title'         => $_product->post_title,
            'price'         => $price,
        ], $removed_item);

        if ( ! isset( $_COOKIE['beeketing-cart-last-deleted-items'] ) ) {
            setcookie( 'beeketing-cart-last-deleted-items', json_encode($item_details), time() + 3600, '/' );
        }
    }

    /**
     * Listen cart item quantity update
     *
     * @since 2.0.0
     * @param $cart_item_key
     * @param $quantity
     * @param $old_quantity
     */
    public function listen_cart_item_quantity_update( $cart_item_key, $quantity, $old_quantity )
    {
        if ( $quantity > $old_quantity ) {
            if ( ! isset($_COOKIE['beeketing-last-added-items'] ) ) {
                $item_details = [
                    'cart_item_key' => $cart_item_key,
                    'quantity'      => $quantity - $old_quantity,
                ];
                setcookie( 'beeketing-last-added-items', json_encode($item_details), time() + 3600, '/' );
            }
        } else {
            if ( ! isset( $_COOKIE['beeketing-last-removed-items'] ) ) {
                $item_details = [
                    'cart_item_key' => $cart_item_key,
                    'quantity'      => $old_quantity - $quantity,
                ];
                setcookie( 'beeketing-last-removed-items', json_encode($item_details), time() + 3600, '/' );
            }
        }

    }

    /**
     * Add to cart fragment
     *
     * @since 2.0.0
     * @param $fragments
     * @return mixed
     */
    public function header_add_to_cart_fragment( $fragments )
    {
        $fragments['beeketing_cart_data'] = $this->get_formatted_cart_data();
        return $fragments;
    }

    /**
     * Get formatted cart data
     *
     * @since 2.0.0
     * @return array
     */
    public function get_formatted_cart_data()
    {
        // Init cart data
        global $woocommerce;
        $cart   = $woocommerce->cart;
        $items  = $cart->get_cart();

        $cartData = [];
        $itemCount = 0;
        $totalPrice = 0;
        foreach ( $items as $item => $values ) {
            $_product   = $values['data']->post;
            $price      = $values['data']->price;
            $itemCount  += $values['quantity'];
            $totalPrice += $price * $values['quantity'];
            if ( $values['variation_id'] > 0 ) {
                $variation  = get_post( $values['variation_id'] );
                $title      = $variation->post_title;
            } else {
                $title      = $_product->post_title;
            }
            unset($values['data']);
            $image_data = wp_get_attachment_image_src( get_post_thumbnail_id( $_product->ID ), 'thumbnail' );
            $cartData['items'][$item] = array_merge([
                'product_token' => $item,
                'url'           => get_permalink( $_product->ID ),
                'title'         => $title,
                'price'         => $price,
                'image'         => isset( $image_data[0] ) ? $image_data[0] : '',
            ], $values);
        }

        $cartData['item_count']     = $itemCount;
        $cartData['total_price']    = $totalPrice;
        $cartData['cart_url']       = $cart->get_cart_url();
        $cartData['checkout_url']   = $cart->get_checkout_url();
        $cartData['shop_url']       = get_permalink( woocommerce_get_page_id('shop') );
        if ( isset( $_COOKIE['beeketing-cart-token'] ) ) {
            $cartData['token'] = $_COOKIE['beeketing-cart-token'];
        }

        return $cartData;
    }

    /**
     * Add to cart
     *
     * @since 2.0.0
     * @param $data
     * @return bool
     */
    public function beeketing_add_to_cart( $data )
    {
        if ( empty( $data['product_id'] ) || ! is_numeric( $data['product_id'] ) ) {
            return false;
        }

        $product_id          = absint( $data['product_id'] );
        $adding_to_cart      = wc_get_product( $product_id );

        if ( ! $adding_to_cart ) {
            return false;
        }

        if ( is_array( $data['attributes'] ) ) {
            $attributes = $data['attributes'];
        } else {
            $attributes = json_decode( stripslashes( $data['attributes'] ), true );
        }

        foreach ( $attributes as $attribute ) {
            $data[ 'attribute_' . sanitize_title( $attribute['name'] ) ] = $attribute['option'];
        }

        $add_to_cart_handler = $adding_to_cart->product_type;
        $data['price'] = $adding_to_cart->get_price();

        // Variable product handling
        if ( 'variable' === $add_to_cart_handler ) {
            $was_added_to_cart = self::add_to_cart_handler_variable( $product_id, $data );

            // Grouped Products
        } elseif ( 'grouped' === $add_to_cart_handler ) {
            $was_added_to_cart = self::add_to_cart_handler_grouped( $product_id, $data );

            // Custom Handler
        } else {
            $was_added_to_cart = self::add_to_cart_handler_simple( $product_id, $data );
        }

        // If we added the product to the cart we can now optionally do a redirect.
        if ( $was_added_to_cart && wc_notice_count( 'error' ) === 0 ) {
            return $was_added_to_cart;
        }

        return false;
    }

    /**
     * Remove item from cart
     *
     * @since 2.0.0
     * @param $cart_item_key
     * @return bool
     */
    public function remove_from_cart( $cart_item_key )
    {
        $cart_item_key = sanitize_text_field( $cart_item_key );

        if ( $cart_item = WC()->cart->get_cart_item( $cart_item_key ) ) {
            WC()->cart->remove_cart_item( $cart_item_key );
            return true;
        }

        return false;
    }

    /**
     * Handle adding simple products to the cart
     *
     * @since 2.0.0
     * @param int $product_id
     * @param $data
     * @return bool success or not
     */
    private static function add_to_cart_handler_simple( $product_id, $data ) {
        $quantity 			= empty( $data['quantity'] ) ? 1 : wc_stock_amount( $data['quantity'] );
        $variation_id       = empty( $data['variation_id'] ) ? '' : absint( $data['variation_id'] );
        $passed_validation 	= true;

        if ( $data['variation_id'] ) {
            if ( isset( $data['price'] ) && $data['price'] == 0 ) {
                $attributes = [
                    'type' => 'Free Gift'
                ];
            } else {
                $attributes = [
                    'type' => 'BK Variation'
                ];
            }

        } else {
            $attributes = [];
        }

        if ( $passed_validation && WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $attributes ) !== false ) {
            wc_add_to_cart_message( $product_id );
            return true;
        }
        return false;
    }

    /**
     * Handle adding grouped products to the cart
     *
     * @since 2.0.0
     * @param int $product_id
     * @param $data
     * @return bool success or not
     */
    private static function add_to_cart_handler_grouped( $product_id, $data ) {
        $was_added_to_cart = false;
        $added_to_cart     = array();

        if ( ! empty( $data['quantity'] ) && is_array( $data['quantity'] ) ) {
            $quantity_set = false;

            foreach ( $data['quantity'] as $item => $quantity ) {
                if ( $quantity <= 0 ) {
                    continue;
                }
                $quantity_set = true;

                // Add to cart validation
                $passed_validation 	= apply_filters( 'woocommerce_add_to_cart_validation', true, $item, $quantity );

                if ( $passed_validation && WC()->cart->add_to_cart( $item, $quantity ) !== false ) {
                    $was_added_to_cart = true;
                    $added_to_cart[]   = $item;
                }
            }

            if ( ! $was_added_to_cart && ! $quantity_set ) {
                wc_add_notice( __( 'Please choose the quantity of items you wish to add to your cart&hellip;', 'woocommerce' ), 'error' );
            } elseif ( $was_added_to_cart ) {
                wc_add_to_cart_message( $added_to_cart );
                return true;
            }

        } elseif ( $product_id ) {
            /* Link on product archives */
            wc_add_notice( __( 'Please choose a product to add to your cart&hellip;', 'woocommerce' ), 'error' );
        }
        return false;
    }

    /**
     * Handle adding variable products to the cart
     *
     * @since 2.0.0
     * @param int $product_id
     * @param $data
     * @return bool success or not
     */
    private static function add_to_cart_handler_variable( $product_id, $data ) {
        $adding_to_cart     = wc_get_product( $product_id );
        $variation_id       = empty( $data['variation_id'] ) ? '' : absint( $data['variation_id'] );
        $quantity           = empty( $data['quantity'] ) ? 1 : wc_stock_amount( $data['quantity'] );
        $missing_attributes = array();
        $variations         = array();
        $attributes         = $adding_to_cart->get_attributes();
        $variation          = wc_get_product( $variation_id );

        // Verify all attributes
        foreach ( $attributes as $attribute ) {
            if ( ! $attribute['is_variation'] ) {
                continue;
            }

            $taxonomy = 'attribute_' . sanitize_title( $attribute['name'] );

            if ( isset( $data[ $taxonomy ] ) ) {

                // Get value from post data
                if ( $attribute['is_taxonomy'] ) {
                    // Don't use wc_clean as it destroys sanitized characters
                    $value = sanitize_title( stripslashes( $data[ $taxonomy ] ) );
                } else {
                    $value = wc_clean( stripslashes( $data[ $taxonomy ] ) );
                }

                // Get valid value from variation
                $valid_value = $variation->variation_data[ $taxonomy ];

                // Allow if valid
                if ( '' === $valid_value || $valid_value === $value ) {
                    $variations[ $taxonomy ] = $value;
                    continue;
                }

            } else {
                $missing_attributes[] = wc_attribute_label( $attribute['name'] );
            }
        }

        if ( $missing_attributes ) {
            return false;
        } elseif ( empty( $variation_id ) ) {
            return false;
        } else {
            // Add to cart validation
            $passed_validation 	= true;

            if ( $passed_validation && WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variations ) !== false ) {
                wc_add_to_cart_message( $product_id );
                return true;
            }
        }
        return false;
    }

}