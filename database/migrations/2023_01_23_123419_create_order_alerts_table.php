<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderAlertsTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create( 'order_alerts', function ( Blueprint $table ) {
            $table->id();
            $table->bigInteger( 'order_id' );
            $table->boolean( 'whatsapp_received' )->default( 0 );
            $table->boolean( 'whatsapp_shipped' )->default( 0 );
            $table->boolean( 'whatsapp_delivered' )->default( 0 );
            $table->boolean( 'email_complete' )->default( 0 );
            $table->timestamps();
        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists( 'order_alerts' );
    }
}
