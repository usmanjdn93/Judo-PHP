<?php

namespace Judopay;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Message\Request as GuzzleRequest;
use Guzzle\Http\Message\Response;
use Guzzle\Log\PsrLogAdapter;
use Guzzle\Plugin\Log\LogPlugin;
use Judopay\Exception\ApiException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class Request implements LoggerAwareInterface
{
    /** @var Configuration */
    protected $configuration;

    /** @var  Client */
    protected $client;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->logger = $this->configuration->get('logger');
    }

    public function setClient(Client $client)
    {
        $this->client = $client;

        // Set headers
        $this->client->setDefaultOption(
            'headers',
            [
                'API-Version'  => $this->configuration->get('apiVersion'),
                'Accept'       => 'application/json; charset=utf-8',
                'Content-Type' => 'application/json',
            ]
        );

        // Use CA cert bundle to verify SSL connection
        $this->client->setSslVerification(
            __DIR__
            .'/../../cert/digicert_sha256_ca.pem'
        );

        // Set up logging
        $adapter = new PsrLogAdapter(
            $this->logger
        );
        $logPlugin = new LogPlugin(
            $adapter,
            $this->configuration->get('httpLogFormat')
        );

        // Set user agent
        $this->client->setUserAgent($this->configuration->get('userAgent'));

        // Attach the plugin to the client, which will in turn be attached to all
        // requests generated by the client
        $this->client->addSubscriber($logPlugin);
    }

    /**
     * Make a GET request to the specified resource path
     * @param string $resourcePath
     * @return array|Response
     */
    public function get($resourcePath)
    {
        $endpointUrl = $this->configuration->get('endpointUrl');
        $guzzleRequest = $this->client->get(
            $endpointUrl.'/'.$resourcePath
        );

        return $this->send($guzzleRequest);
    }

    /**
     * Make a POST request to the specified resource path
     * @param string $resourcePath
     * @param array  $data
     * @return array|Response
     */
    public function post($resourcePath, $data)
    {
        $endpointUrl = $this->configuration->get('endpointUrl');
        $guzzleRequest = $this->client->post(
            $endpointUrl.'/'.$resourcePath,
            [],
            $data
        );

        return $this->send($guzzleRequest);
    }

    public function setRequestAuthentication(GuzzleRequest $request)
    {
        $oauthAccessToken = $this->configuration->get('oauthAccessToken');

        // Do we have an oAuth2 access token?
        if (!empty($oauthAccessToken)) {
            $request->setHeader('Authorization', 'Bearer '.$oauthAccessToken);
        } else {
            // Otherwise, use basic authentication
            $request->setAuth(
                $this->configuration->get('apiToken'),
                $this->configuration->get('apiSecret')
            );
        }

        return $request;
    }

    /**
     * @inheritdoc
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Configuration getter
     * @todo Ideally, configuration should be a separate dependency
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param GuzzleRequest $guzzleRequest
     * @throws ApiException
     * @return array|Response
     */
    protected function send(GuzzleRequest $guzzleRequest)
    {
        $guzzleRequest = $this->setRequestAuthentication($guzzleRequest);

        try {
            $guzzleResponse = $guzzleRequest->send();
        } catch (BadResponseException $e) {
            // Guzzle throws an exception when it encounters a 4xx or 5xx error
            // Rethrow the exception so we can raise our custom exception classes
            throw ApiException::factory($e->getResponse());
        }

        return $guzzleResponse;
    }
}
