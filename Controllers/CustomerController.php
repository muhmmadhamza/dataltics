<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Elasticsearch\ClientBuilder;
use App\Http\Controllers\GeneralFunctionsController;
use App\Http\Controllers\LoginController;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->gen_func_obj = new GeneralFunctionsController();
        $this->login_obj = new LoginController();
    }

    public function get_allowed_topics()
    {
        $cdata = DB::select("SELECT customer_email, customer_reg_scope, customer_account_parent FROM customers WHERE customer_id = ".\Session::get('_loggedin_customer_id'));
        
        if($cdata[0]->customer_reg_scope == 'IS') //invidted signup get parent account topics limit
        {
            $qq = DB::select("SELECT customer_allowed_topics FROM customers WHERE customer_email = '".$cdata[0]->customer_account_parent."'");
            
            return $this->gen_func_obj->decrypt($qq[0]->customer_allowed_topics, $this->gen_func_obj->get_encryption_key());
        }
        else
        {
            $qq = DB::select("SELECT customer_allowed_topics FROM customers WHERE customer_email = '".$cdata[0]->customer_email."'");
            
            return $this->gen_func_obj->decrypt($qq[0]->customer_allowed_topics, $this->gen_func_obj->get_encryption_key());
        }
    }
    
    public function get_created_topics()
    {
        $loggedin_customer_id = \Session::get('_loggedin_customer_id');
        
        if(isset($loggedin_customer_id) && !empty($loggedin_customer_id))
        {
            $cdata = DB::select("SELECT customer_email, customer_reg_scope, customer_account_parent FROM customers WHERE customer_id = ".$loggedin_customer_id);
        
            if($cdata[0]->customer_reg_scope == 'IS') //invidted signup get parent account topics count
            {
                $qq = DB::select("SELECT customer_id FROM customers WHERE customer_email = '".$cdata[0]->customer_account_parent."'");

                $qq1 = DB::select("SELECT COUNT(*) AS totrec FROM customer_topics WHERE customer_portal = 'D24' AND topic_user_id = ".$qq[0]->customer_id);

                return $qq1[0]->totrec;
            }
            else
            {
                $qq = DB::select("SELECT COUNT(*) AS totrec FROM customer_topics WHERE customer_portal = 'D24' AND topic_user_id = ".\Session::get('_loggedin_customer_id'));

                return $qq[0]->totrec;
            }
        }        
    }
    
    public function get_customer_review_elastic_id()
    {
        $rq = DB::select("SELECT customer_reviews_key FROM customers WHERE customer_id = ".$this->get_parent_account_id());
        
        return $rq[0]->customer_reviews_key;
    }
    
    public function get_parent_account_id()
    {
        $loggedin_customer_id = \Session::get('_loggedin_customer_id');
        
        if(isset($loggedin_customer_id) && !empty($loggedin_customer_id))
        {
            $cdata = DB::select("SELECT customer_reg_scope, customer_account_parent FROM customers WHERE customer_id = ".\Session::get('_loggedin_customer_id'));
        
            if(count($cdata) > 0)
            {
                if($cdata[0]->customer_reg_scope == 'IS') //invidted signup get parent account topics count
                {
                    $qq = DB::select("SELECT customer_id FROM customers WHERE customer_email = '".$cdata[0]->customer_account_parent."'");

                    return $qq[0]->customer_id;
                }
                else
                {
                    return \Session::get('_loggedin_customer_id');
                }
            }
            else
            {
                //$this->login_obj->logout_user();
                \Session::flush(); //remove all sessions

                return redirect('/');
            }
        }
        else
        {
            //$this->login_obj->logout_user();
            \Session::flush(); //remove all sessions

            //return redirect('/');
            return '';
        }
    }
    
    public function check_printmedia_access()
    {
        $pm_emails = array("omran.om", "medcoman.com", "beah.om", "holding.nama.om", "mzec.nama.om", "tanweer.nama.om", "majanco.nama.om", "dpcoman.nama.om", "omangrid.nama.om", "omanpwp.nama.om");
        
        $parent_account_id = $this->get_parent_account_id();
        
        $cust_data = DB::select("SELECT customer_email FROM customers WHERE customer_id = ".$parent_account_id);
        
        $email_dom = explode("@", $cust_data[0]->customer_email);
        
        if (in_array(strtolower($email_dom[1]), $pm_emails))
            return strtolower($email_dom[1]);
        else
            return 'NA';
    }
    
    public function get_customer_email($uid)
    {
        $cust_data = DB::select("SELECT customer_email FROM customers WHERE customer_id = ".$uid);
        
        return $cust_data[0]->customer_email;
    }
    
    public function get_customer_sub_account($uid)
    {
        $acc_data = DB::select("SELECT customer_id FROM customers WHERE customer_account_parent = '".$this->get_customer_email($uid)."'");
        
        return $acc_data;
    }
    
    public function get_customer_name($uid)
    {
        $cust_data = DB::select("SELECT customer_name FROM customers WHERE customer_id = ".$uid);
        
        return $cust_data[0]->customer_name;
    }
    
    public function check_customer_module_access($module)
    {
        $customer_id = $this->get_parent_account_id();
        
        $cq = DB::select("SELECT sh_id FROM source_handles WHERE sh_type = '".$module."' AND sh_cid = ".$customer_id);
        
        if(count($cq) > 0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}
?>

