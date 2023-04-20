<?php
/*
 * This file is part of dujiaoka.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Admin\Repositories;

use App\Models\Order as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class OrderUtm extends EloquentRepository {
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
