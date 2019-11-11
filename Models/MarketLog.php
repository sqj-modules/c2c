<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019/10/24
 * Time: 5:03 下午
 */
namespace SQJ\Modules\C2C\Models;

use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Support\Arr;

class MarketLog extends C2C
{
    /**
     * 挂卖
     */
    const TYPE_SELL = 1;
    /**
     * 挂买
     */
    const TYPE_BUY = 2;
    /**
     * 付款
     */
    const TYPE_PAY = 3;
    /**
     * 确认
     */
    const TYPE_CONFIRM = 4;
    /**
     * 申诉未付款
     */
    const TYPE_APPEAL_PAY = -3;
    /**
     * 申诉未确认
     */
    const TYPE_APPEAL_CONFIRM = -4;
    /**
     * 驳回未付款申诉
     */
    const TYPE_REJECT_APPEAL_PAY = 30;
    /**
     * 通过未付款申诉
     */
    const TYPE_ACCEPT_APPEAL_PAY = 31;
    /**
     * 驳回未确认申诉
     */
    const TYPE_REJECT_APPEAL_CONFIRM = 40;
    /**
     * 通过未确认申诉
     */
    const TYPE_ACCEPT_APPEAL_CONFIRM = 41;
    /**
     * 回流市场
     */
    const TYPE_ROLLBACK = -100;

    /**
     * 添加市场记录
     *
     * @param Market $market
     * @param User|AdminUser $operator
     * @param $type
     * @return bool
     */
    public static function insert(Market $market, $operator, $type)
    {
        // 创建记录
        $record = new MarketLog();

        // 市场ID
        $record['marketId'] = $market['id'];
        // 操作类型
        $record['operableType'] = get_class($operator);
        // 操作ID
        $record['operableId'] = $operator['id'];
        // 操作类型
        $record['type'] = $type;

        return $record->save();
    }

    public static function pageList($lastId, $page, $condition)
    {
        // 创建查询构造器
        $builder = self::query()
            ->with(['market' => function($query) {
                $query->select(['id', 'order_no']);
            }, 'operable' => function($query) {
                $query->select(['id', 'nickname']);
            }])
            ->select(['id', 'market_id', 'operable_type', 'operable_id', 'type', 'created_at']);

        // 挂单ID
        if (isset($condition['marketId']) &&  $condition['marketId'] !== '')
        {
            $builder->where('market_id', intval($condition['marketId']));
        }

        // 查询数据
        $data = self::paginate($builder, $lastId, $page);

        foreach ($data['list'] as &$item)
        {
            // 挂单号
            $item['marketNo'] = $item['market']['orderNo'];
            // 类型名称
            $item['typeName'] = self::typeName($item['type']);
            // 操作类型
            $operable = $item['operableType'] == AdminUser::class ? '后台管理员' : '用户';
            // 操作员
            $item['operator'] = "{$item['operable']['nickname']}({$operable})";

            // 保留需要的字段
            $item = Arr::only($item, [
                'id', 'typeName', 'operator', 'createdAt'
            ]);
        }

        return $data;
    }

    /**
     * 类型名称
     *
     * @param integer $type 类型
     * @return string
     */
    private static function typeName($type)
    {
        switch ($type)
        {
            case self::TYPE_SELL:
                return '挂卖';
            case self::TYPE_BUY:
                return '挂买';
            case self::TYPE_PAY:
                return '付款';
            case self::TYPE_CONFIRM:
                return '确认';
            case self::TYPE_APPEAL_PAY:
                return '申诉未付款';
            case self::TYPE_APPEAL_CONFIRM:
                return '申诉未确认';
            case self::TYPE_REJECT_APPEAL_PAY:
                return '驳回未付款申诉';
            case self::TYPE_ACCEPT_APPEAL_PAY:
                return '通过未付款申诉';
            case self::TYPE_REJECT_APPEAL_CONFIRM:
                return '驳回未确认申诉';
            case self::TYPE_ACCEPT_APPEAL_CONFIRM:
                return '通过未确认申诉';
            case self::TYPE_ROLLBACK:
                return '挂单流回市场';
            default:
                return '';
        }
    }

    /**
     * 关联挂单
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function market()
    {
        return $this->belongsTo(Market::class, 'market_id');
    }

    /**
     * 关联操作者
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function operable()
    {
        return $this->morphTo();
    }
}
