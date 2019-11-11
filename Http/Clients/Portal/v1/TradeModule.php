<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019/10/25
 * Time: 7:50 下午
 */
namespace SQJ\Modules\C2C\Http\Clients\Portal\v1;

use App\Http\Controllers\Api\ApiModule;
use App\Models\UserCredit;
use SQJ\Modules\C2C\Models\Market;
use SQJ\Modules\C2C\Models\PaymentAccount;
use App\Rules\ImageUrl;
use App\Rules\UserSafeword;

/**
 * @ApiSector (交易相关)
 *
 * Class TradeModule
 * @package SQJ\Modules\C2C\Http\Clients\Portal\v1
 */
class TradeModule  extends ApiModule
{

    /**
     * 接口编码列表
     *
     * @return mixed
     */
    protected function interfaceList()
    {
        return [
            // 卖单列表
            '1000' => 'soldList',
            // 买单列表
            '1001' => 'boughtList',
            // 交易单详情
            '1002' => 'detail',
            // 付款
            '2000' => 'pay',
            // 确认
            '2001' => 'confirm',
            // 申诉
            '2002' => 'appeal',
            // 撤销
            '2003' => 'cancel'
        ];
    }

    /**
     * @ApiTitle (卖单列表)
     *
     * @ApiParams (name="lastId", type="number", required=true, description="最新记录ID。首次传0，之后传接口返回的lastId")
     * @ApiParams (name="page", type="number", required=true, description="请求数据页码")
     *
     * @ApiReturnParams (name="lastId", type="number", required=true, description="最新一条数据的ID")
     * @ApiReturnParams (name="total", type="number", required=true, description="数据总量")
     * @ApiReturnParams (name="perPage", type="number", required=true, description="每页数据量")
     * @ApiReturnParams (name="currentPage", type="number", required=true, description="当前页码")
     * @ApiReturnParams (name="lastPage", type="number", required=true, description="尾页页码")
     * @ApiReturnParams (name="list", type="array[object]", required=true, description="数据列表")
     *
     * @ApiReturnParams (name="id", group="list", type="number", required=true, description="挂单ID")
     * @ApiReturnParams (name="creditName", group="list", type="string", required=true, description="挂卖类型")
     * @ApiReturnParams (name="orderNo", group="list", type="string", required=true, description="单号")
     * @ApiReturnParams (name="amount", group="list", type="number", required=true, description="挂单数量")
     * @ApiReturnParams (name="unitPrice", group="list", type="number", required=true, description="单价")
     * @ApiReturnParams (name="price", group="list", type="number", required=true, description="总价")
     * @ApiReturnParams (name="status", group="list", type="number", required=true, description="挂单状态。0：匹配中；1：待付款；2：待确认；3：已完成；-1：申诉未付款；-2：申诉未确认。")
     *
     * @return mixed
     * @throws \App\Exceptions\DeveloperException
     */
    protected function soldList()
    {
        // 当前用户
        $user = $this->user();

        return $this->pageList(Market::class, [
            'sellerId' => $user['id']
        ]);
    }

    /**
     * @ApiTitle (买单列表)
     *
     * @ApiParams (name="lastId", type="number", required=true, description="最新记录ID。首次传0，之后传接口返回的lastId")
     * @ApiParams (name="page", type="number", required=true, description="请求数据页码")
     *
     * @ApiReturnParams (name="lastId", type="number", required=true, description="最新一条数据的ID")
     * @ApiReturnParams (name="total", type="number", required=true, description="数据总量")
     * @ApiReturnParams (name="perPage", type="number", required=true, description="每页数据量")
     * @ApiReturnParams (name="currentPage", type="number", required=true, description="当前页码")
     * @ApiReturnParams (name="lastPage", type="number", required=true, description="尾页页码")
     * @ApiReturnParams (name="list", type="array[object]", required=true, description="数据列表")
     *
     * @ApiReturnParams (name="id", group="list", type="number", required=true, description="挂单ID")
     * @ApiReturnParams (name="creditName", group="list", type="string", required=true, description="挂卖类型")
     * @ApiReturnParams (name="orderNo", group="list", type="string", required=true, description="单号")
     * @ApiReturnParams (name="amount", group="list", type="number", required=true, description="挂单数量")
     * @ApiReturnParams (name="unitPrice", group="list", type="number", required=true, description="单价")
     * @ApiReturnParams (name="price", group="list", type="number", required=true, description="总价")
     * @ApiReturnParams (name="status", group="list", type="number", required=true, description="挂单状态。0：匹配中；1：待付款；2：待确认；3：已完成；-1：申诉未付款；-2：申诉未确认。")
     *
     * @return mixed
     * @throws \App\Exceptions\DeveloperException
     */
    protected function boughtList()
    {
        // 当前用户
        $user = $this->user();

        return $this->pageList(Market::class, [
            'buyerId' => $user['id']
        ]);
    }

