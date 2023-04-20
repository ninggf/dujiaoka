<?php
/*
 * This file is part of dujiaoka.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * OrderUtm
 * @package App\Models
 * @author  Leo Ning <windywany@gmail.com>
 * @date    2023-04-20 13:22:15
 * @since   1.0.0
 * @property int    $order_id
 * @property string $order_sn
 * @property string $utm_source
 * @property string $utm_medium
 * @property string $phone
 * @property string $notify_url
 */
class OrderUtm extends Model {
    use SoftDeletes;

    protected $table        = 'orders_utm';
    protected $primaryKey   = 'order_id';
    public    $incrementing = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @author Leo Ning <windywany@gmail.com>
     * @date   2023-04-20 15:16:07
     * @since  1.0.0
     */
    protected function order(): BelongsTo {
        return $this->belongsTo(Order::class, 'id', 'order_id');
    }
}
