<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Elasticsearch\ClientBuilder;
use App\Http\Controllers\GeneralFunctionsController;
use App\Http\Controllers\ActivityLogController;

class LoginController extends Controller
{
    public function __construct()
    {
        $this->gen_func_obj = new GeneralFunctionsController();
        $this->ac_log_obj = new ActivityLogController();
    }

    public function validate_login(Request $request)
    {
        if(isset($request["mode"]) && !empty($request["mode"]))
        {
            if($request["mode"] == 'validate_login')
            {
                if(isset($request["customer_email"]) && !empty($request["customer_email"]))
                {
                    if(isset($request["customer_pass"]) && !empty($request["customer_pass"]))
                    {
                        //validate user from db
                        $cus_data = DB::select("SELECT * FROM customers WHERE customer_email = '".$request["customer_email"]."' AND customer_pass = '".$this->gen_func_obj->encrypt($request["customer_pass"], $this->gen_func_obj->get_encryption_key())."'");
                        if(count($cus_data) > 0)
                        {
                            \Session::put('_loggedin_customer_id', $cus_data[0]->customer_id);
                            
                            $this->ac_log_obj->log_customer_data($request, array("cid" => $cus_data[0]->customer_id));
                            
                            if($cus_data[0]->customer_id == 292 || $cus_data[0]->customer_id == 431 || $cus_data[0]->customer_id == 421)
                                echo 'AuthenticatedSurvey';
                            else
                                echo 'Authenticated';
                        }
                        else
                        {
                            echo 'Authentication failed.';
                        }
                    }
                    else
                    {
                        echo 'Password not provided';
                    }
                }
                else
                {
                    echo 'Login id / email not provided.';
                }
            }
        }
        else
        {
            echo 'Un-authorize access!!';
        }
    }
    
    public function logout_user()
    {
        $loggedin_uid = \Session::get('_loggedin_customer_id');
        
        $this->ac_log_obj->log_customer_data('', array("cid" => \Session::get('_loggedin_customer_id'), "logout" => 'yes'));
        
        \Session::put('_loggedin_customer_id', '');
        \Session::flush(); //remove all sessions
        
        if($loggedin_uid == 308)
            return redirect('/loginrkdxb');
        else if($loggedin_uid == 309)
            return redirect('/logineand');
        else if($loggedin_uid == 411)
            return redirect('/loginadafsa');
        else if($loggedin_uid == 415)
            return redirect('/loginicp');
        else
            return redirect('/');
    }
}
?>
