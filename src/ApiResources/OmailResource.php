<?php

namespace As3\OmedaSDK\ApiResources;

use As3\OmedaSDK\Exception\InvalidArgumentException;

class OmailResource extends AbstractResource
{
    /**
     * Deployment Lookup Resource.
     * The Deployment Lookup API provides the ability to retrieve deployment information such as link tracking, delivery statistics, deployment status, history, etc.
     *
     * @link    https://jira.omeda.com/wiki/en/Deployment_Lookup_Resource
     *
     * @param   string  $trackId            The deployment track identifier.
     * @throws  InvalidArgumentException    If the track id is empty.
     * @return  \GuzzleHttp\Psr7\Response
     */
    public function deploymentLookup($trackId)
    {
        if (empty($trackId)) {
            throw new InvalidArgumentException('The deployment track ID cannot be empty.');
        }
        $endpoint = $this->client->buildBrandEndpoint(sprintf('/omail/deployment/lookup/%s/*', $trackId));
        return $this->client->request('GET', $endpoint);
    }

    /**
     * Deployment Search Resource.
     * This service retrieves a list of most recent deployments for a given brand based on search parameters.
     *
     * @link    https://jira.omeda.com/wiki/en/Deployment_Search_Resource
     *
     * @param   array   $payload            The search parameters to send.
     * @throws  InvalidArgumentException    If search parameter payload is empty.
     * @return  \GuzzleHttp\Psr7\Response
     */
    public function deploymentSearch(array $payload)
    {
        if (empty($payload)) {
            throw new InvalidArgumentException('The deployment search payload cannot be empty.');
        }
        $endpoint = $this->client->buildBrandEndpoint('/omail/deployment/search/*');
        return $this->client->request('POST', $endpoint, $payload);
    }

    /**
     * Optin Queue Service.
     * Opts an email address in to the provided deployment types.
     *
     * @link    https://jira.omeda.com/wiki/en/Optin_Queue_Service
     *
     * @param   string      $emailAddress       The customer's email address for which the deployment type opt-in is requested.
     * @param   int|array   $deploymentTypeIds  The deployment type(s) for which the opt-in is requested.
     * @param   bool        $deleteOptOut       Whether to delete any existing opt-out.
     * @param   string|null $source             Allows you to set the source of the opt-in. If omitted, the default source is "Optout API 2."
     * @throws  InvalidArgumentException        If no deployment types were specified.
     * @return  \GuzzleHttp\Psr7\Response
     */
    public function optInDeployment($emailAddress, $deploymentTypeIds, $deleteOptOut = true, $source = null)
    {
        $this->validateEmailAddress($emailAddress);
        $deploymentTypeIds = (array) $deploymentTypeIds;
        if (empty($deploymentTypeIds)) {
            throw new InvalidArgumentException('At least one deployment type must be specified.');
        }

        $payload = [
            'EmailAddress'      => $emailAddress,
            'DeploymentTypeId'  => $deploymentTypeIds,
            'DeleteOptOut'      => (integer) (boolean) $deleteOptOut,
        ];
        if (isset($source)) {
            $payload['Source'] = $source;
        }
        $endpoint = $this->client->buildClientEndpoint('/optinfilterqueue/*');
        return $this->client->request('POST', $endpoint, ['DeploymentTypeOptIn' => [$payload]]);
    }

    /**
     * Optout Queue Service.
     * Opts an email address out of ALL deployment types for the provided brand(s).
     *
     * @link    https://jira.omeda.com/wiki/en/Optout_Queue_Service
     *
     * @param   string          $emailAddress   The customer's email address for which the deployment type opt-out is requested.
     * @param   string|array    $brandIds       The brand(s) for which the opt-out is requested.
     * @param   string|null     $source         Allows you to set the source of the opt-out. If omitted, the default source is "Optout API 2."
     * @throws  InvalidArgumentException        If no brands were specified.
     * @return  \GuzzleHttp\Psr7\Response
     */
    public function optOutBrand($emailAddress, $brandIds, $source = null)
    {
        $this->validateEmailAddress($emailAddress);
        $brandIds = (array) $brandIds;
        if (empty($brandIds)) {
            throw new InvalidArgumentException('At least one brand must be specified.');
        }

        $payload = [
            'EmailAddress'  => $emailAddress,
            'BrandId'       => $brandIds,
        ];
        if (isset($source)) {
            $payload['Source'] = $source;
        }
        return $this->sendOptOuts(['BrandOptOut' => [$payload]]);
    }

    /**
     * Optout Queue Service.
     * Opts an email address out of ALL deployment types for ALL brands. Considered a global opt-out.
     *
     * @link    https://jira.omeda.com/wiki/en/Optout_Queue_Service
     *
     * @param   string      $emailAddress   The customer's email address for which the deployment type opt-out is requested.
     * @param   string|null $source         Allows you to set the source of the opt-out. If omitted, the default source is "Optout API 2."
     * @return  \GuzzleHttp\Psr7\Response
     */
    public function optOutClient($emailAddress, $source = null)
    {
        $this->validateEmailAddress($emailAddress);

        $payload = ['EmailAddress' => $emailAddress];
        if (isset($source)) {
            $payload['Source'] = $source;
        }
        return $this->sendOptOuts(['ClientOptOut' => [$payload]]);
    }


    /**
     * Optout Queue Service.
     * Opts an email address out of the provided deployment types.
     *
     * @link    https://jira.omeda.com/wiki/en/Optout_Queue_Service
     *
     * @param   string      $emailAddress       The customer's email address for which the deployment type opt-out is requested.
     * @param   int|array   $deploymentTypeIds  The deployment type(s) for which the opt-out is requested.
     * @param   string|null $source             Allows you to set the source of the opt-out. If omitted, the default source is "Optout API 2."
     * @throws  InvalidArgumentException        If no deployment types were specified.
     * @return  \GuzzleHttp\Psr7\Response
     */
    public function optOutDeployment($emailAddress, $deploymentTypeIds, $source = null)
    {
        $this->validateEmailAddress($emailAddress);

        $deploymentTypeIds = (array) $deploymentTypeIds;
        if (empty($deploymentTypeIds)) {
            throw new InvalidArgumentException('At least one deployment type must be specified.');
        }

        $payload = [
            'EmailAddress'      => $emailAddress,
            'DeploymentTypeId'  => $deploymentTypeIds,
        ];
        if (isset($source)) {
            $payload['Source'] = $source;
        }
        return $this->sendOptOuts(['DeploymentTypeOptOut' => [$payload]]);
    }

    /**
     * Sends opt out information to the Omeda filter queue endpoint.
     *
     * @param   array   $payload
     * @return  \GuzzleHttp\Psr7\Response
     */
    private function sendOptOuts(array $payload)
    {
        $endpoint = $this->client->buildClientEndpoint('/optoutfilterqueue/*');
        return $this->client->request('POST', $endpoint, $payload);
    }

    /**
     * Validates the provided email address.
     *
     * @param   string  $emailAddress
     * @throws  InvalidArgumentException
     */
    private function validateEmailAddress($emailAddress)
    {
        if (empty($emailAddress)) {
            throw new InvalidArgumentException('The email address cannot be empty.');
        }
    }
}
