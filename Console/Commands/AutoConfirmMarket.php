<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019/10/29
 * Time: 11:02 上午
 */
namespace SQJ\Modules\C2C\Console\Commands;

use SQJ\Modules\C2C\Models\Market;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoConfirmMarket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'c2c:auto-confirm-market';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '当挂单付款后超出确认时限后，自动确认挂单，并冻结卖家';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 事件处理
     */
    public function handle()
    {
        $this->info('【' . now_datetime() . "】：*************************开始【自动确认过期未确认的挂单】开始*******************");

        // 计数器
        $counter = 0;

        // 进行处理
        Market::autoConfirm(function (Market $market) use (&$counter) {

            // 开启事务
            DB::beginTransaction();

            try
            {
                // 确认挂单
                $market->confirm();

                // 冻结卖家
                $market['seller']->freeze();

                // 输出信息
                $this->info('【' . now_datetime() . "】：挂单【{$market['orderNo']}】自动确认成功");

                // 自动计数
                ++$counter;

                DB::commit();
            }
            catch (\Exception $exception)
            {
                // 输出错误信息
                $this->error('【' . now_datetime() . "】：挂单【{$market['orderNo']}】自动确认失败。原因：" . $exception->getMessage());

                DB::rollBack();
            }
        });

        $this->info('【' . now_datetime() . "】：*************************共计{$counter}挂单自动确认成功*******************");

        $this->info('【' . now_datetime() . "】：*************************结束【自动确认过期未确认的挂单】结束*******************");
    }
}