    /**
     * @ApiTitle (挂单详情)
     *
     * @ApiParams (name="id", type="number", required=true, description="挂单ID")
     *
     * @ApiReturnParams (name="id", type="number", required=true, description="挂单ID")
     * @ApiReturnParams (name="creditName", type="string", required=true, description="挂卖类型")
     * @ApiReturnParams (name="orderNo", type="string", required=true, description="单号")
     * @ApiReturnParams (name="status", type="number", required=true, description="挂单状态。0：匹配中；1：待付款；2：待确认；3：已完成；-1：申诉未付款；-2：申诉未确认。")
     * @ApiReturnParams (name="amount", type="number", required=true, description="挂单数量")
     * @ApiReturnParams (name="unitPrice", type="number", required=true, description="单价")
     * @ApiReturnParams (name="price", type="number", required=true, description="总价")
     * @ApiReturnParams (name="type", type="number", required=true, description="挂单类型。1：买单；-1：卖单")
     * @ApiReturnParams (name="hasSeller", type="boolean", required=true, description="是否存在卖家。true：存在；false：不存在。")
     * @ApiReturnParams (name="hasBuyer", type="boolean", required=true, description="是否存在买家。true：存在；false：不存在。")
     * @ApiReturnParams (name="buyer", type="object", required=true, description="买方信息。")
     * @ApiReturnParams (name="seller", type="object", required=true, description="卖方信息。")
     * @ApiReturnParams (name="account", type="object", required=true, description="卖方收款账号。")
     * @ApiReturnParams (name="voucher", type="string", required=true, description="付款凭证")
     *
     * @ApiReturnParams (name="nickname", group="buyer", type="string", required=true, description="昵称")
     * @ApiReturnParams (name="mobile", group="buyer", type="string", required=true, description="手机号")
     *
     * @ApiReturnParams (name="nickname", group="seller", type="string", required=true, description="昵称")
     * @ApiReturnParams (name="mobile", group="seller", type="string", required=true, description="手机号")
     *
     * @ApiReturnParams (name="bankCard", group="account", type="object", required=true, description="银行卡，未绑定时返回空对象。")
     * @ApiReturnParams (name="alipay", group="account", type="object", required=true, description="支付宝，未绑定时返回空对象。")
     * @ApiReturnParams (name="wechat", group="account", type="object", required=true, description="微信，未绑定时返回空对象。")
     *
     * @ApiReturnParams (name="id", group="account.bankCard", type="number", required=true, description="账号ID")
     * @ApiReturnParams (name="type", group="account.bankCard", type="number", required=true, description="账号类型。0：银行卡；1：支付宝；2：微信")
     * @ApiReturnParams (name="account", group="account.bankCard", type="string", required=true, description="账号")
     * @ApiReturnParams (name="realName", group="account.bankCard", type="string", required=true, description="真实姓名")
     * @ApiReturnParams (name="bankName", group="account.bankCard", type="string", required=true, description="开户行")
     * @ApiReturnParams (name="bankAddress", group="account.bankCard", type="string", required=true, description="开户行支行")
     *
     * @ApiReturnParams (name="id", group="account.alipay", type="number", required=true, description="账号ID")
     * @ApiReturnParams (name="type", group="account.alipay", type="number", required=true, description="账号类型。0：银行卡；1：支付宝；2：微信")
     * @ApiReturnParams (name="account", group="account.alipay", type="string", required=true, description="账号")
     * @ApiReturnParams (name="realName", group="account.alipay", type="string", required=true, description="真实姓名")
     * @ApiReturnParams (name="qrCode", group="account.alipay", type="string", required=true, description="收款二维码")
     *
     * @ApiReturnParams (name="id", group="account.wechat", type="number", required=true, description="账号ID")
     * @ApiReturnParams (name="type", group="account.wechat", type="number", required=true, description="账号类型。0：银行卡；1：支付宝；2：微信")
     * @ApiReturnParams (name="account", group="account.wechat", type="string", required=true, description="账号")
     * @ApiReturnParams (name="realName", group="account.wechat", type="string", required=true, description="真实姓名")
     * @ApiReturnParams (name="qrCode", group="account.wechat", type="string", required=true, description="收款二维码")
     *
     * @return array
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     */
    protected function detail()
    {
        // 当前用户
        $user = $this->user();

        // 验证数据
        $data = $this->validate([
            'id' => 'required|numeric'
        ]);

        // 获取挂单记录
        $market = Market::getById($data['id']);

        // 判断是否为挂单拥有者
        if (!in_array($user['id'], [$market['sellerId'], $market['buyerId']]))
        {
            throw_user(___('您不是该挂单的拥有者，无法查看！'));
        }

        // 投诉中的挂单无法查看
        if (in_array($market['status'], [Market::STATUS_APPEAL_PAYING, Market::STATUS_APPEAL_CONFIRMING]))
        {
            throw_user(___('该挂单已被申诉，无法查看！'));
        }

        // 要返回的基础数据
        $data = [
            'id' => $market['id'],
            'creditName' => UserCredit::creditName($market['creditType']),
            'orderNo' => $market['orderNo'],
            'status' => $market['status'],
            'amount' => $market['amount'],
            'unitPrice' => $market['unitPrice'],
            'price' => $market['price'],
            'type' => $market['sellerId'] == $user['id'] ? -1 : 1,
            'hasSeller' => !empty($market['sellerId']),
            'hasBuyer' => !empty($market['buyerId']),
            'voucher' => $market['voucher']
        ];

        // 存在卖家时
        if ($market['sellerId'])
        {
            $data = array_merge($data, [
                'seller' => [
                    'nickname' => $market['seller']['nickname'],
                    'mobile' => $market['seller']['mobile']
                ],
                'account' => [
                    'bankCard' => PaymentAccount::firstByType($market['seller'], PaymentAccount::TYPE_BANK_CARD),
                    'alipay' => PaymentAccount::firstByType($market['seller'], PaymentAccount::TYPE_ALIPAY),
                    'wechat' => PaymentAccount::firstByType($market['seller'], PaymentAccount::TYPE_WECHAT)
                ]
            ]);
        }

        // 存在买家时
        if ($market['buyerId'])
        {
            $data['buyer'] = [
                'nickname' => $market['buyer']['nickname'],
                'mobile' => $market['buyer']['mobile']
            ];
        }

        return $data;
    }

