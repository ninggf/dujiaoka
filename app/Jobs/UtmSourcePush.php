<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OrderUtm;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class UtmSourcePush implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 任务最大尝试次数。
     *
     * @var int
     */
    public $tries = 1;

    /**
     * 任务运行的超时时间。
     *
     * @var int
     */
    public $timeout = 30;

    /**
     * @var Order
     */
    private $order;
    private $status;
    private $tryIdx;
    private $triesRemain = [
        0  => 0,
        1  => 5,
        2  => 10,
        3  => 15,
        4  => 30,
        5  => 60,
        6  => 300,
        7  => 600,
        8  => 1800,
        9  => 3600,
        10 => 7200,
        11 => 5 * 3600,
        12 => 12 * 3600,
        13 => 24 * 3600,
        14 => 48 * 3600
    ];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Order $order, int $tryIdx = 0, int $status = 0) {
        $this->order  = $order;
        $this->tryIdx = $tryIdx;
        $this->status = $status;
        $this->delay($this->triesRemain[ $tryIdx ] ?? 0);
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle() {
        $id  = $this->order->id;
        $utm = OrderUtm::query()->where('order_id', $id)->first();

        if ($utm->exists && $utm->value('notify_url')) {
            $client = new Client();
            $apiUrl = $utm->notify_url;
            $params = [
                'order_sn'  => $this->order->getAttributeValue('order_sn'),
                'status'    => $this->status,
                'source'    => $utm->utm_source,
                'medium'    => $utm->utm_medium,
                'phone'     => $utm->phone,
                'createdAt' => $this->order->getAttributeValue('created_at'),
                'goods'     => [
                    'name'     => $this->order->getAttributeValue('title'),
                    'quantity' => $this->order->getAttributeValue('buy_amount')
                ]
            ];
            try {
                $response = $client->post($apiUrl, ['json' => $params, 'verify' => false]);
                if ($response->getStatusCode() != 200) {
                    throw new Exception($apiUrl . '响应码不为200: ' . $response->getStatusCode());
                }
            } catch (InvalidArgumentException $e) {
                Log::error("订单{$this->tryIdx}发送通知失败, SN: {$this->order->order_sn}, 通知地址:$apiUrl,具体原因: \n{$e->getMessage()}");
            } catch (Exception $e) {
                Log::error("订单第{$this->tryIdx}次发送通知失败, SN: {$this->order->order_sn}, 通知地址:$apiUrl,具体原因: \n{$e->getMessage()}");
                UtmSourcePush::dispatch($this->order, $this->tryIdx + 1, $this->status);
            }
        }
    }
}
