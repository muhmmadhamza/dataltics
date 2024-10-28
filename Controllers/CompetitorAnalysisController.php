<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
//use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Elasticsearch\ClientBuilder;
use App\Http\Controllers\GeneralFunctionsController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\SubTopicController;
use App\Http\Controllers\QuickChartController;
use Mpdf\Mpdf;
use Crypt;
use App\Http\Controllers\ActivityLogController;

class CompetitorAnalysisController extends Controller
{
    public function __construct()
    {
        $this->client = ClientBuilder::create()->setHosts([env('ELASTICSEARCH_HOST').":".env('ELASTICSEARCH_PORT')])->build();
        
        $this->search_index_name = env('ELASTICSEARCH_DEFAULTINDEX');
        $this->date_fetch_days_number = env('DATA_FETCH_DAYS_NUMBER');
        $this->date_fetch_from_time = env('DATA_FETCH_FROM_TIME');
        $this->date_fetch_to_time = env('DATA_FETCH_TO_TIME');
        
        $this->gen_func_obj = new GeneralFunctionsController();
        $this->cus_obj = new CustomerController();
        $this->topic_obj = new TopicController();
        $this->subtopic_obj = new SubTopicController();
        $this->ac_log_obj = new ActivityLogController();
        
        $this->tmp_request = new \Illuminate\Http\Request();
    }

    public function load_ca_settings_page(Request $request)
    {
        //check for user login
        if(!$this->gen_func_obj->validate_access())
        {
            return redirect('/');
        }
        
        $loggedin_user_id = $this->cus_obj->get_parent_account_id();
         
        $this->topic_obj = new TopicController();

        //Fetch topics of respective user.
        $topics_data = DB::select("SELECT * FROM customer_topics WHERE customer_portal = 'D24' AND topic_is_deleted != 'Y' AND topic_user_id = " . $loggedin_user_id . " ORDER BY topic_id DESC");
        
        //Fetch competitor analysis of respective user.
        $ca_data = DB::select("SELECT * FROM competitor_analysis WHERE ca_cid = ".$loggedin_user_id." ORDER BY ca_id DESC");
        
        $this->ca_obj = new CompetitorAnalysisController();
        
        //return view('pages.topic-settings', compact('topics_data'));
        return view('pages.ca-settings', ['topics_data' => $topics_data, 'ca_data' => $ca_data, 'ca_obj' => $this->ca_obj ]);
    }
    
    public function load_ca_view(Request $request)
    {
        //check for user login
        if(!$this->gen_func_obj->validate_access())
        {
            return redirect('/');
        }
        
        $loggedin_user_id = $this->cus_obj->get_parent_account_id();
         
        $this->topic_obj = new TopicController();

        //Fetch topics of respective user.
        $topics_data = DB::select("SELECT * FROM customer_topics WHERE customer_portal = 'D24' AND topic_is_deleted != 'Y' AND topic_user_id = " . $loggedin_user_id . " ORDER BY topic_id DESC");
        
        //Fetch competitor analysis of respective user.
        $ca_data = DB::select("SELECT * FROM competitor_analysis WHERE ca_cid = ".$loggedin_user_id." ORDER BY ca_id DESC");
        
        $this->ca_obj = new CompetitorAnalysisController();
        
        //return view('pages.topic-settings', compact('topics_data'));
        return view('pages.dashboard-competitor-analysis', ['topics_data' => $topics_data, 'ca_data' => $ca_data, 'ca_obj' => $this->ca_obj, 'ca_id' => \Session::get('_loaded_ca_id') ]);
    }
    
    public function handle_competitor_analysis(Request $request)
    {
        $loggedin_user_id = $this->cus_obj->get_parent_account_id();
        
        if(isset($request["mode"]) && !empty($request["mode"]))
        {
            if($request["mode"] == 'create_competitor_analysis')
            {
                if(isset($request["ca_tids"]) && count($request["ca_tids"]) < 2)
                {
                    echo 'At least select 2 dashboards for comparison';
                }
                //else if(isset($request["ca_tids"]) && count($request["ca_tids"]) > 12)
                //{
                    //echo 'You can select maximum 12 dashboards for comparison.';
                //}
                else
                {
                    $tids = '';
                    
                    for($i=0; $i<count($request["ca_tids"]); $i++)
                    {
                        $tids .= $request["ca_tids"][$i].',';
                    }
                    
                    DB::insert("INSERT INTO competitor_analysis SET ca_title = '".$request["ca_title"]."', ca_tids = '".substr($tids, 0, -1)."', ca_cid = ".$loggedin_user_id.", ca_date = NOW()");
                    $new_ca_id = DB::getPdo()->lastInsertId();
                    $this->ac_log_obj->log_customer_data($request, array("cid" => \Session::get('_loggedin_customer_id'), "caid" => $new_ca_id, "caname" => $request["ca_title"]));
                    echo 'Success';
                }
            }
            else if($request["mode"] == 'delete_record_handler')
            {
                $this->ac_log_obj->log_customer_data($request, array("cid" => \Session::get('_loggedin_customer_id'), "caid" => $request["record_id"], "caname" => $this->get_ca_name($request["record_id"])));
                DB::delete("DELETE FROM competitor_analysis WHERE ca_cid = ".$loggedin_user_id." AND ca_id = ".$request["record_id"]);
                
                echo 'Success';
            }
        }
        else
        {
            echo 'InvalidAccess';
        }
    }
    
    public function get_topic_sentiments_data($tid)
    {
        if (strpos($tid, '_') !== false) //means sub topic is also coming 
        {
            $_tid = explode("_", $tid);
            
            $topic_query_string = $this->topic_obj->get_topic_elastic_query($_tid[0]).' AND '.$this->subtopic_obj->get_subtopic_elastic_query($_tid[1]);
        }
        else
            $topic_query_string = $this->topic_obj->get_topic_elastic_query($tid);
                //echo $topic_query_string.'<br><br><br>';
        $params = [
            'index' => $this->search_index_name,
            'type' => 'mytype',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            ['query_string' => ['query' => $topic_query_string ]],
                            ['match' => ['predicted_sentiment_value' => 'positive']],
                            ['range' => ['p_created_time' => ['gte' => 'now-90d', 'lte' => 'now']]]
                        ]
                    ]
                ]
            ]
        ];
