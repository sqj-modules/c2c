<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019/10/24
 * Time: 2:19 下午
 */
namespace SQJ\Modules\C2C\Models;

use App\Models\AdminUser;
use App\Models\User;
use App\Models\UserCredit;
use SQJ\Modules\C2C\Events\MarketConfirmed;
use SQJ\Modules\C2C\Events\MarketPaid;
use SQJ\Modules\C2C\Support\Settings;
use App\Traits\Locker;

class Market extends C2C
{
    use Locker;

    /**
     * 今日已完成成交的数量
     *
     * @return int
     */
    public static function todayFinishedTotal()
    {
        $total = self::query()
            ->whereDate('confirmed_at', now_date())
            ->sum('amount');

        return $total ?: 0;
    }

    /**
     * 匹配中
     */
    const STATUS_MATCHING = 0;
    /**
     * 待付款
     */
    const STATUS_PAYING = 1;
    /**
     * 待确认
     */
    const STATUS_CONFIRMING = 2;
    /**
     * 已完成
     */
    const STATUS_FINISHED = 3;
    /**
     * 申诉未付款
     */
    const STATUS_APPEAL_PAYING = -1;
    /**
     * 申诉未确认
     */
    const STATUS_APPEAL_CONFIRMING = -2;

    public static function pageList($lastId, $page, $condition)
    {
        // 查询构造器
        $builder = self::query();

        if (self::fromAdmin())
        {
            $builder->with(['buyer' => function($query) {
                $query->select(['id', User::accountType() . ' as account', 'nickname', 'avatar']);
            }, 'seller' => function($query) {
                $query->select(['id', User::accountType() . ' as account', 'nickname', 'avatar']);
            }]);

            $extras = [];
        }
        else
        {
            // 指定卖方用户
            if (isset($condition['sellerId']) && $condition['sellerId'] !== '')
            {
                $builder->where('seller_id', intval($condition['sellerId']));
            }

            // 指定买方用户
            if (isset($condition['buyerId']) && $condition['buyerId'] !== '')
            {
                $builder->where('buyer_id', intval($condition['buyerId']));
            }

            // 额外数据
            $extras = [
                'columns' => ['id', 'order_no', 'amount', 'unit_price', 'price', 'status', 'credit_type']
            ];
        }

        // 买家
        if (isset($condition['buyer']) && $condition['buyer'] !== '')
        {
            $builder->whereHas('buyer', function ($query) use ($condition) {
                $query->where('id', intval($condition['buyer']))
                    ->orWhere(User::accountType(), 'like', "%{$condition['buyer']}%")
                    ->orWhere('nickname', 'like', "%{$condition['buyer']}%");
            });
        }

        // 卖家
        if (isset($condition['seller']) && $condition['seller'] !== '')
        {
            $builder->whereHas('seller', function ($query) use ($condition) {
                $query->where('id', intval($condition['seller']))
                    ->orWhere(User::accountType(), 'like', "%{$condition['seller']}%")
                    ->orWhere('nickname', 'like', "%{$condition['seller']}%");
            });
        }

        // 挂单类型
        if (isset($condition['creditType']) && $condition['creditType'] !== '')
        {
            $builder->where('credit_type', $condition['creditType']);
        }

        // 挂单状态
        if (isset($condition['status']) && $condition['status'] !== '')
        {
            $builder->where('status', intval($condition['status']));
        }

        // 挂卖时间
        if (isset($condition['soldTime']) && !empty($condition['soldTime']))
        {
            $builder->whereBetween('sold_at', $condition['soldTime']);
        }

        // 挂买时间
        if (isset($condition['boughtTime']) && !empty($condition['boughtTime']))
        {
            $builder->whereBetween('bought_at', $condition['boughtTime']);
        }

        // 付款时间
        if (isset($condition['paidTime']) && !empty($condition['paidTime']))
        {
            $builder->whereBetween('paid_at', $condition['paidTime']);
        }

        // 确认时间
        if (isset($condition['confirmedTime']) && !empty($condition['confirmedTime']))
        {
            $builder->whereBetween('confirmed_at', $condition['confirmedTime']);
        }

        $data = self::paginate($builder, $lastId, $page, $extras);

        foreach ($data['list'] as &$item)
        {
            // 挂单类型
            $item['creditName'] = UserCredit::creditName($item['creditType']);

            // 状态名称
            if (self::fromAdmin())
            {
                $item['status'] = [
                    'label' => self::statusName($item['status']),
                    'style' => self::statusStyle($item['status']),
                    'value' => $item['status']
                ];
            }
        }

        return $data;
    }

