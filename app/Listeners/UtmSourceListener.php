<?php

namespace App\Listeners;

use App\Jobs\UtmSourcePush;
use App\Models\OrderUtm;
use Exception;

class UtmSourceListener {
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct() {
        //
    }

    public function saved($order) {
        $utm = session('utm_source');
        if ($utm && ($utm['source'] ?? null)) {
            try {
                $order_utm = new OrderUtm();

                $order_utm->order_id   = $order->id;
                $order_utm->order_sn   = $order->order_sn;
                $order_utm->utm_source = $utm['source'] ?? '';
                $order_utm->utm_medium = $utm['medium'] ?? '';
                $order_utm->phone      = $utm['phone'] ?? '';
                $order_utm->notify_url = $utm['notify'] ?? '';
                $order_utm->save();

                //更新订单来源数据
                if ($utm['notify']) {
                    UtmSourcePush::dispatch($order);
                }
            } catch (Exception $e) {
            }
        }
    }
}
