<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Elasticsearch\ClientBuilder;
use App\Http\Controllers\GeneralFunctionsController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SubTopicController;
use App\Http\Controllers\TouchpointController;
use Crypt;
use App\Http\Controllers\ActivityLogController;

class TopicController extends Controller
{
    public function __construct()
    {
        //define('DATA_FETCH_FROM_TIME', env('DATA_FETCH_FROM_TIME'));
        //define('DATA_FETCH_TO_TIME', env('DATA_FETCH_TO_TIME'));
        $this->client = ClientBuilder::create()->setHosts([env('ELASTICSEARCH_HOST').":".env('ELASTICSEARCH_PORT')])->build();
        $this->gen_func_obj = new GeneralFunctionsController();
        $this->cus_obj = new CustomerController();
        $this->ac_log_obj = new ActivityLogController();
    }

    public function load_topic_settings_page(Request $request)
    {
        //check for user login
        if(!$this->gen_func_obj->validate_access())
        {
            return redirect('/');
        }
        
        //$loggedin_user_id = \Session::get('_loggedin_customer_id');
        $loggedin_user_id = $this->cus_obj->get_parent_account_id();
         
        $this->topic_obj = new TopicController();

        //Fetch topics of respective user.
        $topics_data = DB::select("SELECT * FROM customer_topics WHERE customer_portal = 'D24' AND topic_is_deleted != 'Y' AND topic_user_id = " . $loggedin_user_id . " ORDER BY topic_order ASC");
        
        //Fetch country list
        $country_data = DB::select("SELECT country_name FROM countries_list ORDER BY country_name");
        
        //main topic data
        $edit_topic_obj = NULL;
        $show_edit_topic = 'no';
        
        if(isset($request["tid"]) && !empty($request["tid"])) //case if edit topic is being loaded
        {
            $e_topic = DB::select("SELECT * FROM customer_topics WHERE customer_portal = 'D24' AND topic_is_deleted != 'Y' AND topic_user_id = " . $loggedin_user_id . " AND topic_id = ".Crypt::decrypt($request["tid"]));
            
            if(count($e_topic) > 0)
            {
                $edit_topic_obj = $e_topic;
                $show_edit_topic = 'yes';
            }
        }
        
        //sub topic data
        $edit_subtopic_obj = NULL;
        $show_edit_subtopic = 'no';
        
        $this->subtopic_obj = new SubTopicController();
        
        if(isset($request["stid"]) && !empty($request["stid"])) //case if edit sub topic is being loaded
        {
            $subtopic = $this->subtopic_obj->get_subtopic_data(Crypt::decrypt($request["stid"]));
            
            if(count($subtopic) > 0)
            {
                $edit_subtopic_obj = $subtopic;
                $show_edit_subtopic = 'yes';
            }
        }
        
        //touch point data
        $edit_touchpoint_obj = NULL;
        $show_edit_touchpoint = 'no';
        
        $this->touchpoint_obj = new TouchpointController();
        
        if(isset($request["tpid"]) && !empty($request["tpid"])) //case if edit touchpoint is being loaded
        {
            $touchpoint = $this->touchpoint_obj->get_touchpoint_data(Crypt::decrypt($request["tpid"]));
                        
            if(count($touchpoint) > 0)
            {
                $edit_touchpoint_obj = $touchpoint;
                $show_edit_touchpoint = 'yes';
            }
        }

        //return view('pages.topic-settings', compact('topics_data'));
        return view('pages.topic-settings', ['topics_data' => $topics_data, 'topic_obj' => $this, 'allowed_topics' => $this->cus_obj->get_allowed_topics(), 'created_topics' => $this->cus_obj->get_created_topics(), 'country_data' => $country_data, 'show_edit_topic' => $show_edit_topic, 'edit_topic_data' => $edit_topic_obj, 'show_edit_subtopic' => $show_edit_subtopic, 'edit_subtopic_data' => $edit_subtopic_obj, 'subtopic_obj' => $this->subtopic_obj, 'show_edit_tp' => $show_edit_touchpoint, 'touchpoint_data' => $edit_touchpoint_obj, 'touchpoint_obj' => $this->touchpoint_obj ]);
    }
    
