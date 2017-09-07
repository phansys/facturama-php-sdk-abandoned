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

    const INVOICE_TYPE_INCOME = 'ingreso';
    const INVOICE_TYPE_OUTCOME = 'egreso';
    const INVOICE_TYPE_DELIVERY_NOTE = 'traslado';

    const PAYMENT_METHOD_CASH = 'Efectivo';
    const PAYMENT_METHOD_NOMINAL_CHECK = 'Cheque';
    const PAYMENT_METHOD_TRANSFER = 'Transferencia';
    const PAYMENT_METHOD_CREDIT_CARD = 'Tarjetas de crédito';
    const PAYMENT_METHOD_DIGITAL_WALLET = 'Monederos electrónicos';
    const PAYMENT_METHOD_DIGITAL_MONEY = 'Dinero electrónico';
    const PAYMENT_METHOD_DIGITAL_CARD = 'Tarjetas digitales';
    const PAYMENT_METHOD_GROCERY_COUPON = 'Vales de despensa';
    const PAYMENT_METHOD_HOLDING = 'Bienes';
    const PAYMENT_METHOD_SERVICE = 'Servicio';
    const PAYMENT_METHOD_THIRD_PARTY = 'Por cuenta de tercero';
    const PAYMENT_METHOD_DATION = 'Dación en pago';
    const PAYMENT_METHOD_SUBROGATION = 'Pago por subrogación';
    const PAYMENT_METHOD_CONSIGNMENT = 'Pago por consignación';
    const PAYMENT_METHOD_CONDONATION = 'Condonación';
    const PAYMENT_METHOD_CANCELLATION = 'Cancelación';
    const PAYMENT_METHOD_COMPENSATION = 'Compensación';
    const PAYMENT_METHOD_DOESNT_APPLY = 'NA';
    const PAYMENT_METHOD_OTHER = 'Otros';

    const FILE_TYPE_PDF = 'pdf';
    const FILE_TYPE_HTML = 'html';
    const FILE_TYPE_XML = 'xml';

    const RECEIPT_PAYROLL = 'payroll';
    const RECEIPT_RECEIVED = 'received';
    const RECEIPT_ISSUED = 'issued';

    const TAX_TYPE_FEDERAL_RETAINED = 1;
    const TAX_TYPE_FEDERAL_TRANSFERRED = 2;
    const TAX_TYPE_LOCAL_RETAINED = 3;
    const TAX_TYPE_LOCAL_TRANSFERRED = 4;

    /**
     * Configuration for CURL
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
     * @var null
     */
    protected $user = null;
    /**
     * Password
     *
     * @var null
     */
    protected $password = null;

    /**
     * Init configuration
     *
     * @param string $user     username
     * @param string $password password
     * @param mixed $curl_opts curl options
     */
    public function __construct($user = null, $password = null, $curl_opts = null)
    {
        $this->user = $user ? $user : config('facturama.credentials.username');
        $this->password = $password ? $password : config('facturama.credentials.password');

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
    public function get($path, $params = null)
    {
        $opts = [
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Basic '.$this->getCredentials(),
            ],
        ];
        $exec = $this->execute($path, null, $params);

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
    public function post($path, $body = null, $params = [])
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
     * @param  mixed $body
     * @param  array $params
     *
     * @return mixed
     */
    public function put($path, $body = null, $params = null)
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
     * @param  mixed $params
     *
     * @return mixed
     */
    public function delete($path, $params = null)
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
    public function execute($path, $opts = [], $params = [])
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
    public function make_path($path, $params = [])
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
