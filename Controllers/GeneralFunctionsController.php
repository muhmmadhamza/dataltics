<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\CustomerController;
use Session;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\CompetitorAnalysisController;
use Illuminate\Support\Facades\Log;

class GeneralFunctionsController extends Controller
{
    public function date_difference($d1, $d2) 
    {
        // Return the number of days between the two dates:    
        return round(abs(strtotime($d1) - strtotime($d2))/86400);
    }
    
    public function format_number_data($number)
    {
        if ($number == 0)
            return 0;
        else
        {
            $abbrevs = array(
                12 => "T",
                9 => "B",
                6 => "M",
                3 => "K",
                0 => ""
            );
            foreach ($abbrevs as $exponent => $abbrev)
            {
                if ($number >= pow(10, $exponent))
                {
                    $display_num = $number / pow(10, $exponent);
                    $decimals = ($exponent >= 3 && round($display_num) < 100) ? 2 : 0;
                    return number_format($display_num, $decimals) . $abbrev;
                }
            }
            //return number_format($number);
        }
    }
    
    public function validate_access()
    {
        $login_session = \Session::get('_loggedin_customer_id');
        
        if(is_null($login_session) || empty($login_session) || !isset($login_session))
        {
            \Session::flush(); //remove all sessions
            
            return false;
        }
        else
            return true;
    }
    
    public function test_code(Request $request)
    {
        $tobj = new TopicController();
        
        echo $tobj->get_topic_elastic_query(\Session::get('current_loaded_project'));
    }
    
    public function get_country_flag($cname)
    {
        if(isset($cname) && !empty($cname))
        {
            if($cname == 'USA' || $cname == 'United States of America')
                $cname = 'United States';

            if($cname == 'UK')
                $cname = 'United Kingdom';

            $fquery = DB::select("SELECT country_code FROM countries_list WHERE country_name = '".$cname."'");
            if(count($fquery) > 0)
            {            
                return strtolower($fquery[0]->country_code).'.svg';
            }
            else
                return 'blank.png';
        }
        else
            return 'blank.png';
    }
    
    public function get_countries_list()
    {
        $country_data = DB::select("SELECT country_name FROM countries_list ORDER BY country_name");
        
        return $country_data;
    }
    
    public function encrypt($string, $key)
    {
        $result = '';
        for ($i = 0; $i < strlen($string); $i ++)
        {
            $char = substr($string, $i, 1);
            $keychar = substr($key, ($i % strlen($key)) - 1, 1);
            $char = chr(ord($char) + ord($keychar));
            $result .= $char;
        }
        return base64_encode($result);
    }
    
    public function decrypt($string, $key)
    {
        $result = '';
        $string = base64_decode($string);
    
        for ($i = 0; $i < strlen($string); $i ++)
        {
            $char = substr($string, $i, 1);
            $keychar = substr($key, ($i % strlen($key)) - 1, 1);
            $char = chr(ord($char) - ord($keychar));
            $result .= $char;
        }
        return $result;
    }
    
    public function get_encryption_key()
    {
        return env('ENC_KEY');
    }

    public function handle_general_stuff(Request $request)
    {
        if(isset($request["mode"]) && !empty($request["mode"]))
        {
            if($request["mode"] == 'save_topic_id')
            {
                $this->ac_log_obj = new ActivityLogController();
                
                if($request["topic_type"] == 'maintopic')
                {
                    Session::put('current_loaded_project', $request["tid"]);
                    
                    //set topic name in session
                    $tn = DB::select("SELECT topic_title FROM customer_topics WHERE topic_id = ".$request["tid"]);
                    Session::put('current_loaded_topic_name', $tn[0]->topic_title); 
                    
                    $this->ac_log_obj->log_customer_data($request, array("cid" => \Session::get('_loggedin_customer_id'), "tid" => $request["tid"], "tname" => $tn[0]->topic_title));
                }
                else if($request["topic_type"] == 'subtopic')
                {
                    $cus_obj = new CustomerController();
                    $loggedin_user_id = $cus_obj->get_parent_account_id(); //\Session::get('_loggedin_customer_id');
                    
                    $exp_data = DB::select("SELECT exp.exp_name, exp.exp_topic_id, t.topic_title FROM customer_experience exp, customer_topics t WHERE exp.exp_uid = ".$loggedin_user_id." AND exp.exp_id = ".$request["tid"]." AND exp.exp_topic_id = t.topic_id");
                    
                    $this->ac_log_obj->log_customer_data($request, array("cid" => \Session::get('_loggedin_customer_id'), "stid" => $request["tid"], "stname" => $exp_data[0]->exp_name));
                    
                    Session::put('current_loaded_subtopic_name', $exp_data[0]->exp_name);
                    Session::put('current_loaded_subtopic', $request["tid"]);
                    Session::put('current_loaded_project', $exp_data[0]->exp_topic_id);
                    Session::put('current_loaded_topic_name', $exp_data[0]->topic_title);
                }
                else if($request["topic_type"] == 'comp_analysis')
                {
                    $ca_obj = new CompetitorAnalysisController();
                    $ca_data = DB::select("SELECT * FROM competitor_analysis WHERE ca_id = ".$request["tid"]);
                    
                    $this->ac_log_obj->log_customer_data($request, array("cid" => \Session::get('_loggedin_customer_id'), "caid" => $request["tid"], "caname" => $ca_obj->get_ca_name($request["tid"])));
                    
                    Session::put('_loaded_ca_name', $ca_data[0]->ca_title);
                    Session::put('_loaded_ca_id', $ca_data[0]->ca_id);
                }
                
                return 'ok';
            }
            else if($request["mode"] == 'manual_label_update')
            {
                $new_url = '';
                $this->client = ClientBuilder::create()->setHosts([env('ELASTICSEARCH_HOST').":".env('ELASTICSEARCH_PORT')])->build();
                $this->search_index_name = env('ELASTICSEARCH_DEFAULTINDEX');
                    
                if(isset($request["pid"]) && !empty($request["pid"]))
                {
                    if ($request["match_with"] == 'p_id')
                    {
                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            ['match' => ['p_id' => $request["pid"]]]
                                        ]
                                    ]
                                ]
                            ]
                        ];

