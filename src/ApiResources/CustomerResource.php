<?php

namespace As3\OmedaSDK\ApiResources;

use GuzzleHttp\Exception\ClientException;

class CustomerResource extends AbstractResource
{
    /**
     * Customer Comprehensive Lookup Service.
     *
     * @link    https://jira.omeda.com/wiki/en/Customer_Comprehensive_Lookup_Service
     *
     * @param   int  $customerId
     * @param   bool $returnMerged  Whether to automatically return a merged customer if encountered.
     * @return  \GuzzleHttp\Psr7\Response
     */
    public function lookup($customerId, $returnMerged = true)
    {
        $endpoint = $this->client->buildBrandEndpoint(sprintf('/customer/%s/comp/*', $customerId));
        return $this->handleMergedCustomer($returnMerged, $endpoint);
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
     * @param   bool $returnMerged  Whether to automatically return a merged customer if encountered.
     * @return  \GuzzleHttp\Psr7\Response
     */
    public function lookupByEncryptedId($encryptedId, $returnMerged = true)
    {
        return $this->lookupById($encryptedId, $returnMerged);
    }

    /**
     * Customer Lookup Service By External ID
     *
     * @link    https://jira.omeda.com/wiki/en/Customer_Lookup_Service_By_External_ID
     *
     * @param   string  $namespace  The external customer namespace.
     * @param   string  $externalId The external customer id.
     * @return  \GuzzleHttp\Psr7\Response
     */
    public function lookupByExternalId($namespace, $externalId)
    {
        $endpoint = $this->client->buildBrandEndpoint(sprintf('/customer/%s/externalcustomeridnamespace/%s/externalcustomerid/*', $namespace, $externalId));
        return $this->client->request('GET', $endpoint);
    }

    /**
     * Customer Lookup Service By CustomerId.
     *
     * @link    https://jira.omeda.com/wiki/en/Customer_Lookup_Service_By_CustomerId
     *
     * @param   int  $customerId
     * @param   bool $returnMerged  Whether to automatically return a merged customer if encountered.
     * @return  \GuzzleHttp\Psr7\Response
     */
    public function lookupById($customerId, $returnMerged = true)
    {
        $endpoint = $this->client->buildBrandEndpoint(sprintf('/customer/%s/*', $customerId));
        return $this->handleMergedCustomer($returnMerged, $endpoint);

    }

    /**
     * Save Customer and Order API.
     *
     * @link    https://jira.omeda.com/wiki/en/Save_Customer_and_Order_API
     *
     * @param   array   $payload    The customer payload.
     * @return  \GuzzleHttp\Psr7\Response
     */
    public function save(array $payload)
    {
        $endpoint = $this->client->buildBrandEndpoint('/storecustomerandorder/*');
        return $this->client->request('POST', $endpoint, $payload);
    }

    /**
     * If a `merged into customer` error was encountered, will attempt to retrieve the merged customer.
     * Will only run if enabled.
     *
     * @param   bool    $enabled
     * @param   string  $endpoint
     * @return  \GuzzleHttp\Psr7\Response
     * @throws  ClientException
     */
    private function handleMergedCustomer($enabled, $endpoint)
    {
        $enabled = (boolean) $enabled;
        if (false === $enabled) {
            return $this->client->request('GET', $endpoint);
        }
        try {
            return $this->client->request('GET', $endpoint);
        } catch (ClientException $e) {
            if (404 != $e->getCode()) {
                throw $e;
            }
            $body = $this->client->parseApiResponse($e->getResponse());
            if (is_array($body) && isset($body['Errors']) && is_array($body['Errors'])) {
                foreach ($body['Errors'] as $error) {
                    if (isset($error['MergedIntoCustomerId'])) {
                        return $this->lookupById($error['MergedIntoCustomerId']);
                    }
                }
                throw $e;
            }
            throw $e;
        }
    }
}
