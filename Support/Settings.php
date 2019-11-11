<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019/10/29
 * Time: 9:40 上午
 */
namespace SQJ\Modules\C2C\Support;

use App\Models\SystemConfig;

class Settings
{
    /**
     * 基本设置
     */
    const BASIC = 'basic';
    /**
     * 市场参数
     */
    const MARKET = 'market';

    /**
     * 获取设置参数
     *
     * @param $name
     * @return mixed
     */
    public static function get($name)
    {
        return SystemConfig::get("c2c_{$name}");
    }

    /**
     * 设置参数
     *
     * @param $name
     * @param $params
     */
    public static function set($name, $params)
    {
        SystemConfig::set("c2c_{$name}", $params);
    }
}
