<?php

/**
 * Class BeeketingSDK_Snippet_DefaultSnippet.
 *
 * Add default snippet
 *
 * @class		BeeketingSDK_Snippet_DefaultSnippet
 * @version		1.0.0
 * @author		Beeketing
 * @since		1.0.0
 */
class BeeketingSDK_Snippet_DefaultSnippet
{
    /**
     * @since 1.0.0
     * @var BeeketingSDK_Snippet_SnippetManager $snippetManager
     */
    protected $snippetManager;

    /**
     * @since 1.0.0
     * @var BeeketingSDK_Config_BeeketingConfig $beeketingConfig
     */
    protected $beeketingConfig;

    /**
     * Constructor
     *
     * @since 1.0.0
     * @param $snippetManager
     * @param $beeketingConfig
     */
    public function __construct( $snippetManager, $beeketingConfig )
    {
        $this->snippetManager = $snippetManager;
        $this->beeketingConfig = $beeketingConfig;
    }

    /**
     * Start
     *
     * @since 1.0.0
     */
    public function start()
    {
        // Add default snippet
        $couponBoxSnippet = __DIR__.'/Templates/CouponBoxSnippet.html';

        $this->snippetManager->addSnippet( $couponBoxSnippet );
        $this->snippetManager->addVars( array(
            '{{ bk_script_path }}'  => $this->getScriptPath( $this->beeketingConfig->getPlatform() ),
            '{{ bk_shop_api_key }}' => $this->getShopApiKey(),
        ));
    }

    /**
     * Get shop api key
     *
     * @since 1.0.0
     * @return string
     */
    protected function getShopApiKey()
    {
        return strtolower( $this->beeketingConfig->getApiKey() );
    }

    /**
     * Get script path
     *
     * @since 1.0.0
     * @param $platform
     * @return string
     */
    protected function getScriptPath( $platform )
    {
        return $this->beeketingConfig->getPath() . '/dist/js/front/loader/' . $platform . '.js';
    }
}