<?php


namespace App\Helper;


use App\Models\WhatsappTemplate;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;

class WhatsappHelper {

    private static $phoneId = '115414464778478';
    private static $apiVersion = 'v15.0';

    const T_ORDER_RECEIVED = 'ebay_invoice_received';
    const T_ORDER_SHIPPED = 'ebay_invoice_shipped';
    const T_ORDER_DELIVERED = 'ebay_invoice_delivered';

    public function sendWAMessage( $toNumber, $template, $isEnglish ) {

        try {
            $response = $this->_sendWAMessage( $toNumber, $template, $isEnglish );

            print_r( $response );
            die();

            return ( json_decode( $response ) );
        } catch ( \Exception $exception ) {
            return [ 'error' => 1, 'message' => "Could not send message", 'debug_message' => $exception->getMessage() ];
        }
    }

    public static function dateFormat( $date = null ) {
        if ( $date == null ) {
            $date = Carbon::now();
        }

        return Carbon::parse( $date )->format( 'd M Y' );
    }


    private function _sendWAMessage( $toNumber, $template, $isEnglish ) {
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . env( 'WA_TOKEN' )
        ];
        $lang    = 'en_US';
//        $lang    = 'en_UK';
        if ( ! $isEnglish ) {
            $lang = 'es_ES';
        }
        $body = [
            "messaging_product" => "whatsapp",
            "recipient_type"    => "individual",
            "to"                => $toNumber,
            "type"              => "template",
            "template"          => [ "name" => $template, "language" => [ "code" => $lang ] ]

        ];

        return $this->sendCurl( 'POST', "https://graph.facebook.com/" . self::$apiVersion . "/" . self::$phoneId . "/messages", $headers, json_encode( $body ) );
    }


    private function sendCurl( $method, $url, $headers, $body ) {
        $curl = curl_init();
        curl_setopt_array( $curl, array(
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
        ) );


        $response = curl_exec( $curl );

        curl_close( $curl );

        return $response;
    }

}
