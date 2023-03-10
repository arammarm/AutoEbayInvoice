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
            $ebayLink       = "https://ebay.es/itm/" . eBayFunctions::getItemId( $order );

            $whatsappEnabled = true;

            if ( $orderedDate->lessThan( Carbon::parse( '2023-01-20' ) ) ) {
                continue;
            }

            if ( $whatsappEnabled ) {

                if ( ! $order->whatsapp_received ) {
                    $whatsapp->saveContact( $order->buyer, $mobileNumber, $order->country, $email );
                    $response = $whatsapp->sendWAMessage( $mobileNumber, $isEnglish ? WhatsappHelper::T_ORDER_RECEIVED : WhatsappHelper::T_ORDER_RECEIVED_ES, [
                        $order->buyer,
                        $order->order_id,
                        $ebayLink
                    ] );
                    $order->update( [
                        'whatsapp_received'      => isset( $response->sent ) && $response->sent == 1 ? 1 : 2,
                        'whatsapp_received_date' => Carbon::now()
                    ] );
                }
                if ( ! $order->whatsapp_shipped && $orderedDate->diffInDays( $now ) > 0 ) {
                    $whatsapp->saveContact( $order->buyer, $mobileNumber, $order->country, $email );
                    $response = $whatsapp->sendWAMessage( $mobileNumber, $isEnglish ? WhatsappHelper::T_ORDER_SHIPPED : WhatsappHelper::T_ORDER_SHIPPED_ES, [
                        $order->buyer,
                        $order->order_id
                    ] );
                    if ( ! isset( $response->error ) ) {
                        $order->update( [
                            'whatsapp_shipped'      => isset( $response->sent ) && $response->sent == 1 ? 1 : 2,
                            'whatsapp_shipped_date' => Carbon::now()
                        ] );
                    }
                }
                if ( ! $order->whatsapp_delivered && $orderedDate->diffInDays( $now ) > 9 ) {
                    $whatsapp->saveContact( $order->buyer, $mobileNumber, $order->country, $email );
                    $response = $whatsapp->sendWAMessage( $mobileNumber, $isEnglish ? WhatsappHelper::T_ORDER_DELIVERED : WhatsappHelper::T_ORDER_DELIVERED_ES, [
                        $order->buyer,
                        $order->order_id,
                    ] );
                    if ( ! isset( $response->error ) ) {
                        $order->update( [
                            'whatsapp_delivered'      => isset( $response->sent ) && $response->sent == 1 ? 1 : 2,
                            'whatsapp_delivered_date' => Carbon::now(),
                            [ $order->buyer ]
                        ] );
                    }
                }
            }

            if ( $order->order_status == 'Completed' && $orderedDate->diffInDays( $now ) > 0 && $order->email_complete == 0 ) {
                $orderController = new OrderController();
                $content         = $isEnglish ? EmailTemplate::where( 'template_name', 'auto_received_order' )->first()->template_content : EmailTemplate::where( 'template_name', 'auto_received_order_es' )->first()->template_content;
                $mailRes         = @$orderController->sendMail( [
                    'to'       => $email,
                    'id'       => $order->id,
                    'template' => $content,
                    'subject'  => "Order Confirmation",
                    'invoice'  => true,
                ], true );

                $order->update( [ 'email_complete' => 1, 'email_complete_date' => Carbon::now() ] );
            }
        }

    }
}
