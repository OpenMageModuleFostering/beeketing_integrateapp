<?php
/**
 * Class BeeketingSDK_Api_BeeketingProducts.
 *
 * @class		BeeketingSDK_Api_BeeketingProducts
 * @version		1.0.0
 * @author		Beeketing
 */
class BeeketingSDK_Api_BeeketingProducts
{
    /**
     * Constructor.
     *
     * @since 2.0.0
     */
    public function __construct() {

        // Create/Update product
        add_action( 'save_post', array( $this, 'add_product_to_beeketing' ), 20 );

        // Trash product
        add_action( 'trash_product', array( $this, 'delete_product_from_beeketing' ), 10, 2 );

        // Recover product
        add_action( 'untrashed_post', array( $this, 'beeketing_recover_product' ) );
    }

    /**
     * Add / Edit variation
     *
     * @since 2.0.0
     * @param $post_id
     * @param $data
     * @return bool
     */
    public function beeketing_add_variation( $post_id, $data )
    {
        return beeketing_save_variations( $post_id, $data );
    }

    /**
     * Delete variants
     *
     * @param $variation_id
     * @return bool
     */
    public function beeketing_remove_variation( $variation_id )
    {
        $variation = get_post( $variation_id );

        if ( !$variation ) {
            return [
                'success' => false,
                'message' => 'Variant not found',
            ];
        }

        if ( 'product_variation' == $variation->post_type ) {
            wp_delete_post( $variation_id );
        } else {
            return [
                'success' => false,
                'message' => 'Invalid product type',
            ];
        }

        return [
            'success' => true,
        ];
    }


    /**
     * Create variation product for given item.
     *
     * @since  2.0.0
     * @access public
     *
     * @param  integer $product_id Product to create variation of
     *
     * @param $data
     * @return int Product variation id
     */
    public function create_gift_variation( $product_id, $data )
    {
        if ( ! isset( $data[ 'option1' ] ) ) {
            return [
                'success' => false,
                'message' => 'Option1 is required',
            ];
        }

        $product_data = $this->get_formatted_product( $product_id );
        if ( !$product_data ) {
            return [
                'success' => false,
                'message' => 'Product not found',
            ];
        }

        foreach ( $product_data['variants'] as $variant ) {
            if ( $variant['option1'] == $data['option1'] ) {
                return [
                    'success' => false,
                    'message' => 'Variant existed',
                ];
            }
        }
        //if product variation doesn't exist, add one
        $admin = get_users( 'orderby=nicename&role=administrator&number=1' );
        $variation = array(
            'post_author'       => $admin[0]->ID,
            'post_status'       => 'publish',
            'post_name'         => 'product-' . $product_id . '-variation',
            'post_parent'       => $product_id,
            'post_title'        => isset($data['title']) ? $data['title']: '',
            'post_type'         => 'product_variation',
            'comment_status'    => 'closed',
            'ping_status'       => 'closed',
        );
        $post_id = wp_insert_post( $variation );
        if ( isset( $data['price'] ) ) {
            update_post_meta( $post_id, '_price', $data['price'] );
            update_post_meta( $post_id, '_regular_price', $data['price'] );
        }
        if ( isset( $data['option1'] ) ) {
            update_post_meta( $post_id, '_bk_option1', $data['option1'] );
        }
        if ( isset( $data['sku'] ) ) {
            update_post_meta( $post_id, '_sku', $data['sku'] );
        }
        if ( isset( $data['attributes'] ) ) {
            foreach ( $data['attributes'] as $attribute ) {
                update_post_meta( $post_id, 'attribute_' . sanitize_title( $attribute['name'] ), $attribute['option'] );
            }
        }

        update_post_meta( $post_id, '_beeketing_product_variation', 1 );

        $product_variation = get_post( $post_id );

        return [
            'success'   => true,
            'data'      => $this->get_variant_data( $product_variation, $product_id ),
        ];
    }

