<?php

namespace hupijiao;

use Exception;

class Hupijiao {
    private $app_id;
    private $app_secret;

    //支付请求地址
    private $url;

    private $api_url;
    private $api_url_native;

    public function __construct($config = []) {
        $this->app_id         = isset($config['app_id']) && $config['app_id'] ? $config['app_id'] : Config::APP_ID;
        $this->app_secret     = isset($config['app_secret']) && $config['app_secret'] ? $config['app_secret'] : Config::APP_SECRET;
        $this->api_url        = isset($config['api_url']) && $config['api_url'] ? $config['api_url'] : Config::API_URL;
        $this->api_url_native = $this->api_url . '/do.html';
    }

    //请求支付
    public function request($type = 'wx_native', $data = []) {
        if (!$type || !$data)
            exit('Please pass in the correct request parameters!');
        $data = $this->formatData($data);
        switch ($type) {
            case 'wx_native':
                $this->url = $this->api_url_native;
                break;
            default:
                exit('Please pass in the correct request type!');
        }

        $response = $this->httpRequest($this->url, $data);
        $response = json_decode($response, true);

        return $response;
    }

    //整合请求数据并返回
    public function formatData($data = []) {
        if (!$data)
            exit('Please pass in the request data!');
        if (!isset($data['app_id']))
            $data['app_id'] = $this->app_id;
        $data['hash'] = $this->generateHash($data);

        return $data;
    }

    //生成hash
    public function generateHash($data) {
        if (array_key_exists('hash', $data)) {
            unset($data['hash']);
        }
        ksort($data);

        $buff = "";
        foreach ($data as $k => $v) {
            if ($k != "hash" && $v !== "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff   = trim($buff, "&");
        $string = $buff . $this->app_secret;

        return md5($string);
    }

    //验证返回参数

    /**
     * @throws \Exception
     */
    public function checkResponse($data): bool {
        if ($data['status'] != 'OD') {
            throw new Exception($data['status'], 500);
        }
        //校验签名
        $hish = $this->generateHash($data);
        if ($hish != $data['hash']) {
            throw new Exception('签名校验失败');
        }

        return true;
    }

    //http请求

    /**
     * @throws \Exception
     */
    public function httpRequest($url, $data = [], $headers = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output    = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error     = curl_error($ch);
        curl_close($ch);
        if ($http_code != 200) {
            throw new Exception($error);
        }
        if (!$output) {
            throw new Exception("Error:Request has no return content!");
        }

        return $output;
    }
}
