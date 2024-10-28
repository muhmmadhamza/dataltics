<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\SubTopicController;
use App\Http\Controllers\TouchpointController;
use App\Http\Controllers\GeneralFunctionsController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\xlsxwriter;
use Illuminate\Support\Facades\Log;
//use Session;
//use Crypt;

class ElasticController extends Controller
{
    public function __construct(Request $request)
    {
        $this->client = ClientBuilder::create()->setHosts([env('ELASTICSEARCH_HOST').":".env('ELASTICSEARCH_PORT')])->build();
        
        //define('SEARCH_INDEX_NAME', env('ELASTICSEARCH_DEFAULTINDEX'));
        //define('DATA_FETCH_DAYS_NUMBER', env('DATA_FETCH_DAYS_NUMBER'));
        //define('DATA_FETCH_FROM_TIME', env('DATA_FETCH_FROM_TIME'));
        //define('DATA_FETCH_TO_TIME', env('DATA_FETCH_TO_TIME'));

        $this->search_index_name = env('ELASTICSEARCH_DEFAULTINDEX');
        $this->date_fetch_days_number = env('DATA_FETCH_DAYS_NUMBER');
        $this->date_fetch_from_time = env('DATA_FETCH_FROM_TIME');
        $this->date_fetch_to_time = env('DATA_FETCH_TO_TIME');
        
        $this->printmedia_search_index_name = env('PRINTMEDIA_ELASTIC_INDEX');
        
        $this->topic_obj = new TopicController();
        $this->subtopic_obj = new SubTopicController();
        $this->touchpoint_obj = new TouchpointController();
        $this->gen_func_obj = new GeneralFunctionsController();
        $this->cus_obj = new CustomerController();

        $this->loaded_topic_id = \Session::get('current_loaded_project');
    }

