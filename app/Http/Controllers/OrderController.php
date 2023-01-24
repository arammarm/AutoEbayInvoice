<?php

namespace App\Http\Controllers;

use App\Helper\eBayFunctions;
use App\Models\EmailTemplate;
use App\Models\Order;
use App\Models\WhatsappTemplate;
use Carbon\Carbon;
use Illuminate\Http\Request;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class OrderController extends Controller {
    public function index() {
        $data   = [];
        $orders = Order::orderBy( 'ordered_date', 'desc' )->get();

        $data['orders'] = [];
        foreach ( $orders as $order ) {
            $data['orders'][] = $this->getDetailSummarizedDetail( $order );
        }
        $data['whatsapp_templates'] = WhatsappTemplate::where( 'active', 1 )->get();
        $data['email_templates']    = EmailTemplate::where( 'active', 1 )->get();
        $data['last_downloaded']    = Carbon::parse( $orders[0]['last_downloaded'] )->diffForHumans() ?? null;

        return view( 'orders.orders', $data );
    }

    public function getById( $id = 0 ) {
        $order = Order::where( 'id', $id )->first();

        if ( $order == null ) {
            return response()->json( [ 'error' => 1, 'message' => 'Requested data did not exists' ] );
        }

        return response()->json( [ 'error' => 0, 'data' => $this->getDetailSummarizedDetail( $order ) ] );
    }

    public function updateOrderInvoice() {
        $id      = request()->input( 'id' );
        $order   = Order::select( 'id' )->where( 'id', $id )->first();
        $error   = 1;
        $message = 'Could not find the order';
        if ( $order != null ) {
            Order::where( 'id', $id )->update( [
                'invoice_details' => json_encode( request()->input() ),
                'ref'             => request()->input( 'numero' ),
            ] );
            $error   = 0;
            $message = 'Invoice detail successfully updated';
        }

        return redirect()->back()->with( [ 'error' => $error, 'message' => $message ] );
    }

    public function sendMail( $requestArray = [] ) {

        if ( ! empty( $requestArray ) ) {
            $mailTo         = $requestArray['to'];
            $rowId          = $requestArray['id'];
            $template       = $requestArray['template'];
            $subject        = $requestArray['subject'];
            $includeInvoice = $requestArray['invoice'];
        } else {
            $mailTo         = request()->input( 'mail_to' );
            $rowId          = request()->input( 'row_id' );
            $template       = request()->input( 'template' );
            $subject        = request()->input( 'subject' );
            $includeInvoice = request()->input( 'include_invoice' );
        }


        $filePath = eBayFunctions::getInvoice( $rowId, 'F' );
        $error    = 0;
        $mail     = new PHPMailer( true );
        try {
            //Server settings
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
            $mail->SMTPDebug = false;                      //Enable verbose debug output
            $mail->isSMTP();                                            //Send using SMTP

            $mail->Host       = env( 'MAIL_HOST' );                     //Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
            $mail->Username   = env( 'MAIL_USERNAME' );                     //SMTP username
            $mail->Password   = env( 'MAIL_PASSWORD' );                               //SMTP password
            $mail->SMTPSecure = 'SSL';            //Enable implicit TLS encryption
            $mail->Port       = env( 'MAIL_PORT' );                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

            //Recipients
            $mail->setFrom( env( 'MAIL_FROM_ADDRESS' ), env( 'MAIL_FROM_NAME' ) );
            $mail->addAddress( $mailTo );     //Add a recipient
            $mail->addReplyTo( env( 'MAIL_FROM_ADDRESS' ), env( 'MAIL_FROM_NAME' ) );
            //Attachments
            if ( isset( $includeInvoice ) ) {
                $mail->addAttachment( $filePath );         //Add attachments
            }
            //Content
            $mail->isHTML( true );                                  //Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body    = $template;
            $mail->AltBody = $template;
            $mail->send();
            $message = 'Email has been sent';
        } catch ( Exception $e ) {
            $error   = 1;
            $message = "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
        unlink( $filePath );

        return redirect()->back()->with( [ 'error' => $error, 'message' => $message ] );
    }

    public function downloadInvoice( $id ) {
        $filePath = eBayFunctions::getInvoice( $id, 'F' );
        $order    = Order::where( 'id', $id )->first();

        $headers = array(
            'Content-Type: application/pdf',
        );

        return response()->download( $filePath, $order->order_id . '.pdf', $headers );
    }

    public function editOrderContent( $id ) {
        $data  = [];
        $order = Order::where( 'id', $id )->first();
        if ( $order ) {
            $invoiceDetail           = eBayFunctions::constructInvoiceDetailsArray( $order, json_decode( $order->invoice_details, true ) );
            $data['invoice_details'] = $invoiceDetail;
            $data['order']           = $order;

            return view( 'orders.edit_order_model_content', $data );
        }

        return "Could not find the order";
    }

    private function getDetailSummarizedDetail( $order ) {
        $invoiceDetails  = eBayFunctions::constructInvoiceDetailsArray( $order, json_decode( $order->invoice_details, true ) );
        $products        = eBayFunctions::getProducts( $order->order_detail );
        $totalOrderedQty = eBayFunctions::getTotalOrderedQty( $order->order_detail );

        $tempD['ref']             = $order->ref;
        $tempD['purchase_number'] = eBayFunctions::getSalesRecordNumber( $order->order_detail );
        $tempD['ordered_date']    = eBayFunctions::getDisplayDate( $order->ordered_date );
        $tempD['order_id']        = $order->order_id;
        $tempD['buyer']           = $order->buyer;
        $tempD['country']         = $order->country;

        $tAddress = $invoiceDetails['buyer_name'] . "<br>" . $invoiceDetails['address'] . ",<br>";
        if ( ! empty( $invoiceDetails['address_2'] ) ) {
            $tAddress .= $invoiceDetails['address_2'] . "<br>";
        }

        $tAddress         .= $invoiceDetails['city'] . ", " . $invoiceDetails['state'] . "<br>" . $invoiceDetails['zip_code'] . " " . $invoiceDetails['country'];
        $tempD['address'] = $tAddress;

        $tempD['products']        = implode( "<br>", $products );
        $tempD['total']           = $order->total;
        $tempD['qty']             = $totalOrderedQty;
        $tempD['order_status']    = $order->order_status;
        $tempD['invoice_details'] = $invoiceDetails;
        $tempD['order_detail']    = json_decode( $order->order_detail );
        $tempD['wa']['received']  = $order->whatsapp_received;
        $tempD['wa']['shipped']   = $order->whatsapp_shipped;
        $tempD['wa']['delivered'] = $order->whatsapp_delivered;
        $tempD['ea']['received']  = $order->email_complete;
        $tempD['id']              = $order->id;

        return $tempD;
    }

    public function downloadOrder() {
        $order = new eBayFunctions();
        $order->downloadAndUpdateOrder();

        return response()->json( [ 'error' => 0, 'Orders has been updated' ] );
    }


}
