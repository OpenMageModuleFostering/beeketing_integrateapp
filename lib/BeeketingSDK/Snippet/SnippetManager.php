<?php

/**
 * Class BeeketingSDK_Snippet_SnippetManager.
 *
 * Snippet manager
 *
 * @class		BeeketingSDK_Snippet_SnippetManager
 * @version		1.0.0
 * @author		Beeketing
 * @since		1.0.0
 */
class BeeketingSDK_Snippet_SnippetManager
{
    /**
     * @since 1.0.0
     * @var array
     */
    protected $snippets = array();

    /**
     * @since 1.0.0
     * @var array
     */
    protected $vars = array(
        'keys'      => array(),
        'values'    => array()
    );

    /**
     * Add snippet
     *
     * @since 2.0.0
     * @param $snippet
     * @return bool
     * @throws Exception
     */
    public function addSnippet( $snippet )
    {
        // Already added.
        if ( in_array( $snippet, $this->snippets ) ) {
            return true;
        }

        array_push( $this->snippets, $snippet );

        return true;
    }

    /**
     * Remove snippet
     *
     * @since 2.0.0
     * @param string $snippet
     */
    public function removeSnippet( $snippet )
    {
        $this->array_remove( $this->snippets, $snippet );
    }

    /**
     * Get snippet content
     *
     * @since 2.0.0
     * @param $snippet
     * @return string
     * @throws Exception
     */
    public function getSnippetContent( $snippet )
    {
        return $this->read( $snippet );
    }

    /**
     * Add variables to snippet
     *
     * @since 2.0.0
     * @param $vars
     * @return $this
     */
    public function addVars( $vars )
    {
        foreach ( $vars as $key => $value ) {
            array_push( $this->vars['keys'], $key );
            array_push( $this->vars['values'], $value );
        }

        return $this;
    }

    /**
     * Get all snippet content
     *
     * @since 2.0.0
     * @return string
     * @throws Exception
     */
    public function getAllSnippetContent()
    {
        $content = '';

        // Read
        if ( count( $this->snippets ) > 0 ) {
            foreach( $this->snippets as $snippet ) {
                $content .= $this->read( $snippet );
            }
        }

        // Replace variables
        if ( count( $this->vars['keys'] ) > 0 && count( $this->vars['values'] ) > 0 ) {
            $content = str_replace( $this->vars['keys'], $this->vars['values'], $content );
        }

        if ( function_exists( 'is_shop' ) ) {
            // Tracking script
            if ( is_shop() ) {
                $content .= "<script>var __bkt = {}; __bkt.p = 'home';</script>";
            } elseif (is_product_category()) {
                $cate       = get_queried_object();
                $cateID     = $cate->term_id;
                $content    .= "<script>var __bkt = {}; __bkt.p = 'collection'; __bkt.rid = " . $cateID .";</script>";
            } elseif ( is_product() ) {
                global $product;
                $content    .= "<script> var __bkt = {}; __bkt.p = 'product'; __bkt.rid = " . $product->id . ";</script>";
            } elseif ( is_cart() ) {
                $content    .= "<script>var __bkt = {}; __bkt.p = 'cart';</script>";
            } elseif ( is_checkout() ) {
                $content    .= "<script>var __bkt = {}; __bkt.p = 'checkout';</script>";
            } elseif ( is_account_page() ) {
                $content    .= "<script>var __bkt = {}; __bkt.p = 'account';</script>";
            }
            $userId = get_current_user_id();
            if ( $userId > 0 ) {
                $content    .= "<script>var BKCustomer = {}; BKCustomer.id = " . $userId . ";</script>";
            }

            $cartData   = BeeketingWooCommerce()->cart->get_formatted_cart_data();

            $content    .= "<script>var bkCartData = JSON.parse('" . json_encode($cartData)."');</script>";
        }

        return $content;
    }

    /**
     * Read snippet
     *
     * @since 1.0.0
     * @param $snippet
     * @return string
     * @throws Exception
     */
    protected function read( $snippet )
    {
        // Can't read
        if ( ! is_readable( $snippet ) ) {
            throw new Exception( 'Snippet '.$snippet.' doesn\'t exist.' );
        }

        $content = file_get_contents( $snippet );

        return $content;
    }

    /**
     * array array_remove ( array input, mixed search_value [, bool strict] )
     *
     * @since 1.0.0
     * @param array $a_input
     * @param $m_search_value
     * @param bool $b_strict
     * @return array
     */
    protected function array_remove( array &$a_input, $m_search_value, $b_strict = false )
    {
        $a_Keys = array_keys( $a_input, $m_search_value, $b_strict );
        foreach( $a_Keys as $s_key ) {
            unset( $a_input[ $s_key ] );
        }
        return $a_input;
    }
}