<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Elasticsearch\ClientBuilder;
use App\Http\Controllers\GeneralFunctionsController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\TouchpointController;
use Crypt;
use App\Http\Controllers\ActivityLogController;

class SubTopicController extends Controller
{
    public function __construct()
    {
        $this->client = ClientBuilder::create()->setHosts([env('ELASTICSEARCH_HOST').":".env('ELASTICSEARCH_PORT')])->build();
        $this->gen_func_obj = new GeneralFunctionsController();
        $this->topic_obj = new TopicController();
        $this->cus_obj = new CustomerController();
        $this->tp_obj = new TouchpointController();
        $this->ac_log_obj = new ActivityLogController();
        
        //check for user login
        $this->gen_func_obj->validate_access();
        
        $this->loggedin_user_id = \Session::get('_loggedin_customer_id');
        $this->topic_session = \Session::get('current_loaded_project');
    }
    
    public function load_subtopic_view()
    {
        //check for user login
        if(!$this->gen_func_obj->validate_access())
        {
            return redirect('/');
        }
        else
        {
            $touchpoints = $this->tp_obj->get_all_tp_ids();
            $exp_type = $this->get_subtopic_type(\Session::get('current_loaded_subtopic'));
            
            return view('pages.dashboard-subtopic', ['touch_points_data' => $touchpoints, 'tp_obj' => $this->tp_obj, 'st_type' => $exp_type ]);
        }        
    }
    
    public function get_subtopic_data($stid)
    {
        $subt_data = DB::select("SELECT * FROM customer_experience WHERE exp_id = ".$stid);
        
        return $subt_data;
    }
    
    public function get_subtopic_type($stid)
    {
        $subt_data = DB::select("SELECT exp_type FROM customer_experience WHERE exp_id = ".$stid);
        
        return $subt_data[0]->exp_type;
    }
    
    public function get_roi_subtopic_data($stid)
    {
        $roi_data = DB::select("SELECT * FROM roi_settings WHERE roi_cx_id = ".$stid);
        if(count($roi_data) > 0)
            return $roi_data;
        else
           return 'NA';
    }
    
    public function get_subtopic_parent($sid)
    {
        $sdata = DB::select("SELECT exp_topic_id FROM customer_experience WHERE exp_id = ".$sid);
        
        if(count($sdata) > 0)
            return $sdata[0]->exp_topic_id;
    }
    
    public function get_subtopic_keywords_es($sid)
    {
        $key_str = '';
        $exp_data = DB::select("SELECT exp_keywords FROM customer_experience WHERE exp_id = ".$sid);
        
        $keywords = explode(",", $exp_data[0]->exp_keywords);
        
        for ($i = 0; $i < count($keywords); $i ++)
        {
            if (! empty(trim($keywords[$i])))
            {
                $key_str .= '"' . str_replace('"', '', trim($keywords[$i])) . '" OR ';
            }
        }
        
        $key_str = substr($key_str, 0, -4);
        
        return $key_str;
    }

