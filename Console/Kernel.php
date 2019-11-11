<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019/10/29
 * Time: 10:52 上午
 */
namespace SQJ\Modules\C2C\Console;

use Illuminate\Console\Scheduling\Schedule;

class Kernel
{
    public function schedule(Schedule $schedule)
    {
        // 自动流回市场
        $schedule->command('c2c:auto-rollback-market')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(command_log_file('c2c_auto_rollback_market'));

        // 自动确认挂单
        $schedule->command('c2c:auto-confirm-market')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(command_log_file('c2c_auto_confirm_market'));
    }
}
