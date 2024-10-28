<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\GeneralFunctionsController;
use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Session;

class SurveysController extends Controller
{
    public function __construct()
    {
        $this->gen_func_obj = new GeneralFunctionsController();
        $this->cus_obj = new CustomerController();
    }

    //
    public function load_surveys(Request $request)
    {
        if(!$this->gen_func_obj->validate_access())
        {
            \Session::flush(); //remove all sessions
            return redirect('/');
        }
        
        $loggedin_customer_id = \Session::get('_loggedin_customer_id');
        $parent_account_id = $this->cus_obj->get_parent_account_id();
        
        if(isset($request["lt"]) && $request["lt"] == 'y') //load templates
            $load_templates = 'yes';
        else
            $load_templates = 'no';

        if(isset($request["ld"]) && $request["ld"] == 'y') //load dashboard
            $load_dashpage = 'yes';
        else
            $load_dashpage = 'no';

        if(isset($request["lrd"]) && $request["lrd"] == 'y') //load regions data
            $load_region_data = 'yes';
        else
            $load_region_data = 'no';

        if(isset($request["stype"]) && !empty($request["stype"])) //load surveys with respect to type
            $stype = $request["stype"];
        else
            $stype = 'NA';

        if(isset($request["lcd"]) && $request["lcd"] == 'y') //load customer dashboard
            $load_customer_dashboard = 'yes';
        else
            $load_customer_dashboard = 'no';
        
        return view('pages.customer-surveys', ["cid" => $loggedin_customer_id, "pid" => $parent_account_id, "load_templates" => $load_templates, "load_dashpage" => $load_dashpage, "load_region_data" => $load_region_data, "stype" => $stype, "load_customer_dashboard" => $load_customer_dashboard ]);
    }


    
}
