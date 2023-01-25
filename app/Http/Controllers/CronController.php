<?php

namespace App\Http\Controllers;

use App\Helper\eBayFunctions;
use App\Helper\WhatsappHelper;
use App\Models\EmailTemplate;
use App\Models\Order;
use App\Models\WhatsappTemplate;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CronController extends Controller {
    public function runAlerts() {
        EmailTemplate::requiredTemplate();
        $orders   = Order::where( 'ordered_date', '>=', Carbon::now()->subDays( 11 ) )->get();
        $whatsapp = new WhatsappHelper();

        foreach ( $orders as $order ) {
            $invoiceDetails = eBayFunctions::constructInvoiceDetailsArray( $order, json_decode( $order->invoice_details, true ) );
            $orderedDate    = Carbon::createFromDate( $order->ordered_date );
            $now            = Carbon::now();
            $isEnglish      = $order->country == 'ES' ? false : true;
            $orderDetails   = json_decode( $order->order_detail );
            $email          = eBayFunctions::getEmail( $orderDetails );
            $mobileNumber   = eBayFunctions::getMobileNumber( $invoiceDetails, $order->country );

            if ( $orderedDate->lessThan( Carbon::parse( '2023-01-20' ) ) ) {
                continue;
            }


//            if ( ! $order->whatsapp_received ) {
//                $whatsapp->sendWAMessage( $mobileNumber, 'auto_received_order', $isEnglish );
//                $order->update( [ 'whatsapp_received' => 1, 'whatsapp_received_date' => Carbon::now() ] );
//            }
//            if ( ! $order->whatsapp_shipped && $orderedDate->diffInDays( $now ) > 0 ) {
//                $whatsapp->sendWAMessage( $mobileNumber, 'auto_shipped_order', $isEnglish );
//
//                $order->update( [ 'whatsapp_shipped' => 1, 'whatsapp_shipped_date' => Carbon::now() ] );
//            }
//            if ( ! $order->whatsapp_delivered && $orderedDate->diffInDays( $now ) > 9 ) {
//                $whatsapp->sendWAMessage( $mobileNumber, 'auto_delivered_order', $isEnglish );
//                $order->update( [ 'whatsapp_delivered' => 1, 'whatsapp_delivered_date' => Carbon::now() ] );
//            }

            if ( $order->order_status == 'Completed' && $orderedDate->diffInDays( $now ) > 0 && $order->email_complete == 0 ) {
                $orderController = new OrderController();
                $content         = $isEnglish ? EmailTemplate::where( 'template_name', 'auto_received_order' )->first()->template_content : EmailTemplate::where( 'template_name', 'auto_received_order_es' )->first()->template_content;
                @$orderController->sendMail( [ // TODO: uncomment
                    'to'       => $email,
                    'id'       => $order->id,
                    'template' => $content,
                    'subject'  => "Order Confirmation",
                    'invoice'  => true,
                ] );

                $order->update( [ 'email_complete' => 1, 'email_complete_date' => Carbon::now() ] );
            }
        }

    }
}
