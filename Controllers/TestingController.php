<?php

namespace App\Http\Controllers;

use Session;
use Illuminate\Http\Request;
use App\Http\Controllers\GeneralFunctionsController;

class TestingController extends Controller
{
    public function __construct()
    {
        $this->gen_func_obj = new GeneralFunctionsController();
        
        //check for user login
        $this->gen_func_obj->validate_access();
    }
    
    public function load_topic_dashboard(Request $request)
    {
        $topic_session = \Session::get('current_loaded_project');
        $login_session = \Session::get('_loggedin_customer_id');
        
        if(!is_null($login_session) && !empty($login_session))
        {
            if(!is_null($topic_session) && !empty($topic_session))
            {
                return view('pages.testing-elastic');
            }
            else
            {
                return redirect('topic-settings');
            }
        }
        else
        {
            return redirect('/');
        }
    }
    
    /*public function load_subtopic_dashboard(Request $request)
    {
        $login_session = \Session::get('_loggedin_customer_id');
        $topic_session = \Session::get('current_loaded_project');
        $subtopic_session = \Session::get('current_loaded_subtopic');
        
        if(!is_null($login_session) && !empty($login_session))
        {
            if(!is_null($topic_session) && !empty($topic_session) && !is_null($subtopic_session) && !empty($subtopic_session))
            {
                return view('pages.dashboard-subtopic');
            }
            else
            {
                return redirect('topic-settings');
            }
        }
        else
        {
            return redirect('/');
        }
    }*/

    public function loginPage()
    {
        $pageConfigs = ['bodyCustomClass'=> 'bg-full-screen-image'];
        return view('pages.auth-login',['pageConfigs' => $pageConfigs]);
    }
}
