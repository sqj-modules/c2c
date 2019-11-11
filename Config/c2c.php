<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019/10/24
 * Time: 10:37 上午
 */
use App\Models\UserCredit;

return [

    /**
     * 需要启用安全密码的操作
     */
    'safeword' => [

        /**
         * 收款账号
         */
        'payment' => env('C2C_DEV_SAFEWORD_PAYMENT', true)
    ],

    /**
     * 启用的挂卖钱包
     */
    'enabled_credits' => explode(',', env('C2C_ENABLED_CREDITS', UserCredit::W_BALANCE))
];
