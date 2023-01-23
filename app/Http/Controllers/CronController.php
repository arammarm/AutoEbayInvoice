<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use App\Models\Order;
use App\Models\WhatsappTemplate;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CronController extends Controller {
    public function runAlerts() {
        WhatsappTemplate::requiredTemplate();
        EmailTemplate::requiredTemplate();

        $orders = Order::where( 'ordered_date', Carbon::now()->subDays( 11 ) )->get();


    }
}
