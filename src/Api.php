<?php

/*
 * This file is part of Facturama PHP SDK.
 *
 * (c) Javier Telio <jtelio118@gmail.com>
 *
 * This source file is subject to a MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Facturama;

use Facturama\Exception\ModelException;
use Facturama\Exception\RequestException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;

/**
 * Facturama API
 *
 * @author Javier Telio Zapot <jtelio118@gmail.com>
 */
class Api
{
    /**
     * @version 1.0.0
     */
    const VERSION = '1.0.0';
    /**
     * Configuration for urls
     */
    const API_URL = 'https://www.api.facturama.com.mx/api';
    /**
     * @var string
     */
    private $user;
    /**
     * @var string
     */
    private $password;
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * Init configuration
     *
     * @param string $user     username
     * @param string $password password
     * @param array $config
     */
    public function __construct($user = null, $password = null, array $config = [])
    {
        $this->user = $user ? $user : config('facturama.credentials.username');
        $this->password = $password ? $password : config('facturama.credentials.password');
        $this->client = new Client($config + ['headers' => ['User-Agent' => 'FACTURAMA-PHP-SDK-1.0.0']]);
        $this->client->setDefaultOption('verify', false);
        $this->client->setDefaultOption('auth', [$user, $password]);
        $this->client->setDefaultOption('connect_timeout', 10);
        $this->client->setDefaultOption('timeout', 60);
    }

    /**
     * Get Request
     *
     * @param  string $path
     * @param  array $params
     *
     * @return \stdClass
     */
    public function get($path, array $params = [])
    {
        return $this->executeRequest('GET', $path, ['query' => $params]);
    }

    /**
     * POST Request
     *
     * @param  string $path
     * @param  array|null $body
     * @param  array $params
     *
     * @return \stdClass
     */
    public function post($path, array $body = null, array $params = [])
    {
        return $this->executeRequest('POST', $path, ['json' => $body, 'query' => $params]);
    }

    /**
     * PUT Request
     *
     * @param  string $path
     * @param  array|null $body
     * @param  array $params
     *
     * @return \stdClass
     */
    public function put($path, array $body = null, array $params = [])
    {
        return $this->executeRequest('PUT', $path, ['json' => $body, 'query' => $params]);
    }

    /**
     * DELETE Request
     *
     * @param  string $path
     * @param  array $params
     *
     * @return \stdClass
     */
    public function delete($path, array $params = [])
    {
        return $this->executeRequest('DELETE', $path, ['query' => $params]);
    }

    /**
     * Execute the request and return the resulting object
     *
     * @param string $method
     * @param string $url
     * @param array $options
     *
     * @throws \RuntimeException|\LogicException
     *
     * @return \stdClass
     */
    private function executeRequest($method, $url, array $options = [])
    {
        try {
            $request = $this->client->createRequest($method, sprintf('%s/%s', self::API_URL, $url), $options);
            $response = $this->client->send($request);
            $content = trim($response->getBody()->getContents());
        } catch (GuzzleRequestException $e) {
            if ($e->hasResponse()) {
                $content = trim($e->getResponse()->getBody()->getContents());
                if (($object = json_decode($content)) && isset($object->Message)) {
                    $modelException = null;
                    if (isset($object->ModelState)) {
                        $modelExceptionMessages = [];
                        foreach ($object->ModelState as $invalidPropertyMessages) {
                            $modelExceptionMessages = array_merge($modelExceptionMessages, $invalidPropertyMessages);
                        }
                        $modelExceptionMessage = null;
                        if ($modelExceptionMessages) {
                            $modelExceptionMessage = implode('; ', $modelExceptionMessages).'.';
                        }
                        $modelException = new ModelException($modelExceptionMessage, $e->getCode());
                    }
                    throw new RequestException($object->Message, 0, $modelException);
                }

                throw new RequestException($content ?: $e->getMessage(), $e->getCode());
            }

            throw new RequestException($e->getMessage(), $e->getCode());
        }

        return json_decode($content);
    }
}
