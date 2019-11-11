<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019/10/24
 * Time: 10:12 上午
 */
namespace SQJ\Modules\C2C\Http\Clients\Portal\v1;

use App\Exceptions\DeveloperException;
use App\Exceptions\UserException;
use App\Http\Controllers\Api\ApiModule;
use SQJ\Modules\C2C\Models\PaymentAccount;
use App\Rules\ImageUrl;
use App\Rules\UserSafeword;
use Illuminate\Validation\Rule;

/**
 * @ApiSector (收款账号)
 *
 * Class PaymentModule
 * @package SQJ\Modules\C2C\Http\Clients\Portal\v1
 */
class PaymentModule extends ApiModule
{

    /**
     * 接口编码列表
     *
     * @return mixed
     */
    protected function interfaceList()
    {
        return [
            // 列表
            '1000' => 'getList',
            // 添加
            '1001' => 'add',
            // 详情
            '1002' => 'detail',
            // 修改
            '1003' => 'edit',
            // 删除付款账号
            '1004' => 'remove',
            // 列表
            '2000' => 'getMap'
        ];
    }

    /**
     * @ApiTitle (账号列表)
     *
     * @ApiParams (name="page", type="number", required=true, description="最新一条数据的ID。初次请求时为0，以后请求用返回值。", sample="0")
     * @ApiParams (name="lastId", type="number", required=true, description="当前页页码", sample="1")
     *
     * @ApiReturnParams (name="lastId", type="number", required=true, description="最新一条数据的ID")
     * @ApiReturnParams (name="total", type="number", required=true, description="数据总量")
     * @ApiReturnParams (name="perPage", type="number", required=true, description="每页数据量")
     * @ApiReturnParams (name="currentPage", type="number", required=true, description="当前页码")
     * @ApiReturnParams (name="lastPage", type="number", required=true, description="尾页页码")
     * @ApiReturnParams (name="list", type="array[object]", required=true, description="数据列表")
     *
     * @ApiReturnParams (name="id", group="list", type="number", required=true, description="账号ID")
     * @ApiReturnParams (name="type", group="list", type="number", required=true, description="账号类型。0：银行卡；1：支付宝；2：微信")
     * @ApiReturnParams (name="account", group="list", type="string", required=true, description="账号")
     * @ApiReturnParams (name="realName", group="list", type="string", required=true, description="真实姓名")
     * @ApiReturnParams (name="bankName", group="list", type="string", required=true, description="开户行。仅当type为银行卡时有值")
     * @ApiReturnParams (name="bankAddress", group="list", type="string", required=true, description="开户行支行。仅当type为银行卡时有值")
     * @ApiReturnParams (name="qrCode", group="list", type="string", required=true, description="收款二维码。仅当type为支付宝或微信时有值")
     *
     * @return array
     * @throws DeveloperException
     */
    protected function getList()
    {
        // 当前用户
        $user = $this->user();

        return $this->pageList(PaymentAccount::class, [
            'userId' => $user['id']
        ]);
    }

