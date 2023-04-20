<?php

namespace App\Http\Controllers\Pay;

use App\Exceptions\RuleValidationException;
use App\Http\Controllers\PayController;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class XorpayController extends PayController {

    public function gateway(string $payway, string $orderSN) {
        try {
            $config    = $this->getConfig($orderSN, $payway);
            $orderID   = $this->order->order_sn;
            $notifyUrl = url($this->payGateway->pay_handleroute . '/notify_url');
            $notifyUrl = env('APP_URL') . '/test' . $this->payGateway->pay_handleroute . '/notify_url';
            //$returnUrl = url('detail-order-sn', ['orderSN' => $this->order->order_sn]);
            $expire = dujiaoka_config_get('order_expire_time', 5) * 60;
            $data   = [
                'name'       => 'NO.' . $orderSN,
                'pay_type'   => 'alipay',
                'price'      => (float)$this->order->actual_price,
                'order_id'   => $orderID,
                'notify_url' => $notifyUrl, //回调路由
                'expire'     => $expire
            ];

            $data['sign'] = $this->sign([
                $data['name'],
                $data['pay_type'],
                $data['price'],
                $data['order_id'],
                $data['notify_url'],
                $config['app_secret']
            ]);

            try {
                $result = $this->request($config['api_url'], $data);

                if (($result['status'] ?? '') != 'ok') {
                    throw new Exception('支付网关异常，请稍后再试!');
                }

                $result['qr_code']      = $result['info']['qr'];
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
    public function notifyUrl(Request $request): string {

        try {
            $resp   = $request->post();
            $config = $this->getConfig($resp['order_id']);
            $sign   = $this->sign([
                $resp['aoid'],
                $resp['order_id'],
                $resp['pay_price'],
                $resp['pay_time'],
                $config['app_secret']
            ]);
            if ($sign == $resp['sign']) {
                $this->orderProcessService->completedOrder($resp['order_id'], $resp['pay_price'], $resp['aoid']);

                return 'ok';
            }
            throw new Exception("签名错误:" . $sign . ' != ' . $resp['sign']);

        } catch (Exception $exception) {
            throw new MethodNotAllowedHttpException(['post'], 'Method Not Allowed');
        }
    }

    /**
     * @throws \Exception
     */
    private function request(string $url, array $data): array {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
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

        return @json_decode($output, true) ?: [];
    }

    private function sign(array $data): string {
        return md5(join('', $data));
    }

    private function getConfig(string $orderSn, string $payWay = 'xorpay'): array {
        $this->loadGateWay($orderSn, $payWay);

        return [
            'app_id'     => $this->payGateway->merchant_id,
            'app_secret' => $this->payGateway->merchant_pem,
            'api_url'    => 'https://xorpay.com/api/pay/' . $this->payGateway->merchant_id
        ];
    }
}