    public function get_topic_name($tid)
    {
        $tname = DB::select("SELECT topic_title FROM customer_topics WHERE topic_id = ".$tid);
        
        return $tname[0]->topic_title;
    }
    
    public function get_topics_list($user_id)
    {
        $topics_data = DB::select("SELECT * FROM customer_topics WHERE customer_portal = 'D24' AND topic_is_deleted != 'Y' AND topic_user_id = ".$user_id." ORDER BY topic_title");
        
        return $topics_data;
    }
    
    public function get_topic_hash_keywords($tid)
    {
        //$t_key_hash = DB::select("SELECT topic_hash_tags, topic_keywords FROM customer_topics WHERE topic_id = ".$tid." AND topic_user_id = ".$this->cus_obj->get_parent_account_id());
        
        $t_key_hash = DB::select("SELECT topic_hash_tags, topic_keywords FROM customer_topics WHERE topic_id = ".$tid);
        return $t_key_hash;
    }
    
    public function get_topic_hash_keywords_urls_str($tid)
    {
        $in_val = '';
        
        $topic_data = DB::select("SELECT topic_hash_tags, topic_keywords, topic_urls FROM customer_topics WHERE topic_id = ".$tid);
        
        if(!empty($topic_data[0]->topic_hash_tags))
        {
            $htags = explode("|", $topic_data[0]->topic_hash_tags);
        
            for ($i = 0; $i < count($htags); $i ++)
            {
                if (! empty(trim($htags[$i])))
                    $in_val .= trim($htags[$i]).",";
            }
        }        
        
        if(!empty($topic_data[0]->topic_keywords))
        {
            $keywords = explode(",", $topic_data[0]->topic_keywords);
        
            for ($i = 0; $i < count($keywords); $i ++)
            {
                if (! empty(trim($keywords[$i])))
                    $in_val .= trim($keywords[$i]).",";
            }
        }
        
        
        if(!empty($topic_data[0]->topic_urls))
    	{
    	    $t_urls = explode("|", $topic_data[0]->topic_urls);
                
            for ($i = 0; $i < count($t_urls); $i ++)
            {
                if (! empty(trim($t_urls[$i])))
                    $in_val .= trim($t_urls[$i]).",";
            }
    	}
    	
    	return substr($in_val, 0, -1);
    }
    
    public function get_sub_topics($tid)
    {
        //$sub_topic_obj = DB::select("SELECT * FROM customer_experience WHERE exp_topic_id = ".$tid." AND exp_uid = ".$this->cus_obj->get_parent_account_id());
        //First check if there are any selected subtopics to which account has access to
        $chkq = DB::select("SELECT customer_subtopics_access FROM customers WHERE customer_id = ".\Session::get('_loggedin_customer_id'));
        
        if(isset($chkq[0]->customer_subtopics_access) && !is_null($chkq[0]->customer_subtopics_access) && !empty($chkq[0]->customer_subtopics_access))
            $sub_topic_obj = DB::select("SELECT * FROM customer_experience WHERE exp_id IN (".$chkq[0]->customer_subtopics_access.") AND exp_topic_id = ".$tid);
        else
            $sub_topic_obj = DB::select("SELECT * FROM customer_experience WHERE exp_topic_id = ".$tid);
        
        
        return $sub_topic_obj;
    }
    
    public function get_subtopics_list($tid) //called from ggfr.blade
    {
        $sub_topic_obj = DB::select("SELECT * FROM customer_experience WHERE exp_topic_id = ".$tid);
        
        return $sub_topic_obj;
    }
    
    public function get_all_touchpoints($tid)
    {
        $touch_points_ids = array();
        //Get all topics and then sub topics and then touch points under sub topics
        ///$tpc_data = DB::select("SELECT topic_id FROM customer_topics WHERE customer_portal = 'D24' AND topic_is_deleted != 'Y' AND topic_user_id = " . $this->cus_obj->get_parent_account_id() . " ORDER BY topic_id DESC");
        
        //for($i=0; $i<count($tpc_data); $i++)
        //{
            $stp_data = DB::select("SELECT exp_id FROM customer_experience WHERE exp_topic_id = ".$tid);
            
            if(count($stp_data) > 0)
            {
                for($j=0; $j<count($stp_data); $j++)
                {
                    $tp_data = DB::select("SELECT cx_tp_tp_id FROM cx_touch_points WHERE cx_tp_cx_id = ".$stp_data[$j]->exp_id);
                    if(count($tp_data) > 0)
                    {
                        for($l=0; $l<count($tp_data); $l++)
                        {
                            if(!in_array($tp_data[$l]->cx_tp_tp_id, $touch_points_ids))
                                $touch_points_ids[] = $tp_data[$l]->cx_tp_tp_id;
                        }
                        
                        //$touch_points_ids[] = '22';
                    }
                }
            }            
        //}
        
        return $touch_points_ids;
    }
    
