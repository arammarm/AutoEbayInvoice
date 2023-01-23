<?php


namespace App\Helper;

use App\Models\Order;
use App\Models\VatConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Mpdf\Mpdf;

class eBayFunctions {
    public function downloadAndUpdateOrder() {
        $ebay          = new eBayApi();
        $orderDetails  = $ebay->downloadOrders();
        $orderXmlArray = @simplexml_load_string( $orderDetails );
        $orderArray    = [];
        if ( $orderXmlArray ) {
            if ( isset( $orderXmlArray->Ack ) && ( (string) $orderXmlArray->Ack == 'Success' || (string) $orderXmlArray->Ack == 'Warning' ) ) {
                $totalNumberOfPages = (string) $orderXmlArray->PaginationResult->TotalNumberOfPages;
                $orderArray         = $ebay->constructOrderArray( $orderXmlArray );
                if ( $totalNumberOfPages > 1 ) {
                    for ( $i = 2; $i <= $totalNumberOfPages; $i ++ ) {
                        $orderDetails  = $ebay->downloadOrders( $i );
                        $orderXmlArray = @simplexml_load_string( $orderDetails );
                        if ( $orderXmlArray ) {
                            if ( isset( $orderXmlArray->Ack ) && ( (string) $orderXmlArray->Ack == 'Success' || (string) $orderXmlArray->Ack == 'Warning' ) ) {
                                $orderArray = $ebay->constructOrderArray( $orderXmlArray, $orderArray );
                            }
                        }
                    }
                }
            }
        }

        if ( ! empty( $orderArray ) ) {
            foreach ( $orderArray as $order ) {
                $invoiceCount = Order::select( 'id' )->where( 'country', (string) $order['ShippingAddress']->Country )->count();
                $ref          = "EBAY " . (string) $order['ShippingAddress']->Country . " " . sprintf( "%05d", ( $invoiceCount + 1 ) );
                $purchaseNumber = eBayFunctions::getSalesRecordNumber( json_encode( $order ) );

                $orderDetails = Order::where( 'order_id', $order['OrderID'] )->first();
                if ( $orderDetails == null ) {
                    $insert = Order::create( [
                        'order_id'         => $order['OrderID'],
                        'order_status'     => $order['OrderStatus'],
                        'total'            => $order['Total'],
                        'ordered_date'     => self::CDate( strtotime( $order['CreatedTime'] ) ),
                        'buyer'            => $order['BuyerUserID'],
                        'order_detail'     => json_encode( $order ),
                        'invoice_details'  => json_encode( [] ),
                        'ref'              => $ref,
                        'country'          => (string) $order['ShippingAddress']->Country,
                        'last_downloaded'  => self::CDate( time() ),
                        'purchase_history' => $purchaseNumber,
                    ] );
                } else {
                    $id     = $orderDetails->id;
                    $update = Order::where( 'id', $id )->update( [
                        'order_status'     => $order['OrderStatus'],
                        'total'            => $order['Total'],
                        'ordered_date'     => self::CDate( strtotime( $order['CreatedTime'] ) ),
                        'buyer'            => $order['BuyerUserID'],
                        'order_detail'     => json_encode( $order ),
                        'country'          => (string) $order['ShippingAddress']->Country,
                        'last_downloaded'  => self::CDate( time() ),
                        'purchase_history' => $purchaseNumber,
                    ] );
                }
            }
        }
    }

    private static function CDate( $timestamp ) {
        return date( 'Y-m-d H:i:s', $timestamp );
    }

    static function getProducts( $orderDetails ) {
        $productNames = [];
        if ( ! empty( $orderDetails ) && is_array( json_decode( $orderDetails, true ) ) ) {
            $orderDetailsArray = json_decode( $orderDetails, true );
            if ( isset( $orderDetailsArray['TransactionArray']['Transaction']['Buyer'] ) ) {
                array_push( $productNames, $orderDetailsArray['TransactionArray']['Transaction']['Item']['Title'] );
            } else if ( isset( $orderDetailsArray['TransactionArray']['Transaction'][0]['Buyer'] ) ) {
                foreach ( $orderDetailsArray['TransactionArray']['Transaction'] as $transaction ) {
                    array_push( $productNames, $transaction['Item']['Title'] );
                }
            }
        }

        return $productNames;
    }