    public function get_touchpoint_sentiments($tid, Request $request)
    {
        //$request = new \Illuminate\Http\Request();
        //$request->setMethod('POST');
        //dd($request);
        if($request["pdf_report"] == 'yes')
        {
            $greater_than_time = $request["dfrom"];
            $less_than_time = $request["dto"];

            $topic_session = $request["tid"];
            $subtopic_session_id = $request["stid"];

            $topic_query_string = $this->topic_obj->get_topic_elastic_query($topic_session);
            $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
            $tp_es_query_string = $this->touchpoint_obj->get_touchpoint_elastic_query($request["tpid"]);
        }
        else
        {
            $greater_than_time = $this->date_fetch_from_time;
            $less_than_time = $this->date_fetch_to_time;
            $days_difference = $this->date_fetch_days_number;

            $topic_session = \Session::get('current_loaded_project');
            $subtopic_session_id = \Session::get('current_loaded_subtopic');

            $topic_query_string = $this->topic_obj->get_topic_elastic_query($this->loaded_topic_id);
            $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
            $tp_es_query_string = $this->touchpoint_obj->get_touchpoint_elastic_query($tid);
        }
        

        $es_query = $topic_query_string.' AND '.$subtopic_query_string.' AND '.$tp_es_query_string;

        $params = [
            'index' => $this->search_index_name,
            'type' => 'mytype',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            ['query_string' => ['query' => $es_query ]],
                            ['match' => ['predicted_sentiment_value' => 'positive']],
                            ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                        ]
                    ]
                ]
            ]
        ];
        //print_r($params);
        $results = $this->client->count($params);

        $pos_senti = $results["count"];

        $params = [
            'index' => $this->search_index_name,
            'type' => 'mytype',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            ['query_string' => ['query' => $es_query ]],
                            ['match' => ['predicted_sentiment_value' => 'negative']],
                            ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
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
                            ['query_string' => ['query' => $es_query ]],
                            ['match' => ['predicted_sentiment_value' => 'neutral']],
                            ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                        ]
                    ]
                ]
            ]
        ];

        $results = $this->client->count($params);

        $neu_senti = $results["count"];

        $total_sentiments = $pos_senti + $neg_senti + $neu_senti;
        
        //$response_output = 'Positive,'.number_format(($pos_senti / $total_sentiments) * 100, 2).'|Negative,'.number_format(($neg_senti / $total_sentiments) * 100, 2).'|Neutral,'.number_format(($neu_senti / $total_sentiments) * 100, 2);

        if($total_sentiments == 0)
            $total_sentiments = 1;

        //$senti_array["pos"] = number_format(($pos_senti / $total_sentiments) * 100, 2);
        //$senti_array["neg"] = number_format(($neg_senti / $total_sentiments) * 100, 2);
        //$senti_array["neu"] = number_format(($neu_senti / $total_sentiments) * 100, 2);
        $senti_array["pos"] = $pos_senti;
        $senti_array["neg"] = $neg_senti;
        $senti_array["neu"] = $neu_senti;

        return $senti_array;
    }
        
    public function get_data_from_elastic(Request $request)
    {
        $topic_query_string = '';
        $mentions = array();
        $response_output = '';
        $inc_dec_from_date = '';
        $inc_dec_to_date = '';
        $str_to_search = '';
        
        $topic_session = \Session::get('current_loaded_project');
        
        if(isset($request["pdf_report_data"]) && $request["pdf_report_data"] == 'yes')
            $loaded_topic_id = $request["tid"];
        else 
            $loaded_topic_id = $topic_session;
        
        if(isset($request["topic_type"]) && $request["topic_type"] != 'competitor_analysis')
            $topic_query_string = $this->topic_obj->get_topic_elastic_query($loaded_topic_id);
        else if(isset($request["topic_type"]) && $request["topic_type"] == 'competitor_analysis')
        {
            
        }
        else
            $topic_query_string = $this->topic_obj->get_topic_elastic_query($loaded_topic_id);
        
        //echo $topic_query_string; exit;
        
        if(isset($request["section"]) && !empty($request["section"]))
        {
            $greater_than_time = $this->date_fetch_from_time;
            $less_than_time = $this->date_fetch_to_time;
            $days_difference = $this->date_fetch_days_number;
            
            if(isset($request["dash_filters_applied"]) && $request["dash_filters_applied"] == 'yes')
            {
                if(isset($request["tm_slot"]) && $request["tm_slot"] == 'custom')
                {
                    if(isset($request["from_date"]) && !empty($request["from_date"]) && !is_null($request["from_date"]))
                    {
                        //$greater_than_time = date("Y-m-d", strtotime($request["from_date"]));
                        $greater_than_time = date_create_from_format('j F, Y', $request["from_date"]);
                        $greater_than_time = date_format($greater_than_time, 'Y-m-d');

                    }
                    else
                        $greater_than_time = date("Y-m-d", strtotime('-90 day', strtotime(date("Y-m-d"))));

                    if(isset($request["to_date"]) && !empty($request["to_date"]) && !is_null($request["to_date"]))
                    {
                        //$less_than_time = date("Y-m-d", strtotime($request["to_date"]));
                        $less_than_time = date_create_from_format('j F, Y', $request["to_date"]);
                        $less_than_time = date_format($less_than_time, 'Y-m-d');
                    }
                    else
                        $less_than_time = date("Y-m-d");
                }
                else
                {
                    if($request["tm_slot"] == 'today')
                    {
                        $greater_than_time = date("Y-m-d");
                        $less_than_time = date("Y-m-d");
                    }
                    else if($request["tm_slot"] == '24h')
                    {
                        $greater_than_time = date('Y-m-d', strtotime(date('Y-m-d') . " -24 hours"));
                        $less_than_time = date("Y-m-d");
                    }
                    else
                    {
                        $greater_than_time = date('Y-m-d', strtotime(date('Y-m-d') . " -".$request["tm_slot"]." day"));
                        $less_than_time = date("Y-m-d");
                    }
                }
                
                
                
                $days_difference = $this->gen_func_obj->date_difference($less_than_time, $greater_than_time);
                //echo 'gte: '.strtotime($request["from_date"]);
                if (isset($request["tags"]) && !empty($request["tags"]))
                {
                    $topic_urls = '';
                    $topic_key_hash = '';

                    $tags_str = $request["tags"];

                    $tags_array = explode(",", $tags_str);

                    for ($i = 0; $i < count($tags_array); $i++)
                    {
                        if(!empty($tags_array[$i]))
                        {
                            if (substr($tags_array[$i], 0, 4) == 'http') //means url is added
                            {
                                $topic_urls .= '"' . $tags_array[$i] . '" ' . $request["opr"] . ' ';
                            }
                            else
                            {
                                $topic_key_hash .= '"' . $tags_array[$i] . '" ' . $request["opr"] . ' ';
                            }
                        }
                    }

                    if ($_POST['opr'] == 'OR')
                        $topic_key_hash = substr($topic_key_hash, 0, -4);
                    else
                        $topic_key_hash = substr($topic_key_hash, 0, -5);

                    if ($_POST['opr'] == 'OR')
                        $topic_urls = substr($topic_urls, 0, -4);
                    else
                        $topic_urls = substr($topic_urls, 0, -5);

                    if (!empty($topic_key_hash) && !empty($topic_urls))
                        $str_to_search = '(p_message_text:(' . $topic_key_hash . ' OR ' . $topic_urls . ') OR u_username:(' . $topic_key_hash . ') OR u_fullname:(' . $topic_key_hash . ') OR u_source:(' . $topic_urls . '))';

                    if (!empty($topic_key_hash) && empty($topic_urls))
                        $str_to_search = '(p_message_text:(' . $topic_key_hash . ') OR u_fullname:(' . $topic_key_hash . '))';

                    if (empty($topic_key_hash) && !empty($topic_urls))
                        $str_to_search = 'u_source:(' . $topic_urls . ')';
                    
                    if(!empty($str_to_search))
                        $topic_query_string = $str_to_search;
                }
                
                if (isset($request["senti"]) && !empty($request["senti"]) && !is_null($request["senti"]) && $request["senti"] != 'null')
                {
                    $senti = explode(",", $request["senti"]);

                    $temp_str = '';

                    for ($i = 0; $i < count($senti); $i++)
                    {
                        $temp_str .= '"' . $senti[$i] . '" OR ';
                    }

                    $topic_query_string .= ' AND predicted_sentiment_value:(' . substr($temp_str, 0, -4) . ')';
                }

                if (isset($request["dsource"]) && !empty($request["dsource"]) && !is_null($request["dsource"]) && $request["dsource"] != 'null')
                {
                    $dsource = explode(",", $request["dsource"]);

                    $temp_str = '';

                    for ($i = 0; $i < count($dsource); $i++)
                    {
                        $temp_str .= '"' . $dsource[$i] . '" OR ';
                    }

                    $topic_query_string .= ' AND source:(' . substr($temp_str, 0, -4) . ')';
                }

                if (isset($request["dloc"]) && !empty($request["dloc"]) && !is_null($request["dloc"]) && $request["dloc"] != 'null')
                {
                    $dloc = explode(",", $request["dloc"]);

                    $temp_str = '';

                    for ($i = 0; $i < count($dloc); $i++)
                    {
                        $temp_str .= '"' . $dloc[$i] . '" OR ';
                    }

                    $topic_query_string .= ' AND u_country:(' . substr($temp_str, 0, -4) . ')';
                }

                if (isset($request["dlang"]) && !empty($request["dlang"]) && !is_null($request["dlang"]) && $request["dlang"] != 'null')
                {
                    $dlang = explode(",", $request["dlang"]);

                    $temp_str = '';

                    for ($i = 0; $i < count($dlang); $i++)
                    {
                        $temp_str .= '"' . $dlang[$i] . '" OR ';
                    }

                    $topic_query_string .= ' AND lange_detect:(' . substr($temp_str, 0, -4) . ')'; // AND u_country:('.substr($temp_str, 0, -4).')';
                }
            }
            //echo $topic_query_string; exit;
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
             
            if($request["section"] == 'dashboard_mentions_graph' || $request["section"] == 'subtopic_mentions_graph')
            {//Log::info($topic_query_string);
                $date_count_string = '';
                $dates_array = array();
                $max_date = '';
                $max_mentions = 0;
                //echo $topic_query_string;
                $subtopic_session_id = \Session::get('current_loaded_subtopic');
                
                if($request["section"] == 'subtopic_mentions_graph')
                {
                    $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
                    $topic_query_string .= ' AND '.$subtopic_query_string;
                }
                
                if(isset($request["manual_filter"]) && $request["manual_filter"] == 'yes') //this is if individual graph filters are set
                {
                    if($request["filter_type"] == 'mentions_today')
                    {
                        $d_date = date('Y-m-d', strtotime(date('Y-m-d')));
                        
                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            [ 'query_string' => [ 'query' => $topic_query_string.' AND p_created_time:("' . $d_date . '")' ] ]
                                        ]
                                    ]
                                ]
                            ]
                        ];

                        $es_data = $this->client->count($params);
                        $dates_array[] = $d_date.','.$es_data["count"];
                        
                        if($es_data["count"] > $max_mentions)
                        {
                            $max_mentions = $es_data["count"];
                            $max_date = $d_date;
                        }
                    }
                    else if($request["filter_type"] == 'mentions_this_week')
                    {
                        for ($i = 0; $i <= 6; $i++)
                        {
                            $d_date = date('Y-m-d', strtotime(date('Y-m-d') . " -" . $i . " day"));

                            $params = [
                                'index' => $this->search_index_name,
                                'type' => 'mytype',
                                'body' => [
                                    'query' => [
                                        'bool' => [
                                            'must' => [
                                                [ 'query_string' => [ 'query' => $topic_query_string.' AND p_created_time:("' . $d_date . '")' ] ]
                                            ]
                                        ]
                                    ]
                                ]
                            ];

                            $es_data = $this->client->count($params);
                            $dates_array[] = $d_date.','.$es_data["count"];
                            
                            if($es_data["count"] > $max_mentions)
                            {
                                $max_mentions = $es_data["count"];
                                $max_date = $d_date;
                            }
                        }
                    }
                    else if($request["filter_type"] == 'mentions_this_month')
                    {
                        $year_month = date("Y-m");
                        $day = date("d");
                        $zero_val = strpos($day, '0');
                        
                        if($zero_val == 0)
                            $limit = str_replace('0', '', $day);
                        else 
                            $limit = $day;
                                
                        for ($i = 0; $i < $limit; $i++)
                        {
                            $d_date = date('Y-m-d', strtotime(date('Y-m-d') . " -" . $i . " day"));

                            $params = [
                                'index' => $this->search_index_name,
                                'type' => 'mytype',
                                'body' => [
                                    'query' => [
                                        'bool' => [
                                            'must' => [
                                                [ 'query_string' => [ 'query' => $topic_query_string.' AND p_created_time:("' . $d_date . '")' ] ]
                                            ]
                                        ]
                                    ]
                                ]
                            ];

                            $es_data = $this->client->count($params);
                            $dates_array[] = $d_date.','.$es_data["count"];
                            
                            if($es_data["count"] > $max_mentions)
                            {
                                $max_mentions = $es_data["count"];
                                $max_date = $d_date;
                            }
                        }
                    }
                    else if($request["filter_type"] == 'mentions_x_days')
                    {
                        if(isset($request["days_num"]) && !empty($request["days_num"]) && is_numeric($request["days_num"]))
                        {
                            //if($request["days_num"] > 30)
                              //  $inc = 5;
                            //else
                                $inc = 1;
                            
                            for ($i = 0; $i <= $request["days_num"]; $i+=$inc)
                            {
                                $d_date = date('Y-m-d', strtotime(date('Y-m-d') . " -" . $i . " day"));

                                $params = [
                                    'index' => $this->search_index_name,
                                    'type' => 'mytype',
                                    'body' => [
                                        'query' => [
                                            'bool' => [
                                                'must' => [
                                                    [ 'query_string' => [ 'query' => $topic_query_string.' AND p_created_time:("' . $d_date . '")' ] ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ];

                                $es_data = $this->client->count($params);
                                $dates_array[] = $d_date.','.$es_data["count"];
                                
                                if($es_data["count"] > $max_mentions)
                                {
                                    $max_mentions = $es_data["count"];
                                    $max_date = $d_date;
                                }
                            }
                        }
                        else
                            $dates_array[] = 'Incomplete days request';
                        
                    }
                    else if($request["filter_type"] == 'custom_dates')
                    {
                        
                        if((isset($request["from_date"]) && !empty($request["from_date"]) && isset($request["to_date"]) && !empty($request["to_date"])) || $request["dash_filters_applied"] == 'yes')
                        {
                            if(isset($request["dash_filters_applied"]) && $request["dash_filters_applied"] == 'yes')
                            {
                                $from_date = $greater_than_time;
                                $to_date = $less_than_time;
                            }
                            else
                            {
                                $from_date = $request["from_date"];
                                $to_date = $request["to_date"];
                            }
                                                        
                            $days_diff = $this->gen_func_obj->date_difference($from_date, $to_date);
                        
                            for ($i = 0; $i <= $days_diff; $i++)
                            {
                                $d_date = date('Y-m-d', strtotime(date($to_date) . " -" . $i . " day"));

                                $params = [
                                    'index' => $this->search_index_name,
                                    'type' => 'mytype',
                                    'body' => [
                                        'query' => [
                                            'bool' => [
                                                'must' => [
                                                    [ 'query_string' => [ 'query' => $topic_query_string.' AND p_created_time:("' . $d_date . '")' ] ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ];

                                $es_data = $this->client->count($params);
                                
                                $dates_array[] = $d_date.','.$es_data["count"];
                                
                                if($es_data["count"] > $max_mentions)
                                {
                                    $max_mentions = $es_data["count"];
                                    $max_date = $d_date;
                                }
                            }
                        }
                        else
                            $dates_array[] = 'Incomplete dates request';
                    }
                    else
                        $dates_array[] = 'Incomplete request';
                }
                else
                {
                    /*for ($i = 0; $i <= $days_difference; $i++)
                    {
                        $d_date = date('Y-m-d', strtotime(date('Y-m-d') . " -" . $i . " day"));

                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            [ 'query_string' => [ 'query' => $topic_query_string.' AND p_created_time:("' . $d_date . '")' ] ]
                                        ]
                                    ]
                                ]
                            ]
                        ];

                        $es_data = $this->client->count($params);
                        $dates_array[] = $d_date.','.$es_data["count"];
                        //dd($params);
                    }*/
                    //new
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'size' => 0,
                        'docvalue_fields' => array("field" => "p_created_time", "format" => "date_time"),
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        [ 'query_string' => [  'query' => $topic_query_string  ] ],
                                        [ 'range' => [ 'p_created_time' => [ 'gte' => $greater_than_time, 'lte' => $less_than_time ] ] ]
                                    ]
                                ]
                            ],
                            'aggs' => [
                                '2' => [
                                    'date_histogram' => [ 
                                        "field" => "p_created_time", "interval" => "1d", "min_doc_count" => 0
                                    ]
                                ]
                            ]
                        ]
                    ];

                    $es_data = $this->client->search($params);
                    
                    for($p=0; $p<count($es_data["aggregations"]["2"]["buckets"]); $p++)
                    {
                        if($es_data["aggregations"]["2"]["buckets"][$p]["doc_count"] > $max_mentions)
                        {
                            $max_mentions = $es_data["aggregations"]["2"]["buckets"][$p]["doc_count"];
                            $max_date = date("Y-m-d", strtotime($es_data["aggregations"]["2"]["buckets"][$p]["key_as_string"]));
                        }
                        
                        $dates_array[] = date("Y-m-d", strtotime($es_data["aggregations"]["2"]["buckets"][$p]["key_as_string"])).','.$es_data["aggregations"]["2"]["buckets"][$p]["doc_count"];
                    }
                    //end new
                }
                
                
                krsort($dates_array);
                
                $abc = '';
                
                for($ii=count($dates_array)-1; $ii>=0; $ii--)
                {
                    $abc .= $dates_array[$ii].'|';
                }
                
                $response_output = substr($abc, 0, -1);
                
                //Calculate max mentions popup data
                /*$hash_keys = $this->topic_obj->get_topic_hash_keywords($loaded_topic_id);
                $in_val = '';
                
                if(!empty($hash_keys[0]->topic_hash_tags))
                {
                    $htags = explode("|", $hash_keys[0]->topic_hash_tags);

                    for ($i = 0; $i < count($htags); $i ++)
                    {
                        if (! empty(trim($htags[$i])))
                            $in_val .= trim($htags[$i]).",";
                    }
                }        

                if(!empty($hash_keys[0]->topic_keywords))
                {
                    $keywords = explode(",", $hash_keys[0]->topic_keywords);

                    for ($i = 0; $i < count($keywords); $i ++)
                    {
                        if (! empty(trim($keywords[$i])))
                            $in_val .= trim($keywords[$i]).",";
                    }
                }
                
                $in_val = substr($in_val, 0, -1);
                $kh = explode(",", $in_val);
                $popup_str = '';
                
                for($p=0; $p<count($kh); $p++)
                {
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        [ 'query_string' => [ 'query' => $topic_query_string.' AND (p_message_text:("'.$kh[$p].'") OR u_fullname:("'.$kh[$p].'") OR u_source:("'.$kh[$p].'")) AND p_created_time:("' . $max_date . '")' ] ]
                                    ]
                                ]
                            ]
                        ]
                    ];

                    $key_data = $this->client->count($params);
                    
                    $popup_str .= '<b><span style="font-size:12px !important;">'.number_format($key_data["count"]).'</span></b> posts containing <b>'.$kh[$p].'</b><br>';
                }*/
                //END: calculate max mentions popup data
                
                echo $response_output.'~'.$max_date.','.$max_mentions; //.'~'.$popup_str;
            }
            else if($request["section"] == 'dashboard_engagement' || $request["section"] == 'subtopic_engagement')
            {//echo $topic_query_string; exit;
                $subtopic_session_id = \Session::get('current_loaded_subtopic');
                
                if($request["section"] == 'subtopic_engagement')
                {
                    $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
                    $topic_query_string .= ' AND '.$subtopic_query_string;
                }
                
                if(isset($request["manual_filter"]) && $request["manual_filter"] == 'yes') //this is if individual graph filters are set
                {
                    $data_dates = array();
                    $data_counts = array();

                    if($request["filter_type"] == 'engagement_today')
                    {
                        $d_date = date('Y-m-d', strtotime(date('Y-m-d')));

                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            ['query_string' => ['query' => $topic_query_string . ' AND p_created_time:("' . $d_date . '")']]
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

                        $data_dates[] = $d_date;
                        $data_counts[] = $tot_eng;
                    }
                    else if($request["filter_type"] == 'engagements_this_week')
                    {
                        for ($i = 0; $i <= 6; $i++)
                        {
                            $d_date = date('Y-m-d', strtotime(date('Y-m-d') . " -" . $i . " day"));

                            $params = [
                                'index' => $this->search_index_name,
                                'type' => 'mytype',
                                'body' => [
                                    'query' => [
                                        'bool' => [
                                            'must' => [
                                                ['query_string' => ['query' => $topic_query_string . ' AND p_created_time:("' . $d_date . '")']]
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

                            $data_dates[] = $d_date;
                            $data_counts[] = $tot_eng;
                        }
                    }
                    else if($request["filter_type"] == 'engagements_this_month')
                    {
                        $year_month = date("Y-m");
                        $day = date("d");
                        $zero_val = strpos($day, '0');
                        
                        if($zero_val == 0)
                            $limit = str_replace('0', '', $day);
                        else 
                            $limit = $day;

                        for ($i = 0; $i < $limit; $i++)
                        {
                            $d_date = date('Y-m-d', strtotime(date('Y-m-d') . " -" . $i . " day"));
                            
                            $params = [
                                'index' => $this->search_index_name,
                                'type' => 'mytype',
                                'body' => [
                                    'query' => [
                                        'bool' => [
                                            'must' => [
                                                ['query_string' => ['query' => $topic_query_string . ' AND p_created_time:("' . $d_date . '")']]
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
                            
                            $data_dates[] = $d_date;
                            $data_counts[] = $tot_eng;
                        }
                    }
                    else if($request["filter_type"] == 'engagements_x_days')
                    {
                        if(isset($request["days_num"]) && !empty($request["days_num"]) && is_numeric($request["days_num"]))
                        {
                            for ($i = 1; $i <= $request["days_num"]; $i++)
                            {
                                $d_date = date('Y-m-d', strtotime(date('Y-m-d') . " -" . $i . " day"));

                                $params = [
                                    'index' => $this->search_index_name,
                                    'type' => 'mytype',
                                    'body' => [
                                        'query' => [
                                            'bool' => [
                                                'must' => [
                                                    ['query_string' => ['query' => $topic_query_string . ' AND p_created_time:("' . $d_date . '")']]
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
                                
                                $data_dates[] = $d_date;
                                $data_counts[] = $tot_eng;
                            }
                        }
                        else
                        {
                            $data_dates[] = 'Invalid access days count';
                            $data_counts[] = 'Invalid access days count';
                        }
                    }
                    else if($request["filter_type"] == 'custom_engagement_dates')
                    {
                        
                        if(isset($request["from_date"]) && !empty($request["from_date"]) && isset($request["to_date"]) && !empty($request["to_date"]))
                        {
                            $from_date = $request["from_date"];
                            $to_date = $request["to_date"];
                            
                            $days_diff = $this->gen_func_obj->date_difference($from_date, $to_date);
                        
                            for ($i = 0; $i <= $days_diff; $i++)
                            {
                                $d_date = date('Y-m-d', strtotime(date($to_date) . " -" . $i . " day"));

                                //$d_date = date('Y-m-d', strtotime(date('Y-m-d') . " -" . $i . " day"));

                                $params = [
                                    'index' => $this->search_index_name,
                                    'type' => 'mytype',
                                    'body' => [
                                        'query' => [
                                            'bool' => [
                                                'must' => [
                                                    ['query_string' => ['query' => $topic_query_string . ' AND p_created_time:("' . $d_date . '")']]
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
                                
                                $data_dates[] = $d_date;
                                $data_counts[] = $tot_eng;
                            }
                        }
                        else
                        {
                            $data_dates[] = 'Invalid custom dates';
                            $data_counts[] = 'Invalid custom dates';
                        }
                    }
                    else
                    {
                        $data_dates[] = 'Invalid access date';
                        $data_counts[] = 'Invalid access count';
                    }

                    return response()->json([
                            'data_dates' => array_reverse($data_dates),
                            'data_counts' => array_reverse($data_counts),
                        ]);
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
                                        ['query_string' => ['query' => $topic_query_string ]],
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
//echo '<pre>'; print_r($params); exit;
                    $results = $this->client->search($params);
                    $tot_eng = $results["aggregations"]["total_shares"]["value"] + $results["aggregations"]["total_comments"]["value"] + $results["aggregations"]["total_likes"]["value"] + $results["aggregations"]["total_views"]["value"];
                    
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string ]],
                                        ['range' => ['p_created_time' => ['gte' => $inc_dec_from_date, 'lte' => $inc_dec_to_date]]]
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

                    $results1 = $this->client->search($params);
                    $tot_eng1 = $results1["aggregations"]["total_shares"]["value"] + $results1["aggregations"]["total_comments"]["value"] + $results1["aggregations"]["total_likes"]["value"] + $results1["aggregations"]["total_views"]["value"];

                    if ($tot_eng > $tot_eng1) //increase
                        $result_diff = $tot_eng - $tot_eng1;
                    else
                        $result_diff = $tot_eng1 - $tot_eng;

                    if($tot_eng > 0)
                        $per_diff = ($result_diff / $tot_eng) * 100;
                    else
                        $per_diff = 0;

                    if ($tot_eng > $tot_eng1) //increase
                    {
                        $response = 'increase|' . number_format($per_diff, 2);
                    }
                    else
                        $response = 'decrease|'.number_format($per_diff, 2);
                    
                    //Graph calculations for last 5 days
                    for ($i = 1; $i <= 7; $i++)
                    {
                        $subtopic_session_id = \Session::get('current_loaded_subtopic');
                        
                        $d_date = date('Y-m-d', strtotime(date('Y-m-d') . " -" . $i . " day"));
                        
                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            [ 'query_string' => [ 'query' => $topic_query_string.' AND p_created_time:("' . $d_date . '")' ] ]
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
                        
                        $es_data = $this->client->search($params);
                        $total_eng = $es_data["aggregations"]["total_shares"]["value"] + $es_data["aggregations"]["total_comments"]["value"] + $es_data["aggregations"]["total_likes"]["value"] + $results["aggregations"]["total_views"]["value"];
                        $dates_array[] = date('D', strtotime($d_date)).','.$total_eng;
                    }
                    
                    krsort($dates_array);
                    $abc = '';
                    for($ii=count($dates_array)-1; $ii>=0; $ii--)
                    {
                        $abc .= $dates_array[$ii].'|';
                    }
                    //End graph calculations
                    
                    $response_output = $this->gen_func_obj->format_number_data($tot_eng).'|'.$response.'~'.substr($abc, 0, -1).'~'.$tot_eng;
                    
                    echo $response_output;
                }
            }
            else if($request["section"] == 'dashboard_shares' || $request["section"] == 'subtopic_shares')
            {
                $subtopic_session_id = \Session::get('current_loaded_subtopic');
                
                if($request["section"] == 'subtopic_shares')
                {
                    $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
                    $topic_query_string .= ' AND '.$subtopic_query_string;
                                        
                }
                
                if(isset($request["manual_filter"]) && $request["manual_filter"] == 'yes') //this is if individual graph filters are set
                {
                    $data_dates = array();
                    $data_counts = array();

                    if($request["filter_type"] == 'shares_today')
                    {
                        $d_date = date('Y-m-d', strtotime(date('Y-m-d')));

                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            [ 'query_string' => [ 'query' => $topic_query_string.' AND p_created_time:("' . $d_date . '") AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")' ] ]
                                        ]
                                    ]
                                ]
                                ,
                                'aggs' => [
                                    'total_shares' => ['sum' => ['field' => 'p_shares']]
                                ]
                            ]
                        ];
                        
                        $es_data = $this->client->search($params);
                        $tot_shares = $es_data["aggregations"]["total_shares"]["value"];

                        $data_dates[] = $d_date;
                        $data_counts[] = $tot_shares;
                    }
                    else if($request["filter_type"] == 'shares_this_week')
                    {
                        for ($i = 0; $i <= 6; $i++)
                        {
                            $d_date = date('Y-m-d', strtotime(date('Y-m-d') . " -" . $i . " day"));

                            $params = [
                                'index' => $this->search_index_name,
                                'type' => 'mytype',
                                'body' => [
                                    'query' => [
                                        'bool' => [
                                            'must' => [
                                                [ 'query_string' => [ 'query' => $topic_query_string.' AND p_created_time:("' . $d_date . '") AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")' ] ]
                                            ]
                                        ]
                                    ]
                                    ,
                                    'aggs' => [
                                        'total_shares' => ['sum' => ['field' => 'p_shares']]
                                    ]
                                ]
                            ];
                            
                            $es_data = $this->client->search($params);
                            $tot_shares = $es_data["aggregations"]["total_shares"]["value"];

                            $data_dates[] = $d_date;
                            $data_counts[] = $tot_shares;
                        }
                    }
                    else if($request["filter_type"] == 'shares_this_month')
                    {
                        $year_month = date("Y-m");
                        $day = date("d");
                        $zero_val = strpos($day, '0');
                        
                        if($zero_val == 0)
                            $limit = str_replace('0', '', $day);
                        else 
                            $limit = $day;

                        for ($i = 0; $i < $limit; $i++)
                        {
                            $d_date = date('Y-m-d', strtotime(date('Y-m-d') . " -" . $i . " day"));

                            $params = [
                                'index' => $this->search_index_name,
                                'type' => 'mytype',
                                'body' => [
                                    'query' => [
                                        'bool' => [
                                            'must' => [
                                                [ 'query_string' => [ 'query' => $topic_query_string.' AND p_created_time:("' . $d_date . '") AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")' ] ]
                                            ]
                                        ]
                                    ]
                                    ,
                                    'aggs' => [
                                        'total_shares' => ['sum' => ['field' => 'p_shares']]
                                    ]
                                ]
                            ];
                            
                            $es_data = $this->client->search($params);
                            $tot_shares = $es_data["aggregations"]["total_shares"]["value"];

                            $data_dates[] = $d_date;
                            $data_counts[] = $tot_shares;
                        }
                    }
                    else if($request["filter_type"] == 'shares_x_days')
                    {
                        if(isset($request["days_num"]) && !empty($request["days_num"]) && is_numeric($request["days_num"]))
                        {
                            for ($i = 1; $i <= $request["days_num"]; $i++)
                            {
                                $d_date = date('Y-m-d', strtotime(date('Y-m-d') . " -" . $i . " day"));

                                $params = [
                                    'index' => $this->search_index_name,
                                    'type' => 'mytype',
                                    'body' => [
                                        'query' => [
                                            'bool' => [
                                                'must' => [
                                                    [ 'query_string' => [ 'query' => $topic_query_string.' AND p_created_time:("' . $d_date . '") AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")' ] ]
                                                ]
                                            ]
                                        ]
                                        ,
                                        'aggs' => [
                                            'total_shares' => ['sum' => ['field' => 'p_shares']]
                                        ]
                                    ]
                                ];
                                
                                $es_data = $this->client->search($params);
                                $tot_shares = $es_data["aggregations"]["total_shares"]["value"];

                                $data_dates[] = $d_date;
                                $data_counts[] = $tot_shares;
                            }
                        }
                        else
                        {
                            $data_dates[] = 'Invalid access days count';
                            $data_counts[] = 'Invalid access days count';
                        }
                    }
                    else if($request["filter_type"] == 'custom_shares_dates')
                    {
                        
                        if(isset($request["from_date"]) && !empty($request["from_date"]) && isset($request["to_date"]) && !empty($request["to_date"]))
                        {
                            $from_date = $request["from_date"];
                            $to_date = $request["to_date"];
                            
                            $days_diff = $this->gen_func_obj->date_difference($from_date, $to_date);
                        
                            for ($i = 0; $i <= $days_diff; $i++)
                            {
                                $d_date = date('Y-m-d', strtotime(date($to_date) . " -" . $i . " day"));

                                //$d_date = date('Y-m-d', strtotime(date('Y-m-d') . " -" . $i . " day"));

                                $params = [
                                    'index' => $this->search_index_name,
                                    'type' => 'mytype',
                                    'body' => [
                                        'query' => [
                                            'bool' => [
                                                'must' => [
                                                    [ 'query_string' => [ 'query' => $topic_query_string.' AND p_created_time:("' . $d_date . '") AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")' ] ]
                                                ]
                                            ]
                                        ]
                                        ,
                                        'aggs' => [
                                            'total_shares' => ['sum' => ['field' => 'p_shares']]
                                        ]
                                    ]
                                ];
                                
                                $es_data = $this->client->search($params);
                                $tot_shares = $es_data["aggregations"]["total_shares"]["value"];

                                $data_dates[] = $d_date;
                                $data_counts[] = $tot_shares;
                            }
                        }
                        else
                        {
                            $data_dates[] = 'Invalid custom dates';
                            $data_counts[] = 'Invalid custom dates';
                        }
                    }
                    else
                    {
                        $data_dates[] = 'Invalid access date';
                        $data_counts[] = 'Invalid access count';
                    }

                    return response()->json([
                            'data_dates' => array_reverse($data_dates),
                            'data_counts' => array_reverse($data_counts)
                        ]);
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
                                        ['query_string' => ['query' => $topic_query_string]], // . ' AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")'
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                    ]
                                ]
                            ],
                            'aggs' => [
                                'total_shares' => ['sum' => ['field' => 'p_shares']]
                            ]
                        ]
                    ];
                    $pp = $params;
                    $results = $this->client->search($params);

                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string]], // . ' AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")'
                                        ['range' => ['p_created_time' => ['gte' => $inc_dec_from_date, 'lte' => $inc_dec_to_date]]]
                                    ]
                                ]
                            ],
                            'aggs' => [
                                'total_shares' => ['sum' => ['field' => 'p_shares']]
                            ]
                        ]
                    ];

                    $results1 = $this->client->search($params);

                    if ($results["aggregations"]["total_shares"]["value"] > $results1["aggregations"]["total_shares"]["value"]) //increase
                        $result_diff = $results["aggregations"]["total_shares"]["value"] - $results1["aggregations"]["total_shares"]["value"];
                    else
                        $result_diff = $results1["aggregations"]["total_shares"]["value"] - $results["aggregations"]["total_shares"]["value"];

                    if($results["aggregations"]["total_shares"]["value"] > 0)
                        $per_diff = ($result_diff / $results["aggregations"]["total_shares"]["value"]) * 100;
                    else
                        $per_diff = 0;

                    if ($results["aggregations"]["total_shares"]["value"] == 0)
                        $per_diff = 0;

                    if ($results["aggregations"]["total_shares"]["value"] > $results1["aggregations"]["total_shares"]["value"]) //increase
                        $response = 'increase|'.number_format($per_diff, 2);
                    else
                        $response = 'decrease|'.number_format($per_diff, 2);
                    
                    //Graph calculations for last 5 days
                    for ($i = 1; $i <= 7; $i++)
                    {
                        $subtopic_session_id = \Session::get('current_loaded_subtopic');
                        
                        $d_date = date('Y-m-d', strtotime(date('Y-m-d') . " -" . $i . " day"));
                        
                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            [ 'query_string' => [ 'query' => $topic_query_string.' AND p_created_time:("' . $d_date . '")' ] ] // AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")
                                        ]
                                    ]
                                ]
                                ,
                                'aggs' => [
                                    'total_shares' => ['sum' => ['field' => 'p_shares']]
                                ]
                            ]
                        ];
                        
                        $es_data = $this->client->search($params);
                        $dates_array[] = date('D', strtotime($d_date)).','.$es_data["aggregations"]["total_shares"]["value"];
                    }
                    
                    krsort($dates_array);
                    $abc = '';
                    for($ii=count($dates_array)-1; $ii>=0; $ii--)
                    {
                        $abc .= $dates_array[$ii].'|';
                    }
                    //End graph calculations

                    $response_output = $this->gen_func_obj->format_number_data($results["aggregations"]["total_shares"]["value"]).'|'.$response.'~'.substr($abc, 0, -1).'~'.$results["aggregations"]["total_shares"]["value"];
                    
                    echo $response_output;
                }
            }
            else if($request["section"] == 'dashboard_likes' || $request["section"] == 'subtopic_likes')
            {
                $subtopic_session_id = \Session::get('current_loaded_subtopic');
                
                if($request["section"] == 'subtopic_likes')
                {
                    $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
                    $topic_query_string .= ' AND '.$subtopic_query_string;
                    
                }
                
                if(isset($request["manual_filter"]) && $request["manual_filter"] == 'yes') //this is if individual graph filters are set
                {
                    $data_dates = array();
                    $data_counts = array();

                    if($request["filter_type"] == 'likes_today')
                    {
                        $d_date = date('Y-m-d', strtotime(date('Y-m-d')));

                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            [ 'query_string' => [ 'query' => $topic_query_string.' AND p_created_time:("' . $d_date . '") AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")' ] ]
                                        ]
                                    ]
                                ]
                                ,
                                'aggs' => [
                                    'total_likes' => ['sum' => ['field' => 'p_likes']]
                                ]
                            ]
                        ];
                        
                        $es_data = $this->client->search($params);
                        $tot_likes = $es_data["aggregations"]["total_likes"]["value"];

                        $data_dates[] = $d_date;
                        $data_counts[] = $tot_likes;
                    }
                    else if($request["filter_type"] == 'likes_this_week')
                    {
                        for ($i = 0; $i <= 6; $i++)
                        {
                            $d_date = date('Y-m-d', strtotime(date('Y-m-d') . " -" . $i . " day"));

                            $params = [
                                'index' => $this->search_index_name,
                                'type' => 'mytype',
                                'body' => [
                                    'query' => [
                                        'bool' => [
                                            'must' => [
                                                [ 'query_string' => [ 'query' => $topic_query_string.' AND p_created_time:("' . $d_date . '") AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")' ] ]
                                            ]
                                        ]
                                    ]
                                    ,
                                    'aggs' => [
                                        'total_likes' => ['sum' => ['field' => 'p_likes']]
                                    ]
                                ]
                            ];
                            
                            $es_data = $this->client->search($params);
                            $tot_likes = $es_data["aggregations"]["total_likes"]["value"];

                            $data_dates[] = $d_date;
                            $data_counts[] = $tot_likes;
                        }
                    }
                    else if($request["filter_type"] == 'likes_this_month')
                    {
                        $year_month = date("Y-m");
                        $day = date("d");
                        $zero_val = strpos($day, '0');
                        
                        if($zero_val == 0)
                            $limit = str_replace('0', '', $day);
                        else 
                            $limit = $day;

                        for ($i = 0; $i < $limit; $i++)
                        {
                            $d_date = date('Y-m-d', strtotime(date('Y-m-d') . " -" . $i . " day"));

                            $params = [
                                'index' => $this->search_index_name,
                                'type' => 'mytype',
                                'body' => [
                                    'query' => [
                                        'bool' => [
                                            'must' => [
                                                [ 'query_string' => [ 'query' => $topic_query_string.' AND p_created_time:("' . $d_date . '") AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")' ] ]
                                            ]
                                        ]
                                    ]
                                    ,
                                    'aggs' => [
                                        'total_likes' => ['sum' => ['field' => 'p_likes']]
                                    ]
                                ]
                            ];
                            
                            $es_data = $this->client->search($params);
                            $tot_likes = $es_data["aggregations"]["total_likes"]["value"];

                            $data_dates[] = $d_date;
                            $data_counts[] = $tot_likes;
                        }
                    }
                    else if($request["filter_type"] == 'likes_x_days')
                    {
                        if(isset($request["days_num"]) && !empty($request["days_num"]) && is_numeric($request["days_num"]))
                        {
                            for ($i = 1; $i <= $request["days_num"]; $i++)
                            {
                                $d_date = date('Y-m-d', strtotime(date('Y-m-d') . " -" . $i . " day"));

                                $params = [
                                    'index' => $this->search_index_name,
                                    'type' => 'mytype',
                                    'body' => [
                                        'query' => [
                                            'bool' => [
                                                'must' => [
                                                    [ 'query_string' => [ 'query' => $topic_query_string.' AND p_created_time:("' . $d_date . '") AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")' ] ]
                                                ]
                                            ]
                                        ]
                                        ,
                                        'aggs' => [
                                            'total_likes' => ['sum' => ['field' => 'p_likes']]
                                        ]
                                    ]
                                ];
                                
                                $es_data = $this->client->search($params);
                                $tot_likes = $es_data["aggregations"]["total_likes"]["value"];

                                $data_dates[] = $d_date;
                                $data_counts[] = $tot_likes;
                            }
                        }
                        else
                        {
                            $data_dates[] = 'Invalid access days count';
                            $data_counts[] = 'Invalid access days count';
                        }
                    }
                    else if($request["filter_type"] == 'custom_likes_dates')
                    {
                        
                        if(isset($request["from_date"]) && !empty($request["from_date"]) && isset($request["to_date"]) && !empty($request["to_date"]))
                        {
                            $from_date = $request["from_date"];
                            $to_date = $request["to_date"];
                            
                            $days_diff = $this->gen_func_obj->date_difference($from_date, $to_date);
                        
                            for ($i = 0; $i <= $days_diff; $i++)
                            {
                                $d_date = date('Y-m-d', strtotime(date($to_date) . " -" . $i . " day"));

                                //$d_date = date('Y-m-d', strtotime(date('Y-m-d') . " -" . $i . " day"));

                                $params = [
                                    'index' => $this->search_index_name,
                                    'type' => 'mytype',
                                    'body' => [
                                        'query' => [
                                            'bool' => [
                                                'must' => [
                                                    [ 'query_string' => [ 'query' => $topic_query_string.' AND p_created_time:("' . $d_date . '") AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")' ] ]
                                                ]
                                            ]
                                        ]
                                        ,
                                        'aggs' => [
                                            'total_likes' => ['sum' => ['field' => 'p_likes']]
                                        ]
                                    ]
                                ];
                                
                                $es_data = $this->client->search($params);
                                $tot_likes = $es_data["aggregations"]["total_likes"]["value"];

                                $data_dates[] = $d_date;
                                $data_counts[] = $tot_likes;
                            }
                        }
                        else
                        {
                            $data_dates[] = 'Invalid custom dates';
                            $data_counts[] = 'Invalid custom dates';
                        }
                    }
                    else
                    {
                        $data_dates[] = 'Invalid access date';
                        $data_counts[] = 'Invalid access count';
                    }

                    return response()->json([
                            'data_dates' => array_reverse($data_dates),
                            'data_counts' => array_reverse($data_counts)
                        ]);
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
                                        ['query_string' => ['query' => $topic_query_string ]],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                    ]
                                ]
                            ],
                            'aggs' => [
                                'total_likes' => ['sum' => ['field' => 'p_likes']]
                            ]
                        ]
                    ];

                    $results = $this->client->search($params);

                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string ]],
                                        ['range' => ['p_created_time' => ['gte' => $inc_dec_from_date, 'lte' => $inc_dec_to_date]]]
                                    ]
                                ]
                            ],
                            'aggs' => [
                                'total_likes' => ['sum' => ['field' => 'p_likes']]
                            ]
                        ]
                    ];

                    $results1 = $this->client->search($params);

                    if ($results["aggregations"]["total_likes"]["value"] > $results1["aggregations"]["total_likes"]["value"]) //increase
                        $result_diff = $results["aggregations"]["total_likes"]["value"] - $results1["aggregations"]["total_likes"]["value"];
                    else
                        $result_diff = $results1["aggregations"]["total_likes"]["value"] - $results["aggregations"]["total_likes"]["value"];

                    if($results["aggregations"]["total_likes"]["value"] > 0)
                        $per_diff = ($result_diff / $results["aggregations"]["total_likes"]["value"]) * 100;
                    else
                        $per_diff = 0;

                    if ($results["aggregations"]["total_likes"]["value"] > $results1["aggregations"]["total_likes"]["value"]) //increase
                        $response = 'increase|'.number_format($per_diff, 2);
                    else
                        $response = 'decrease|'.number_format($per_diff, 2);

                    //Graph calculations for last 5 days
                    for ($i = 1; $i <= 7; $i++)
                    {
                        $subtopic_session_id = \Session::get('current_loaded_subtopic');
                        
                        $d_date = date('Y-m-d', strtotime(date('Y-m-d') . " -" . $i . " day"));
                        
                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            [ 'query_string' => [ 'query' => $topic_query_string.' AND p_created_time:("' . $d_date . '")' ] ]
                                        ]
                                    ]
                                ],
                                'aggs' => [
                                    'total_likes' => ['sum' => ['field' => 'p_likes']]
                                ]
                            ]
                        ];
                        
                        $es_data = $this->client->search($params);
                        $dates_array[] = date('D', strtotime($d_date)).','.$es_data["aggregations"]["total_likes"]["value"];
                    }
                    
                    krsort($dates_array);
                    $abc = '';
                    for($ii=count($dates_array)-1; $ii>=0; $ii--)
                    {
                        $abc .= $dates_array[$ii].'|';
                    }
                    //End graph calculations
                    
                    $response_output = $this->gen_func_obj->format_number_data($results["aggregations"]["total_likes"]["value"]).'|'.$response.'~'.substr($abc, 0, -1).'~'.$results["aggregations"]["total_likes"]["value"];
                    
                    echo $response_output;
                }
                
            }
            else if($request["section"] == 'dashboard_comments')
            {
                $subtopic_session_id = \Session::get('current_loaded_subtopic');
                
                if($request["section"] == 'subtopic_likes')
                {
                    $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
                    $topic_query_string .= ' AND '.$subtopic_query_string;
                    
                }
                
                if(isset($request["manual_filter"]) && $request["manual_filter"] == 'yes') //this is if individual graph filters are set
                {
                    $data_dates = array();
                    $data_counts = array();

                    if($request["filter_type"] == 'likes_today')
                    {
                        $d_date = date('Y-m-d', strtotime(date('Y-m-d')));

                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            [ 'query_string' => [ 'query' => $topic_query_string.' AND p_created_time:("' . $d_date . '") AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")' ] ]
                                        ]
                                    ]
                                ]
                                ,
                                'aggs' => [
                                    'total_likes' => ['sum' => ['field' => 'p_likes']]
                                ]
                            ]
                        ];
                        
                        $es_data = $this->client->search($params);
                        $tot_likes = $es_data["aggregations"]["total_likes"]["value"];

                        $data_dates[] = $d_date;
                        $data_counts[] = $tot_likes;
                    }
                    else if($request["filter_type"] == 'likes_this_week')
                    {
                        for ($i = 0; $i <= 6; $i++)
                        {
                            $d_date = date('Y-m-d', strtotime(date('Y-m-d') . " -" . $i . " day"));

                            $params = [
                                'index' => $this->search_index_name,
                                'type' => 'mytype',
                                'body' => [
                                    'query' => [
                                        'bool' => [
                                            'must' => [
                                                [ 'query_string' => [ 'query' => $topic_query_string.' AND p_created_time:("' . $d_date . '") AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")' ] ]
                                            ]
                                        ]
                                    ]
                                    ,
                                    'aggs' => [
                                        'total_likes' => ['sum' => ['field' => 'p_likes']]
                                    ]
                                ]
                            ];
                            
                            $es_data = $this->client->search($params);
                            $tot_likes = $es_data["aggregations"]["total_likes"]["value"];

                            $data_dates[] = $d_date;
                            $data_counts[] = $tot_likes;
                        }
                    }
                    else if($request["filter_type"] == 'likes_this_month')
                    {
                        $year_month = date("Y-m");
                        $day = date("d");
                        $zero_val = strpos($day, '0');
                        
                        if($zero_val == 0)
                            $limit = str_replace('0', '', $day);
                        else 
                            $limit = $day;

                        for ($i = 0; $i < $limit; $i++)
                        {
                            $d_date = date('Y-m-d', strtotime(date('Y-m-d') . " -" . $i . " day"));

                            $params = [
                                'index' => $this->search_index_name,
                                'type' => 'mytype',
                                'body' => [
                                    'query' => [
                                        'bool' => [
                                            'must' => [
                                                [ 'query_string' => [ 'query' => $topic_query_string.' AND p_created_time:("' . $d_date . '") AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")' ] ]
                                            ]
                                        ]
                                    ]
                                    ,
                                    'aggs' => [
                                        'total_likes' => ['sum' => ['field' => 'p_likes']]
                                    ]
                                ]
                            ];
                            
                            $es_data = $this->client->search($params);
                            $tot_likes = $es_data["aggregations"]["total_likes"]["value"];

                            $data_dates[] = $d_date;
                            $data_counts[] = $tot_likes;
                        }
                    }
                    else if($request["filter_type"] == 'likes_x_days')
                    {
                        if(isset($request["days_num"]) && !empty($request["days_num"]) && is_numeric($request["days_num"]))
                        {
                            for ($i = 1; $i <= $request["days_num"]; $i++)
                            {
                                $d_date = date('Y-m-d', strtotime(date('Y-m-d') . " -" . $i . " day"));

                                $params = [
                                    'index' => $this->search_index_name,
                                    'type' => 'mytype',
                                    'body' => [
                                        'query' => [
                                            'bool' => [
                                                'must' => [
                                                    [ 'query_string' => [ 'query' => $topic_query_string.' AND p_created_time:("' . $d_date . '") AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")' ] ]
                                                ]
                                            ]
                                        ]
                                        ,
                                        'aggs' => [
                                            'total_likes' => ['sum' => ['field' => 'p_likes']]
                                        ]
                                    ]
                                ];
                                
                                $es_data = $this->client->search($params);
                                $tot_likes = $es_data["aggregations"]["total_likes"]["value"];

                                $data_dates[] = $d_date;
                                $data_counts[] = $tot_likes;
                            }
                        }
                        else
                        {
                            $data_dates[] = 'Invalid access days count';
                            $data_counts[] = 'Invalid access days count';
                        }
                    }
                    else if($request["filter_type"] == 'custom_likes_dates')
                    {                        
                        if(isset($request["from_date"]) && !empty($request["from_date"]) && isset($request["to_date"]) && !empty($request["to_date"]))
                        {
                            $from_date = $request["from_date"];
                            $to_date = $request["to_date"];
                            
                            $days_diff = $this->gen_func_obj->date_difference($from_date, $to_date);
                        
                            for ($i = 0; $i <= $days_diff; $i++)
                            {
                                $d_date = date('Y-m-d', strtotime(date($to_date) . " -" . $i . " day"));

                                //$d_date = date('Y-m-d', strtotime(date('Y-m-d') . " -" . $i . " day"));

                                $params = [
                                    'index' => $this->search_index_name,
                                    'type' => 'mytype',
                                    'body' => [
                                        'query' => [
                                            'bool' => [
                                                'must' => [
                                                    [ 'query_string' => [ 'query' => $topic_query_string.' AND p_created_time:("' . $d_date . '") AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")' ] ]
                                                ]
                                            ]
                                        ]
                                        ,
                                        'aggs' => [
                                            'total_likes' => ['sum' => ['field' => 'p_likes']]
                                        ]
                                    ]
                                ];
                                
                                $es_data = $this->client->search($params);
                                $tot_likes = $es_data["aggregations"]["total_likes"]["value"];

                                $data_dates[] = $d_date;
                                $data_counts[] = $tot_likes;
                            }
                        }
                        else
                        {
                            $data_dates[] = 'Invalid custom dates';
                            $data_counts[] = 'Invalid custom dates';
                        }
                    }
                    else
                    {
                        $data_dates[] = 'Invalid access date';
                        $data_counts[] = 'Invalid access count';
                    }

                    return response()->json([
                            'data_dates' => array_reverse($data_dates),
                            'data_counts' => array_reverse($data_counts)
                        ]);
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
                                        ['query_string' => ['query' => $topic_query_string ]],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                    ]
                                ]
                            ],
                            'aggs' => [
                                'total_comments' => ['sum' => ['field' => 'p_comments']]
                            ]
                        ]
                    ];

                    $results = $this->client->search($params);

                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string ]],
                                        ['range' => ['p_created_time' => ['gte' => $inc_dec_from_date, 'lte' => $inc_dec_to_date]]]
                                    ]
                                ]
                            ],
                            'aggs' => [
                                'total_comments' => ['sum' => ['field' => 'p_comments']]
                            ]
                        ]
                    ];

                    $results1 = $this->client->search($params);

                    if ($results["aggregations"]["total_comments"]["value"] > $results1["aggregations"]["total_comments"]["value"]) //increase
                        $result_diff = $results["aggregations"]["total_comments"]["value"] - $results1["aggregations"]["total_comments"]["value"];
                    else
                        $result_diff = $results1["aggregations"]["total_comments"]["value"] - $results["aggregations"]["total_comments"]["value"];

                    if($results["aggregations"]["total_comments"]["value"] > 0)
                        $per_diff = ($result_diff / $results["aggregations"]["total_comments"]["value"]) * 100;
                    else
                        $per_diff = 0;

                    if ($results["aggregations"]["total_comments"]["value"] > $results1["aggregations"]["total_comments"]["value"]) //increase
                        $response = 'increase|'.number_format($per_diff, 2);
                    else
                        $response = 'decrease|'.number_format($per_diff, 2);

                    //Graph calculations for last 5 days
                    for ($i = 1; $i <= 7; $i++)
                    {
                        $subtopic_session_id = \Session::get('current_loaded_subtopic');
                        
                        $d_date = date('Y-m-d', strtotime(date('Y-m-d') . " -" . $i . " day"));
                        
                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            [ 'query_string' => [ 'query' => $topic_query_string.' AND p_created_time:("' . $d_date . '")' ] ]
                                        ]
                                    ]
                                ],
                                'aggs' => [
                                    'total_comments' => ['sum' => ['field' => 'p_comments']]
                                ]
                            ]
                        ];
                        
                        $es_data = $this->client->search($params);
                        $dates_array[] = date('D', strtotime($d_date)).','.$es_data["aggregations"]["total_comments"]["value"];
                    }
                    
                    krsort($dates_array);
                    $abc = '';
                    for($ii=count($dates_array)-1; $ii>=0; $ii--)
                    {
                        $abc .= $dates_array[$ii].'|';
                    }
                    //End graph calculations
                    
                    $response_output = $this->gen_func_obj->format_number_data($results["aggregations"]["total_comments"]["value"]).'|'.$response.'~'.substr($abc, 0, -1).'~'.$results["aggregations"]["total_comments"]["value"];
                    
                    echo $response_output;
                }
                
            }
            else if($request["section"] == 'dashboard_sentiment_chart' || $request["section"] == 'subtopic_sentiment_chart')
            {
                if(isset($request["pdf_report_data"]) && $request["pdf_report_data"] == 'yes')
                {
                    $greater_than_time = $request["from_date"];
                    $less_than_time = $request["to_date"];
                    $subtopic_session_id = $request["stid"];
                }
                else
                {
                    $subtopic_session_id = \Session::get('current_loaded_subtopic');
                }
                
                if($request["section"] == 'subtopic_sentiment_chart')
                {
                    $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
                    $topic_query_string .= ' AND '.$subtopic_query_string;
                }
                
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string ]],
                                    ['match' => ['predicted_sentiment_value' => 'positive']],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];
