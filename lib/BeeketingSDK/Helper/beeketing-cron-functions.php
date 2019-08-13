<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * CRONTABs
 *
 *
 * @author	Beeketing
 *
 */


if ( ! function_exists( 'beeketing_add_custom_schedules' ) ) {
    /**
     * Custom interval.
     *
     * Add custom interval to the cron schedules.
     *
     * @since  2.0.0
     *
     * @param	array $schedules	List of current CRON schedules.
     * @return	array				List of modified CRON schedules.
     */
    function beeketing_add_custom_schedules( $schedules )
    {
        $schedules['quarter_hour'] = array(
            'interval'	=> 60 * 15, // 60 seconds * 15 minutes
            'display'	=> __( 'Every quarter', 'beeketing' ),
        );

        $schedules['beeketing_ten_minute'] = array(
            'interval'  => 60 * 10,
            'display'   => __( 'Every ten minute', 'beeketing' ),
        );

        return $schedules;

    }
    add_filter( 'cron_schedules', 'beeketing_add_custom_schedules' );
}

if ( ! function_exists( 'beeketing_schedule_events' ) ) {
    /**
     * Schedule events.
     * @since  2.0.0
     */
    function beeketing_schedule_events()
    {
        // Retry queue
        if ( ! wp_next_scheduled( 'beeketing_retry_queue' ) ) {
            wp_schedule_event( BEEKETING_INITIAL_SYNC_TIME, 'beeketing_ten_minute', 'beeketing_retry_queue' );
        }

        // Initial product sync
        if ( ! wp_next_scheduled( 'beeketing_initial_product_sync' ) && 1 != get_option( 'beeketing_completed_initial_product_sync', 0 ) ) {
            wp_schedule_event( BEEKETING_INITIAL_SYNC_TIME, 'beeketing_ten_minute', 'beeketing_initial_product_sync' );
        } elseif ( wp_next_scheduled( 'beeketing_initial_product_sync' ) && 1 == get_option( 'beeketing_completed_initial_product_sync', 0 ) ) {
            // Remove CRON when we're done with it.
            wp_clear_scheduled_hook( 'beeketing_initial_product_sync' );
        }

        // Initial order sync
        if ( ! wp_next_scheduled( 'beeketing_initial_orders_sync' ) && 1 != get_option( 'beeketing_completed_initial_order_sync', 0 ) ) {
            wp_schedule_event( BEEKETING_INITIAL_SYNC_TIME, 'beeketing_ten_minute', 'beeketing_initial_orders_sync' );
        } elseif ( wp_next_scheduled( 'beeketing_initial_orders_sync' ) && 1 == get_option( 'beeketing_completed_initial_order_sync', 0 ) ) {
            wp_clear_scheduled_hook( 'beeketing_initial_orders_sync' ); // Remove CRON when we're done with it.
        }

        // Initial collection sync
        if ( ! wp_next_scheduled( 'beeketing_initial_collections_sync' ) && 1 != get_option( 'beeketing_completed_initial_collection_sync', 0 ) ) {
            wp_schedule_event( BEEKETING_INITIAL_SYNC_TIME, 'beeketing_ten_minute', 'beeketing_initial_collections_sync' );
        } elseif ( wp_next_scheduled( 'beeketing_initial_collections_sync' ) && 1 == get_option( 'beeketing_completed_initial_collection_sync', 0 ) ) {
            wp_clear_scheduled_hook( 'beeketing_initial_collections_sync' ); // Remove CRON when we're done with it.
        }

        // Initial collect sync
        if ( ! wp_next_scheduled( 'beeketing_initial_collects_sync' ) && 1 != get_option( 'beeketing_completed_initial_collects_sync', 0 ) ) {
            wp_schedule_event( BEEKETING_INITIAL_SYNC_TIME, 'beeketing_ten_minute', 'beeketing_initial_collects_sync' );
        } elseif ( wp_next_scheduled( 'beeketing_initial_collects_sync' ) && 1 == get_option( 'beeketing_completed_initial_collects_sync', 0 ) ) {
            wp_clear_scheduled_hook( 'beeketing_initial_collects_sync' ); // Remove CRON when we're done with it.
        }

        // Initial sync collects by categories
        if ( ! wp_next_scheduled( 'beeketing_initial_sync_collects_by_collections' ) ) {
            wp_schedule_event( BEEKETING_INITIAL_SYNC_TIME, 'beeketing_ten_minute', 'beeketing_initial_sync_collects_by_collections' );
        }

        // Initial customers sync
        if ( ! wp_next_scheduled( 'beeketing_initial_customers_sync' ) && 1 != get_option( 'beeketing_completed_initial_customer_sync', 0 ) ) {
            wp_schedule_event( BEEKETING_INITIAL_SYNC_TIME, 'beeketing_ten_minute', 'beeketing_initial_customers_sync' );
        } elseif ( wp_next_scheduled( 'beeketing_initial_customers_sync' ) && 1 == get_option( 'beeketing_completed_initial_customer_sync', 0 ) ) {
            wp_clear_scheduled_hook( 'beeketing_initial_customers_sync' ); // Remove CRON when we're done with it.
        }

    }
    add_action( 'init', 'beeketing_schedule_events' );
}