    static function getTotalOrderedQty( $orderDetails ) {
        $totalQty = 0;
        if ( ! empty( $orderDetails ) && is_array( json_decode( $orderDetails, true ) ) ) {
            $orderDetailsArray = json_decode( $orderDetails, true );
            if ( isset( $orderDetailsArray['TransactionArray']['Transaction']['Buyer'] ) ) {
                $totalQty = $orderDetailsArray['TransactionArray']['Transaction']['QuantityPurchased'];
            } else if ( isset( $orderDetailsArray['TransactionArray']['Transaction'][0]['Buyer'] ) ) {
                foreach ( $orderDetailsArray['TransactionArray']['Transaction'] as $transaction ) {
                    $totalQty += $transaction['QuantityPurchased'];
                }
            }
        }

        return $totalQty;
    }

    static function getRefNum( $order ) {
        if ( ! empty( $order->ref ) ) {
            return $order->ref;
        }

        return "EBAY ES " . sprintf( "%05d", $order->id );
    }

    static function getSalesRecordNumber( $orderDetails ) {
        if ( ! empty( $orderDetails ) && is_array( json_decode( $orderDetails, true ) ) ) {
            $orderDetailsArray = json_decode( $orderDetails, true );
            if ( isset( $orderDetailsArray['ShippingDetails']['SellingManagerSalesRecordNumber'] ) ) {
                return $orderDetailsArray['ShippingDetails']['SellingManagerSalesRecordNumber'];
            }
        }

        return 0;
    }

    static function getSubtotal( $subTotal, $percentage ) {
        return ( $subTotal - self::getVatPrice( $subTotal, $percentage ) );
    }

    static function getShippingPrice( $shippingPrice, $percentage ) {
        return ( $shippingPrice - self::getVatPrice( $shippingPrice, $percentage ) );
    }

    static function getVatPrice( $price, $percentage ) {
        $ivaCal = 1 + ( $percentage / 100 );

        return number_format( (float) ( $price - ( $price / $ivaCal ) ), 2, '.', '' );
    }

    static function getMobileNumber( $invoiceDetails ) {
        if ( isset( $invoiceDetails['phone_no'] ) && ! empty( $invoiceDetails['phone_no'] ) ) {
            return $invoiceDetails['phone_no'];
        }

        return;
    }

    static function getItemId( $orderDetails ) {
        if ( is_array( $orderDetails ) ) {
            $orderDetails = json_decode( json_encode( $orderDetails ) );
        }
        $invoiceDetailArray = self::constructInvoiceDetailsArray( $orderDetails );
        if ( isset( $invoiceDetailArray['ref'][0] ) ) {
            return $invoiceDetailArray['ref'][0];
        }
    }