//print_r($params);
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
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
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
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];

                $results = $this->client->count($params);

                $neu_senti = $results["count"];

                $total_sentiments = $pos_senti + $neg_senti + $neu_senti;
                
                //$response_output = 'Positive,'.number_format(($pos_senti / $total_sentiments) * 100, 2).'|Negative,'.number_format(($neg_senti / $total_sentiments) * 100, 2).'|Neutral,'.number_format(($neu_senti / $total_sentiments) * 100, 2);
                $response_output = trim('Positive,'.$pos_senti.'|Negative,'.$neg_senti.'|Neutral,'.$neu_senti);
                
                echo $response_output;
            }
            else if($request["section"] == 'subtopic_senti_area_graph')
            {
                $date_count_string = '';
                $dates_array = array();
                $pos_dates_array = array();
                $neg_dates_array = array();
                
                $subtopic_session_id = \Session::get('current_loaded_subtopic');
                
                if($request["section"] == 'subtopic_senti_area_graph')
                {
                    $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
                    $topic_query_string .= ' AND '.$subtopic_query_string;
                }
                //echo $topic_query_string;
                if(isset($request["manual_filter"]) && $request["manual_filter"] == 'yes') //this is if individual graph filters are set
                {
                    if($request["filter_type"] == 'custom_dates')
                    {
                        
                        if((isset($request["from_date"]) && !empty($request["from_date"]) && isset($request["to_date"]) && !empty($request["to_date"])) || $request["dash_filters_applied"] == 'yes')
                        {
                            if(isset($request["dash_filters_applied"]) && $request["dash_filters_applied"] == 'yes')
                            {
                                $from_date = $greater_than_time;
                                $to_date = $less_than_time;
                            }
                            else
                            {
                                $from_date = $request["from_date"];
                                $to_date = $request["to_date"];
                            }
                                                        
                            $params = [
                                'index' => $this->search_index_name,
                                'type' => 'mytype',
                                'size' => 0,
                                'docvalue_fields' => array("field" => "p_created_time", "format" => "date_time"),
                                'body' => [
                                    'query' => [
                                        'bool' => [
                                            'must' => [
                                                [ 'query_string' => [ 'query' => $topic_query_string ] ],
                                                [ 'range' => [ 'p_created_time' => [ 'gte' => $greater_than_time, 'lte' => $less_than_time ] ] ]
                                            ]
                                        ]
                                    ],
                                    'aggs' => [
                                        '2' => [
                                            'date_histogram' => [ 
                                                "field" => "p_created_time", "interval" => "1d", "min_doc_count" => 0
                                            ],
                                            'aggs' => [
                                                '3' => [
                                                    'terms' => [ "field" => "predicted_sentiment_value.keyword" ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ];

                            $es_data = $this->client->search($params);

                            $p_str = '';
                            $n_str = '';

                            for($p=0; $p<count($es_data["aggregations"]["2"]["buckets"]); $p++)
                            {
                                $pos_count = 0;
                                $neg_count = 0;

                                for($m=0; $m<count($es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"]); $m++)
                                {
                                    if($es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["key"] == 'positive')
                                        $pos_count = $es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["doc_count"];
                                    else if($es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["key"] == 'negative')
                                        $neg_count = $es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["doc_count"];
                                }

                                $p_str .= date("Y-m-d", strtotime($es_data["aggregations"]["2"]["buckets"][$p]["key_as_string"])).'~'.$pos_count.'|';
                                $n_str .= date("Y-m-d", strtotime($es_data["aggregations"]["2"]["buckets"][$p]["key_as_string"])).'~'.$neg_count.'|';
                            }

                            $dates_array["positive_data"] = substr($p_str, 0, -1);
                            $dates_array["negative_data"] = substr($n_str, 0, -1);
                        }
                        else
                            $dates_array[] = 'Incomplete dates request';
                    }
                    else
                        $dates_array[] = 'Incomplete request';
                }
                else
                {
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'size' => 0,
                        'docvalue_fields' => array("field" => "p_created_time", "format" => "date_time"),
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        [ 'query_string' => [ 'query' => $topic_query_string ] ],
                                        [ 'range' => [ 'p_created_time' => [ 'gte' => $greater_than_time, 'lte' => $less_than_time ] ] ]
                                    ]
                                ]
                            ],
                            'aggs' => [
                                '2' => [
                                    'date_histogram' => [ 
                                        "field" => "p_created_time", "interval" => "1d", "min_doc_count" => 0
                                    ],
                                    'aggs' => [
                                        '3' => [
                                            'terms' => [ "field" => "predicted_sentiment_value.keyword" ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ];

                    $es_data = $this->client->search($params);

                    $p_str = '';
                    $n_str = '';

                    for($p=0; $p<count($es_data["aggregations"]["2"]["buckets"]); $p++)
                    {
                        $pos_count = 0;
                        $neg_count = 0;

                        for($m=0; $m<count($es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"]); $m++)
                        {
                            if($es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["key"] == 'positive')
                                $pos_count = $es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["doc_count"];
                            else if($es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["key"] == 'negative')
                                $neg_count = $es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["doc_count"];
                        }

                        $p_str .= date("Y-m-d", strtotime($es_data["aggregations"]["2"]["buckets"][$p]["key_as_string"])).'~'.$pos_count.'|';
                        $n_str .= date("Y-m-d", strtotime($es_data["aggregations"]["2"]["buckets"][$p]["key_as_string"])).'~'.$neg_count.'|';
                    }

                    $dates_array["positive_data"] = substr($p_str, 0, -1);
                    $dates_array["negative_data"] = substr($n_str, 0, -1);
                }
                
                echo json_encode($dates_array);
            }
            else if($request["section"] == 'subtopic_emo_area_graph')
            {
                $date_count_string = '';
                $dates_array = array();
                $anger_dates_array = array();
                $fear_dates_array = array();
                $happy_dates_array = array();
                $sadness_dates_array = array();
                $surprise_dates_array = array();
                
                $subtopic_session_id = \Session::get('current_loaded_subtopic');
                
                if($request["section"] == 'subtopic_emo_area_graph')
                {
                    $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
                    $topic_query_string .= ' AND '.$subtopic_query_string;
                }
                //echo $topic_query_string;
                if(isset($request["manual_filter"]) && $request["manual_filter"] == 'yes') //this is if individual graph filters are set
                {
                    if($request["filter_type"] == 'custom_dates')
                    {
                        
                        if((isset($request["from_date"]) && !empty($request["from_date"]) && isset($request["to_date"]) && !empty($request["to_date"])) || $request["dash_filters_applied"] == 'yes')
                        {
                            if(isset($request["dash_filters_applied"]) && $request["dash_filters_applied"] == 'yes')
                            {
                                $from_date = $greater_than_time;
                                $to_date = $less_than_time;
                            }
                            else
                            {
                                $from_date = $request["from_date"];
                                $to_date = $request["to_date"];
                            }
                                                        
                            $params = [
                                'index' => $this->search_index_name,
                                'type' => 'mytype',
                                'size' => 0,
                                'docvalue_fields' => array("field" => "p_created_time", "format" => "date_time"),
                                'body' => [
                                    'query' => [
                                        'bool' => [
                                            'must' => [
                                                [ 'query_string' => [ 'query' => $topic_query_string ] ],
                                                [ 'range' => [ 'p_created_time' => [ 'gte' => $greater_than_time, 'lte' => $less_than_time ] ] ]
                                            ]
                                        ]
                                    ],
                                    'aggs' => [
                                        '2' => [
                                            'date_histogram' => [ 
                                                "field" => "p_created_time", "interval" => "1d", "min_doc_count" => 0
                                            ],
                                            'aggs' => [
                                                '3' => [
                                                    'terms' => [ "field" => "emotion_detector.keyword" ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ];

                            $es_data = $this->client->search($params);

                            $anger_str = '';
                            $sadness_str = '';
                            $happy_str = '';
                            $fear_str = '';
                            $surprise_str = '';

                            for($p=0; $p<count($es_data["aggregations"]["2"]["buckets"]); $p++)
                            {
                                $anger_count = 0;
                                $happy_count = 0;
                                $fear_count = 0;
                                $sadness_count = 0;
                                $surprise_count = 0;

                                for($m=0; $m<count($es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"]); $m++)
                                {
                                    if($es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["key"] == 'anger')
                                        $anger_count = $es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["doc_count"];
                                    else if($es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["key"] == 'happy')
                                        $happy_count = $es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["doc_count"];
                                    else if($es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["key"] == 'fear')
                                        $fear_count = $es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["doc_count"];
                                    else if($es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["key"] == 'sadness')
                                        $sadness_count = $es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["doc_count"];
                                    else if($es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["key"] == 'surprise')
                                        $surprise_count = $es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["doc_count"];
                                }

                                $anger_str .= date("Y-m-d", strtotime($es_data["aggregations"]["2"]["buckets"][$p]["key_as_string"])).'~'.$anger_count.'|';
                                $sadness_str .= date("Y-m-d", strtotime($es_data["aggregations"]["2"]["buckets"][$p]["key_as_string"])).'~'.$sadness_count.'|';
                                $happy_str .= date("Y-m-d", strtotime($es_data["aggregations"]["2"]["buckets"][$p]["key_as_string"])).'~'.$happy_count.'|';
                                $fear_str .= date("Y-m-d", strtotime($es_data["aggregations"]["2"]["buckets"][$p]["key_as_string"])).'~'.$fear_count.'|';
                                $surprise_str .= date("Y-m-d", strtotime($es_data["aggregations"]["2"]["buckets"][$p]["key_as_string"])).'~'.$surprise_count.'|';
                            }

                            $dates_array["anger_data"] = substr($anger_str, 0, -1);
                            $dates_array["happy_data"] = substr($happy_str, 0, -1);
                            $dates_array["fear_data"] = substr($fear_str, 0, -1);
                            $dates_array["sadness_data"] = substr($sadness_str, 0, -1);
                            $dates_array["surprise_data"] = substr($surprise_str, 0, -1);
                        }
                        else
                            $dates_array[] = 'Incomplete dates request';
                    }
                    else
                        $dates_array[] = 'Incomplete request';
                }
                else
                {
                    /////////////////////////////////////
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'size' => 0,
                        'docvalue_fields' => array("field" => "p_created_time", "format" => "date_time"),
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        [ 'query_string' => [ 'query' => $topic_query_string ] ],
                                        [ 'range' => [ 'p_created_time' => [ 'gte' => $greater_than_time, 'lte' => $less_than_time ] ] ]
                                    ]
                                ]
                            ],
                            'aggs' => [
                                '2' => [
                                    'date_histogram' => [ 
                                        "field" => "p_created_time", "interval" => "1d", "min_doc_count" => 0
                                    ],
                                    'aggs' => [
                                        '3' => [
                                            'terms' => [ "field" => "emotion_detector.keyword" ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ];

                    $es_data = $this->client->search($params);

                    $anger_str = '';
                    $sadness_str = '';
                    $happy_str = '';
                    $fear_str = '';
                    $surprise_str = '';

                    for($p=0; $p<count($es_data["aggregations"]["2"]["buckets"]); $p++)
                    {
                        $anger_count = 0;
                        $happy_count = 0;
                        $fear_count = 0;
                        $sadness_count = 0;
                        $surprise_count = 0;

                        for($m=0; $m<count($es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"]); $m++)
                        {
                            if($es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["key"] == 'anger')
                                $anger_count = $es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["doc_count"];
                            else if($es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["key"] == 'happy')
                                $happy_count = $es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["doc_count"];
                            else if($es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["key"] == 'fear')
                                $fear_count = $es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["doc_count"];
                            else if($es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["key"] == 'sadness')
                                $sadness_count = $es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["doc_count"];
                            else if($es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["key"] == 'surprise')
                                $surprise_count = $es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["doc_count"];
                        }

                        $anger_str .= date("Y-m-d", strtotime($es_data["aggregations"]["2"]["buckets"][$p]["key_as_string"])).'~'.$anger_count.'|';
                        $sadness_str .= date("Y-m-d", strtotime($es_data["aggregations"]["2"]["buckets"][$p]["key_as_string"])).'~'.$sadness_count.'|';
                        $happy_str .= date("Y-m-d", strtotime($es_data["aggregations"]["2"]["buckets"][$p]["key_as_string"])).'~'.$happy_count.'|';
                        $fear_str .= date("Y-m-d", strtotime($es_data["aggregations"]["2"]["buckets"][$p]["key_as_string"])).'~'.$fear_count.'|';
                        $surprise_str .= date("Y-m-d", strtotime($es_data["aggregations"]["2"]["buckets"][$p]["key_as_string"])).'~'.$surprise_count.'|';
                    }

                    $dates_array["anger_data"] = substr($anger_str, 0, -1);
                    $dates_array["happy_data"] = substr($happy_str, 0, -1);
                    $dates_array["fear_data"] = substr($fear_str, 0, -1);
                    $dates_array["sadness_data"] = substr($sadness_str, 0, -1);
                    $dates_array["surprise_data"] = substr($surprise_str, 0, -1);
                    ////////////////////////////////////
                    
                }
                echo json_encode($dates_array);
            }
            else if($request["section"] == 'subtopic_emotions_radar_chart')
            {
                $date_count_string = '';
                $dates_array = array();
                $anger_dates_array = array();
                $fear_dates_array = array();
                $happy_dates_array = array();
                $sadness_dates_array = array();
                $surprise_dates_array = array();
                
                $subtopic_session_id = \Session::get('current_loaded_subtopic');
                
                if($request["section"] == 'subtopic_emotions_radar_chart')
                {
                    $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
                    $topic_query_string .= ' AND '.$subtopic_query_string;
                }
                //echo $topic_query_string;
                if(isset($request["manual_filter"]) && $request["manual_filter"] == 'yes') //this is if individual graph filters are set
                {
                    if($request["filter_type"] == 'custom_dates')
                    {
                        
                        if((isset($request["from_date"]) && !empty($request["from_date"]) && isset($request["to_date"]) && !empty($request["to_date"])) || $request["dash_filters_applied"] == 'yes')
                        {
                            if(isset($request["dash_filters_applied"]) && $request["dash_filters_applied"] == 'yes')
                            {
                                $from_date = $greater_than_time;
                                $to_date = $less_than_time;
                            }
                            else
                            {
                                $from_date = $request["from_date"];
                                $to_date = $request["to_date"];
                            }
                                                        
                            $days_diff = $this->gen_func_obj->date_difference($from_date, $to_date);
                        
                            for ($i = 0; $i <= $days_diff; $i++)
                            {
                                $d_date = date('Y-m-d', strtotime(date($to_date) . " -" . $i . " day"));

                                $params = [
                                    'index' => $this->search_index_name,
                                    'type' => 'mytype',
                                    'body' => [
                                        'query' => [
                                            'bool' => [
                                                'must' => [
                                                    [ 'query_string' => [ 'query' => $topic_query_string.' AND p_created_time:("' . $d_date . '")' ] ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ];

                                $es_data = $this->client->count($params);
                                $dates_array[] = $d_date.','.$es_data["count"];
                            }
                        }
                        else
                            $dates_array[] = 'Incomplete dates request';
                    }
                    else
                        $dates_array[] = 'Incomplete request';
                }
                else
                {
                    /////////////////////////////////////
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'size' => 0,
                        'docvalue_fields' => array("field" => "p_created_time", "format" => "date_time"),
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        [ 'query_string' => [ 'query' => $topic_query_string ] ],
                                        [ 'range' => [ 'p_created_time' => [ 'gte' => $greater_than_time, 'lte' => $less_than_time ] ] ]
                                    ]
                                ]
                            ],
                            'aggs' => [
                                '2' => [
                                    'date_histogram' => [ 
                                        "field" => "p_created_time", "interval" => "20d", "min_doc_count" => 0
                                    ],
                                    'aggs' => [
                                        '3' => [
                                            'terms' => [ "field" => "emotion_detector.keyword" ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ];

                    $es_data = $this->client->search($params);

                    $anger_str = '';
                    $sadness_str = '';
                    $happy_str = '';
                    $fear_str = '';
                    $surprise_str = '';

                    for($p=0; $p<count($es_data["aggregations"]["2"]["buckets"]); $p++)
                    {
                        $anger_count = 0;
                        $happy_count = 0;
                        $fear_count = 0;
                        $sadness_count = 0;
                        $surprise_count = 0;

                        for($m=0; $m<count($es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"]); $m++)
                        {
                            if($es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["key"] == 'anger')
                                $anger_count = $es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["doc_count"];
                            else if($es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["key"] == 'happy')
                                $happy_count = $es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["doc_count"];
                            else if($es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["key"] == 'fear')
                                $fear_count = $es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["doc_count"];
                            else if($es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["key"] == 'sadness')
                                $sadness_count = $es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["doc_count"];
                            else if($es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["key"] == 'surprise')
                                $surprise_count = $es_data["aggregations"]["2"]["buckets"][$p]["3"]["buckets"][$m]["doc_count"];
                        }

                        $anger_str .= $anger_count.',';
                        $sadness_str .= $sadness_count.',';
                        $happy_str .= $happy_count.',';
                        $fear_str .= $fear_count.',';
                        $surprise_str .= $surprise_count.',';
                    }

                    $dates_array["anger_data"] = 'Anger|['.substr($anger_str, 0, -1).']';
                    $dates_array["fear_data"] = 'Fear|['.substr($fear_str, 0, -1).']';
                    $dates_array["happy_data"] = 'Happy|['.substr($happy_str, 0, -1).']';
                    $dates_array["sadness_data"] = 'Sadness|['.substr($sadness_str, 0, -1).']';
                    $dates_array["surprise_data"] = 'Surprise|['.substr($surprise_str, 0, -1).']';
                    ////////////////////////////////////
                    
                }
                echo json_encode($dates_array);
            }
            else if($request["section"] == 'maintopic_emo_bar_graph' || $request["section"] == 'subtopic_emo_bar_graph')
            {
                $date_count_string = '';
                $dates_array = array();
                $pos_dates_array = array();
                $neg_dates_array = array();
                
                $subtopic_session_id = \Session::get('current_loaded_subtopic');
                
                if($request["section"] == 'subtopic_emo_bar_graph')
                {
                    $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
                    $topic_query_string .= ' AND '.$subtopic_query_string;
                }
                //echo $topic_query_string;
                if(isset($request["manual_filter"]) && $request["manual_filter"] == 'yes') //this is if individual graph filters are set
                {
                    if($request["filter_type"] == 'custom_dates')
                    {
                        
                        if((isset($request["from_date"]) && !empty($request["from_date"]) && isset($request["to_date"]) && !empty($request["to_date"])) || $request["dash_filters_applied"] == 'yes')
                        {
                            /*if(isset($request["dash_filters_applied"]) && $request["dash_filters_applied"] == 'yes')
                            {
                                $from_date = $greater_than_time;
                                $to_date = $less_than_time;
                            }
                            else
                            {
                                $from_date = $request["from_date"];
                                $to_date = $request["to_date"];
                            }
                                                        
                            $days_diff = $this->gen_func_obj->date_difference($from_date, $to_date);
                        
                            for ($i = 0; $i <= $days_diff; $i++)
                            {
                                $d_date = date('Y-m-d', strtotime(date($to_date) . " -" . $i . " day"));

                                $params = [
                                    'index' => $this->search_index_name,
                                    'type' => 'mytype',
                                    'body' => [
                                        'query' => [
                                            'bool' => [
                                                'must' => [
                                                    [ 'query_string' => [ 'query' => $topic_query_string.' AND p_created_time:("' . $d_date . '")' ] ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ];

                                $es_data = $this->client->count($params);
                                $dates_array[] = $d_date.','.$es_data["count"];
                            }*/
                            $params = [
                                'index' => $this->search_index_name,
                                'type' => 'mytype',
                                'body' => [
                                    'query' => [
                                        'bool' => [
                                            'must' => [
                                                ['query_string' => ['query' => $topic_query_string ]],
                                                ['match' => ['emotion_detector' => 'anger']],
                                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                            ]
                                        ]
                                    ]
                                ]
                            ];

                            $results = $this->client->count($params);

                            $anger_count = $results["count"];

                            $params = [
                                'index' => $this->search_index_name,
                                'type' => 'mytype',
                                'body' => [
                                    'query' => [
                                        'bool' => [
                                            'must' => [
                                                ['query_string' => ['query' => $topic_query_string ]],
                                                ['match' => ['emotion_detector' => 'surprise']],
                                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                            ]
                                        ]
                                    ]
                                ]
                            ];

                            $results = $this->client->count($params);

                            $surprise_count = $results["count"];

                            $params = [
                                'index' => $this->search_index_name,
                                'type' => 'mytype',
                                'body' => [
                                    'query' => [
                                        'bool' => [
                                            'must' => [
                                                ['query_string' => ['query' => $topic_query_string ]],
                                                ['match' => ['emotion_detector' => 'happy']],
                                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                            ]
                                        ]
                                    ]
                                ]
                            ];

                            $results = $this->client->count($params);

                            $happy_count = $results["count"];

                            $params = [
                                'index' => $this->search_index_name,
                                'type' => 'mytype',
                                'body' => [
                                    'query' => [
                                        'bool' => [
                                            'must' => [
                                                ['query_string' => ['query' => $topic_query_string ]],
                                                ['match' => ['emotion_detector' => 'sadness']],
                                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                            ]
                                        ]
                                    ]
                                ]
                            ];

                            $results = $this->client->count($params);

                            $sadness_count = $results["count"];

                            $params = [
                                'index' => $this->search_index_name,
                                'type' => 'mytype',
                                'body' => [
                                    'query' => [
                                        'bool' => [
                                            'must' => [
                                                ['query_string' => ['query' => $topic_query_string ]],
                                                ['match' => ['emotion_detector' => 'fear']],
                                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                            ]
                                        ]
                                    ]
                                ]
                            ];

                            $results = $this->client->count($params);

                            $fear_count = $results["count"];
                        }
                        else
                            $dates_array[] = 'Incomplete dates request';
                    }
                    else
                        $dates_array[] = 'Incomplete request';
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
                                        ['query_string' => ['query' => $topic_query_string ]],
                                        ['match' => ['emotion_detector' => 'anger']],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                    ]
                                ]
                            ]
                        ]
                    ];
    
                    $results = $this->client->count($params);

                    $anger_count = $results["count"];
                    
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string ]],
                                        ['match' => ['emotion_detector' => 'surprise']],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                    ]
                                ]
                            ]
                        ]
                    ];
    
                    $results = $this->client->count($params);

                    $surprise_count = $results["count"];
                    
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string ]],
                                        ['match' => ['emotion_detector' => 'happy']],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                    ]
                                ]
                            ]
                        ]
                    ];
    
                    $results = $this->client->count($params);

                    $happy_count = $results["count"];
                    
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string ]],
                                        ['match' => ['emotion_detector' => 'sadness']],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                    ]
                                ]
                            ]
                        ]
                    ];
    
                    $results = $this->client->count($params);

                    $sadness_count = $results["count"];
                    
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string ]],
                                        ['match' => ['emotion_detector' => 'fear']],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                    ]
                                ]
                            ]
                        ]
                    ];
    
                    $results = $this->client->count($params);

                    $fear_count = $results["count"];
                }
                
                echo $anger_count.'|'.$fear_count.'|'.$happy_count.'|'.$sadness_count.'|'.$surprise_count;
            }
            else if($request["section"] == 'maintopic_users_graph')
            {
                if(isset($request["pdf_report_data"]) && $request["pdf_report_data"] == 'yes')
                {
                    $greater_than_time = $request["from_date"];
                    $less_than_time = $request["to_date"];
                }
                
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string ]], // . ' AND account_type:("0" OR "2") AND NOT account_type:("1")'
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]],
                                    ['range' => ['u_followers' => ['gte' => 0, 'lte' => 1000]]]
                                ]
                            ]
                        ]
                    ]
                ];

                $results = $this->client->count($params);

                $u_normal = $results["count"];

                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string]],
                                    ['match' => ['account_type' => '1']],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];

                $results = $this->client->count($params);

                $u_unidentified = $results["count"];
                
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string]], // . ' AND account_type:("0" OR "2") AND NOT account_type:("1")'
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]],
                                    ['range' => ['u_followers' => ['gte' => 1000]]]
                                ]
                            ]
                        ]
                    ]
                ];

                $results = $this->client->count($params);

                $u_influencer = $results["count"];
                
                return response()->json([
                    'normal_users_mentions' => $u_normal,
                    'inf_users_mentions' => $u_influencer
                    //'unidentified_users_mentions' => $u_unidentified
                ]);
            }
            else if($request["section"] == 'sources_counts' || $request["section"] == 'sources_counts_subtopic')
            {
                $subtopic_session_id = \Session::get('current_loaded_subtopic');
                
                if($request["section"] == 'sources_counts_subtopic')
                {
                    $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
                    $topic_query_string .= ' AND '.$subtopic_query_string;
                }
                
                $blog_counts = 0; $news_counts = 0; $twitter_count = 0; $youtube_count = 0; $linkedin_count = 0; $tumblr_count = 0; $facebook_count = 0; $reddit_count = 0; $web_count = 0; $pinterest_count = 0; $instagram_count = 0; $googlemaps_count = 0; $tripadvisor_count = 0; $tiktok_count = 0;
               
                //videos
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string . ' AND source:("Youtube" OR "Vimeo")']],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];
                //print_r($params);							
                $results = $this->client->count($params);
                $youtube_count = $results["count"];
                
                //news sources
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string . ' AND source:("FakeNews" OR "News")']],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];
                							
                $results = $this->client->count($params);
                $news_counts = $results["count"];
                
                //twitter
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string . ' AND source:("Twitter")']],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];
                							
                $results = $this->client->count($params);
                $twitter_count = $results["count"];
                
                //Pinterest
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string . ' AND source:("Pinterest")']],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];
                							
                $results = $this->client->count($params);
                $pinterest_count = $results["count"];
                
                //Instagram
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string . ' AND source:("Instagram")']],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];
                							
                $results = $this->client->count($params);
                $instagram_count = $results["count"];
                
                //Blogs
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string . ' AND source:("Blogs")']],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];
                							
                $results = $this->client->count($params);
                $blog_counts = $results["count"];
                                
                //Reddit
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string . ' AND source:("Reddit")']],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];
                							
                $results = $this->client->count($params);
                $reddit_count = $results["count"];
                
                //Tumblr
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string . ' AND source:("Tumblr")']],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];
                							
                $results = $this->client->count($params);
                $tumblr_count = $results["count"];
                
                //Facebook
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string . ' AND source:("Facebook")']],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];
                							
                $results = $this->client->count($params);
                $facebook_count = $results["count"];
                
                //Printmedia: This is now eligible for all company accounts
                $pm_query_str = str_replace('p_message_text', 'p_message', $topic_query_string);
                $params = [
                    'index' => $this->printmedia_search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    [ 'query_string' => [ 'query' => $pm_query_str ] ],
                                    [ 'range' => [ 'p_created_time' => [ 'gte' => $greater_than_time, 'lte' => $less_than_time ] ] ]
                                ]
                            ]
                        ]
                    ]
                ];

                //Log::info("Here2");
                //Log::info($params);
                
                $results = $this->client->count($params);
                
                if($results["count"] > 0)
                    $response_output .= 'Printmedia,'.$results["count"].'|';
                
                //Web
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string . ' AND source:("Web")']],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];
                							
                $results = $this->client->count($params);
                $web_count = $results["count"]+$blog_counts+$news_counts;
                
                //GoogleMaps
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string . ' AND source:("GoogleMaps")']],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];
                							
                $results = $this->client->count($params);
                $googlemaps_count = $results["count"];
                
                //Tripadvisor
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string . ' AND source:("Tripadvisor")']],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];
                							
                $results = $this->client->count($params);
                $tripadvisor_count = $results["count"];
                
                //Linkedin
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string . ' AND source:("Linkedin")']],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];
                							
                $results = $this->client->count($params);
                $linkedin_count = $results["count"];
                
                //Tiktok
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string . ' AND source:("Tiktok")']],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];
                							
                $results = $this->client->count($params);
                $tiktok_count = $results["count"];
                
                $total_sources_count = $twitter_count + $youtube_count + $linkedin_count + $tumblr_count + $facebook_count + $reddit_count + $web_count + $pinterest_count + $instagram_count + $googlemaps_count + $tripadvisor_count + $tiktok_count;
                
                if($youtube_count > 0)
                {
                    $response_output .= 'YouTube,'.$youtube_count.','. number_format(($youtube_count/$total_sources_count)*100,2).'|';
                }
                
                if($twitter_count > 0)
                {   
                    $response_output .= 'Twitter,'.$twitter_count.','. number_format(($twitter_count/$total_sources_count)*100,2).'|';
                }
                
                if($pinterest_count > 0)
                {
                    $response_output .= 'Pinterest,'.$pinterest_count.','. number_format(($pinterest_count/$total_sources_count)*100,2).'|';
                }
                
                if($instagram_count > 0)
                {   
                    $response_output .= 'Instagram,'.$instagram_count.','. number_format(($instagram_count/$total_sources_count)*100,2).'|';
                }
                
                if($reddit_count > 0)
                {
                    $response_output .= 'Reddit,'.$reddit_count.','. number_format(($reddit_count/$total_sources_count)*100,2).'|';
                }
                
                if($tumblr_count > 0)
                {
                    $response_output .= 'Tumblr,'.$tumblr_count.','. number_format(($tumblr_count/$total_sources_count)*100,2).'|';
                }
                
                if($facebook_count > 0)
                {                    
                    $response_output .= 'Facebook,'.$facebook_count.','. number_format(($facebook_count/$total_sources_count)*100,2).'|';
                }
                
                if($web_count > 0)
                {
                    $response_output .= 'Web,'.$web_count.','. number_format(($web_count/$total_sources_count)*100,2).'|';
                }
                
                if($googlemaps_count > 0)
                {
                    $response_output .= 'GoogleMaps,'.$googlemaps_count.','. number_format(($googlemaps_count/$total_sources_count)*100,2).'|';
                }
                
                if($tripadvisor_count > 0)
                {
                    $response_output .= 'Tripadvisor,'.$tripadvisor_count.','. number_format(($tripadvisor_count/$total_sources_count)*100,2).'|';
                }
                
                if($linkedin_count > 0)
                {
                    $response_output .= 'Linkedin,'.$linkedin_count.','. number_format(($linkedin_count/$total_sources_count)*100,2).'|';
                }
                
                if($tiktok_count > 0)
                {
                    $response_output .= 'Tiktok,'.$tiktok_count.','. number_format(($tiktok_count/$total_sources_count)*100,2).'|';
                }
                
                //Add reviews data if present. Add customer ids in array
                $reviews_customer_array = array("292", "309", "310", "312", "412", "420"); //292 Sohar bank - 309 GDFRA - DCT Abudhabi - Al Shami - NCEMA
                $reviews_topic_ids_array = array("2325", "2388", "2391", "2401", "2416", "2443");
                $reviews_source_array = array("GooglePlayStore", "GoogleMyBusiness", "AppleAppStore", "HuaweiAppGallery", "Glassdoor", "Zomato", "Talabat");
                
                if(in_array($this->cus_obj->get_parent_account_id(), $reviews_customer_array) && (in_array($loaded_topic_id, $reviews_topic_ids_array) || $request["section"] == 'sources_counts_subtopic'))
                {
                    if(!is_null($this->cus_obj->get_customer_review_elastic_id()) && !empty($this->cus_obj->get_customer_review_elastic_id()))
                    {
                        $rquery = '';
                        $proceed_further = true;
                        
                        if($request["section"] == 'sources_counts_subtopic')
                        {
                            $rquery = 'p_message_text:('.$this->subtopic_obj->get_subtopic_keywords_es($subtopic_session_id).') AND ';
                            if($this->subtopic_obj->get_subtopic_parent($subtopic_session_id) != 2325)
                                $proceed_further = false;
                        }
                            
                        if($proceed_further == true)
                        {
                            for($x=0; $x<count($reviews_source_array); $x++)
                            {
                                if($loaded_topic_id == 2388 && $reviews_source_array[$x] == 'GooglePlayStore') //Not to show GooglePlayStore reviews to GDFRA topic
                                    continue;
                                
                                $params = [
                                    'index' => $this->search_index_name,
                                    'type' => 'mytype',
                                    'body' => [
                                        'query' => [
                                            'bool' => [
                                                'must' => [
                                                    ['query_string' => ['query' => $rquery.'source:("'.$reviews_source_array[$x].'") AND manual_entry_type:("review") AND review_customer:("'.$this->cus_obj->get_customer_review_elastic_id().'")']],
                                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                                ]
                                            ]
                                        ]
                                    ]
                                ];
                                //dd($params);

                                $results = $this->client->count($params);

                                if($results["count"] > 0)
                                    $response_output .= $reviews_source_array[$x].','.$results["count"].'|';
                            }
                        }
                        
                    }
                }
                
                
                $response_output = substr($response_output, 0, -1);
                
                echo $response_output;
            }
            else if($request["section"] == 'influencers_list' || $request["section"] == 'influencers_list_st')
            {
                $followers_from = 0;
                $followers_to = 0;

                if ($request["inf_type"] == 'nano')
                {
                    $followers_from = 1000;
                    $followers_to = 10000;
                }
                else if ($request["inf_type"] == 'micro')
                {
                    $followers_from = 10000;
                    $followers_to = 50000;
                }
                else if ($request["inf_type"] == 'midtier')
                {
                    $followers_from = 50000;
                    $followers_to = 500000;
                }
                else if ($request["inf_type"] == 'macro')
                {
                    $followers_from = 500000;
                    $followers_to = 1000000;
                }
                else if ($request["inf_type"] == 'mega')
                {
                    $followers_from = 1000000;
                    $followers_to = 5000000;
                }
                else if ($request["inf_type"] == 'celebrity')
                {
                    $followers_from = 5000000;
                    $followers_to = 500000000;
                }
                else
                {
                    $followers_from = 1000;
                    $followers_to = 10000;
                }
                
                if($request["section"] == 'influencers_list_st')
                {
                    $subtopic_session_id = \Session::get('current_loaded_subtopic');
                    $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
                    $topic_query_string .= ' AND '.$subtopic_query_string;
                }

                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string ]],
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
                            'group_by_user' => ['terms' => ['field' => 'u_source.keyword', 'size' => 10, 'order' => ['followers_count' => 'desc']],
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
                        
                        if ($user_source == 'Twitter') //bx bxl-twitter
                            $source_icon = '<i class="fa-brands fa-x-twitter mr-25 align-middle" title="Twitter" style="font-size: 35px; color: #000000 !important;"></i>';
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
                //array_multisort(array_column($data_array, 'followers'), SORT_DESC, $data_array);
                
                header('Content-Type: application/json');
                echo json_encode($data_array);
            }
            else if($request["section"] == 'most_active_users')
            {
                if(!empty($request["records_num"]))
                    $records_num = $request["records_num"];
                else
                    $records_num = 26;
                
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'from' => '0',
                    'size' => '0',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string . ' AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Instagram" OR "Facebook")' ]],
                                    ['exists' => ['field' => 'u_profile_photo']],
                                    ['exists' => ['field' => 'u_followers']],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ],
                                'must_not' => [
                                    ['term' => ['u_profile_photo.keyword' => '']]
                                ]
                            ]
                        ],
                        'aggs' => [
                            'group_by_user' => ['terms' => ['field' => 'u_source.keyword', 'size' => $records_num],
                                'aggs' => [
                                    'grouped_results' => [
                                        'top_hits' => ['size' => 1, '_source' => ['include' => ['u_fullname', 'u_profile_photo', 'u_date_joined', 'u_country', 'u_followers', 'source', 'u_source']],
                                            'sort' => ['p_created_time' => ['order' => 'desc']]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];

                $results = $this->client->search($params);

                $n = 1;
                $j = 0;

                $u_html = '';

                for ($i = 0; $i < count($results["aggregations"]["group_by_user"]["buckets"]); $i ++)
                {
                    if (!empty($results["aggregations"]["group_by_user"]["buckets"][$i]["key"]))
                    {
                        $bg_color = 'transparent';
                        
                        $user_source = $results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["source"];
                        
                        if ($user_source == 'Twitter')
                            $source_icon = '<i class="fa-brands fa-x-twitter mr-25 align-middle" title="Twitter" style="font-size: 20px; color: #000000 !important;"></i>';
                        else if ($user_source == 'Youtube')
                            $source_icon = '<i class="bx bxl-youtube mr-25 align-middle" title="YouTube" style="font-size: 20px; color: #cd201f !important;"></i>';
                        else if ($user_source == 'Linkedin')
                            $source_icon = '<i class="bx bxl-linkedin mr-25 align-middle" title="Linkedin" style="font-size: 20px; color: #365d98 !important;"></i>';
                        else if ($user_source == 'Facebook')
                            $source_icon = '<i class="bx bxl-facebook-square mr-25 align-middle" title="Facebook" style="font-size: 20px; color: #365d98 !important;"></i>';
                        else if ($user_source == 'Pinterest')
                            $source_icon = '<i class="bx bxl-pinterest mr-25 align-middle" title="Pinterest" style="font-size: 20px; color: #bd081c !important;"></i>';
                        else if ($user_source == 'Instagram')
                            $source_icon = '<i class="bx bxl-instagram mr-25 align-middle" title="Instagram" style="font-size: 20px; color: #e4405f !important;"></i>';
                        else if ($user_source == 'khaleej_times' || $user_source == 'Omanobserver' || $user_source == 'Time of oman' || $user_source == 'Blogs')
                            $source_icon = '<i class="bx bxs-book mr-25 align-middle" title="Blog" style="font-size: 20px; color: #f57d00 !important;"></i>';
                        else if ($user_source == 'Reddit')
                            $source_icon = '<i class="bx bxl-reddit mr-25 align-middle" title="Reddit" style="font-size: 20px; color: #ff4301 !important;"></i>';
                        else if ($user_source == 'FakeNews' || $user_source == 'News')
                            $source_icon = '<i class="bx bx-news mr-25 align-middle" title="News" style="font-size: 20px; color: #77BD9D !important;"></i>';
                        else if ($user_source == 'Tumblr')
                            $source_icon = '<i class="bx bxl-tumblr mr-25 align-middle" title="Tumblr" style="font-size: 20px; color: #34526f !important;"></i>';
                        else if ($user_source == 'Vimeo')
                            $source_icon = '<i class="bx bxl-vimeo mr-25 align-middle" title="Vimeo" style="font-size: 20px; color: #86c9ef !important;"></i>';
                        else if ($user_source == 'Web' || $user_source == 'DeepWeb')
                            $source_icon = '<i class="bx bx-globe mr-25 align-middle" title="Web" style="font-size: 20px; color: #FF7D02 !important;"></i>';
                        else
                            $source_icon = '';
                        
                        if(isset($results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_country"]))
                            $flag_image = '<img src="https://dashboard.datalyticx.ai/images/flags/4x3/'.$this->gen_func_obj->get_country_flag($results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_country"]). '" width="30">';
                        else
                            $flag_image = '&nbsp;';

                        $u_html .= '<div style="background: ' . $bg_color . '; width: 50%; float: left; margin-left:0px; padding: 7px 0px 7px 0px; font-size: 15px;">';
                        $u_html .= '<table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="3%">&nbsp;&nbsp;&nbsp;' . $n . '.</td>
                                    <td width="10%" style="text-align: center;"><div><img alt="" src="' . $results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_profile_photo"] . '" style="width: 25px; height: 25px;"></div></td>
                                    <td width="34%" style="font-size:1.1rem;">'.strip_tags($results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_fullname"]) . '</td>
                                    <td width="10%" style="text-align: center;"><a href="'.$results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_source"].'" target="_blank">'.$source_icon.'</a></td>
                                    <td width="12%">'.$flag_image.'</td>
                                    <td width="15%" style="text-align: right; font-size:1.1rem;">' . $this->gen_func_obj->format_number_data($results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_followers"]) . '<p style="color:#cccccc; font-size: 13px;">Followers</p></td>
                                    <td width="16%" style="text-align: right; padding-right: 15px;">' . $this->gen_func_obj->format_number_data($results["aggregations"]["group_by_user"]["buckets"][$i]["doc_count"]) . '<p style="color:#cccccc; font-size: 12px;">Posts</p></td>
                                </tr>
                            </table>';

                        $u_html .= '</div>';

                        $n = $n + 1;

                        if ($j == 3)
                            $j = 0;
                        else
                            $j = $j + 1;
                    }
                }

                echo $u_html;
            }
            else if($request["section"] == 'dashboard_heatmap')
            {
                $key_hash_array = array();
                $heatmap_data = array();
                $heat_data = array();
                $kk = 0;
                $heighest_range = 0;
                
                $key_hash = $this->topic_obj->get_topic_hash_keywords(\Session::get('current_loaded_project'));
                
                $keywords = explode(",", $key_hash[0]->topic_keywords);
                
                if($keywords[0] != "")
                {
                    for($i=0; $i<count($keywords); $i++)
                    {
                        //if(!is_null(trim($keywords[$i])) && !empty(trim($keywords[$i])))
                            $key_hash_array[] = trim($keywords[$i]);
                    }
                }                
                
                $hash_tags = explode("|", $key_hash[0]->topic_hash_tags);
                
                if($hash_tags[0] != "")
                {
                    for($i=0; $i<count($hash_tags); $i++)
                    {
                        //if(!empty(trim($keywords[$i])))
                            $key_hash_array[] = trim($hash_tags[$i]);
                    }
                }                
                
                for($i=0; $i<count($key_hash_array); $i++)
                {
                    $heatmap_data[$kk][0] = $key_hash_array[$i];
                    $counts_data = '';
                    $heat_data[$i]["name"] = $key_hash_array[$i];
                    //For one keyword/hashtag calculate data for last 30 days
                    for($j=1; $j<=30; $j++)
                    {
                        //if($topic_session == 2076)
                            //$day_date = date('2021-08-30', strtotime("-".$j." day"));
                        //else
                            $day_date = date('Y-m-d', strtotime("-".$j." day"));
                        
                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            [ 'query_string' => [ 'query' => ' p_message_text:("'.$key_hash_array[$i].'") AND p_created_time:("' . $day_date . '")' ] ]
                                        ]
                                    ]
                                ]
                            ]
                        ];

                        $results = $this->client->count($params);
                        $counts_data .= $results["count"].',';
                        
                        
                        $heat_data[$i]["data"][] = $results["count"];
                        
                        
                        //Set heighest range
                        if($results["count"] > $heighest_range)
                            $heighest_range = $results["count"];
                    }
                    
                    $heatmap_data[$kk][1] = substr($counts_data, 0, -1);
                    $kk++;
                }
                
                $response_array["heatmap_data"] = $heat_data;
                $response_array["upper_limit"] = $heighest_range;
                
                //set graph height dynamically
                if(count($key_hash_array) <= 5)
                    $graph_height = 300;
                else if(count($key_hash_array) > 5 && count($key_hash_array) <= 10)
                    $graph_height = 400;
                else if(count($key_hash_array) > 10 && count($key_hash_array) <= 20)
                    $graph_height = 500;
                else if(count($key_hash_array) > 20 && count($key_hash_array) <= 30)
                    $graph_height = 600;
                else if(count($key_hash_array) > 30 && count($key_hash_array) <= 40)
                    $graph_height = 700;
                else if(count($key_hash_array) > 40 && count($key_hash_array) <= 50)
                    $graph_height = 800;
                else if(count($key_hash_array) > 50 && count($key_hash_array) <= 60)
                    $graph_height = 900;
                else if(count($key_hash_array) > 60)
                    $graph_height = 1000;
                
                $response_array["graph_height"] = $graph_height;
                
                echo json_encode($response_array);
                
                //Below logic is for last 6 months mentions count for a main topic
                /*$month = '';
                $year = '';
                $month_name = '';
                $days_in_month = 0;
                $heatmap_data = array();
                $month_names = [];
                $month_data = [];
                $heat_data = [];
                $heighest_range = 0;
                
                $j=0;
                $tt = 0;
                //Get last 6 months names and mentions
                for ($i=1; $i<=6; $i++) 
                {
                    $month = date('m', strtotime("-".$i." month"));
                    $year = date('Y', strtotime("-".$i." month"));
                    $month_name = date('F', strtotime("-".$i." month"));
                    $heatmap_data[$j][0] = $month_name;
                    $month_names[] = $month_name;
                    
                    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                    
                    $heat_data[$i-1]["name"] = $month_name;
                    
                    $counts_data = '';
                    
                    for($k=1; $k<=$days_in_month; $k++)
                    {
                        if($k<10)
                            $day = '0'.$k;
                        else
                            $day = $k;
                        
                        $greater_than_time = $year.'-'.$month.'-'.$day;
                        $less_than_time = $year.'-'.$month.'-'.$day;
                        
                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            ['query_string' => ['query' => $topic_query_string ]],
                                            ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                        ]
                                    ]
                                ]
                            ]
                        ];

                        $results = $this->client->count($params);
                        
                        $counts_data .= $results["count"].',';
                        
                        $month_data[]["data"] = $results["count"];
                        if($k==1)
                            $tt = 0;
                        $heat_data[$i-1]["data"][] = $results["count"];
                        $tt = $tt + 1;
                        
                        //Set heighest range
                        if($results["count"] > $heighest_range)
                            $heighest_range = $results["count"];
                    }
                    
                    $heatmap_data[$j][1] = substr($counts_data, 0, -1);
                    $j++;
                }
                
                $response_array["heatmap_data"] = $heat_data;
                $response_array["upper_limit"] = $heighest_range;
                
                echo json_encode($response_array);*/
            }
            else if($request["section"] == 'subtopic_mentions')
            {
                $subtopic_session_id = \Session::get('current_loaded_subtopic');
                $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
                
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string . ' AND '.$subtopic_query_string ]],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];

                $results = $this->client->count($params);
                $tot_mentions = $results["count"];
                
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string . ' AND '.$subtopic_query_string ]],
                                    ['range' => ['p_created_time' => ['gte' => $inc_dec_from_date, 'lte' => $inc_dec_to_date]]]
                                ]
                            ]
                        ]
                    ]
                ];

                $results1 = $this->client->count($params);
                $tot_mentions1 = $results1["count"];

                if ($tot_mentions > $tot_mentions1) //increase
                    $result_diff = $tot_mentions - $tot_mentions1;
                else
                    $result_diff = $tot_mentions1 - $tot_mentions;

                if($tot_mentions > 0)
                    $per_diff = ($result_diff / $tot_mentions) * 100;
                else
                    $per_diff = 0;

                if ($tot_mentions > $tot_mentions1) //increase
                {
                    $response = 'increase|' . number_format($per_diff, 2);
                }
                else
                    $response = 'decrease|'.number_format($per_diff, 2);
                
                //Graph calculations for last 5 days
                for ($i = 1; $i <= 7; $i++)
                {
                    $d_date = date('Y-m-d', strtotime(date('Y-m-d') . " -" . $i . " day"));
                    
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        [ 'query_string' => [ 'query' => $topic_query_string.' AND '.$subtopic_query_string.' AND p_created_time:("' . $d_date . '")' ] ]
                                    ]
                                ]
                            ]
                        ]
                    ];
                    
                    $es_data = $this->client->count($params);
                    $total_mentions = $es_data["count"];
                    $dates_array[] = date('D', strtotime($d_date)).','.$total_mentions;
                }
                
                krsort($dates_array);
                $abc = '';
                for($ii=count($dates_array)-1; $ii>=0; $ii--)
                {
                    $abc .= $dates_array[$ii].'|';
                }
                //End graph calculations
                
                $response_output = $this->gen_func_obj->format_number_data($tot_mentions).'|'.$response.'~'.substr($abc, 0, -1).'~'.$tot_mentions;
                
                echo $response_output;
            }
            else if($request["section"] == 'get_posts_html_data') //This is being used to fetch json saved query for posts listing
            {
                $posts_limit = 50;
                $posts_html = '';
                $twitter_dm = false;
                $ca_senti_emo = '';
                
                if($request["source_handle"] == 'News')
                    $topic_query_string .= ' AND source:("FakeNews" OR "News")';
                else if($request["source_handle"] == 'Youtube' || $request["source_handle"] == 'Videos')
                    $topic_query_string .= ' AND source:("Youtube" OR "Vimeo")';
                else if($request["source_handle"] == 'All')
                    $topic_query_string = $topic_query_string; //We do need to attach any filter
                else if($request["source_handle"] == 'Web')
                    $topic_query_string .= ' AND source:("FakeNews" OR "News" OR "Blogs" OR "Web")';
                else
                    $topic_query_string .= ' AND source:("'.$request["source_handle"].'")';
                
                if($request["topic_type"] == 'competitor_analysis')
                {
                    $ca_tid = explode("~", $request["param1"]);
                    $ca_senti_emo = $ca_tid[0];
                    $tid = $ca_tid[1];
                    
                    if (strpos($tid, '_') !== false) //means sub topic is also coming 
                    {
                        $_tid = explode("_", $tid);

                        $topic_query_string = $this->topic_obj->get_topic_elastic_query($_tid[0]).' AND '.$this->subtopic_obj->get_subtopic_elastic_query($_tid[1]);
                    }
                    else
                        $topic_query_string = $this->topic_obj->get_topic_elastic_query($tid);
                }
                
                //Following are filters via graph clicks
                if($request["param1"] == 'Positive' || $ca_senti_emo == 'Positive')
                {
                    $topic_query_string .= ' AND predicted_sentiment_value:("positive")';
                }
                else if($request["param1"] == 'Negative' || $ca_senti_emo == 'Negative')
                {
                    $topic_query_string .= ' AND predicted_sentiment_value:("negative")';
                }
                else if($request["param1"] == 'Neutral' || $ca_senti_emo == 'Neutral')
                {
                    $topic_query_string .= ' AND predicted_sentiment_value:("neutral")';
                }
                else if($request["param1"] == 'Surprise' || $ca_senti_emo == 'surprise')
                {
                    $topic_query_string .= ' AND emotion_detector:("surprise")';
                }
                else if($request["param1"] == 'Sadness' || $ca_senti_emo == 'sadness')
                {
                    $topic_query_string .= ' AND emotion_detector:("sadness")';
                }
                else if($request["param1"] == 'Happy' || $ca_senti_emo == 'happy')
                {
                    $topic_query_string .= ' AND emotion_detector:("happy")';
                }
                else if($request["param1"] == 'Fear' || $ca_senti_emo == 'fear')
                {
                    $topic_query_string .= ' AND emotion_detector:("fear")';
                }
                else if($request["param1"] == 'Anger' || $ca_senti_emo == 'anger')
                {
                    $topic_query_string .= ' AND emotion_detector:("anger")';
                }
                
                if($request["topic_type"] == 'subtopic' || $request["topic_type"] == 'touchpoint')
                {
                    $subtopic_session_id = \Session::get('current_loaded_subtopic');
                    $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
                    $topic_query_string .= ' AND '.$subtopic_query_string;
                    
                    if($request["topic_type"] == 'touchpoint')
                    {
                        $tp_es_query_string = $this->touchpoint_obj->get_touchpoint_elastic_query($request["param2"]);
                        
                        $topic_query_string .= ' AND '.$tp_es_query_string;
                    }
                }
                else if($request["topic_type"] == 'touchpoint_barchart')
                {
                    $subtopic_session_id = \Session::get('current_loaded_subtopic');
                    $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
                    $topic_query_string .= ' AND '.$subtopic_query_string;
                    
                    //Get touchpoint id by its name
                    $tp_id = DB::select("SELECT ctp.cx_tp_tp_id FROM cx_touch_points ctp, touch_points tp WHERE ctp.cx_tp_tp_id = tp.tp_id AND tp.tp_name = '".addslashes($request["param1"])."'");
                    
                    $tp_es_query_string = $this->touchpoint_obj->get_touchpoint_elastic_query($tp_id[0]->cx_tp_tp_id);
                        
                    $topic_query_string .= ' AND '.$tp_es_query_string;
                }
                else if($request["topic_type"] == 'twiiter_dm')
                {
                    if($this->cus_obj->check_customer_module_access($request->param1))
                    {
                        $twitter_dm = true;
                    }
                }
                
                if($request["param1"] == 'posts_by_date')
                {
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'from' => 0,
                        'size' => $posts_limit,
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string . ' AND p_created_time:("'.$request["param2"].'")' ]]
                                    ]
                                ]
                            ],
                            'sort' => [
                                ['p_created_time' => ['order' => 'desc']]
                            ]
                        ]
                    ];
                }
                else
                {
                    if($request["topic_type"] == 'twiiter_dm')
                    {
                        $params = [
                            'index' => 'd24_june_2020_dev',
                            'type' => 'mytype',
                            'from' => 0,
                            'size' => 30,
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            ['query_string' => ['query' => 'source:("DM") AND db_customer_id:("'.$this->cus_obj->get_parent_account_id().'")']],
                                            ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                        ]
                                    ]
                                ],
                                'sort' => [
                                    ['p_created_time' => ['order' => 'desc']]
                                ]
                            ]
                        ];
                    }
                    else
                    {
                        if($request["source_handle"] == 'GoogleMaps' || $request["source_handle"] == 'Tripadvisor')
                        {
                            $params = [
                                'index' => $this->search_index_name,
                                'type' => 'mytype',
                                'from' => 0,
                                'size' => $posts_limit,
                                'body' => [
                                    'query' => [
                                        'bool' => [
                                            'must' => [
                                                ['query_string' => ['query' => $topic_query_string ]],
                                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                            ]
                                        ]
                                    ],
                                    'sort' => [
                                        ['p_created_time' => ['order' => 'desc']]
                                    ]
                                ]
                            ];
                            //['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                        }
                        else
                        {
                            //attach date filter if posted
                            if(isset($request["param2"]) && !empty($request["param2"]) && !is_numeric($request["param2"]))
                            {
                                $selected_dates = explode("|", $request["param2"]);
                                                                
                                $greater_than_time = date_create_from_format('j F, Y', $selected_dates[0]);
                                $greater_than_time = date_format($greater_than_time, 'Y-m-d');
                                
                                $less_than_time = date_create_from_format('j F, Y', $selected_dates[1]);
                                $less_than_time = date_format($less_than_time, 'Y-m-d');
                            }
                            //Log::info("Query: ".$topic_query_string);
                            $params = [
                                'index' => $this->search_index_name,
                                'type' => 'mytype',
                                'from' => 0,
                                'size' => $posts_limit,
                                'body' => [
                                    'query' => [
                                        'bool' => [
                                            'must' => [
                                                ['query_string' => ['query' => $topic_query_string ]],
                                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                            ]
                                        ]
                                    ],
                                    'sort' => [
                                        ['p_created_time' => ['order' => 'desc']]
                                    ]
                                ]
                            ];
                            //Log::info($topic_query_string);
                        }
                    }
                }
                                
                //dd($topic_query_string);
                $es_data = $this->client->search($params); //dd($params);
                //print_r($es_data);
                if($request["topic_type"] == 'twiiter_dm' && $twitter_dm == false)
                {
                    $posts_html = 'No results';
                }
                else
                {
                    for($ii=0; $ii<count($es_data["hits"]["hits"]); $ii++)
                    {
                        if(($request["source_handle"] == 'GoogleMaps' || $request["source_handle"] == 'Tripadvisor') && $ii == 0)
                        {
                            //(SUM(individual rating)/total_reviews )
                            $params1 = [
                                'index' => $this->search_index_name,
                                'type' => 'mytype',
                                'from' => 0,
                                'size' => '500',
                                'body' => [
                                    'query' => [
                                        'bool' => [
                                            'must' => [
                                                ['query_string' => ['query' => $topic_query_string]],
                                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                            ]
                                        ]
                                    ]
                                ]
                            ];
                            //echo '<pre>';
                            //print_r($params1);
                            //['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            $results1 = $this->client->search($params1);
                            $rating_count = 0;
                            $total_reviews = $results1["hits"]["total"];
                            for($p=0; $p<count($results1["hits"]["hits"]); $p++)
                            {
                                $rating_count = $rating_count + $results1["hits"]["hits"][$p]["_source"]["p_rating"];
                            }
                            
                            $avg_rating = $rating_count/$total_reviews;
                            
                            $rating_icon = '';
                            if(floor($avg_rating) > 3)
                            {
                                $rating_icon = '<i class="bx bx-happy" style="color:#3bdb8b; font-size: 4rem;"></i>';
                            }
                            else if(floor($avg_rating) == 3)
                            {
                                $rating_icon = '<i class="bx bx-meh" style="color:#5b8eee; font-size: 4rem;"></i>';
                            }
                            else if(floor($avg_rating) < 3)
                            {
                                $rating_icon = '<i class="bx bx-sad" style="color:#fe5a5c; font-size: 4rem;"></i>';
                            }
                            
                            $rating_stars = '';
                            for($k=0; $k<floor($results1["hits"]["hits"][0]["_source"]["place_star_rating"]); $k++)
                            {
                                $rating_stars .= '<i class="bx bxs-star" style="color: #b0e0e6;"></i>';
                            }

                            $rating_html = '<div>'.$rating_stars.'<small class="text-muted"> </small></div>';
                                                        
                            $posts_html .= '<div class="card" style="background: #fafafa !important;">
                            <div class="card-header" style="padding-bottom: 5px;">
                              <h4 class="card-title">'.$es_data["hits"]["hits"][$ii]["_source"]["place_name"].'</h4>
                            </div>                            
                            <div class="card-body">
                                '.$rating_html.'
                                <div style="padding-top: 0px;">
                                    <table width="100%" cellpadding="0" cellspacing="0">
                                        <tr><td style="width:75%;"><i>'.$es_data["hits"]["hits"][$ii]["_source"]["place_category"][0].'</i><br><small>'.$es_data["hits"]["hits"][$ii]["_source"]["place_location"].'</small></td><td style="width:25%; text-align: right;">'.$rating_icon.'</td></tr>
                                    </table>
                                </div>
                            </div>

                          </div>';
                        }
                        if($ii < $posts_limit)
                        {
                            $posts_html .= $this->gen_func_obj->get_postview_html($es_data["hits"]["hits"][$ii]);
                        }
                        else 
                            break;
                    }
                }
                
                //Calculate top insights
                if(isset($request["topic_type"]) && $request["topic_type"] == 'competitor_analysis')
                {
                    $loaded_topic_id = 4;
                }
                $hash_keys = $this->topic_obj->get_topic_hash_keywords($loaded_topic_id);
                $in_val = '';
                
                if(!empty($hash_keys[0]->topic_hash_tags))
                {
                    $htags = explode("|", $hash_keys[0]->topic_hash_tags);

                    for ($i = 0; $i < count($htags); $i ++)
                    {
                        if (! empty(trim($htags[$i])))
                            $in_val .= trim($htags[$i]).",";
                    }
                }        

                if(!empty($hash_keys[0]->topic_keywords))
                {
                    $keywords = explode(",", $hash_keys[0]->topic_keywords);

                    for ($i = 0; $i < count($keywords); $i ++)
                    {
                        if (! empty(trim($keywords[$i])))
                            $in_val .= trim($keywords[$i]).",";
                    }
                }
                
                $in_val = substr($in_val, 0, -1);
                $kh = explode(",", $in_val);
                
                $top_insight_str = '<div class="card" style="border:1px solid #f0f0f0; margin-top: 15px;"><div class="card-header" style="border-bottom: 1px solid #f0f0f0;"><h5 class="card-title mb-0">Top insights</h5></div><div class="card-body" style="padding-top: 10px;">';
                
                for($p=0; $p<count($kh); $p++)
                {
                    if($request["param1"] == 'posts_by_date')
                    {
                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            [ 'query_string' => [ 'query' => $topic_query_string.' AND (p_message_text:("'.$kh[$p].'") OR u_fullname:("'.$kh[$p].'") OR u_source:("'.$kh[$p].'")) AND p_created_time:("' . $request["param2"] . '")' ] ]
                                        ]
                                    ]
                                ]
                            ]
                        ];
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
                                            [ 'query_string' => [ 'query' => $topic_query_string.' AND (p_message_text:("'.$kh[$p].'") OR u_fullname:("'.$kh[$p].'") OR u_source:("'.$kh[$p].'"))' ] ],
                                            ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                        ]
                                    ]
                                ]
                            ]
                        ];
                    }
                    

                    $key_data = $this->client->count($params);
                    
                    if($key_data["count"] > 0)
                    {
                        if(substr($kh[$p], 0, 1) == '#')
                            $istr_class = 'bx-hash';
                        else if(substr($kh[$p], 0, 1) == '@')
                            $istr_class = 'bxl-twitter';
                        else
                            $istr_class = 'bx-text';
                        
                        $top_insight_str .= '<div class="avatar mr-75" style="background-color: #8bc5d6 !important">'
                        . '<div class="avatar-content" style="width:25px; height:25px;">'
                        . '<i class="bx '.$istr_class.'" style="color: #ffffff !important; font-size: 1rem;"></i>'
                        .'</div>'
                        . '</div>'
                        . '<b>'.number_format($key_data["count"]).'</b> posts containing <b>'.$kh[$p].'</b><br>';
                    }
                }
                
                //sentiments
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    [ 'query_string' => [ 'query' => $topic_query_string.' AND predicted_sentiment_value:("positive")' ] ],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];

                $senti_data = $this->client->count($params);
                $pos = $senti_data["count"];
                
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    [ 'query_string' => [ 'query' => $topic_query_string.' AND predicted_sentiment_value:("negative")' ] ],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];

                $senti_data = $this->client->count($params);
                $neg = $senti_data["count"];
                
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    [ 'query_string' => [ 'query' => $topic_query_string.' AND predicted_sentiment_value:("neutral")' ] ],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];

                $senti_data = $this->client->count($params);
                $neu = $senti_data["count"];
                
                $total_senti = $pos+$neg+$neu;
                
                if($total_senti > 0)
                {
                    $top_insight_str .= '<div class="avatar mr-75" style="background-color: #8bc5d6 !important">'
                    . '<div class="avatar-content" style="width:25px; height:25px;">'
                    . '<i class="bx bx-gift" style="color: #ffffff !important; font-size: 1rem;"></i>'
                    .'</div>'
                    . '</div>'
                    . '<b>Sentiment distribution: </b>'.number_format(($pos/$total_senti)*100).'% Postive, '.number_format(($neu/$total_senti)*100).'% Neutral & '.number_format(($neg/$total_senti)*100).'% Negative';
                }
                /***** sentiments ****/
                $top_insight_str .= '</div></div>';
                //END: Top insights
                
                /***** by source *****/
                $channels_str = '<div class="card" style="border:1px solid #f0f0f0; margin-top: 15px;"><div class="card-header" style="border-bottom: 1px solid #f0f0f0;"><h5 class="card-title mb-0">Breakdown by channels</h5></div><div class="card-body" style="padding-top: 10px;">';
                
                //$channels_str .= '<div class="row"><div class="col-sm-6" style="border-right: 1px solid #f0f0f0; font-weight: bold;">Source</div><div class="col-sm-6" style="text-align: right; font-weight: bold;">Mentions</div></div>';
                
                $sources_array = array("Videos", "News", "Twitter", "Pinterest", "Instagram", "Blogs", "Reddit", "Tumblr", "Facebook", "Web", "Linkedin");
                
                $channels_str .= '<div class="row">';
                
                for($i=0; $i<count($sources_array); $i++)
                {
                    if($i%2 == 0)
                        $bg_col = '#F8F8FA';
                    else
                        $bg_col = '#ffffff';
                    
                    if($sources_array[$i] == 'Videos')
                        $_sources = '"Youtube" OR "Vimeo"';
                    else if($sources_array[$i] == 'News')
                        $_sources = '"FakeNews" OR "News"';
                    else
                        $_sources = $sources_array[$i];
                    
                    if($request["param1"] == 'posts_by_date')
                    {
                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            [ 'query_string' => [ 'query' => $topic_query_string.' AND source:("'.$_sources.'") AND p_created_time:("' . $request["param2"] . '")' ] ]
                                        ]
                                    ]
                                ]
                            ]
                        ];
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
                                            [ 'query_string' => [ 'query' => $topic_query_string.'  AND source:("'.$_sources.'")' ] ],
                                            ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                        ]
                                    ]
                                ]
                            ]
                        ];
                    }
                    

                    $s_data = $this->client->count($params);
                    
                    if($sources_array[$i] == 'Videos')
                        $channel_logo = 'bxl-youtube';
                    else if($sources_array[$i] == 'News')
                        $channel_logo = 'bx-news';
                    
                    $channels_str .= '<div class="col-sm-6" style="background: '.$bg_col.';">';
                    $channels_str .= '<div class="avatar mr-75" style="background-color: #8bc5d6 !important">'
                        . '<div class="avatar-content" style="width:30px; height:30px;">'
                        . '<i class="bx '.$channel_logo.'" style="color: #ffffff !important; font-size: 1.2rem;"></i>'
                        .'</div> Twitter'
                        . '</div>';
                    $channels_str .= '</div>';
                    
                    $channels_str .= '<div class="col-sm-6" style="text-align: right; background: '.$bg_col.'; line-height: 39px;">';
                    $channels_str .= number_format($s_data["count"]);
                    $channels_str .= '</div>';
                }
                
                $channels_str .= '</div>';
                
                
                
                $channels_str .= '</div></div>';
                /*****END: by source *****/
                
                
                                
                //echo $posts_html.'~|~'.$top_insight_str.'~|~'.$channels_str;
                echo $posts_html;
            }
            else if($request["section"] == 'subtopic_emotions_chart' || $request["section"] == 'maintopic_emotions_chart')
            {
                if(isset($request["pdf_report_data"]) && $request["pdf_report_data"] == 'yes')
                {
                    $subtopic_session_id = $request["stid"];
                    $greater_than_time = $request["from_date"];
                    $less_than_time = $request["to_date"];
                }
                else
                {
                    $subtopic_session_id = \Session::get('current_loaded_subtopic');
                }
                
                                
                if($request["section"] == 'subtopic_emotions_chart')
                {
                    $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
                    $topic_query_string .= ' AND '.$subtopic_query_string;
                }                    
                
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string.' AND emotion_detector:("anger")' ]],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];
                
                $es_data = $this->client->count($params);
                $emo_anger = $es_data["count"];
                
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string.' AND emotion_detector:("fear")' ]],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];
                
                $es_data = $this->client->count($params);
                $emo_fear = $es_data["count"];
                
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string.' AND emotion_detector:("happy")' ]],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];
                
                $es_data = $this->client->count($params);
                $emo_happy = $es_data["count"];
                
                /*$params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string.' AND emotion_detector:("neutral")' ]],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];
                
                $es_data = $this->client->count($params);
                $emo_neutral = $es_data["count"];*/
                
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string.' AND emotion_detector:("sadness")' ]],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];
                
                $es_data = $this->client->count($params);
                $emo_sadness = $es_data["count"];
                
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string.' AND emotion_detector:("surprise")' ]],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];
                
                $es_data = $this->client->count($params);
                $emo_suprise = $es_data["count"];
                
                $total_emos = $emo_anger+$emo_fear+$emo_happy+$emo_sadness+$emo_suprise; //+$emo_neutral
                
                if($total_emos == 0)
                {
                    $emotions_array = array("Anger", "Fear", "Happy", "Sadness", "Surprise"); //keep sequence same as its counts array
                    $emotions_counts = array(0, 0, 0, 0, 0);
                }
                else
                {
                    $emotions_array = array("Anger", "Fear", "Happy", "Sadness", "Surprise"); //keep sequence same as its counts array
                    //$emotions_counts = array(round(($emo_anger / $total_emos) * 100, 2), round(($emo_fear / $total_emos) * 100, 2), round(($emo_happy / $total_emos) * 100, 2), round(($emo_sadness / $total_emos) * 100, 2), round(($emo_suprise / $total_emos) * 100, 2));
                    $emotions_counts = array($emo_anger, $emo_fear, $emo_happy, $emo_sadness, $emo_suprise);
                }        
                
                
                return response()->json([
                    'emos' => $emotions_array,
                    'counts' => $emotions_counts
                ]);
            }
            else if($request["section"] == 'touchpoints_bar_chart')
            {
                if(isset($request["pdf_report_data"]) && $request["pdf_report_data"] == 'yes')
                {
                    $subtopic_session_id = $request["stid"];
                    $greater_than_time = $request["from_date"];
                    $less_than_time = $request["to_date"];
                    $all_touchpoints = $this->touchpoint_obj->get_all_touchpoint_ids($subtopic_session_id);
                }
                else
                {
                    $subtopic_session_id = \Session::get('current_loaded_subtopic');
                    $all_touchpoints = $this->touchpoint_obj->get_all_tp_ids();
                }
                
                $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
                $topic_query_string .= ' AND '.$subtopic_query_string;
                                
                $tp_names = [];
                $tp_counts = [];
                
                
                
                if($all_touchpoints == 'NA')
                    echo 'NA';
                else
                {
                    for($i=0; $i<count($all_touchpoints); $i++)
                    {
                        if(isset($request["pdf_report_data"]) && $request["pdf_report_data"] == 'yes')
                        {
                            $tp_data = $this->touchpoint_obj->get_touchpoint_data($all_touchpoints[$i]->tp_id);
                            $tp_es_query_string = $this->touchpoint_obj->get_touchpoint_elastic_query($all_touchpoints[$i]->tp_id);
                        }
                        else
                        {
                            $tp_data = $this->touchpoint_obj->get_touchpoint_data($all_touchpoints[$i]->cx_tp_tp_id);
                            $tp_es_query_string = $this->touchpoint_obj->get_touchpoint_elastic_query($all_touchpoints[$i]->cx_tp_tp_id);
                        }                        
                        
                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            ['query_string' => ['query' => $topic_query_string.' AND '.$tp_es_query_string ]],
                                            ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                        ]
                                    ]
                                ]
                            ]
                        ];

                        $es_data = $this->client->count($params);
                        $tp_count = $es_data["count"];
                        
                        $tp_names[] = $tp_data[0]->tp_name;
                        $tp_counts[] = $tp_count;
                    }
                    
                    return response()->json([
                        'tp_names' => $tp_names,
                        'tp_counts' => $tp_counts
                    ]);
                }
            }
            else if($request["section"] == 'csat_data')
            {
                if(isset($request["pdf_report_data"]) && $request["pdf_report_data"] == 'yes')
                {
                    $subtopic_session_id = $request["stid"];
                    $exp_metrics = $this->subtopic_obj->get_subtopic_metrics($subtopic_session_id);
                }
                else
                {
                    $subtopic_session_id = \Session::get('current_loaded_subtopic');
                
                    $exp_metrics = $this->subtopic_obj->get_subtopic_metrics($subtopic_session_id);
                }
                
                
                if(stristr($exp_metrics[0]->exp_metrics, 'csat') === FALSE)
                {
                    return response()->json([
                        'csat_score' => 'NA'
                    ]);
                }
                else
                {
                    $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
                    $topic_query_string .= ' AND '.$subtopic_query_string;
                    
                    //postive unique uers
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'from' => '0',
                        'size' => '0',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string . ' AND predicted_sentiment_value:("positive") AND NOT source:("FakeNews" OR "News")']],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                    ]
                                ]
                            ],
                            'aggs' => ['group_by_user' => ['terms' => ['field' => 'u_source.keyword', 'size' => 1]], 'unique_users' => ['cardinality' => ['field' => 'u_source.keyword']]]
                        ]
                    ];

                    $results = $this->client->search($params);
                    $unique_pos_users = $results["aggregations"]["unique_users"]["value"];

                    //negative unique uers
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'from' => '0',
                        'size' => '0',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string . ' AND predicted_sentiment_value:("negative") AND NOT source:("FakeNews" OR "News")']],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                    ]
                                ]
                            ],
                            'aggs' => ['group_by_user' => ['terms' => ['field' => 'u_source.keyword', 'size' => 1]], 'unique_users' => ['cardinality' => ['field' => 'u_source.keyword']]]
                        ]
                    ];

                    $results = $this->client->search($params);
                    $unique_neg_users = $results["aggregations"]["unique_users"]["value"];

                    //neutral unique uers
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'from' => '0',
                        'size' => '0',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string . ' AND predicted_sentiment_value:("neutral") AND NOT source:("FakeNews" OR "News")']],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                    ]
                                ]
                            ],
                            'aggs' => ['group_by_user' => ['terms' => ['field' => 'u_source.keyword', 'size' => 1]], 'unique_users' => ['cardinality' => ['field' => 'u_source.keyword']]]
                        ]
                    ];

                    $results = $this->client->search($params);
                    $unique_neu_users = $results["aggregations"]["unique_users"]["value"];

                    //CSAT score
                    $tot_uniq_sen = $unique_pos_users + $unique_neg_users + $unique_neu_users;
                    $csta = ($unique_pos_users / $tot_uniq_sen) * 100; //$tot_experience_results

                    if($csta == 0)
                        $csta = 1;

                    return response()->json([
                        'csat_score' => ceil($csta)
                    ]);
                }                
            }
            else if($request["section"] == 'revenue_loss_data')
            {
                $subtopic_session_id = \Session::get('current_loaded_subtopic');
                $exp_metrics = $this->subtopic_obj->get_subtopic_metrics($subtopic_session_id);
                
                
                if(stristr($exp_metrics[0]->exp_metrics, 'potential_loss') === FALSE)
                {
                    return response()->json([
                        'rev_loss' => 'NA'
                    ]);
                }
                else
                {
                    $roi_data = $this->subtopic_obj->get_revenue_loss_data($subtopic_session_id);
                    
                    $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
                    $topic_query_string .= ' AND '.$subtopic_query_string;
                    
                    //negative unique uers
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'from' => '0',
                        'size' => '0',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string . ' AND predicted_sentiment_value:("negative") AND NOT source:("FakeNews" OR "News")']],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                    ]
                                ]
                            ],
                            'aggs' => ['group_by_user' => ['terms' => ['field' => 'u_source.keyword', 'size' => 1]], 'unique_users' => ['cardinality' => ['field' => 'u_source.keyword']]]
                        ]
                    ];

                    $results = $this->client->search($params);
                    $unique_neg_users = $results["aggregations"]["unique_users"]["value"];
                    
                    $avg_roi = preg_replace('/[^0-9]+/', '', $roi_data[0]->roi_avg_revenue);
                    $churn_rate = ($unique_neg_users * $roi_data[0]->roi_churn_rate) / 100;

                    $potential_loss = $churn_rate * $avg_roi;
                    
                    return response()->json([
                        'rev_loss' => number_format(ceil($potential_loss)),
                        'rev_per' => $roi_data[0]->roi_churn_rate
                    ]);
                }
            }
            else if($request["section"] == 'subtopic_wordcloud' || $request["section"] == 'maintopic_wordcloud')
            {
                $load_elastic_data = true;
                
                if($request["section"] == 'subtopic_wordcloud' && $request["pdf_report_data"] != 'yes')
                {
                    $subtopic_session_id = \Session::get('current_loaded_subtopic');
                    $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
                    $topic_query_string .= ' AND '.$subtopic_query_string; 
                }
                
                if(isset($request["pdf_report_data"]) && $request["pdf_report_data"] == 'yes')
                {
                    $load_elastic_data = true;
                    $greater_than_time = $request["from_date"];
                    $less_than_time = $request["to_date"];
                    
                    if($request["section"] == 'subtopic_wordcloud')
                    {
                        $subtopic_session_id = $request["stid"];
                        $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
                        $topic_query_string .= ' AND '.$subtopic_query_string;
                    }
                }
                
                //Check if saved word cloud data in db for this subtopic
                if(!isset($request["pdf_report_data"]) && $request["pdf_report_data"] != 'yes')
                {
                    //DB::update("SET NAMES 'utf8'");
                    //DB::update("SET CHARACTER SET utf8");
                    //https://stackoverflow.com/questions/15932300/how-to-convert-unicode-to-arabic-characters-in-php
                    
                    if($request["section"] == 'subtopic_wordcloud')
                        $chk = DB::select("SELECT wc_str, wc_str_sorted FROM wordcloud_data WHERE wc_time >= DATE_SUB(NOW(),INTERVAL 7 DAY) AND wc_stid = ".$subtopic_session_id);
                    else if($request["section"] == 'maintopic_wordcloud')
                        $chk = DB::select("SELECT wc_str, wc_str_sorted FROM wordcloud_data WHERE wc_time >= DATE_SUB(NOW(),INTERVAL 7 DAY) AND wc_tid = ".\Session::get('current_loaded_project'));
                    
                    if(count($chk) > 0 && $request["dash_filters_applied"] != 'yes')
                    {
                        $load_elastic_data = false;
                    }
                    else
                    {
                        $load_elastic_data = true;
                    }
                }
                
                if($load_elastic_data == true)
                {
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string ]],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                    ]
                                ]
                            ],
                            'aggs' => [
                                'tagcloud' => [
                                    'terms' => ['field' => 'p_message', 'size' => 60]
                                ]
                            ]
                        ]
                    ];

                    $results = $this->client->search($params);

                    $wordstag_array = array();
                    $omit_words = array();

                    //Get omit words from DB
                    $omt_wrdz = DB::select("SELECT * FROM omit_words");
                    for($o=0; $o<count($omt_wrdz); $o++)
                    {
                        $omit_words[] = stripslashes($omt_wrdz[$o]->word);
                    }



                    for ($i = 0; $i < count($results["aggregations"]["tagcloud"]["buckets"]); $i ++)
                    {
                        if (!empty($results["aggregations"]["tagcloud"]["buckets"][$i]["key"]) && !is_numeric($results["aggregations"]["tagcloud"]["buckets"][$i]["key"]) && !in_array($results["aggregations"]["tagcloud"]["buckets"][$i]["key"], $omit_words) && strlen($results["aggregations"]["tagcloud"]["buckets"][$i]["key"]) >= 4)
                            $wordstag_array[$results["aggregations"]["tagcloud"]["buckets"][$i]["key"]] = $results["aggregations"]["tagcloud"]["buckets"][$i]["doc_count"];
                    }

                    arsort($wordstag_array);

                    $fsize = 0;
                    $top_count = 0;
                    $j = 0;
                    $final_tags_array = array();
                    $final_tags_array_sorted = array();
                    $tag_str = '';

                    foreach ($wordstag_array as $word => $word_count)
                    {
                        // echo 'Word: '.$word.' => '.$word_count.'<br>';
                        if ($j == 0)
                        {
                            $top_count = $word_count;
                            $top_count_name = $word;
                        }


                        //$final_tags_array[$j]["weight"] = 52 - $fsize;
                        //$final_tags_array[$j]["tagname"] = $word;
                        //$final_tags_array[$j]["url"] = '#';
                        //$final_tags_array[$j]["count"] = $word_count;
                        $final_tags_array[$j]["tag"] = str_replace("'", "", $word);
                        $final_tags_array[$j]["count"] = $word_count;
                        $tag_str .= $word.' ';

                        if ($fsize <= 38)
                            $fsize = $fsize + 2;

                        $j ++;

                        if ($j > 60) // This check should be equal to how many tags to show
                            break;
                    }
                    $final_tags_array_sorted = $final_tags_array;
                    shuffle($final_tags_array);

                    //Save wordcloud data in db
                    if(!isset($request["pdf_report_data"]) && $request["pdf_report_data"] != 'yes')
                    {
                        if($request["section"] == 'subtopic_wordcloud')
                            $chk = DB::select("SELECT wc_id FROM wordcloud_data WHERE wc_stid = ".$subtopic_session_id);
                        else if($request["section"] == 'maintopic_wordcloud')
                            $chk = DB::select("SELECT wc_id FROM wordcloud_data WHERE wc_tid = ".\Session::get('current_loaded_project'));

                        //DB::update("SET NAMES 'utf8'");
                        //DB::update("SET CHARACTER SET utf8");
                    
                        if(count($chk) > 0)
                        {
                            if($request["section"] == 'subtopic_wordcloud')
                                DB::update("UPDATE wordcloud_data SET wc_time = NOW() WHERE wc_stid = ".$subtopic_session_id."  AND wc_id = ".$chk[0]->wc_id);
                            else if($request["section"] == 'maintopic_wordcloud')
                                DB::update("UPDATE wordcloud_data SET wc_time = NOW() WHERE wc_tid = ".\Session::get('current_loaded_project')."  AND wc_id = ".$chk[0]->wc_id);
                        }
                        else
                        {
                            if($request["section"] == 'subtopic_wordcloud')
                                DB::insert("INSERT INTO wordcloud_data SET wc_stid = ".$subtopic_session_id.", wc_str = '".json_encode($final_tags_array, JSON_UNESCAPED_UNICODE)."', wc_str_sorted = '".json_encode($final_tags_array_sorted, JSON_UNESCAPED_UNICODE)."', wc_time = NOW()");
                            else if($request["section"] == 'maintopic_wordcloud')
                                DB::insert("INSERT INTO wordcloud_data SET wc_tid = ".\Session::get('current_loaded_project').", wc_str = '".json_encode($final_tags_array, JSON_UNESCAPED_UNICODE)."', wc_str_sorted = '".json_encode($final_tags_array_sorted, JSON_UNESCAPED_UNICODE)."', wc_time = NOW()");
                        }
                    }
                    
                    $wc_array["sorted"] = $final_tags_array_sorted;
                    $wc_array["shuffeled"] = $final_tags_array;
                    
                    $wc_to_array = $wc_array["sorted"];
                    
                    $wc_str = '';
                    for($i=0; $i<count($wc_to_array); $i++)
                    {
                        //$wc_str .= '<div style="float:left; padding: 10px 15px 10px 15px; text-align:center; text-transform: capitalize; margin: 0px 0px 5px 0px; border-right: 5px solid #fff; background: #fafafa;"><span  class="text-muted">'.$wc_to_array[$i]["tag"].'</span><br><span style="font-size:18px;">'.number_format($wc_to_array[$i]["count"]).'</span></div>';
                        $wc_str .= '<div class="card bg-secondary text-white" style="float:left; padding: 10px 15px 10px 15px; text-align:center; text-transform: capitalize; margin: 0px 10px 10px 0px;"><h5 class="card-title text-white" style="margin-bottom: 0px;">'.$wc_to_array[$i]["tag"].'</h5><span class="line-ellipsis" style="font-size:18px;">'.number_format($wc_to_array[$i]["count"]).'</span></div>';
                    }
                    
                    $wc_array["list_view"] = $wc_str;
                    
                    if(isset($request["pdf_report_data"]) && $request["pdf_report_data"] == 'yes')
                        echo json_encode($final_tags_array);
                    else
                        echo json_encode($wc_array);
                }
                else
                {
                    $wc_array["sorted"] = $chk[0]->wc_str_sorted;
                    
                    $wc_to_array = json_decode($chk[0]->wc_str_sorted, true);
                    
                    $wc_str = '';
                    for($i=0; $i<count($wc_to_array); $i++)
                    {
                        //$wc_str .= '<div style="float:left; padding: 10px 15px 10px 15px; text-align:center; text-transform: capitalize; margin: 0px 0px 5px 0px; border-right: 5px solid #fff; background: #fafafa;"><span  class="text-muted">'.$wc_to_array[$i]["tag"].'</span><br><span style="font-size:18px;">'.number_format($wc_to_array[$i]["count"]).'</span></div>';
                        $wc_str .= '<div class="card bg-secondary text-white" style="float:left; padding: 10px 15px 10px 15px; text-align:center; text-transform: capitalize; margin: 0px 10px 10px 0px;"><h5 class="card-title text-white" style="margin-bottom: 0px;">'.$wc_to_array[$i]["tag"].'</h5><span class="line-ellipsis" style="font-size:18px;">'.number_format($wc_to_array[$i]["count"]).'</span></div>';
                    }
                    
                    $wc_array["list_view"] = $wc_str; 
                    
                    $wc_array["shuffeled"] = $chk[0]->wc_str;
                    echo json_encode($wc_array);
                    //echo $chk[0]->wc_str;
                }
                
                
                
                //https://stackoverflow.com/questions/59324184/update-workcloud-data-amcharts
                
                //$max_val_index = array_search(40, array_column($final_tags_array, 'weight'));
                //$tagCloud = new tagCloud($final_tags_array, $max_val_index);

                //$tag_cloud_html = $tagCloud->displayTagCloud();

                //$response = $tag_cloud_html . '~|~' . $top_count_name . '~|~' . number_format($top_count);
            }
            else if($request["section"] == 'maintopic_mentions' || $request["section"] == 'subtopic_tot_mentions')
            {
                if($request["section"] == 'subtopic_tot_mentions')
                {
                    $subtopic_session_id = \Session::get('current_loaded_subtopic');
                    
                    $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
                    $topic_query_string .= ' AND '.$subtopic_query_string;
                                        
                }
                
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string ]],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];
                //Log::info($topic_query_string);
                $results = $this->client->count($params);
                
                if($request["format_text"] == 'yes')
                    echo $this->gen_func_obj->format_number_data($results["count"]);
                else
                    echo $results["count"];
            }
            else if($request["section"] == 'maintopic_reach' || $request["section"] == 'subtopic_reach')
            {
                if($request["section"] == 'subtopic_reach')
                {
                    $subtopic_session_id = \Session::get('current_loaded_subtopic');
                    
                    $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
                    $topic_query_string .= ' AND '.$subtopic_query_string;
                                        
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
                                    ['query_string' => ['query' => $topic_query_string]],
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
                                    ['query_string' => ['query' => $topic_query_string]],
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
                                    ['query_string' => ['query' => $topic_query_string]],
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
                
                if($request["format_text"] == 'yes')
                    echo $this->gen_func_obj->format_number_data($estimated_reach);
                else
                    echo $estimated_reach;
            }
            else if($request["section"] == 'maintopic_influencers' || $request["section"] == 'subtopic_influencers')
            {
                if(isset($request["pdf_report_data"]) && $request["pdf_report_data"] == 'yes')
                {
                    $greater_than_time = $request["from_date"];
                    $less_than_time = $request["to_date"];
                }
                
                if($request["section"] == 'subtopic_influencers')
                {
                    $subtopic_session_id = \Session::get('current_loaded_subtopic');
                    
                    $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
                    $topic_query_string .= ' AND '.$subtopic_query_string;
                }
                
                $inf_array = array();
                
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string]], // . ' AND account_type:("0" OR "2") AND NOT account_type:("1")'
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]],
                                    ['range' => ['u_followers' => ['gte' => 5000000]]]
                                ]
                            ]
                        ]
                    ]
                ];

                $results = $this->client->count($params);

                $infl_celebrity = $results["count"];
                $inf_array["celebrity"] = $results["count"];

                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string]], // . ' AND account_type:("0" OR "2") AND NOT account_type:("1")'
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]],
                                    ['range' => ['u_followers' => ['gte' => 1000000, 'lte' => 5000000]]]
                                ]
                            ]
                        ]
                    ]
                ];

                $results = $this->client->count($params);

                $infl_mega = $results["count"];
                $inf_array["mega"] = $results["count"];

                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string]], // . ' AND account_type:("0" OR "2") AND NOT account_type:("1")'
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]],
                                    ['range' => ['u_followers' => ['gte' => 500000, 'lte' => 1000000]]]
                                ]
                            ]
                        ]
                    ]
                ];

                $results = $this->client->count($params);

                $infl_macro = $results["count"];
                $inf_array["macro"] = $results["count"];

                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string]], // . ' AND account_type:("0" OR "2") AND NOT account_type:("1")'
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]],
                                    ['range' => ['u_followers' => ['gte' => 50000, 'lte' => 500000]]]
                                ]
                            ]
                        ]
                    ]
                ];

                $results = $this->client->count($params);

                $infl_midtier = $results["count"];
                $inf_array["midtier"] = $results["count"];

                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string]], // . ' AND account_type:("0" OR "2") AND NOT account_type:("1")'
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]],
                                    ['range' => ['u_followers' => ['gte' => 10000, 'lte' => 50000]]]
                                ]
                            ]
                        ]
                    ]
                ];

                $results = $this->client->count($params);

                $infl_micro = $results["count"];
                $inf_array["micro"] = $results["count"];

                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string]], // . ' AND account_type:("0" OR "2") AND NOT account_type:("1")'
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]],
                                    ['range' => ['u_followers' => ['gte' => 1000, 'lte' => 10000]]]
                                ]
                            ]
                        ]
                    ]
                ];

                $results = $this->client->count($params);

                $infl_nano = $results["count"];
                $inf_array["nano"] = $results["count"];
                
                echo json_encode($inf_array);
                

                //$total_influencers = $infl_celebrity + $infl_macro + $infl_mega + $infl_micro + $infl_midtier + $infl_nano;
            }
            else if($request["section"] == 'most_active_users' || $request["section"] == 'most_active_users_st')
            {
                if($request["section"] == 'most_active_users_st')
                {
                    $subtopic_session_id = \Session::get('current_loaded_subtopic');
                    
                    $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
                    $topic_query_string .= ' AND '.$subtopic_query_string;
                }
                
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'from' => '0',
                    'size' => '0',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string . ' AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Instagram")' ]],
                                    ['exists' => ['field' => 'u_profile_photo']],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ],
                                'must_not' => [
                                    ['term' => ['u_profile_photo.keyword' => '']]
                                ]
                            ]
                        ],
                        'aggs' => [
                            'group_by_user' => ['terms' => ['field' => 'u_source.keyword', 'size' => 20],
                                'aggs' => [
                                    'grouped_results' => [
                                        'top_hits' => ['size' => 1, '_source' => ['include' => ['u_fullname', 'u_profile_photo', 'u_date_joined', 'u_country', 'u_followers']],
                                            'sort' => ['p_created_time' => ['order' => 'desc']]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];
                
                $results = $this->client->search($params);

                $n = 1;
                $j = 0;
                
                $u_html = '';

                for ($i = 0; $i < count($results["aggregations"]["group_by_user"]["buckets"]); $i ++)
                {
                    if (!empty($results["aggregations"]["group_by_user"]["buckets"][$i]["key"]))
                    {
                        $bg_color = ($j == 0 || $j == 1) ? '#f9f9f9' : '#ffffff';
                        
                        if(isset($results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_followers"]))
                            $ufollowers = $results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_followers"];
                        else
                            $ufollowers = 0;

                        $u_html .= '<div class="col-sm-6" style="background: ' . $bg_color . '; margin-left:0px; padding: 7px 0px 7px 0px; font-size: 16px; float:left;">';
                        $u_html .= '<table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="3%">&nbsp;&nbsp;&nbsp;' . $n . '.</td>
                                    <td width="10%" style="text-align: center;"><img alt="" src="' . $results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_profile_photo"] . '" style="width: 40px; height: 40px; border-radius: 20px;"></td>
                                    <td width="44%"><a href="javascript:void(0);">' . $results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_fullname"] . '</a></td>';
                        if(isset($results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_country"]))
                            $u_html .= '<td width="12%"><img src="https://'.$request->getHost().'/images/flags/4x3/'.$this->gen_func_obj->get_country_flag($results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_country"]) . '" width="35"></td>';
                        else
                            $u_html .= '<td width="12%">&nbsp;</td>';
                        
                        $u_html .= '<td width="15%" style="text-align: right; font-size:1.1rem;">' . $this->gen_func_obj->format_number_data($ufollowers) . '<p style="color:#cccccc; font-size: 13px;">Followers</p></td>
                                    <td width="16%" style="text-align: right; padding-right: 15px; font-size:1.1rem;">' . $this->gen_func_obj->format_number_data($results["aggregations"]["group_by_user"]["buckets"][$i]["doc_count"]) . '<p style="color:#cccccc; font-size: 13px;">Posts</p></td>
                                </tr>
                            </table>';
                        
                        $u_html .= '</div>';

                        $n = $n + 1;

                        if ($j == 3)
                            $j = 0;
                        else
                            $j = $j + 1;
                    }
                }

                return $u_html;
            }
            else if($request["section"] == 'get_map_data')
            {
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'from' => 0,
                    'size' => 1500,
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string ]],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ],
                                'must_not' => [
                                    'match' => ['u_latitude' => 0],
                                    'match' => ['u_longitude' => 0],
                                    'match' => ['u_latitude' => ''],
                                    'match' => ['u_longitude' => '']
                                ]
                            ]
                        ]
                    ]
                ];
                
                $results = $this->client->search($params);
                
                $coordinates_str = "";

                for ($j = 0; $j < count($results["hits"]["hits"]); $j ++)
                {
                    $n = str_replace('"', '', $results["hits"]["hits"][$j]["_source"]["u_fullname"]);
                    $n = str_replace("'", "", $n);
                    $name = trim(preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $n));

                    if (isset($results["hits"]["hits"][$j]["_source"]["p_shares"]))
                        $p_shares = $results["hits"]["hits"][$j]["_source"]["p_shares"];
                    else
                        $p_shares = 0;

                    if (isset($results["hits"]["hits"][$j]["_source"]["p_likes"]))
                        $p_likes = $results["hits"]["hits"][$j]["_source"]["p_likes"];
                    else
                        $p_likes = 0;

                    if (isset($results["hits"]["hits"][$j]["_source"]["u_followers"]))
                        $u_followers = $results["hits"]["hits"][$j]["_source"]["u_followers"];
                    else
                        $u_followers = 0;

                    if (isset($results["hits"]["hits"][$j]["_source"]["u_likes"]))
                        $u_likes = $results["hits"]["hits"][$j]["_source"]["u_likes"];
                    else
                        $u_likes = 0;

                    if (isset($results["hits"]["hits"][$j]["_source"]["u_following"]))
                        $u_following = $results["hits"]["hits"][$j]["_source"]["u_following"];
                    else
                        $u_following = 0;

                    if (isset($results["hits"]["hits"][$j]["_source"]["u_latitude"]) && isset($results["hits"]["hits"][$j]["_source"]["u_longitude"]))
                    {
                        $coordinates_str .= 'L.marker([' . $results["hits"]["hits"][$j]["_source"]['u_latitude'] . ', ' . $results["hits"]["hits"][$j]["_source"]['u_longitude'] . '], {icon: myIcon}).addTo(map).bindPopup("<div style=\'min-width: 230px;\'><div style=\'float:left;\'><img src=\'' . $results["hits"]["hits"][$j]["_source"]["u_profile_photo"] . '\' width=\'40\' height=\'40\'></div><div style=\'margin-left: 10px; float:left; width: 170px;\'><b>Source:</b> <a href=\'' . $results["hits"]["hits"][$j]["_source"]["p_url"] . '\' target=\'_blank\'>' . $results["hits"]["hits"][$j]["_source"]["source"] . '</a><br><b>Name:</b> ' . $name . '<br><b>Post Shares:</b> ' . $p_shares . '<br><b>Post likes:</b> ' . $p_likes . '<br><b>User Followers:</b> ' . $u_followers . '<br><b>User Following:</b> ' . $u_following . '<br><b>User Likes:</b> ' . $u_likes . '</div><div style=\'clear: both;\'></div></div>");';
                        
                    }
                }
                
                echo $coordinates_str;
            }
            else if($request["section"] == 'get_printmedia_posts_html_data')
            {
                $date_from = '';
                $date_to = '';
                
                if(isset($request["date_from"]) && $request["date_from"])
                {
                    //$date_from = date("Y-m-d", strtotime($request["date_from"]));
                    $date_from = date_create_from_format('j F, Y', $request["date_from"]);
                    $date_from = date_format($date_from, 'Y-m-d');
                }
                else
                    $date_from = date("Y-m-d", strtotime('-90 day', strtotime(date("Y-m-d"))));
                
                if(isset($request["date_to"]) && $request["date_to"])
                {
                    //$date_to = date("Y-m-d", strtotime($request["date_to"]));
                    $date_to = date_create_from_format('j F, Y', $request["date_to"]);
                    $date_to = date_format($date_to, 'Y-m-d');
                }
                else
                    $date_to = date("Y-m-d");
                
                $pm_data_email = $this->cus_obj->check_printmedia_access();
                
                if (isset($request["tags"]) && !empty($request["tags"]))
                {
                    $topic_urls = '';
                    $topic_key_hash = '';

                    $tags_str = $request["tags"];

                    $tags_array = explode(",", $tags_str);

                    for ($i = 0; $i < count($tags_array); $i++)
                    {
                        if (substr($tags_array[$i], 0, 4) == 'http') //means url is added
                        {
                            $topic_urls .= '"' . $tags_array[$i] . '" ' . $request["opr"] . ' ';
                        }
                        else
                        {
                            $topic_key_hash .= '"' . $tags_array[$i] . '" ' . $request["opr"] . ' ';
                        }
                    }

                    if ($request['opr'] == 'OR')
                        $topic_key_hash = substr($topic_key_hash, 0, -4);
                    else
                        $topic_key_hash = substr($topic_key_hash, 0, -5);

                    if ($request['opr'] == 'OR')
                        $topic_urls = substr($topic_urls, 0, -4);
                    else
                        $topic_urls = substr($topic_urls, 0, -5);

                    if (!empty($topic_key_hash) && !empty($topic_urls))
                        $str_to_search = '(p_message:(' . $topic_key_hash . ' OR ' . $topic_urls . ') OR u_username:(' . $topic_key_hash . ') OR u_fullname:(' . $topic_key_hash . ') OR u_source:(' . $topic_urls . '))';

                    if (!empty($topic_key_hash) && empty($topic_urls))
                        $str_to_search = 'p_message:(' . $topic_key_hash . ')';

                    if (empty($topic_key_hash) && !empty($topic_urls))
                        $str_to_search = 'u_source:(' . $topic_urls . ')';
                    
                    $topic_query_string = $str_to_search;
                }
                
                $pm_company = '';
                
                /*if($pm_data_email == 'omran.om')
                    $pm_company = 'Omran';
                else if($pm_data_email == 'medcoman.com' || $pm_data_email == 'holding.nama.om')
                    $pm_company = 'MEDC';
                else if($pm_data_email == 'beah.om')
                    $pm_company = 'Beah';*/
                
                $params = [
                    'index' => $this->printmedia_search_index_name,
                    'type' => 'mytype',
                    'from' => '0',
                    'size' => '100',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    [ 'query_string' => [ 'query' => $topic_query_string ] ], // . ' AND company:("'.$pm_company.'")'
                                    [ 'range' => [ 'p_created_time' => [ 'gte' => $date_from, 'lte' => $date_to ] ] ]
                                ]
                            ]
                        ],
                        'sort' => [
                            [ 'p_created_time' => ['order' => 'desc'] ]
                        ]
                    ]
                ];
                //Log::info("Here1");
                //Log::info($params);
                //echo '<pre>';
                //print_r($params);
                $results = $this->client->search($params);
                //print_r($results);
                $manual_print_media_post_html = '<div style="width: 100%; padding: 25px 0px 25px 25px;"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td>';
                
                if(count($results["hits"]["hits"]) > 0)
                {
                    for($i=0; $i<count($results["hits"]["hits"]); $i++)
                    {
                        $src_data = DB::select("SELECT * FROM news_sources WHERE source_id = ".$results["hits"]["hits"][$i]["_source"]["source"]);

                        $pic = 'https://dashboard.datalyticx.ai/printmedia-data-entry/assets/uploads/news_sources/'.$this->gen_func_obj->decrypt($src_data[0]->source_image, $this->gen_func_obj->get_encryption_key());

                        $emotion_icon = '&nbsp;';

                        $post_id = $results["hits"]["hits"][$i]["_source"]["p_id"];

                        if(is_file("https://dashboard.datalyticx.ai/printmedia-data-entry/assets/uploads/".$results["hits"]["hits"][$i]["_source"]["post_full_detail_doc"]) && file_exists("https://dashboard.datalyticx.ai/printmedia-data-entry/assets/uploads/".$results["hits"]["hits"][$i]["_source"]["post_full_detail_doc"]))
                        {
                            if (false === file_get_contents("https://dashboard.datalyticx.ai/printmedia-data-entry/assets/uploads/".$results["hits"]["hits"][$i]["_source"]["post_full_detail_doc"],0,null,0,1))
                                $detail_url = $results["hits"]["hits"][$i]["_source"]["p_url"];
                        }
                        else
                            $detail_url = 'https://dashboard.datalyticx.ai/observer/pmd?pmdid='.$this->gen_func_obj->encrypt($this->gen_func_obj->encrypt($post_id, $this->gen_func_obj->get_encryption_key()), $this->gen_func_obj->get_encryption_key());

                        $manual_print_media_post_html .= '
                        <div style="width: 100%; float: left; margin: 0px 0px 25px -25px;">
                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td>
                                    <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                        <tr>
                                            <td style="background: #ffffff !important; border:1px solid #f0f0f0; padding:15px;">
                                                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td style="width:20%;"><img src="'.$pic.'" width="70" height="70"></td>
                                                        <td style="width: 80%;"><h5 style="padding-bottom:6px; margin:0px;">'.$results["hits"]["hits"][$i]["_source"]["title"].'</h5><span style="padding-top:5px; color:#8b91a0;"></span></td>
                                                    </tr>
                                                    <tr><td colspan="2">'.date("j M, Y h:i a", strtotime('+4 hours', strtotime($results["hits"]["hits"][$i]["_source"]["p_created_time"]))).'</td></tr>
                                                    <tr><td>&nbsp;</td></tr>
                                                    <tr><td colspan="2"><div style="height: 50px; overflow-x: hidden; overflow-y: auto;">'.strip_tags($results["hits"]["hits"][$i]["_source"]["p_message"]).'</div></td></tr>
                                                    <tr>
                                                        <td colspan="2">
                                                            <table width="100%">
                                                                <tr>
                                                                    <td style="padding-top: 25px;">Source: '.$this->gen_func_obj->decrypt($src_data[0]->source_name, $this->gen_func_obj->get_encryption_key()).'&nbsp;&nbsp;|&nbsp;&nbsp;Reach: '.number_format($this->gen_func_obj->decrypt($src_data[0]->source_reach, $this->gen_func_obj->get_encryption_key())).'<br><a href="'.$detail_url.'" target="_blank">Read more</a></td>
                                                                    </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                        </div>

                        ';
                    }
                }
                else
                    $manual_print_media_post_html = 'No results found.';
                
                $manual_print_media_post_html .= '</td></tr></table></div>';
                echo $manual_print_media_post_html;
            }
            else if($request["section"] == 'get_reviews_posts_html_data')
            {
                $rquery = '';
                
                if(isset($request["var1"]) && $request["var1"] == 'subtopic')
                    $rquery = 'p_message_text:('.$this->subtopic_obj->get_subtopic_keywords_es(\Session::get('current_loaded_subtopic')).') AND ';
                
                /*if(isset($request["var2"]) && !empty($request["var2"]))
                {
                    $dates = explode("|", $request["var2"]);
                    
                    if($dates[0] == 'fixed')
                    {
                        $greater_than_time = 'now-'.$dates[1].'d';
                        $less_than_time = 'now';
                    }
                    else
                    {
                        $greater_than_time = $dates[0];
                        $less_than_time = $dates[1];
                    }
                }*/
                                               
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'from' => '0',
                    'size' => '150',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    [ 'query_string' => [ 'query' => $rquery.'review_customer:("'.$this->cus_obj->get_customer_review_elastic_id().'") AND source:("'.$request["review_type"].'") AND manual_entry_type:("review")' ] ],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ],
                        'sort' => [
                            [ 'p_created_time' => ['order' => 'desc'] ]
                        ]
                    ]
                ];
                
                //echo '<pre>';
                //dd($params);
                $results = $this->client->search($params);
                //print_r($results);
                $reviews_post_html = '<div style="width: 100%; padding: 25px 0px 25px 25px;"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td>';
                
                if(count($results["hits"]["hits"]) > 0)
                {
                    for($i=0; $i<count($results["hits"]["hits"]); $i++)
                    {
                        $emotion_icon = '&nbsp;';
                        
                        $star_rating = '';
                        $sentiment_str = '';
                        
                        if(isset($results["hits"]["hits"][$i]["_source"]["p_likes"]) && $results["hits"]["hits"][$i]["_source"]["p_likes"] > 0)
                        {
                            for($t=0; $t<$results["hits"]["hits"][$i]["_source"]["p_likes"]; $t++)
                            {
                                $star_rating .= '<i class="bx bxs-star" style="color:#ffcd3c;"></i>';
                            }
                            
                            if($results["hits"]["hits"][$i]["_source"]["p_likes"] > 3)
                                $sentiment_str = '<i class="bx bx-happy" style="font-size:1.5em; color:green;" title="Positive sentiment"></i>';
                            else if($results["hits"]["hits"][$i]["_source"]["p_likes"] == 2 || $results["hits"]["hits"][$i]["_source"]["p_likes"] == 3)
                                $sentiment_str = '<i class="bx bx-meh" style="font-size:1.5em; color:blue;" title="Neutral sentiment"></i>';
                            else if($results["hits"]["hits"][$i]["_source"]["p_likes"] == 1)
                                $sentiment_str = '<i class="bx bx-sad" style="font-size:1.5em; color:red;" title="Negative sentiment"></i>';
                        }

                        $reviews_post_html .= '
                        <div style="width: 100%; float: left; margin: 0px 0px 25px -25px;">
                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td>
                                    <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                        <tr>
                                            <td style="background: #ffffff !important; border:1px solid #f0f0f0; padding:15px;">
                                                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td style="width:5%; background: #f0f0f0;">&nbsp;</td>
                                                        <td style="width: 95%;"><h5 style="padding-left:6px; margin:0px; font-size:16px;">'.$results["hits"]["hits"][$i]["_source"]["u_fullname"].'</h5><span style="padding-top:5px; color:#8b91a0;"></span></td>
                                                    </tr>
                                                    <tr><td colspan="2">'.date("j M, Y", strtotime('+4 hours', strtotime($results["hits"]["hits"][$i]["_source"]["p_created_time"]))).'</td></tr>
                                                    <tr><td colspan="2"><div style="float:left;">'.$star_rating.'</div><div style="float:left;">&nbsp;&nbsp;|&nbsp;&nbsp;</div><div style="float:left;">'.$sentiment_str.'</div></td></tr>
                                                    <tr><td colspan="2"><div style="padding-top:5px;">'.strip_tags($results["hits"]["hits"][$i]["_source"]["p_message"]).'</div></td></tr>
                                                    <tr>
                                                        <td colspan="2">
                                                            <table width="100%">
                                                                <tr>
                                                                    <td style="padding-top: 0px;"><input type="hidden" name="eid" value="'.$results["hits"]["hits"][$i]["_id"].'"></td>
                                                                    </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                        </div>

                        ';
                    }
                }
                else
                    $reviews_post_html = 'No results found.';
                
                $reviews_post_html .= '</td></tr></table></div>';
                echo $reviews_post_html;
            }
            else if($request["section"] == 'maintopic_languages' || $request["section"] == 'subtopic_languages')
            {
                if($request["section"] == 'subtopic_languages')
                {
                    $subtopic_session_id = \Session::get('current_loaded_subtopic');
                    
                    $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
                    $topic_query_string .= ' AND '.$subtopic_query_string;
                }
                
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string ]],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];

                $results = $this->client->count($params);
                $total_mentions = $results["count"];

                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string.' AND lange_detect:("en")' ]],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];

                $results = $this->client->count($params);
                $eng_mentions = $results["count"];

                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string.' AND lange_detect:("ar")' ]],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];

                $results = $this->client->count($params);
                $ar_mentions = $results["count"];

                if($total_mentions == 0)
                    $total_mentions = 1;

                $mentions_english = number_format(($eng_mentions/$total_mentions)*100, 2);
                $mentions_arabic = number_format(($ar_mentions/$total_mentions)*100, 2);

                $other_mentions = 100 - ($mentions_english + $mentions_arabic);

                $response = $mentions_english.'|'.$mentions_arabic.'|'. number_format($other_mentions, 2);
                
                echo $response;
            }
            else if($request["section"] == 'maintopic_ave' || $request["section"] == 'subtopic_ave')
            {
                if($request["section"] == 'subtopic_ave')
                {
                    $subtopic_session_id = \Session::get('current_loaded_subtopic');
                    
                    $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
                    $topic_query_string .= ' AND '.$subtopic_query_string;
                }
                
                //Digital = From all sources other than printmedia & social media
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string.' AND source:("khaleej_times" OR "Omanobserver" OR "Time of oman" OR "Blogs" OR "FakeNews" OR "News")']],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];

                $results = $this->client->count($params);
                $digital_mentions = $results["count"];
                
                $digital_mentions = $digital_mentions*735.76;
                
                //Conventional = Only Printmedia
                $params = [
                    'index' => $this->printmedia_search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => [ 'query' => str_replace('p_message_text', 'p_message', $topic_query_string) ] ],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];

                $results = $this->client->count($params);

                $conventional_mentions = $results["count"];
                $conventional_mentions = $conventional_mentions*3276.45;
                
                echo number_format($digital_mentions).'|'.number_format($conventional_mentions);
            }
            else if($request["section"] == 'maintopic_keywords_bar_chart')
            {
                $key_hash_array = array();
                
                $key_hash = $this->topic_obj->get_topic_hash_keywords(\Session::get('current_loaded_project'));
                
                $keywords = explode(",", $key_hash[0]->topic_keywords);
                
                if($keywords[0] != "")
                {
                    for($i=0; $i<count($keywords); $i++)
                    {
                        $key_hash_array[] = trim($keywords[$i]);
                    }
                }                
                
                $hash_tags = explode("|", $key_hash[0]->topic_hash_tags);
                
                if($hash_tags[0] != "")
                {
                    for($i=0; $i<count($hash_tags); $i++)
                    {
                        $key_hash_array[] = trim($hash_tags[$i]);
                    }
                }
                
                $response_array = array();
                
                for($i=0; $i<count($key_hash_array); $i++)
                {
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string . ' AND  p_message_text:("'.$key_hash_array[$i].'")' ]],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                    ]
                                ]
                            ]
                        ]
                    ];

                    $results = $this->client->count($params);
                    
                    $response_array[$i]["key_count"] = $results["count"];
                    $response_array[$i]["keyword"] = $key_hash_array[$i];
                }
                
                echo json_encode($response_array);
            }
            else if($request["section"] == 'maintopic_country_bar_chart')
            {
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'size' => '0',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string]],
                                    ['exists' => ['field' => 'u_country']],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ],
                        'aggs' => [
                            'group_by_country' => ['terms' => ['field' => 'u_country.keyword', 'size' => 15]]
                        ]
                    ]
                ];

                $results = $this->client->search($params);

                $new_country_array = array();

                for ($i = 0; $i < count($results["aggregations"]["group_by_country"]["buckets"]); $i++)
                {
                    if (!empty($results["aggregations"]["group_by_country"]["buckets"][$i]["key"]))
                        $new_country_array[$results["aggregations"]["group_by_country"]["buckets"][$i]["key"]] = $results["aggregations"]["group_by_country"]["buckets"][$i]["doc_count"];
                }
                //echo '<pre>'; print_r($new_country_array);
                arsort($new_country_array);
                //echo '<pre>'; print_r($new_country_array);
                
                $i = 0;
                $response_array = array();
                
                foreach ($new_country_array as $country_name => $data_count)
                {
                    $response_array[$i]["key_count"] = $data_count;
                    $response_array[$i]["country_name"] = $country_name;
                    
                    $i = $i+1;
                }
                
                echo json_encode($response_array);
            }
            else if($request["section"] == 'maintopic_channel_sentiments')
            {
                $response_output = array();
                $sources_array = array("Youtube", "Twitter", "Pinterest", "Instagram", "Reddit", "Tumblr", "Facebook", "Web", "Linkedin", "GooglePlayStore", "GoogleMyBusiness", "AppleAppStore", "HuaweiAppGallery", "Glassdoor");
                //News, Blogs and Web are combined together as Web
                
                for($i=0; $i<count($sources_array); $i++)
                {
                    if($sources_array[$i] == 'Youtube')
                        $_sources = '"Youtube" OR "Vimeo"';
                    else if($sources_array[$i] == 'Web')
                        $_sources = '"FakeNews" OR "News" OR "Blogs" OR "Web"';
                    else
                        $_sources = $sources_array[$i];
                    
                    $pos_senti = 0;
                    $neg_senti = 0;
                    $neu_senti = 0;
                    
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string . ' AND source:('.$_sources.') AND predicted_sentiment_value:("positive")']],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                    ]
                                ]
                            ]
                        ]
                    ];
                    						
                    $pos_senti = $this->client->count($params);
                    
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string . ' AND source:('.$_sources.') AND predicted_sentiment_value:("negative")']],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                    ]
                                ]
                            ]
                        ]
                    ];
                    						
                    $neg_senti = $this->client->count($params);
                    
                    $params = [
                        'index' => $this->search_index_name,
                        'type' => 'mytype',
                        'body' => [
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        ['query_string' => ['query' => $topic_query_string . ' AND source:('.$_sources.') AND predicted_sentiment_value:("neutral")']],
                                        ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                    ]
                                ]
                            ]
                        ]
                    ];
                    						
                    $neu_senti = $this->client->count($params);
                    
                    if($loaded_topic_id == 2325 || $loaded_topic_id == 2388) //Sohar international bank & gdrfa
                    {
                        if($loaded_topic_id == 2388 && $sources_array[$i] == 'GooglePlayStore') //for gdrfa we have to skip google play store
                            continue;
                        
                        if($sources_array[$i] == 'GooglePlayStore' || $sources_array[$i] == 'GoogleMyBusiness' || $sources_array[$i] == 'AppleAppStore' || $sources_array[$i] == 'HuaweiAppGallery' || $sources_array[$i] == 'Glassdoor')
                        {
                            //positive
                            $params = [
                                'index' => $this->search_index_name,
                                'type' => 'mytype',
                                'body' => [
                                    'query' => [
                                        'bool' => [
                                            'must' => [
                                                ['query_string' => ['query' => 'source:("'.$sources_array[$i].'") AND manual_entry_type:("review") AND review_customer:("'.$this->cus_obj->get_customer_review_elastic_id().'")']],
                                                ['range' => ['p_likes' => ['gt' => 3]]],
                                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                            ]
                                        ]
                                    ]
                                ]
                            ];

                            $pos_senti = $this->client->count($params);
                            
                            //negative
                            $params = [
                                'index' => $this->search_index_name,
                                'type' => 'mytype',
                                'body' => [
                                    'query' => [
                                        'bool' => [
                                            'must' => [
                                                ['query_string' => ['query' => 'source:("'.$sources_array[$i].'") AND manual_entry_type:("review") AND review_customer:("'.$this->cus_obj->get_customer_review_elastic_id().'")']],
                                                ['range' => ['p_likes' => ['lt' => 2]]],
                                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                            ]
                                        ]
                                    ]
                                ]
                            ];

                            $neg_senti = $this->client->count($params);
                            
                            //neutral
                            $params = [
                                'index' => $this->search_index_name,
                                'type' => 'mytype',
                                'body' => [
                                    'query' => [
                                        'bool' => [
                                            'must' => [
                                                ['query_string' => ['query' => 'source:("'.$sources_array[$i].'") AND manual_entry_type:("review") AND review_customer:("'.$this->cus_obj->get_customer_review_elastic_id().'")']],
                                                ['range' => ['p_likes' => ['gte' => 2, 'lte' => 3]]],
                                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                            ]
                                        ]
                                    ]
                                ]
                            ];

                            $neu_senti = $this->client->count($params);
                        }
                    }

                    if($pos_senti["count"] > 0 || $neg_senti["count"] > 0 || $neu_senti["count"] > 0)
                    {
                        $response_output[$sources_array[$i]]["positive"] = $pos_senti["count"];
                        $response_output[$sources_array[$i]]["negative"] = $neg_senti["count"];
                        $response_output[$sources_array[$i]]["neutral"] = $neu_senti["count"];
                    }
                }
                
                echo json_encode($response_output);
            }
            else if($request["section"] == 'subtopic_review_sentiments')
            {
                $response_output = array();
                $sources_array = array("GooglePlayStore", "GoogleMyBusiness", "AppleAppStore", "HuaweiAppGallery", "Glassdoor");
                
                $proceed_further = false;
                
                if($this->subtopic_obj->get_subtopic_parent(\Session::get('current_loaded_subtopic')) == 2325)
                    $proceed_further = true;
                
                $pos_senti = 0;
                $neg_senti = 0;
                $neu_senti = 0;
                    
                if($proceed_further == true)
                {
                    for($i=0; $i<count($sources_array); $i++)
                    {
                        //positive
                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            ['query_string' => ['query' => 'p_message_text:('.$this->subtopic_obj->get_subtopic_keywords_es(\Session::get('current_loaded_subtopic')).') AND source:("'.$sources_array[$i].'") AND manual_entry_type:("review") AND review_customer:("'.$this->cus_obj->get_customer_review_elastic_id().'")']],
                                            ['range' => ['p_likes' => ['gt' => 3]]],
                                            ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                        ]
                                    ]
                                ]
                            ]
                        ];

                        $pos_senti = $this->client->count($params);

                        //negative
                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            ['query_string' => ['query' => 'p_message_text:('.$this->subtopic_obj->get_subtopic_keywords_es(\Session::get('current_loaded_subtopic')).') AND source:("'.$sources_array[$i].'") AND manual_entry_type:("review") AND review_customer:("'.$this->cus_obj->get_customer_review_elastic_id().'")']],
                                            ['range' => ['p_likes' => ['lt' => 2]]],
                                            ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                        ]
                                    ]
                                ]
                            ]
                        ];

                        $neg_senti = $this->client->count($params);

                        //neutral
                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            ['query_string' => ['query' => 'p_message_text:('.$this->subtopic_obj->get_subtopic_keywords_es(\Session::get('current_loaded_subtopic')).') AND source:("'.$sources_array[$i].'") AND manual_entry_type:("review") AND review_customer:("'.$this->cus_obj->get_customer_review_elastic_id().'")']],
                                            ['range' => ['p_likes' => ['gte' => 2, 'lte' => 3]]],
                                            ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                        ]
                                    ]
                                ]
                            ]
                        ];

                        $neu_senti = $this->client->count($params);

                        if($pos_senti["count"] > 0 || $neg_senti["count"] > 0 || $neu_senti["count"] > 0)
                        {
                            $response_output[$sources_array[$i]]["positive"] = $pos_senti["count"];
                            $response_output[$sources_array[$i]]["negative"] = $neg_senti["count"];
                            $response_output[$sources_array[$i]]["neutral"] = $neu_senti["count"];
                        }
                    }
                }
                
                echo json_encode($response_output);
            }
            else if($request["section"] == 'touchpoints_emotions_chart')
            {
                $response_output = array();
                $emotions_array = array("Anger", "Fear", "Happy", "Sadness", "Surprise");
                
                $touchpoints_ids = $this->touchpoint_obj->get_all_tp_ids();
                
                if($touchpoints_ids != 'NA')
                {
                    $subtopic_session_id = \Session::get('current_loaded_subtopic');
                    $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_session_id);
                    $topic_query_string .= ' AND '.$subtopic_query_string;
                    
                    for($i=0; $i<count($touchpoints_ids); $i++)
                    {
                        $anger_emo = 0;
                        $fear_emo = 0;
                        $happy_emo = 0;
                        $sadness_emo = 0;
                        $surprise_emo = 0;
                        
                        $tp_es_query = $this->touchpoint_obj->get_touchpoint_elastic_query($touchpoints_ids[$i]->cx_tp_tp_id);
                        
                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            ['query_string' => ['query' => $topic_query_string . ' AND '.$tp_es_query.' AND emotion_detector:("Anger")']],
                                            ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                        ]
                                    ]
                                ]
                            ]
                        ];

                        $anger_emo = $this->client->count($params);
                        
                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            ['query_string' => ['query' => $topic_query_string . ' AND '.$tp_es_query.' AND emotion_detector:("Fear")']],
                                            ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                        ]
                                    ]
                                ]
                            ]
                        ];

                        $fear_emo = $this->client->count($params);
                        
                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            ['query_string' => ['query' => $topic_query_string . ' AND '.$tp_es_query.' AND emotion_detector:("Happy")']],
                                            ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                        ]
                                    ]
                                ]
                            ]
                        ];

                        $happy_emo = $this->client->count($params);
                        
                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            ['query_string' => ['query' => $topic_query_string . ' AND '.$tp_es_query.' AND emotion_detector:("Sadness")']],
                                            ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                        ]
                                    ]
                                ]
                            ]
                        ];

                        $sadness_emo = $this->client->count($params);
                        
                        $params = [
                            'index' => $this->search_index_name,
                            'type' => 'mytype',
                            'body' => [
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            ['query_string' => ['query' => $topic_query_string . ' AND '.$tp_es_query.' AND emotion_detector:("Surprise")']],
                                            ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                        ]
                                    ]
                                ]
                            ]
                        ];

                        $surprise_emo = $this->client->count($params);
                        
                        if($anger_emo["count"] > 0 || $fear_emo["count"] > 0 || $happy_emo["count"] > 0 || $sadness_emo["count"] > 0 || $surprise_emo["count"] > 0)
                        {
                            $tp_data = $this->touchpoint_obj->get_touchpoint_data($touchpoints_ids[$i]->cx_tp_tp_id);
                            
                            $response_output[$tp_data[0]->tp_name]["Anger"] = $anger_emo["count"];
                            $response_output[$tp_data[0]->tp_name]["Fear"] = $fear_emo["count"];
                            $response_output[$tp_data[0]->tp_name]["Happy"] = $happy_emo["count"];
                            $response_output[$tp_data[0]->tp_name]["Sadness"] = $sadness_emo["count"];
                            $response_output[$tp_data[0]->tp_name]["Surprise"] = $surprise_emo["count"];
                        }
                        
                    }
                    echo json_encode($response_output);
                }
            }
            else if($request["section"] == 'download_excel_data')
            {
                $unique_id = time().'_'.uniqid();
                $file_name = $unique_id.'.xlsx';
                
                $xlsx_headers  = array("Serial"=>"string","Sentiment"=>"string","Comments"=>"string","Likes"=>"string","Isshared"=>"string","VideoURL"=>"string","UserName"=>"string","UserLikes"=>"string","UserFollowing"=>"string","UserFollowers"=>"string","UserSource"=>"string","Source"=>"string","PostCreated"=>"string","PostUrl"=>"string");
                
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'from' => '0',
                    'size' => '500',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string ]],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];
                
                $results = $this->client->search($params);
                
                $j = 1;
                for($i=0; $i<count($results["hits"]["hits"]); $i++)
                {
                    $filtered_data[] = array($j, 
                        isset($results["hits"]["hits"][$i]["_source"]["predicted_sentiment_value"]) ? ucfirst($results["hits"]["hits"][$i]["_source"]["predicted_sentiment_value"]) : '', 
                        isset($results["hits"]["hits"][$i]["_source"]["p_comments"]) ? $results["hits"]["hits"][$i]["_source"]["p_comments"] : '', 
                        isset($results["hits"]["hits"][$i]["_source"]["p_likes"]) ? $results["hits"]["hits"][$i]["_source"]["p_likes"] : '', 
                        isset($results["hits"]["hits"][$i]["_source"]["p_is_shared"]) ? $results["hits"]["hits"][$i]["_source"]["p_is_shared"] : '', 
                        isset($results["hits"]["hits"][$i]["_source"]["p_video_url"]) ? $results["hits"]["hits"][$i]["_source"]["p_video_url"] : '', 
                        isset($results["hits"]["hits"][$i]["_source"]["u_fullname"]) ? $results["hits"]["hits"][$i]["_source"]["u_fullname"] : '', 
                        isset($results["hits"]["hits"][$i]["_source"]["u_likes"]) ? $results["hits"]["hits"][$i]["_source"]["u_likes"] : '', 
                        isset($results["hits"]["hits"][$i]["_source"]["u_following"]) ? $results["hits"]["hits"][$i]["_source"]["u_following"] : '', 
                        isset($results["hits"]["hits"][$i]["_source"]["u_followers"]) ? $results["hits"]["hits"][$i]["_source"]["u_followers"] : '', 
                        isset($results["hits"]["hits"][$i]["_source"]["u_source"]) ? $results["hits"]["hits"][$i]["_source"]["u_source"] : '', 
                        isset($results["hits"]["hits"][$i]["_source"]["source"]) ? $results["hits"]["hits"][$i]["_source"]["source"] : '', 
                        isset($results["hits"]["hits"][$i]["_source"]["p_created_time"]) ? $results["hits"]["hits"][$i]["_source"]["p_created_time"] : '', 
                        isset($results["hits"]["hits"][$i]["_source"]["p_url"]) ? $results["hits"]["hits"][$i]["_source"]["p_url"] : '');
                    $j = $j+1;
                }
                
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment;filename="'.$file_name.'"');
                header('Cache-Control: max-age=0');

                $writer = new XLSXWriter();
                $writer->setAuthor('myCards Inc.');
                $writer->writeSheet($filtered_data, $unique_id, $xlsx_headers);
                //$writer->writeToStdOut();
                $writer->writeToFile('/var/www/html/datalyticx-live/public/xlxs_docs/'.$unique_id.'.xlsx');
                //exit(0);
                echo 'https://dashboard.datalyticx.ai/xlxs_docs/'.$unique_id.'.xlsx';
            }
            else if($request["section"] == 'popular_posts')
            {
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'from' => '0',
                    'size' => '15',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string ]],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ],
                        'sort' => [
                            ['p_likes' => ['order' => 'desc']]
                        ]
                    ]
                ];
                
                $results = $this->client->search($params);
                
                $pop_posts_html = '';
                for($l=0; $l<15; $l++)
                {
                    $pop_posts_html .= '<div class="swiper-slide" style="border: 0px !important; padding: 0px !important; width: 34% !important; text-align:left !important;">';
                    $pop_posts_html .= $this->gen_func_obj->get_postview_html($results["hits"]["hits"][$l]);
                    $pop_posts_html .= '</div>';
                }
                
                echo $pop_posts_html.'<div class="clear:both;"></div>';
            }
            else if($request["section"] == 'get_post_comments')
            {
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => 'p_url:("'.$request["purl"].'")' ]]
                                ]
                            ]
                        ]
                    ]
                ];
                
                $results = $this->client->search($params);
                
                $comments_html = ''; $predicted_sentiment = ''; $pos = 0; $neg = 0; $neu = 0;
                
                if(isset($results["hits"]["hits"][0]["_source"]["p_comments_text"]) && !empty($results["hits"]["hits"][0]["_source"]["p_comments_text"]))
                {
                    $comments_json = $this->gen_func_obj->fix_json($results["hits"]["hits"][0]["_source"]["p_comments_text"]);
                    
                    $comments_data = json_decode($comments_json, true);
                    
                    for($k=0; $k<count($comments_data); $k++)
                    {
                        if ($comments_data[$k]["predicted_sentiment_value"] == 'positive')
                        {
                            $pos = $pos + 1;
                            $predicted_sentiment = '<i class="bx bx-happy" style="font-size:1.6em; color:green; float:right;" title="Positive sentiment"></i>';
                        }
                        else if ($comments_data[$k]["predicted_sentiment_value"] == 'negative')
                        {
                            $neg = $neg + 1;
                            $predicted_sentiment = '<i class="bx bx-sad" style="font-size:1.6em; color:red; float:right;" title="Negative sentiment"></i>';
                        }
                        else if ($comments_data[$k]["predicted_sentiment_value"] == 'neutral')
                        {
                            $neu = $neu + 1;
                            $predicted_sentiment = '<i class="bx bx-meh" style="font-size:1.6em; color:blue; float:right;" title="Neutral sentiment"></i>';
                        }
            
                        $comments_html .= '<div style="width:100%; background:#f0f0f0; padding: 15px; margin-bottom: 15px; border-radius: 8px;">';
                        
                        $comments_html .= '<p>'
                            . '<a href="'.$comments_data[$k]["u_source"].'" target="_blank">'.$comments_data[$k]["u_fullname"].'</a> '.$predicted_sentiment.'<br>'
                            . '<b>'.date("Y-m-d h:i A", strtotime($comments_data[$k]["p_created_time"])).'</b><br>'
                            . $comments_data[$k]["u_fullname"].'<br>'
                            . $comments_data[$k]["p_message"].'<br>'
                            . '</p>';
                        
                        $comments_html .= '</div>';
                    }
                }
                
                //echo $comments_html;
                /*$resp_array = array();
                
                $resp_array["comments_html"] = $comments_html;
                $resp_array["pos_senti"] = $pos;
                $resp_array["neg_senti"] = $neg;
                $resp_array["neu_senti"] = $neu;

                echo json_encode($resp_array);*/

                return response()->json([
                            'comments_html' => $comments_html,
                            'pos_senti' => $pos,
                            'neg_senti' => $neg,
                            'neu_senti' => $neu,
                        ]);
            }
            else if($request["section"] == 'dashboard_comments_sentiments')
            {
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'from' => '0',
                    'size' => '2000',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string ]],
                                    ['exists' => ['field' => 'p_comments_text']],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ],
                        'sort' => [
                            ['p_created_time' => ['order' => 'desc']]
                        ]
                    ]
                ];
                
                $results = $this->client->search($params);

                $pos = 0; $neg = 0; $neu = 0; 

                if(count($results["hits"]["hits"]) > 0)
                {
                    for($i=0; $i<count($results["hits"]["hits"]); $i++)
                    {
                        if(isset($results["hits"]["hits"][$i]["_source"]["p_comments_text"]) && !empty($results["hits"]["hits"][$i]["_source"]["p_comments_text"]))
                        {
                            $comments_json = $results["hits"]["hits"][$i]["_source"]["p_comments_text"];

                            try {
                                //$comments_json = $this->gen_func_obj->fix_json($results["hits"]["hits"][$i]["_source"]["p_comments_text"]);
                            }
                            catch(Exception $e)
                            {
                                Log::info("6439 line error".$e);
                            }
                            
                            
                            $comments_data = json_decode($comments_json, true);
                            //print_r($comments_data);
                            if(is_array($comments_data))
                            {
                                for($k=0; $k<count($comments_data); $k++)
                                {
                                    if ($comments_data[$k]["predicted_sentiment_value"] == 'positive')
                                        $pos = $pos + 1;
                                    else if ($comments_data[$k]["predicted_sentiment_value"] == 'negative')
                                        $neg = $neg + 1;
                                    else if ($comments_data[$k]["predicted_sentiment_value"] == 'neutral')
                                        $neu = $neu + 1;
                                }
                            }                            
                        }   
                    }                    
                }

                echo 'Positive,'.$pos.'|Negative,'.$neg.'|Neutral,'.$neu;
            }
        }
    }
    
    
}

?>
