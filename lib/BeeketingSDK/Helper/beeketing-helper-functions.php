<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! function_exists( 'getallheaders' ) ) {
    function getallheaders()
    {
        $headers = '';
        foreach ( $_SERVER as $name => $value ) {
            if ( substr( $name, 0, 5 ) == 'HTTP_' ) {
                $headers[ str_replace(' ', '-', ucwords( strtolower( str_replace( '_', ' ', substr( $name, 5 ) ) ) ) ) ] = $value;
            }
        }
        return $headers;
    }
}

if ( ! function_exists( 'beeketing_query_customers' ) ) {
    /**
     * Helper method to get customer user objects
     *
     * Note that WP_User_Query does not have built-in pagination so limit & offset are used to provide limited
     * pagination support
     *
     * The filter for role can only be a single role in a string.
     *
     * @since  2.0.0
     * @param array $args request arguments for filtering query
     * @return WP_User_Query
     */
     function beeketing_query_customers( $args = array() ) {
        // Set base query arguments
        $query_args = array(
            'fields'  => 'ID',
            'role'    => 'customer',
            'orderby' => 'registered',
        );
        // Custom Role
        if ( ! empty( $args['role'] ) ) {
            $query_args['role'] = $args['role'];
            // Show users on all roles
            if ( 'all' === $query_args['role'] ) {
                unset( $query_args['role'] );
            }
        }
        // Search
        if ( ! empty( $args['q'] ) ) {
            $query_args['search'] = $args['q'];
        }
        // Limit number of users returned
        if ( ! empty( $args['limit'] ) ) {
            if ( $args['limit'] == -1 ) {
                unset( $query_args['number'] );
            } else {
                $query_args['number'] = absint( $args['limit'] );
                $users_per_page       = absint( $args['limit'] );
            }
        } else {
            $args['limit'] = $query_args['number'];
        }
        // Page
        $page = ( isset( $args['page'] ) ) ? absint( $args['page'] ) : 1;
        // Offset
        if ( ! empty( $args['offset'] ) ) {
            $query_args['offset'] = absint( $args['offset'] );
        } else {
            $query_args['offset'] = $users_per_page * ( $page - 1 );
        }

        // Order (ASC or DESC, ASC by default)
        if ( ! empty( $args['order'] ) ) {
            $query_args['order'] = $args['order'];
        }
        // Orderby
        if ( ! empty( $args['orderby'] ) ) {
            $query_args['orderby'] = $args['orderby'];
            // Allow sorting by meta value
            if ( ! empty( $args['orderby_meta_key'] ) ) {
                $query_args['meta_key'] = $args['orderby_meta_key'];
            }
        }
        $query = new WP_User_Query( $query_args );
        // Helper members for pagination headers
        $query->total_pages = ( $args['limit'] == -1 ) ? 1 : ceil( $query->get_total() / $users_per_page );
        $query->page        = $page;
        return $query;
    }
}

if ( ! function_exists( 'beeketing_query_products' ) ) {
    /**
     * Helper method to get product post objects
     *
     * @since  2.0.0
     * @param array $args request arguments for filtering query
     * @return WP_Query
     */
    function beeketing_query_products( $args ) {
        // Set base query arguments
        $query_args = array(
            'fields'      => 'ids',
            'post_type'   => 'product',
            'post_status' => 'publish',
            'cache_results' => false,
            'meta_query'  => array(),
        );

        $query_args = array_merge( $query_args, $args );

        return new WP_Query( $query_args );
    }
}

if ( ! function_exists( 'beeketing_format_datetime' ) ) {
    function beeketing_format_datetime( $timestamp, $convert_to_utc = false ) {

        if ( $convert_to_utc ) {
            $timezone = new DateTimeZone( wc_timezone_string() );
        } else {
            $timezone = new DateTimeZone( 'UTC' );
        }

        try {

            if ( is_numeric( $timestamp ) ) {
                $date = new DateTime( "@{$timestamp}" );
            } else {
                $date = new DateTime( $timestamp, $timezone );
            }

            // convert to UTC by adjusting the time based on the offset of the site's timezone
            if ( $convert_to_utc ) {
                $date->modify( -1 * $date->getOffset() . ' seconds' );
            }

        } catch ( Exception $e ) {

            $date = new DateTime( '@0' );
        }

        return $date->format( 'Y-m-d\TH:i:s\Z' );
    }
}

