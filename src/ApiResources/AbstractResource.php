<?php

namespace As3\OmedaSDK\ApiResources;

use As3\OmedaSDK\ApiClient;

abstract class AbstractResource
{
    /**
     * @var ApiClient
     */
    protected $client;

    /**
     * @var string
     */
    protected $key;

    /**
     * Constructor.
     *
     * @param   string      $key
     * @param   ApiClient   $client
     */
    public function __construct($key, ApiClient $client)
    {
        $this->key    = $key;
        $this->client = $client;
    }
}
