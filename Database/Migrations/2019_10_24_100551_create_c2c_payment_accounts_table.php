<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateC2cPaymentAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('c2c_payment_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');

            // 用户
            $table->unsignedBigInteger('user_id')
                ->default(0)
                ->comment('用户 ID')
                ->index();

            // 账号类型
            $table->integer('type')
                ->default(0)
                ->comment('账号类型。0：银行卡；1：支付宝；2：微信')
                ->index();

            // 账号
            $table->string('account', 32)
                ->default('')
                ->comment('账号')
                ->index();

            // 真实姓名
            $table->string('real_name', 32)
                ->default('')
                ->comment('真实姓名');

            // 银行名称
            $table->string('bank_name')
                ->default('')
                ->comment('银行名称');

            // 银行地址
            $table->string('bank_address')
                ->default('')
                ->comment('银行地址');

            // 收款二维码
            $table->text('qr_code')
                ->nullable()
                ->comment('收款二维码');

            // 是否启用
            $table->tinyInteger('is_enabled')
                ->default(1)
                ->comment('是否启用。1：启用；0：禁用')
                ->index();

            // 软删除
            $table->softDeletes();

            $table->timestamps();

            // 外键约束
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('c2c_payment_accounts');
    }
}
