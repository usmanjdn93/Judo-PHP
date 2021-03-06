<?php

namespace Tests\Helpers;

use Judopay\Configuration;

class ConfigHelper
{
    /**
     * Returns Configuration from the environment variables
     * @param array $settings custom settings
     * @return Configuration
     */
    public static function getConfig(array $settings = array())
    {
        return new Configuration(
            $settings +
            array(
                'apiToken'      => getenv('JUDO_API_TOKEN'),
                'apiSecret'     => getenv('JUDO_API_SECRET'),
                'judoId'        => getenv('JUDO_API_ID'),
                'useProduction' => false,
            )
        );
    }

    /**
     * Returns Configuration from an alternate set of environment variables
     * @param array $settings custom settings
     * @return Configuration
     */
    public static function getConfigAlt(array $settings = array())
    {
        return new Configuration(
            $settings +
            array(
                'apiToken'      => getenv('JUDO_API_TOKEN_2'),
                'apiSecret'     => getenv('JUDO_API_SECRET_2'),
                'judoId'        => getenv('JUDO_API_ID_2'),
                'useProduction' => false,
            )
        );
    }

    /**
     * Returns Configuration from settings array
     * @param array $credentials [judoId, apiToken, apiSecret]
     * @param array $settings custom settings
     * @return Configuration
     */
    public static function getConfigFromList(array $credentials, array $settings = array())
    {
        return new Configuration(
            array(
                'judoId'        => $credentials[0],
                'apiToken'      => $credentials[1],
                'apiSecret'     => $credentials[2],
                'useProduction' => false,
            ) + $settings
        );
    }
}