    /**
     * @ApiTitle (账号列表)
     *
     * @ApiReturnParams (name="bankCard", type="object", required=true, description="银行卡，未绑定时返回空对象。")
     * @ApiReturnParams (name="alipay", type="object", required=true, description="支付宝，未绑定时返回空对象。")
     * @ApiReturnParams (name="wechat", type="object", required=true, description="微信，未绑定时返回空对象。")
     *
     * @ApiReturnParams (name="id", group="bankCard", type="number", required=true, description="账号ID")
     * @ApiReturnParams (name="type", group="bankCard", type="number", required=true, description="账号类型。0：银行卡；1：支付宝；2：微信")
     * @ApiReturnParams (name="account", group="bankCard", type="string", required=true, description="账号")
     * @ApiReturnParams (name="realName", group="bankCard", type="string", required=true, description="真实姓名")
     * @ApiReturnParams (name="bankName", group="bankCard", type="string", required=true, description="开户行")
     * @ApiReturnParams (name="bankAddress", group="bankCard", type="string", required=true, description="开户行支行")
     *
     * @ApiReturnParams (name="id", group="alipay", type="number", required=true, description="账号ID")
     * @ApiReturnParams (name="type", group="alipay", type="number", required=true, description="账号类型。0：银行卡；1：支付宝；2：微信")
     * @ApiReturnParams (name="account", group="alipay", type="string", required=true, description="账号")
     * @ApiReturnParams (name="realName", group="alipay", type="string", required=true, description="真实姓名")
     * @ApiReturnParams (name="qrCode", group="alipay", type="string", required=true, description="收款二维码")
     *
     * @ApiReturnParams (name="id", group="wechat", type="number", required=true, description="账号ID")
     * @ApiReturnParams (name="type", group="wechat", type="number", required=true, description="账号类型。0：银行卡；1：支付宝；2：微信")
     * @ApiReturnParams (name="account", group="wechat", type="string", required=true, description="账号")
     * @ApiReturnParams (name="realName", group="wechat", type="string", required=true, description="真实姓名")
     * @ApiReturnParams (name="qrCode", group="wechat", type="string", required=true, description="收款二维码")
     *
     * @return array
     * @throws DeveloperException
     */
    protected function getMap()
    {
        // 当前用户
        $user = $this->user();

        return [
            'bankCard' => PaymentAccount::firstByType($user, PaymentAccount::TYPE_BANK_CARD),
            'alipay' => PaymentAccount::firstByType($user, PaymentAccount::TYPE_ALIPAY),
            'wechat' => PaymentAccount::firstByType($user, PaymentAccount::TYPE_WECHAT)
        ];
    }

    /**
     * @ApiTitle (添加账号)
     *
     * @ApiParams (name="type", type="number", required=true, description="账号类型。0：银行卡；1：支付宝；2：微信")
     * @ApiParams (name="account", type="string", required=true, description="收款账号")
     * @ApiParams (name="realName", type="string", required=true, description="真实姓名")
     * @ApiParams (name="bankAddress", type="string", required=false, description="开户行支行。仅当type为银行卡时有效")
     * @ApiParams (name="qrCode", type="string", required=false, description="收款码地址。仅当type为微信和支付宝时有效")
     * @ApiParams (name="safeword", type="string", required=true, description="安全密码")
     *
     * @return string
     * @throws DeveloperException
     * @throws UserException
     */
    protected function add()
    {
        // 验证数据
        $data = $this->validateData();

        // 当前用户
        $user = $this->user();

        // 添加账号
        PaymentAccount::insert($user, $data);

        return ___('添加成功');
    }

    /**
     * @ApiTitle (账号详情)
     *
     * @ApiReturnParams (name="id", type="string", required=true, description="收款账号ID")
     * @ApiReturnParams (name="type", type="number", required=true, description="账号类型。0：银行卡；1：支付宝；2：微信")
     * @ApiReturnParams (name="account", type="string", required=true, description="收款账号")
     * @ApiReturnParams (name="realName", type="string", required=true, description="真实姓名")
     * @ApiReturnParams (name="bankName", type="string", required=true, description="开户行名称。仅当type为银行卡时有效")
     * @ApiReturnParams (name="bankAddress", type="string", required=false, description="开户行支行。仅当type为银行卡时有效")
     * @ApiReturnParams (name="qrCode", type="string", required=false, description="收款码地址。仅当type为微信和支付宝时有效")
     *
     * @return array
     * @throws DeveloperException
     * @throws UserException
     */
    protected function detail()
    {
        // 验证数据
        $data = $this->validate([
            'id' => 'required|numeric'
        ]);

        // 获取账号
        $account = PaymentAccount::getById($data['id']);

        // 验证账号
        $this->validateOwner($account);

        return [
            'id' => $account['id'],
            'type' => $account['type'],
            'account' => $account['account'],
            'realName' => $account['realName'],
            'bankName' => $account['bankName'],
            'bankAddress' => $account['bankAddress'],
            'qrCode' => $account['qrCode']
        ];
    }