    /**
     * 状态名称
     *
     * @param $status
     * @return string
     */
    public static function statusName($status)
    {
        switch ($status)
        {
            case self::STATUS_APPEAL_CONFIRMING:
                return '申诉未确认';
            case self::STATUS_APPEAL_PAYING:
                return '申诉未付款';
            case self::STATUS_FINISHED:
                return '已完成';
            case self::STATUS_CONFIRMING:
                return '待确认';
            case self::STATUS_PAYING:
                return '待付款';
            case self::STATUS_MATCHING:
                return '匹配中';
            default:
                return '';
        }
    }

    /**
     * 状态样式
     *
     * @param $status
     * @return string
     */
    public static function statusStyle($status)
    {
        switch ($status)
        {
            case self::STATUS_APPEAL_PAYING:
            case self::STATUS_APPEAL_CONFIRMING:
                return 'danger';
            case self::STATUS_FINISHED:
                return 'success';
            case self::STATUS_PAYING:
            case self::STATUS_CONFIRMING:
                return 'warning';
            case self::STATUS_MATCHING:
                return 'info';
            default:
                return '';
        }
    }

    /**
     * 挂单状态字典
     */
    public static function statusDictionary()
    {
        return [
            [
                'label' => self::statusName(self::STATUS_MATCHING),
                'value' => self::STATUS_MATCHING
            ],
            [
                'label' => self::statusName(self::STATUS_PAYING),
                'value' => self::STATUS_PAYING
            ],
            [
                'label' => self::statusName(self::STATUS_CONFIRMING),
                'value' => self::STATUS_CONFIRMING
            ],
            [
                'label' => self::statusName(self::STATUS_FINISHED),
                'value' => self::STATUS_FINISHED
            ],
            [
                'label' => self::statusName(self::STATUS_APPEAL_PAYING),
                'value' => self::STATUS_APPEAL_PAYING
            ],
            [
                'label' => self::statusName(self::STATUS_APPEAL_CONFIRMING),
                'value' => self::STATUS_APPEAL_CONFIRMING
            ],
        ];
    }

    /**
     * 是否正在出售中
     *
     * @return bool
     */
    public function getIsSellingAttribute()
    {
        return $this['buyerId'] == 0;
    }

    /**
     * 是否正在挂买中
     *
     * @return bool
     */
    public function getIsBuyingAttribute()
    {
        return $this['sellerId'] == 0;
    }

    /**
     * 关联卖家
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * 关联买家
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * 进行挂卖
     *
     * @param User $seller
     * @param $creditType
     * @param $amount
     * @param $unitPrice
     * @throws \App\Exceptions\UserException
     * @throws \Throwable
     */
    public static function fromSeller(User $seller, $creditType, $amount, $unitPrice)
    {
        // 检测参数
        self::checkParams($seller, $creditType, $amount);

        // 市场单
        $record = new Market();

        // 卖家
        $record['sellerId'] = $seller['id'];
        // 单号
        $record['orderNo'] = generate_order_no();
        // 挂卖类型
        $record['creditType'] = $creditType;
        // 数量
        $record['amount'] = $amount;
        // 单价
        $record['unitPrice'] = $unitPrice;
        // 总价
        $record['price'] = $amount * $unitPrice;
        // 挂卖时间
        $record['soldAt'] = now_datetime();
        // 状态
        $record['status'] = self::STATUS_MATCHING;

        $record->save();

        // 添加挂卖记录
        MarketLog::insert($record, $seller, MarketLog::TYPE_SELL);

        // 扣除金额
        UserCredit::setCredit($seller, $creditType, -1 * $amount, [
            ___('挂卖%amount%【%credit%】', [
                '%amount%' => $amount,
                '%credit%' => UserCredit::creditName($creditType)
            ]),
            ___('【%credit%】余额不足，无法挂卖', [
                '%credit%' => UserCredit::creditName($creditType)
            ])
        ]);
    }

    /**
     * 进行挂买
     *
     * @param User $buyer
     * @param $creditType
     * @param $amount
     * @param $unitPrice
     * @throws \App\Exceptions\UserException
     */
    public static function fromBuyer(User $buyer, $creditType, $amount, $unitPrice)
    {
        // 检测参数
        self::checkParams($buyer, $creditType, $amount);

        // 市场单
        $record = new Market();

        // 买家
        $record['buyerId'] = $buyer['id'];
        // 单号
        $record['orderNo'] = generate_order_no();
        // 挂卖类型
        $record['creditType'] = $creditType;
        // 数量
        $record['amount'] = $amount;
        // 单价
        $record['unitPrice'] = $unitPrice;
        // 总价
        $record['price'] = $amount * $unitPrice;
        // 挂买时间
        $record['boughtAt'] = now_datetime();
        // 状态
        $record['status'] = self::STATUS_MATCHING;

        $record->save();

        // 添加挂买记录
        MarketLog::insert($record, $buyer, MarketLog::TYPE_BUY);
    }

