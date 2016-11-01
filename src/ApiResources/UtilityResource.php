<?php

namespace As3\OmedaSDK\ApiResources;

class UtilityResource extends AbstractResource
{
    /**
     * Run Processor API.
     *
     * @link    https://jira.omeda.com/wiki/en/Run_Processor_API
     *
     * @param   string|array    $transactionIds
     * @return  \GuzzleHttp\Psr7\Response
     */
    public function runProcessor($transactionIds)
    {
        $ids = [];
        foreach ((array) $transactionIds as $id) {
            $id = (integer) $id;
            if (empty($id)) {
                continue;
            }
            $ids[] = $id;
        }

        $total = count($ids);
        $body  = ['Process' => []];
        if (0 === $total) {
            throw new \InvalidArgumentException('You must provide at least one transaction ID to run the processor.');
        }
        foreach ($ids as $id) {
            $body['Process'][] = [
                'TransactionId' => $id,
            ];
        }

        $endpoint = $this->client->buildBrandEndpoint('/runprocessor/*');
        return $this->client->request('POST', $endpoint, $body);
    }
}