    /**
     * @ApiTitle (修改账号)
     *
     * @ApiParams (name="id", type="string", required=true, description="收款账号ID")
     * @ApiParams (name="type", type="number", required=true, description="账号类型。0：银行卡；1：支付宝；2：微信")
     * @ApiParams (name="account", type="string", required=true, description="收款账号")
     * @ApiParams (name="realName", type="string", required=true, description="真实姓名")
     * @ApiParams (name="bankAddress", type="string", required=false, description="开户行支行。仅当type为银行卡时有效")
     * @ApiParams (name="qrCode", type="string", required=false, description="收款码地址。仅当type为微信和支付宝时有效")
     * @ApiParams (name="safeword", type="string", required=true, description="安全密码")
     *
     * @return string
     * @throws DeveloperException
     * @throws UserException
     */
    protected function edit()
    {
        // 验证数据
        $data = $this->validateData(true);

        // 获取账号
        $account = PaymentAccount::getById($data['id']);

        // 验证账号
        $this->validateOwner($account);

        // 修改账号
        $account->change($data);

        return ___('修改成功');
    }

    /**
     * 验证付款账号信息
     *
     * @param bool $isChanging 是否正在修改
     * @return array
     * @throws DeveloperException
     * @throws UserException
     */
    private function validateData($isChanging = false)
    {
        // 规则
        $rules = [
            'type' => [
                'required', 'numeric',
                Rule::in([PaymentAccount::TYPE_BANK_CARD, PaymentAccount::TYPE_ALIPAY, PaymentAccount::TYPE_WECHAT])
            ],
            'account' => [
                'required', 'string'
            ],
            'realName' => 'required|string|min:2'
        ];

        // 启用安全密码验证
        if (config('c2c.safeword.payment'))
        {
            $rules['safeword'] = ['required', 'string', new UserSafeword()];
        }

        if ($isChanging)
        {
            $rules['id'] = 'required|numeric';
        }

        $data = $this->validate($rules, [], function ($validator) {
            // 开户行支行
            $validator->sometimes('bankAddress', 'nullable', function ($input) {
                return $input->type == PaymentAccount::TYPE_BANK_CARD;
            });
            // 付款二维码
            $validator->sometimes('qrCode', ['required', 'string', new ImageUrl()], function ($input) {
                return $input->type != PaymentAccount::TYPE_BANK_CARD;
            });
        });

        // 判断银行卡号
        if ($data['type'] == PaymentAccount::TYPE_BANK_CARD && !validate_bank_card($data['account']))
        {
            throw_user(___('请输入合法的银行卡号'));
        }

        // 当前用户
        $user = $this->user();

        // 验证安全密码
        if (config('c2c.safeword.payment'))
        {
            $user->checkSafeword($data['safeword'], __CLASS__, __FUNCTION__);
        }

        return $data;
    }

    /**
     * @ApiTitle (删除付款账号)
     *
     * @ApiParams (name="id", type="string", required=true, description="收款账号ID")
     * @ApiParams (name="safeword", type="string", required=true, description="安全密码")
     *
     * @return string
     * @throws DeveloperException
     * @throws UserException
     */
    protected function remove()
    {
        // 验证规则
        $rules['id'] = 'required|numeric';

        // 开启安全密码
        if (config('c2c.safeword.payment'))
        {
            $rules['safeword'] = ['required', 'string', new UserSafeword()];
        }

        // 验证数据
        $data = $this->validate($rules);

        // 当前用户
        $user = $this->user();

        // 验证安全密码
        if (config('c2c.safeword.payment'))
        {
            $user->checkSafeword($data['safeword'], __CLASS__, __FUNCTION__);
        }

        // 获取账号
        $account = PaymentAccount::getById($data['id']);

        // 验证账号
        $this->validateOwner($account);

        // 执行删除
        $account->delete();

        return ___('删除成功');
    }

    /**
     * 验证地址的拥有者
     *
     * @param PaymentAccount $account
     * @throws DeveloperException
     */
    private function validateOwner(PaymentAccount $account)
    {
        // 当前用户
        $user = $this->user();

        if ($user['id'] != $account['userId'])
        {
            throw_developer(___('当前用户与收款账号拥有者不匹配'));
        }
    }
}
