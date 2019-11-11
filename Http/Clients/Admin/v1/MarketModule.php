<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019/10/26
 * Time: 2:59 下午
 */
namespace SQJ\Modules\C2C\Http\Clients\Admin\v1;

use App\Http\Controllers\Api\ApiModule;
use App\Models\UserCredit;
use SQJ\Modules\C2C\Models\Market;
use SQJ\Modules\C2C\Models\MarketLog;

class MarketModule extends ApiModule
{
    protected $name = '挂单管理';

    /**
     * 接口编码列表
     *
     * @return mixed
     */
    protected function interfaceList()
    {
        return [
            // 挂单参数
            '1000' => 'params',
            // 挂单列表
            '1001' => [
                'method' => 'getList',
                'permission' => 'c2c.market.list',
                'label' => '列表'
            ],
            // 付款
            '1002' => [
                'method' => 'pay',
                'permission' => 'c2c.market.pay',
                'label' => '付款'
            ],
            // 确认
            '1003' => [
                'method' => 'confirm',
                'permission' => 'c2c.market.confirm',
                'label' => '确认'
            ],
            // 流回市场
            '1004' => [
                'method' => 'rollback',
                'permission' => 'c2c.market.rollback',
                'label' => '流回市场'
            ],
            // 挂单记录
            '2000' => [
                'method' => 'logList',
                'permission' => 'c2c.market.log',
                'label' => '记录'
            ]
        ];
    }

    public function permissions()
    {
        return [];
    }

    /**
     * 启用的钱包
     *
     * @return array
     */
    protected function params()
    {
        // 启用的钱包列表
        $enabledCredits = config('c2c.enabled_credits');

        // 钱包列表
        $creditList = [];

        foreach ($enabledCredits as $credit)
        {
            $creditList[] = [
                'label' => UserCredit::creditName($credit),
                'value' => $credit
            ];
        }

        return [
            'creditList' => $creditList,
            'statusList' => Market::statusDictionary()
        ];
    }

    /**
     * 挂单列表
     *
     * @return mixed
     * @throws \App\Exceptions\DeveloperException
     */
    protected function getList()
    {
        return $this->pageList(Market::class);
    }

    /**
     * 付款
     *
     * @param $callback
     * @return array
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     */
    protected function pay($callback)
    {
        // 验证数据
        $data = $this->validate([
            'id' => 'required|numeric'
        ]);

        // 获取挂单
        $market = Market::getById($data['id']);

        // 挂单付款
        $market->pay('');

        // 添加记录
        $callback("后台操作挂单{$market['orderNo']}为付款状态");

        return [
            'status' => [
                'label' => Market::statusName($market['status']),
                'style' => Market::statusStyle($market['status']),
                'value' => $market['status']
            ],
            'paidAt' => $market['paidAt']
        ];
    }

    /**
     * 挂单确认
     *
     * @param $callback
     * @return array
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     */
    protected function confirm($callback)
    {
        // 验证数据
        $data = $this->validate([
            'id' => 'required|numeric'
        ]);

        // 获取挂单
        $market = Market::getById($data['id']);

        // 挂单确认
        $market->confirm();

        // 添加记录
        $callback("后台操作挂单{$market['orderNo']}为确认状态");

        return [
            'status' => [
                'label' => Market::statusName($market['status']),
                'style' => Market::statusStyle($market['status']),
                'value' => $market['status']
            ],
            'confirmedAt' => $market['confirmedAt']
        ];
    }

    /**
     * 挂单流回市场
     *
     * @param $callback
     * @return array
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     */
    protected function rollback($callback)
    {
        // 验证数据
        $data = $this->validate([
            'id' => 'required|numeric'
        ]);

        // 获取挂单
        $market = Market::getById($data['id']);

        // 挂单流回
        $market->rollback();

        // 添加记录
        $callback("后台操作挂单{$market['orderNo']}流回市场");

        return [
            'status' => [
                'label' => Market::statusName($market['status']),
                'style' => Market::statusStyle($market['status']),
                'value' => $market['status']
            ],
            'buyerId' => 0,
            'buyer' => null,
            'boughtAt' => null
        ];
    }

    /**
     * 挂单记录
     *
     * @return mixed
     * @throws \App\Exceptions\DeveloperException
     */
    protected function logList()
    {
        return $this->pageList(MarketLog::class);
    }
}