if ( ! function_exists('beeketing_retry_queue') ) {

    /**
     * Resend queue.
     *
     * @since  2.0.0
     */
    function beeketing_retry_queue()
    {
        // Customers
        BeeketingWooCommerce()->customers->process_queue();

        // Products queue
        BeeketingWooCommerce()->products->process_queue();

        // Collections queue
        BeeketingWooCommerce()->collections->process_queue();

        // Orders queue
        BeeketingWooCommerce()->orders->process_queue();

    }
    add_action( 'beeketing_retry_queue', 'beeketing_retry_queue' );
}

if ( ! function_exists( 'beeketing_initial_product_sync' ) ) {

    /**
     * Sync product with Beeketing API
     *
     * @since  2.0.0
     *
     */
    function beeketing_initial_product_sync()
    {
        if ( get_option( 'beeketing_current_sync_time' ) ) {
            $current_sync_time = get_option( 'beeketing_current_sync_time' );
        } else {
            $current_sync_time = time();
        }

        $query = beeketing_query_products( array(
            'posts_per_page'    => 250,
            'meta_query'		=> array(
                'relation'      => 'OR',
                array(
                    'key'		    => '_beeketing_last_update',
                    'compare'	    => '<',
                    'value'		    => $current_sync_time,
                ),
                array(
                    'relation'      => 'AND',
                    array(
                        'key'		=> '_beeketing_last_update',
                        'compare'	=> 'NOT EXISTS',
                        'value'		=> '',
                    ),
                    array(
                        'key'		=> '_visibility',
                        'compare'	=> '!=',
                        'value'		=> 'hidden',
                    ),
                ),
            ),
        ) );

        $product_ids = $query->posts;

        // Update option so the system knows it should stop syncing
        if ( empty( $product_ids ) ) {
            update_option( 'beeketing_completed_initial_product_sync', 1 );
            return;
        }

        foreach ( $product_ids as $product_id ) {
            BeeketingWooCommerce()->products->add_product_to_beeketing( $product_id, true );
        }

    }
    add_action( 'beeketing_initial_product_sync', 'beeketing_initial_product_sync' );
}


if ( ! function_exists( 'beeketing_initial_orders_sync' ) ) {

    /**
     * Sync Orders data.
     *
     * Sync orders with Beeketing API
     *
     * @since  2.0.0
     *
     */
    function beeketing_initial_orders_sync()
    {
        if ( get_option( 'beeketing_current_sync_time' ) ) {
            $current_sync_time = get_option( 'beeketing_current_sync_time' );
        } else {
            $current_sync_time = time();
        }

        $query = beeketing_query_orders( array(
            'posts_per_page'    => 250,
            'meta_query'		=> array(
                'relation'      => 'OR',
                array(
                    'key'		=> '_beeketing_last_update',
                    'compare'	=> 'NOT EXISTS',
                    'value'		=> '',
                ),
                array(
                    'key'		=> '_beeketing_last_update',
                    'compare'	=> '<',
                    'value'		=> $current_sync_time,
                ),
            )
        ));

        $order_ids = $query->posts;
        if ( empty( $order_ids ) ) {
            update_option( 'beeketing_completed_initial_order_sync', 1 );
            return;
        }

        foreach ( $query->posts as $order_id ) {
            BeeketingWooCommerce()->orders->add_order_to_beeketing($order_id);
        }
    }
    add_action( 'beeketing_initial_orders_sync', 'beeketing_initial_orders_sync' );
}

