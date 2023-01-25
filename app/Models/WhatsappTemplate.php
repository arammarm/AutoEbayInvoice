<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappTemplate extends Model {
    use HasFactory;

    protected $fillable = [ 'template_name', 'template_content', 'active' ];


    public static function requiredTemplate() {
        foreach ( self::$requiredTemplates as $required_template ) {
            if ( ! self::where( 'template_name', $required_template )->exists() ) {
                self::create( [ 'template_name' => $required_template, 'template_content' => 'Content not initialize', 'active' => 1 ] );
            }
        }
    }

}