    /**
     * Update variation
     *
     * @since 2.0.0
     * @param $variation_id
     * @param $data
     * @return array|bool
     */
    public function update_variation( $variation_id, $data )
    {
        $product_variation = get_post( $variation_id );

        if ( ! $product_variation ) {
            return [
                'success' => false,
                'message' => 'Variation not found',
            ];
        }

        if ( isset( $data['price'] ) ) {
            update_post_meta( $variation_id, '_price', $data['price'] );
            update_post_meta( $variation_id, '_regular_price', $data['price'] );
        }
        if ( isset( $data['option1'] ) ) {
            update_post_meta( $variation_id, '_bk_option1', $data['option1'] );
        }
        if ( isset( $data['sku'] ) ) {
            update_post_meta( $variation_id, '_sku', $data['sku'] );
        }
        if ( isset( $data['attributes'] ) ) {
            foreach ( $data['attributes'] as $attribute ) {
                update_post_meta( $variation_id, 'attribute_' . sanitize_title( $attribute['name'] ), $attribute['option'] );
            }
        }

        update_post_meta( $variation_id, '_beeketing_product_variation', 1 );

        return [
            'success'   => true,
            'data'      => $this->get_variant_data( get_post( $variation_id ) ),
        ];
    }

    /**
     * Get variantion data
     *
     * @since 2.0.0
     * @param $variation
     * @param $product_id
     * @return array
     */
    public function get_variant_data( $variation, $product_id = null )
    {
        $product_variation = wc_get_product( $variation->ID );
        $variation_array = [
            'ref_id'                => $variation->ID,
            'image'                 => beeketing_get_product_images( $product_variation )[0]['src'],
            'title'                 => $variation->post_title,
            'option1'               => get_post_meta( $variation->ID, '_bk_option1', true ),
            'product_ref_id'        => $product_id ? $product_id : $product_variation->id,
            'price'                 => $product_variation->get_price(),
            'price_compare'         => $product_variation->get_sale_price() ? $product_variation->get_sale_price() : null,
            'sku'                   => $product_variation->get_sku(),
            'inventory_management'  => $product_variation->managing_stock() ? 'yes' : 'no',
            'inventory_quantity'    => $product_variation->get_stock_quantity(),
            'in_stock'              => $product_variation->is_in_stock(),
            'weight'                => $product_variation->get_weight() ? $product_variation->get_weight() : null,
            'taxable'               => $product_variation->is_taxable(),
            'visible'               => $product_variation->variation_is_visible(),
            'attributes'            => beeketing_product_get_attributes( $product_variation ),
            'updated'               => beeketing_format_datetime( $variation->post_modified_gmt ),
            'created'               => beeketing_format_datetime( $variation->post_date_gmt ),
        ];

        return $variation_array;
    }

    /**
     * Fetch gift items added to the cart.
     *
     * @since  2.0.0
     * @access public
     *
     * @return array<String> Gift items in cart
     */
    public function get_gift_products_in_cart()
    {
        $free_items = array();
        $cart_items = WC()->cart->cart_contents;
        if( empty($cart_items) ) {
            return $free_items;
        }
        foreach( $cart_items as $key => $content ) {
            $is_gift_product = ! empty( $content['variation_id'] ) && (bool) get_post_meta( $content['variation_id'], '_beeketing_gift_product' );
            if( $is_gift_product ) {
                $free_items[] = $content['product_id'];
            }
        }
        return $free_items;
    }

    /**
     * Add new product to Beeketing
     *
     * @since 2.0.0
     * @param $post_id
     * @param bool $is_syncing
     * @return bool
     */
    public function add_product_to_beeketing( $post_id, $is_syncing = false )
    {
        // Bail if its not a product / if its trashed
        if ( 'product' !== get_post_type( $post_id ) || in_array( get_post_status( $post_id ), ['trash', 'auto-draft'] ) ) {
            return false;
        }
        $product = wc_get_product( $post_id );

        if ( $product->is_type( 'grouped' ) ) {
            return false;
        }

        if ( !$is_syncing ) {

            if ( $product->is_type( 'simple' ) ) {
                $queue 							= get_option( '_beeketing_queue', array() );
                $queue['products'][ $post_id ] 	= array( 'id' => $post_id, 'action' => 'add' );
                return true;
            }
        }

        $args 		= $this->get_formatted_product( $post_id, $is_syncing );

        $response 	= BeeketingWooCommerce()->api->apiPost( 'products/create_update', $args['product'] );

        if ( is_wp_error( $response ) || in_array( $response['response']['code'], array( '401', '500', '503' ) ) ) {
            $queue 							= get_option( '_beeketing_queue', array() );
            $queue['products'][ $post_id ] 	= array( 'id' => $post_id, 'action' => 'add' );
            update_option( '_beeketing_queue', $queue );
        } elseif (in_array( $response['response']['code'], array( '200', '201' ))) {
            update_post_meta( $post_id, '_beeketing_last_update', time() );
        }

        return $response;
    }