    public function get_subtopic_elastic_query($subtopic_id)
    {
        //check for user login
        //$this->gen_func_obj->validate_access();
        
        $search_str = '';
        $key_str = '';
        $ex_key_str = '';
        $exp_key_exclude = '';
        $exp_sources = '';
        $exp_sources_str = '';
        //dd(\Session::get('current_loaded_project'));
        //$exp_data = DB::select("SELECT * FROM customer_experience WHERE exp_id = ".$subtopic_id." AND exp_uid = ".\Session::get('_loggedin_customer_id'));
        $exp_data = DB::select("SELECT * FROM customer_experience WHERE exp_id = ".$subtopic_id);
        
        $keywords = explode(",", $exp_data[0]->exp_keywords);
        
        for ($i = 0; $i < count($keywords); $i ++)
        {
            if (! empty(trim($keywords[$i])))
            {
                $key_str .= '"' . str_replace('"', '', trim($keywords[$i])) . '" OR ';
            }
        }
        
        $key_str = substr($key_str, 0, -4);
        
        if (isset($exp_data[0]->exp_exclude_keywords) && !empty($exp_data[0]->exp_exclude_keywords))
        {
            $exp_key_exclude = explode(",", $exp_data[0]->exp_exclude_keywords);
            
            for ($i = 0; $i < count($exp_key_exclude); $i++)
            {
                $ex_key_str .= '"' . trim($exp_key_exclude[$i]) . '" OR ';
            }
            
            $ex_key_str = substr($ex_key_str, 0, -4);
        }
        
        $exp_exclude_accounts = '';
        if (isset($exp_data[0]->exp_exclude_accounts) && !empty($exp_data[0]->exp_exclude_accounts))
        {
            $temp_str = "";
            $temp_array = explode(",", $exp_data[0]->exp_exclude_accounts);

            for ($i = 0; $i < count($temp_array); $i++)
            {
                if (!empty($temp_array[$i]))
                {
                    $temp_str .= '"' . trim($temp_array[$i]) . '" OR ';
                }
            }

            $temp_str = substr($temp_str, 0, -4);

            $exp_exclude_accounts = ' AND NOT u_username:(' . $temp_str . ') AND NOT u_source:(' . $temp_str . ') AND NOT u_profile_photo:(' . $temp_str . ')';
        }
        
        if($ex_key_str == '')
            $search_str = '(p_message_text:('.$key_str.') OR u_source:('.$key_str.') OR u_fullname:('.$key_str.'))';
        else 
             $search_str = '(p_message_text:('.$key_str.') OR u_source:('.$key_str.') OR u_fullname:('.$key_str.')) AND NOT p_message_text:(' . $ex_key_str . ')';
        
        if (isset($exp_data[0]->exp_source) && !empty($exp_data[0]->exp_source) && !is_null($exp_data[0]->exp_source))
        {
            $exp_sources = explode(",", $exp_data[0]->exp_source);
            
            for ($i = 0; $i < count($exp_sources); $i++)
            {
                $exp_sources_str .= '"' . trim($exp_sources[$i]) . '" OR ';
            }
            
            $exp_sources_str = substr($exp_sources_str, 0, -4);
        }
        
        //check if sub topic is as customer_experience then only add source to social channels.
        if($exp_data[0]->exp_type == 'cx_monitoring' || $exp_data[0]->exp_type == 'campaign_monitoring')
        {
            if(isset($exp_data[0]->exp_source) && !empty($exp_data[0]->exp_source) && !is_null($exp_data[0]->exp_source))
                $search_str .= ' AND source:('.$exp_sources_str.')';
            else
                $search_str .= ' AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "Pinterest" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Instagram" OR "Facebook")';
        }
        else if($exp_data[0]->exp_type == 'media_monitoring')
        {
            if(isset($exp_data[0]->exp_source) && !empty($exp_data[0]->exp_source) && !is_null($exp_data[0]->exp_source))
                $search_str .= ' AND source:('.$exp_sources_str.')';
            else
                $search_str .= ' AND source:("khaleej_times" OR "Omanobserver" OR "Time of oman" OR "Blogs" OR "FakeNews" OR "News" OR "Web")';
        }
        
        if(!empty($exp_exclude_accounts))
            $search_str = $search_str.$exp_exclude_accounts;
        
        return $search_str;
    }
    
    public function get_subtopic_metrics($st_id)
    {
        $st_metrics = DB::select("SELECT exp_metrics FROM customer_experience WHERE exp_id = ".$st_id);
        
        return $st_metrics;
    }
    
    public function get_revenue_loss_data($st_id)
    {
        $roi_data = DB::select("SELECT * FROM roi_settings WHERE roi_cid = " . $this->cus_obj->get_parent_account_id()." AND roi_cx_id = ".$st_id);
        
        if(count($roi_data) > 0)
            return $roi_data;
        else
            return 'NA';
    }
    