    public function get_touchpoint_data($touch_id)
    {
        $touchpoint_data = DB::select("SELECT * FROM touch_points WHERE tp_id = ".$touch_id);
        
        return $touchpoint_data;
    }
    
    public function handle_topic(Request $request)
    {
        if(isset($request["mode"]) && !empty($request["mode"]))
        {
            if($request["mode"] == 'create_topic')
            {
                $tuid = $this->cus_obj->get_parent_account_id();
                
                $tdata = DB::select("SELECT topic_id FROM customer_topics WHERE customer_portal = 'D24' AND topic_title = '".addslashes($request["topic_title"])."' AND topic_user_id = ".$tuid);
                if(count($tdata) > 0)
                {
                    echo 'You already have a topic with same topic. Choose another title.';
                }
                else
                {
                    $hashtag_str = "";
                    $keywords_str = "";
                    $urls_str = "";
                    $data_source_str = "";
                    $data_location_str = "";
                    $data_lang_str = "";
                    $exclude_words_str = "";
                    $exclude_accounts_str = "";
                    $google_maps_url = "";
                    $logo_error = false;
                    $new_file_name = 'NA';
                    
                    //$file->getClientOriginalName();
                    //$file->getClientOriginalExtension();
                    //$file->getRealPath();
                    //$file->getSize();
                    //$file->getMimeType();
                    
                    //topic logo upload
                    if($request->hasFile('topic_logo')) 
                    {
                        $image = $request->file('topic_logo');
                        $file_extension = strtolower($image->getClientOriginalExtension());
                        $path = public_path(). '/images/topic_logos/';
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
                        //#hashtags & keywords
                        $hash_key_str = explode(",", $request["topic_hash_keywords"]);

                        for($i=0; $i<count($hash_key_str); $i++)
                        {
                            if(trim(substr($hash_key_str[$i], 0, 1)) == '#')
                            {
                                $hashtag_str .= $hash_key_str[$i].'|';
                            }
                            else 
                            {
                                $keywords_str .= trim($hash_key_str[$i]).',';
                            }
                        }

                        //urls
                        $url_str = explode(",", $request["topic_url"]);

                        for($i=0; $i<count($url_str); $i++)
                        {
                            if(isset($url_str[$i]) && !empty($url_str[$i]))
                            {
                                $url = trim($url_str[$i]);
                                $urls_str .= $url.'|';
                            }
                        }

                        //exclude words
                        $exclude_words = explode(",", $request["exclude_key_hash"]);

                        for($i=0; $i<count($exclude_words); $i++)
                        {
                            if(isset($exclude_words[$i]) && !empty($exclude_words[$i]))
                            {
                                $exclude_words_str .= trim($exclude_words[$i]).',';
                            }
                        }

                        //exclude accounts
                        $exclude_accounts = explode(",", $request["exclude_accounts"]);

                        for($i=0; $i<count($exclude_accounts); $i++)
                        {
                            if(isset($exclude_accounts[$i]) && !empty($exclude_accounts[$i]))
                            {
                                $exclude_accounts_str .= trim($exclude_accounts[$i]).',';
                            }
                        }

                        //data source
                        if(isset($request["data_source"]) && count($request["data_source"]) > 0)
                        {
                            for($i=0; $i<count($request["data_source"]); $i++)
                            {
                                $data_source_str .= $request["data_source"][$i].',';
                            }
                        }

                        //data location
                        if(isset($request["data_location"]) && count($request["data_location"]) > 0)
                        {
                            for($i=0; $i<count($request["data_location"]); $i++)
                            {
                                $data_location_str .= $request["data_location"][$i].',';
                            }
                        }

                        //data language
                        if(isset($request["data_lang"]) && count($request["data_lang"]) > 0)
                        {
                            for($i=0; $i<count($request["data_lang"]); $i++)
                            {
                                $data_lang_str .= $request["data_lang"][$i].',';
                            }
                        }
                        
                        if(isset($request["topic_gmaps_url"]) && !empty($request["topic_gmaps_url"]))
                        {
                            $google_maps_url = $request["topic_gmaps_url"];
                        }

                        $hashtag_str = substr($hashtag_str, 0, -1);
                        $keywords_str = substr($keywords_str, 0, -1);
                        $urls_str = substr($urls_str, 0, -1);
                        $data_source_str = substr($data_source_str, 0, -1);
                        $data_location_str = substr($data_location_str, 0, -1);
                        $data_lang_str = substr($data_lang_str, 0, -1);
                        $exclude_words_str = substr($exclude_words_str, 0, -1);
                        $exclude_accounts_str = substr($exclude_accounts_str, 0, -1);

                        $insert = DB::insert("INSERT INTO customer_topics (topic_title, topic_hash_tags, topic_urls, topic_user_id, topic_created_at, topic_updated_at, topic_keywords, topic_is_deleted, topic_exclude_words, topic_exclude_accounts, topic_data_source, topic_data_location, topic_data_lang, topic_is_premium, customer_portal, customer_sub_account_id, topic_logo, topic_gmaps_url) VALUES ('".addslashes($_POST["topic_title"])."', '".addslashes($hashtag_str)."', '".addslashes($urls_str)."', ".$tuid.", NOW(), NOW(), '".addslashes($keywords_str)."', 'N', '".$exclude_words_str."', '".$exclude_accounts_str."', '".$data_source_str."', '".$data_location_str."', '".$data_lang_str."', 'N', 'D24', ".\Session::get('_loggedin_customer_id').", '".$new_file_name."', '".addslashes($google_maps_url)."')");
                        
                        $new_topic_id = DB::getPdo()->lastInsertId();
                        
                        //log activity
                        $this->ac_log_obj->log_customer_data($request, array("cid" => \Session::get('_loggedin_customer_id'), "tid" => $new_topic_id));
                        
                        if(!empty($google_maps_url))
                        {
                            $urls_str .= "|".$google_maps_url;
                        }
                        
                        $post_fields = array(
                            'id' => $new_topic_id,
                            'hashtags' => $hashtag_str,
                            'urls' => $urls_str,
                            'topic' => $request["topic_title"],
                            'keywords' => $keywords_str
                        );                                            

                        $fields_string = http_build_query($post_fields);

                        $headers = array(
                            "Accept: */*",
                            "Cache-Control: no-cache",
                            "Pragma: no-cache",
                            "Authorization: Token 237e47a1ce5c07d65926c89d056c5ca357c6f4ac"
                        );

                        /*$ch = curl_init();
                        curl_setopt($ch,CURLOPT_URL, 'http://35.222.163.30:5800/crawl/');
                        curl_setopt($ch,CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
                        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
                        $result = curl_exec($ch);
                        $err = curl_error($ch);

                        curl_close($ch);*/
                        //echo $result; echo $err; exit;
                        ////////////////////////////////////////////////////////////////////////////////////////////
                        /*if($result == 'Success')
                            $response = 'Success';
                        else
                            $response = $result;
                        
                        echo trim($result);*/
                        echo trim('Success');
                    }
                    else
                        echo $response;
                }
            }
            else if($request["mode"] == 'edit_topic')
            {
                $tuid = $this->cus_obj->get_parent_account_id();
                $topic_id = Crypt::decrypt($request["tid"]);
                
                $tdata = DB::select("SELECT topic_id FROM customer_topics WHERE customer_portal = 'D24' AND topic_title = '". addslashes($request["topic_title"])."' AND topic_user_id = ".$tuid." AND topic_id != ".$topic_id);
                
                if(count($tdata) > 0)
                {
                    echo 'You already have a topic with same topic. Choose another title.';
                }
                else
                {
                    $hashtag_str = "";
                    $keywords_str = "";
                    $urls_str = "";
                    $data_source_str = "";
                    $data_location_str = "";
                    $data_lang_str = "";
                    $exclude_words_str = "";
                    $exclude_accounts_str = "";
                    $google_maps_url = "";
                    $logo_error = false;
                    $new_file_name = 'NA';
                    
                    if(isset($request["old_topic_logo"]) && !empty($request["old_topic_logo"]))
                        $new_file_name = $request["old_topic_logo"];
                                        
                    //topic logo upload
                    if($request->hasFile('topic_logo')) 
                    {
                        $image = $request->file('topic_logo');
                        $file_extension = strtolower($image->getClientOriginalExtension());
                        $path = public_path(). '/images/topic_logos/';
                        $new_file_name = md5(time() . $image->getClientOriginalName()) . '.' . $image->getClientOriginalExtension();
                        
                        $allowed_extensions = array('jpg', 'png', 'jpeg');
                        
                        if(in_array($file_extension, $allowed_extensions))
                        {
                            if(file_exists(public_path().'/images/topic_logos/'.$request["old_topic_logo"]) && is_file(public_path().'/images/topic_logos/'.$request["old_topic_logo"]))
                            {
                                unlink(public_path().'/images/topic_logos/'.$request["old_topic_logo"]);
                            }
                            
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
                        //#hashtags & keywords
                        $hash_key_str = explode(",", $request["topic_hash_keywords"]);

                        for($i=0; $i<count($hash_key_str); $i++)
                        {
                            if(trim(substr($hash_key_str[$i], 0, 1)) == '#')
                            {
                                $hashtag_str .= $hash_key_str[$i].'|';
                            }
                            else 
                            {
                                $keywords_str .= trim($hash_key_str[$i]).',';
                            }
                        }

                        //urls
                        $url_str = explode(",", $request["topic_url"]);

                        for($i=0; $i<count($url_str); $i++)
                        {
                            if(isset($url_str[$i]) && !empty($url_str[$i]))
                            {
                                $url = trim($url_str[$i]);
                                $urls_str .= $url.'|';
                            }
                        }

                        //exclude words
                        $exclude_words = explode(",", $request["exclude_key_hash"]);

                        for($i=0; $i<count($exclude_words); $i++)
                        {
                            if(isset($exclude_words[$i]) && !empty($exclude_words[$i]))
                            {
                                $exclude_words_str .= trim($exclude_words[$i]).',';
                            }
                        }

                        //exclude accounts
                        $exclude_accounts = explode(",", $request["exclude_accounts"]);

                        for($i=0; $i<count($exclude_accounts); $i++)
                        {
                            if(isset($exclude_accounts[$i]) && !empty($exclude_accounts[$i]))
                            {
                                $exclude_accounts_str .= trim($exclude_accounts[$i]).',';
                            }
                        }

                        //data source
                        if(isset($request["data_source"]) && count($request["data_source"]) > 0)
                        {
                            for($i=0; $i<count($request["data_source"]); $i++)
                            {
                                $data_source_str .= $request["data_source"][$i].',';
                            }
                        }

                        //data location
                        if(isset($request["data_location"]) && count($request["data_location"]) > 0)
                        {
                            for($i=0; $i<count($request["data_location"]); $i++)
                            {
                                $data_location_str .= $request["data_location"][$i].',';
                            }
                        }

                        //data language
                        if(isset($request["data_lang"]) && count($request["data_lang"]) > 0)
                        {
                            for($i=0; $i<count($request["data_lang"]); $i++)
                            {
                                $data_lang_str .= $request["data_lang"][$i].',';
                            }
                        }
                        
                        if(isset($request["topic_gmaps_url"]) && !empty($request["topic_gmaps_url"]))
                        {
                            $google_maps_url = $request["topic_gmaps_url"];
                        }

                        $hashtag_str = substr($hashtag_str, 0, -1);
                        $keywords_str = substr($keywords_str, 0, -1);
                        $urls_str = substr($urls_str, 0, -1);
                        $data_source_str = substr($data_source_str, 0, -1);
                        $data_location_str = substr($data_location_str, 0, -1);
                        $data_lang_str = substr($data_lang_str, 0, -1);
                        $exclude_words_str = substr($exclude_words_str, 0, -1);
                        $exclude_accounts_str = substr($exclude_accounts_str, 0, -1);

                                        
                        DB::update("UPDATE customer_topics SET topic_title = '".addslashes($request["topic_title"])."', topic_hash_tags = '".addslashes($hashtag_str)."', topic_urls = '".addslashes($urls_str)."', topic_updated_at = NOW(), topic_keywords = '".addslashes($keywords_str)."', topic_exclude_words = '".$exclude_words_str."', topic_exclude_accounts = '".$exclude_accounts_str."', topic_data_source = '".$data_source_str."', topic_data_location = '".$data_location_str."', topic_data_lang = '".$data_lang_str."', topic_logo = '".$new_file_name."', topic_gmaps_url = '".addslashes($google_maps_url)."' WHERE customer_portal = 'D24' AND topic_id = ".$topic_id." AND topic_user_id = ".$tuid);
                        
                        $this->ac_log_obj->log_customer_data($request, array("cid" => \Session::get('_loggedin_customer_id'), "tid" => $topic_id));
                        
                        if(!empty($google_maps_url))
                        {
                            $urls_str .= "|".$google_maps_url;
                        }
                        
                        $post_fields = array(
                            'id' => $topic_id,
                            'hashtags' => $hashtag_str,
                            'urls' => $urls_str,
                            'topic' => $request["topic_title"],
                            'keywords' => $keywords_str
                        );                                            

                        $fields_string = http_build_query($post_fields);

                        $headers = array(
                            "Accept: */*",
                            "Cache-Control: no-cache",
                            "Pragma: no-cache",
                            "Authorization: Token 237e47a1ce5c07d65926c89d056c5ca357c6f4ac"
                        );

                        /*$ch = curl_init();
                        curl_setopt($ch,CURLOPT_URL, 'http://35.222.163.30:5800/crawl/');
                        curl_setopt($ch,CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
                        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
                        $result = curl_exec($ch);
                        $err = curl_error($ch);

                        curl_close($ch);*/
                        //echo $result; echo $err; exit;
                        ////////////////////////////////////////////////////////////////////////////////////////////
                        //if($result == 'Success')
                            //$response = 'Success';
                        //else
                            //$response = $result;
                        
                        echo trim('Success');
                    }
                    else
                        echo $response;
                }
            }
            else if($request["mode"] == 'delete_record_handler')
            {
                $tuid = $this->cus_obj->get_parent_account_id();
                
                if($request["section"] == 'maintopic')
                {
                    $t_name = $this->get_topic_name($request["record_id"]);
                    //Need to add further option of deleting sub topic & experiences if any
                    //Also delete topic logo
                    DB::delete("UPDATE customer_topics SET topic_is_deleted = 'Y' WHERE customer_portal = 'D24' AND topic_user_id = ".$tuid." AND topic_id = ".$request["record_id"]);
                    
                    $this->ac_log_obj->log_customer_data($request, array("cid" => \Session::get('_loggedin_customer_id'), "t_name" => $t_name));
                    
                    echo 'Success';
                }
                else if($request["section"] == 'subtopic')
                {
                    $this->subtopic_obj = new SubTopicController();
                    $st_name = $this->subtopic_obj->get_subtopic_data($request["record_id"]);
                    
                    //Delete all touch points under this experience / sub topic
                    DB::delete("DELETE FROM customer_experience WHERE exp_uid = ".$tuid." AND exp_id = ".$request["record_id"]);
                    
                    $this->ac_log_obj->log_customer_data($request, array("cid" => \Session::get('_loggedin_customer_id'), "st_name" => $st_name[0]->exp_name));
                    
                    echo 'Success';
                }
                else if($request["section"] == 'touchpoint')
                {
                    $this->touchpoint_obj = new TouchpointController();
                    $tp_data = $this->touchpoint_obj->get_touchpoint_data($request["record_id"]);
                    
                    DB::delete("DELETE FROM touch_points WHERE tp_uid = ".$tuid." AND tp_id = ".$request["record_id"]);
                    
                    DB::delete("DELETE FROM cx_touch_points WHERE cx_tp_tp_id = ".$request["record_id"]);
                    
                    $this->ac_log_obj->log_customer_data($request, array("cid" => \Session::get('_loggedin_customer_id'), "tp_name" => $tp_data[0]->tp_name));
                    
                    echo 'Success';
                }
            }
        }
        else
        {
            echo 'InvalidAccess';
        }
    }
    
    public function get_allowed_topics()
    {
        $cdata = DB::select("SELECT customer_email, customer_account_parent FROM customers WHERE customer_id = ".\Session::get('_loggedin_customer_id'));
        if($pkg == 'IS') //invidted signup get parent account topics limit
        {
            $q = "SELECT customer_account_parent FROM customers WHERE customer_email = '".$_SESSION["_email"]."'";
            $r = run_query($q);
            $d = mysqli_fetch_array($r);
            
            $chk_query = "SELECT customer_allowed_topics FROM customers WHERE customer_email = '".$d["customer_account_parent"]."'";
            $chk_result = run_mysql_query($chk_query);
            $chk_data = mysqli_fetch_array($chk_result);
            
            return decrypt($chk_data["customer_allowed_topics"], EK);
        }
        else
        {
            $chk_query = "SELECT customer_allowed_topics FROM customers WHERE customer_email = '".$_SESSION["_email"]."'";
            $chk_result = run_mysql_query($chk_query);
            $chk_data = mysqli_fetch_array($chk_result);
            
            return decrypt($chk_data["customer_allowed_topics"], EK);
        }
    }

    public function get_topic_total_es_results($topic_id)
    {
        $greater_than_time = env('DATA_FETCH_FROM_TIME');
        $less_than_time = env('DATA_FETCH_TO_TIME');

        $topic_query_string = $this->topic_obj->get_topic_elastic_query($topic_id);

        $params = [
            'index' => env('ELASTICSEARCH_DEFAULTINDEX'),
            'type' => 'mytype',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [ 'query_string' => [ 'query' => $topic_query_string ] ],
                            [ 'range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time ]]]
                        ]
                    ]
                ]
            ]
        ];
        //echo '<pre>';
        //print_r($params);
        //echo '</pre>';
        $es_data = $this->client->count($params);

        return $this->gen_func_obj->format_number_data($es_data["count"]).'|'.$es_data["count"]; //It returns formated | non formated count
    }

    public function get_topic_elastic_query($topic_id)
    {
        $in_val = '';
        $tpk_urls = '';
        $search_str = '';
        //dd("SELECT * FROM customer_topics WHERE topic_id = ".$topic_id);
        $topic_data = DB::select("SELECT * FROM customer_topics WHERE topic_id = ".$topic_id);
        
        $htags = explode("|", $topic_data[0]->topic_hash_tags);
        
        for ($i = 0; $i < count($htags); $i ++)
        {
            if (! empty(trim($htags[$i])))
            {
                $in_val = $in_val . "'" . trim($htags[$i]) . "',";
            }
        }
        
        $keywords = explode(",", $topic_data[0]->topic_keywords);
        
        for ($i = 0; $i < count($keywords); $i ++)
        {
            if (! empty(trim($keywords[$i])))
            {
                $in_val = $in_val . "'" . trim($keywords[$i]) . "',";
            }
        }
        
        if(!empty($topic_data[0]->topic_urls))
    	{
    	    $t_urls = explode("|", $topic_data[0]->topic_urls);
                
            for ($i = 0; $i < count($t_urls); $i ++)
            {
                if (! empty(trim($t_urls[$i])))
                {
                    $in_val = $in_val . "'" . trim($t_urls[$i]) . "',";
                    $tpk_urls .= '"' . trim($t_urls[$i]) . '" OR ';
                }
            }
    	}
        
        
        $search_str = substr($in_val, 0, - 1);
        $search_str = str_replace("'", "", $search_str);
        $str_array = explode(",", $search_str);

        $str_to_search = '';

        for ($i = 0; $i < count($str_array); $i ++)
        {
            $str_to_search .= '"' . trim($str_array[$i]) . '" OR ';
        }

        if (!empty($tpk_urls)) //!empty($topic_data[0]->topic_urls)
        {
            //$str_to_search = '(p_message_text:(' . substr($str_to_search, 0, -4) . ') OR u_source:(' . substr($tpk_urls, 0, -4) . ') OR p_url:(' . substr($tpk_urls, 0, -4) . '))';
            /*if($topic_id == 2296)
            {
                $str_to_search = '(p_message_text:(' . substr($str_to_search, 0, -4) . ') OR u_source:(' . substr($tpk_urls, 0, -4) . ') OR p_url:(' . substr($tpk_urls, 0, -4) . ') OR u_fullname:("Oman Air"))';
            }
            else*/
            //a test
            $str_to_search = '(p_message_text:(' . substr($str_to_search, 0, -4) . ') OR u_fullname:(' . substr($str_to_search, 0, -4) . ') OR u_source:(' . substr($tpk_urls, 0, -4) . ') OR p_url:(' . substr($tpk_urls, 0, -4) . '))';
        }
        else
        {
            if (!empty($topic_data[0]->topic_gmaps_url) && !is_null($topic_data[0]->topic_gmaps_url))
                $str_to_search = '(p_message_text:(' . substr($str_to_search, 0, -4) . ') OR place_url:("' . $topic_data[0]->topic_gmaps_url . '"))';
            else
                $str_to_search = 'p_message_text:(' . substr($str_to_search, 0, -4) . ')';
        }
        
        //Fetch filtered topic data if any
        if (!empty($topic_data[0]->topic_exclude_words))
        {
            $temp_str = "";
            $temp_array = explode(",", $topic_data[0]->topic_exclude_words);

            for ($i = 0; $i < count($temp_array); $i++)
            {
                if (!empty($temp_array[$i]))
                {
                    $temp_str .= '"' . $temp_array[$i] . '" OR ';
                }
            }

            $temp_str = substr($temp_str, 0, -4);

            $str_to_search = $str_to_search . ' AND NOT p_message_text:(' . $temp_str . ')';
        }

        if (!empty($topic_data[0]->topic_exclude_accounts))
        {
            $temp_str = "";
            $temp_array = explode(",", $topic_data[0]->topic_exclude_accounts);

            for ($i = 0; $i < count($temp_array); $i++)
            {
                if (!empty($temp_array[$i]))
                {
                    $temp_str .= '"' . $temp_array[$i] . '" OR ';
                }
            }

            $temp_str = substr($temp_str, 0, -4);

            $str_to_search = $str_to_search . ' AND NOT u_username:(' . $temp_str . ') AND NOT u_source:(' . $temp_str . ')';
        }

        if (!empty($topic_data[0]->topic_data_source))
        {
            $temp_str = "";
            $temp_array = explode(",", $topic_data[0]->topic_data_source);

            for ($i = 0; $i < count($temp_array); $i++)
            {
                $temp_str .= '"' . $temp_array[$i] . '" OR ';
            }

            $temp_str = substr($temp_str, 0, -4);

            $str_to_search = $str_to_search . ' AND source:(' . $temp_str . ')';
        }
        
        if (!empty($topic_data[0]->topic_data_location))
        {
            $temp_str = "";
            $temp_array = explode(",", $topic_data[0]->topic_data_location);

            for ($i = 0; $i < count($temp_array); $i++)
            {
                $temp_str .= '"' . $temp_array[$i] . '" OR ';
            }

            $temp_str = substr($temp_str, 0, -4);

            $str_to_search = $str_to_search . ' AND u_location:(' . $temp_str . ')';
        }
        
        if (!empty($topic_data[0]->topic_data_lang))
        {
            $temp_str = "";
            $temp_array = explode(",", $topic_data[0]->topic_data_lang);

            for ($i = 0; $i < count($temp_array); $i++)
            {
                $temp_str .= '"' . $temp_array[$i] . '" OR ';
            }

            $temp_str = substr($temp_str, 0, -4);

            $str_to_search = $str_to_search . ' AND lange_detect:(' . $temp_str . ')';
        }
        
        //if (!empty($topic_data[0]->topic_gmaps_url) && !is_null($topic_data[0]->topic_gmaps_url))
        //{
            //$str_to_search = $str_to_search . ' OR place_url:("' . $topic_data[0]->topic_gmaps_url . '")';
        //}

        /** START DM Source * */
        $str_to_search = $str_to_search . ' AND NOT source:("DM") AND NOT manual_entry_type:("review")'; // AND NOT source:("Web")
        
        return $str_to_search;
    }
}
?>

