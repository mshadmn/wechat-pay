<?php
/**
 * @author: Jackong
 * Date: 15/7/31
 * Time: 下午5:36
 */

namespace wechat;


use Unirest\Request;

class Payment {
    const SUCCESS = 'SUCCESS';
    const FAIL = 'FAIL';

    private $options = [];

    public function __construct($options) {
        $this->options = $options;
    }

    public function sign($params) {
        unset($params['sign']);
        ksort($params);
        reset($params);
        $qs = http_build_query($params);
        $qs .= "&key={$this->options['key']}";
        $qs = urldecode($qs);
        return strtoupper(md5($qs));
    }

    public function xmlEncode($params) {
        $doc = new \DOMDocument('1.0', 'utf-8');
        $xml = $doc->createElement('xml');
        foreach ($params as $key => $value) {
            $elem = $doc->createElement($key);
            $elem->appendChild($doc->createCDATASection($value));
            $xml->appendChild($elem);
        }
        return $doc->saveXML($xml);
    }

    public function xmlDecode($xml) {
        if (is_null($xml)) {
            return null;
        }
        $data = simplexml_load_string($xml);
        $obj = [];
        foreach ($data->children() as $key => $node) {
            $obj[$key] = (string) $node;
        }
        return $obj;
    }

    public function getNonce() {
        return '' . rand(12345, time() + 12345);
    }

    public function getOptions() {
        return $this->options;
    }

    public function request($path, $params, $headers = []) {
        $params['appid'] = $this->options['appid'];
        $params['mch_id'] = $this->options['mch_id'];
        $params['nonce_str'] = $this->getNonce();
        $params['sign'] = $this->sign($params);
        $xml = $this->xmlEncode($params);
        /**
         * @var $res \Unirest\Response
         */
        $res = Request::post(sprintf('%s%s', $this->options['host'], $path), $headers, $xml);
        if ($res->code !== 200) {
            return null;
        }
        $body = $this->xmlDecode($res->body);
        if (!is_array($body)) {
            return null;
        }
        if ($body['return_code'] !== static::SUCCESS || $body['result_code'] !== static::SUCCESS) {
            return null;
        }
        if ($body['appid'] !== $this->options['appid'] || $body['mch_id'] !== $this->options['mch_id']) {
            return null;
        }
        $sign = $body['sign'];
        if ($sign !== $this->sign($body)) {
            return null;
        }
        return $body;
    }

    public function prepay($params) {
        return $this->request('/pay/unifiedorder', $params);
    }

    public function queryOrder($params) {
        return $this->request('/pay/orderquery', $params);
    }

    public function closeOrder($params) {
        return $this->request('/pay/closeorder', $params);
    }

    public function queryRefund($params) {
        return $this->request('/pay/refundquery', $params);
    }

    public function downloadBill($params) {
        return $this->request('/pay/downloadbill', $params);
    }

    public function refund($params) {
        return $this->request('/secapi/pay/refund', $params);
    }

    public function response($code, $msg) {
        return $this->xmlEncode([
            'return_code' => $code,
            'return_msg' => $msg
        ]);
    }
}