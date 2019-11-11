<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019-06-25
 * Time: 11:42
 */
namespace SQJ\Modules\C2C\Models;

use App\Models\User;
use App\Utils\BankInfo;

class PaymentAccount extends C2C
{
    /**
     * @var string 对象不存在时的提示信息
     */
    protected static $nonexistent = '提现账号不存在';

    /**
     * 银行卡
     */
    const TYPE_BANK_CARD = 0;

    /**
     * 支付宝
     */
    const TYPE_ALIPAY = 1;

    /**
     * 微信
     */
    const TYPE_WECHAT = 2;

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
     * 分页获取数据
     *
     * @param int $lastId 最新ID
     * @param int $page 当前页码
     * @param array $condition 搜索条件
     * @return array
     */
    public static function pageList($lastId, $page, $condition = [])
    {
        // 创建查询构造器
        $builder = self::query();

        if (self::fromAdmin())
        {
            $builder->with(['user' => function ($query) {
                $query->select(['id', User::accountType() . ' as account', 'nickname', 'avatar']);
            }])
                ->has('user');

            $extras = [];
        }
        else
        {
            // 用户 ID
            if (isset($condition['userId']) && $condition['userId'] !== '')
            {
                $builder->where('user_id', intval($condition['userId']));
            }

            $extras = [
                'columns' => [
                    'id', 'type', 'account', 'real_name', 'bank_name', 'bank_address', 'qr_code'
                ]
            ];
        }

        // 指定用户
        if (isset($condition['user']) && $condition['user'] !== '')
        {
            $builder->whereHas('user', function ($query) use ($condition) {
                $query->where('id', intval($condition['user']))
                    ->orWhere('nickname', 'like', "%{$condition['user']}%")
                    ->orWhere(User::accountType(), 'like', "%{$condition['user']}%");
            });
        }

        // 账号类型
        if (isset($condition['type']) && $condition['type'] !== '')
        {
            $builder->where('type', intval($condition['type']));
        }

        // 账号信息
        if (isset($condition['account']) && $condition['account'] !== '')
        {
            $builder->where('account', 'like', "%{$condition['account']}%");
        }

        // 真实姓名
        if (isset($condition['realName']) && $condition['realName'] !== '')
        {
            $builder->where('real_name', 'like', "%{$condition['realName']}%");
        }

        return self::paginate($builder, $lastId, $page, $extras);
    }

    /**
     * 创建付款账号
     *
     * @param User $user
     * @param array $data 账号内容
     * @throws \App\Exceptions\UserException
     */
    public static function insert(User $user, $data)
    {
        // 创建账号
        $record = new PaymentAccount();

        // 当前用户
        $record['userId'] = $user['id'];

        // 执行修改
        $record->change($data);
    }

    /**
     * 修改账号内容
     *
     * @param array $content 要修改的内容
     * @return bool
     * @throws \App\Exceptions\UserException
     */
    public function change($content)
    {
        // 账号类型
        if (isset($content['type']) && $content['type'] !== '')
        {
            $this['type'] = intval($content['type']);
        }

        // 账号
        if (isset($content['account']) && $content['account'] !== '')
        {
            $this['account'] = $content['account'];
        }

        // 真实姓名
        if (isset($content['realName']) && $content['realName'] !== '')
        {
            $this['real_name'] = $content['realName'];
        }

        // 银行名称
        if ($this['type'] == self::TYPE_BANK_CARD)
        {
            // 银行卡信息
            $bankInfo = BankInfo::info($this['account']);

            $this['bank_name'] = $bankInfo['bankName'];
        }

        // 银行地址
        if (isset($content['bankAddress']) && $content['bankAddress'] !== '')
        {
            $this['bank_address'] = $content['bankAddress'];
        }

        // 付款二维码
        if (isset($content['qrCode']) && $content['qrCode'] !== '')
        {
            $this['qr_code'] = $content['qrCode'];
        }

        // 启用状态
        if (isset($content['isEnabled']) && $content['isEnabled'] !== '')
        {
            $this['is_enabled'] = $content['isEnabled'] ? 1 : 0;
        }

        return $this->save();
    }

    /**
     * 是否是支付宝账号
     *
     * @return bool
     */
    public function isAlipay()
    {
        return $this['type'] == self::TYPE_ALIPAY;
    }

    /**
     * 是否是微信账号
     *
     * @return bool
     */
    public function isWechat()
    {
        return $this['type'] == self::TYPE_WECHAT;
    }

    /**
     * 是否是银行卡账号
     *
     * @return bool
     */
    public function isBankCard()
    {
        return $this['type'] == self::TYPE_BANK_CARD;
    }

    /**
     * 根据类型返回账号
     *
     * @param User $user
     * @param $type
     * @return \stdClass
     */
    public static function firstByType(User $user, $type)
    {
        // 返回的字段
        $columns = ['id', 'type', 'account', 'real_name'];

        // 根据类型组织字段
        if ($type == self::TYPE_BANK_CARD)
        {
            $columns = array_merge($columns, [
                'bank_name', 'bank_address'
            ]);
        }
        else
        {
            $columns[] = 'qr_code';
        }

        // 获取账号
        $account = self::query()
            ->where('user_id', $user['id'])
            ->where('type', $type)
            ->select($columns)
            ->first();

        return $account ?: new \stdClass();
    }
}
