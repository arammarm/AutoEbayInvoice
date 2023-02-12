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
 
    const T_ORDER_RECEIVED = 'd61eb143-6048-403e-855e-3b330050b9f3';
    const T_ORDER_SHIPPED = '3058bd52-f5b7-48e5-80a6-0e00a685846c';
    const T_ORDER_DELIVERED = 'b061aa06-dd66-4509-b627-2791a1d0c5f6';
    const T_ORDER_RECEIVED_ES = 'a502d4de-7e1c-4cb2-991c-228cf41d483f';
    const T_ORDER_SHIPPED_ES = '86c41ed6-611c-4963-bd75-7915c0d420e9';
    const T_ORDER_DELIVERED_ES = '90230d77-9116-4a43-9a1a-cae062ad1baf';

    public function sendWAMessage( $toNumber, $template, $params = [] ) {
        try {
            $response = $this->_sendWAMessage( $toNumber, $template, $params );
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

    private function _sendWAMessage( $toNumber, $template, $paramArr = [] ) {
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . env( 'WA_TOKEN_WASAPI' )
        ];

        $paramArr = array_map( function ( $value, $key ) {
            $_key = $key + 1;

            return [ 'text' => "{{{$_key}}}", 'val' => $value ];
        }, $paramArr, array_keys( $paramArr ) );


        $body = [
            "recipients"   => $toNumber,
            "template_id"  => $template,
            "contact_type" => 'phone',
            "body_vars"    => $paramArr,
        ];

        return $this->sendCurl( 'POST', "https://api.wasapi.io/prod/api/v1/whatsapp-messages/send-template", $headers, json_encode( $body ) );
    }

    public function saveContact( $name, $phoneNumber, $countryCode, $email ) {
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . env( 'WA_TOKEN_WASAPI' )
        ];

        $body = [
            "first_name"   => $name,
            "last_name"    => '',
            "email"        => $email,
            "country_code" => $countryCode,
            "phone"        => $phoneNumber,
            "notes"        => 'eBay Customer',
            "labels"       => [ 4606 ],
        ];

        return $this->sendCurl( 'POST', "https://api.wasapi.io/prod/api/v1/contacts", $headers, json_encode( $body ) );
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
