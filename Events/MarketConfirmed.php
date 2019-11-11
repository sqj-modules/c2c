<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019/10/25
 * Time: 9:25 下午
 */
namespace SQJ\Modules\C2C\Events;

use SQJ\Modules\C2C\Models\Market;
use Illuminate\Queue\SerializesModels;

class MarketConfirmed
{
    use SerializesModels;

    /**
     * @var Market 挂单
     */
    private $market;

    public function __construct(Market $market)
    {
        $this->market = $market;
    }

    /**
     * 获取付款的挂单
     *
     * @return Market
     */
    public function market()
    {
        return $this->market;
    }
}
