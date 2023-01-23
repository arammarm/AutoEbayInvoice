<?php


namespace App\Helper;


class eBayApi {

    public $token = 'v^1.1#i^1#r^1#f^0#I^3#p^3#t^Ul4xMF82Ojc3Njk1MzI4NUZGNzhBRkM0RTkzMjNBRjYyOTYxRUJBXzJfMSNFXjI2MA==';

    public function constructOrderArray( $orderXmlArray, $orderArray = array() ) {
        if ( isset( $orderXmlArray->OrderArray->Order ) && ! empty( (array) $orderXmlArray->OrderArray->Order ) ) {
            foreach ( $orderXmlArray->OrderArray->Order as $order ) {
                array_push( $orderArray, (array) $order );
            }
        }

        return $orderArray;
    }

    public function downloadOrders( $pageNo = 1 ) {

//        $dateFrom = date( 'Y-m-d', strtotime( '-7 days' ) );
        $dateFrom = '2023-01-01';
        $dateTo   = date( 'Y-m-d', strtotime( 'now' ) );

        $postData = '<?xml version="1.0" encoding="utf-8"?>
        <GetOrdersRequest xmlns="urn:ebay:apis:eBLBaseComponents">
          <RequesterCredentials>
            <eBayAuthToken>' . $this->token . '</eBayAuthToken>
          </RequesterCredentials>
            <ErrorLanguage>en_US</ErrorLanguage>
            <Pagination>
                <EntriesPerPage>100</EntriesPerPage>
                <PageNumber>' . $pageNo . '</PageNumber>
            </Pagination>
            <WarningLevel>High</WarningLevel>
            <CreateTimeFrom>' . $dateFrom . 'T00:00:00.000Z</CreateTimeFrom>
            <CreateTimeTo>' . $dateTo . 'T23:59:59.000Z</CreateTimeTo>
          <OrderRole>Seller</OrderRole>
          <OrderStatus>All</OrderStatus>
        </GetOrdersRequest>';

        return $this->sendRequest( $postData, 'GetOrders' );
    }


    public function sendRequest( $postData, $action ) {
        $curl = curl_init();
        curl_setopt_array( $curl, array(
            CURLOPT_URL            => 'https://api.ebay.com/ws/api.dll',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $postData,
            CURLOPT_HTTPHEADER     => array(
                'X-EBAY-API-SITEID: 186',
                'X-EBAY-API-COMPATIBILITY-LEVEL: 967',
                'X-EBAY-API-CALL-NAME: ' . $action,
                'Content-Type: application/xml',
            ),
        ) );
        $response = curl_exec( $curl );
        curl_close( $curl );

        return $response;
    }
}
