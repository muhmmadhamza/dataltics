<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Elasticsearch\ClientBuilder;
use App\Http\Controllers\GeneralFunctionsController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ElasticController;
use Crypt;
use App\Http\Controllers\ActivityLogController;

class TouchpointController extends Controller
{
    public function __construct()
    {
        $tmp_request = new \Illuminate\Http\Request();
        //$tmp_request->replace(['foo' => 'bar']);

        $this->gen_func_obj = new GeneralFunctionsController();
        $this->topic_obj = new TopicController();
        $this->cus_obj = new CustomerController();
        $this->ac_log_obj = new ActivityLogController();
        
        //check for user login
        $this->gen_func_obj->validate_access();
        
        $this->loggedin_user_id = \Session::get('_loggedin_customer_id');
        $this->topic_session = \Session::get('current_loaded_project');
    }

    public function handle_touchpoint(Request $request)
    {
        if(isset($request["mode"]) && !empty($request["mode"]))
        {
            if($request["mode"] == 'create_tp')
            {
                $tuid = $this->cus_obj->get_parent_account_id();
                //$topic_id = Crypt::decrypt($request["hidden_tid"]);
                
                $tp_data = DB::insert("INSERT INTO touch_points SET tp_name = '".$request["tp_name"]."', tp_keywords = '".$request["tp_keywords"]."', tp_uid = ".$tuid.", tp_cx_id = ".$request["sub_topic"].", tp_date = NOW()");
                
                $new_tp_id = DB::getPdo()->lastInsertId();
                
                DB::insert("INSERT INTO cx_touch_points SET cx_tp_cx_id = ".$request["sub_topic"].", cx_tp_tp_id = ".$new_tp_id);
                
                $this->ac_log_obj->log_customer_data($request, array("cid" => \Session::get('_loggedin_customer_id'), "tpid" => $new_tp_id));
                
                echo 'Success';
            }
            else if($request["mode"] == 'edit_tp')
            {
                $tuid = $this->cus_obj->get_parent_account_id();
                
                DB::update("UPDATE touch_points SET tp_name = '".$request["tp_name"]."', tp_keywords = '".$request["tp_keywords"]."', tp_cx_id = ".$request["sub_topic"]." WHERE tp_uid = ".$tuid." AND tp_id = ".Crypt::decrypt($request["tpid"]));
                
                $this->ac_log_obj->log_customer_data($request, array("cid" => \Session::get('_loggedin_customer_id'), "tpid" => Crypt::decrypt($request["tpid"])));
                
                //DB::update("UPDATE cx_touch_points SET cx_tp_cx_id = ".$request["sub_topic"]." WHERE cx_tp_tp_id = ".Crypt::decrypt($request["tpid"]));
                
                echo 'Success';
            }
        }
    }
    
    public function tempfunc(Request $request)
    {
        $response = array();
        if(isset($request["tpid"]) && !empty($request["tpid"])) //case if edit touchpoint is being loaded
        {
            $tname = DB::table('touch_points')
                ->select('tp_name','tp_keywords','tp_cx_id')
                ->WhereRaw('tp_id = ?', Crypt::decrypt($request["tpid"]))
                ->get();
            $tnamedata = $tname->count();
            if($tnamedata > 0){
               $response = $tname;
            }
        }
        return json_encode($response);
    }
    
    public function get_all_tp_ids()
    {
        $subtopic_session_id = \Session::get('current_loaded_subtopic');
        if(isset($subtopic_session_id) && !empty($subtopic_session_id))
        {
            $tp_data = DB::select("SELECT cx_tp_tp_id FROM cx_touch_points WHERE cx_tp_cx_id = ".$subtopic_session_id);
            
            if(count($tp_data) > 0)
                return $tp_data;
            else
                return 'NA';
        }
        else
            return 'NA';
    }
    
    public function get_all_touchpoints_data($cx_id)
    {
        $tp_data = DB::select("SELECT * FROM touch_points WHERE tp_cx_id = ".$cx_id." ORDER BY tp_id ASC");
            
        if(count($tp_data) > 0)
            return $tp_data;
        else
            return 'NA';
    }
    
    public function get_touchpoint_data($tp_id)
    {
        $tp_data = DB::select("SELECT * FROM touch_points WHERE tp_id = ".$tp_id);
        
        return $tp_data;
    }
    
    public function get_all_touchpoint_ids($cx_id)
    {
        $tp_data = DB::select("SELECT tp_id FROM touch_points WHERE tp_cx_id = ".$cx_id);
            
        if(count($tp_data) > 0)
            return $tp_data;
        else
            return 'NA';
    }
    
    public function get_touchpoint_elastic_query($tp_id)
    {
        $key_str = '';
        
        $tp_dataz = DB::select("SELECT tp_keywords FROM touch_points WHERE tp_id = ".$tp_id);
        
        $keywords = explode(",", $tp_dataz[0]->tp_keywords);
        
        for ($i = 0; $i < count($keywords); $i ++)
        {
            if (! empty(trim($keywords[$i])))
            {
                $key_str .= '"' . trim($keywords[$i]) . '" OR ';
            }
        }
        
        $key_str = substr($key_str, 0, -4);
        
        return 'p_message_text:(' . $key_str . ')';
    }

    public function get_touchpoint_sentiments_data($tid)
    {
        $tmp_request = new \Illuminate\Http\Request();
        //$tmp_request->replace(['foo' => 'bar']);

        
        $this->es_obj = new ElasticController($tmp_request);

        return $this->es_obj->get_touchpoint_sentiments($tid, $tmp_request);
        //return $tid;
    }
    
    public function get_touchpoint_sentiments_data_report($tid, $stid, $tpid, $dfrom, $dto)
    {
        $tmp_request = new \Illuminate\Http\Request();
        $tmp_request->setMethod('POST');
        $tmp_request->request->add(['pdf_report' => 'yes', 'tid' => $tid, 'stid' => $stid, 'tpid' => $tpid, 'dfrom' => $dfrom, 'dto' => $dto]);
        //$tmp_request->replace(['pdf_report' => 'yes', 'tid' => $tid, 'stid' => $stid, 'tpid' => $tpid, 'dfrom' => $dfrom, 'dto' => $dto]);

        
        $this->es_obj = new ElasticController($tmp_request);

        return $this->es_obj->get_touchpoint_sentiments($tid, $tmp_request);
        //var_dump ($tid.' - '.$stid.' - '.$tpid.' - '.$dfrom.' - '.$dto);
    }
}
?>

