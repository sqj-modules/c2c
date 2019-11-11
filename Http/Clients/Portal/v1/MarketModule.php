<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019/10/24
 * Time: 1:44 下午
 */
namespace SQJ\Modules\C2C\Http\Clients\Portal\v1;

use App\Http\Controllers\Api\ApiModule;
use App\Models\UserCredit;
use SQJ\Modules\C2C\Models\Market;
use SQJ\Modules\C2C\Models\PaymentAccount;
use App\Rules\UserSafeword;
use Illuminate\Support\Arr;

/**
 * @ApiSector (市场相关)
 *
 * Class MarketModule
 * @package SQJ\Modules\C2C\Http\Clients\Portal\v1
 */
class MarketModule extends ApiModule
{

    /**
     * 接口编码列表
     *
     * @return mixed
     */
    protected function interfaceList()
    {
        return [
            // 市场概览
            '1000' => 'summary',
            // 卖单列表
            '1001' => 'sellingList',
            // 买单列表
            '1002' => 'buyingList',
            // 交易详情,
            '1003' => 'detail',
            // 挂卖
            '2000' => 'hangSell',
            // 挂买
            '2001' => 'hangBuy',
            // 出售
            '3000' => 'sellToMarket',
            // 买入
            '3001' => 'buyFromMarket'
        ];
    }

