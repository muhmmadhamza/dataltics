<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AccountSettingController extends Controller
{
    //
    public function index(Request $request)
    {
        //check for user login
        // if(!$this->gen_func_obj->validate_access())
        // {
        //     return redirect('/');
        // }
        return view('pages.account');
    }
}