                        $es_doc_id = $request["pid"];
                    }
                    else
                    {
                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            ['match' => ['p_url' => $request["pid"]]]
                                        ]
                                    ]
                                ]
                            ]
                        ];

                        $new_url = preg_replace_callback('/([\x{0600}-\x{06ff}]|[\x{0750}-\x{077f}]|[\x{fb50}-\x{fc3f}]|[\x{fe70}-\x{fefc}])+/Uui', (function($match){return urlencode($match[1]);}), $request["pid"]);

                        $new_url = str_replace('%2F', '/', urlencode($request["pid"]));
                        $new_url = str_replace('%3A', ':', $new_url);
                        $new_url = str_replace('%3F', '?', $new_url);
                        $new_url = str_replace('%3D', '=', $new_url);
                        $new_url = str_replace('%23', '#', $new_url);
                        
                        $es_doc_id = strtolower($new_url);
                    }
                    
                    $results = $this->client->search($params);
                    
                    if (count($results["hits"]["hits"]) > 0)
                    {
                        if ($request["opt_req"] == 'positive' || $request["opt_req"] == 'negative' || $request["opt_req"] == 'neutral') //sentiment update
                        {
                            $senti = '';
                            $lang = '';
                            $pmsg = '';
                            
                            if(isset($results["hits"]["hits"][0]["_source"]["predicted_sentiment_value"]) && !empty($results["hits"]["hits"][0]["_source"]["predicted_sentiment_value"]))
                                $senti = $results["hits"]["hits"][0]["_source"]["predicted_sentiment_value"];
                            
                            if(isset($results["hits"]["hits"][0]["_source"]["lange_detect"]) && !empty($results["hits"]["hits"][0]["_source"]["lange_detect"]))
                                $lang = $results["hits"]["hits"][0]["_source"]["lange_detect"];
                            
                            if(isset($results["hits"]["hits"][0]["_source"]["p_message_text"]) && !empty($results["hits"]["hits"][0]["_source"]["p_message_text"]))
                                $pmsg = $results["hits"]["hits"][0]["_source"]["p_message_text"];
                            
                            DB::insert("INSERT INTO customers_label_data (p_message, predicted_sentiment_value_requested, predicted_sentiment_value_current, req_status, req_uid, p_id, lange_detect, request_date, topic_id) VALUES ('" . addslashes($pmsg) . "', '" . $request["opt_req"] . "', '" . $senti . "', '0', '" . \Session::get('_loggedin_customer_id') . "', '" . $request["pid"] . "', '" . $lang . "', NOW(), '" . \Session::get('current_loaded_project') . "')");

                            //update data in Elastic
                            $params = [
                                'index' => $this->search_index_name,
                                'type' => 'mytype',
                                'id' => $es_doc_id,
                                'body' => [
                                    'doc' => ['predicted_sentiment_value' => $request["opt_req"]]
                                ]
                            ];
                            //print_r($params); exit;
                            $resp = $this->client->update($params);
                        }
                        else if ($request["opt_req"] == 'anger' || $request["opt_req"] == 'fear' || $request["opt_req"] == 'happy' || $request["opt_req"] == 'neutral_em' || $request["opt_req"] == 'sadness' || $request["opt_req"] == 'surprise') //emotion update
                        {
                            if ($_POST["opt_req"] == 'neutral_em')
                                $opt_req = 'neutral';
                            else
                                $opt_req = $_POST["opt_req"];

                            DB::insert("INSERT INTO customers_label_data (p_message, emotion_requested, emotion_current, req_status, req_uid, p_id, lange_detect, request_date, topic_id) VALUES ('" . addslashes($results["hits"]["hits"][0]["_source"]["p_message_text"]) . "', '" . $opt_req . "', '" . $results["hits"]["hits"][0]["_source"]["emotion_detector"] . "', '0', '" . \Session::get('_loggedin_customer_id') . "', '" . $request["pid"] . "', '" . $results["hits"]["hits"][0]["_source"]["lange_detect"] . "', NOW(), '" . \Session::get('current_loaded_project') . "')");
                            //update data in Elastic
                            $params = [
                                'index' => $this->search_index_name,
                                'type' => 'mytype',
                                'id' => $es_doc_id,
                                'body' => [
                                    'doc' => ['emotion_detector' => $opt_req]
                                ]
                            ];
                            //print_r($params); exit;
                            $resp = $this->client->update($params);
                        }
                    }
                }
                
                echo 'ok';
            }
            else if($request["mode"] == 'dashboard_notify_email')
            {
                if(!is_null(\Session::get('_loggedin_customer_id')))
                {
                    DB::update("UPDATE customer_topics SET topic_email_notify = '".$request["enotify"]."' WHERE topic_user_id = ".\Session::get('_loggedin_customer_id')." AND topic_id = ".$request["tid"]);
                    echo 'updated';
                }
            }
            else if($request["mode"] == 'dashboard_monthly_report')
            {
                if(!is_null(\Session::get('_loggedin_customer_id')))
                {
                    DB::update("UPDATE customer_topics SET topic_send_monthly_report = '".$request["sendreport"]."' WHERE topic_id = ".$request["tid"]);
                    echo 'updated';
                }
            }
        }
    }
    
    public function cutoff_words($text, $length) //function_that_shortens_text_but_doesnt_cutoff_words
    {
        if(strlen($text) > $length) {
            $text = substr($text, 0, strpos($text, ' ', $length));
        }

        return $text;
    }
    
    public function get_postview_image(Request $request)
    {
        if(isset($request["t"]) && $request["t"] == csrf_token())
        {
            if(isset($request["i"]) && !empty($request["i"]))
            {
                if (@getimagesize(decrypt($request["i"])) === false)
                {//echo 'hi: '.decrypt($request["i"]); exit;
                    header("Content-Type: image/png");
                    $contents = file_get_contents('https://dashboard.datalyticx.ai/images/grey.png');
                    echo $contents;
                }
                else
                {
                    $image_data = @getimagesize(decrypt($request["i"]));
                    header("Content-Type: ".$image_data["mime"]);
                    $contents = file_get_contents(decrypt($request["i"]));
                    echo $contents;
                }
            }
            else
            {
                header("Content-Type: image/png");
                $contents = file_get_contents('https://dashboard.datalyticx.ai/images/grey.png');
                echo $contents;
            }
        }
    }
    
    public function fix_json($json) 
    {//Log::info($json);
        
        try {
            $newJSON = '';

            $jsonLength = strlen($json);
            for ($i = 0; $i < $jsonLength; $i++) {
                if ($json[$i] == '"' || $json[$i] == "'") {
                    $nextQuote = strpos($json, $json[$i], $i + 1);
                    $quoteContent = substr($json, $i + 1, $nextQuote - $i - 1);
                    $newJSON .= '"' . str_replace('"', "'", $quoteContent) . '"';
                    $i = $nextQuote;
                } else {
                    $newJSON .= $json[$i];
                }
            }

            return $newJSON;
        }
        catch(Exception $e)
        {
            //Log::info("Fixjson: "+$e);
            return $json;
        }        
    }
    
    public function get_postview_html($es_data)
    {
        $html_str = '';
        $user_data_string = '';
        $post_data_string = '';
        
        /*if (@getimagesize($es_data["_source"]["p_picture"]) === false)
            $img_src = env('PUBLIC_IMAGES_PATH')."grey.png";
        else
            $img_src = $es_data["_source"]["p_picture"];*/
        
        if (isset($es_data["_source"]["u_profile_photo"]) && !empty(trim($es_data["_source"]["u_profile_photo"])))
            $user_profile_pic = $es_data["_source"]["u_profile_photo"];
        else
            $user_profile_pic = env('PUBLIC_IMAGES_PATH')."grey.png";
        
        /**** User data string ****/
        if(isset($es_data["_source"]["u_followers"]) && $es_data["_source"]["u_followers"] > 0)
            $user_data_string .= '<i class="bx bxs-group mr-25 align-middle" title="Followers"></i>'.$this->format_number_data($es_data["_source"]["u_followers"]).'&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;';
        
        if(isset($es_data["_source"]["u_following"]) && $es_data["_source"]["u_following"] > 0)
            $user_data_string .= '<i class="bx bxs-share mr-25 align-middle" title="Following"></i>'.$this->format_number_data($es_data["_source"]["u_following"]).'&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;';
        
        if(isset($es_data["_source"]["u_posts"]) && $es_data["_source"]["u_posts"] > 0)
            $user_data_string .= '<i class="bx bx-file-blank mr-25 align-middle" title="Posts"></i>'.$this->format_number_data($es_data["_source"]["u_posts"]).'&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;';
        
        /******** Post data string ****/
        if(isset($es_data["_source"]["p_likes"]) && $es_data["_source"]["p_likes"] > 0)
        {
            $post_data_string .= '<div style="padding-bottom: 10px;"><i class="bx bxs-like mr-25 align-middle" title="Post likes"></i>'.$this->format_number_data($es_data["_source"]["p_likes"]).'<br><small class="text-muted">Likes</small></div>';
        }
        
        if(isset($es_data["_source"]["p_comments"]) && $es_data["_source"]["p_comments"] > 0)
        {
            if(isset($es_data["_source"]["p_comments_text"]) && !empty($es_data["_source"]["p_comments_text"])) //isset($es_data["_source"]["p_comments_text"]) && !empty($es_data["_source"]["p_comments_text"])
            {
                $post_data_string .= '<div style="padding-bottom: 10px;"><a href="javascript:void(0);" onclick="javascript:load_comments(\''.str_replace("https: // ", "https://", trim($es_data["_source"]["p_url"])).'\', \''. csrf_token().'\');"><i class="bx bxs-comment-dots mr-25 align-middle" title="Post comments"></i>'.$this->format_number_data($es_data["_source"]["p_comments"]).'<br><small class="text-muted">Comments</small></a></div>';
            }
            else
            {
                $post_data_string .= '<div style="padding-bottom: 10px;"><i class="bx bxs-comment-dots mr-25 align-middle" title="Post comments"></i>'.$this->format_number_data($es_data["_source"]["p_comments"]).'<br><small class="text-muted">Comments</small></div>';
            }
        }
        
        if(isset($es_data["_source"]["p_shares"]) && $es_data["_source"]["p_shares"] > 0)
        {
            $post_data_string .= '<div style="padding-bottom: 10px;"><i class="bx bx-share mr-25 align-middle" title="Post shares"></i>'.$this->format_number_data($es_data["_source"]["p_shares"]).'<br><small class="text-muted">Shares</small></div>';
        }
        
        if(isset($es_data["_source"]["p_engagement"]) && $es_data["_source"]["p_engagement"] > 0)
        {
            $post_data_string .= '<div style="padding-bottom: 10px;"><i class="bx bx-show mr-25 align-middle" title="Post views"></i>'.$this->format_number_data($es_data["_source"]["p_engagement"]).'<br><small class="text-muted">Views</small></div>';
        }
        
        /*********** source icon **********/
        if ($es_data["_source"]["source"] == 'Twitter')
            $source_icon = '<i class="fa-brands fa-x-twitter mr-25 align-middle" title="Twitter" style="font-size: 35px; color: #000000 !important;"></i>';
        else if ($es_data["_source"]["source"] == 'Youtube')
            $source_icon = '<i class="bx bxl-youtube mr-25 align-middle" title="YouTube" style="font-size: 35px; color: #cd201f !important;"></i>';
        else if ($es_data["_source"]["source"] == 'Linkedin')
            $source_icon = '<i class="bx bxl-linkedin mr-25 align-middle" title="Linkedin" style="font-size: 35px; color: #365d98 !important;"></i>';
        else if ($es_data["_source"]["source"] == 'Facebook')
            $source_icon = '<i class="bx bxl-facebook-square mr-25 align-middle" title="Facebook" style="font-size: 35px; color: #365d98 !important;"></i>';
        else if ($es_data["_source"]["source"] == 'Pinterest')
            $source_icon = '<i class="bx bxl-pinterest mr-25 align-middle" title="Pinterest" style="font-size: 35px; color: #bd081c !important;"></i>';
        else if ($es_data["_source"]["source"] == 'Instagram')
            $source_icon = '<i class="bx bxl-instagram mr-25 align-middle" title="Instagram" style="font-size: 35px; color: #e4405f !important;"></i>';
        else if ($es_data["_source"]["source"] == 'khaleej_times' || $es_data["_source"]["source"] == 'Omanobserver' || $es_data["_source"]["source"] == 'Time of oman' || $es_data["_source"]["source"] == 'Blogs')
            $source_icon = '<i class="bx bxs-book mr-25 align-middle" title="Blog" style="font-size: 35px; color: #f57d00 !important;"></i>';
        else if ($es_data["_source"]["source"] == 'Reddit')
            $source_icon = '<i class="bx bxl-reddit mr-25 align-middle" title="Reddit" style="font-size: 35px; color: #ff4301 !important;"></i>';
        else if ($es_data["_source"]["source"] == 'FakeNews' || $es_data["_source"]["source"] == 'News')
            $source_icon = '<i class="bx bx-news mr-25 align-middle" title="News" style="font-size: 35px; color: #77BD9D !important;"></i>';
        else if ($es_data["_source"]["source"] == 'Tumblr')
            $source_icon = '<i class="bx bxl-tumblr mr-25 align-middle" title="Tumblr" style="font-size: 35px; color: #34526f !important;"></i>';
        else if ($es_data["_source"]["source"] == 'Vimeo')
            $source_icon = '<i class="bx bxl-vimeo mr-25 align-middle" title="Vimeo" style="font-size: 35px; color: #86c9ef !important;"></i>';
        else if ($es_data["_source"]["source"] == 'Web' || $es_data["_source"]["source"] == 'DeepWeb')
            $source_icon = '<i class="bx bx-globe mr-25 align-middle" title="Web" style="font-size: 35px; color: #FF7D02 !important;"></i>';
        else if ($es_data["_source"]["source"] == 'GoogleMaps')
            $source_icon = '<i class="bx bxs-map mr-25 align-middle" title="Google Maps" style="font-size: 35px; color: #8a2be2 !important;"></i>';
        else if ($es_data["_source"]["source"] == 'Tripadvisor')
            $source_icon = '<i class="bx bxl-trip-advisor mr-25 align-middle" title="Tripadvisor" style="font-size: 35px; color: #00AF87 !important;"></i>';
        else if ($es_data["_source"]["source"] == 'Tiktok')
            $source_icon = '<i class="bx bx-text mr-25 align-middle" title="Tiktok" style="font-size: 35px; color: #000000 !important;"></i>';
        else
            $source_icon = '';
        //else if ($es_data["source"] == 'Zomato')
            //$source_icon = '<img src="'.IMAGES_PATH.'zomato.png" width="30" height="30" border="0" data-toggle="tooltip" data-original-title="Source">';
        
        //Sentiment prediction
        $predicted_sentiment = '';
        
        //check if the record was manually updated, if yes, use it
        $chk_senti = DB::select("SELECT predicted_sentiment_value_requested FROM customers_label_data WHERE p_id = '".$es_data["_id"]."' ORDER BY label_id DESC LIMIT 1");
        
        if(count($chk_senti) > 0)
        {
            if ($chk_senti[0]->predicted_sentiment_value_requested == 'positive')
                $predicted_sentiment = '<div style="float:left; padding: 0px 5px 0px 5px;"> | </div><div style="float:left;"><i class="bx bx-happy" style="font-size:1.6em; color:green;" title="Positive sentiment"></i></div>';
            else if ($chk_senti[0]->predicted_sentiment_value_requested == 'negative')
                $predicted_sentiment = '<div style="float:left; padding: 0px 5px 0px 5px;"> | </div><div style="float:left;"><i class="bx bx-sad" style="font-size:1.6em; color:red;" title="Negative sentiment"></i></div>';
            else if ($chk_senti[0]->predicted_sentiment_value_requested == 'neutral')
                $predicted_sentiment = '<div style="float:left; padding: 0px 5px 0px 5px;"> | </div><div style="float:left;"><i class="bx bx-meh" style="font-size:1.6em; color:blue;" title="Neutral sentiment"></i></div>';
        }
        else if(isset($es_data["_source"]["predicted_sentiment_value"]) && !empty($es_data["_source"]["predicted_sentiment_value"]))
        {
            if ($es_data["_source"]["predicted_sentiment_value"] == 'positive')
                $predicted_sentiment = '<div style="float:left; padding: 0px 5px 0px 5px;"> | </div><div style="float:left;"><i class="bx bx-happy" style="font-size:1.6em; color:green;" title="Positive sentiment"></i></div>';
            else if ($es_data["_source"]["predicted_sentiment_value"] == 'negative')
                $predicted_sentiment = '<div style="float:left; padding: 0px 5px 0px 5px;"> | </div><div style="float:left;"><i class="bx bx-sad" style="font-size:1.6em; color:red;" title="Negative sentiment"></i></div>';
            else if ($es_data["_source"]["predicted_sentiment_value"] == 'neutral')
                $predicted_sentiment = '<div style="float:left; padding: 0px 5px 0px 5px;"> | </div><div style="float:left;"><i class="bx bx-meh" style="font-size:1.6em; color:blue;" title="Neutral sentiment"></i></div>';
        }
        
        //Category prediction
        $predicted_category = '';
        
        if(isset($es_data["_source"]["predicted_category"]))
        {
            if ($es_data["_source"]["predicted_category"] == 'Business')
                $predicted_category = '<div style="float:left; padding: 0px 5px 0px 5px;"> | </div><div style="float:left;"><i class="bx bx-briefcase" style="font-size:1.6em; color:gold;" title="Predicted category: Business"></i></div>';
            else if ($es_data["_source"]["predicted_category"] == 'Education')
                $predicted_category = '<div style="float:left; padding: 0px 5px 0px 5px;"> | </div><div style="float:left;"><i class="bx bxs-graduation" style="font-size:1.6em; color:gold;" title="Predicted category: Education"></i></div>';
            else if ($es_data["_source"]["predicted_category"] == 'Entertainment')
                $predicted_category = '<div style="float:left; padding: 0px 5px 0px 5px;"> | </div><div style="float:left;"><i class="bx bx-film" style="font-size:1.6em; color:gold;" title="Predicted category: Entertainment"></i></div>';
            else if ($es_data["_source"]["predicted_category"] == 'Fashion')
                $predicted_category = '<div style="float:left; padding: 0px 5px 0px 5px;"> | </div><div style="float:left;"><i class="bxs bx-t-shirt" style="font-size:1.6em; color:gold;" title="Predicted category: Fashion"></i></div>';
            else if ($es_data["_source"]["predicted_category"] == 'Food')
                $predicted_category = '<div style="float:left; padding: 0px 5px 0px 5px;"> | </div><div style="float:left;"><i class="bx bx-restaurant" style="font-size:1.6em; color:gold;" title="Predicted category: Food"></i></div>';
            else if ($es_data["_source"]["predicted_category"] == 'Health')
                $predicted_category = '<div style="float:left; padding: 0px 5px 0px 5px;"> | </div><div style="float:left;"><i class="bx bx-plus-medical" style="font-size:1.6em; color:gold;" title="Predicted category: Health"></i></div>';
            else if ($es_data["_source"]["predicted_category"] == 'Politics')
                $predicted_category = '<div style="float:left; padding: 0px 5px 0px 5px;"> | </div><div style="float:left;"><i class="bx bx-chair" style="font-size:1.6em; color:gold;" title="Predicted category: Politics"></i></div>';
            else if ($es_data["_source"]["predicted_category"] == 'Sports')
                $predicted_category = '<div style="float:left; padding: 0px 5px 0px 5px;"> | </div><div style="float:left;"><i class="bx bx-football" style="font-size:1.6em; color:gold;" title="Predicted category: Sports"></i></div>';
            else if ($es_data["_source"]["predicted_category"] == 'Technology')
                $predicted_category = '<div style="float:left; padding: 0px 5px 0px 5px;"> | </div><div style="float:left;"><i class="bx bx-laptop" style="font-size:1.6em; color:gold;" title="Predicted category: Technology"></i></div>';
            else if ($es_data["_source"]["predicted_category"] == 'Transport')
                $predicted_category = '<div style="float:left; padding: 0px 5px 0px 5px;"> | </div><div style="float:left;"><i class="bx bx-bus" style="font-size:1.6em; color:gold;" title="Predicted category: Transport"></i></div>';
            else if ($es_data["_source"]["predicted_category"] == 'Weather')
                $predicted_category = '<div style="float:left; padding: 0px 5px 0px 5px;"> | </div><div style="float:left;"><i class="bx bx-cloud" style="font-size:1.6em; color:gold;" title="Predicted category: Weather"></i></div>';
            else
                $predicted_category = '';
        }
        
        if ($es_data["_source"]["source"] == 'GoogleMaps' || $es_data["_source"]["source"] == 'Tripadvisor')
            $html_str = '<div class="card" style="box-shadow: -8px 12px 18px 0 rgba(25,42,70,.13) !important; margin-bottom: 3.5rem !important;">';
        else
            $html_str = '<div class="card" style="box-shadow: -8px 12px 18px 0 rgba(25,42,70,.13) !important;">';
        
        if ($es_data["_source"]["source"] == 'Youtube')
        {
            if(isset($es_data["_source"]["video_embed_url"]) && !empty($es_data["_source"]["video_embed_url"]))
                $html_str .= '<div style="width: 100%; height: 150px; max-height: 150px; background: #000000;"><iframe width="80%" height="150" src="'.$es_data["_source"]["video_embed_url"].'" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
            else if(isset($es_data["_source"]["p_id"]) && !empty($es_data["_source"]["p_id"]))
                $html_str .= '<div style="width: 100%; height: 150px; max-height: 150px; background: #000000;"><iframe width="80%" height="150" src="https://www.youtube.com/embed/'.$es_data["_source"]["p_id"].'" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
            else
                $html_str .= '<div style="width: 100%; height: 150px; max-height: 150px; background:url(\'data:image/png;base64,'.base64_encode(file_get_contents($img_src)).'\'); background-size: cover; background-position: center; background-repeat: no-repeat;"></div>';
        }
        else if ($es_data["_source"]["source"] == 'GoogleMaps' || $es_data["_source"]["source"] == 'Tripadvisor')
        {
            
        }
        else
        {
            if(isset($es_data["_source"]["p_picture"]))
                $html_str .= '<div style="width: 100%; height: 150px; max-height: 150px; background:url(\'https://dashboard.datalyticx.ai/get-postview-image?i='.encrypt($es_data["_source"]["p_picture"]).'&t='. csrf_token().'\'); background-size: cover; background-position: center; background-repeat: no-repeat; border-bottom: 1px solid #f0f0f0; background-color: #f0f0f0;"></div>';
            else
                $html_str .= '<div style="width: 100%; height: 150px; max-height: 150px; border-bottom: 1px solid #f0f0f0; background-color: #f0f0f0;"></div>';
        }
            //$html_str .= '<div style="width: 100%; height: 150px; max-height: 150px; background:url(\'data:image/png;base64,'.base64_encode(file_get_contents($img_src)).'\'); background-size: cover; background-position: center; background-repeat: no-repeat;"></div>';
        
        /*if ($es_data["_source"]["source"] == 'GoogleMaps' || $es_data["_source"]["source"] == 'Tripadvisor')
            $html_str .= '<div style="width:60px; height: 60px; position:absolute; z-index: 100; right: 15px; top: 15px; background: url(\'data:image/png;base64,'.base64_encode(@file_get_contents($user_profile_pic)).'\'); background-size: cover; border-radius: 30px;"></div>';
        else
            $html_str .= '<div style="width:80px; height: 80px; position:absolute; z-index: 100; right: 15px; top: 90px; background: url(\'data:image/png;base64,'.base64_encode(@file_get_contents($user_profile_pic)).'\'); background-color:#f0f0f0; background-size: cover; border-radius: 5px; border: 1px solid #999999;"></div>';*/
        if ($es_data["_source"]["source"] == 'GoogleMaps' || $es_data["_source"]["source"] == 'Tripadvisor')
            $html_str .= '<div style="width:60px; height: 60px; position:absolute; z-index: 100; right: 15px; top: 15px; background: url(\'data:image/png;base64,'.base64_encode(@file_get_contents($user_profile_pic)).'\'); background-size: cover; border-radius: 30px;"></div>';
        else
        {
            //$html_str .= '<div style="width:80px; height: 80px; position:absolute; z-index: 100; right: 15px; top: 90px; background: url(\'https://dashboard.datalyticx.ai/get-postview-image?i='.encrypt($es_data["_source"]["u_profile_photo"]).'&t='. csrf_token().'\'); background-color:#f0f0f0; background-size: cover; border-radius: 5px; border: 1px solid #999999;"></div>';
            $html_str .= '<div style="width:80px; height: 80px; position:absolute; z-index: 100; right: 15px; top: 90px; background: url(\'https://dashboard.datalyticx.ai/get-postview-image?i='.encrypt($user_profile_pic).'&t='. csrf_token().'\'); background-color:#f0f0f0; background-size: cover; border-radius: 5px; border: 1px solid #999999;"></div>';
        }
                   
        $html_str .= '<div class="card-header" style="padding-bottom: 5px;"><h5 class="card-title">'.$es_data["_source"]["u_fullname"].'&nbsp;</h5></div>';
        
        $html_str .= '<div style="padding-left: 1.7rem;">'.substr($user_data_string, 0, -37).'&nbsp;</div>';
        
        $html_str .= '<div style="padding-left: 1.7rem; margin-top:4px;"><div style="float:left;"><small class="text-muted">'.date("j M, Y h:i a", strtotime($es_data["_source"]["p_created_time"])).'</small></div><div style="float:left;">'.$predicted_sentiment.$predicted_category.'</div></div>';
        
        if(($es_data["_source"]["source"] == 'GoogleMaps' || $es_data["_source"]["source"] == 'Tripadvisor') && isset($es_data["_source"]["p_rating"]))
        {
            $rating_stars = '';
            for($k=0; $k<floor($es_data["_source"]["p_rating"]); $k++)
            {
                $rating_stars .= '<i class="bx bxs-star" style="color: #b0e0e6;"></i>';
            }
            
            $html_str .= '<div style="padding-left: 1.7rem;">'.$rating_stars.'<small class="text-muted"> </small></div>';
        }
                
        //Following check is applied as location information is being stored in p_message_text field. We don't have to show it to customers
        $message_text = '';
        
        if ($es_data["_source"]["source"] == 'GoogleMaps' || $es_data["_source"]["source"] == 'Tripadvisor')
        {
            $m_text = explode('***|||###', $es_data["_source"]["p_message_text"]);
            $message_text = nl2br($m_text[0]);
            
        }
        else
            $message_text = isset($es_data["_source"]["p_message_text"]) ? nl2br(strip_tags($es_data["_source"]["p_message_text"])) : '';
        
        if(!empty($post_data_string))
        {
            $html_str .= '<div class="row">';
            $html_str .= '<div class="col-sm-9" style="float:left; padding:0px 0px 0px 30px;"><p class="hide-native-scrollbar" style="height: 100px; overflow-x: hidden; overflow-y: auto; padding: 0px 0px 0px 11px; margin-top: 5px; cursor: row-resize;">'.$message_text.'</p></div>';
            $html_str .= '<div class="col-sm-3" style="padding: 0px 0px 0px 0px; float:left; text-align: center; margin-top:-22px; height: 150px;">'.$post_data_string.'</div>';
            $html_str .= '</div>';
        }
        else
        {
            $html_str .= '<div class="col-sm-12"><p class="hide-native-scrollbar" style="height: 150px; overflow-x: hidden; overflow-y: auto; padding: 10px 0px 0px 11px; cursor: row-resize;">'.$message_text.'</p></div>';
        }
        
        if(isset($es_data["_source"]["p_url"]))
        {
            $html_str .= '<div class="row" style="padding: 0px 0px 5px 35px;">'.$source_icon.'<a href="javascript:void(0);" onclick="javacript:window.open(\''.str_replace("https: // ", "https://", trim($es_data["_source"]["p_url"])).'\');"><i class="bx bx-link-external mr-25 align-middle" title="View original post" style="font-size: 16px; color: #999999 !important; padding-top:14px;"></i></a></div>';
        }
        
        //Manual update process
        $allowed_admins = array("demo@mofa.gov.ae","demo@ead.gov.ae","amira.alaufi@supply.nama.om","demo@silal.ae","demo@healthgates.com","demo@adcda.gov.ae","demo@omansail.com","demo@ehs.gov.ae","sm@enterprisemobility.ae", "ab@datalytics24.com", "ns3633315@gmail.com", "sulaiman.maqbali@omran.om", "ahmed@basket.xyz", "info@basket.xyz", "suad47.h@gmail.com", "majida.wahaibi@medcoman.com", "ruqaiya.hadrami@medcoman.com", "demo@datalytics24.com", "mmamp.oman@gmail.com", "zahra1alnabhani@gmail.com", "moosatalk2010@gmail.com", "salma.hajri@omran.om", "jm@airomob.com", "zakiya.alghammari@holding.nama.om", "juhaina.alkendi@beah.om", "mariam.albalushi@beah.om", "mohammed.hassan@beah.om", "fahad.altoubi@oia.gov.om", "bader.alhinai@oia.gov.om", "sultan.alhabsi@oia.gov.om", "info@datalytics24.com", "fatma.alsubhi@mzec.nama.om", "said.albusaidi@mzec.nama.om", "ruqaiya.hadrami@medcoman.com", "nawal.khusaibi@medcoman.com", "samira.almughairi@tanweer.nama.om", "zakiya.alghammari@holding.nama.om", "abdulaziz.alhandhali@majanco.nama.om", "moza.alkaabi@majanco.nama.om", "ymujaizi@omantourism.gov.om", "rmaskeri@omantourism.gov.om", "sshamli@omantourism.gov.om", "aghafri@omantourism.gov.om", "naima.aljabri@tanweer.nama.om", "amira.alaufi@tanweer.nama.om", "sk.albalushi@bankdhofar.com", "walyam.al-said@ooredoo.om", "noor.alojaili@trainee.ooredoo.om", "amal.al-hadhrami@ooredoo.om", "abdullah.al-nabhani@ooredoo.om", "ahmed.al-housni@ooredoo.om", "jalila.alakhzami@omaninfo.gov.om", "abnasser@opp.gov.om", "mariam.alaraimi@majanco.nama.om", "shakir.ghamtil@majanco.nama.om", "fahad.altobi@omangrid.nama.om", "juhaina.alkendi@beah.om", "miqat.alyousfi@holding.nama.om", "althuraya.alkharusi@holding.nama.om", "ibtisam.alhadhrami@holding.nama.om", "demo@datalyticx.ai", "b.almania@ghc.sa", "ahmed@datalyticx.ai", "haider.allawati@soharinternational.com", "mazin.alraisi@soharinternational.com", "ibrahim.albrashdi@soharinternational.com", "darshil@media21world.com", "omer.syed@soharinternational.com", "raufrasheed@yahoo.com", "mansoor@jazbzatel.com", "demo@gdfra.gov.ae", "demo@bankmuscat.com", "demo@dct.gov.ae", "marya.alshamsi@soharinternational.com", "mjl0332@distribution.nama.om", "demo@adafsa.gov.ae", "demo@ncema.gov.ae", "demo@smartservices.icp.gov.ae", "demo@gdrfa.gov.ae", "demo@adnoc.ae", "demo@aldar.com", "demo@scad.ae");
        $login_session = \Session::get('_loggedin_customer_id');
        $cus_obj = new CustomerController();
        $cus_email = $cus_obj->get_customer_email($login_session);
        
        if(in_array(strtolower($cus_email), $allowed_admins))
        {
            $senti_pos = ''; $senti_neg = ''; $senti_neu = '';
            $polit = ''; $non_polit = '';
            $cat_bus = ''; $cat_edu = ''; $cat_ent = ''; $cat_fas = ''; $cat_foo = ''; $cat_hea = ''; $cat_pol = ''; $cat_spo = ''; $cat_tec = ''; $cat_tra = ''; $cat_wea = ''; $cat_telc = ''; $cat_tour = '';
            $emo_anger = ''; $emo_fear = ''; $emo_happy = ''; $emo_neutral = ''; $emo_sadness = ''; $emo_surprise = '';
            $u_normal = ''; $u_influencer = ''; $u_unverified = '';
            $hate = ''; $normal = '';

            $senti_exists = false;
            
            $p_uniq_id = $es_data["_id"];
            
            if($es_data["_source"]["source"] == 'FakeNews' || $es_data["_source"]["source"] == 'News' || $es_data["_source"]["source"] == 'Blogs')
            {
                $match_with = 'p_url';
            }
            else
            {
                $match_with = 'p_id';
            }
            
            //sentiments
            $check_data = DB::select("SELECT req_uid, predicted_sentiment_value_requested FROM customers_label_data WHERE p_id = '".$p_uniq_id."' AND predicted_sentiment_value_requested != '' ORDER BY label_id DESC LIMIT 1");
            if(count($check_data) > 0)
            {
                $senti_exists = true;
                    
                if($check_data[0]->predicted_sentiment_value_requested == 'positive')
                    $senti_pos = ' style="background: #ff0;"';
                else if($check_data[0]->predicted_sentiment_value_requested == 'negative')
                    $senti_neg = ' style="background: #ff0;"';
                else if($check_data[0]->predicted_sentiment_value_requested == 'neutral')
                    $senti_neu = ' style="background: #ff0;"';
            }
            
            //emotions
            $check_data = DB::select("SELECT req_uid, emotion_requested FROM customers_label_data WHERE p_id = '".$p_uniq_id."' AND emotion_requested != '' ORDER BY label_id DESC LIMIT 1");
            if(count($check_data) > 0)
            {
                $senti_exists = true;
                    
                if($check_data[0]->emotion_requested == 'anger')
                    $emo_anger = ' style="background: #ff0;"';
                else if($check_data[0]->emotion_requested == 'fear')
                    $emo_fear = ' style="background: #ff0;"';
                else if($check_data[0]->emotion_requested == 'happy')
                    $emo_happy = ' style="background: #ff0;"';
                else if($check_data[0]->emotion_requested == 'neutral')
                    $emo_neutral = ' style="background: #ff0;"';
                else if($check_data[0]->emotion_requested == 'sadness')
                    $emo_sadness = ' style="background: #ff0;"';
                else if($check_data[0]->emotion_requested == 'surprise')
                    $emo_surprise = ' style="background: #ff0;"';
            }
            
            $label_dropdown = '
                <select style="font-size: 12px; width:auto !important;" onChange="javascript:update_label_request(\''.$p_uniq_id.'\', this.value, \''.$match_with.'\', \''.csrf_token().'\');">
                    <option value="-1">Request label update</option>
                    <optgroup label="Sentiment">
                        <option value="positive"'.$senti_pos.'>Postive</option>
                        <option value="negative"'.$senti_neg.'>Negative</option>
                        <option value="neutral"'.$senti_neu.'>Neutral</option>
                    </optgroup>
                    <optgroup label="Emotions">
                        <option value="anger"'.$emo_anger.'>Anger</option>
                        <option value="fear"'.$emo_fear.'>Fear</option>
                        <option value="happy"'.$emo_happy.'>Happy</option>
                        <option value="neutral_em"'.$emo_neutral.'>Neutral</option>
                        <option value="sadness"'.$emo_sadness.'>Sadness</option>
                        <option value="surprise"'.$emo_surprise.'>Surprise</option>
                    </optgroup>
                </select>';
            
            $html_str .= '<div class="row" style="padding: 0px 0px 5px 35px;">'.$label_dropdown.'</div>';
        }
        //END: Manual update process
        
        $html_str .= '</div>';
        
        return $html_str;
    }
    
    public function get_postview_simple_html($es_data)
    {
        $html_str = ''; $user_data_string = ''; $post_data_string = ''; $source_icon = '';
        
        if (isset($es_data["u_profile_photo"]) && !empty(trim($es_data["u_profile_photo"])))
            $user_profile_pic = $es_data["u_profile_photo"];
        else
            $user_profile_pic = env('PUBLIC_IMAGES_PATH')."grey.png";
        
        /*********** source icon **********/
        if ($es_data["source"] == 'Twitter')
            $source_icon = '<i class="bx bxl-twitter mr-25 align-middle" title="Twitter" style="font-size: 25px; color: #00abea !important;"></i>';
        else if ($es_data["source"] == 'Youtube')
            $source_icon = '<i class="bx bxl-youtube mr-25 align-middle" title="YouTube" style="font-size: 25px; color: #cd201f !important;"></i>';
        else if ($es_data["source"] == 'Linkedin')
            $source_icon = '<i class="bx bxl-linkedin mr-25 align-middle" title="Linkedin" style="font-size: 25px; color: #365d98 !important;"></i>';
        else if ($es_data["source"] == 'Facebook')
            $source_icon = '<i class="bx bxl-facebook-square mr-25 align-middle" title="Facebook" style="font-size: 25px; color: #365d98 !important;"></i>';
        else if ($es_data["source"] == 'Pinterest')
            $source_icon = '<i class="bx bxl-pinterest mr-25 align-middle" title="Pinterest" style="font-size: 25px; color: #bd081c !important;"></i>';
        else if ($es_data["source"] == 'Instagram')
            $source_icon = '<i class="bx bxl-instagram mr-25 align-middle" title="Instagram" style="font-size: 25px; color: #e4405f !important;"></i>';
        else if ($es_data["source"] == 'khaleej_times' || $es_data["source"] == 'Omanobserver' || $es_data["source"] == 'Time of oman' || $es_data["source"] == 'Blogs')
            $source_icon = '<i class="bx bxs-book mr-25 align-middle" title="Blog" style="font-size: 25px; color: #f57d00 !important;"></i>';
        else if ($es_data["source"] == 'Reddit')
            $source_icon = '<i class="bx bxl-reddit mr-25 align-middle" title="Reddit" style="font-size: 25px; color: #ff4301 !important;"></i>';
        else if ($es_data["source"] == 'FakeNews' || $es_data["source"] == 'News')
            $source_icon = '<i class="bx bx-news mr-25 align-middle" title="News" style="font-size: 25px; color: #77BD9D !important;"></i>';
        else if ($es_data["source"] == 'Tumblr')
            $source_icon = '<i class="bx bxl-tumblr mr-25 align-middle" title="Tumblr" style="font-size: 25px; color: #34526f !important;"></i>';
        else if ($es_data["source"] == 'Vimeo')
            $source_icon = '<i class="bx bxl-vimeo mr-25 align-middle" title="Vimeo" style="font-size: 25px; color: #86c9ef !important;"></i>';
        else if ($es_data["source"] == 'Web' || $es_data["source"] == 'DeepWeb')
            $source_icon = '<i class="bx bx-globe mr-25 align-middle" title="Web" style="font-size: 25px; color: #FF7D02 !important;"></i>';
        
        /**** User data string ****/
        if(isset($es_data["u_followers"]) && $es_data["u_followers"] > 0)
            $user_data_string .= '<i class="bx bxs-group mr-25 align-middle" title="Followers"></i>'.$this->format_number_data($es_data["u_followers"]).'&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;';
        
        if(isset($es_data["u_following"]) && $es_data["u_following"] > 0)
            $user_data_string .= '<i class="bx bxs-share mr-25 align-middle" title="Following"></i>'.$this->format_number_data($es_data["u_following"]).'&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;';
        
        if(isset($es_data["u_posts"]) && $es_data["u_posts"] > 0)
            $user_data_string .= '<i class="bx bx-file-blank mr-25 align-middle" title="Posts"></i>'.$this->format_number_data($es_data["u_posts"]).'&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;';
        
        /******** Post data string ****/
        if(isset($es_data["p_likes"]) && $es_data["p_likes"] > 0)
        {
            $post_data_string .= '<div style="float: right; margin-left: 10px;"><i class="bx bxs-like mr-25 align-middle" title="Post likes"></i>'.$this->format_number_data($es_data["p_likes"]).'</div>';
        }
        
        if(isset($es_data["p_comments"]) && $es_data["p_comments"] > 0)
        {
            $post_data_string .= '<div style="float: right; margin-left: 10px;"><i class="bx bxs-comment-dots mr-25 align-middle" title="Post comments"></i>'.$this->format_number_data($es_data["p_comments"]).'</div>';
        }
        
        if(isset($es_data["p_shares"]) && $es_data["p_shares"] > 0)
        {
            $post_data_string .= '<div style="float: right; margin-left: 10px;"><i class="bx bx-share mr-25 align-middle" title="Post shares"></i>'.$this->format_number_data($es_data["p_shares"]).'</div>';
        }

        if(isset($es_data["p_engagement"]) && $es_data["p_engagement"] > 0)
        {
            $post_data_string .= '<div style="float: right; margin-left: 10px;"><i class="bx bx-show mr-25 align-middle" title="Post views"></i>'.$this->format_number_data($es_data["p_engagement"]).'</div>';
        }
        
        $html_str = '<div class="card">
                        <div class="card-body">
                            <div class="col-sm-12">
                                <div style="float:left; width:25px; height: 25px; margin-right: 15px; background:url(\'data:image/png;base64,'.base64_encode(@file_get_contents($user_profile_pic)).'\'); background-size: cover; background-position: center; background-repeat: no-repeat; background-color: #F2F4F4;"></div>
                                <div style="float: left;"><h5 style="padding-top: 2px;">'.$es_data["u_fullname"].'</h5></div>
                                <div style="float:right; width: 30px; padding-top: 3px;">'.$source_icon.'</div>
                                <div style="clear:both;"></div>
                            </div>';
        if($user_data_string != '')
        {
            $html_str .= '<div class="col-sm-12" style="font-size: 12px;">'.substr($user_data_string, 0, -37).'</div>';
        }
        
        if(isset($es_data["title"]) && !empty($es_data["title"]))
        {
            $html_str .= '<div class="col-sm-12">
                            <h6 style="color: #5a8dee;">'.$es_data["title"].'</h6>
                        </div>';
        }
        
        if(isset($es_data["p_message_text"]) && !empty($es_data["p_message_text"]))
            $p_message_text = $es_data["p_message_text"];
        else
            $p_message_text = $es_data["p_message"];
        
        $html_str .= '
            <div class="col-sm-12"><p style="padding: 0px 0px 0px 0px; margin: 5px 0px 0px 0px;">'.$this->cutoff_words(strip_tags(trim($p_message_text)), 180).' ...</p></div>
                <div class="col-sm-12" style="padding-top: 10px;">
                    <div style="font-size: 12px; float: left;">'.date("j-M-Y", strtotime($es_data["p_created_time"])).'</div>
                    <div style="font-size: 12px; float: right;">'.$post_data_string.'</div>
                    <div style="clear: both;"></div>
                </div>
            </div>
            <div class="card-footer border-top d-flex justify-content-between"><small class="text-truncate"><a href="'.$es_data["p_url"].'" class="success darken-4 " target="_blank">'.$es_data["p_url"].'</a></small></div>
        </div>';                           
                            
        return $html_str;
    }
    
    public function clean_input_data($str)
    {
        $str = trim($str);
        $str = str_replace("~", "", $str);
        $str = str_replace("`", "", $str);
        $str = str_replace("!", "", $str);
        $str = str_replace("@", "", $str);
        $str = str_replace("#", "", $str);
        $str = str_replace("$", "", $str);
        $str = str_replace("%", "", $str);
        $str = str_replace("^", "", $str);
        $str = str_replace("&", "", $str);
        $str = str_replace("*", "", $str);
        $str = str_replace("(", "", $str);
        $str = str_replace(")", "", $str);
        $str = str_replace("-", "", $str);
        $str = str_replace("_", "", $str);
        $str = str_replace("+", "", $str);
        $str = str_replace("=", "", $str);
        $str = str_replace("{", "", $str);
        $str = str_replace("[", "", $str);
        $str = str_replace("}", "", $str);
        $str = str_replace("]", "", $str);
        $str = str_replace("|", "", $str);
        $str = str_replace("\\", "", $str);
        $str = str_replace(":", "", $str);
        $str = str_replace(";", "", $str);
        $str = str_replace("\"", "", $str);
        $str = str_replace("'", "", $str);
        $str = str_replace("<", "", $str);
        $str = str_replace(",", "", $str);
        $str = str_replace(">", "", $str);
        $str = str_replace(".", "", $str);
        $str = str_replace("?", "", $str);
        $str = str_replace("/", "", $str);
        $str = str_replace("'", "", $str);
        
        return $str;
    }
    
    public function get_source_handle_name($handle)
    {
        $handle_name = '';
        $cus_obj = new CustomerController();
        
        if($handle == 'Twitter')
        {
            $hq = DB::select("SELECT sh_screen_name FROM source_handles WHERE sh_type = '".$handle."' AND sh_cid = ".$cus_obj->get_parent_account_id());
            if(count($hq) > 0)
            {
                $handle_name = $this->decrypt($hq[0]->sh_screen_name, $this->get_encryption_key());
            }
        }
        
        return $handle_name;
    }
}
?>
