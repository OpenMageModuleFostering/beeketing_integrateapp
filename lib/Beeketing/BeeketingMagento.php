<?php

/**
 * Defined environments
 * List: prod, master, staging, local
 */
require_once('config.php');

$env = file_get_contents(__DIR__.'/env');
$env = trim($env);

define('BEEKETING_ENVIRONMENT', $env);

class Beeketing_BeeketingMagento
{
    /**
     * @var BeeketingSDK_Snippet_SnippetManager $snippetManager
     */
    protected $snippetManager;

    /**
     * @var BeeketingSDK_Config_BeeketingConfig
     */
    protected $beeketingConfig;

    /**
     * Set constructor
     * @param $apiKey
     */
    public function __construct($apiKey)
    {
        # Set beeketing config
        $this->beeketingConfig = new BeeketingSDK_Config_BeeketingConfig();
        $this->beeketingConfig->setEnv(BEEKETING_ENVIRONMENT);
        $this->beeketingConfig->setApiKey($apiKey);
        $this->beeketingConfig->setPlatform('magento');

        $this->snippetManager = new BeeketingSDK_Snippet_SnippetManager();
        $defaultSnippet = new BeeketingSDK_Snippet_DefaultSnippet($this->snippetManager, $this->beeketingConfig);
        $defaultSnippet->start();
    }

    /**
     * @return BeeketingSDK_Config_BeeketingConfig
     */
    public function getBeeketingConfig()
    {
        return $this->beeketingConfig;
    }

    /**
     * @return BeeketingSDK_Snippet_SnippetManager
     */
    public function getSnippetManager()
    {
        return $this->snippetManager;
    }
}