if ( ! function_exists( 'beeketing_query_orders' ) ) {
    /**
     * Helper method to get order post objects
     *
     * @since  2.0.0
     * @param array $args request arguments for filtering query
     * @return WP_Query
     */
    function beeketing_query_orders( $args ) {
        // set base query arguments
        $query_args = array(
            'fields'      => 'ids',
            'post_type'   => 'shop_order',
            'post_status' => array_keys( wc_get_order_statuses() )
        );
        // add status argument
        if ( ! empty( $args['status'] ) ) {
            $statuses                  = 'wc-' . str_replace( ',', ',wc-', $args['status'] );
            $statuses                  = explode( ',', $statuses );
            $query_args['post_status'] = $statuses;
            unset( $args['status'] );
        }

        $query_args = array_merge( $query_args, $args );
        return new WP_Query( $query_args );
    }
}

if ( ! function_exists( 'beeketing_get_product_ids_in_collection' ) ) {
    /**
     * Helper method get products ids in collection
     *
     * @since 2.0.0
     * @param $cat_id
     * @param array $query
     * @return mixed
     */
    function beeketing_get_product_ids_in_collection($cat_id, $query = array()) {

        $args = array(
            'fields'			=> 'ids',
            'post_type'			=> 'product',
            'has_password'		=> false,
            'post_status'		=> 'publish',
            'tax_query'         => array(
                array(
                    'taxonomy'  => 'product_cat',
                    'terms'     => $cat_id,
                ),
            ),
        );

        $args = array_merge( $args, $query );

        $products = new WP_Query( $args );

        return $products->posts;
    }
}

if ( ! function_exists( 'merge_query_args' ) ) {
    /**
     * Merge query args
     *
     * @since 2.0.0
     * @param $base_args
     * @param $request_args
     * @return array
     */
    function merge_query_args( $base_args, $request_args ) {

        $args = array();

        // date
        if ( ! empty( $request_args['created_at_min'] ) || ! empty( $request_args['created_at_max'] ) || ! empty( $request_args['updated_at_min'] ) || ! empty( $request_args['updated_at_max'] ) ) {

            $args['date_query'] = array();

            // resources created after specified date
            if ( ! empty( $request_args['created_at_min'] ) )
                $args['date_query'][] = array( 'column' => 'post_date_gmt', 'after' => $this->server->parse_datetime( $request_args['created_at_min'] ), 'inclusive' => true );

            // resources created before specified date
            if ( ! empty( $request_args['created_at_max'] ) )
                $args['date_query'][] = array( 'column' => 'post_date_gmt', 'before' => $this->server->parse_datetime( $request_args['created_at_max'] ), 'inclusive' => true );

            // resources updated after specified date
            if ( ! empty( $request_args['updated_at_min'] ) )
                $args['date_query'][] = array( 'column' => 'post_modified_gmt', 'after' => $this->server->parse_datetime( $request_args['updated_at_min'] ), 'inclusive' => true );

            // resources updated before specified date
            if ( ! empty( $request_args['updated_at_max'] ) )
                $args['date_query'][] = array( 'column' => 'post_modified_gmt', 'before' => $this->server->parse_datetime( $request_args['updated_at_max'] ), 'inclusive' => true );
        }

        // search
        if ( ! empty( $request_args['q'] ) )
            $args['s'] = $request_args['q'];

        // resources per response
        if ( ! empty( $request_args['limit'] ) )
            $args['posts_per_page'] = $request_args['limit'];

        // resource offset
        if ( ! empty( $request_args['offset'] ) )
            $args['offset'] = $request_args['offset'];

        // resource page
        $args['paged'] = ( isset( $request_args['page'] ) ) ? absint( $request_args['page'] ) : 1;

        return array_merge( $base_args, $args );
    }
}

