<?php

namespace As3\OmedaSDK\ApiResources;

use As3\OmedaSDK\Exception\InvalidArgumentException;

class OmailResource extends AbstractResource
{
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