    /**
     * 向挂买单进行出售
     *
     * @param User $seller
     * @throws \App\Exceptions\UserException
     * @throws \Throwable
     */
    public function sellTo(User $seller)
    {
        // 自己不能向自己出售
        if ($seller['id'] == $this['buyerId'])
        {
            throw_user(___('无法向自己的挂买单出售！'));
        }

        Locker::lock("selling_{$this['id']}", function () use ($seller) {

            // 判断挂单状态
            if (!$this['isBuying'] || $this['status'] != self::STATUS_MATCHING)
            {
                throw_user(___('该挂买单已完成匹配！'));
            }

            // 卖出者
            $this['sellerId'] = $seller['id'];
            // 状态
            $this['status'] = self::STATUS_PAYING;
            // 卖出时间
            $this['soldAt'] = now_datetime();

            // 保存状态
            $this->save();

            // 扣除金额
            UserCredit::setCredit($seller, $this['creditType'], -1 * $this['amount'], [
                ___('向挂买单【%orderNo%】出售。', [
                    '%orderNo%' => $this['orderNo']
                ]),
                ___('%credit%不足，无法出售', [
                    '%credit%' => UserCredit::creditName($this['creditType'])
                ])
            ]);

        }, ___('其他用户正在出售，请重试其他挂买单！'));
    }

    /**
     * 买入挂卖单
     *
     * @param User $buyer
     * @throws \App\Exceptions\UserException
     * @throws \Throwable
     */
    public function buyFrom(User $buyer)
    {
        // 自己不能向自己出售
        if ($buyer['id'] == $this['sellerId'])
        {
            throw_user(___('无法买入自己的挂卖单！'));
        }

        Locker::lock("buying_{$this['id']}", function () use ($buyer) {

            // 判断挂单状态
            if (!$this['isSelling'] || $this['status'] != self::STATUS_MATCHING)
            {
                throw_user(___('该挂卖单已完成匹配！'));
            }

            // 买入者
            $this['buyerId'] = $buyer['id'];
            // 状态
            $this['status'] = self::STATUS_PAYING;
            // 买入时间
            $this['boughtAt'] = now_datetime();

            // 保存状态
            $this->save();

        }, ___('其他用户正在买入，请重试其他挂卖单！'));
    }

    /**
     * 挂单付款
     *
     * @param string $voucher 付款凭证
     * @throws \App\Exceptions\UserException
     */
    public function pay($voucher)
    {
        // 判断状态
        if ($this['status'] != Market::STATUS_PAYING)
        {
            throw_user(___('当前状态无法付款！'));
        }

        // 付款凭证
        $this['voucher'] = $voucher;
        // 付款时间
        $this['paidAt'] = now_datetime();
        // 挂单状态
        $this['status'] = self::STATUS_CONFIRMING;

        $this->save();

        // 添加记录
        MarketLog::insert($this, self::fromAdmin() ? self::admin() : $this['buyer'],
            MarketLog::TYPE_PAY);

        // 发放付款事件
        event(new MarketPaid($this));
    }

    /**
     * 重置付款状态
     *
     * @throws \App\Exceptions\UserException
     */
    public function resetPayment()
    {
        // 判断状态
        if ($this['status'] != Market::STATUS_APPEAL_PAYING)
        {
            throw_user(___('当前状态无法重置为未付款！'));
        }

        // 付款凭证
        $this['voucher'] = '';
        // 付款时间
        $this['paidAt'] = null;
        // 挂单状态
        $this['status'] = self::STATUS_PAYING;
        // 申诉时间
        $this['appealedAt'] = null;

        $this->save();
    }

    /**
     * 确认挂单
     *
     * @throws \App\Exceptions\UserException
     * @throws \Throwable
     */
    public function confirm()
    {
        // 判断状态
        if (!in_array($this['status'], [self::STATUS_CONFIRMING, self::STATUS_APPEAL_CONFIRMING]))
        {
            throw_user(___('当前状态无法确认！'));
        }

        // 确认时间
        $this['confirmedAt'] = now_datetime();
        // 挂单状态
        $this['status'] = self::STATUS_FINISHED;
        // 申诉时间
        $this['appealedAt'] = null;

        $this->save();

        // 后台操作者
        $operator = self::operator();

        // 添加记录
        MarketLog::insert($this, $operator ?: $this['seller'], MarketLog::TYPE_CONFIRM);

        // 计算手续费
        $fee = $this->calculateFee();

        // 将金额转到买家手中
        UserCredit::setCredit($this['buyer'], $this['creditType'],
            bcsub($this['amount'], $fee, config('app.user_credit_place')), ___('卖家确认挂单'));

        // 发放确认事件
        event(new MarketConfirmed($this));
    }

