<?php

namespace App\Http\Controllers;

use App\Helper\eBayFunctions;
use App\Helper\ReportHelper;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller {
    public function index() {

        $month = Carbon::now()->month;
        $year  = Carbon::now()->year;

        $data['total']['count']       = Order::where( 'order_status', 'Completed' )->count();
        $data['total']['sales']       = Order::where( 'order_status', 'Completed' )->sum( 'total' );
        $data['total_month']['sales'] = Order::whereMonth( 'ordered_date', $month )->whereYear( 'ordered_date', $year )->where( 'order_status', 'Completed' )->sum( 'total' );
        $data['total_month']['count'] = Order::whereMonth( 'ordered_date', $month )->whereYear( 'ordered_date', $year )->where( 'order_status', 'Completed' )->count();
        $data['total_year']['sales']  = Order::whereYear( 'ordered_date', $year )->where( 'order_status', 'Completed' )->sum( 'total' );
        $data['total_year']['count']  = Order::whereYear( 'ordered_date', $year )->where( 'order_status', 'Completed' )->count();

        $countries = Order::select( 'country' )->groupBy( 'country' )->pluck( 'country' )->toArray();
        foreach ( $countries as $country ) {
            $data['countries'][] = [
                'code' => $country,
                'name' => eBayFunctions::getCountryByCode( $country ),
                'flag' => eBayFunctions::getCountryFlagByCode( $country )
            ];
        }

        $data['years']     = Order::select( DB::raw( 'YEAR(ordered_date) as year' ) )->groupBy( DB::raw( 'YEAR(ordered_date)' ) )->pluck( 'year' )->toArray();
        $data['durations'] = ReportHelper::$durations;

        return view( 'reports.report', $data );
    }

    public function getReportData() {
        $country  = request()->input( 'country' );
        $duration = request()->input( 'duration' );

        $rec        = null;
        $countrySum = "SUM(CASE WHEN o.total IS NOT NULL THEN o.total ELSE 0 END) AS t";
        if ( $country != null ) {
            $countrySum = "SUM(CASE WHEN o.country = '$country' THEN o.total ELSE 0 END) AS t";
        }
        if ( $duration == 'daily' ) {
            $rec = DB::table( 'date_lists as dl' )
                     ->select( DB::raw( 'DATE(dl.date) as date' ), DB::raw( $countrySum ) )
                     ->whereDate( 'dl.date', '>=', Carbon::now()->subDays( 30 ) )->groupBy( 'dl.date' );
        } else if ( $duration == 'weekly' ) {
            $rec = DB::table( 'date_lists as dl' )
                     ->select( DB::raw( 'WEEK(dl.date) as date' ), DB::raw( 'DATE(dl.date) as dDate' ), DB::raw( $countrySum ) )
                     ->whereDate( 'dl.date', '>=', Carbon::now()->subMonths( 2 ) )->groupBy( DB::Raw( 'WEEK(dl.date)' ) );
        } else if ( $duration == 'monthly' ) {
            $rec = DB::table( 'date_lists as dl' )
                     ->select( DB::raw( 'CONCAT( YEAR(dl.date),\'-\',MONTH(dl.date)  ) as date' ), DB::raw( $countrySum ) )
                     ->whereDate( 'dl.date', '>=', Carbon::now()->subYear() )->groupBy( DB::Raw( DB::raw( 'CONCAT( YEAR(dl.date),\'-\',MONTH(dl.date)  )' ) ) );
        } else if ( $duration == 'year-quarter' ) {
            $rec = DB::table( 'date_lists as dl' )
                     ->select( DB::raw( 'CONCAT( YEAR(dl.date),\'-Q\',QUARTER(dl.date) ) as date' ), DB::raw( $countrySum ) )
                     ->whereDate( 'dl.date', '>=', Carbon::now()->subYears( 2 ) )->groupBy( DB::Raw( DB::raw( 'CONCAT( YEAR(dl.date),\'-\',QUARTER(dl.date) )' ) ) );
        } else if ( $duration == 'yearly' ) {
            $rec = DB::table( 'date_lists as dl' )
                     ->select( DB::raw( 'YEAR(dl.date) as date' ), DB::raw( $countrySum ), 'o.country' )
                     ->whereDate( 'dl.date', '>=', Carbon::now()->subYears( 4 ) )->groupBy( DB::Raw( 'YEAR(dl.date)' ) );
        }

        if ( $rec ) {
            $rec = $rec->whereDate( 'dl.date', '<', Carbon::now() )
                       ->leftJoin( 'orders as o', DB::raw( 'DATE(o.ordered_date)' ), '=', DB::raw( 'DATE(dl.date)' ) )
                       ->orderBy( 'dl.date', "ASC" )->get();

            return response()->json( [
                'error' => 0,
                'data'  => [
                    'raw_data'   => $rec,
                    'graph_data' => ReportHelper::setUpDailyData( $rec, ReportHelper::getDurationLabel( $duration, count( $rec ) ) )
                ]
            ] );
        }

        return response()->json( [ 'error' => 1, 'message' => 'No Records found' ] );
    }

    public function getSummaryView() {
        $rec      = request()->input( 'raw_data' );
        $duration = request()->input( 'duration' );

        return view( 'reports.report_summary', [ 'data' => $rec ] );
    }

    public function downloadInvoice() {
        $country = request()->input( 'country' );
        $year    = request()->input( 'year' );
        $month   = request()->input( 'month' );

        $recs = Order::select( 'id' )->where( function ( $query ) use ( $country ) {
            if ( $country != null ) {
                return $query->where( 'country', $country );
            }
        } )->whereYear( 'ordered_date', $year )->whereMonth( 'ordered_date', $month )->get();

        if ( isset( $recs[0] ) ) {
            $filePaths = [];
            foreach ( $recs as $rec ) {
                $filePaths[] = eBayFunctions::getInvoice( $rec->id, "F" );
            }

            $zip_file = "invoices-$country-$year-$month.zip";
            $zip      = new \ZipArchive();
            $zip->open( $zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE );

            foreach ( $filePaths as $file ) {
                $zip->addFile( $file, basename( $file ) );
            }
            $zip->close();

            return response()->download( $zip_file );
        }

        return back()->with( 'error', 'No orders found with-in the criteria' )->withInput();
    }
}