    static function constructInvoiceDetailsArray( $orders, $invoiceDetails = [] ) {
        $ebayDetails          = $orders->order_detail;
        $countryVatPercentage = self::getTaxByOrder( $orders );

        if ( is_string( $ebayDetails ) ) {
            $ebayDetails = json_decode( $ebayDetails, true );
        } else if ( is_object( $ebayDetails ) ) {
            $ebayDetails = json_decode( json_encode( $ebayDetails ), true );
        }
        $street2ArrayKey = ( is_array( $ebayDetails['ShippingAddress']['Street2'] ) && ! empty( $ebayDetails['ShippingAddress']['Street2'] ) ) ? array_keys( $ebayDetails['ShippingAddress']['Street2'] ) : '';
        if ( is_array( $street2ArrayKey ) && ! empty( $street2ArrayKey ) ) {
            $street2 = $ebayDetails['ShippingAddress']['Street2'][ $street2ArrayKey[0] ];
        } else if ( empty( $street2ArrayKey ) ) {
            $street2 = '';
        } else {
            $street2 = $ebayDetails['ShippingAddress']['Street2'];
        }
        // $invoiceDetails['numero'] = (isset($invoiceDetails['numero'])) ? $invoiceDetails['numero'] : getRefNum();
        $invoiceDetails['numero']     = $orders->ref;
        $invoiceDetails['fetcha']     = ( isset( $invoiceDetails['fetcha'] ) ) ? $invoiceDetails['fetcha'] : date( 'd/m/Y', strtotime( $orders->ordered_date ) );
        $invoiceDetails['site']       = ( isset( $invoiceDetails['site'] ) ) ? $invoiceDetails['site'] : 'EBAY';
        $invoiceDetails['buyer_name'] = ( isset( $invoiceDetails['buyer_name'] ) ) ? $invoiceDetails['buyer_name'] : $ebayDetails['ShippingAddress']['Name'];
        $invoiceDetails['address']    = ( isset( $invoiceDetails['address'] ) ) ? $invoiceDetails['address'] : $ebayDetails['ShippingAddress']['Street1'];
        $invoiceDetails['address_2']  = ( isset( $invoiceDetails['address_2'] ) ) ? $invoiceDetails['address_2'] : $street2;
        if ( is_array( $ebayDetails['ShippingAddress']['CityName'] ) ) {
            $city = implode( ', ', $ebayDetails['ShippingAddress']['CityName'] );
        } else {
            $city = $ebayDetails['ShippingAddress']['CityName'];
        }
        if ( is_array( $ebayDetails['ShippingAddress']['StateOrProvince'] ) ) {
            $state = implode( ', ', $ebayDetails['ShippingAddress']['StateOrProvince'] );
        } else {
            $state = $ebayDetails['ShippingAddress']['StateOrProvince'];
        }
        $invoiceDetails['city']     = ( isset( $invoiceDetails['city'] ) ) ? $invoiceDetails['city'] : $city;
        $invoiceDetails['state']    = ( isset( $invoiceDetails['state'] ) ) ? $invoiceDetails['state'] : $state;
        $invoiceDetails['zip_code'] = ( isset( $invoiceDetails['zip_code'] ) ) ? $invoiceDetails['zip_code'] : $ebayDetails['ShippingAddress']['PostalCode'];
        $invoiceDetails['country']  = ( isset( $invoiceDetails['country'] ) ) ? $invoiceDetails['country'] : $ebayDetails['ShippingAddress']['CountryName'];
        $invoiceDetails['phone_no'] = ( isset( $invoiceDetails['phone_no'] ) ) ? $invoiceDetails['phone_no'] : $ebayDetails['ShippingAddress']['Phone'];
        $invoiceDetails['cif_no']   = ( isset( $invoiceDetails['cif_no'] ) ) ? $invoiceDetails['cif_no'] : '000000000';
        if ( isset( $ebayDetails['TransactionArray']['Transaction'][0] ) ) {
            $i = 0;
            foreach ( $ebayDetails['TransactionArray']['Transaction'] as $transaction ) {
                $invoiceDetails['ref'][ $i ]       = ( isset( $invoiceDetails['ref'][ $i ] ) ) ? $invoiceDetails['ref'][ $i ] : $transaction['Item']['ItemID'];
                $invoiceDetails['item_name'][ $i ] = ( isset( $invoiceDetails['item_name'][ $i ] ) ) ? $invoiceDetails['item_name'][ $i ] : $transaction['Item']['Title'];
                if ( isset( $transaction['SellerDiscounts']['OriginalItemPrice'] ) ) {
                    $invoiceDetails['price'][ $i ] = ( isset( $invoiceDetails['price'][ $i ] ) ) ? $invoiceDetails['price'][ $i ] : $transaction['SellerDiscounts']['OriginalItemPrice'];
                } else {
                    $invoiceDetails['price'][ $i ] = ( isset( $invoiceDetails['price'][ $i ] ) ) ? $invoiceDetails['price'][ $i ] : $transaction['TransactionPrice'];
                }
                if ( isset( $transaction['SellerDiscounts']['SellerDiscount']['ItemDiscountAmount'] ) ) {
                    $invoiceDetails['discount'][ $i ] = ( isset( $invoiceDetails['discount'][ $i ] ) ) ? $invoiceDetails['discount'][ $i ] : $transaction['SellerDiscounts']['SellerDiscount']['ItemDiscountAmount'];
                } else {
                    $invoiceDetails['discount'][ $i ] = ( isset( $invoiceDetails['discount'][ $i ] ) ) ? $invoiceDetails['discount'][ $i ] : 0.00;
                }
                $invoiceDetails['qty'][ $i ]       = ( isset( $invoiceDetails['qty'][ $i ] ) ) ? $invoiceDetails['qty'][ $i ] : $transaction['QuantityPurchased'];
                $invoiceDetails['sub_total'][ $i ] = ( isset( $invoiceDetails['sub_total'][ $i ] ) ) ? $invoiceDetails['sub_total'][ $i ] : $transaction['TransactionPrice'];

                if ( isset( $transaction['ShippingDetails']['SalesTax']['SalesTaxPercent'] ) ) {
                    $invoiceDetails['tax'][ $i ] = ( isset( $invoiceDetails['tax'][ $i ] ) ) ? $invoiceDetails['tax'][ $i ] : $countryVatPercentage;
                } else {
                    $invoiceDetails['tax'][ $i ] = ( isset( $invoiceDetails['tax'][ $i ] ) ) ? $invoiceDetails['tax'][ $i ] : '0.00';
                }

                $i ++;
            }
        } else {
            $i                                 = 0;
            $transaction                       = $ebayDetails['TransactionArray']['Transaction'];
            $invoiceDetails['ref'][ $i ]       = ( isset( $invoiceDetails['ref'][ $i ] ) ) ? $invoiceDetails['ref'][ $i ] : $transaction['Item']['ItemID'];
            $invoiceDetails['item_name'][ $i ] = ( isset( $invoiceDetails['item_name'][ $i ] ) ) ? $invoiceDetails['item_name'][ $i ] : $transaction['Item']['Title'];
            if ( isset( $transaction['SellerDiscounts']['OriginalItemPrice'] ) ) {
                $invoiceDetails['price'][ $i ] = ( isset( $invoiceDetails['price'][ $i ] ) ) ? $invoiceDetails['price'][ $i ] : $transaction['SellerDiscounts']['OriginalItemPrice'];
            } else {
                $invoiceDetails['price'][ $i ] = ( isset( $invoiceDetails['price'][ $i ] ) ) ? $invoiceDetails['price'][ $i ] : $transaction['TransactionPrice'];
            }
            if ( isset( $transaction['SellerDiscounts']['SellerDiscount']['ItemDiscountAmount'] ) ) {
                $invoiceDetails['discount'][ $i ] = ( isset( $invoiceDetails['discount'][ $i ] ) ) ? $invoiceDetails['discount'][ $i ] : $transaction['SellerDiscounts']['SellerDiscount']['ItemDiscountAmount'];
            } else {
                $invoiceDetails['discount'][ $i ] = ( isset( $invoiceDetails['discount'][ $i ] ) ) ? $invoiceDetails['discount'][ $i ] : 0.00;
            }
            $invoiceDetails['qty'][ $i ]       = ( isset( $invoiceDetails['qty'][ $i ] ) ) ? $invoiceDetails['qty'][ $i ] : $transaction['QuantityPurchased'];
            $invoiceDetails['sub_total'][ $i ] = ( isset( $invoiceDetails['sub_total'][ $i ] ) ) ? $invoiceDetails['sub_total'][ $i ] : $transaction['TransactionPrice'];
            if ( isset( $transaction['ShippingDetails']['SalesTax']['SalesTaxPercent'] ) ) {
                $invoiceDetails['tax'][ $i ] = ( isset( $invoiceDetails['tax'][ $i ] ) ) ? $invoiceDetails['tax'][ $i ] : 'IVA ' . $countryVatPercentage . ' %';
            } else {
                $invoiceDetails['tax'][ $i ] = ( isset( $invoiceDetails['tax'][ $i ] ) ) ? $invoiceDetails['tax'][ $i ] : 'IVA 0.00%';
            }
        }

        $invoiceDetails['sub_total_final'] = ( isset( $invoiceDetails['sub_total_final'] ) ) ? $invoiceDetails['sub_total_final'] : self::getSubtotal( $ebayDetails['Subtotal'], $countryVatPercentage );
        $invoiceDetails['shipping_cost']   = ( isset( $invoiceDetails['shipping_cost'] ) ) ? $invoiceDetails['shipping_cost'] : self::getShippingPrice( $ebayDetails['ShippingServiceSelected']['ShippingServiceCost'], $countryVatPercentage );
        $invoiceDetails['total_tax']       = ( isset( $invoiceDetails['total_tax'] ) ) ? $invoiceDetails['total_tax'] : self::getVatPrice( $ebayDetails['Total'], $countryVatPercentage );
        $invoiceDetails['total_final']     = ( isset( $invoiceDetails['total_final'] ) ) ? $invoiceDetails['total_final'] : $ebayDetails['Total'];


        return $invoiceDetails;
    }