    /**
     * 计算手续费
     *
     * @return int|string
     */
    private function calculateFee()
    {
        // 获取配置参数
        $params = Settings::get(Settings::MARKET);

        // 判断是否存在
        if (!isset($params[$this['creditType']]))
        {
            return 0;
        }

        // 重构参数
        $params = $params[$this['creditType']];

        // 判断是否启用
        if (!isset($params['isEnabled']) || !$params['isEnabled'])
        {
            return 0;
        }

        // 是否配置手续费
        if (empty($params['feeRate']))
        {
            return 0;
        }

        // 设置计算精度
        bcscale(config('app.user_credit_place'));

        return bcmul($this['amount'], bcdiv($params['feeRate'], 100.0));
    }

    /**
     * 申诉挂单
     *
     * @param $content
     * @param $images
     * @throws \App\Exceptions\UserException
     */
    public function appeal($content, $images)
    {
        // 判断状态
        if (!in_array($this['status'], [self::STATUS_PAYING, self::STATUS_CONFIRMING]))
        {
            throw_user(___('当前状态无法申诉！'));
        }

        // 申诉时间
        $this['appealedAt'] = now_datetime();
        // 状态
        $this['status'] =  -1 * $this['status'];

        $this->save();

        // 类型
        if ($this['status'] == self::STATUS_APPEAL_PAYING)
        {
            $type = MarketLog::TYPE_APPEAL_PAY;
        }
        else
        {
            $type = MarketLog::TYPE_APPEAL_CONFIRM;
        }

        // 添加记录
        MarketLog::insert($this, self::fromAdmin() ? self::admin() : $this['seller'], $type);

        // 添加申诉记录
        MarketAppeal::insert($this, $content, $images);
    }

    /**
     * 驳回申诉
     */
    public function rejectAppeal()
    {
        // 申诉时间
        $this['appealedAt'] = null;
        // 状态
        $this['status'] =  -1 * $this['status'];

        $this->save();
    }

    /**
     * 撤销挂单
     *
     * @throws \App\Exceptions\UserException
     * @throws \Throwable
     */
    public function cancel()
    {
        // 判断状态
        if ($this['status'] != self::STATUS_MATCHING)
        {
            throw_user(___('匹配完成，无法撤销！'));
        }

        // 如果是挂买单，直接删除
        if (!$this['sellerId'])
        {
            $this->delete();
        }
        // 如果是挂卖单，先撤回挂卖的金额，再删除
        else
        {
            // 退回挂单费用
            UserCredit::setCredit($this['seller'], $this['creditType'], $this['amount'], ___('撤销挂单'));

            // 删除挂单
            $this->delete();
        }
    }

    /**
     * 自动确认挂单
     *
     * @param $callback
     */
    public static function autoConfirm($callback)
    {
        // 获取基本参数
        $params = Settings::get(Settings::BASIC);

        // 确认过期时间，未设置默认用两小时
        $confirmLimit = isset($params['confirmLimit']) ? $params['confirmLimit'] : 2;

        // 遍历数据处理
        self::query()
            ->where('status', self::STATUS_CONFIRMING)
            ->where('paid_at', '<' ,now()->subHours(intval($confirmLimit)))
            ->whereNull('confirmed_at')
            ->chunkById(50, function ($marketList) use ($callback) {
                foreach ($marketList as $market)
                {
                    $callback($market);
                }
            });
    }

    /**
     * 回滚挂单
     *
     * @return void
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     */
    public function rollback()
    {
        // 判断挂单状态
        if ($this['status'] !== self::STATUS_PAYING)
        {
            throw_developer(___('当前状态无法流回市场'));
        }

        // 判断是挂卖单还是挂买单
        if (empty($this['sellerId']) || empty($this['soldAt']))
        {
            throw_developer(___('仅挂卖单可回流市场'));
        }

        // 更改状态
        $this['status'] = self::STATUS_MATCHING;
        // 清除购买信息
        $this['buyerId'] = 0;
        $this['boughtAt'] = null;

        $this->save();

        // 添加记录
        MarketLog::insert($this, self::operator(), MarketLog::TYPE_ROLLBACK);
    }