    /**
     * @ApiTitle (付款)
     *
     * @ApiParams (name="id", type="number", required=true, description="挂单ID")
     * @ApiParams (name="voucher", type="string", required=true, description="付款凭证")
     * @ApiParams (name="safeword", type="string", required=true, description="安全密码")
     *
     * @return string
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     */
    protected function pay()
    {
        // 当前用户
        $user = $this->user();

        // 验证数据
        $data = $this->validate([
            'id' => 'required|numeric',
            'voucher' => [
                'required', 'string', 'url', new ImageUrl()
            ],
            'safeword' => [
                'required', 'string', new UserSafeword()
            ]
        ]);

        // 获取挂单记录
        $market = Market::getById($data['id']);

        // 判断身份
        if ($user['id'] != $market['buyerId'])
        {
            throw_user(___('您不是该挂单的买家，无法付款！'));
        }

        // 验证安全密码
        $user->checkSafeword($data['safeword'], __CLASS__, __FUNCTION__);

        // 挂单付款
        $market->pay($data['voucher']);

        return ___('付款成功');
    }

    /**
     * @ApiTitle (确认)
     *
     * @ApiParams (name="id", type="number", required=true, description="挂单ID")
     * @ApiParams (name="safeword", type="string", required=true, description="安全密码")
     *
     * @return string
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     */
    protected function confirm()
    {
        // 当前用户
        $user = $this->user();

        // 验证数据
        $data = $this->validate([
            'id' => 'required|numeric',
            'safeword' => [
                'required', 'string', new UserSafeword()
            ]
        ]);

        // 获取挂单记录
        $market = Market::getById($data['id']);

        // 判断身份
        if ($user['id'] != $market['sellerId'])
        {
            throw_user(___('您不是该挂单的卖家，无法确认！'));
        }

        // 验证安全密码
        $user->checkSafeword($data['safeword'], __CLASS__, __FUNCTION__);

        // 挂单确认
        $market->confirm();

        return ___('确认成功');
    }

    /**
     * @ApiTitle (申诉)
     *
     * @ApiParams (name="id", type="number", required=true, description="挂单ID")
     * @ApiParams (name="content", type="string", required=true, description="申诉内容")
     * @ApiParams (name="images", type="array[string]", required=true, description="申诉凭证。图片数组")
     *
     * @return string
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     */
    protected function appeal()
    {
        // 当前用户
        $user = $this->user();

        // 验证数据
        $data = $this->validate([
            'id' => 'required|numeric',
            'content' => 'required|string',
            'images' => 'required|array|min:1',
            'images.*' => [
                'required', 'string', 'url', new ImageUrl()
            ]
        ]);

        // 获取挂单记录
        $market = Market::getById($data['id']);

        // 判断是否为挂单拥有者
        if (!in_array($user['id'], [$market['sellerId'], $market['buyerId']]))
        {
            throw_user(___('您不是该挂单的拥有者，无法申诉！'));
        }

        // 挂单申诉
        $market->appeal($data['content'], $data['images']);

        return ___('申诉成功');
    }

    /**
     * @ApiTitle (撤销)
     *
     * @ApiSummary (撤销后从挂单列表中删除)
     *
     * @ApiParams (name="id", type="number", required=true, description="挂单ID")
     *
     * @return string
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     */
    protected function cancel()
    {
        // 验证数据
        $data = $this->validate([
            'id' => 'required|numeric'
        ]);

        // 当前用户
        $user = $this->user();

        // 获取挂单记录
        $market = Market::getById($data['id']);

        // 判断是否为挂单拥有者
        if (!in_array($user['id'], [$market['sellerId'], $market['buyerId']]))
        {
            throw_user(___('您不是该挂单的拥有者，无法撤销！'));
        }

        // 撤销
        $market->cancel();

        return ___('撤销成功');
    }
}
