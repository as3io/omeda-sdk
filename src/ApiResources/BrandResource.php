<?php

namespace As3\OmedaSDK\ApiResources;

class BrandResource extends AbstractResource
{
    /**
     * Performs a Brand Comprehensive Lookup
     * https://wiki.omeda.com/wiki/en/Brand_Comprehensive_Lookup_Service
     *
     * @return  \GuzzleHttp\Psr7\Response
     */
    public function lookup()
    {
        $endpoint = $this->client->buildBrandEndpoint('/comp/*');
        return $this->client->request('GET', $endpoint);
    }
}