    /**
     * 操作者
     *
     * @return AdminUser|null
     * @throws \App\Exceptions\UserException
     */
    private static function operator()
    {
        if (app()->runningInConsole())
        {
            return AdminUser::getByUsername(SQJ_ADMIN_USER_DEV);
        }
        else
        {
            return self::admin();
        }
    }

    /**
     * 自动返回市场
     *
     * @param $callback
     */
    public static function autoRollback($callback)
    {
        // 获取基本参数
        $params = Settings::get(Settings::BASIC);

        // 付款过期时间，未设置默认用两小时
        $paymentLimit = isset($params['paymentLimit']) ? $params['paymentLimit'] : 2;

        // 遍历数据处理
        self::query()
            ->where('status', self::STATUS_PAYING)
            ->where('bought_at', '<' ,now()->subHours(intval($paymentLimit)))
            ->whereNotNull('sold_at')
            ->where('seller_id', '>', 0)
            ->whereNull('paid_at')
            ->chunkById(50, function ($marketList) use ($callback) {
                foreach ($marketList as $market)
                {
                    $callback($market);
                }
            });
    }

    /**
     * 检测是否能够挂单
     *
     * @param User $user
     * @param $creditType
     * @param $money
     * @throws \App\Exceptions\UserException
     */
    private static function checkParams(User $user, $creditType, $money)
    {
        // 获取配置参数
        $params = Settings::get(Settings::MARKET);

        // 判断是否开启挂单
        if (empty($creditType) || !isset($params[$creditType]))
        {
            throw_user(___('系统尚未开启【%credit%】的挂单功能', [
                '%credit%' => UserCredit::creditName($creditType)
            ]));
        }

        // 钱包是否启用
        if (!UserCredit::checkEnabled($creditType))
        {
            throw_user(___('钱包【%credit%】尚未启用', [
                '%credit%' => UserCredit::creditName($creditType)
            ]));
        }

        // 重组参数
        $params = $params[$creditType];

        // 判断是否启用
        if (!$params['isEnabled'])
        {
            throw_user(___('系统未开启【%credit%】的挂单功能', [
                '%credit%' => UserCredit::creditName($creditType)
            ]));
        }

        // 判断周期
        if (!empty($params['period']))
        {
            // 获取本日星期
            if (!in_array(date('N'), $params['period']))
            {
                throw_user(___('今日系统不允许挂单'));
            }
        }

        // 判断时间
        if (!empty($params['timeRange']))
        {
            // 当前时间
            $datetime = now_datetime();

            if ($datetime < $params['timeRange'][0] || $datetime > $params['timeRange'][1])
            {
                throw_user(___('当前时间系统不允许挂单。可挂单时间范围是：%startTime% ~ %endTime%。', [
                    '%startTime%' => $params['timeRange'][0],
                    '%endTime%' => $params['timeRange'][1]
                ]));
            }
        }

        // 判断挂单次数
        if (!empty($params['dailyLimit']))
        {
            // 今日挂单次数
            $todayCount = self::todayCount($user);

            if ($todayCount >= $params['dailyLimit'])
            {
                throw_user(___('挂单次数已达到上限。每人每天仅可进行 %dailyLimit% 笔挂单', [
                    '%dailyLimit%' => $params['dailyLimit']
                ]));
            }
        }

        // 挂单倍数
        if (isset($params['baseNum']) && $params['baseNum'] > 0)
        {
            if ($money % $params['baseNum'] != 0)
            {
                throw_user(___('挂单金额必须是 %baseNum% 的整数倍', [
                    '%baseNum%' => $params['baseNum']
                ]));
            }
        }

        // 最低金额
        if (isset($params['minNum']) && $params['minNum'] > 0)
        {
            if ($money < $params['minNum'])
            {
                throw_user(___('挂单金额不得低于 %minNum%。', [
                    '%minNum%' => $params['minNum']
                ]));
            }
        }

        // 最高金额
        if (isset($params['maxNum']) && $params['maxNum'] > 0)
        {
            if ($money > $params['maxNum'])
            {
                throw_user(___('挂单金额不得高于 %maxNum%。', [
                    '%maxNum%' => $params['maxNum']
                ]));
            }
        }
    }

    /**
     * 指定用户今日挂单的数量
     *
     * @param User $user
     * @return int
     */
    public static function todayCount(User $user)
    {
        return self::query()
            ->where(function ($query) use ($user) {
                $query->where('seller_id', $user['id'])
                    ->whereDate('sold_at', now_date());
            })
            ->orWhere(function ($query) use ($user) {
                $query->where('seller_id', $user['id'])
                    ->whereDate('sold_at', now_date());
            })
            ->count();
    }
}
