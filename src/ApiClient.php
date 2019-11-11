<?php

namespace As3\OmedaSDK;

use As3\OmedaSDK\Exception\RuntimeException;
use As3\Parameters\DefinedParameters as Parameters;
use As3\Parameters\Definitions;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class ApiClient
{
    const HOST_PROD     = 'ows.omeda.com';
    const HOST_STAGING  = 'ows.omedastaging.com';
    const BASE_ENDPOINT = '/webservices/rest/';

    /**
     * The HTTP client for sending requests.
     *
     * @var GuzzleHttp\Client
     */
    private $client;

    /**
     * The Omeda API configuration.
     *
     * @var Paramters
     */
    private $configuration;

    /**
     * The API client parameters definitions/requirements.
     *
     * @var Definitions
     */
    private $definitions;

    /**
     * Determines if API client is using the Omeda staging environment.
     *
     * @var bool
     */
    private $isStaging = false;

    /**
     * The Omeda API resource instances.
     *
     * @var AbstractResource[]
     */
    private $resources = [];

    /**
     * Magic call method to access resources as an object method.
     *
     * @return AbstractResource
     */
    public function __call($name, array $args)
    {
        return $this->getResource($name);
    }

    /**
     * Constructor.
     *
     * @param   array|null  $settings   The Omeda API settings.
     * @param   bool        $staging    Whether the staging environment should be used.
     */
    public function __construct(array $settings = null, $staging = false)
    {
        $this->initClient();
        $this->useStaging($staging);
        $this->setParameterDefinitions();
        $this->setResources();
        if (null !== $settings) {
            $this->configure($settings);
        }
    }

    /**
     * Magic get method to access resources as an object property.
     *
     * @return AbstractResource
     */
    public function __get($name)
    {
        return $this->getResource($name);
    }

    /**
     * Builds a standard brand endpoint, e.g. /brand/{brandKey}/{$endpoint}
     *
     * @param   string  $endpoint
     * @return  string
     */
    public function buildBrandEndpoint($endpoint)
    {
        return $this->buildEndpoint('brand', 'brandKey', $endpoint);
    }

    /**
     * Builds a client endpoint, e.g. /client/{clientKey}/{$endpoint}
     *
     * @param   string  $endpoint
     * @return  string
     */
    public function buildClientEndpoint($endpoint)
    {
        return $this->buildEndpoint('client', 'clientKey', $endpoint);
    }

    /**
     * Configures/re-configures the API client.
     *
     * @param   array   $settings
     * @return  self
     */
    public function configure(array $settings)
    {
        $this->configuration = $this->createParameterInstance($settings);
        return $this;
    }

    /**
     * Determines if hte current Omeda API client configuration is valid.
     *
     * @return  bool
     */
    public function hasValidConfig()
    {
        return null !== $this->configuration && $this->configuration->valid();
    }

    /**
     * Gets the current Omeda API configuration as a read-only array.
     *
     * @return  array
     */
    public function getConfiguration()
    {
        if (null === $this->configuration) {
            return [];
        }
        return $this->configuration->toArray();
    }

    /**
     * Gets an API resource
     *
     * @param   string      $key
     * @return  AbstractResource
     * @throws  RuntimeException If resource is not found.
     */
    public function getResource($key)
    {
        if (!isset($this->resources[$key])) {
            throw new RuntimeException(sprintf('No Omeda API resource exists for "%s"', $key));
        }
        return $this->resources[$key];
    }

    /**
     * Determines if the client is using the Omeda production environment.
     *
     * @return  bool
     */
    public function isUsingProduction()
    {
        return false === $this->isUsingStaging();
    }

    /**
     * Determines if the client is using the Omeda staging environment.
     *
     * @return  bool
     */
    public function isUsingStaging()
    {
        return true === $this->isStaging;
    }

    /**
     * Parses an API response body.
     *
     * @param   Response    $response
     * @return  array
     */
    public function parseApiResponse(Response $response)
    {
        $payload = @json_decode($response->getBody()->getContents(), true);
        if (!is_array($payload)) {
            throw new RuntimeException('Unable to parse API response');
        }
        return $payload;
    }

    /**
     * Sends a request to the Omeda API.
     *
     * @return  \GuzzleHttp\Psr7\Response
     * @throws  RuntimeException
     */
    public function request($method, $endpoint, $body = null, $contentType = 'application/json')
    {
        if (false === $this->hasValidConfig()) {
            throw new RuntimeException(sprintf('The Omeda API configuration is not valid. Unable to perform request.'));
        }
        $method      = strtoupper($method);
        $contentType = strtolower($contentType);
        $endpoint    = trim($endpoint, '/');
        $uri         = $this->prepareRequestUri($endpoint);

        $body    = $this->prepareRequestBody($body, $contentType);
        $options = [
            'headers' => $this->prepareRequestHeaders($method, $contentType),
        ];
        if (!empty($body)) {
            $options['body'] = $body;
        }
        return $this->client->request($method, $uri, $options);
    }

    /**
     * Specifies whether to use the Omeda staging API.
     *
     * @param   bool    $staging
     * @return  self
     */
    public function useStaging($staging = true)
    {
        $this->isStaging = (boolean) $staging;
        return $this;
    }

    /**
     * Builds a complete API endpoint for the type, the config value to inject, and the remaining endpoint details.
     *
     * @param   string  $type       One of brand or client.
     * @param   string  $configKey  The config key to use to get the value.
     * @param   string  $endpoint   The remaining endpoint.
     * @return  string
     * @throws  RuntimeException    If the client config is currently invalid.
     */
    private function buildEndpoint($type, $configKey, $endpoint)
    {
        if (false === $this->hasValidConfig()) {
            throw new RuntimeException(sprintf('The Omeda API configuration is not valid. Unable to build endpoint.'));
        }
        return sprintf('/%s/%s/%s', $type, $this->configuration->get($configKey), trim($endpoint, '/'));
    }

    /**
     * Creates a new Parameters instance based on the provided params.
     *
     * @param   array   $parameters
     * @return  Parameters
     */
    private function createParameterInstance(array $parameters)
    {
        return new Parameters($this->definitions, $parameters);
    }

    /**
     * Sets the HTTP client instance.
     *
     * @return  self
     */
    private function initClient()
    {
        $this->client = new Client();
        return $this;
    }

    /**
     * Prepares a request body.
     *
     * @param   mixed   $body
     * @param   string  $contentType
     * @return  string|null
     */
    private function prepareRequestBody($body, $contentType)
    {
        if (is_scalar($body)) {
            $body = (string) $body;
        } elseif (is_array($body) && 'application/json' === $contentType) {
            $body = @json_encode($body);
        }
        if (empty($body)) {
            $body = null;
        }
        return $body;
    }

    /**
     * Prepares the request headers
     *
     * @param   string  $method
     * @param   string  $contentType
     * @return  array
     */
    private function prepareRequestHeaders($method, $contentType)
    {
        $headers = [
            'x-omeda-appid' => $this->configuration->get('appId'),
        ];
        if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
            $headers['x-omeda-inputid'] = $this->configuration->get('inputId');
            $headers['content-type']    = $contentType;
        }
        return $headers;
    }

    /**
     * Prepares the request uri.
     *
     * @param   string  $endpoint
     * @return  string
     */
    private function prepareRequestUri($endpoint)
    {
        $host = $this->isUsingStaging() ? self::HOST_STAGING : self::HOST_PROD;
        return sprintf('https://%s/%s/%s', $host, trim(self::BASE_ENDPOINT, '/'), trim($endpoint, '/'));
    }

    /**
     * Sets the API parameter definitions.
     *
     * @return  self
     */
    private function setParameterDefinitions()
    {
        $this->definitions = new Definitions();
        $this->definitions
            ->add('clientKey', 'string', null, true)
            ->add('brandKey', 'string', null, true)
            ->add('appId', 'string', null, true)
            ->add('inputId', 'string', null, true)
        ;
        return $this;
    }

    /**
     * Sets the available API resource instances.
     *
     * @return  self
     */
    private function setResources()
    {
        $namespace = sprintf('%s\ApiResources', __NAMESPACE__);
        $resources = [
            'brand'     => 'BrandResource',
            'customer'  => 'CustomerResource',
            'omail'     => 'OmailResource',
            'utility'   => 'UtilityResource',
        ];
        foreach ($resources as $key => $class) {
            $fqcn = sprintf('%s\%s', $namespace, $class);
            $this->resources[$key] = new $fqcn($key, $this);
        }
        return $this;
    }
}
