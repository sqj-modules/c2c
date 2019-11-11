<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019/10/28
 * Time: 1:47 下午
 */
namespace SQJ\Modules\C2C\Http\Clients\Admin\v1;

use App\Http\Controllers\Api\ApiModule;
use SQJ\Modules\C2C\Models\MarketAppeal;

class AppealModule extends ApiModule
{
    protected $name = '申诉管理';

    /**
     * 接口编码列表
     *
     * @return mixed
     */
    protected function interfaceList()
    {
        return [
            // 申诉列表
            '1000' => [
                'method' => 'getList',
                'permission' => 'c2c.appeal.list',
                'label' => '列表'
            ],
            // 通过申诉
            '1001' => [
                'method' => 'accept',
                'permission' => 'c2c.appeal.accept',
                'label' => '通过'
            ],
            // 驳回申诉
            '1002' => [
                'method' => 'reject',
                'permission' => 'c2c.appeal.reject',
                'label' => '驳回'
            ]
        ];
    }

    // 所允许的权限
    public function permissions()
    {
        return [];
    }

    /**
     * 申诉列表
     *
     * @return mixed
     * @throws \App\Exceptions\DeveloperException
     */
    protected function getList()
    {
        return $this->pageList(MarketAppeal::class);
    }

    /**
     * 通过申诉
     *
     * @param $callback
     * @return string
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     */
    protected function accept($callback)
    {
        // 验证数据
        $data = $this->validate([
            'id' => 'required|numeric'
        ]);

        // 获取申诉
        $appeal = MarketAppeal::getById($data['id']);

        // 通过申诉
        $appeal->accept();

        $callback("通过申诉【{$appeal['id']}】");

        return ___('通过申诉');
    }

    /**
     * 驳回申诉
     *
     * @param $callback
     * @return string
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     */
    protected function reject($callback)
    {
        // 验证数据
        $data = $this->validate([
            'id' => 'required|numeric',
            'reason' => 'required|string|min:2|max:200'
        ]);

        // 获取申诉
        $appeal = MarketAppeal::getById($data['id']);

        // 驳回申诉
        $appeal->reject($data['reason']);


        $callback("驳回申诉【{$appeal['id']}】");

        return ___('驳回申诉');
    }
}
