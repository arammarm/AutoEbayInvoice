<?php


namespace App\Helper;


class ReportHelper {


    public static $durations = [
        'yearly'       => 'Yearly',
        'year-quarter' => 'Quarter Yearly',
        'monthly'      => 'Monthly',
        'weekly'       => 'Weekly',
        'daily'        => 'Daily',
    ];

    public static function setUpDailyData( $rawData, $datasetLabel = 'Sales' ) {

        $data = [];

        $dataSet = [];
        foreach ( $rawData as $datum ) {
            $data['labels'][] = $datum->date;
            $dataSet[]        = $datum->t;
        }

        $data['datasets'][] = [ 'label' => $datasetLabel, 'data' => $dataSet, 'fill' => false, '$dataSet' => 'rgb(75, 192, 192)', 'tension' => '0.1' ];

        return $data;
    }

    public static function getDurationLabel( $key ) {
        return ReportHelper::$durations[ $key ];
    }


}
