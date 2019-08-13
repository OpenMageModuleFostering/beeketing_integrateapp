<?php

/**
 * Class BeeketingSDK_Config_BeeketingConfig.
 *
 * Config beeketing plugin
 *
 * @class		BeeketingSDK_Config_BeeketingConfig
 * @version		1.0.0
 * @author		Beeketing
 * @since		1.0.0
 */

class BeeketingSDK_Config_BeeketingConfig
{
    /**
     * Beeketing format date
     *
     * @since 2.0.0
     */
    const BEEKETING_FORMAT_DATE = 'Y-m-d\TH:i:s\Z';

    /**
     * Type webhook
     *
     * @since 2.0.0
     */
    const SOURCE_TYPE_WEBHOOK = 'webhook';

    /**
     * Beeketing path
     *
     * @var string
     * @since 1.0.0
     */
    protected $path;

    /**
     * Default config
     *
     * @var array
     * @since 1.0.0
     */
    protected $defaultConfig = array(
        'BEEKETING_PROFILE_URL' => 'user/profile',
        'BEEKETING_GET_APPS_DATA_API' => 'get-apps-data-by-key/',
        'BEEKETING_CHECK_STATUS_KEY_API' => 'check-key-exists/',
        'BEEKETING_SIGN_IN_URL' => 'sign-in?platform='
    );

    /**
     * @since 1.0.0
     * @var string
     */
    protected $apiKey;

    /**
     * @since 1.0.0
     * @var string
     */
    protected $env;

    /**
     * @since 1.0.0
     * @var string
     */
    protected $platform;

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->path = BEEKETING_PATH;
    }

    /**
     * Set environment
     *
     * @since 1.0.0
     * @param $env
     * @return $this
     * @throws Exception
     */
    public function setEnv( $env )
    {
        $this->env = $env;

        return $this;
    }

    /**
     * Get environment
     *
     * @since 1.0.0
     * @return string
     */
    public function getEnv()
    {
        return $this->env;
    }

    /**
     * Get api key
     *
     * @since 1.0.0
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Set api key
     *
     * @since 1.0.0
     * @param $apiKey
     * @return $this
     */
    public function setApiKey( $apiKey )
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Get platform
     *
     * @since 1.0.0
     * @return string
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * Set platform
     *
     * @since 1.0.0
     * @param $platform
     * @return $this
     */
    public function setPlatform( $platform )
    {
        $this->platform = $platform;

        return $this;
    }


    /**
     * Get config by name
     *
     * @since 1.0.0
     * @param $name
     * @return string|bool
     */
    public function getConfigByName( $name )
    {
        // If config not found.
        if ( ! isset( $this->defaultConfig[ $name ] ) ) {
            return false;
        }

        return $this->defaultConfig[ $name ];
    }

    /**
     * Get profile url
     *
     * @since 1.0.0
     * @return string
     */
    public function getProfileUrl()
    {

        return $this->path . $this->getConfigByName( 'BEEKETING_PROFILE_URL' );
    }

    /**
     * Get sign in url
     *
     * @since 1.0.0
     * @return string
     */
    public function getSignInUrl()
    {
        return $this->path . '/' . $this->getConfigByName( 'BEEKETING_SIGN_IN_URL' ) . $this->getPlatform();
    }

    /**
     * Get apps data by domain from beeketing.
     *
     * @since 1.0.0
     * @return array
     */
    public function getAppsDataByApiKey()
    {
        $platform   = $this->getPlatform();
        $apiKey     = $this->getApiKey();

        $data = @file_get_contents( $this->path . '/' . $this->getConfigByName( 'BEEKETING_GET_APPS_DATA_API' ) . $apiKey . '?platform=' . $platform  );
        $data = json_decode( $data, true ); // decode & convert to array

        if ( json_last_error() === JSON_ERROR_NONE ) {
            return $data;
        }

        return array();
    }

    /**
     * Is api key exists?
     *
     * @since 1.0.0
     * @param string
     * @return bool
     */
    public function isApiKeyExists( $apiKey )
    {
        $platform = $this->getPlatform();

        $data = @file_get_contents( $this->path . '/' . $this->getConfigByName( 'BEEKETING_CHECK_STATUS_KEY_API' ) . $apiKey . '?platform=' . $platform );
        $data = json_decode( $data );

        return ( isset( $data->status ) ) ? $data->status : false;
    }

    /**
     * Get path
     *
     * @since 1.0.0
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set path
     *
     * @since 1.0.0
     * @param string $path
     */
    public function setPath( $path )
    {
        $this->path = $path;
    }
}