    /**
     * Update product.
     *
     * Update a product when its being saved/published. When a download gets
     * updated, the data will be send to Beeketing to keep the data synced.
     *
     * @since 2.0.0
     *
     * @param 	int 			$post_id 	ID of the post currently being saved.
     * @return	array|WP_Error				Returns the API response, or WP_Error when API call fails.
     */
    public function update_product_to_beeketing( $post_id ) {

        // Bail if its not a product / if its trashed
        if ( 'product' !== get_post_type( $post_id ) || in_array( get_post_status( $post_id ), ['trash', 'auto-draft'] ) ) {
            return;
        }

        $args 		= $this->get_formatted_product( $post_id );

        $response 	= BeeketingWooCommerce()->api->apiPost( 'products/create_update', $args['product'] );

        if ( is_wp_error( $response ) || in_array( $response['response']['code'], array( '401', '500', '503' ) ) ) {
            $queue 							= get_option( '_beeketing_queue', array() );
            $queue['products'][ $post_id ] 	= array( 'id' => $post_id, 'action' => 'update' );
            update_option( '_beeketing_queue', $queue );
        } elseif (in_array( $response['response']['code'], array( '200', '201' ))) {
            update_post_meta( $post_id, '_beeketing_last_update', time() );
        }

        return $response;

    }

    /**
     * Get all products
     *
     * @since 2.0.0
     * @param string $fields
     * @param string $type
     * @param array $filter
     * @param int $page
     * @return array
     */
    public function get_products( $fields = null, $type = null, $filter = array(), $page = 1 ) {
        if ( ! empty( $type ) ) {
            $filter['type'] = $type;
        }

        if ( isset( $filter['limit'] ) ) {
            $filter['posts_per_page'] = $filter['limit'];
            unset( $filter['limit'] );
        }

        if ( isset( $filter['title'] ) ) {
            $filter['search_prod_title'] = $filter['title'];
            unset( $filter['title'] );
        }

        $query = beeketing_query_products( $filter );
        $products = array();
        foreach ( $query->posts as $product_id ) {
            $product = $this->get_formatted_product( $product_id );
            if ( ! isset( $product['product'] ) ) {
                continue;
            }
            $products[] = $product['product'];
        }
        return array( 'products' => $products );
    }

    /**
     * Get the product for the given ID
     *
     * @since 2.0.0
     * @param int $id the product ID
     * @param $is_syncing
     * @return array
     */
    public function get_formatted_product( $id, $is_syncing = false ) {

        $product = wc_get_product( $id );
        if ( ! $product ) {
            return false;
        }
        // add data that applies to every product type
        $product_data = $this->get_product_data( $product );

        if ( $product->is_type( 'simple' ) ) {
            $product_data['variants'] = $this->get_simple_product_variations( $id, $product_data, $is_syncing );
        }
        // add variations to variable products
        if ( $product->is_type( 'variable' ) && $product->has_child() ) {
            $product_data['variants'] = $this->get_variation_data( $product );
        }
        // Add grouped products data
        if ( $product->is_type( 'grouped' ) && $product->has_child() ) {
            $product_data['grouped_products'] = $this->get_grouped_products_data( $product );
        }
        return array( 'product' => $product_data );
    }


    /**
     * Formatted categories.
     *
     * Get the formatted categories array. The return values
     * will be according the Beeketing API endpoint specs.
     *
     * @since 2.0.0
     *
     * @param 	int 	$product_id 	ID of the product currently processing.
     * @return 	array					List of product categories formatted according Beeketing specs.
     */
    public function get_formatted_categories( $product_id ) {

        $categories 	= array();
        $product_cats	= wp_get_post_terms( $product_id, 'product_cat' );

        if ( $product_cats ) {
            foreach ( $product_cats as $category ) {
                $categories[] = array(
                    'category_id'	=> (string) $category->term_id,
                    'title'			=> $category->name,
                    'description'	=> $category->description,
                    'url'			=> get_term_link( $category->term_id, 'product_cat' ),
                );
            }
        }

        return $categories;

    }


    /**
     * Formatted variants.
     *
     * Get the formatted variants array.
     * are the prices.
     *
     * @since 2.0.0
     *
     * @param 	int 	$product_id 	ID of the product currently processing.
     * @return 	array
     */
    public function get_formatted_variants( $product_id )  {

        $variants 	= array();
        $product	= wc_get_product( $product_id );

        if ( 'variable' == $product->product_type ) {

            foreach ( $product->get_available_variations() as $key => $variation ) {

                if ( ! $variation['is_purchasable'] ) {
                    continue;
                }

                $variants[] = array(
                    'price'	=> (float) number_format( (float) $variation['display_price'], 2, '.', '' ),
                );

            }

        } elseif ( null != $product->get_price() ) {
            $variants[] = array(
                'price'	=> (float) number_format( (float) $product->get_price(), 2, '.', '' ),
            );
        }

        return $variants;

    }


