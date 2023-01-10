<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create( 'orders', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'order_id' );
            $table->string( 'order_status' );
            $table->decimal( 'total', 10 );
            $table->dateTime( 'ordered_date' );
            $table->string( 'buyer' );
            $table->longText( 'order_detail' );
            $table->longText( 'invoice_details' );
            $table->string( 'ref' );
            $table->string( 'country', 10 );
            $table->dateTime( 'last_downloaded' )->nullable();
            $table->timestamps();
        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists( 'orders' );
    }
}
