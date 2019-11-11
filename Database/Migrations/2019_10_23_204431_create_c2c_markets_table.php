<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateC2cMarketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('c2c_markets', function (Blueprint $table) {
            $table->bigIncrements('id');

            // 交易单号
            $table->string('order_no', 32)
                ->default('')
                ->comment('交易单号');

            // 挂卖用户
            $table->unsignedBigInteger('seller_id')
                ->default(0)
                ->comment('挂卖用户的ID')
                ->index();

            // 挂买用户
            $table->unsignedBigInteger('buyer_id')
                ->default(0)
                ->comment('挂买用户的ID')
                ->index();

            // 挂卖类型
            $table->string('credit_type', 32)
                ->default('')
                ->comment('交易钱包类型')
                ->index();

            // 总长度
            $total = config('app.user_credit_total');
            // 精度
            $place = config('app.user_credit_place');

            // 挂卖数量
            $table->decimal('amount', $total, $place)
                ->default(0)
                ->comment('挂卖数量');

            // 挂卖单价
            $table->decimal('unit_price', $total, $place)
                ->default(0)
                ->comment('挂卖单价');

            // 挂卖总价
            $table->decimal('price', $total, $place)
                ->default(0)
                ->comment('挂卖总价');

            // 挂单状态
            $table->tinyInteger('status')
                ->default(0)
                ->comment('挂单状态。0：匹配中；1：待付款；2：待确认；3：已完成；-1：申诉未付款；-2：申诉未确认。')
                ->index();

            // 卖出时间
            $table->timestamp('sold_at')
                ->nullable()
                ->comment('卖出时间');

            // 买入时间
            $table->timestamp('bought_at')
                ->nullable()
                ->comment('买入时间');

            // 付款时间
            $table->timestamp('paid_at')
                ->nullable()
                ->comment('买家付款时间');

            // 付款凭证
            $table->string('voucher')
                ->default('')
                ->comment('付款凭证');

            // 确认时间
            $table->timestamp('confirmed_at')
                ->nullable()
                ->comment('卖家确认时间');

            // 申诉时间
            $table->timestamp('appealed_at')
                ->nullable()
                ->comment('申诉时间');

            // 软删除
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('c2c_markets');
    }
}