if (!function_exists('beeketing_get_product_images')) {
    /**
     * Get the images for a product or product variation
     *
     * @since  2.0.0
     * @param WC_Product|WC_Product_Variation $product
     * @return array
     */
    function beeketing_get_product_images( $product ) {
        $images = $attachment_ids = array();
        if ( $product->is_type( 'variation' ) ) {
            if ( has_post_thumbnail( $product->get_variation_id() ) ) {
                // Add variation image if set
                $attachment_ids[] = get_post_thumbnail_id( $product->get_variation_id() );
            } elseif ( has_post_thumbnail( $product->id ) ) {
                // Otherwise use the parent product featured image if set
                $attachment_ids[] = get_post_thumbnail_id( $product->id );
            }
        } else {
            // Add featured image
            if ( has_post_thumbnail( $product->id ) ) {
                $attachment_ids[] = get_post_thumbnail_id( $product->id );
            }
            // Add gallery images
            $attachment_ids = array_merge( $attachment_ids, $product->get_gallery_attachment_ids() );
        }
        // Build image data
        foreach ( $attachment_ids as $position => $attachment_id ) {
            $attachment_post = get_post( $attachment_id );
            if ( is_null( $attachment_post ) ) {
                continue;
            }
            $attachment = wp_get_attachment_image_src( $attachment_id, 'full' );
            if ( ! is_array( $attachment ) ) {
                continue;
            }
            $images[] = array(
                'id'         => (int) $attachment_id,
                'created_at' => beeketing_format_datetime( $attachment_post->post_date_gmt ),
                'updated_at' => beeketing_format_datetime( $attachment_post->post_modified_gmt ),
                'src'        => current( $attachment ),
                'title'      => get_the_title( $attachment_id ),
                'alt'        => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
                'position'   => (int) $position,
            );
        }
        // Set a placeholder image if the product has no images set
        if ( empty( $images ) ) {
            $images[] = array(
                'id'         => 0,
                'created_at' => beeketing_format_datetime( time() ), // Default to now
                'updated_at' => beeketing_format_datetime( time() ),
                'src'        => wc_placeholder_img_src(),
                'title'      => __( 'Placeholder', 'woocommerce' ),
                'alt'        => __( 'Placeholder', 'woocommerce' ),
                'position'   => 0,
            );
        }
        return $images;
    }
}

if ( ! function_exists( 'beeketing_product_get_attributes' ) ) {
    /**
     * Get the attributes for a product or product variation
     *
     * @since  2.0.0
     * @param WC_Product|WC_Product_Variation $product
     * @return array
     */
    function beeketing_product_get_attributes( $product ) {
        $attributes = array();
        if ( $product->is_type( 'variation' ) ) {
            // variation attributes
            foreach ( $product->get_variation_attributes() as $attribute_name => $attribute ) {
                // taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`
                $attributes[] = array(
                    'name'   => wc_attribute_label( str_replace( 'attribute_', '', $attribute_name ) ),
                    'slug'   => str_replace( 'attribute_', '', str_replace( 'pa_', '', $attribute_name ) ),
                    'option' => $attribute,
                );
            }
        } else {
            foreach ( $product->get_attributes() as $attribute ) {
                // taxonomy-based attributes are comma-separated, others are pipe (|) separated
                if ( $attribute['is_taxonomy'] ) {
                    $options = explode( ',', $product->get_attribute( $attribute['name'] ) );
                } else {
                    $options = explode( '|', $product->get_attribute( $attribute['name'] ) );
                }
                $attributes[] = array(
                    'name'      => wc_attribute_label( $attribute['name'] ),
                    'slug'      => str_replace( 'pa_', '', $attribute['name'] ),
                    'position'  => (int) $attribute['position'],
                    'visible'   => (bool) $attribute['is_visible'],
                    'variation' => (bool) $attribute['is_variation'],
                    'options'   => array_map( 'trim', $options ),
                );
            }
        }
        return $attributes;
    }
}

if ( ! function_exists( 'title_filter' ) ) {
    /**
     * Title filter
     *
     * @since 2.0.0
     * @param $where
     * @param $wp_query
     * @return string
     */
    function title_filter($where, &$wp_query) {
        global $wpdb;

        if ($search_term = $wp_query->get( 'search_prod_title' )) {
            /*using the esc_like() in here instead of other esc_sql()*/
            $search_term = $wpdb->esc_like( $search_term );
            $search_term = ' \'%' . $search_term . '%\'';
            $where      .= ' AND ' . $wpdb->posts . '.post_title LIKE ' . $search_term;
        }

        return $where;
    }

    add_filter( 'posts_where', 'title_filter', 10, 2 );
}

