<?php

namespace As3\OmedaSDK\ApiResources;

class CustomerResource extends AbstractResource
{
    /**
     * Customer Comprehensive Lookup Service.
     *
     * @link    https://jira.omeda.com/wiki/en/Customer_Comprehensive_Lookup_Service
     *
     * @param   int  $customerId
     * @return  \GuzzleHttp\Psr7\Response
     */
    public function lookup($customerId)
    {
        $endpoint = $this->client->buildBrandEndpoint(sprintf('/customer/%s/comp/*', $customerId));
        return $this->client->request('GET', $endpoint);
    }

    /**
     * Customer Lookup Service By Email.
     *
     * @link    https://jira.omeda.com/wiki/en/Customer_Lookup_Service_By_Email
     *
     * @param   string      $emailAddress
     * @param   int|null    $productId
     * @return  \GuzzleHttp\Psr7\Response
     */
    public function lookupByEmail($emailAddress, $productId = null)
    {
        $path = sprintf('/customer/email/%s', $emailAddress);
        if (!empty($productId)) {
            $path = sprintf('%s/productid/%s/*', $path, $productId);
        } else {
            $path = sprintf('%s/*', $path);
        }
        $endpoint = $this->client->buildBrandEndpoint($path);
        return $this->client->request('GET', $endpoint);
    }

    /**
     * Customer Lookup Service By EncryptedCustomerId.
     *
     * @link    https://jira.omeda.com/wiki/en/Customer_Lookup_Service_By_EncryptedCustomerId
     *
     * @param   string      $encryptedId
     * @return  \GuzzleHttp\Psr7\Response
     */
    public function lookupByEncryptedId($encryptedId)
    {
        $endpoint = $this->client->buildBrandEndpoint(sprintf('/customer/%s/encrypted/*', $encryptedId));
        return $this->client->request('GET', $endpoint);
    }

    /**
     * Customer Lookup Service By CustomerId.
     *
     * @link    https://jira.omeda.com/wiki/en/Customer_Lookup_Service_By_CustomerId
     *
     * @param   int  $customerId
     * @return  \GuzzleHttp\Psr7\Response
     */
    public function lookupById($customerId)
    {
        $endpoint = $this->client->buildBrandEndpoint(sprintf('/customer/%s/*', $customerId));
        return $this->client->request('GET', $endpoint);
    }
}
