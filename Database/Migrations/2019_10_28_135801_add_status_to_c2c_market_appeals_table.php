<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToC2cMarketAppealsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('c2c_market_appeals', function (Blueprint $table) {
            // 申诉状态
            $table->tinyInteger('status')
                ->default(0)
                ->comment('申诉状态。0：申诉中；1：通过申诉；-1：驳回申诉')
                ->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('c2c_market_appeals', function (Blueprint $table) {
            //
            $table->dropColumn(['status']);
        });
    }
}
