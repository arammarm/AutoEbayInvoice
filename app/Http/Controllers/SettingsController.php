<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use App\Models\VatConfig;
use App\Models\WhatsappTemplate;
use Illuminate\Http\Request;

class SettingsController extends Controller {
    public function __construct() {
        WhatsappTemplate::requiredTemplate();
        EmailTemplate::requiredTemplate();
    }

    public function whatsapp() {
        $data              = [];
        $data['templates'] = WhatsappTemplate::get();

        return view( 'settings.whatsapp', $data );
    }

    public function addWATemplate() {
        $id     = request()->input( 'id' );
        $update = [ 'template_name' => request()->input( 'template_name' ), 'template_content' => request()->input( 'message' ), 'active' => 1 ];
        if ( $id != null && $id != 0 && $id != '' ) {
            WhatsappTemplate::where( 'id', $id )->update( $update );
        } else {
            WhatsappTemplate::create( $update );
        }

        return redirect()->back()->with( [ 'error' => 0, 'message' => 'Template has been updated' ] );
    }

    public function getWATemplate( $id ) {
        $rec       = WhatsappTemplate::where( 'id', $id )->first();
        $rec->auto = false;
        if ( in_array( $rec->template_name, WhatsappTemplate::$requiredTemplates ) ) {
            $rec->auto = true;
        }

        return response()->json( [ 'error' => 0, 'data' => $rec ] );
    }

    public function deleteWATemplate() {
        $id = request()->input( 'id' );
        WhatsappTemplate::where( 'id', $id )->delete();

        return response()->json( [ 'error' => 0, 'message' => 'Template has been removed' ] );

    }

    public function email() {
        $data              = [];
        $data['templates'] = EmailTemplate::get();

        return view( 'settings.email', $data );
    }

    public function addEmailTemplate() {
        $id     = request()->input( 'id' );
        $update = [ 'template_name' => request()->input( 'template_name' ), 'template_content' => request()->input( 'message' ), 'active' => 1 ];
        if ( $id != null && $id != 0 && $id != '' ) {
            EmailTemplate::where( 'id', $id )->update( $update );
        } else {
            EmailTemplate::create( $update );
        }

        return redirect()->back()->with( [ 'error' => 0, 'message' => 'Template has been updated' ] );
    }

    public function getEmailTemplate( $id ) {
        $rec       = EmailTemplate::where( 'id', $id )->first();
        $rec->auto = false;
        if ( in_array( $rec->template_name, EmailTemplate::$requiredTemplates ) ) {
            $rec->auto = true;
        }

        return response()->json( [ 'error' => 0, 'data' => $rec ] );
    }

    public function deleteEmailTemplate() {
        $id = request()->input( 'id' );
        EmailTemplate::where( 'id', $id )->delete();

        return response()->json( [ 'error' => 0, 'message' => 'Template has been removed' ] );

    }

    public function vat() {
        $data = [];

        $data['vat_configs'] = VatConfig::get();

        return view( 'settings.vat', $data );
    }

    public function vatSave() {
        $id         = request()->input( 'id' );
        $percentage = request()->input( 'percentage' );
        VatConfig::where( 'id', $id )->update( [ 'percentage' => $percentage ] );

        return response()->json( [ 'error' => 0, 'message' => 'Successfully saved' ] );
    }
}