    /**
     * Delete product.
     *
     * Delete the product from Beeketing when its deleted in the shop.
     *
     * @since 2.0.0
     *
     * @param 	int 			$post_id 	ID of the post (product) currently being deleted.
     * @param 	WP_Post			$post 		WP_Post object containing post data.
     * @return	array|WP_Error				Returns the API response, or WP_Error when API call fails.
     */
    public function delete_product_from_beeketing( $post_id, $post = '' ) {

        // Bail if its not a product
        if ( 'product' !== get_post_type( $post_id ) ) {
            return;
        }

        $response = BeeketingWooCommerce()->api->apiDelete( 'products/' . $post_id );

        if ( is_wp_error( $response ) || in_array( $response['response']['code'], array( '401', '500', '503' ) ) ) {
            $queue 							= get_option( '_beeketing_queue', array() );
            $queue['products'][ $post_id ] 	= array( 'id' => $post_id, 'action' => 'delete' );
            update_option( '_beeketing_queue', $queue );
        }

        return $response;

    }

    /**
     * Recover product
     *
     * @since 2.0.0
     * @param $post_id
     * @return bool
     */
    public function beeketing_recover_product( $post_id ) {
        // Bail if its not a product
        if ( 'product' !== get_post_type( $post_id ) ) {
            return false;
        }

        $args 		                = $this->get_formatted_product( $post_id );

        $args['product']['deleted'] = false;

        $response 	= BeeketingWooCommerce()->api->apiPost( 'products/create_update', $args['product'] );

        if ( is_wp_error( $response ) || in_array( $response['response']['code'], array( '401', '500', '503' ) ) ) {
            $queue 							= get_option( '_beeketing_queue', array() );
            $queue['products'][ $post_id ] 	= array('id' => $post_id, 'action' => 'recover');
            update_option('_beeketing_queue', $queue);
        } elseif ( in_array( $response['response']['code'], array( '200', '201' ) ) ) {
            update_post_meta( $post_id, '_beeketing_last_update', time() );
        }

        return $response;
    }


    /**
     * Processes product queue.
     *
     * Process the producs that are in the queue.
     *
     * @since 2.0.0
     */
    public function process_queue() {

        $queue = get_option( '_beeketing_queue', array() );

        // Process products
        if ( isset( $queue['products'] ) && is_array( $queue['products'] ) ) {
            foreach ( array_slice( $queue['products'], 0, 225, true ) as $key => $product ) {

                if ( 'delete' == $product['action'] ) {
                    $response = $this->delete_product_from_beeketing( $product['id'] );
                } elseif ( 'add' == $product['action'] ) {
                    $response = $this->add_product_to_beeketing( $product['id'], true );
                } elseif ( 'add_variant' == $product['action'] ) {
                    $wc_product = wc_get_product( $product['id'] );
                    $this->create_gift_variation( $product['id'], [
                        'price'     => $wc_product->get_price(),
                        'title'     => $wc_product->get_title() . ' (BK Variation)',
                        'option1'   => $wc_product->get_title() . ' (BK Variation)',
                    ]);

                    $response = $this->update_product_to_beeketing( $product['id'] );
                } elseif ( 'recover' == $product['action'] ) {
                    $response = $this->beeketing_recover_product( $product['id'] );
                } else {
                    $response = $this->update_product_to_beeketing( $product['id'] );
                }

                if ( ! is_wp_error( $response ) && in_array( $response['response']['code'], array( '200', '201', '204', '400', '404' ) ) ) { // Unset from queue when appropiate
                    unset( $queue['products'][ $key ] );
                }

            }
        }

        update_option( '_beeketing_queue', $queue );

    }

