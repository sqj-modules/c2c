<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019/10/29
 * Time: 9:37 上午
 */
namespace SQJ\Modules\C2C\Http\Clients\Admin\v1;

use App\Http\Controllers\Api\ApiModule;
use App\Models\SystemConfig;
use App\Models\UserCredit;
use SQJ\Modules\C2C\Support\Settings;
use Illuminate\Support\Arr;

class SettingsModule extends ApiModule
{
    protected $name = '参数设置';

    /**
     * 接口编码列表
     *
     * @return mixed
     */
    protected function interfaceList()
    {
        return [
            // 获取基本设置
            '1000' => [
                'method' => 'getBasic',
                'permission' => 'c2c.settings.basic'
            ],
            // 设置基本设置
            '1001' => [
                'method' => 'setBasic',
                'permission' => 'c2c.settings.basic',
                'label' => '基本设置'
            ],
            // 获取市场参数
            '2000' => [
                'method' => 'getMarket',
                'permission' => 'c2c.settings.market'
            ],
            // 设置市场参数
            '2001' => [
                'method' => 'setMarket',
                'permission' => 'c2c.settings.market',
                'label' => '市场参数'
            ]
        ];
    }

    /**
     * 权限
     *
     * @return array|bool
     */
    public function permissions()
    {
        return [
            'items' => [
                '1001', '2001'
            ]
        ];
    }

    /**
     * 获取基本设置
     *
     * @return array
     */
    protected function getBasic()
    {
        $params = Settings::get(Settings::BASIC);

        return $params ?: [];
    }

    /**
     * 保存基本设置
     *
     * @param $callback
     * @return string
     * @throws \App\Exceptions\DeveloperException
     */
    protected function setBasic($callback)
    {
        // 验证数据
        $data = $this->validate([
            'paymentLimit' => 'required|numeric|min:0',
            'confirmLimit' => 'required|numeric|min:0'
        ]);

        // 保存
        Settings::set(Settings::BASIC, $data);

        $callback('修改【C2C】基本设置');

        return ___('修改成功');
    }

    /**
     * 获取市场参数
     *
     * @return array
     */
    protected function getMarket()
    {
        // 获取参数
        $params = Settings::get(Settings::MARKET);

        // 启用的钱包
        $credits = config('c2c.enabled_credits');

        // 钱包列表
        $creditList = [];

        foreach ($credits as $credit)
        {
            $creditList[] = [
                'label' => UserCredit::creditName($credit),
                'value' => $credit
            ];
        }

        return [
            'creditList' => $creditList,
            'params' => $params ?: new \stdClass()
        ];
    }

    /**
     * 设置挂卖参数
     *
     * @return string
     * @throws \App\Exceptions\DeveloperException
     */
    protected function setMarket()
    {
        // 获取参数
        $params = $this->requiredParam('params');

        // 所有配置
        $configs = [];

        foreach ($params as $key=>$param)
        {
            // 是否启用
            $isEnabled = $this->requiredParam('isEnabled', $param);

            // 挂卖周期
            $period = $this->optionalParam('period', [], $param);
            // 挂卖周期进行排序
            $period = array_values(Arr::sort($period));

            // 时间范围
            $timeRange = $this->optionalParam('timeRange', [], $param);

            // 验证是否为数组
            if (!is_array($timeRange))
            {
                throw_developer('字段【timeRange】必须是数组');
            }

            // 验证长度
            if (!empty($timeRange) && count($timeRange) != 2)
            {
                throw_developer('字段【timeRange】必须有开始时间和结束时间');
            }

            // 每日限制
            $dailyLimit = $this->optionalParam('dailyLimit', 0, $param);

            // 挂卖基数
            $baseNum = $this->optionalParam('baseNum', 0, $param);

            // 最低金额
            $minNum = $this->optionalParam('minNum', 0, $param);

            // 最高金额
            $maxNum = $this->optionalParam('maxNum', 0, $param);

            // 手续费比例
            $feeRate = $this->optionalParam('feeRate', 0, $param);

            // 挂卖声明
            $statement = $this->optionalParam('statement', '', $param);

            $configs[$key] =  [
                'isEnabled' => $isEnabled,
                'period' => $period,
                'timeRange' => $timeRange,
                'dailyLimit' => intval($dailyLimit),
                'baseNum' => intval($baseNum),
                'minNum' => floatval($minNum),
                'maxNum' => floatval($maxNum),
                'feeRate' => floatval($feeRate),
                'statement' => $statement
            ];
        }

        Settings::set(Settings::MARKET, $configs);

        return ___('设置成功');
    }
}