//print_r($params); exit;
        $results = $this->client->count($params);

        $pos_senti = $results["count"];

        $params = [
            'index' => $this->search_index_name,
            'type' => 'mytype',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            ['query_string' => ['query' => $topic_query_string ]],
                            ['match' => ['predicted_sentiment_value' => 'negative']],
                            ['range' => ['p_created_time' => ['gte' => 'now-90d', 'lte' => 'now']]]
                        ]
                    ]
                ]
            ]
        ];

        $results = $this->client->count($params);

        $neg_senti = $results["count"];

        $params = [
            'index' => $this->search_index_name,
            'type' => 'mytype',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            ['query_string' => ['query' => $topic_query_string ]],
                            ['match' => ['predicted_sentiment_value' => 'neutral']],
                            ['range' => ['p_created_time' => ['gte' => 'now-90d', 'lte' => 'now']]]
                        ]
                    ]
                ]
            ]
        ];

        $results = $this->client->count($params);

        $neu_senti = $results["count"];

        $response_output = trim($pos_senti.'|'.$neg_senti.'|'.$neu_senti);

        return $response_output;
    }
    
    public function get_topic_name($tid)
    {
        if (strpos($tid, '_') !== false) //means subtopic is also attached 
        {
            $ttid = explode("_", $tid);
            
            $tname = $this->topic_obj->get_topic_name($ttid[0]);
            $stname = $this->get_subtopic_name($ttid[1]);
            
            return $tname.' - '.$stname;
        }
        else
            return $this->topic_obj->get_topic_name($tid);
    }
    
    public function get_subtopic_name($stid)
    {
        $st = DB::select("SELECT exp_name FROM customer_experience WHERE exp_id = ".$stid);
        
        return $st[0]->exp_name;
    }
    
    public function get_ca_tids($caid)
    {
        $tids = DB::select("SELECT ca_tids FROM competitor_analysis WHERE ca_id = ".$caid." AND ca_cid = ".$this->cus_obj->get_parent_account_id());
        
        return $tids[0]->ca_tids;
    }
    
    public function get_subtopics($tid)
    {
        $stids = DB::select("SELECT exp_id, exp_name FROM customer_experience WHERE exp_topic_id = ".$tid);
        
        if(count($stids) > 0)
            return $stids;
        else
            return 'NA';
    }
    
    public function get_ca_name($caid)
    {
        $can = DB::select("SELECT ca_title FROM competitor_analysis WHERE ca_id = ".$caid);
        
        return count($can) > 0 ? $can[0]->ca_title : '';
    }
    
    public function get_ca_created_date($caid)
    {
        $can = DB::select("SELECT ca_date FROM competitor_analysis WHERE ca_id = ".$caid);
        
        return $can[0]->ca_date;
    }
    
    public function get_ca_data_es(Request $request)
    {
        $response_output = '';
        $inc_dec_from_date = '';
        $inc_dec_to_date = '';
        $topic_query_string_filters = '';
        
        $greater_than_time = $this->date_fetch_from_time;
        $less_than_time = $this->date_fetch_to_time;
        $days_difference = $this->date_fetch_days_number;
        
        if(isset($request["dash_filters_applied"]) && $request["dash_filters_applied"] == 'yes')
        {
            if(isset($request["from_date"]) && !empty($request["from_date"]) && !is_null($request["from_date"]))
            {
                $greater_than_time = date_create_from_format('j F, Y', $request["from_date"]);
                $greater_than_time = date_format($greater_than_time, 'Y-m-d');

            }
            else
                $greater_than_time = date("Y-m-d", strtotime('-90 day', strtotime(date("Y-m-d"))));

            if(isset($request["to_date"]) && !empty($request["to_date"]) && !is_null($request["to_date"]))
            {
                $less_than_time = date_create_from_format('j F, Y', $request["to_date"]);
                $less_than_time = date_format($less_than_time, 'Y-m-d');
            }
            else
                $less_than_time = date("Y-m-d");

            $days_difference = $this->gen_func_obj->date_difference($less_than_time, $greater_than_time);
                        
            if (isset($request["senti"]) && !empty($request["senti"]) && !is_null($request["senti"]) && $request["senti"] != 'null')
            {
                $senti = explode(",", $request["senti"]);

                $temp_str = '';

                for ($i = 0; $i < count($senti); $i++)
                {
                    $temp_str .= '"' . $senti[$i] . '" OR ';
                }

                $topic_query_string_filters .= ' AND predicted_sentiment_value:(' . substr($temp_str, 0, -4) . ')';
            }

            if (isset($request["dsource"]) && !empty($request["dsource"]) && !is_null($request["dsource"]) && $request["dsource"] != 'null')
            {
                $dsource = explode(",", $request["dsource"]);

                $temp_str = '';

                for ($i = 0; $i < count($dsource); $i++)
                {
                    $temp_str .= '"' . $dsource[$i] . '" OR ';
                }

                $topic_query_string_filters .= ' AND source:(' . substr($temp_str, 0, -4) . ')';
            }

            if (isset($request["dloc"]) && !empty($request["dloc"]) && !is_null($request["dloc"]) && $request["dloc"] != 'null')
            {
                $dloc = explode(",", $request["dloc"]);

                $temp_str = '';

                for ($i = 0; $i < count($dloc); $i++)
                {
                    $temp_str .= '"' . $dloc[$i] . '" OR ';
                }

                $topic_query_string_filters .= ' AND u_country:(' . substr($temp_str, 0, -4) . ')';
            }

            if (isset($request["dlang"]) && !empty($request["dlang"]) && !is_null($request["dlang"]) && $request["dlang"] != 'null')
            {
                $dlang = explode(",", $request["dlang"]);

                $temp_str = '';

                for ($i = 0; $i < count($dlang); $i++)
                {
                    $temp_str .= '"' . $dlang[$i] . '" OR ';
                }

                $topic_query_string_filters .= ' AND lange_detect:(' . substr($temp_str, 0, -4) . ')'; // AND u_country:('.substr($temp_str, 0, -4).')';
            }
        }
        //echo $topic_query_string;
        if (stristr($greater_than_time, 'now') === FALSE) //means dates are manually selected in dasbhoard
        {
            $tmp_tm = date("Y-m-d", strtotime($greater_than_time));
            $inc_dec_to_date = $tmp_tm;
        }
        else
        {
            $tmp_tm = strtotime('-' . $this->date_fetch_days_number . ' day', time());
            $inc_dec_to_date = date('Y-m-d', $tmp_tm);
        }

        if (stristr($less_than_time, 'now') === FALSE) //means dates are manually selected in dasbhoard
        {
            $days_diff = $this->gen_func_obj->date_difference($less_than_time, $greater_than_time);
            $tmp_tm = date("Y-m-d", strtotime('-' . $days_diff . ' day', strtotime(date("Y-m-d", strtotime($inc_dec_to_date)))));
            $inc_dec_from_date = $tmp_tm;
        }
        else
        {
            $tmp_tm = date("Y-m-d", strtotime('-' . $this->date_fetch_days_number . ' day', strtotime(date("Y-m-d", strtotime($inc_dec_to_date)))));
            $inc_dec_from_date = $tmp_tm;
        }
        
        if(isset($request["section"]) && !empty($request["section"]))
        {
            //Get dashboards ids
            $t_ids = $this->get_ca_tids($request["ca_id"]);
            $topic_ids = explode(",", $t_ids);
            $topic_names = array();
            $response_array = array();
            
            if($request["section"] == 'ca_mentions_chart')
            {
                $params = array();
                
                $params["index"] = $this->search_index_name;
                $params["type"] = 'mytype';
                $params["size"] = 0;
                $params["docvalue_fields"] = array("field" => "p_created_time", "format" => "date_time");
                $params["body"]["query"]["bool"]["must"]["range"]["p_created_time"] = array('gte' => $greater_than_time, 'lte' => $less_than_time);
                $params["body"]["aggs"]["2"]["date_histogram"] = array("field" => "p_created_time", "interval" => "1d", "min_doc_count" => 0);
                
                //Generate dynamic aggs for selected topics
                for($i=0; $i<count($topic_ids); $i++)
                {
                    if(strpos($topic_ids[$i], '_') !== false) //means subtopic is present 
                    {
                        $_tid = explode("_", $topic_ids[$i]);
                        
                        $topic_query_string = $this->topic_obj->get_topic_elastic_query($_tid[0]).' AND '.$this->subtopic_obj->get_subtopic_elastic_query($_tid[1]);
                        $topic_name = $this->get_topic_name($_tid[0]).' - '.$this->get_subtopic_name($_tid[1]);
                        $topic_names[] = $topic_name;

                        $params["body"]["aggs"]["2"]["aggs"]["3"]["filters"]["filters"][$topic_name]["query_string"] = array("query" => $topic_query_string.$topic_query_string_filters);
                    }                    
                    else
                    {
                        $topic_query_string = $this->topic_obj->get_topic_elastic_query($topic_ids[$i]);
                        $topic_name = $this->get_topic_name($topic_ids[$i]);
                        $topic_names[] = $topic_name;

                        $params["body"]["aggs"]["2"]["aggs"]["3"]["filters"]["filters"][$topic_name]["query_string"] = array("query" => $topic_query_string.$topic_query_string_filters);
                    }
                }
                //echo '<pre>'; print_r($params);
                $es_data = $this->client->search($params);
                
                for($p=0; $p<count($es_data["aggregations"]["2"]["buckets"]); $p++)
                {
                    for($jj=0; $jj<count($topic_names); $jj++)
                    {
                        $response_array["data"][$p][$topic_names[$jj]]["date"] = date("Y-m-d", strtotime($es_data["aggregations"]["2"]["buckets"][$p]["key_as_string"]));
                        $response_array["data"][$p][$topic_names[$jj]]["count"] = $es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$topic_names[$jj]]["doc_count"];
                    }
                }
                
                $output_array = array();
                $data_array = array();
                $topic_names_str = '';
                
                for($j=0; $j<count($topic_names); $j++)
                {
                    $data_str = '';
                    
                    for($k=0; $k<count($response_array["data"]); $k++)
                    {
                        $data_str .= $response_array["data"][$k][$topic_names[$j]]["date"].'~'.$response_array["data"][$k][$topic_names[$j]]["count"].'|';
                    }
                    
                    $topic_names_str .= $topic_names[$j].',';
                    
                    $data_array[$topic_names[$j]] = substr($data_str, 0, -1);
                }
                
                $output_array["data"] = $data_array;
                $output_array["topic_names"] = substr($topic_names_str, 0, -1);
                
                //echo '<pre>'; print_r($output_array);
                echo json_encode($output_array);
                //foreach ($es_data["aggregations"]["2"]["buckets"] as $key => $value)
                //{
                    //echo 'Key: '.$key.'<br>Value: '.$es_data["aggregations"]["2"]["buckets"][$key]["doc_count"].'<br><br>';
                //}
            }
            else if($request["section"] == 'ca_key_counts')
            {
                $twitter_count = 0; $reddit_count = 0; $youtube_count = 0; $vimeo_count = 0; $tumblr_count = 0; $pinterest_count = 0; $web_count = 0; $facebook_count = 0; $tripadvisor_count = 0; $fakenews_count = 0; $news_count = 0; $instagram_count = 0; $blogs_count = 0; $googlemaps_count = 0; $videos_count = 0; $news_count_data = 0;
                
                $sources_array = array("Twitter", "Facebook", "Pinterest", "Youtube", "Vimeo", "Instagram", "Blogs", "FakeNews", "News", "Reddit", "Tumblr", "Web", "Linkedin");
                
                $output_html = '<div class="col-12">';
                $output_html .= '<table width="100%" cellpadding="0" cellspacing="0" style="">';
                $output_html .= '<tr style="border-bottom: 1px solid #f0f0f0;">';
                $output_html .= '<td width="20%" style="text-align:left; padding-bottom: 10px;">Dashboards</td>'
                    . '<td width="16%" style="text-align:center; padding-bottom: 10px;"><i class="bx bx-bullseye" style="font-size:1.5rem;"></i><br><span style="text-transform: uppercase; font-size: 12px;">Mentions</span></td>'
                    . '<td width="16%" style="text-align:center; padding-bottom: 10px;"><i class="bx bx-group" style="font-size:1.5rem;"></i><br><span style="text-transform: uppercase; font-size: 12px;">Social media results</td>'
                    . '<td width="16%" style="text-align:center; padding-bottom: 10px;"><i class="bx bx-log-in-circle" style="font-size:1.5rem;"></i><br><span style="text-transform: uppercase; font-size: 12px;">Estimated social reach</td>'
                    . '<td width="16%" style="text-align:center; padding-bottom: 10px;"><i class="bx bx-hive" style="font-size:1.5rem;"></i><br><span style="text-transform: uppercase; font-size: 12px;">Beyond social</td>'
                    . '<td width="16%" style="text-align:center; padding-bottom: 10px;"><i class="bx bx-shape-circle" style="font-size:1.5rem;"></i><br><span style="text-transform: uppercase; font-size: 12px;">Engagement</td>';
                $output_html .= '</tr>';
                
                $j = 0; $initial_mentions = 0;
                
                for($i=0; $i<count($topic_ids); $i++)
                {
                    if(strpos($topic_ids[$i], '_') !== false) //means subtopic is present 
                    {
                        $_tid = explode("_", $topic_ids[$i]);
                        
                        $topic_query_string = $this->topic_obj->get_topic_elastic_query($_tid[0]).' AND '.$this->subtopic_obj->get_subtopic_elastic_query($_tid[1]);
                        $topic_name = $this->get_topic_name($_tid[0]).' - '.$this->get_subtopic_name($_tid[1]);
                        $topic_names[] = $topic_name;
                    }
                    else
                    {
                        $topic_query_string = $this->topic_obj->get_topic_elastic_query($topic_ids[$i]);
                        $topic_name = $this->get_topic_name($topic_ids[$i]);
                        $topic_names[] = $topic_name;
                    }

                    $output_html .= '<tr style="border-bottom: 1px solid #f0f0f0;">';
                    
                    $output_html .= '<td width="20%" style="text-align:left; padding: 10px 0px 10px 0px;">'.$topic_name.'</td>';
                    
                    //total mentions
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string.$topic_query_string_filters ]],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                    ]
                                ]
                            ]
                        ]
                    ];
                    
                    $results = $this->client->count($params);
                    
                    if($j == 0)
                    {
                        $initial_mentions = $results["count"];
                        $output_html .= '<td width="16%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($results["count"]).'</td>';
                    }
                    else
                    {
                        if($initial_mentions > $results["count"]) //decrease
                        {
                            $diff = $initial_mentions - $results["count"];
                            $per_diff = ($diff/$initial_mentions)*100;
                            
                            $output_html .= '<td width="16%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($results["count"]).' <i class="bx font-medium-1 text-danger bx-caret-down"></i> <small class="text-muted">-'. number_format($per_diff).'%</small></td>';
                        }
                        else //increase
                        {
                            $diff = $results["count"] - $initial_mentions;
                            if($initial_mentions > 0)
                                $per_diff = ($diff/$initial_mentions)*100;
                            else
                                $per_diff = 0;
                            
                            $output_html .= '<td width="16%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($results["count"]).' <i class="bx font-medium-1 text-success bx-caret-up"></i> <small class="text-muted">+'. number_format($per_diff).'%</small></td>';
                        }
                        
                    }//END: Total mentions
                    
                    //total social mentions
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string.$topic_query_string_filters.' AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")' ]],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                    ]
                                ]
                            ]
                        ]
                    ];
                    
                    $results = $this->client->count($params);
                                        
                    if($j == 0)
                    {
                        $initial_social_mentions = $results["count"];
                        $output_html .= '<td width="16%%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($results["count"]).'</td>';
                    }
                    else
                    {
                        if($initial_social_mentions > $results["count"]) //decrease
                        {
                            $diff = $initial_social_mentions - $results["count"];
                            $per_diff = ($diff/$initial_social_mentions)*100;
                            
                            $output_html .= '<td width="16%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($results["count"]).' <i class="bx font-medium-1 text-danger bx-caret-down"></i> <small class="text-muted">-'. number_format($per_diff).'%</small></td>';
                        }
                        else //increase
                        {
                            $diff = $results["count"] - $initial_social_mentions;
                            if($initial_social_mentions == 0)
                                $per_diff =0;
                            else
                                $per_diff = ($diff/$initial_social_mentions)*100;
                            
                            $output_html .= '<td width="16%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($results["count"]).' <i class="bx font-medium-1 text-success bx-caret-up"></i> <small class="text-muted">+'. number_format($per_diff).'%</small></td>';
                        }
                        
                    }
                    //END: Total social mentions
                    
                    //Estimated reach 50% normal user followers + 5% influencers + total engagement
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'size' => '0',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string.$topic_query_string_filters.' AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")' ]],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]],
                                        ['range' => ['u_followers' => ['gt' => 0, 'lt' => 1000]]]
                                    ]
                                ]
                            ],
                            'aggs' => [
                                'total_normal_users_followers' => ['sum' => ['field' => 'u_followers']]
                            ]
                        ]
                    ];
                    $results = $this->client->search($params);

                    $total_normal_users_followers = $results["aggregations"]["total_normal_users_followers"]["value"];
                    $normal_user_followers = ceil($total_normal_users_followers * 0.50); //50%

                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'size' => '0',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string.$topic_query_string_filters.' AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")' ]],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]],
                                        ['range' => ['u_followers' => ['gt' => 1000]]]
                                    ]
                                ]
                            ],
                            'aggs' => [
                                'total_influencer_followers' => ['sum' => ['field' => 'u_followers']]
                            ]
                        ]
                    ];

                    $results = $this->client->search($params);

                    $total_influencer_followers = $results["aggregations"]["total_influencer_followers"]["value"];
                    $influencer_user_followers = ceil($total_influencer_followers * 0.05); //5% of followers

                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'size' => '0',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string.$topic_query_string_filters.' AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")' ]],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                    ]
                                ]
                            ],
                            'aggs' => [
                                'total_shares' => ['sum' => ['field' => 'p_shares']],
                                'total_comments' => ['sum' => ['field' => 'p_comments']],
                                'total_likes' => ['sum' => ['field' => 'p_likes']],
                                'total_views' => ['sum' => ['field' => 'p_engagement']]
                            ]
                        ]
                    ];

                    $results = $this->client->search($params);
                    $tot_eng = $results["aggregations"]["total_shares"]["value"] + $results["aggregations"]["total_comments"]["value"] + $results["aggregations"]["total_likes"]["value"] + $results["aggregations"]["total_views"]["value"];
                    $estimated_reach = $normal_user_followers + $influencer_user_followers + $tot_eng;
                    
                    if($j == 0)
                    {
                        $initial_estimated_reach = $estimated_reach;
                        $output_html .= '<td width="16%%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($estimated_reach).'</td>';
                    }
                    else
                    {
                        if($initial_estimated_reach > $estimated_reach) //decrease
                        {
                            $diff = $initial_estimated_reach - $estimated_reach;
                            $per_diff = ($diff/$initial_estimated_reach)*100;
                            
                            $output_html .= '<td width="16%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($estimated_reach).' <i class="bx font-medium-1 text-danger bx-caret-down"></i> <small class="text-muted">-'. number_format($per_diff).'%</small></td>';
                        }
                        else //increase
                        {
                            $diff = $estimated_reach - $initial_estimated_reach;
                            if($initial_estimated_reach == 0)
                                $per_diff = 0;
                            else
                                $per_diff = ($diff/$initial_estimated_reach)*100;
                            
                            $output_html .= '<td width="16%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($estimated_reach).' <i class="bx font-medium-1 text-success bx-caret-up"></i> <small class="text-muted">+'. number_format($per_diff).'%</small></td>';
                        }
                        
                    }                    
                    //END: Estimated reach
                    
                    //Beyond social
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string.$topic_query_string_filters.' AND source:("khaleej_times" OR "Omanobserver" OR "Time of oman" OR "Blogs" OR "FakeNews" OR "News")' ]],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                    ]
                                ]
                            ]
                        ]
                    ];
                    
                    $results = $this->client->count($params);
                    
                    if($j == 0)
                    {
                        $initial_beyond_social_mentions = $results["count"];
                        $output_html .= '<td width="16%%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($results["count"]).'</td>';
                    }
                    else
                    {
                        if($initial_beyond_social_mentions > $results["count"]) //decrease
                        {
                            $diff = $initial_beyond_social_mentions - $results["count"];
                            if($initial_beyond_social_mentions > 0)
                                $per_diff = ($diff/$initial_beyond_social_mentions)*100;
                            else
                                $per_diff = ($diff/1)*100;
                            
                            $output_html .= '<td width="16%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($results["count"]).' <i class="bx font-medium-1 text-danger bx-caret-down"></i> <small class="text-muted">-'. number_format($per_diff).'%</small></td>';
                        }
                        else //increase
                        {
                            $diff = $results["count"] - $initial_beyond_social_mentions;
                            if($initial_beyond_social_mentions > 0)
                                $per_diff = ($diff/$initial_beyond_social_mentions)*100;
                            else
                                $per_diff = ($diff/1)*100;
                            
                            $output_html .= '<td width="16%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($results["count"]).' <i class="bx font-medium-1 text-success bx-caret-up"></i> <small class="text-muted">+'. number_format($per_diff).'%</small></td>';
                        }
                    }
                    //END: Beyond social
                    
                    //Engagement
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'size' => '0',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string.$topic_query_string_filters]],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                    ]
                                ]
                            ],
                            'aggs' => [
                                'total_shares' => ['sum' => ['field' => 'p_shares']],
                                'total_comments' => ['sum' => ['field' => 'p_comments']],
                                'total_likes' => ['sum' => ['field' => 'p_likes']],
                                'total_views' => ['sum' => ['field' => 'p_engagement']]
                            ]
                        ]
                    ];

                    $results = $this->client->search($params);
                    $tot_eng = $results["aggregations"]["total_shares"]["value"] + $results["aggregations"]["total_comments"]["value"] + $results["aggregations"]["total_likes"]["value"] + $results["aggregations"]["total_views"]["value"];
                    //$output_html .= '<td width="16%%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($tot_eng).'</td>';
                    
                    if($j == 0)
                    {
                        $initial_total_eng = $tot_eng;
                        $output_html .= '<td width="16%%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($tot_eng).'</td>';
                    }
                    else
                    {
                        if($initial_total_eng > $tot_eng) //decrease
                        {
                            $diff = $initial_total_eng - $tot_eng;
                            $per_diff = ($diff/$initial_total_eng)*100;
                            
                            $output_html .= '<td width="16%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($tot_eng).' <i class="bx font-medium-1 text-danger bx-caret-down"></i> <small class="text-muted">-'. number_format($per_diff).'%</small></td>';
                        }
                        else //increase
                        {
                            $diff = $tot_eng - $initial_total_eng;
                            if($initial_total_eng == 0)
                                $per_diff = ($diff/1)*100;
                            else
                                $per_diff = ($diff/$initial_total_eng)*100;
                            
                            $output_html .= '<td width="16%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($tot_eng).' <i class="bx font-medium-1 text-success bx-caret-up"></i> <small class="text-muted">+'. number_format($per_diff).'%</small></td>';
                        }
                    }
                    //END: Engagement
                    
                    $output_html .= '</tr>';
                    $j = $j+1;
                        /*. 
                        . 
                        . 
                        . '<td width="11.4%%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($videos_count).'</td>'
                        . '<td width="11.4%%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($news_count_data).'</td>'
                        . '<td width="11.4%%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($reddit_count).'</td>'
                        . '<td width="11.4%%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($blogs_count).'</td>'
                        . '<td width="11.4%%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($tumblr_count).'</td>';*/
                    
                    
                    
                }
                
                $output_html .= '</table>';
                $output_html .= '</div>';
                
                echo $output_html;
                
            }
            else if($request["section"] == 'ca_engagement_chart')
            {
                $params = array();
                
                $params["index"] = $this->search_index_name;
                $params["type"] = 'mytype';
                $params["size"] = 0;
                $params["docvalue_fields"] = array("field" => "p_created_time", "format" => "date_time");
                $params["body"]["query"]["bool"]["must"]["range"]["p_created_time"] = array('gte' => $greater_than_time, 'lte' => $less_than_time);
                $params["body"]["aggs"]["2"]["date_histogram"] = array("field" => "p_created_time", "interval" => "1d", "min_doc_count" => 0);
                
                //Generate dynamic aggs for selected topics
                for($i=0; $i<count($topic_ids); $i++)
                {
                    if(strpos($topic_ids[$i], '_') !== false) //means subtopic is present 
                    {
                        $_tid = explode("_", $topic_ids[$i]);
                        
                        $topic_query_string = $this->topic_obj->get_topic_elastic_query($_tid[0]).' AND '.$this->subtopic_obj->get_subtopic_elastic_query($_tid[1]);
                        $topic_name = $this->get_topic_name($_tid[0]).' - '.$this->get_subtopic_name($_tid[1]);
                        $topic_names[] = $topic_name;

                        $params["body"]["aggs"]["2"]["aggs"]["3"]["filters"]["filters"][$topic_name]["query_string"] = array("query" => $topic_query_string.$topic_query_string_filters);
                    }
                    else
                    {
                        $topic_query_string = $this->topic_obj->get_topic_elastic_query($topic_ids[$i]);
                        $topic_name = $this->get_topic_name($topic_ids[$i]);
                        $topic_names[] = $topic_name;

                        $params["body"]["aggs"]["2"]["aggs"]["3"]["filters"]["filters"][$topic_name]["query_string"] = array("query" => $topic_query_string.$topic_query_string_filters);
                    }
                }
                
                $params["body"]["aggs"]["2"]["aggs"]["3"]["aggs"]["total_shares"]["sum"] = array('field' => 'p_shares');
                $params["body"]["aggs"]["2"]["aggs"]["3"]["aggs"]["total_likes"]["sum"] = array('field' => 'p_likes');
                $params["body"]["aggs"]["2"]["aggs"]["3"]["aggs"]["total_comments"]["sum"] = array('field' => 'p_comments');
                
                
                $es_data = $this->client->search($params);
                //echo '<pre>'; print_r($es_data);
                for($p=0; $p<count($es_data["aggregations"]["2"]["buckets"]); $p++)
                {
                    for($jj=0; $jj<count($topic_names); $jj++)
                    {
                        $response_array["data"][$p][$topic_names[$jj]]["date"] = date("Y-m-d", strtotime($es_data["aggregations"]["2"]["buckets"][$p]["key_as_string"]));
                        
                        //get likes, shares and comments
                        $tot_eng = $es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$topic_names[$jj]]["total_comments"]["value"] + $es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$topic_names[$jj]]["total_shares"]["value"] + $es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$topic_names[$jj]]["total_likes"]["value"];
                        $response_array["data"][$p][$topic_names[$jj]]["count"] = $tot_eng;
                    }
                }
                
                $output_array = array();
                $data_array = array();
                $topic_names_str = '';
                
                for($j=0; $j<count($topic_names); $j++)
                {
                    $data_str = '';
                    
                    for($k=0; $k<count($response_array["data"]); $k++)
                    {
                        $data_str .= $response_array["data"][$k][$topic_names[$j]]["date"].'~'.$response_array["data"][$k][$topic_names[$j]]["count"].'|';
                    }
                    
                    $topic_names_str .= $topic_names[$j].',';
                    
                    $data_array[$topic_names[$j]] = substr($data_str, 0, -1);
                }
                
                $output_array["data"] = $data_array;
                $output_array["topic_names"] = substr($topic_names_str, 0, -1);
                
                //echo '<pre>'; print_r($output_array);
                echo json_encode($output_array);
                //foreach ($es_data["aggregations"]["2"]["buckets"] as $key => $value)
                //{
                    //echo 'Key: '.$key.'<br>Value: '.$es_data["aggregations"]["2"]["buckets"][$key]["doc_count"].'<br><br>';
                //}
            }
            else if($request["section"] == 'ca_main_source_channels')
            {
                $twitter_count = 0; $reddit_count = 0; $youtube_count = 0; $vimeo_count = 0; $tumblr_count = 0; $pinterest_count = 0; $web_count = 0; $facebook_count = 0; $tripadvisor_count = 0; $fakenews_count = 0; $news_count = 0; $instagram_count = 0; $blogs_count = 0; $googlemaps_count = 0; $videos_count = 0; $news_count_data = 0;
                
                $sources_array = array("Twitter", "Facebook", "Pinterest", "Youtube", "Vimeo", "Instagram", "Blogs", "FakeNews", "News", "Reddit", "Tumblr", "Web", "Linkedin");
                //Blogs, News & Web are combined as Web
                
                $output_html = '<div class="col-12">';
                $output_html .= '<table width="100%" cellpadding="0" cellspacing="0" style="">';
                $output_html .= '<tr style="border-bottom: 1px solid #f0f0f0;">';
                $output_html .= '<td width="20%" style="text-align:left; padding-bottom: 10px;">Dashboards</td>'
                    . '<td width="7.2%" style="text-align:center; padding-bottom: 10px;"><i class="bx bxl-twitter" style="color: #00ABEA !important; font-size:2rem;"></i><br><span style="text-transform: uppercase; font-size: 12px;">Twitter</span></td>'
                    . '<td width="7.2%" style="text-align:center;"><i class="bx bxl-facebook-square" style="color: #3B5998 !important; font-size:2rem;"></i><br><span style="text-transform: uppercase; font-size: 12px;">Facebook</td>'
                    . '<td width="7.2%" style="text-align:center;"><i class="bx bxl-youtube" style="color: #FF0000 !important; font-size:2rem;"></i><br><span style="text-transform: uppercase; font-size: 12px;">Videos</td>'
                    //. '<td width="7.2%" style="text-align:center;"><i class="bx bx-news" style="color: #77BD9D !important; font-size:2rem;"></i><br><span style="text-transform: uppercase; font-size: 12px;">News</td>'
                    . '<td width="7.2%" style="text-align:center;"><i class="bx bxl-reddit" style="color: #FF4301 !important; font-size:2rem;"></i><br><span style="text-transform: uppercase; font-size: 12px;">Reddit</td>'
                    //. '<td width="7.2%" style="text-align:center;"><i class="bx bxl-blogger" style="color: #F57D00 !important; font-size:2rem;"></i><br><span style="text-transform: uppercase; font-size: 12px;">Blogs</td>'
                    . '<td width="7.2%" style="text-align:center;"><i class="bx bxl-tumblr" style="color: #34526F !important; font-size:2rem;"></i><br><span style="text-transform: uppercase; font-size: 12px;">Tumblr</td>'
                    . '<td width="7.2%" style="text-align:center;"><i class="bx bxl-pinterest" style="color: #E60023 !important; font-size:2rem;"></i><br><span style="text-transform: uppercase; font-size: 12px;">Pinterest</td>'
                    . '<td width="7.2%" style="text-align:center;"><i class="bx bx-globe" style="color: #b6aa6e !important; font-size:2rem;"></i><br><span style="text-transform: uppercase; font-size: 12px;">Web</td>'
                    . '<td width="7.2%" style="text-align:center;"><i class="bx bxl-instagram-alt" style="color: #E4405F !important; font-size:2rem;"></i><br><span style="text-transform: uppercase; font-size: 12px;">Instagram</td>'
                    . '<td width="7.2%" style="text-align:center;"><i class="bx bxl-linkedin-square" style="color: #0072b1 !important; font-size:2rem;"></i><br><span style="text-transform: uppercase; font-size: 12px;">Linkedin</td>';
                $output_html .= '</tr>';
                
                for($i=0; $i<count($topic_ids); $i++)
                {
                    if(strpos($topic_ids[$i], '_') !== false) //means subtopic is present 
                    {
                        $_tid = explode("_", $topic_ids[$i]);
                        
                        $topic_query_string = $this->topic_obj->get_topic_elastic_query($_tid[0]).' AND '.$this->subtopic_obj->get_subtopic_elastic_query($_tid[1]);
                        $topic_name = $this->get_topic_name($_tid[0]).' - '.$this->get_subtopic_name($_tid[1]);
                        $topic_names[] = $topic_name;
                    }
                    else
                    {
                        $topic_query_string = $this->topic_obj->get_topic_elastic_query($topic_ids[$i]);
                        $topic_name = $this->get_topic_name($topic_ids[$i]);
                        $topic_names[] = $topic_name;
                    }                    
                    
                    for($a=0; $a<count($sources_array); $a++)
                    {
                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            ['query_string' => ['query' => $topic_query_string.$topic_query_string_filters . ' AND source:("'.$sources_array[$a].'")']],
                                            ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                        ]
                                    ]
                                ]
                            ]
                        ];

                        $results = $this->client->count($params);

                        if($sources_array[$a] == 'Twitter')
                            $twitter_count = $results["count"];
                        else if($sources_array[$a] == 'Youtube')
                            $youtube_count = $results["count"];
                        else if($sources_array[$a] == 'Vimeo')
                            $vimeo_count = $results["count"];
                        else if($sources_array[$a] == 'FakeNews')
                            $fakenews_count = $results["count"];
                        else if($sources_array[$a] == 'News')
                            $news_count = $results["count"];
                        else if($sources_array[$a] == 'Pinterest')
                            $pinterest_count = $results["count"];
                        else if($sources_array[$a] == 'Instagram')
                            $instagram_count = $results["count"];
                        else if($sources_array[$a] == 'Blogs')
                            $blogs_count = $results["count"];
                        else if($sources_array[$a] == 'Reddit')
                            $reddit_count = $results["count"];
                        else if($sources_array[$a] == 'Tumblr')
                            $tumblr_count = $results["count"];
                        else if($sources_array[$a] == 'Facebook')
                            $facebook_count = $results["count"];
                        else if($sources_array[$a] == 'Web')
                            $web_count = $results["count"];
                        else if($sources_array[$a] == 'Linkedin')
                            $linkedin_count = $results["count"];
                    }
                    
                    $videos_count = $youtube_count + $vimeo_count;
                    $news_count_data = $news_count + $fakenews_count;
                    
                    $output_html .= '<tr style="border-bottom: 1px solid #f0f0f0;">'
                        . '<td width="20%" style="text-align:left; padding: 10px 0px 10px 0px;">'.$topic_name.'</td>'
                        . '<td width="8.9%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($twitter_count).'</td>'
                        . '<td width="8.9%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($facebook_count).'</td>'
                        . '<td width="8.9%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($videos_count).'</td>'
                        //. '<td width="7.2%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($news_count_data).'</td>'
                        . '<td width="8.9%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($reddit_count).'</td>'
                        //. '<td width="7.2%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($blogs_count).'</td>'
                        . '<td width="8.9%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($tumblr_count).'</td>'
                        . '<td width="8.9%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($pinterest_count).'</td>'
                        . '<td width="8.9%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($web_count+$news_count+$blogs_count).'</td>'
                        . '<td width="8.9%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($instagram_count).'</td>'
                        . '<td width="8.9%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($linkedin_count).'</td>';
                    $output_html .= '</tr>';
                }
                
                $output_html .= '</table>';
                $output_html .= '</div>';
                
                echo $output_html;
                
            }
            else if($request["section"] == 'ca_emotions_group_bar_chart')
            {
                $anger_count = 0; $fear_count = 0; $happy_count = 0; $sadness_count = 0; $surprise_count = 0;
                $topic_names = array();
                $response_array = array();
                
                $emos_array = array("anger", "fear", "happy", "sadness", "surprise");
                
                for($a=0; $a<count($emos_array); $a++)
                {
                    for($i=0; $i<count($topic_ids); $i++)
                    {
                        if(strpos($topic_ids[$i], '_') !== false) //means subtopic is present 
                        {
                            $_tid = explode("_", $topic_ids[$i]);

                            $topic_query_string = $this->topic_obj->get_topic_elastic_query($_tid[0]).' AND '.$this->subtopic_obj->get_subtopic_elastic_query($_tid[1]);
                            $topic_name = $this->get_topic_name($_tid[0]).' - '.$this->get_subtopic_name($_tid[1]);
                        }
                        else
                        {
                            $topic_query_string = $this->topic_obj->get_topic_elastic_query($topic_ids[$i]);
                            $topic_name = $this->get_topic_name($topic_ids[$i]);
                        }
                        
                        if(!in_array($topic_name, $topic_names))
                            $topic_names[] = $topic_name;
                        
                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            ['query_string' => ['query' => $topic_query_string.$topic_query_string_filters . ' AND emotion_detector:("'.$emos_array[$a].'")']],
                                            ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                        ]
                                    ]
                                ]
                            ]
                        ];

                        $results = $this->client->count($params);
                        
                        if($i==0)
                            $response_array["emo_data"][$a]["name"] = $emos_array[$a];
                        
                        $response_array["emo_data"][$a]["data"][] = $results["count"];
                        $response_array["emo_data"][$a]["tid"][] = $topic_ids[$i];
                    }
                }
                
                $response_array["topic_names"] = $topic_names;
                $response_array["topic_ids"] = $topic_ids;
                                
                echo json_encode($response_array);
            }
            else if($request["section"] == 'ca_sentiments_charts')
            {
                $pos_count = 0; $neg_count = 0; $neu_count = 0;
                $response_array = array();
                
                
                $senti_array = array("positive", "negative", "neutral");
                
                for($i=0; $i<count($topic_ids); $i++)
                {
                    $senti_data_array = array();
                    
                    for($a=0; $a<count($senti_array); $a++)
                    {
                        if(strpos($topic_ids[$i], '_') !== false) //means subtopic is present 
                        {
                            $_tid = explode("_", $topic_ids[$i]);

                            $topic_query_string = $this->topic_obj->get_topic_elastic_query($_tid[0]).' AND '.$this->subtopic_obj->get_subtopic_elastic_query($_tid[1]);
                        }
                        else
                            $topic_query_string = $this->topic_obj->get_topic_elastic_query($topic_ids[$i]);
                        
                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            ['query_string' => ['query' => $topic_query_string.$topic_query_string_filters . ' AND predicted_sentiment_value:("'.$senti_array[$a].'")']],
                                            ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                        ]
                                    ]
                                ]
                            ]
                        ];

                        $results = $this->client->count($params);
                        
                        $senti_data_array[] = $results["count"];
                        //if($a === 0)
                            
                        
                        //$response_array["emo_data"][$a]["data"][] = $results["count"];
                    }
                    
                    $response_array["emo_data"][$i]["tid"] = $topic_ids[$i];
                    $response_array["emo_data"][$i]["data"] = $senti_data_array;
                }
                
                echo json_encode($response_array);
            }
            else if($request["section"] == 'ca_reach')
            {
                $topic_names = array();
                $reach_array = array();
                
                for($i=0; $i<count($topic_ids); $i++)
                {
                    if(strpos($topic_ids[$i], '_') !== false) //means subtopic is present 
                    {
                        $_tid = explode("_", $topic_ids[$i]);
                        
                        $topic_query_string = $this->topic_obj->get_topic_elastic_query($_tid[0]).' AND '.$this->subtopic_obj->get_subtopic_elastic_query($_tid[1]);
                        $topic_name = $this->get_topic_name($_tid[0]).' - '.$this->get_subtopic_name($_tid[1]);
                        $topic_names[] = $topic_name;
                    }
                    else
                    {
                        $topic_query_string = $this->topic_obj->get_topic_elastic_query($topic_ids[$i]);
                        $topic_name = $this->get_topic_name($topic_ids[$i]);
                        $topic_names[] = $topic_name;
                    }
                    
                    //estimated reach 50% normal user followers + 5% influencers + total engagement
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'size' => '0',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string.$topic_query_string_filters]],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]],
                                        ['range' => ['u_followers' => ['gt' => 0, 'lt' => 1000]]]
                                    ]
                                ]
                            ],
                            'aggs' => [
                                'total_normal_users_followers' => ['sum' => ['field' => 'u_followers']]
                            ]
                        ]
                    ];
                    
                    $results = $this->client->search($params);

                    $total_normal_users_followers = $results["aggregations"]["total_normal_users_followers"]["value"];
                    $normal_user_followers = ceil($total_normal_users_followers * 0.50); //50%

                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'size' => '0',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string.$topic_query_string_filters]],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]],
                                        ['range' => ['u_followers' => ['gt' => 1000]]]
                                    ]
                                ]
                            ],
                            'aggs' => [
                                'total_influencer_followers' => ['sum' => ['field' => 'u_followers']]
                            ]
                        ]
                    ];

                    $results = $this->client->search($params);

                    $total_influencer_followers = $results["aggregations"]["total_influencer_followers"]["value"];
                    $influencer_user_followers = ceil($total_influencer_followers * 0.05); //5% of followers

                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'size' => '0',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string.$topic_query_string_filters]],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                    ]
                                ]
                            ],
                            'aggs' => [
                                'total_shares' => ['sum' => ['field' => 'p_shares']],
                                'total_comments' => ['sum' => ['field' => 'p_comments']],
                                'total_likes' => ['sum' => ['field' => 'p_likes']],
                                'total_views' => ['sum' => ['field' => 'p_engagement']]
                            ]
                        ]
                    ];

                    $results = $this->client->search($params);
                    $tot_eng = $results["aggregations"]["total_shares"]["value"] + $results["aggregations"]["total_comments"]["value"] + $results["aggregations"]["total_likes"]["value"] + $results["aggregations"]["total_views"]["value"];
                    $estimated_reach = $normal_user_followers + $influencer_user_followers + $tot_eng;

                    $reach_array[$topic_name] = $this->gen_func_obj->format_number_data($estimated_reach);
                }
                
                echo json_encode($reach_array);
            }
            else if($request["section"] == 'ca_influencers')
            {
                $data_array = array();
                $output_data = array();
                
                $inf_types = array("nano", "micro", "midtier", "macro", "mega", "celebrity");
                //echo 'hi';
                for($k=0; $k<count($topic_ids); $k++)
                {
                    if(strpos($topic_ids[$i], '_') !== false) //means subtopic is present 
                    {
                        $_tid = explode("_", $topic_ids[$i]);
                        
                        $topic_query_string = $this->topic_obj->get_topic_elastic_query($_tid[0]).' AND '.$this->subtopic_obj->get_subtopic_elastic_query($_tid[1]);
                    }
                    else
                        $topic_query_string = $this->topic_obj->get_topic_elastic_query($topic_ids[$k]);
                                        
                    //echo $topic_query_string; exit;
                    for($l=0; $l<count($inf_types); $l++)
                    {
                        if ($inf_types[$l] == 'nano')
                        {
                            $followers_from = 1000;
                            $followers_to = 10000;
                        }
                        else if ($inf_types[$l] == 'micro')
                        {
                            $followers_from = 10000;
                            $followers_to = 50000;
                        }
                        else if ($inf_types[$l] == 'midtier')
                        {
                            $followers_from = 50000;
                            $followers_to = 500000;
                        }
                        else if ($inf_types[$l] == 'macro')
                        {
                            $followers_from = 500000;
                            $followers_to = 1000000;
                        }
                        else if ($inf_types[$l] == 'mega')
                        {
                            $followers_from = 1000000;
                            $followers_to = 5000000;
                        }
                        else if ($inf_types[$l] == 'celebrity')
                        {
                            $followers_from = 5000000;
                            $followers_to = 500000000;
                        }
                        
                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            ['query_string' => ['query' => $topic_query_string.$topic_query_string_filters ]],
                                            ['exists' => ['field' => 'u_profile_photo']],
                                            ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]],
                                            ['range' => ['u_followers' => ['gte' => $followers_from, 'lte' => $followers_to]]]
                                        ],
                                        'must_not' => [
                                            ['term' => ['u_profile_photo.keyword' => '']]
                                        ]
                                    ]
                                ],
                                'aggs' => [
                                    'group_by_user' => ['terms' => ['field' => 'u_source.keyword', 'size' => 5, 'order' => ['followers_count' => 'desc']],
                                        'aggs' => [
                                            'grouped_results' => [
                                                'top_hits' => ['size' => 1, '_source' => ['include' => ['u_fullname', 'u_profile_photo', 'u_country', 'u_followers', 'source', 'u_source']],
                                                    'sort' => ['p_created_time' => ['order' => 'desc']]
                                                ]
                                            ],
                                            'followers_count' => ['max' => ['script' => 'doc.u_followers']]
                                        ]
                                    ]
                                ]
                            ]
                        ];

                        $results = $this->client->search($params);

                        $n = 1;
                        $j = 0;
                        $u_html = '';
                        $data_array = array();

                        for ($i = 0; $i < count($results["aggregations"]["group_by_user"]["buckets"]); $i ++)
                        {
                            if (!empty($results["aggregations"]["group_by_user"]["buckets"][$i]["key"]))
                            {
                                if(isset($results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_country"]))
                                    $flag_image = '<img src="images/flags/4x3/'.$this->gen_func_obj->get_country_flag($results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_country"]).'" width="35">';
                                else
                                    $flag_image = '&nbsp;';

                                $user_source = $results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["source"];

                                if ($user_source == 'Twitter')
                                    $source_icon = '<i class="bx bxl-twitter mr-25 align-middle" title="Twitter" style="font-size: 35px; color: #00abea !important;"></i>';
                                else if ($user_source == 'Youtube')
                                    $source_icon = '<i class="bx bxl-youtube mr-25 align-middle" title="YouTube" style="font-size: 35px; color: #cd201f !important;"></i>';
                                else if ($user_source == 'Linkedin')
                                    $source_icon = '<i class="bx bxl-linkedin mr-25 align-middle" title="Linkedin" style="font-size: 35px; color: #365d98 !important;"></i>';
                                else if ($user_source == 'Facebook')
                                    $source_icon = '<i class="bx bxl-facebook-square mr-25 align-middle" title="Facebook" style="font-size: 35px; color: #365d98 !important;"></i>';
                                else if ($user_source == 'Pinterest')
                                    $source_icon = '<i class="bx bxl-pinterest mr-25 align-middle" title="Pinterest" style="font-size: 35px; color: #bd081c !important;"></i>';
                                else if ($user_source == 'Instagram')
                                    $source_icon = '<i class="bx bxl-instagram mr-25 align-middle" title="Instagram" style="font-size: 35px; color: #e4405f !important;"></i>';
                                else if ($user_source == 'khaleej_times' || $user_source == 'Omanobserver' || $user_source == 'Time of oman' || $user_source == 'Blogs')
                                    $source_icon = '<i class="bx bxs-book mr-25 align-middle" title="Blog" style="font-size: 35px; color: #f57d00 !important;"></i>';
                                else if ($user_source == 'Reddit')
                                    $source_icon = '<i class="bx bxl-reddit mr-25 align-middle" title="Reddit" style="font-size: 35px; color: #ff4301 !important;"></i>';
                                else if ($user_source == 'FakeNews' || $user_source == 'News')
                                    $source_icon = '<i class="bx bx-news mr-25 align-middle" title="News" style="font-size: 35px; color: #77BD9D !important;"></i>';
                                else if ($user_source == 'Tumblr')
                                    $source_icon = '<i class="bx bxl-tumblr mr-25 align-middle" title="Tumblr" style="font-size: 35px; color: #34526f !important;"></i>';
                                else if ($user_source == 'Vimeo')
                                    $source_icon = '<i class="bx bxl-vimeo mr-25 align-middle" title="Vimeo" style="font-size: 35px; color: #86c9ef !important;"></i>';
                                else if ($user_source == 'Web' || $user_source == 'DeepWeb')
                                    $source_icon = '<i class="bx bx-globe mr-25 align-middle" title="Web" style="font-size: 35px; color: #FF7D02 !important;"></i>';
                                else
                                    $source_icon = '';

                                $data_array[$i]['profile_image'] = $results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_profile_photo"];
                                $data_array[$i]['fullname'] = $results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_fullname"];
                                $data_array[$i]['source'] = '<a href="'.$results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_source"].'" target="_blank">'.$source_icon.'</a>';
                                $data_array[$i]['country'] = $flag_image;
                                $data_array[$i]['followers'] = $this->gen_func_obj->format_number_data($results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_followers"]);
                                $data_array[$i]['posts'] = $this->gen_func_obj->format_number_data($results["aggregations"]["group_by_user"]["buckets"][$i]["doc_count"]);
                            }
                        }
                        
                        $output_data[$inf_types[$l]."_".$topic_ids[$k]] = $data_array;
                            
                    }
                }
                //echo 'hi';
                //print_r($output_data);
                echo json_encode($output_data);
            }
            else if($request["section"] == 'ca_influencers_list')
            {
                $topic_id = $request["ca_tid"];
                
                $inf_types = array("nano", "micro", "midtier", "macro", "mega", "celebrity");
                
                if(strpos($topic_id, '_') !== false) //means subtopic is present 
                {
                    $_tid = explode("_", $topic_id);

                    $topic_query_string = $this->topic_obj->get_topic_elastic_query($_tid[0]).' AND '.$this->subtopic_obj->get_subtopic_elastic_query($_tid[1]);
                }
                else
                    $topic_query_string = $this->topic_obj->get_topic_elastic_query($topic_id);
                
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'size' => 0,
                    'docvalue_fields' => array("field" => "p_created_time", "format" => "date_time"),
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['exists' => ['field' => 'u_profile_photo']],
                                    [ 'range' => [ 'p_created_time' => [ 'gte' => 'now-90d', 'lte' => 'now' ] ] ]
                                ],
                                'must_not' => [
                                    ['term' => ['u_profile_photo.keyword' => '']]
                                ]
                            ]
                        ],
                        'aggs' => [
                            'inf_data' => [
                                'filters' => [
                                    'filters' => [
                                        $inf_types[0] => [ 'query_string' => [ 'query' => '(u_followers:>1000 AND u_followers:<=10000) AND '.$topic_query_string, 'analyze_wildcard' => true, 'default_field' => '*' ] ],
                                        $inf_types[1] => [ 'query_string' => [ 'query' => '(u_followers:>10000 AND u_followers:<=50000) AND '.$topic_query_string, 'analyze_wildcard' => true, 'default_field' => '*' ] ],
                                        $inf_types[2] => [ 'query_string' => [ 'query' => '(u_followers:>50000 AND u_followers:<=500000) AND '.$topic_query_string, 'analyze_wildcard' => true, 'default_field' => '*' ] ],
                                        $inf_types[3] => [ 'query_string' => [ 'query' => '(u_followers:>500000 AND u_followers:<=1000000) AND '.$topic_query_string, 'analyze_wildcard' => true, 'default_field' => '*' ] ],
                                        $inf_types[4] => [ 'query_string' => [ 'query' => '(u_followers:>1000000 AND u_followers:<=5000000) AND '.$topic_query_string, 'analyze_wildcard' => true, 'default_field' => '*' ] ],
                                        $inf_types[5] => [ 'query_string' => [ 'query' => '(u_followers:>5000000 AND u_followers:<=500000000) AND '.$topic_query_string, 'analyze_wildcard' => true, 'default_field' => '*' ] ],
                                    ],
                                ],
                                'aggs' => [
                                    'group_by_user' => [
                                        'terms' => [ 'field' => 'u_source.keyword', 'order' => [ 'followers_count' => 'desc', ], 'size' => 5 ],
                                        'aggs' => [
                                            'top_sales_hits' => [
                                                'top_hits' => [ '_source' => [ 'includes' => [ 'source', 'u_country', 'u_followers', 'u_fullname', 'u_profile_photo', 'source', 'u_source'] ], 'size' => 1 ],
                                            ],
                                            'followers_count' => [ 'max' => [ 'script' => 'doc.u_followers'] ],
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];
                
                $results = $this->client->search($params);
                //echo '<pre>';
                //print_r($results);
                $k = 0;
                $data_array = array();
                
                for($i=0; $i<count($inf_types); $i++)
                {
                    $k = 0;
                    $data_array = array();
                    
                    //echo count($results["aggregations"]["inf_data"]["buckets"][$inf_types[$i]]["group_by_user"]["buckets"]).' -- ';
                    for($j=0; $j<count($results["aggregations"]["inf_data"]["buckets"][$inf_types[$i]]["group_by_user"]["buckets"]); $j++)
                    {
                        if(isset($results["aggregations"]["inf_data"]["buckets"][$inf_types[$i]]["group_by_user"]["buckets"][$j]["top_sales_hits"]["hits"]["hits"][0]["_source"]["u_country"]))
                            $flag_image = '<img src="images/flags/4x3/'.$this->gen_func_obj->get_country_flag($results["aggregations"]["inf_data"]["buckets"][$inf_types[$i]]["group_by_user"]["buckets"][$j]["top_sales_hits"]["hits"]["hits"][0]["_source"]["u_country"]).'" width="35">';
                        else
                            $flag_image = '&nbsp;';
                        
                        $user_source = $results["aggregations"]["inf_data"]["buckets"][$inf_types[$i]]["group_by_user"]["buckets"][$j]["top_sales_hits"]["hits"]["hits"][0]["_source"]["source"];
                        
                        if ($user_source == 'Twitter')
                            $source_icon = '<i class="bx bxl-twitter mr-25 align-middle" title="Twitter" style="font-size: 35px; color: #00abea !important;"></i>';
                        else if ($user_source == 'Youtube')
                            $source_icon = '<i class="bx bxl-youtube mr-25 align-middle" title="YouTube" style="font-size: 35px; color: #cd201f !important;"></i>';
                        else if ($user_source == 'Linkedin')
                            $source_icon = '<i class="bx bxl-linkedin mr-25 align-middle" title="Linkedin" style="font-size: 35px; color: #365d98 !important;"></i>';
                        else if ($user_source == 'Facebook')
                            $source_icon = '<i class="bx bxl-facebook-square mr-25 align-middle" title="Facebook" style="font-size: 35px; color: #365d98 !important;"></i>';
                        else if ($user_source == 'Pinterest')
                            $source_icon = '<i class="bx bxl-pinterest mr-25 align-middle" title="Pinterest" style="font-size: 35px; color: #bd081c !important;"></i>';
                        else if ($user_source == 'Instagram')
                            $source_icon = '<i class="bx bxl-instagram mr-25 align-middle" title="Instagram" style="font-size: 35px; color: #e4405f !important;"></i>';
                        else if ($user_source == 'khaleej_times' || $user_source == 'Omanobserver' || $user_source == 'Time of oman' || $user_source == 'Blogs')
                            $source_icon = '<i class="bx bxs-book mr-25 align-middle" title="Blog" style="font-size: 35px; color: #f57d00 !important;"></i>';
                        else if ($user_source == 'Reddit')
                            $source_icon = '<i class="bx bxl-reddit mr-25 align-middle" title="Reddit" style="font-size: 35px; color: #ff4301 !important;"></i>';
                        else if ($user_source == 'FakeNews' || $user_source == 'News')
                            $source_icon = '<i class="bx bx-news mr-25 align-middle" title="News" style="font-size: 35px; color: #77BD9D !important;"></i>';
                        else if ($user_source == 'Tumblr')
                            $source_icon = '<i class="bx bxl-tumblr mr-25 align-middle" title="Tumblr" style="font-size: 35px; color: #34526f !important;"></i>';
                        else if ($user_source == 'Vimeo')
                            $source_icon = '<i class="bx bxl-vimeo mr-25 align-middle" title="Vimeo" style="font-size: 35px; color: #86c9ef !important;"></i>';
                        else if ($user_source == 'Web' || $user_source == 'DeepWeb')
                            $source_icon = '<i class="bx bx-globe mr-25 align-middle" title="Web" style="font-size: 35px; color: #FF7D02 !important;"></i>';
                        else
                            $source_icon = '';
                        
                        $data_array[$k]['profile_image'] = $results["aggregations"]["inf_data"]["buckets"][$inf_types[$i]]["group_by_user"]["buckets"][$j]["top_sales_hits"]["hits"]["hits"][0]["_source"]["u_profile_photo"];
                        $data_array[$k]['fullname'] = $results["aggregations"]["inf_data"]["buckets"][$inf_types[$i]]["group_by_user"]["buckets"][$j]["top_sales_hits"]["hits"]["hits"][0]["_source"]["u_fullname"];
                        $data_array[$k]['source'] = '<a href="'.$results["aggregations"]["inf_data"]["buckets"][$inf_types[$i]]["group_by_user"]["buckets"][$j]["top_sales_hits"]["hits"]["hits"][0]["_source"]["u_source"].'" target="_blank">'.$source_icon.'</a>';
                        $data_array[$k]['country'] = $flag_image;
                        $data_array[$k]['followers'] = $this->gen_func_obj->format_number_data($results["aggregations"]["inf_data"]["buckets"][$inf_types[$i]]["group_by_user"]["buckets"][$j]["top_sales_hits"]["hits"]["hits"][0]["_source"]["u_followers"]);
                        $data_array[$k]['posts'] = $this->gen_func_obj->format_number_data($results["aggregations"]["inf_data"]["buckets"][$inf_types[$i]]["group_by_user"]["buckets"][$j]["doc_count"]);
                        $k = $k + 1;
                    }
                    
                    $output_data[$inf_types[$i]."_".$topic_id] = $data_array;
                }
                
                echo json_encode($output_data);
            }
            else if($request["section"] == 'gen_ca_pdf_report')
            {
                $server_url = 'https://'.$request->getHost().'/';
                $report_image_url = 'https://'.$request->getHost().'/ca-reports/images/';
                $report_image_path = public_path().'/ca-reports/images/';
        
                $html_opening_tags = '<html><head><title></title><body>';

                $html_closing_tags = '</body></html>';

                $mpdf = new \Mpdf\Mpdf([
                            'mode' => 'utf-8',
                            'orientation' => 'L', 
                            'format' => 'A4',
                            'margin_top' => 0,
                            'margin_right' => 0,
                            'margin_bottom' => 0,
                            'margin_left' => 0,
                            'default_font' => 'Avenir'
                        ]);
                        $mpdf->SetFont('Avenir');

                //First page
                $mpdf->AddPage();

                $content_html = '<div style="width: 100%; height: 100%; min-height: 100%;  background: #EEEFF1;">';
                
                $topic_subtopics_names = ''; $ca_dates = '';
                
                $t_ids = $this->get_ca_tids($request->ca_id); $topic_ids = explode(",", $t_ids);
                
                for($i=0; $i<count($topic_ids); $i++)
                {
                    $topic_subtopics_names .= $this->get_topic_name($topic_ids[$i]).'<br>';
                }
                
                if($greater_than_time == 'now-90d')
                    $ca_dates = 'Report dates: Last 90 days.';
                else
                    $ca_dates = 'Report dates: '.date("d/m/Y", strtotime($greater_than_time)).' - '.date("d/m/Y", strtotime($less_than_time));
                
                $content_html .= '<div style="float:left; width:55%; height:100%; min-height: 100%; background:#333333; padding:50px 0px 0px 30px;">'
                    . '<div style="font-size:40px; padding:100px 0px 0px 0px; color:#ffffff;">Competitor analysis report</div>'
                    . '<div style="font-size:30px; padding:30px 0px 0px 0px; color:#ffffff;">'.$this->get_ca_name($request->ca_id).'</div>'
                    . '<div style="padding: 30px 0px 0px 0px; font-size:20px; color:#ffffff;">'.$topic_subtopics_names.'</div>'
                    . '<div style="padding: 50px 0px 0px 0px; color:#ffffff;">'.$ca_dates.'</div>'
                    . '</div>';
                $content_html .= '<div style="float:left; width:35%; height:100%; min-height: 100%; text-align:right; background: #EEEFF1; padding:600px 0px 0px 0px;"><img src="'.$server_url.'images/logo/logo.png" width="300"></div>';
                
                $content_html .= '</div>';

                $pdf_html = mb_convert_encoding($content_html, 'UTF-8', 'UTF-8');
                $mpdf->WriteHTML($pdf_html, 2);
        
                //2nd page
                $content_html = '<div style="width: 100%; height: 100%; min-height: 100%;">';
                $content_html .= '<div style="width: 30%; height: 100%; float: left; color: #ffffff; background:#333333;">'
                    . '<div style="padding:30px 0px 0px 20px; font-size:40px;">Mentions &<br>Engagement</div>'
                    . '</div>';
                
                //mentions graph
                $params = array();
                
                $params["index"] = $this->search_index_name;
                $params["type"] = 'mytype';
                $params["size"] = 0;
                $params["docvalue_fields"] = array("field" => "p_created_time", "format" => "date_time");
                $params["body"]["query"]["bool"]["must"]["range"]["p_created_time"] = array('gte' => $greater_than_time, 'lte' => $less_than_time);
                $params["body"]["aggs"]["2"]["date_histogram"] = array("field" => "p_created_time", "interval" => "1d", "min_doc_count" => 0);
                
                //Generate dynamic aggs for selected topics
                for($i=0; $i<count($topic_ids); $i++)
                {
                    if(strpos($topic_ids[$i], '_') !== false) //means subtopic is present 
                    {
                        $_tid = explode("_", $topic_ids[$i]);
                        
                        $topic_query_string = $this->topic_obj->get_topic_elastic_query($_tid[0]).' AND '.$this->subtopic_obj->get_subtopic_elastic_query($_tid[1]);
                        $topic_name = $this->get_topic_name($_tid[0]).' - '.$this->get_subtopic_name($_tid[1]);
                        $topic_names[] = $topic_name;

                        $params["body"]["aggs"]["2"]["aggs"]["3"]["filters"]["filters"][$topic_name]["query_string"] = array("query" => $topic_query_string.$topic_query_string_filters);
                    }                    
                    else
                    {
                        $topic_query_string = $this->topic_obj->get_topic_elastic_query($topic_ids[$i]);
                        $topic_name = $this->get_topic_name($topic_ids[$i]);
                        $topic_names[] = $topic_name;

                        $params["body"]["aggs"]["2"]["aggs"]["3"]["filters"]["filters"][$topic_name]["query_string"] = array("query" => $topic_query_string.$topic_query_string_filters);
                    }
                }
                //echo '<pre>'; print_r($params);
                $es_data = $this->client->search($params);
                
                for($p=0; $p<count($es_data["aggregations"]["2"]["buckets"]); $p++)
                {
                    for($jj=0; $jj<count($topic_names); $jj++)
                    {
                        $response_array["data"][$p][$topic_names[$jj]]["date"] = date("Y-m-d", strtotime($es_data["aggregations"]["2"]["buckets"][$p]["key_as_string"]));
                        $response_array["data"][$p][$topic_names[$jj]]["count"] = $es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$topic_names[$jj]]["doc_count"];
                    }
                }
                //echo '<pre>'; print_r($response_array); exit;
                $output_array = array();
                $data_array = array();
                $topic_names_str = '';
                $dates_str = '';
                $set_dates_str = 'yes';
                
                for($j=0; $j<count($topic_names); $j++)
                {
                    //$data_str = '';
                    
                    for($k=0; $k<count($response_array["data"]); $k++)
                    {
                        if($set_dates_str == 'yes')
                            $dates_str .= '"'.$response_array["data"][$k][$topic_names[$j]]["date"].'",';
                    }
                    
                    $set_dates_str = 'no'; //We have to get dates once.
                    
                    $topic_names_str .= $topic_names[$j].',';
                    
                    $data_array[$topic_names[$j]] = substr($data_str, 0, -1);
                }
                
                $output_array["topic_names"] = substr($topic_names_str, 0, -1);
                $output_array["dates"] = substr($dates_str, 0, -1);
                                
                $dataset_str = '';
                $tnames = explode(",", $output_array["topic_names"]);
                $colors_array = array("#0088a8", "#fac158", "#6c577e", "#a7cc5f", "#cb4d29", "#ff9e51", "red", "green", "orange", "blue", "pink", "brown", "lightblue", "lightgreen");
                
                for($i=0; $i<count($tnames); $i++)
                {
                    $data_plots_str = '';
                    
                    for($j=0; $j<count($response_array["data"]); $j++)
                    {
                        $data_plots_str .= $response_array["data"][$j][$tnames[$i]]["count"].',';
                        //echo $tnames[$i].'<br>';
                    }
                    
                    $dataset_str .= '{
                                label: "'.$tnames[$i].'",
                                backgroundColor: "'.$colors_array[$i].'",
                                borderColor: "'.$colors_array[$i].'",
                                data: ['.substr($data_plots_str, 0, -1).'],
                                fill: false,
                            },';
                }
                
                $qc_obj = new QuickChartController(array("width"=>750));
                $config = '
                {
                    type: "line",
                    data: {
                        labels: ['.$output_array["dates"].'],
                        datasets: [
                            '.$dataset_str.'
                        ],
                    },
                    options: {
                        title: {
                            display: false,
                            text: "Mentions trend",
                        },
                        legend: {
                            display: true,
                            position: \'bottom\',
                            align: \'middle\',
                            labels: {
                                boxWidth:10,
                                fontSize: 11
                            }
                        }
                    },
                }';
                
                $qc_obj->setConfig($config);
                  
                $mentions_graph = 'mentions_'.uniqid().'.png';
                $qc_obj->toFile($report_image_path.$mentions_graph);
                //mentions graph end
                
                //engagement graph
                $params = array();
                
                $params["index"] = $this->search_index_name;
                $params["type"] = 'mytype';
                $params["size"] = 0;
                $params["docvalue_fields"] = array("field" => "p_created_time", "format" => "date_time");
                $params["body"]["query"]["bool"]["must"]["range"]["p_created_time"] = array('gte' => $greater_than_time, 'lte' => $less_than_time);
                $params["body"]["aggs"]["2"]["date_histogram"] = array("field" => "p_created_time", "interval" => "1d", "min_doc_count" => 0);
                
                //Generate dynamic aggs for selected topics
                for($i=0; $i<count($topic_ids); $i++)
                {
                    if(strpos($topic_ids[$i], '_') !== false) //means subtopic is present 
                    {
                        $_tid = explode("_", $topic_ids[$i]);
                        
                        $topic_query_string = $this->topic_obj->get_topic_elastic_query($_tid[0]).' AND '.$this->subtopic_obj->get_subtopic_elastic_query($_tid[1]);
                        $topic_name = $this->get_topic_name($_tid[0]).' - '.$this->get_subtopic_name($_tid[1]);
                        $topic_names[] = $topic_name;

                        $params["body"]["aggs"]["2"]["aggs"]["3"]["filters"]["filters"][$topic_name]["query_string"] = array("query" => $topic_query_string.$topic_query_string_filters);
                    }
                    else
                    {
                        $topic_query_string = $this->topic_obj->get_topic_elastic_query($topic_ids[$i]);
                        $topic_name = $this->get_topic_name($topic_ids[$i]);
                        $topic_names[] = $topic_name;

                        $params["body"]["aggs"]["2"]["aggs"]["3"]["filters"]["filters"][$topic_name]["query_string"] = array("query" => $topic_query_string.$topic_query_string_filters);
                    }
                }
                
                $params["body"]["aggs"]["2"]["aggs"]["3"]["aggs"]["total_shares"]["sum"] = array('field' => 'p_shares');
                $params["body"]["aggs"]["2"]["aggs"]["3"]["aggs"]["total_likes"]["sum"] = array('field' => 'p_likes');
                $params["body"]["aggs"]["2"]["aggs"]["3"]["aggs"]["total_comments"]["sum"] = array('field' => 'p_comments');
                
                
                $es_data = $this->client->search($params);
                //echo '<pre>'; print_r($es_data); exit;
                for($p=0; $p<count($es_data["aggregations"]["2"]["buckets"]); $p++)
                {
                    for($jj=0; $jj<count($topic_names); $jj++)
                    {
                        $response_array["data"][$p][$topic_names[$jj]]["date"] = date("Y-m-d", strtotime($es_data["aggregations"]["2"]["buckets"][$p]["key_as_string"]));
                        
                        //get likes, shares and comments
                        $tot_eng = $es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$topic_names[$jj]]["total_comments"]["value"] + $es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$topic_names[$jj]]["total_shares"]["value"] + $es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$topic_names[$jj]]["total_likes"]["value"];
                        $response_array["data"][$p][$topic_names[$jj]]["count"] = $tot_eng;
                    }
                }
                
                //$output_array = array();
                $data_array = array();
                $topic_names_str = '';
                $dataset_str = '';
                
                for($i=0; $i<count($tnames); $i++)
                {
                    $data_plots_str = '';
                    
                    for($j=0; $j<count($response_array["data"]); $j++)
                    {
                        $data_plots_str .= $response_array["data"][$j][$tnames[$i]]["count"].',';
                        //echo $tnames[$i].'<br>';
                    }
                    
                    $dataset_str .= '{
                                label: "'.$tnames[$i].'",
                                backgroundColor: "'.$colors_array[$i].'",
                                borderColor: "'.$colors_array[$i].'",
                                data: ['.substr($data_plots_str, 0, -1).'],
                                fill: false,
                            },';
                }
                //echo $dataset_str; exit;
                $qc_obj = new QuickChartController(array("width"=>750));
                $config = '
                {
                    type: "line",
                    data: {
                        labels: ['.$output_array["dates"].'],
                        datasets: [
                            '.$dataset_str.'
                        ],
                    },
                    options: {
                        title: {
                            display: false,
                            text: "Engagement trend",
                        },
                        legend: {
                            display: true,
                            position: \'bottom\',
                            align: \'middle\',
                            labels: {
                                boxWidth:10,
                                fontSize: 11
                            }
                        }
                    },
                }';
                
                $qc_obj->setConfig($config);
                  
                $engagement_graph = 'engagement_'.uniqid().'.png';
                $qc_obj->toFile($report_image_path.$engagement_graph);
                
                //engagement graph end
                
                $content_html .= '<div style="width:70%; height: 100%; float: left;">'
                    . '<div style="padding:40px 0px 20px 20px; font-size: 20px;">Mentions trend</div>'
                    . '<div><img src="'.$report_image_url.$mentions_graph.'" width="750"></div>'
                    . '<div style="padding:40px 0px 20px 20px; font-size: 20px;">Engagement trend</div>'
                    . '<div><img src="'.$report_image_url.$engagement_graph.'" width="750"></div>'
                    . '</div>';
                
                $content_html .= '</div>'; //end main page div
                
                $mpdf->AddPage();
                $pdf_html = mb_convert_encoding($content_html, 'UTF-8', 'UTF-8');
                $mpdf->WriteHTML($pdf_html, 2);
                
                //key counts and source channels 3rd page
                $content_html = '<div style="width: 100%; height: 100%; min-height: 100%;">';
                $content_html .= '<div style="width: 30%; height: 100%; float: left; color: #ffffff; background:#333333;">'
                    . '<div style="padding:30px 0px 0px 20px; font-size:40px;">Counts &<br>Channels</div>'
                    . '</div>';
                
                //key counts data calculation
                $twitter_count = 0; $reddit_count = 0; $youtube_count = 0; $vimeo_count = 0; $tumblr_count = 0; $pinterest_count = 0; $web_count = 0; $facebook_count = 0; $tripadvisor_count = 0; $fakenews_count = 0; $news_count = 0; $instagram_count = 0; $blogs_count = 0; $googlemaps_count = 0; $videos_count = 0; $news_count_data = 0;
                
                $sources_array = array("Twitter", "Facebook", "Pinterest", "Youtube", "Vimeo", "Instagram", "Blogs", "FakeNews", "News", "Reddit", "Tumblr", "Web", "Linkedin");
                
                $output_html = '<div class="col-12">';
                $output_html .= '<table width="100%" cellpadding="0" cellspacing="0" style="font-size:12px;">';
                $output_html .= '<tr style="">';
                $output_html .= '<td width="20%" style="text-align:left; padding-bottom: 10px; border-bottom: 1px solid #f0f0f0; font-weight:bold;">DASHBOARDS</td>'
                    . '<td width="16%" style="text-align:center; padding-bottom: 10px; border-bottom: 1px solid #f0f0f0; font-weight:bold;"><i class="bx bx-bullseye" style="font-size:1.5rem;"></i><br><span style="text-transform: uppercase; font-size: 12px;">Mentions</span></td>'
                    . '<td width="16%" style="text-align:center; padding-bottom: 10px; border-bottom: 1px solid #f0f0f0; font-weight:bold;"><i class="bx bx-group" style="font-size:1.5rem;"></i><br><span style="text-transform: uppercase; font-size: 12px;">Social media results</td>'
                    . '<td width="16%" style="text-align:center; padding-bottom: 10px; border-bottom: 1px solid #f0f0f0; font-weight:bold;"><i class="bx bx-log-in-circle" style="font-size:1.5rem;"></i><br><span style="text-transform: uppercase; font-size: 12px;">Estimated social reach</td>'
                    . '<td width="16%" style="text-align:center; padding-bottom: 10px; border-bottom: 1px solid #f0f0f0; font-weight:bold;"><i class="bx bx-hive" style="font-size:1.5rem;"></i><br><span style="text-transform: uppercase; font-size: 12px;">Beyond social</td>'
                    . '<td width="16%" style="text-align:center; padding-bottom: 10px;border-bottom: 1px solid #f0f0f0; font-weight:bold;"><i class="bx bx-shape-circle" style="font-size:1.5rem;"></i><br><span style="text-transform: uppercase; font-size: 12px;">Engagement</td>';
                $output_html .= '</tr>';
                
                $j = 0; $initial_mentions = 0;
                
                for($i=0; $i<count($topic_ids); $i++)
                {
                    if(strpos($topic_ids[$i], '_') !== false) //means subtopic is present 
                    {
                        $_tid = explode("_", $topic_ids[$i]);
                        
                        $topic_query_string = $this->topic_obj->get_topic_elastic_query($_tid[0]).' AND '.$this->subtopic_obj->get_subtopic_elastic_query($_tid[1]);
                        $topic_name = $this->get_topic_name($_tid[0]).' - '.$this->get_subtopic_name($_tid[1]);
                        $topic_names[] = $topic_name;
                    }
                    else
                    {
                        $topic_query_string = $this->topic_obj->get_topic_elastic_query($topic_ids[$i]);
                        $topic_name = $this->get_topic_name($topic_ids[$i]);
                        $topic_names[] = $topic_name;
                    }

                    $output_html .= '<tr style="">';
                    
                    $output_html .= '<td width="20%" style="text-align:left; padding: 10px 0px 10px 0px; border-bottom: 1px solid #f0f0f0;">'.$topic_name.'</td>';
                    
                    //total mentions
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string.$topic_query_string_filters ]],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                    ]
                                ]
                            ]
                        ]
                    ];
                    
                    $results = $this->client->count($params);
                    
                    if($j == 0)
                    {
                        $initial_mentions = $results["count"];
                        $output_html .= '<td width="16%" style="text-align:center; padding: 10px 0px 10px 0px; border-bottom: 1px solid #f0f0f0;">'.number_format($results["count"]).'</td>';
                    }
                    else
                    {
                        if($initial_mentions > $results["count"]) //decrease
                        {
                            $diff = $initial_mentions - $results["count"];
                            $per_diff = ($diff/$initial_mentions)*100;
                            
                            $output_html .= '<td width="16%" style="text-align:center; padding: 10px 0px 10px 0px; border-bottom: 1px solid #f0f0f0;">'.number_format($results["count"]).' <i class="bx font-medium-1 text-danger bx-caret-down"></i> <small class="text-muted" style="color:red;">-'. number_format($per_diff).'%</small></td>';
                        }
                        else //increase
                        {
                            $diff = $results["count"] - $initial_mentions;
                            $per_diff = ($diff/$initial_mentions)*100;
                            
                            $output_html .= '<td width="16%" style="text-align:center; padding: 10px 0px 10px 0px; border-bottom: 1px solid #f0f0f0;">'.number_format($results["count"]).' <i class="bx font-medium-1 text-success bx-caret-up"></i> <small class="text-muted" style="color:green;">+'. number_format($per_diff).'%</small></td>';
                        }
                        
                    }//END: Total mentions
                    
                    //total social mentions
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string.$topic_query_string_filters.' AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")' ]],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                    ]
                                ]
                            ]
                        ]
                    ];
                    
                    $results = $this->client->count($params);
                                        
                    if($j == 0)
                    {
                        $initial_social_mentions = $results["count"];
                        $output_html .= '<td width="16%%" style="text-align:center; padding: 10px 0px 10px 0px; border-bottom: 1px solid #f0f0f0;">'.number_format($results["count"]).'</td>';
                    }
                    else
                    {
                        if($initial_social_mentions > $results["count"]) //decrease
                        {
                            $diff = $initial_social_mentions - $results["count"];
                            $per_diff = ($diff/$initial_social_mentions)*100;
                            
                            $output_html .= '<td width="16%" style="text-align:center; padding: 10px 0px 10px 0px; border-bottom: 1px solid #f0f0f0;">'.number_format($results["count"]).' <i class="bx font-medium-1 text-danger bx-caret-down"></i> <small class="text-muted" style="color:red;">-'. number_format($per_diff).'%</small></td>';
                        }
                        else //increase
                        {
                            $diff = $results["count"] - $initial_social_mentions;
                            $per_diff = ($diff/$initial_social_mentions)*100;
                            
                            $output_html .= '<td width="16%" style="text-align:center; padding: 10px 0px 10px 0px; border-bottom: 1px solid #f0f0f0;">'.number_format($results["count"]).' <i class="bx font-medium-1 text-success bx-caret-up"></i> <small class="text-muted" style="color:green;">+'. number_format($per_diff).'%</small></td>';
                        }
                        
                    }
                    //END: Total social mentions
                    
                    //Estimated reach 50% normal user followers + 5% influencers + total engagement
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'size' => '0',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string.$topic_query_string_filters.' AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")' ]],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]],
                                        ['range' => ['u_followers' => ['gt' => 0, 'lt' => 1000]]]
                                    ]
                                ]
                            ],
                            'aggs' => [
                                'total_normal_users_followers' => ['sum' => ['field' => 'u_followers']]
                            ]
                        ]
                    ];
                    $results = $this->client->search($params);

                    $total_normal_users_followers = $results["aggregations"]["total_normal_users_followers"]["value"];
                    $normal_user_followers = ceil($total_normal_users_followers * 0.50); //50%

                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'size' => '0',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string.$topic_query_string_filters.' AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")' ]],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]],
                                        ['range' => ['u_followers' => ['gt' => 1000]]]
                                    ]
                                ]
                            ],
                            'aggs' => [
                                'total_influencer_followers' => ['sum' => ['field' => 'u_followers']]
                            ]
                        ]
                    ];

                    $results = $this->client->search($params);

                    $total_influencer_followers = $results["aggregations"]["total_influencer_followers"]["value"];
                    $influencer_user_followers = ceil($total_influencer_followers * 0.05); //5% of followers

                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'size' => '0',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string.$topic_query_string_filters.' AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")' ]],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                    ]
                                ]
                            ],
                            'aggs' => [
                                'total_shares' => ['sum' => ['field' => 'p_shares']],
                                'total_comments' => ['sum' => ['field' => 'p_comments']],
                                'total_likes' => ['sum' => ['field' => 'p_likes']],
                                'total_views' => ['sum' => ['field' => 'p_engagement']]
                            ]
                        ]
                    ];

                    $results = $this->client->search($params);
                    $tot_eng = $results["aggregations"]["total_shares"]["value"] + $results["aggregations"]["total_comments"]["value"] + $results["aggregations"]["total_likes"]["value"] + $results["aggregations"]["total_views"]["value"];
                    $estimated_reach = $normal_user_followers + $influencer_user_followers + $tot_eng;
                    
                    if($j == 0)
                    {
                        $initial_estimated_reach = $estimated_reach;
                        $output_html .= '<td width="16%%" style="text-align:center; padding: 10px 0px 10px 0px; border-bottom: 1px solid #f0f0f0;">'.number_format($estimated_reach).'</td>';
                    }
                    else
                    {
                        if($initial_estimated_reach > $estimated_reach) //decrease
                        {
                            $diff = $initial_estimated_reach - $estimated_reach;
                            $per_diff = ($diff/$initial_estimated_reach)*100;
                            
                            $output_html .= '<td width="16%" style="text-align:center; padding: 10px 0px 10px 0px; border-bottom: 1px solid #f0f0f0;">'.number_format($estimated_reach).' <i class="bx font-medium-1 text-danger bx-caret-down"></i> <small class="text-muted" style="color:red;">-'. number_format($per_diff).'%</small></td>';
                        }
                        else //increase
                        {
                            $diff = $estimated_reach - $initial_estimated_reach;
                            $per_diff = ($diff/$initial_estimated_reach)*100;
                            
                            $output_html .= '<td width="16%" style="text-align:center; padding: 10px 0px 10px 0px; border-bottom: 1px solid #f0f0f0;">'.number_format($estimated_reach).' <i class="bx font-medium-1 text-success bx-caret-up"></i> <small class="text-muted" style="color:green;">+'. number_format($per_diff).'%</small></td>';
                        }
                        
                    }                    
                    //END: Estimated reach
                    
                    //Beyond social
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string.$topic_query_string_filters.' AND source:("khaleej_times" OR "Omanobserver" OR "Time of oman" OR "Blogs" OR "FakeNews" OR "News")' ]],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                    ]
                                ]
                            ]
                        ]
                    ];
                    
                    $results = $this->client->count($params);
                    
                    if($j == 0)
                    {
                        $initial_beyond_social_mentions = $results["count"];
                        $output_html .= '<td width="16%%" style="text-align:center; padding: 10px 0px 10px 0px; border-bottom: 1px solid #f0f0f0;">'.number_format($results["count"]).'</td>';
                    }
                    else
                    {
                        if($initial_beyond_social_mentions > $results["count"]) //decrease
                        {
                            $diff = $initial_beyond_social_mentions - $results["count"];
                            if($initial_beyond_social_mentions > 0)
                                $per_diff = ($diff/$initial_beyond_social_mentions)*100;
                            else
                                $per_diff = ($diff/1)*100;
                            
                            $output_html .= '<td width="16%" style="text-align:center; padding: 10px 0px 10px 0px; border-bottom: 1px solid #f0f0f0;">'.number_format($results["count"]).' <i class="bx font-medium-1 text-danger bx-caret-down"></i> <small class="text-muted" style="color:red;">-'. number_format($per_diff).'%</small></td>';
                        }
                        else //increase
                        {
                            $diff = $results["count"] - $initial_beyond_social_mentions;
                            if($initial_beyond_social_mentions > 0)
                                $per_diff = ($diff/$initial_beyond_social_mentions)*100;
                            else
                                $per_diff = ($diff/1)*100;
                            
                            $output_html .= '<td width="16%" style="text-align:center; padding: 10px 0px 10px 0px; border-bottom: 1px solid #f0f0f0;">'.number_format($results["count"]).' <i class="bx font-medium-1 text-success bx-caret-up"></i> <small class="text-muted" style="color:green;">+'. number_format($per_diff).'%</small></td>';
                        }
                    }
                    //END: Beyond social
                    
                    //Engagement
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'size' => '0',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string.$topic_query_string_filters]],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                    ]
                                ]
                            ],
                            'aggs' => [
                                'total_shares' => ['sum' => ['field' => 'p_shares']],
                                'total_comments' => ['sum' => ['field' => 'p_comments']],
                                'total_likes' => ['sum' => ['field' => 'p_likes']]
                            ]
                        ]
                    ];

                    $results = $this->client->search($params);
                    $tot_eng = $results["aggregations"]["total_shares"]["value"] + $results["aggregations"]["total_comments"]["value"] + $results["aggregations"]["total_likes"]["value"];
                    //$output_html .= '<td width="16%%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($tot_eng).'</td>';
                    
                    if($j == 0)
                    {
                        $initial_total_eng = $tot_eng;
                        $output_html .= '<td width="16%%" style="text-align:center; padding: 10px 0px 10px 0px; border-bottom: 1px solid #f0f0f0;">'.number_format($tot_eng).'</td>';
                    }
                    else
                    {
                        if($initial_total_eng > $tot_eng) //decrease
                        {
                            $diff = $initial_total_eng - $tot_eng;
                            $per_diff = ($diff/$initial_total_eng)*100;
                            
                            $output_html .= '<td width="16%" style="text-align:center; padding: 10px 0px 10px 0px; border-bottom: 1px solid #f0f0f0;">'.number_format($tot_eng).' <i class="bx font-medium-1 text-danger bx-caret-down"></i> <small class="text-muted" style="color:red;">-'. number_format($per_diff).'%</small></td>';
                        }
                        else //increase
                        {
                            $diff = $tot_eng - $initial_total_eng;
                            $per_diff = ($diff/$initial_total_eng)*100;
                            
                            $output_html .= '<td width="16%" style="text-align:center; padding: 10px 0px 10px 0px; border-bottom: 1px solid #f0f0f0;">'.number_format($tot_eng).' <i class="bx font-medium-1 text-success bx-caret-up"></i> <small class="text-muted" style="color:green;">+'. number_format($per_diff).'%</small></td>';
                        }
                    }
                    //END: Engagement
                    
                    $output_html .= '</tr>';
                    $j = $j+1;
                }
                
                $output_html .= '</table>';
                $output_html .= '</div>';
                $key_counts_html = $output_html;
                //key counts data calculation end
                
                //sources count start
                $twitter_count = 0; $reddit_count = 0; $youtube_count = 0; $vimeo_count = 0; $tumblr_count = 0; $pinterest_count = 0; $web_count = 0; $facebook_count = 0; $tripadvisor_count = 0; $fakenews_count = 0; $news_count = 0; $instagram_count = 0; $blogs_count = 0; $googlemaps_count = 0; $videos_count = 0; $news_count_data = 0;
                
                $sources_array = array("Twitter", "Facebook", "Pinterest", "Youtube", "Vimeo", "Instagram", "Blogs", "FakeNews", "News", "Reddit", "Tumblr", "Web", "Linkedin");
                //Blogs, News & Web are combined as Web
                
                $output_html = '<div class="col-12">';
                $output_html .= '<table width="100%" cellpadding="0" cellspacing="0" style="font-size:12px;">';
                $output_html .= '<tr style="">';
                $output_html .= '<td width="20%" style="text-align:left; padding-bottom: 10px; font-weight:bold; border-bottom: 1px solid #f0f0f0;">DASHBOARDS</td>'
                    . '<td width="7.2%" style="text-align:center; padding-bottom: 10px; font-weight:bold; border-bottom: 1px solid #f0f0f0;"><span style="text-transform: uppercase; font-size: 12px;">Twitter</span></td>'
                    . '<td width="7.2%" style="text-align:center; padding-bottom: 10px; font-weight:bold; border-bottom: 1px solid #f0f0f0;"><span style="text-transform: uppercase; font-size: 12px;">Facebook</td>'
                    . '<td width="7.2%" style="text-align:center; padding-bottom: 10px; font-weight:bold; border-bottom: 1px solid #f0f0f0;"><span style="text-transform: uppercase; font-size: 12px;">Videos</td>'
                    . '<td width="7.2%" style="text-align:center; padding-bottom: 10px; font-weight:bold; border-bottom: 1px solid #f0f0f0;"><span style="text-transform: uppercase; font-size: 12px;">Reddit</td>'
                    . '<td width="7.2%" style="text-align:center; padding-bottom: 10px; font-weight:bold; border-bottom: 1px solid #f0f0f0;"><span style="text-transform: uppercase; font-size: 12px;">Tumblr</td>'
                    . '<td width="7.2%" style="text-align:center; padding-bottom: 10px; font-weight:bold; border-bottom: 1px solid #f0f0f0;"><span style="text-transform: uppercase; font-size: 12px;">Pinterest</td>'
                    . '<td width="7.2%" style="text-align:center; padding-bottom: 10px; font-weight:bold; border-bottom: 1px solid #f0f0f0;"><span style="text-transform: uppercase; font-size: 12px;">Web</td>'
                    . '<td width="7.2%" style="text-align:center; padding-bottom: 10px; font-weight:bold; border-bottom: 1px solid #f0f0f0;"><span style="text-transform: uppercase; font-size: 12px;">Instagram</td>'
                    . '<td width="7.2%" style="text-align:center; padding-bottom: 10px; font-weight:bold; border-bottom: 1px solid #f0f0f0;"><span style="text-transform: uppercase; font-size: 12px;">Linkedin</td>';
                $output_html .= '</tr>';
                
                for($i=0; $i<count($topic_ids); $i++)
                {
                    if(strpos($topic_ids[$i], '_') !== false) //means subtopic is present 
                    {
                        $_tid = explode("_", $topic_ids[$i]);
                        
                        $topic_query_string = $this->topic_obj->get_topic_elastic_query($_tid[0]).' AND '.$this->subtopic_obj->get_subtopic_elastic_query($_tid[1]);
                        $topic_name = $this->get_topic_name($_tid[0]).' - '.$this->get_subtopic_name($_tid[1]);
                        $topic_names[] = $topic_name;
                    }
                    else
                    {
                        $topic_query_string = $this->topic_obj->get_topic_elastic_query($topic_ids[$i]);
                        $topic_name = $this->get_topic_name($topic_ids[$i]);
                        $topic_names[] = $topic_name;
                    }                    
                    
                    for($a=0; $a<count($sources_array); $a++)
                    {
                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            ['query_string' => ['query' => $topic_query_string.$topic_query_string_filters . ' AND source:("'.$sources_array[$a].'")']],
                                            ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                        ]
                                    ]
                                ]
                            ]
                        ];

                        $results = $this->client->count($params);

                        if($sources_array[$a] == 'Twitter')
                            $twitter_count = $results["count"];
                        else if($sources_array[$a] == 'Youtube')
                            $youtube_count = $results["count"];
                        else if($sources_array[$a] == 'Vimeo')
                            $vimeo_count = $results["count"];
                        else if($sources_array[$a] == 'FakeNews')
                            $fakenews_count = $results["count"];
                        else if($sources_array[$a] == 'News')
                            $news_count = $results["count"];
                        else if($sources_array[$a] == 'Pinterest')
                            $pinterest_count = $results["count"];
                        else if($sources_array[$a] == 'Instagram')
                            $instagram_count = $results["count"];
                        else if($sources_array[$a] == 'Blogs')
                            $blogs_count = $results["count"];
                        else if($sources_array[$a] == 'Reddit')
                            $reddit_count = $results["count"];
                        else if($sources_array[$a] == 'Tumblr')
                            $tumblr_count = $results["count"];
                        else if($sources_array[$a] == 'Facebook')
                            $facebook_count = $results["count"];
                        else if($sources_array[$a] == 'Web')
                            $web_count = $results["count"];
                        else if($sources_array[$a] == 'Linkedin')
                            $linkedin_count = $results["count"];
                    }
                    
                    $videos_count = $youtube_count + $vimeo_count;
                    $news_count_data = $news_count + $fakenews_count;
                    
                    $output_html .= '<tr style="">'
                        . '<td width="20%" style="text-align:left; padding: 10px 0px 10px 0px; border-bottom: 1px solid #f0f0f0;">'.$topic_name.'</td>'
                        . '<td width="8.9%" style="text-align:center; padding: 10px 0px 10px 0px; border-bottom: 1px solid #f0f0f0;">'.number_format($twitter_count).'</td>'
                        . '<td width="8.9%" style="text-align:center; padding: 10px 0px 10px 0px; border-bottom: 1px solid #f0f0f0;">'.number_format($facebook_count).'</td>'
                        . '<td width="8.9%" style="text-align:center; padding: 10px 0px 10px 0px; border-bottom: 1px solid #f0f0f0;">'.number_format($videos_count).'</td>'
                        //. '<td width="7.2%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($news_count_data).'</td>'
                        . '<td width="8.9%" style="text-align:center; padding: 10px 0px 10px 0px; border-bottom: 1px solid #f0f0f0;">'.number_format($reddit_count).'</td>'
                        //. '<td width="7.2%" style="text-align:center; padding: 10px 0px 10px 0px;">'.number_format($blogs_count).'</td>'
                        . '<td width="8.9%" style="text-align:center; padding: 10px 0px 10px 0px; border-bottom: 1px solid #f0f0f0;">'.number_format($tumblr_count).'</td>'
                        . '<td width="8.9%" style="text-align:center; padding: 10px 0px 10px 0px; border-bottom: 1px solid #f0f0f0;">'.number_format($pinterest_count).'</td>'
                        . '<td width="8.9%" style="text-align:center; padding: 10px 0px 10px 0px; border-bottom: 1px solid #f0f0f0;">'.number_format($web_count+$news_count+$blogs_count).'</td>'
                        . '<td width="8.9%" style="text-align:center; padding: 10px 0px 10px 0px; border-bottom: 1px solid #f0f0f0;">'.number_format($instagram_count).'</td>'
                        . '<td width="8.9%" style="text-align:center; padding: 10px 0px 10px 0px; border-bottom: 1px solid #f0f0f0;">'.number_format($linkedin_count).'</td>';
                    $output_html .= '</tr>';
                }
                
                $output_html .= '</table>';
                $output_html .= '</div>';
                
                $sources_count_html = $output_html;
                //sources count end
                
                $content_html .= '<div style="width:70%; height: 100%; float: left;">'
                    . '<div style="padding:40px 0px 20px 20px; font-size: 20px;">Key counts comparison</div>'
                    . '<div style="padding:0px 15px 0px 15px;">'.$key_counts_html.'</div>'
                    . '<div style="padding:40px 0px 20px 20px; font-size: 20px;">Source channels</div>'
                    . '<div style="padding:0px 15px 0px 15px;">'.$sources_count_html.'</div>'
                    . '</div>';
                
                $content_html .= '</div>'; //end main page div
                
                $mpdf->AddPage();
                $pdf_html = mb_convert_encoding($content_html, 'UTF-8', 'UTF-8');
                $mpdf->WriteHTML($pdf_html, 2);
                //end key counts & source channels
                
                //4th page sentiment and emotions
                $content_html = '<div style="width: 100%; height: 100%; min-height: 100%;">';
                $content_html .= '<div style="width: 30%; height: 100%; float: left; color: #ffffff; background:#333333;">'
                    . '<div style="padding:30px 0px 0px 20px; font-size:40px;">Sentiments &<br>Emotions</div>'
                    . '</div>';
                
                $content_html .= '<div style="width:70%; height: 100%; float: left;"><div style="padding:25px 20px 0px 20px;">';
                
                $tids = explode(",", $this->get_ca_tids($request->ca_id));
                for($i=0; $i<count($tids); $i++)
                {
                    $senti_data = explode("|", trim($this->get_topic_sentiments_data($tids[$i])));
                    
                    $qc = new QuickChartController(array("width"=>150, "height"=>150));

                    $config = '{
                        type: "doughnut",
                        data: {
                            datasets: [
                                {
                                    data: ['.$senti_data[0].', '.$senti_data[1].', '.$senti_data[2].'],
                                    backgroundColor: ["#3bdb8b","#fe5a5c", "#5b8eee"],
                                    label: "Sentiment",
                                },
                            ],
                            labels: ["Positive", "Negative", "Neutral"],
                        },
                        options: {
                            title: {
                                display: true,
                                text: "'.$this->get_topic_name($tids[$i]).'",
                            },
                            legend: {
                                display: false,
                            }
                        },
                    }';

                    // Chart config can be set as a string or as a nested array
                    $qc->setConfig($config);
                    $senti_graph = 'senti_'.uniqid().'.png';
                    $qc->toFile($report_image_path.$senti_graph);
                    
                    $content_html .= '<div style="float:left; width:150px; padding:0px 25px 25px 0px;"><img src="'.$report_image_url.$senti_graph.'" width="150"></div>';
                }
                
                $content_html .= '</div>'; //end sentiment charts container div
                
                $anger_count = 0; $fear_count = 0; $happy_count = 0; $sadness_count = 0; $surprise_count = 0;
                $topic_names = array();
                $response_array = array();
                
                $topic_ids = explode(",", $this->get_ca_tids($request->ca_id));
                
                $emos_array = array("anger", "fear", "happy", "sadness", "surprise");
                
                for($a=0; $a<count($emos_array); $a++)
                {
                    for($i=0; $i<count($topic_ids); $i++)
                    {
                        if(strpos($topic_ids[$i], '_') !== false) //means subtopic is present 
                        {
                            $_tid = explode("_", $topic_ids[$i]);

                            $topic_query_string = $this->topic_obj->get_topic_elastic_query($_tid[0]).' AND '.$this->subtopic_obj->get_subtopic_elastic_query($_tid[1]);
                            $topic_name = $this->get_topic_name($_tid[0]).' - '.$this->get_subtopic_name($_tid[1]);
                        }
                        else
                        {
                            $topic_query_string = $this->topic_obj->get_topic_elastic_query($topic_ids[$i]);
                            $topic_name = $this->get_topic_name($topic_ids[$i]);
                        }
                        
                        if(!in_array($topic_name, $topic_names))
                            $topic_names[] = $topic_name;
                        
                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            ['query_string' => ['query' => $topic_query_string.$topic_query_string_filters . ' AND emotion_detector:("'.$emos_array[$a].'")']],
                                            ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                        ]
                                    ]
                                ]
                            ]
                        ];

                        $results = $this->client->count($params);
                        
                        if($i==0)
                            $response_array["emo_data"][$a]["name"] = $emos_array[$a];
                        
                        $response_array["emo_data"][$a]["data"][] = $results["count"];
                    }
                }
                
                $dataset_str = '';
                $labels_str = '';
                $emo_color_code = '';
                
                $response_array["topic_names"] = $topic_names;
                
                for($i=0; $i<count($response_array["topic_names"]); $i++)
                {
                    $labels_str .= '"'.$response_array["topic_names"][$i].'",';
                }
                
                for($j=0; $j<count($response_array["emo_data"]); $j++)
                {
                    $emo_data_str = '';

                    for($k=0; $k<count($response_array["emo_data"][$j]["data"]); $k++)
                    {
                        $emo_data_str .= $response_array["emo_data"][$j]["data"][$k].',';
                    }

                    if($response_array["emo_data"][$j]["name"] == 'anger')
                        $emo_color_code = '#df7970';
                    else if($response_array["emo_data"][$j]["name"] == 'fear')
                        $emo_color_code = '#FFA65B';
                    else if($response_array["emo_data"][$j]["name"] == 'happy')
                        $emo_color_code = '#51cda0';
                    else if($response_array["emo_data"][$j]["name"] == 'sadness')
                        $emo_color_code = '#BEB145';
                    else if($response_array["emo_data"][$j]["name"] == 'surprise')
                        $emo_color_code = '#BA56FF';

                    $dataset_str .= '{
                            label: "'.ucfirst($response_array["emo_data"][$j]["name"]).'",
                            maxBarThickness: 50,
                            backgroundColor: "'.$emo_color_code.'",
                            data: ['.substr($emo_data_str, 0, -1).'],
                        },';
                }
                //echo $labels_str; echo $dataset_str; exit;
                $config = '{
                type: "bar",
                data: {
                    labels: ['.substr($labels_str, 0, -1).'],
                        datasets: [
                            '.$dataset_str.'
                        ],
                    },
                    options: {
                        title: {
                            display: true,
                            text: "Emotions",
                        },
                        scales: {
                            xAxes: [
                                {
                                    stacked: true,
                                },
                            ],
                            yAxes: [
                                {
                                    stacked: true,
                                },
                            ],
                        },
                        plugins: {
                            datalabels: {
                            }
                        },
                    },
                }';
                
                $qc_obj = new QuickChartController(array("width"=>750));
                $qc_obj->setConfig($config);
                  
                $emo_graph = 'engagement_'.uniqid().'.png';
                $qc_obj->toFile($report_image_path.$emo_graph);
                
                $content_html .= '<div style="padding: 30px 20px 0px 20px;">'
                    . '<div><img src="'.$report_image_url.$emo_graph.'" width="750"></div>'
                    . '</div>';
                
                $content_html .= '</div>'; //end right side div
                
                $content_html .= '</div>'; //end main page div
                
                $mpdf->AddPage();
                $pdf_html = mb_convert_encoding($content_html, 'UTF-8', 'UTF-8');
                $mpdf->WriteHTML($pdf_html, 2);
                
                //end sentiment and emotions
                
                $fname = str_replace(' ', '', $this->get_ca_name($request->ca_id)).'-'.strtotime($this->get_ca_created_date($request->ca_id)).'.pdf';
                
                $mpdf->Output(public_path().'/ca-reports/'.$fname, 'F');
                echo encrypt($fname);
            }
        }
    }
    
    public function get_ca_report(Request $request)
    {
        if(isset($request->filename) && !empty($request->filename))
        {
            if(file_exists(public_path().'/ca-reports/'.decrypt($request->filename)) && is_file(public_path().'/ca-reports/'.decrypt($request->filename)))
            {
                $file_url = 'https://'.$request->getHost().'/ca-reports/'.decrypt($request->filename);
                header('Content-Type: application/pdf');
                header("Content-Transfer-Encoding: Binary");
                header("Content-disposition: attachment; filename=".decrypt($request->filename));
                readfile($file_url);
            }
        }
    }
}
?>

