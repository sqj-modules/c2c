<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateC2cMarketLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('c2c_market_logs', function (Blueprint $table) {
            $table->bigIncrements('id');

            // 挂单ID
            $table->unsignedBigInteger('market_id')
                ->default(0)
                ->comment('挂单ID')
                ->index();

            // 操作用户类型
            $table->string('operable_type')
                ->default('')
                ->comment('操作对象类型')
                ->index();

            // 操作用户ID
            $table->unsignedBigInteger('operable_id')
                ->default(0)
                ->comment('操作对象ID')
                ->index();

            // 操作类型
            $table->tinyInteger('type')
                ->default(0)
                ->comment('操作类型。1：挂卖；2：挂买；3：付款；4：确认；-3：申诉未付款；-4：申诉未确认；'
                    . '30：驳回未付款申诉；31：通过未付款申诉；40：驳回未确认申诉；41：通过未确认申诉。 ')
                ->index();

            // 软删除
            $table->softDeletes();
            $table->timestamps();

            // 添加挂单外键约束
            $table->foreign('market_id')
                ->references('id')
                ->on('c2c_markets')
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
        Schema::dropIfExists('c2c_market_logs');
    }
}