    /**
     * @ApiTitle (市场概况)
     *
     * @ApiReturnParams (name="today", type="number", required=true, description="今日总成交额")
     *
     * @return array
     */
    protected function summary()
    {
        return [
            'today' => Market::todayFinishedTotal()
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
     *
     * @return mixed
     * @throws \App\Exceptions\DeveloperException
     */
    protected function sellingList()
    {
        return $this->pageList(Market::class, [
            'status' => Market::STATUS_MATCHING,
            'buyerId' => 0
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
     *
     * @return mixed
     * @throws \App\Exceptions\DeveloperException
     */
    protected function buyingList()
    {
        return $this->pageList(Market::class, [
            'status' => Market::STATUS_MATCHING,
            'sellerId' => 0
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
     * @ApiReturnParams (name="amount", type="number", required=true, description="挂单数量")
     * @ApiReturnParams (name="unitPrice", type="number", required=true, description="单价")
     * @ApiReturnParams (name="price", type="number", required=true, description="总价")
     * @ApiReturnParams (name="type", type="number", required=true, description="挂单类型。1：买单；-1：卖单")
     * @ApiReturnParams (name="buyer", type="object", required=true, description="买方信息。仅当type为1时")
     * @ApiReturnParams (name="seller", type="object", required=true, description="卖方信息。仅当type为-1时。")
     * @ApiReturnParams (name="account", type="object", required=true, description="卖方收款账号。仅当type为-1时")
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
        // 验证数据
        $data = $this->validate([
            'id' => 'required|numeric'
        ]);

        // 获取挂单记录
        $market = Market::getById($data['id']);

        // 判断挂单状态
        if ($market['status'] != Market::STATUS_MATCHING)
        {
            throw_user(___('挂单已交易'));
        }

        // 初始化要返回的数据
        $data = [
            'id' => $market['id'],
            'creditName' => UserCredit::creditName($market['creditType']),
            'orderNo' => $market['orderNo'],
            'amount' => $market['amount'],
            'unitPrice' => $market['unitPrice'],
            'price' => $market['price']
        ];

        // 卖单
        if ($market['isSelling'])
        {
            // 挂单类型
            $data['type'] = -1;
            // 卖家信息
            $data['seller'] = [
                'nickname' => $market['seller']['nickname'],
                'mobile' => $market['seller']['mobile']
            ];
            // 收款账号
            $data['account'] = [
                'bankCard' => PaymentAccount::firstByType($market['seller'], PaymentAccount::TYPE_BANK_CARD),
                'alipay' => PaymentAccount::firstByType($market['seller'], PaymentAccount::TYPE_ALIPAY),
                'wechat' => PaymentAccount::firstByType($market['seller'], PaymentAccount::TYPE_WECHAT)
            ];
        }

        // 买单
        if ($market['isBuying'])
        {
            // 挂单类型
            $data['type'] = 1;
            // 买家信息
            $data['buyer'] = [
                'nickname' => $market['buyer']['nickname'],
                'mobile' => $market['buyer']['mobile']
            ];
        }

        return $data;
    }

    /**
     * @ApiTitle (挂卖)
     *
     * @ApiParams (name="creditType", type="string", required=true, description="挂卖类型。")
     * @ApiParams (name="amount", type="number", required=true, description="挂卖数量")
     * @ApiParams (name="unitPrice", type="number", required=true, description="单价")
     * @ApiParams (name="safeword", type="string", required=true, description="安全密码")
     *
     * @return string
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     * @throws \Throwable
     */
    protected function hangSell()
    {
        // 当前用户
        $user = $this->user();

        // 判断是否实名认证
        if (!$user['isAuthorized'])
        {
            throw_user(___('尚未实名认证，无法挂卖'));
        }

        // 验证数据
        $data = $this->validateTrade();

        // 进行挂卖
        Market::fromSeller($user, $data['creditType'], abs($data['amount']), abs($data['unitPrice']));

        return ___('挂卖成功');
    }

    /**
     * @ApiTitle (挂买)
     *
     * @ApiParams (name="creditType", type="string", required=true, description="挂买类型。")
     * @ApiParams (name="amount", type="number", required=true, description="挂买数量")
     * @ApiParams (name="unitPrice", type="number", required=true, description="单价")
     * @ApiParams (name="safeword", type="string", required=true, description="安全密码")
     *
     * @return string
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     */
    protected function hangBuy()
    {
        // 当前用户
        $user = $this->user();

        // 判断是否实名认证
        if (!$user['isAuthorized'])
        {
            throw_user(___('尚未实名认证，无法挂买'));
        }

        // 验证数据
        $data = $this->validateTrade();

        // 进行挂买
        Market::fromBuyer($user, $data['creditType'], abs($data['amount']), abs($data['unitPrice']));

        return ___('挂买成功');
    }

    /**
     * 验证交易数据
     *
     * @return array
     * @throws \App\Exceptions\DeveloperException
     */
    private function validateTrade()
    {
        $data = $this->validate([
            'creditType' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'unitPrice' => 'required|numeric|min:0',
            'safeword' => [
                'required', 'string', new UserSafeword()
            ]
        ]);

        // 验证是否允许挂卖
        if (!in_array($data['creditType'], config('c2c.enabled_credits')))
        {
            throw_developer(___('该钱包类型暂不允许挂买挂卖'));
        }

        // 当前用户
        $user = $this->user();

        // 验证安全密码
        $user->checkSafeword($data['safeword'], __CLASS__, __FUNCTION__);

        return Arr::only($data, [
            'creditType', 'amount', 'unitPrice'
        ]);
    }

    /**
     * @ApiTitle (出售)
     *
     * @ApiParams (name="id", type="numeric", required=true, description="市场挂单ID")
     * @ApiParams (name="safeword", type="string", required=true, description="安全密码")
     *
     * @return string
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     */
    protected function sellToMarket()
    {
        // 验证数据
        $data = $this->validate([
            'id' => 'required|numeric',
            'safeword' => [
                'required', 'string', new UserSafeword()
            ]
        ]);

        // 当前用户
        $user = $this->user();

        // 验证安全密码
        $user->checkSafeword($data['safeword'], __CLASS__, __FUNCTION__);

        // 获取挂单记录
        $market = Market::getById($data['id']);

        // 卖给用户
        $market->sellTo($user);

        return ___('出售成功');
    }

    /**
     * @ApiTitle (买入)
     *
     * @ApiParams (name="id", type="numeric", required=true, description="市场挂单ID")
     * @ApiParams (name="safeword", type="string", required=true, description="安全密码")
     *
     * @return string
     * @throws \App\Exceptions\DeveloperException
     * @throws \App\Exceptions\UserException
     */
    protected function buyFromMarket()
    {
        // 验证数据
        $data = $this->validate([
            'id' => 'required|numeric',
            'safeword' => [
                'required', 'string', new UserSafeword()
            ]
        ]);

        // 当前用户
        $user = $this->user();

        // 验证安全密码
        $user->checkSafeword($data['safeword'], __CLASS__, __FUNCTION__);

        // 获取挂单记录
        $market = Market::getById($data['id']);

        // 卖给用户
        $market->buyFrom($user);

        return ___('买入成功');
    }
}
