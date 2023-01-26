<?php

namespace App\Console;

use App\Helper\eBayFunctions;
use App\Http\Controllers\CronController;
use Carbon\Carbon;
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
            try {
                $ebay = new eBayFunctions();
                $ebay->downloadAndUpdateOrder();
                $cron = new CronController();
                $cron->runAlerts();

                file_put_contents( public_path( 'cron_history.log' ), "\nRun at " . Carbon::now()->toString(), FILE_APPEND );
            } catch ( \Exception $exception ) {
                file_put_contents( public_path( 'cron_error_history.log' ), "\nError  " . Carbon::now()->format( 'Y-m-d H:i:s' ) . "  " . $exception->getMessage(), FILE_APPEND );
            }

        } )->everySixHours()->name( 'download-order-and-update' )->withoutOverlapping();

        $schedule->call(function () {
            file_put_contents( public_path( 'cron_history_.log' ), "\nRun at " . Carbon::now()->toString(), FILE_APPEND );
        })->everyMinute();
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