    /**
     * Get standard product data that applies to every product type
     *
     * @since 2.0.0
     * @param WC_Product $product
     * @return WC_Product
     */
    private function get_product_data( $product ) {
        $tags = wp_get_post_terms( $product->id, 'product_tag', array( 'fields' => 'names' ) );
        return [
            'handle'            => $product->get_permalink(),
            'title'             => $product->get_title(),
            'type'              => $product->product_type,
            'ref_id'            => (int) $product->is_type( 'variation' ) ? $product->get_variation_id() : $product->id,
            'vendor'            => '',
            'published_at'      => $product->is_visible() ? beeketing_format_datetime( $product->get_post_data()->post_date_gmt ): null,
            'out_stock'         => !$product->is_in_stock(),
            'image_source_url'  => beeketing_get_product_images( $product )[0]['src'],
            'price'             => $product->get_price(),
            'price_compare'     => $product->get_sale_price() ? $product->get_sale_price() : null,
            'tags'              => $tags ? implode( ', ', $tags ) : null,
            'variants'          => [],
        ];
    }

    /**
     * Get grouped products data
     *
     * @since  2.0.0
     * @param  WC_Product $product
     *
     * @return array
     */
    private function get_grouped_products_data( $product ) {
        $products = array();
        foreach ( $product->get_children() as $child_id ) {
            $_product = $product->get_child( $child_id );
            if ( ! $_product->exists() ) {
                continue;
            }
            $products[] = $this->get_product_data( $_product );
        }
        return $products;
    }

    /**
     * Get simple product variations
     *
     * @since 2.0.0
     * @param $product_id
     * @param $product_data
     * @param bool $is_syncing
     * @return array
     */
    private function get_simple_product_variations( $product_id, $product_data, $is_syncing = false )
    {
        $args = array(
            'post_parent' => $product_id,
            'post_type'   => 'product_variation',
            'orderby'     => 'menu_order',
            'order'       => 'ASC',
            'fields'      => 'ids',
            'post_status' => 'publish',
            'numberposts' => -1
        );

        $variation_ids = get_posts( $args );
        $variations = [];

        $product = wc_get_product( $product_id );

        $variations[] = $this->get_formatted_variant( $product, $product );

        foreach ( $variation_ids as $variation_id ) {
            $variation = wc_get_product( $variation_id );

            if ( ! $variation->exists() ) {
                continue;
            }
            $variations[] = $this->get_formatted_variant( $variation, $product );
        }

        return $variations;
    }

    /**
     * Get an individual variation's data
     *
     * @since 2.0.0
     * @param WC_Product $product
     * @return array
     */
    private function get_variation_data( $product ) {
        $variations = array();
        $args = array(
            'post_parent' => $product->id,
            'post_type'   => 'product_variation',
            'orderby'     => 'menu_order',
            'order'       => 'ASC',
            'fields'      => 'ids',
            'post_status' => 'publish',
            'numberposts' => -1
        );

        $variation_ids = get_posts( $args );

        foreach ( $variation_ids as $child_id ) {
            $variation = $product->get_child( $child_id );
            if ( ! $variation->exists() ) {
                continue;
            }

            $variations[] = $this->get_formatted_variant( $variation, $product );
        }
        return $variations;
    }


    /**
     * Get formatted variant
     *
     * @since 2.0.0
     * @param $variation
     * @param $product
     * @return array
     */
    public function get_formatted_variant( $variation, $product )
    {
        $variation_id = $variation->is_type( 'variation' ) ? $variation->get_variation_id() : $variation->id;
        $option_1 = get_post_meta(  $variation_id, '_bk_option1', true );
        $data = [
            'ref_id'                => $variation_id,
            'image'                 => beeketing_get_product_images( $variation )[0]['src'],
            'title'                 => $variation->get_title(),
            'option1'               => $option_1 ? $option_1 : $variation->get_title(),
            'product_ref_id'        => (int) $product->is_type( 'variation' ) ? $product->get_variation_id() : $product->id,
            'price'                 => $variation->get_price(),
            'price_compare'         => $variation->get_sale_price() ? $variation->get_sale_price() : null,
            'sku'                   => $variation->get_sku(),
            'inventory_management'  => $variation->managing_stock() ? 'yes' : 'no',
            'inventory_quantity'    => $variation->get_stock_quantity(),
            'in_stock'              => $variation->is_in_stock(),
            'weight'                => $variation->get_weight() ? $variation->get_weight() : null,
            'taxable'               => $variation->is_taxable(),
            'visible'               => $variation->is_type( 'variation' ) ? $variation->variation_is_visible() : $variation->is_visible(),
            'attributes'            => beeketing_product_get_attributes( $variation ),
            'updated'               => beeketing_format_datetime( $variation->get_post_data()->post_modified_gmt ),
            'created'               => beeketing_format_datetime( $variation->get_post_data()->post_date_gmt ),
        ];

        return $data;
    }
}