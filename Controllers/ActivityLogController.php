<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\GeneralFunctionsController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\SubTopicController;
use App\Http\Controllers\TouchpointController;
use App\Http\Controllers\CompetitorAnalysisController;

class ActivityLogController extends Controller
{
    public function load_activity_log()
    {
        $activity_log = '';
        $this->cus_obj = new CustomerController();
        $this->topic_obj = new TopicController();
        $this->stopic_obj = new SubTopicController();
        $this->tp_obj = new TouchpointController();
        $this->ca_obj = new CompetitorAnalysisController();
        
        $logged_cus_id = $this->cus_obj->get_parent_account_id();
        
        if($logged_cus_id == \Session::get('_loggedin_customer_id') || \Session::get('_loggedin_customer_id') == 301)
        {
            $sub_accounts = $this->cus_obj->get_customer_sub_account($logged_cus_id);
            $ids = $logged_cus_id;
            
            if(count($sub_accounts) > 0)
            {
                for($i=0; $i<count($sub_accounts); $i++)
                {
                    $ids .= ','.$sub_accounts[$i]->customer_id;
                }
                
                $activity_log = DB::select("SELECT * FROM activity_log WHERE al_cid IN (".$ids.") ORDER BY al_id DESC LIMIT 200");
            }
            else
                $activity_log = DB::select("SELECT * FROM activity_log WHERE al_cid IN (".$logged_cus_id.") ORDER BY al_id DESC LIMIT 200");
            
            return view('pages.activity-log', ['activity_log' => $activity_log, 'cus_obj' => $this->cus_obj, 'topic_obj' => $this->topic_obj, 'stopic_obj' => $this->stopic_obj, 'tp_obj' => $this->tp_obj, 'ca_obj' => $this->ca_obj]);
        }
        else
            return redirect('/topic-settings');
    }
    
    public function log_customer_data($request, $params)
    {
        if(isset($request["mode"]) && !empty($request["mode"]))
        {
            if($request["mode"] == 'validate_login')
                DB::insert("INSERT INTO activity_log SET al_cid = ".$params["cid"].", al_message = 'Logged into dashboard.'");
            else if($request["mode"] == 'create_topic')
                DB::insert("INSERT INTO activity_log SET al_cid = ".$params["cid"].", al_tid = ".$params["tid"].", al_message = 'Created a new dashboard'");
            else if($request["mode"] == 'edit_topic')
                DB::insert("INSERT INTO activity_log SET al_cid = ".$params["cid"].", al_tid = ".$params["tid"].", al_message = 'Updated dashboard'");
            else if($request["mode"] == 'create_exp')
                DB::insert("INSERT INTO activity_log SET al_cid = ".$params["cid"].", al_stid = ".$params["stid"].", al_message = 'Added in-depth analysis'");
            else if($request["mode"] == 'edit_exp')
                DB::insert("INSERT INTO activity_log SET al_cid = ".$params["cid"].", al_stid = ".$params["stid"].", al_message = 'Updated in-depth analysis'");
            else if($request["mode"] == 'create_tp')
                DB::insert("INSERT INTO activity_log SET al_cid = ".$params["cid"].", al_tpid = ".$params["tpid"].", al_message = 'New touchpoint created'");
            else if($request["mode"] == 'edit_tp')
                DB::insert("INSERT INTO activity_log SET al_cid = ".$params["cid"].", al_tpid = ".$params["tpid"].", al_message = 'Touchpoint updated'");
            else if($request["mode"] == 'delete_record_handler')
            {
                if($request["section"] == 'touchpoint')
                    DB::insert("INSERT INTO activity_log SET al_cid = ".$params["cid"].", al_message = 'Touchpoint (".$params["tp_name"].") deleted.'");
                else if($request["section"] == 'subtopic')
                    DB::insert("INSERT INTO activity_log SET al_cid = ".$params["cid"].", al_message = 'In-depth analysis (".$params["st_name"].") deleted.'");
                else if($request["section"] == 'maintopic')
                    DB::insert("INSERT INTO activity_log SET al_cid = ".$params["cid"].", al_message = 'Dashboard (".$params["t_name"].") deleted.'");
                else if($request["section"] == 'comp_analysis')
                    DB::insert("INSERT INTO activity_log SET al_cid = ".$params["cid"].", al_message = 'Competitor analysis (".$params["caname"].") deleted.'");
            }
            else if($request["mode"] == 'save_topic_id')
            {
                if($request["topic_type"] == 'maintopic')
                    DB::insert("INSERT INTO activity_log SET al_cid = ".$params["cid"].", al_tid = ".$params["tid"].", al_message = 'Dashboard (".$params["tname"].") loaded'");
                else if($request["topic_type"] == 'subtopic')
                    DB::insert("INSERT INTO activity_log SET al_cid = ".$params["cid"].", al_stid = ".$params["stid"].", al_message = 'In-depth analysis (".$params["stname"].") loaded'");
                else if($request["topic_type"] == 'comp_analysis')
                    DB::insert("INSERT INTO activity_log SET al_cid = ".$params["cid"].", al_ca_id = ".$params["caid"].", al_message = 'Competitor analysis (".$params["caname"].") loaded'");
            }
            else if($request["mode"] == 'create_competitor_analysis')
            {
                DB::insert("INSERT INTO activity_log SET al_cid = ".$params["cid"].", al_ca_id = ".$params["caid"].", al_message = 'Competitor analysis (".$params["caname"].") created'");
            }
        }
        else if(isset($params["logout"]) && $params["logout"] == 'yes')
        {
            if(isset($params["cid"]) && !empty($params["cid"]))
                DB::insert("INSERT INTO activity_log SET al_cid = ".$params["cid"].", al_message = 'Logged out from dashboard.'");
        }
    }
}
?>

