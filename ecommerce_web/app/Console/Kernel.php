<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Chạy báo cáo doanh thu hàng ngày lúc 8:00 AM
        $schedule->command('report:daily-revenue')
            ->dailyAt('08:00')
            ->timezone('Asia/Ho_Chi_Minh')
            ->description('Gửi báo cáo doanh thu hàng ngày cho admin');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}