    static function getTaxByOrder( $order ) {
        $percentage = null;


        if ( $order != null ) {
            if ( isset( $order->country ) ) {
                $vatConfig  = VatConfig::select( 'percentage' )->where( 'country_code', $order->country )->first();
                $percentage = ( $vatConfig != null ) ? $vatConfig->percentage : null;
            }
        }

        if ( $percentage == null ) {
            $default    = VatConfig::where( 'country_code', 'DEFAULT' )->first();
            $percentage = ( $default != null ) ? $default->percentage : null;
        }

        return $percentage;
    }

    static function getInvoice( $id, $mode ) {
        $orders = Order::where( 'id', $id )->get();
        if ( isset( $orders[0]->id ) && ! empty( $orders[0]->id ) ) {
            $invoiceDetails       = self::constructInvoiceDetailsArray( $orders[0], json_decode( $orders[0]->invoice_details, true ) );
            $countryVatPercentage = self::getTaxByOrder( $orders[0] );
            $html                 = '
            <div>
                <div style="width: 40%;float:left;"><img style="width:90%;margin-top:20px" src="' . dirname( __DIR__ ) . '/images/logo.png"/></div>
                <div style="width: 30%;float:right;">
                    <div style="margin-bottom:3px;font-size:10px;padding-top:20px">QUICENTRO SHOPPING S.L.</div>
                    <div style="margin-bottom:3px;font-size:10px">AV. OVIEDO 30</div>
                    <div style="margin-bottom:3px;font-size:10px">33420, LUGONES, ASTURIAS, ESPAÑA</div>
                    <div style="margin-bottom:3px;font-size:10px">CIF: B05395454</div>
                    <div style="margin-bottom:0px;font-size:10px">quicentroshoppingsl@gmail.com</div>
                </div>
            </div>
            <div>
                <h2 style="margin-top:50px;display:initial;text-align:center">FACTURA ORDINARIA</h2>
            </div>
            <div>
                <div style="width:40%;float:left">
                    <h4 style="margin-bottom:0px;font-size:12px">DATOS DE FACTURA ORDINARIA</h4>
                    <hr/>
                    <div style="margin-bottom:5px;font-size:11px;">Número: <b>' . $invoiceDetails['numero'] . '</b></div>
                    <div style="font-size:11px;margin-bottom:5px">Fecha: <b>' . $invoiceDetails['fetcha'] . '</b></div>
                    <div style="font-size:11px;margin-bottom:5px">Forma de pago: <b>' . $invoiceDetails['site'] . '</b></div>
                </div>
                <div style="width:20%;float:left"></div>
                <div style="width:38%;float:right">
                    <h4 style="margin-bottom:0px;font-size:12px">DATOS DEL CLIENTE</h4>
                    <hr/>
                    <div style="margin-bottom:5px;font-size:11px;"><b>' . $invoiceDetails['buyer_name'] . '</b></div>
                    <div style="margin-bottom:5px;font-size:11px;">' . $invoiceDetails['cif_no'] . '</div>
                    <div style="margin-bottom:5px;font-size:11px;">' . $invoiceDetails['address'] . '</div>
                    <div style="margin-bottom:5px;font-size:11px;">' . $invoiceDetails['address_2'] . '</div>
                    <div style="margin-bottom:5px;font-size:11px;">' . $invoiceDetails['city'] . ', ' . $invoiceDetails['state'] . ', ' . $invoiceDetails['zip_code'] . '</div>
                    <div style="margin-bottom:5px;font-size:11px;">' . $invoiceDetails['country'] . '</div>
                    <div style="margin-bottom:5px;font-size:11px;">Telf.: ' . $invoiceDetails['phone_no'] . '</div>
                </div>
            </div>
            <style>
                th,td {
                    padding: 8px;
                }
                td{
                    border-top:1px solid black;
                    border-bottom:1px solid black
                }
                table{
                    border-collapse: collapse;
                    width: 100%;
                }
            </style>
            <div style="margin-top:40px">
                <table>
                    <tr style="border-bottom:1px solid black">
                        <th style="width:10%;text-align:left;font-size:11px;">REF.</th>
                        <th style="width:40%;text-align:left;font-size:11px;">NOMBRE</th>
                        <th style="width:10%;text-align:left;font-size:11px;">PRECIO</th>
                        <th style="width:10%;text-align:left;font-size:11px;">DTO.</th>
                        <th style="width:10%;text-align:left;font-size:11px;">UDS.</th>
                        <th style="width:10%;text-align:left;font-size:11px;">SUBTOTAL</th>
                        <th style="width:10%;text-align:left;font-size:11px;">IMP.</th>
                    </tr>
                    <tbody>';
            $count                = 0;
            foreach ( $invoiceDetails['ref'] as $ref ) {
                $html .= '<tr>
                                        <td style="width:10%;font-size:11px;text-align:left">' . $ref . '</td>
                                        <td style="width:40%;font-size:11px;">' . $invoiceDetails['item_name'][ $count ] . '</td>
                                        <td style="width:10%;font-size:11px;text-align:left">' . number_format( floatval( $invoiceDetails['price'][ $count ] ), 2, ',', '.' ) . '</td>
                                        <td style="width:10%;font-size:11px;text-align:left">' . number_format( floatval( $invoiceDetails['discount'][ $count ] ), 2, ',', '.' ) . '</td>
                                        <td style="width:10%;font-size:11px;text-align:left">' . $invoiceDetails['qty'][ $count ] . '</td>
                                        <td style="width:10%;font-size:11px;text-align:left">' . number_format( floatval( $invoiceDetails['sub_total'][ $count ] ), 2, ',', '.' ) . '</td>
                                        <td style="width:10%;font-size:11px;text-align:left">IVA ' . number_format( floatval( $invoiceDetails['tax'][ $count ] ), 2, ',', '.' ) . '%</td>
                                    </tr>';
                $count ++;
            }
            $html                   .= ' </tbody>
                </table>
            </div>
        ';
            $mpdf                   = new Mpdf( [
                'mode'          => 'utf-8',
                'format'        => 'A4',
                'margin_left'   => 5,
                'margin_right'  => 5,
                'margin_top'    => 5,
                'margin_bottom' => 0,
                'margin_header' => 0,
                'margin_footer' => 0
            ] );
            $footerHtml             = '
            <div style="width:40%;float:right;padding-bottom:50px">
                <table>
                    <tbody>
                        <tr>
                            <td style="border-top:0"><b>BASE</b></td>
                            <td style="border-top:0"></td>
                            <td style="text-align:right;border-top:0">' . number_format( floatval( $invoiceDetails['sub_total_final'] ), 2, ',', '.' ) . ' €</td>
                        </tr>
                        <tr>
                            <td>Envío</td>
                            <td></td>
                            <td style="text-align:right">' . number_format( floatval( $invoiceDetails['shipping_cost'] ), 2, ',', '.' ) . ' €</td>
                        </tr>
                        <tr>
                            <td>IVA</td>
                            <td style="text-align:right"></td>
                            <td style="text-align:right">(' . $countryVatPercentage . '%) ' . number_format( floatval( $invoiceDetails['total_tax'] ), 2, ',', '.' ) . ' €</td>
                        </tr>
                        <tr>
                            <td style="border-bottom:0"><b>TOTAL</b></td>
                            <td style="border-bottom:0"></td>
                            <td style="text-align:right;border-bottom:0">' . number_format( floatval( $invoiceDetails['total_final'] ), 2, ',', '.' ) . ' €</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        ';
            $mpdf->autoScriptToLang = true;
            $mpdf->autoLangToFont   = true;
            $mpdf->SetHTMLFooter( $footerHtml );
            $mpdf->WriteHTML( $html );
            $storagePath = storage_path( 'orders' );
            File::makeDirectory( $storagePath, 0777, true, true );
            $filePath = $storagePath . '/' . $orders[0]->order_id . ".pdf";
            $mpdf->Output( $filePath, $mode );

            return $filePath;
        }
    }

    static function getDisplayDate( $dateTime ) {
        return date( 'd-m-Y H:i:s', strtotime( $dateTime ) );
    }

    static function getCountryByCode( $code ) {
        $json         = file_get_contents( public_path( 'json/country.json' ) );
        $countryArray = json_decode( $json );
        $country      = 'None';

        foreach ( $countryArray as $value ) {
            if ( $value->code == $code ) {
                $country = $value->name;
            }
        };

        return $country;
    }

    static function getCountryFlagByCode( $code, $type = 'emoji' ) {
        $json         = file_get_contents( public_path( 'json/country.json' ) );
        $countryArray = json_decode( $json );
        $country      = 'None';

        foreach ( $countryArray as $value ) {
            if ( $value->code == $code ) {
                $country = ( $type == 'emoji' ) ? $value->emoji : $value->image;
            }
        };

        return $country;
    }
}
