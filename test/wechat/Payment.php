<?php
/**
 * @author: Jackong
 * Date: 15/7/31
 * Time: 下午5:38
 */

date_default_timezone_set('Asia/Chongqing');

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/wechat/Payment.php';
describe('wxmp', function () {
    $options = require('options.php');
    $this->options = $options['wxmp'];
    $this->payment = new \wechat\Payment($this->options);
    $this->order = [
        'id' => '1217752501201407033233368017'
    ];
    $this->params = [
        'param' => 'value'
    ];
    \pho\context('sign', function () {

        it('should be equal that from wechat', function () {

            \pho\expect($this->payment->sign($this->params))
                ->toBe('FCA46065F6C42945257E21854751AD9C');
        });
    });

    \pho\context('xmlEncode', function () {
        it('should be equal that from wechat', function () {
            $xml = '<xml><param><![CDATA[value]]></param><sign><![CDATA[FCA46065F6C42945257E21854751AD9C]]></sign></xml>';
            \pho\expect($this->payment->xmlEncode(array_merge($this->params, ['sign' => $this->payment->sign($this->params)])))
                ->toBe($xml);
        });
    });

    context('xmlDecode', function () {
        it('should be right', function () {
            $xml = '<xml>
   <return_code><![CDATA[SUCCESS]]></return_code>
   <return_msg><![CDATA[OK]]></return_msg>
   <appid><![CDATA[wx2421b1c4370ec43b]]></appid>
   <mch_id><![CDATA[10000100]]></mch_id>
   <nonce_str><![CDATA[IITRi8Iabbblz1Jc]]></nonce_str>
   <sign><![CDATA[7921E432F65EB8ED0CE9755F0E86D72F]]></sign>
   <result_code><![CDATA[SUCCESS]]></result_code>
   <prepay_id><![CDATA[wx201411101639507cbf6ffd8b0779950874]]></prepay_id>
   <trade_type><![CDATA[JSAPI]]></trade_type>
</xml>';
            $decoded = $this->payment->xmlDecode($xml);
            \pho\expect($decoded)->toHaveLength(9)->toHaveKey('return_code');
            \pho\expect($decoded['return_code'])->toEqual('SUCCESS');
        });
    });

    \pho\context('prepay', function () {
        \pho\it('should response success', function () {
            $params = [
                'device_info' => 'WEB',
                'body' => 'Ipad mini  16G  白色',
                'detail' => 'Ipad mini  16G  白色',
                'attach' => '说明',
                'out_trade_no' => $this->order['id'],
                'total_fee' => 888,
                'spbill_create_ip' => '8.8.8.8',
                'time_start' => date('YmdHis'),
                'time_expire' => date('YmdHis', strtotime('+10 minutes')),
                'notify_url' => 'http://wxmp.facelike.com/api/wechat/payments/callback',
                'trade_type' => 'JSAPI',
                'openid' => 'obBtPs3uivk1vmFtEF1DYUqoIGPo'
            ];
            $res = $this->payment->prepay($params);
            \pho\expect($res)->toHaveKey('prepay_id');
        });
    });
});

describe('app', function () {
    $options = require('options.php');
    $this->options = $options['app'];
    $this->payment = new \wechat\Payment($this->options);
    $this->order = [
        'id' => '1217752501201407033233368017'
    ];
    $this->params = [
        'param' => 'value'
    ];
    \pho\context('prepay', function () {
        \pho\it('should response success', function () {
            $params = [
                'device_info' => '013467007045764',
                'body' => 'Ipad mini  16G  白色',
                'detail' => 'Ipad mini  16G  白色',
                'attach' => '说明',
                'out_trade_no' => $this->order['id'],
                'total_fee' => 888,
                'spbill_create_ip' => '8.8.8.8',
                'time_start' => date('YmdHis'),
                'time_expire' => date('YmdHis', strtotime('+10 minutes')),
                'notify_url' => 'http://wxmp.facelike.com/api/wechat/payments/callback',
                'trade_type' => 'APP'
            ];
            $res = $this->payment->prepay($params);
            \pho\expect($res)->toHaveKey('prepay_id');
        });
    });

    \pho\context('query order', function () {
        \pho\it('should response NOTPAY status', function () {
            $params = [
                'out_trade_no' => $this->order['id']
            ];

            $res = $this->payment->queryOrder($params);
            \pho\expect($res['trade_state'])->toEql('NOTPAY');
        });
    });

    \pho\context('close order', function () {
        \pho\it('should response error if order not exist', function () {
            $params = [
                'out_trade_no' => $this->order['id'] . 'not-exist'
            ];
            $res = $this->payment->closeOrder($params);
            \pho\expect($res['result_code'])->toBe(\wechat\Payment::FAIL);
            \pho\expect($res['err_code'])->toBe('SYSTEMERROR');
        });

        \pho\it('should response success if order closed', function () {
            $params = [
                'out_trade_no' => '1217752501201407033233368018'
            ];
            $res = $this->payment->closeOrder($params);
            \pho\expect($res['result_code'])->toBe(\wechat\Payment::SUCCESS);
        });
    });

    \pho\context('query refund', function () {
        \pho\it('should response error if refund not exist', function () {
            $params = [
                'out_trade_no' => $this->order['id']
            ];
            $res = $this->payment->queryRefund($params);
            \pho\expect($res['result_code'])->toBe(\wechat\Payment::FAIL);
            \pho\expect($res['err_code'])->toBe('REFUNDNOTEXIST');
        });
    });
});