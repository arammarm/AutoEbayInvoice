<?php


namespace App\Helper;


use App\Models\WhatsappTemplate;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;

class WhatsappHelper {

    private static $accessToken = 'EAAKNNs6NtgEBAAX3ZChoVaeOMfFhB9GpVluwd5Lg0B9wDazSg3wuGl40iJo3mZBRSl5rg94hmnuZAUOQjl118qbZAd3eTUBltlIiO3T2zigLkVSZAm1iZC1MeYVZBGAvlo5j9QviqZCwmVrY0PMQTqvPRIUXZAW5qA2pZAZCEzBxl81oELRKf1J4UsD61abjznXeTXk4A3GZAkSs2AZDZD';
    private static $phoneId = '115414464778478';
    private static $apiVersion = 'v15.0';

    public function sendWAMessage( $toNumber, $type, $isEnglish ) {

        try {
            $content = WhatsappTemplate::where( 'template_name', $isEnglish ? $type : $type . "_es" )->first();
            $response = $this->_sendWAMessage( $toNumber, $content->template_content ?? '' );
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


    private function _sendWAMessage( $toNumber, $content ) {
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . self::$accessToken
        ];

        $body = [
            "messaging_product" => "whatsapp",
            "recipient_type"    => "individual",
            "to"                => $toNumber,
            "type"              => "text",
            "template"          => [ "preview_url" => false, "body" => $content ]
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
