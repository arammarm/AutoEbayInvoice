<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappTemplatesTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create( 'whatsapp_templates', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'template_name' );
            $table->longText( 'template_content' );
            $table->boolean( 'active' );
            $table->timestamps();
        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists( 'whatsapp_templates' );
    }
}
