<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019/10/26
 * Time: 9:00 上午
 */
namespace SQJ\Modules\C2C\Models;

use App\Models\AdminUser;
use App\Models\User;
use function Clue\StreamFilter\fun;

class MarketAppeal extends C2C
{
    // 类型转换
    protected $casts = [
        'images' => 'array'
    ];

    /**
     * 买家未付款
     */
    const TYPE_NOT_PAY = 1;
    /**
     * 卖家未确认
     */
    const TYPE_NOT_CONFIRM = 2;

    /**
     * 申请中
     */
    const STATUS_APPLYING = 0;
    /**
     * 已通过
     */
    const STATUS_ACCEPTED = 1;
    /**
     * 已驳回
     */
    const STATUS_REJECTED = -1;

    /**
     * 添加申诉
     *
     * @param Market $market 申诉的挂单
     * @param string $content 申诉内容
     * @param array $images 申诉图片
     * @return bool
     */
    public static function insert(Market $market, $content, $images)
    {
        // 添加记录
        $record = new MarketAppeal();

        // 挂单ID
        $record['marketId'] = $market['id'];

        // 申诉人和申诉类型
        if ($market['status'] == Market::STATUS_APPEAL_PAYING)
        {
            $record['userId'] = $market['sellerId'];
            $record['type'] = self::TYPE_NOT_PAY;
        }
        else
        {
            $record['userId'] = $market['buyerId'];
            $record['type'] = self::TYPE_NOT_CONFIRM;
        }

        // 申诉状态
        $record['status'] = self::STATUS_APPLYING;

        // 申诉内容
        $record['content'] = $content;
        // 申诉凭证
        $record['images'] = $images;

        return $record->save();
    }

    /**
     * 分页获取数据
     *
     * @param $lastId
     * @param $page
     * @param $condition
     * @return array
     */
    public static function pageList($lastId, $page, $condition)
    {
        // 创建查询构造器
        $builder = self::query()
            ->with(['market' => function($query) {
                $query->select(['id', 'order_no']);
            }, 'user' => function($query) {
                $query->select(['id', User::accountType() . ' as account', 'nickname', 'avatar']);
            }, 'adminUser' => function($query) {
                $query->select(['id', 'username', 'nickname']);
            }]);

        // 挂单账号
        if (isset($condition['marketNo']) && $condition['marketNo'] !== '')
        {
            $builder->whereHas('market', function ($query) use ($condition) {
                $query->where('order_no', $condition['marketNo']);
            });
        }

        // 申请状态
        if (isset($condition['status']) && $condition['status'] !== '')
        {
            $builder->where('status', intval($condition['status']));
        }

        // 用户账号
        if (isset($condition['user']) && $condition['user'] !== '')
        {
            $builder->whereHas('user', function ($query) use ($condition) {
                $query->where('id', intval($condition['user']))
                    ->orWhere(User::accountType(), 'like', "%{$condition['user']}%")
                    ->orWhere('nickname', 'like', "%{$condition['user']}%");
            });
        }

        // 时间范围
        if (isset($condition['timeRange']) && !empty($condition['timeRange']))
        {
            $builder->whereBetween(self::CREATED_AT, $condition['timeRange']);
        }

        return self::paginate($builder, $lastId, $page);
    }

    /**
     * 通过申诉
     *
     * @throws \App\Exceptions\DeveloperException
     */
    public function accept()
    {
        // 验证状态
        if ($this['status'] != self::STATUS_APPLYING)
        {
            throw_developer(___('仅申诉中的可执行通过'));
        }

        // 修改状态
        $this['status'] = self::STATUS_ACCEPTED;
        // 通过时间
        $this['acceptedAt'] = now_datetime();
        // 操作员
        $this['adminId'] = self::adminId();

        // 保存
        $this->save();

        // 申诉未付款
        if ($this['type'] == self::TYPE_NOT_PAY)
        {
            // 挂单撤销付款
            $this['market']->resetPayment();

            // 添加记录
            MarketLog::insert($this['market'], self::admin(), MarketLog::TYPE_ACCEPT_APPEAL_PAY);
        }

        // 申诉未确认
        if ($this['type'] == self::TYPE_NOT_CONFIRM)
        {
            // 确认挂单
            $this['market']->confirm();

            // 添加记录
            MarketLog::insert($this['market'], self::admin(), MarketLog::TYPE_ACCEPT_APPEAL_CONFIRM);
        }
    }

    /**
     * 驳回申诉
     *
     * @param $reason
     * @throws \App\Exceptions\DeveloperException
     */
    public function reject($reason)
    {
        // 验证状态
        if ($this['status'] != self::STATUS_APPLYING)
        {
            throw_developer(___('仅申诉中的可执行驳回'));
        }

        // 修改状态
        $this['status'] = self::STATUS_REJECTED;
        // 驳回时间
        $this['rejectedAt'] = now_datetime();
        // 驳回原因
        $this['rejectedReason'] = $reason;
        // 操作员
        $this['adminId'] = self::adminId();

        // 保存
        $this->save();

        // 驳回申诉
        $this['market']->rejectAppeal();

        // 申诉未付款
        if ($this['type'] == self::TYPE_NOT_PAY)
        {
            // 添加记录
            MarketLog::insert($this['market'], self::admin(), MarketLog::TYPE_REJECT_APPEAL_PAY);
        }

        // 申诉未确认
        if ($this['type'] == self::TYPE_NOT_CONFIRM)
        {
            // 添加记录
            MarketLog::insert($this['market'], self::admin(), MarketLog::TYPE_REJECT_APPEAL_CONFIRM);
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
     * 关联用户
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 关联管理员
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function adminUser()
    {
        return $this->belongsTo(AdminUser::class, 'admin_id');
    }
}
