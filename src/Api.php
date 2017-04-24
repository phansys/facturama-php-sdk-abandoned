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
     * Configuration for CURL
     *
     * @var array
     */
    protected $curl_opts = [
        CURLOPT_USERAGENT => 'FACTURAMA-PHP-SDK-1.0.0',
        //CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HEADER => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
    ];
    /**
     * User Name
     *
     * @var string
     */
    protected $user;
    /**
     * Password
     *
     * @var string
     */
    protected $password;

    /**
     * Init configuration
     *
     * @param string $user     username
     * @param string $password password
     * @param array $curl_opts curl options
     */
    public function __construct($user, $password, array $curl_opts = [])
    {
        $this->user = $user;
        $this->password = $password;

        $this->curl_opts = $curl_opts ? array_merge($this->curl_opts, $curl_opts) : $this->curl_opts;
    }

    /**
     * Get Request
     *
     * @param  string $path
     * @param  array $params
     *
     * @return mixed
     */
    public function get($path, array $params = [])
    {
        $opts = [
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Basic '.$this->getCredentials(),
            ],
        ];
        $exec = $this->execute($path, [], $params);

        return $exec;
    }

    /**
     * POST Request
     *
     * @param  string $path
     * @param  array $body
     * @param  array  $params
     *
     * @return mixed
     */
    public function post($path, array $body = [], array $params = [])
    {
        $body = json_encode($body);
        $opts = [
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Basic '.$this->getCredentials(),
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
        ];
        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * PUT Request
     *
     * @param  string $path
     * @param  array $body
     * @param  array $params
     *
     * @return mixed
     */
    public function put($path, array $body = [], array $params = [])
    {
        $body = json_encode($body);
        $opts = [
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Basic '.$this->getCredentials(),
            ],
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => $body,
        ];

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * DELETE Request
     *
     * @param  string $path
     * @param  array $params
     *
     * @return mixed
     */
    public function delete($path, array $params = [])
    {
        $opts = [
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Basic '.$this->getCredentials(),
            ],
            CURLOPT_CUSTOMREQUEST => 'DELETE',
        ];

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute all requests and returns the json body and headers
     *
     * @param  string $path
     * @param  array  $opts
     * @param  array  $params
     *
     * @return mixed
     */
    public function execute($path, array $opts = [], array $params = [])
    {
        $uri = $this->make_path($path, $params);
        $ch = curl_init($uri);

        curl_setopt_array($ch, $this->curl_opts);
        curl_setopt($ch, CURLOPT_USERPWD, $this->user.':'.$this->password);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_getinfo($ch, CURLINFO_HEADER_OUT);

        if (!empty($opts)) {
            curl_setopt_array($ch, $opts);
        }

        $response = curl_exec($ch);
        $response = preg_split('/^\r?$/m', $response, 2);
        $return['body'] = json_decode(trim($response[1]));
        $return['httpCode'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $return;
    }

    /**
     * Check and construct an real URL to make request
     *
     * @param  string $path
     * @param  array  $params
     *
     * @return string
     */
    public function make_path($path, array $params = [])
    {
        if (!preg_match('/^http/', $path)) {
            if (!preg_match("/^\//", $path)) {
                $path = '/'.$path;
            }
            $uri = self::API_URL.$path;
        } else {
            $uri = $path;
        }
        if (!empty($params)) {
            $paramsJoined = [];
            foreach ($params as $param => $value) {
                $paramsJoined[] = "$param=$value";
            }
            $params = '?'.implode('&', $paramsJoined);
            $uri = $uri.$params;
        }

        return $uri;
    }

    /**
     * Generate Basic Auth
     *
     * @return string
     */
    protected function getCredentials()
    {
        return base64_encode($this->user.':'.$this->password);
    }
}
