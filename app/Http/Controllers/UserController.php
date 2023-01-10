<?php

namespace App\Http\Controllers;


use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller {

    public function changePassword() {
        return view( 'settings.change_password' );
    }

    public function changePasswordPost() {
        $this->validate( request(), [
            'new_password'     => 'required|confirmed|min:5|string'
        ] );
        $auth = Auth::user();

        $user           = User::find( $auth->id );
        $user->password = Hash::make( request()->new_password );
        $user->save();

        return back()->with( 'success', "Password Changed Successfully" );
    }
}
