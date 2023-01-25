<?php

namespace App\Console;

use App\Helper\eBayFunctions;
use App\Http\Controllers\CronController;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel {
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     *
     * @return void
     */
    protected function schedule( Schedule $schedule ) {

        $schedule->call( function () {
            $ebay = new eBayFunctions();
            $ebay->downloadAndUpdateOrder();
            $cron = new CronController();
            $cron->runAlerts();
        } )->twiceDaily()->name('download-order-and-update')->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands() {
        $this->load( __DIR__ . '/Commands' );

        require base_path( 'routes/console.php' );
    }
}
