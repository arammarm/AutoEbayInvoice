<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddToUsersTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table( 'orders', function ( Blueprint $table ) {
            $table->boolean( 'whatsapp_received' )->default( 0 );
            $table->dateTime( 'whatsapp_received_date' )->nullable();
            $table->boolean( 'whatsapp_shipped' )->default( 0 );
            $table->dateTime( 'whatsapp_shipped_date' )->nullable();
            $table->boolean( 'whatsapp_delivered' )->default( 0 );
            $table->dateTime( 'whatsapp_delivered_date' )->nullable();
            $table->boolean( 'email_complete' )->default( 0 );
            $table->dateTime( 'email_complete_date' )->nullable();
        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table( 'orders', function ( Blueprint $table ) {
            $table->dropColumn( [
                'whatsapp_received',
                'whatsapp_received_date',
                'whatsapp_shipped',
                'whatsapp_shipped_date',
                'whatsapp_delivered',
                'whatsapp_delivered_date',
                'email_complete',
                'email_complete_date'
            ] );
        } );
    }
}