if ( ! function_exists( 'beeketing_initial_collections_sync' ) ) {
    /**
     * Sync product categories
     * @since  2.0.0
     */
    function beeketing_initial_collections_sync()
    {
        $page = 0;
        $count_terms = wp_count_terms( 'product_cat', [
            'hide_empty' => false,
        ]);

        if ( $count_terms > 100 ) {
            $count_terms = 100;
        } else {
            $count_terms = $count_terms - 1;
        }
        while ( true ) {
            $terms = get_terms( 'product_cat', array(
                'hide_empty' => false,
                'fields' => 'ids',
                'number' => $count_terms,
                'offset' => $page * $count_terms
            ) );

            if ( ! $terms ) {
                break;
            }

            foreach ( $terms as $term_id ) {
                BeeketingWooCommerce()->collections->add_product_category_to_beeketing( $term_id, null, 'product_cat' );
            }

            $page ++;
        }

        update_option( 'beeketing_completed_initial_collection_sync', 1 );
    }

    add_action( 'beeketing_initial_collections_sync', 'beeketing_initial_collections_sync' );
}

if ( !function_exists( 'beeketing_initial_collects_sync' ) ) {
    /**
     * Sync collects
     *
     * @since  2.0.0
     */
    function beeketing_initial_collects_sync()
    {
        update_option( 'beeketing_syncing_collects', 1 );

        $startTime = time();

        while ( true ) {
            // Stop syncing if time is up
            if ( time() - $startTime > 30 * 60 ) {
                update_option( 'beeketing_syncing_collects', 0 );
                break;
            }

            $count_terms = wp_count_terms( 'product_cat', [
                'hide_empty' => false,
            ] );

            if ( $count_terms > 100 ) {
                $count_terms = 100;
            } else {
                $count_terms = $count_terms - 1;
            }

            $terms_per_page = $count_terms;

            $offset = get_option( 'beeketing_collections_offset', 0 );
            $terms = get_terms( 'product_cat', array(
                'hide_empty'    => false,
                'fields'        => 'ids',
                'number'        => $terms_per_page,
                'offset'        => $offset * $terms_per_page
            ) );

            if ( ! $terms ) {
                update_option( 'beeketing_completed_initial_collects_sync', 1 );
                update_option( 'beeketing_collections_offset', 0 );
                update_option( 'beeketing_syncing_collects', 0 );
                break;
            }

            foreach ( $terms as $term_id ) {
                BeeketingWooCommerce()->collects->sync_collect_by_collection_id( $term_id );
            }

            update_option( 'beeketing_collections_offset', $offset + 1 );
        }

    }

    add_action( 'beeketing_initial_collects_sync', 'beeketing_initial_collects_sync' );
}


if ( ! function_exists( 'beeketing_initial_sync_collects_by_collections' ) ) {
    /**
     * Sync collects by collections
     *
     * @since  2.0.0
     */
    function beeketing_initial_sync_collects_by_collections()
    {
        $terms = get_option( '_beeketing_updated_categories', [] );

        foreach ( $terms as $key => $term_id ) {
            BeeketingWooCommerce()->collects->sync_collect_by_collection_id( $term_id );
            unset( $terms[ $key ] );
            update_option( '_beeketing_updated_categories', $terms );

        }

    }

    add_action( 'beeketing_initial_sync_collects_by_collections', 'beeketing_initial_sync_collects_by_collections' );
}

if ( ! function_exists( 'beeketing_initial_customers_sync' ) ) {
    /**
     * Sync customers
     *
     * @since  2.0.0
     */
    function beeketing_initial_customers_sync()
    {
        if ( get_option( 'beeketing_current_sync_time' ) ) {
            $current_sync_time = get_option( 'beeketing_current_sync_time' );
        } else {
            $current_sync_time = time();
        }

        $query = beeketing_query_customers( array(
            'number'            => 250,
            'meta_query'		=> array(
                'relation'      => 'OR',
                array(
                    'key'		=> '_beeketing_last_update',
                    'compare'	=> 'NOT EXISTS',
                    'value'		=> '',
                ),
                array(
                    'key'		=> '_beeketing_last_update',
                    'compare'	=> '<',
                    'value'		=> $current_sync_time,
                ),
            )
        ));
        $user_ids = $query->get_results();

        if ( empty( $user_ids ) ) {
            update_option( 'beeketing_completed_initial_customer_sync', 1 );
            return;
        }
        foreach ( $user_ids as $user_id ) {
            BeeketingWooCommerce()->customers->beeketing_add_customer( $user_id, null );
        }
    }

    add_action( 'beeketing_initial_customers_sync', 'beeketing_initial_customers_sync' );
}




