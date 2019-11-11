<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateC2cMarketAppealsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('c2c_market_appeals', function (Blueprint $table) {
            $table->bigIncrements('id');

            // 挂单ID
            $table->unsignedBigInteger('market_id')
                ->default(0)
                ->comment('挂单市场ID')
                ->index();

            // 申诉用户
            $table->unsignedBigInteger('user_id')
                ->default(0)
                ->comment('申诉人ID')
                ->index();

            // 申诉类型
            $table->tinyInteger('type')
                ->default(0)
                ->comment('申诉类型。1：买家未付款；2：卖家未确认')
                ->index();

            // 申诉内容
            $table->string('content')
                ->default('')
                ->comment('申诉内容');

            // 申诉图片
            $table->json('images')
                ->comment('申诉的图片内容');

            // 通过时间
            $table->timestamp('accepted_at')
                ->nullable()
                ->comment('申诉通过时间');

            // 驳回时间
            $table->timestamp('rejected_at')
                ->nullable()
                ->comment('驳回时间');

            // 驳回原因
            $table->string('rejected_reason')
                ->nullable()
                ->comment('驳回原因');

            // 操作管理员
            $table->unsignedBigInteger('admin_id')
                ->default(0)
                ->comment('操作管理员ID')
                ->index();

            // 软删除
            $table->softDeletes();
            $table->timestamps();

            // 挂单外键约束
            $table->foreign('market_id')
                ->references('id')
                ->on('c2c_markets')
                ->onDelete('cascade');

            // 申诉人外键约束
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
        Schema::dropIfExists('c2c_market_appeals');
    }
}
