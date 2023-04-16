<?php

namespace App\Http\Controllers\Pay;
require_once __DIR__ . "/../../../../extends/hupijiao/Hupijiao.php";

use App\Exceptions\RuleValidationException;
use App\Http\Controllers\PayController;
use Exception;
use hupijiao\Hupijiao;
use Illuminate\Http\Request;

class XunhupayController extends PayController {

    public function gateway(string $payway, string $orderSN) {
        try {
            $config      = $this->getConfig($orderSN, $payway);
            $HupijiaoObj = new Hupijiao($config);

            $orderID   = $this->order->order_sn;
            $notifyUrl = url($this->payGateway->pay_handleroute . '/notify_url');
            $returnUrl = url('detail-order-sn', ['orderSN' => $this->order->order_sn]);
            $data      = [
                'version'        => '1.1',
                'appid'          => $config['app_id'],
                'trade_order_id' => $orderID,  //订单编号
                'payment'        => 'wechat',
                'total_fee'      => (float)$this->order->actual_price,
                'title'          => 'NO.' . $orderID,
                'time'           => time(),
                'notify_url'     => $notifyUrl,  //异步回调地址
                'return_url'     => $returnUrl,  //支付成功跳转地址，可携带参数
                'nonce_str'      => md5(time())
            ];

            try {
                $result = $HupijiaoObj->request('wx_native', $data);
                if ($result['errcode']) {
                    throw new Exception($result['errmsg']);
                }
                $result['qr_code_img']  = $result['url_qrcode'];
                $result['payname']      = $this->payGateway->pay_name;
                $result['actual_price'] = (float)$this->order->actual_price;
                $result['orderid']      = $this->order->order_sn;

                return $this->render('static_pages/qrpay', $result, __('dujiaoka.scan_qrcode_to_pay'));
            } catch (Exception $e) {
                throw new RuleValidationException(__('dujiaoka.prompt.abnormal_payment_channel') . $e->getMessage());
            }
        } catch (RuleValidationException $exception) {
            return $this->err($exception->getMessage());
        }
    }

    /**
     * 异步通知
     */
    public function notifyUrl(Request $request) {

        try {
            $resp   = $request->post();
            $config = $this->getConfig($resp['trade_order_id']);

            $HupijiaoObj = new Hupijiao($config);
            $HupijiaoObj->checkResponse($resp);

            $this->orderProcessService->completedOrder($resp['trade_order_id'], $resp['total_fee'], $resp['transaction_id']);

            return 'success';
        } catch (Exception $exception) {
            if ($exception->getCode() != 0) {
                return $exception->getMessage();
            }

            return 'fail';
        }
    }

    private function getConfig(string $orderSn, string $payWay = 'xunhupay'): array {
        $this->loadGateWay($orderSn, $payWay);

        return [
            'app_id'     => $this->payGateway->merchant_id,
            'app_secret' => $this->payGateway->merchant_pem,
            'api_url'    => 'https://api.xunhupay.com/payment'
        ];
    }
}