    public function handle_subtopic(Request $request)
    {
        if(isset($request["mode"]) && !empty($request["mode"]))
        {
            if($request["mode"] == 'create_exp')
            {
                $tuid = $this->cus_obj->get_parent_account_id();
                $topic_id = Crypt::decrypt($request["hidden_tid"]);
                
                $exp_metrics = '';
                /*if(count($request["exp_metrics"]) > 0)
                {
                    for($i=0; $i<count($request["exp_metrics"]); $i++)
                    {
                        $exp_metrics .= $request["exp_metrics"][$i].',';
                    }
                }*/
                
                $tdata = DB::select("SELECT exp_id FROM customer_experience WHERE exp_name = '".$this->gen_func_obj->clean_input_data($request["exp_name"])."' AND exp_uid = ".$tuid." AND exp_topic_id = ".$topic_id);
                if(count($tdata) > 0)
                {
                    echo trim('TitleExist');
                }
                else if(stristr($exp_metrics, 'potential_loss') !== FALSE && (empty($request["roi_avg_revenue"]) || $request["roi_avg_revenue"] == 0))
                {
                    echo trim('CLV');
                }
                else
                {
                    //print_r($_POST);
                    $logo_error = false;
                    $new_file_name = 'NA';
                    
                    //topic logo upload
                    if($request->hasFile('sub_topic_logo')) 
                    {
                        $image = $request->file('sub_topic_logo');
                        $file_extension = strtolower($image->getClientOriginalExtension());
                        $path = public_path(). '/images/subtopic_logos/';
                        $new_file_name = md5(time() . $image->getClientOriginalName()) . '.' . $image->getClientOriginalExtension();
                        
                        $allowed_extensions = array('jpg', 'png', 'jpeg');
                        
                        if(in_array($file_extension, $allowed_extensions))
                        {
                            
                            $image->move($path, $new_file_name);
                        }
                        else 
                        {
                            $response = 'Only .jpg, .jpeg and .png files are allowed for logos.';
                            $logo_error = true;
                        }
                    }
                    
                    if($logo_error == false)
                    {
                        $exp_dms = '';
                        
                        if($request["exp_dm"])
                        {
                            if(count($request["exp_dm"]) > 0)
                            {
                                for($i=0; $i<count($request["exp_dm"]); $i++)
                                {
                                    $exp_dms .= $request["exp_dm"][$i].',';
                                }
                            }
                        }
                        
                        //data source
                        $data_source_str = '';
                        
                        if(isset($request["exp_source"]) && count($request["exp_source"]) > 0)
                        {
                            for($i=0; $i<count($request["exp_source"]); $i++)
                            {
                                $data_source_str .= $request["exp_source"][$i].',';
                            }
                        }
                        
                        
                        DB::insert("INSERT INTO customer_experience SET exp_name = '".$this->gen_func_obj->clean_input_data($request["exp_name"])."', exp_uid = ".$tuid.", exp_topic_id = ".$topic_id.", exp_keywords = '".$request["exp_keywords"]."', exp_exclude_keywords = '".$request["exp_exclude_keywords"]."', exp_exclude_accounts = '".$request["exp_exclude_accounts"]."', exp_metrics = '".substr($exp_metrics, 0, -1)."', exp_source = '".substr($data_source_str, 0, -1)."', exp_logo = '".$new_file_name."', exp_detail = '".$this->gen_func_obj->clean_input_data($request["exp_detail"])."', exp_dms = '".substr($exp_dms, 0, -1)."', exp_type = '".$request["exp_type"]."'");
                        
                        $new_subtopic_id = DB::getPdo()->lastInsertId();
                        
                        $this->ac_log_obj->log_customer_data($request, array("cid" => \Session::get('_loggedin_customer_id'), "stid" => $new_subtopic_id));
                        
                        //if(stristr($exp_metrics, 'potential_loss') !== FALSE)
                        if($request["exp_type"] == 'cx_monitoring' && !empty($request["roi_avg_revenue"]))
                        {
                            DB::insert("INSERT INTO roi_settings SET roi_cid = ".$tuid.", roi_currency = '".$request["roi_currency"]."', roi_avg_revenue = '".$request["roi_avg_revenue"]."', roi_churn_rate = ".$request["roi_churn_rate"].", roi_cx_id = ".$new_subtopic_id);
                        }

                        echo trim('Success');
                    }
                    else
                        echo trim($response);
                }
            }
            else if($request["mode"] == 'edit_exp')
            {
                $tuid = $this->cus_obj->get_parent_account_id();
                $subtopic_id = Crypt::decrypt($request["stid"]);
                
                $exp_metrics = '';
                
                /*if(count($request["exp_metrics"]) > 0)
                {
                    for($i=0; $i<count($request["exp_metrics"]); $i++)
                    {
                        $exp_metrics .= $request["exp_metrics"][$i].',';
                    }
                }*/
                
                $tdata = DB::select("SELECT exp_id FROM customer_experience WHERE exp_name = '".$this->gen_func_obj->clean_input_data($request["exp_name"])."' AND exp_uid = ".$tuid." AND exp_topic_id = ".$request["tid"]." AND exp_id != ".$subtopic_id);
                if(count($tdata) > 0)
                {
                    echo trim('TitleExist'); //echo "SELECT exp_id FROM customer_experience WHERE exp_name = '".$this->gen_func_obj->clean_input_data($request["exp_name"])."' AND exp_uid = ".$tuid." AND exp_topic_id = ".$request["tid"]." AND exp_id != ".$subtopic_id;
                }
                else if(stristr($exp_metrics, 'potential_loss') !== FALSE && (empty($request["roi_avg_revenue"]) || $request["roi_avg_revenue"] == 0))
                {
                    echo trim('CLV');
                }
                else
                {
                    $logo_error = false;
                    $new_file_name = 'NA';
                    
                    if(isset($request["old_subtopic_logo"]) && !empty($request["old_subtopic_logo"]))
                        $new_file_name = $request["old_subtopic_logo"];
                    
                    //subtopic logo upload
                    if($request->hasFile('sub_topic_logo')) 
                    {
                        $image = $request->file('sub_topic_logo');
                        $file_extension = strtolower($image->getClientOriginalExtension());
                        $path = public_path(). '/images/subtopic_logos/';
                        $new_file_name = md5(time() . $image->getClientOriginalName()) . '.' . $image->getClientOriginalExtension();
                        
                        $allowed_extensions = array('jpg', 'png', 'jpeg');
                        
                        if(in_array($file_extension, $allowed_extensions))
                        {
                            
                            $image->move($path, $new_file_name);
                        }
                        else 
                        {
                            $response = 'Only .jpg, .jpeg and .png files are allowed for logos.';
                            $logo_error = true;
                        }
                    }
                    
                    if($logo_error == false)
                    {
                        $exp_dms = '';
                        if($request["exp_dm"])
                        {
                            if(count($request["exp_dm"]) > 0)
                            {
                                for($i=0; $i<count($request["exp_dm"]); $i++)
                                {
                                    $exp_dms .= $request["exp_dm"][$i].',';
                                }
                            }
                        }
                        
                        //data source
                        $data_source_str = '';
                        
                        if(isset($request["exp_source"]) && count($request["exp_source"]) > 0)
                        {
                            for($i=0; $i<count($request["exp_source"]); $i++)
                            {
                                $data_source_str .= $request["exp_source"][$i].',';
                            }
                        }
                        
                        
                        DB::update("UPDATE customer_experience SET exp_name = '".$this->gen_func_obj->clean_input_data($request["exp_name"])."', exp_uid = ".$tuid.", exp_keywords = '".$request["exp_keywords"]."', exp_exclude_keywords = '".$request["exp_exclude_keywords"]."', exp_exclude_accounts = '".$request["exp_exclude_accounts"]."', exp_metrics = '".substr($exp_metrics, 0, -1)."', exp_source = '".substr($data_source_str, 0, -1)."', exp_logo = '".$new_file_name."', exp_detail = '".$this->gen_func_obj->clean_input_data($request["exp_detail"])."', exp_dms = '".substr($exp_dms, 0, -1)."', exp_type = '".$request["exp_type"]."' WHERE exp_id = ".$subtopic_id);
                        
                        $this->ac_log_obj->log_customer_data($request, array("cid" => \Session::get('_loggedin_customer_id'), "stid" => $subtopic_id));
                        
                        //if(stristr($exp_metrics, 'potential_loss') !== FALSE)
                        if($request["exp_type"] == 'cx_monitoring' && !empty($request["roi_avg_revenue"]))
                        {
                            $roi_check = DB::select("SELECT roi_id FROM roi_settings WHERE roi_cx_id = ".$subtopic_id);
                            
                            if(count($roi_check) > 0)
                            {
                                DB::update("UPDATE roi_settings SET roi_currency = '".$request["roi_currency"]."', roi_avg_revenue = '".$request["roi_avg_revenue"]."', roi_churn_rate = ".$request["roi_churn_rate"]." WHERE roi_cx_id = ".$subtopic_id." AND roi_id = ".$roi_check[0]->roi_id);
                            }
                            else
                            {
                                DB::insert("INSERT INTO roi_settings SET roi_cid = ".$tuid.", roi_currency = '".$request["roi_currency"]."', roi_avg_revenue = '".$request["roi_avg_revenue"]."', roi_churn_rate = ".$request["roi_churn_rate"].", roi_cx_id = ".$subtopic_id);
                            }
                            
                        }

                        echo trim('Success');
                    }
                    else
                        echo trim($response);
                }
            }
            else if($request["mode"] == 'subtopics_list_json')
            {
                if(isset($request["tid"]) && !empty($request["tid"]))
                {
                    $st_data = DB::select("SELECT exp_id AS eid, exp_name AS ename FROM customer_experience WHERE exp_topic_id = ".Crypt::decrypt($request["tid"]));
                    if(count($st_data) > 0)
                    {
                        echo json_encode($st_data);
                    }
                }
            }
        }
    }
}
?>

