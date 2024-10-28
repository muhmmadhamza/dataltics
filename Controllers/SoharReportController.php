<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\SubTopicController;
use App\Http\Controllers\TouchpointController;
use App\Http\Controllers\CustomerController;

/*
 * This class is used to generate special monthly report on demand for Sohar Intl
 */
class SoharReportController extends Controller
{
    public function __construct()
    {
        $this->client = ClientBuilder::create()->setHosts([env('ELASTICSEARCH_HOST').":".env('ELASTICSEARCH_PORT')])->build();
        $this->search_index_name = env('ELASTICSEARCH_DEFAULTINDEX');
        
        $this->topic_obj = new TopicController();
        $this->cus_obj = new CustomerController();
    }
    
    public function generate_report_stats(Request $request)
    {
        if(isset($request->month) && !empty($request->month))
        {
            $timestamp = strtotime($request->month.' '.date("Y"));
            $greater_than_time = date('Y-m-01', $timestamp);
            $less_than_time  = date('Y-m-t', $timestamp);
            
            $first_date = strtotime('first day of previous month', strtotime($greater_than_time));
            $previous_month_first_date=date('Y-m-d', $first_date);
            $previous_month_last_date=date('Y-m-t', $first_date);
            
            //$prev_greater_than_time = date("Y-m-d", strtotime('-30 day', strtotime($greater_than_time)));
            $prev_greater_than_time = $previous_month_first_date;
            $prev_less_than_time = $previous_month_last_date;
            
            $topic_query_string = $this->topic_obj->get_topic_elastic_query('2325');
            
            //community size (followers)
            /*$params = [
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
                        'total_followers' => ['sum' => ['field' => 'u_followers']]
                    ]
                ]
            ];*/
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'size' => '0',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => '(u_source:("https://www.facebook.com/sohar.intl" OR "https://www.instagram.com/sohar_intl/" OR "https://twitter.com/sohar_intl" OR "https://www.linkedin.com/company/soharinternational" OR "https://www.youtube.com/c/SoharInternational"))']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
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
                    /*'aggs' => [
                        'unique_users' => ['cardinality' => ['field' => 'u_source.keyword']],
                        'sum_followers' => ['sum' => ['field' => 'u_followers']]
                    ]*/
                ]
            ];
            
            $results = $this->client->search($params);
            //echo '<pre>'; print_r($params); print_r($results); exit;
            $current_followers = 0;
            for($i=0; $i<count($results["aggregations"]["group_by_user"]["buckets"]); $i++)
            {
                if(isset($results["aggregations"]["group_by_user"]["buckets"][$i]["followers_count"]["value"]) && $results["aggregations"]["group_by_user"]["buckets"][$i]["followers_count"]["value"] > 0)
                    $current_followers = $current_followers + $results["aggregations"]["group_by_user"]["buckets"][$i]["followers_count"]["value"];
            }
            //$results = $this->client->search($params);
            //$current_followers = $results["aggregations"]["total_followers"]["value"];
            
            /*$params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'size' => '0',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string]],
                                ['range' => ['p_created_time' => ['gte' => $prev_greater_than_time, 'lt' => $greater_than_time]]]
                            ]
                        ]
                    ],
                    'aggs' => [
                        'total_followers' => ['sum' => ['field' => 'u_followers']]
                    ]
                ]
            ];*/
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'size' => '0',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => '(u_source:("https://www.facebook.com/sohar.intl" OR "https://www.instagram.com/sohar_intl/" OR "https://twitter.com/sohar_intl" OR "https://www.linkedin.com/company/soharinternational" OR "https://www.youtube.com/c/SoharInternational"))']],
                                ['range' => ['p_created_time' => ['gte' => $prev_greater_than_time, 'lte' => $prev_less_than_time]]]
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
                    /*'aggs' => [
                        'unique_users' => ['cardinality' => ['field' => 'u_source.keyword']],
                        'sum_followers' => ['sum' => ['field' => 'u_followers']]
                    ]*/
                ]
            ];
            
            $results = $this->client->search($params);
            $prev_followers = 0;
            for($i=0; $i<count($results["aggregations"]["group_by_user"]["buckets"]); $i++)
            {
                if(isset($results["aggregations"]["group_by_user"]["buckets"][$i]["followers_count"]["value"]) && $results["aggregations"]["group_by_user"]["buckets"][$i]["followers_count"]["value"] > 0)
                    $prev_followers = $prev_followers + $results["aggregations"]["group_by_user"]["buckets"][$i]["followers_count"]["value"];
            }
            //$prev_followers = $results["aggregations"]["total_followers"]["value"];
            
            if($current_followers > $prev_followers)
            {
                $diff = $current_followers - $prev_followers;
                $per_diff = ($diff/$current_followers)*100;
                $followers_per_diff = number_format($per_diff, 2).'%';
            }
            else
            {
                $diff = $prev_followers - $current_followers;
                $per_diff = ($diff/$prev_followers)*100;
                $followers_per_diff = '-'.number_format($per_diff, 2).'%';
            }
            //End: followers
            
            //Engagement
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

            $results = $this->client->search($params);
            $tot_eng = $results["aggregations"]["total_shares"]["value"] + $results["aggregations"]["total_comments"]["value"] + $results["aggregations"]["total_likes"]["value"]; // + $results["aggregations"]["total_views"]["value"];

            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string ]],
                                ['range' => ['p_created_time' => ['gte' => $prev_greater_than_time, 'lte' => $prev_less_than_time]]]
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
            $tot_eng1 = $results1["aggregations"]["total_shares"]["value"] + $results1["aggregations"]["total_comments"]["value"] + $results1["aggregations"]["total_likes"]["value"]; // + $results["aggregations"]["total_views"]["value"];

            if($tot_eng > $tot_eng1)
            {
                $diff = $tot_eng - $tot_eng1;
                $per_diff = ($diff/$tot_eng)*100;
                $eng_per_diff = number_format($per_diff, 2).'%';
            }
            else
            {
                $diff = $tot_eng1 - $tot_eng;
                $per_diff = ($diff/$tot_eng1)*100;
                $eng_per_diff = '-'.number_format($per_diff, 2).'%';
            }
            //End: Engagement
            
            //Shares
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
                        'total_shares' => ['sum' => ['field' => 'p_shares']]
                    ]
                ]
            ];
            
            $results = $this->client->search($params);

            $current_shares = $results["aggregations"]["total_shares"]["value"];
            
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'size' => '0',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string]],
                                ['range' => ['p_created_time' => ['gte' => $prev_greater_than_time, 'lt' => $prev_less_than_time]]]
                            ]
                        ]
                    ],
                    'aggs' => [
                        'total_shares' => ['sum' => ['field' => 'p_shares']]
                    ]
                ]
            ];
            
            $results = $this->client->search($params);

            $prev_shares = $results["aggregations"]["total_shares"]["value"];
            
            if($current_shares > $prev_shares)
            {
                $diff = $current_shares - $prev_shares;
                $per_diff = ($diff/$current_shares)*100;
                $shares_per_diff = number_format($per_diff, 2).'%';
            }
            else
            {
                $diff = $prev_shares - $current_shares;
                $per_diff = ($diff/$prev_shares)*100;
                $shares_per_diff = '-'.number_format($per_diff, 2).'%';
            }
            //End: Shares
            
            //Likes
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
                        'total_likes' => ['sum' => ['field' => 'p_likes']]
                    ]
                ]
            ];
            
            $results = $this->client->search($params);

            $current_likes = $results["aggregations"]["total_likes"]["value"];
            
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'size' => '0',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string]],
                                ['range' => ['p_created_time' => ['gte' => $prev_greater_than_time, 'lt' => $prev_less_than_time]]]
                            ]
                        ]
                    ],
                    'aggs' => [
                        'total_likes' => ['sum' => ['field' => 'p_likes']]
                    ]
                ]
            ];
            
            $results = $this->client->search($params);

            $prev_likes = $results["aggregations"]["total_likes"]["value"];
            
            if($current_likes > $prev_likes)
            {
                $diff = $current_likes - $prev_likes;
                $per_diff = ($diff/$current_likes)*100;
                $likes_per_diff = number_format($per_diff, 2).'%';
            }
            else
            {
                $diff = $prev_likes - $current_likes;
                $per_diff = ($diff/$prev_likes)*100;
                $likes_per_diff = '-'.number_format($per_diff, 2).'%';
            }
            //End: Likes
            
            //Comments
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
                        'total_comments' => ['sum' => ['field' => 'p_comments']]
                    ]
                ]
            ];
            
            $results = $this->client->search($params);

            $current_comments = $results["aggregations"]["total_comments"]["value"];
            
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'size' => '0',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string]],
                                ['range' => ['p_created_time' => ['gte' => $prev_greater_than_time, 'lt' => $prev_less_than_time]]]
                            ]
                        ]
                    ],
                    'aggs' => [
                        'total_comments' => ['sum' => ['field' => 'p_comments']]
                    ]
                ]
            ];
            
            $results = $this->client->search($params);

            $prev_comments = $results["aggregations"]["total_comments"]["value"];
            
            if($current_comments > $prev_comments)
            {
                $diff = $current_comments - $prev_comments;
                $per_diff = ($diff/$current_comments)*100;
                $comments_per_diff = number_format($per_diff, 2).'%';
            }
            else
            {
                $diff = $prev_comments - $current_comments;
                $per_diff = ($diff/$prev_comments)*100;
                $comments_per_diff = '-'.number_format($per_diff, 2).'%';
            }
            //End: Shares
            
            //Impressions / reach
            //estimated reach 50% normal user followers + 5% influencers + total engagement
            
            //Current month
            /*$params = [
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
                        'total_likes' => ['sum' => ['field' => 'p_likes']]
                    ]
                ]
            ];

            $results = $this->client->search($params);
            $tot_eng = $results["aggregations"]["total_shares"]["value"] + $results["aggregations"]["total_comments"]["value"] + $results["aggregations"]["total_likes"]["value"];
            
            $estimated_reach = $normal_user_followers + $influencer_user_followers + $tot_eng;
            
            //previous month reach
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'size' => '0',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string]],
                                ['range' => ['p_created_time' => ['gte' => $prev_greater_than_time, 'lte' => $greater_than_time]]],
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
                                ['range' => ['p_created_time' => ['gte' => $prev_greater_than_time, 'lte' => $greater_than_time]]],
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
                                ['range' => ['p_created_time' => ['gte' => $prev_greater_than_time, 'lte' => $greater_than_time]]]
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
            
            $estimated_reach_prev = $normal_user_followers + $influencer_user_followers + $tot_eng;*/
            //End: Impressions / reach
            
            //No of posts
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
            
            $mentions = $results["count"];
            
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string ]],
                                ['range' => ['p_created_time' => ['gte' => $prev_greater_than_time, 'lte' => $prev_less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);
            
            $mentions1 = $results["count"];
            
            if($mentions > $mentions1)
            {
                $diff = $mentions - $mentions1;
                $per_diff = ($diff/$mentions)*100;
                $mentions_per_diff = number_format($per_diff, 2).'%';
            }
            else
            {
                $diff = $mentions1 - $mentions;
                $per_diff = ($diff/$mentions1)*100;
                $mentions_per_diff = '-'.number_format($per_diff, 2).'%';
            }
            //End: No of posts
            
            $report_month_timestamp = strtotime($request->month.' '.date("Y"));
            $last_date_of_month = date('Y-m-t', $report_month_timestamp);
            $first_date_of_month = date('Y-m-01', $report_month_timestamp);
            
            //csat
            $surveys_csat = $this->get_avg_csat_value('292', array("from_date" => $first_date_of_month, "to_date" => $last_date_of_month));
            //End: csat
            
            //surveys taken
            $surveys_info = $this->get_surveys_surveys_taken_info('292', array("from_date" => $first_date_of_month, "to_date" => $last_date_of_month));
            //End: surveys taken

            $resp_array = array(
                'sohar_report_obj' => $this,
                'month_name' => $request->month,
                'current_followers' => number_format($current_followers),
                'followers_per_diff' => $followers_per_diff,
                'engagement' => number_format($tot_eng),
                'eng_per_diff' => $eng_per_diff,
                'shares' => number_format($current_shares),
                'shares_per_diff' => $shares_per_diff,
                'likes' => number_format($current_likes),
                'likes_per_diff' => $likes_per_diff,
                'comments' => number_format($current_comments),
                'comments_per_diff' => $comments_per_diff,
                'mentions' => number_format($mentions),
                'mentions_per_diff' => $mentions_per_diff,
                'surveys_csat' => $surveys_csat.'%',
                'surveys_info' => $surveys_info
            );
            
            return view('pages.sohar-monthly-report', $resp_array);
        }
        else
            echo 'Provide full month name';
    }
    
    public function get_surveys_surveys_taken_info($cid, $params)
    {
        //$sr = DB::select("SELECT COUNT(*) AS totrec FROM survey WHERE survey_active = 'y' AND survey_pid = ".$cid);
        //$total_surveys = $sr[0]->totrec;
        //dd("SELECT COUNT(*) AS total_surveys_taken FROM `survey_response` sr, survey s WHERE s.survey_pid = ".$cid." AND s.survey_id = sr.survey_id AND (sr.time_taken >= '".$params["from_date"]."' AND sr.time_taken <= '".$params["to_date"]."') GROUP BY s.survey_id");
        $sr = DB::select("SELECT COUNT(*) AS total_surveys_taken FROM `survey_response` sr, survey s WHERE s.survey_pid = ".$cid." AND s.survey_id = sr.survey_id AND (sr.time_taken >= '".$params["from_date"]."' AND sr.time_taken <= '".$params["to_date"]."') GROUP BY s.survey_id");
        
        $total_surveys = 0;
        $branches = 0;
        //dd($sr);
        if(count($sr) > 0)
        {
            for($i=0; $i<count($sr); $i++)
            {
                $total_surveys = $total_surveys + $sr[$i]->total_surveys_taken;
                $branches = $branches + 1;
            }
        }
        
        
        
        //total surveys taken
        $total_surveys_taken = 0;
        $st = DB::select("SELECT survey_id FROM survey WHERE survey_active = 'y' AND survey_pid = ".$cid);
        
        if(count($st) > 0)
        {
            for($i=0; $i<count($st); $i++)
            {
                $total_surveys_taken = $total_surveys_taken + $this->get_total_surveys_taken($st[$i]->survey_id, $params);
            }
        }
        
        return $branches.' branches ('.$total_surveys_taken.' surveys)';
    }
    
    public function get_avg_csat_value($cid, $params)
    {
        $avg_csat = 0;
        $csat_survey_count = 0;
        
        $sur_q = DB::select("SELECT survey_id FROM survey WHERE survey_active = 'y' AND survey_pid = ".$cid);
        
        if(count($sur_q) > 0)
        {
            for($i=0; $i<count($sur_q); $i++)
            {
                $survey_csat = $this->get_survey_csat($sur_q[$i]->survey_id, $params);
                
                if($survey_csat !== 'NA') //means csat question was in survey
                {
                    $csat_survey_count = $csat_survey_count + 1;
                    
                    $avg_csat = $avg_csat + $survey_csat;
                }
            }
            
            $avg_csat = $avg_csat/$csat_survey_count;
        }
        
        return ceil($avg_csat);
    }
    
    public function get_survey_csat($sid, $params)
    {
        if(isset($sid) && !empty($sid))
        {
            $csat_array = array();
            
            $cq = DB::select("SELECT question_id FROM question WHERE survey_id = ".$sid." AND is_rate_service = 1");
                        
            if(count($cq) > 0) //csat question found
            {
                for($i=0; $i<count($cq); $i++) //while($cdata = mysqli_fetch_array($cres))
                {
                    $aq = DB::select("SELECT COUNT(*) AS totrec FROM survey_answer WHERE question_id = ".$cq[$i]->question_id." AND answer_value = 'happy' AND (created_at >= '".$params["from_date"]."' AND created_at <= '".$params["to_date"]."')");
                    
                    if(count($aq) > 0)
                    {
                        $total_surveys_taken = $this->get_total_surveys_taken($sid, $params);
                        
                        if($total_surveys_taken > 0)
                            $csat = ($aq[0]->totrec/$total_surveys_taken)*100;
                        else
                            $csat = 0;
                        
                        $csat_array[] = number_format($csat);
                    }
                }
                
                $total_csat_questions = count($csat_array);
                $total_csat = 0;

                for($i=0; $i<count($csat_array); $i++)
                {
                    $total_csat = $total_csat + $csat_array[$i];
                }

                $avg_csat = $total_csat/$total_csat_questions;
                
                DB::update("UPDATE survey SET survey_csat = '".number_format($avg_csat)."' WHERE survey_id = ".$sid);

                return number_format($avg_csat);
            }
            else
                return 'NA';
        }
        else
            return 0;
    }
    
    public function get_total_surveys_taken($sid, $params)
    {
        if(isset($sid) && !empty($sid))
        {
            $q = DB::select("SELECT COUNT(*) AS totrec FROM survey_response WHERE (time_taken >= '".$params["from_date"]."' AND time_taken <= '".$params["to_date"]."') AND survey_id = ".$sid);
                        
            return $q[0]->totrec;
        }
        else
            return '0';
    }
    
    public function sources_sentiments_data(Request $request)
    {
        $timestamp    = strtotime($request->month.' '.date("Y"));
        $greater_than_time = date('Y-m-01', $timestamp);
        $less_than_time  = date('Y-m-t', $timestamp);

        $topic_query_string = $this->topic_obj->get_topic_elastic_query('2325');
        
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
                                    ['query_string' => ['query' => 'source:("'.$sources_array[$i].'") AND manual_entry_type:("review") AND review_customer:("rutinXYvOCpad1jDnmYM")']],
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
                                    ['query_string' => ['query' => 'source:("'.$sources_array[$i].'") AND manual_entry_type:("review") AND review_customer:("rutinXYvOCpad1jDnmYM")']],
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
                                    ['query_string' => ['query' => 'source:("'.$sources_array[$i].'") AND manual_entry_type:("review") AND review_customer:("rutinXYvOCpad1jDnmYM")']],
                                    ['range' => ['p_likes' => ['gte' => 2, 'lte' => 3]]],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ]
                    ]
                ];

                $neu_senti = $this->client->count($params);
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
    
    public function get_topic_sentiments_data($tid, $month)
    {
        $timestamp = strtotime($month.' '.date("Y"));
        $greater_than_time = date('Y-m-01', $timestamp);
        $less_than_time = date('Y-m-t', $timestamp);
            
        $topic_query_string = $this->topic_obj->get_topic_elastic_query($tid);
                
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

        $response_output = trim($pos_senti.'|'.$neg_senti.'|'.$neu_senti);

        return $response_output;
    }
    
    public function get_topic_name($tid)
    {
        return $this->topic_obj->get_topic_name($tid);
    }
    
    public function topics_reach(Request $request)
    {
        $timestamp    = strtotime($request->month.' '.date("Y"));
        $greater_than_time = date('Y-m-01', $timestamp);
        $less_than_time  = date('Y-m-t', $timestamp);
        
        $topic_names = array();
        $reach_array = array();
        
        $topic_ids = explode(",", "2325,2324,2321,2320,2319,2318"); //These competitor ids taken from Share of voice competitor analysis

        for($i=0; $i<count($topic_ids); $i++)
        {
            $topic_query_string = $this->topic_obj->get_topic_elastic_query($topic_ids[$i]);
            $topic_name = $this->get_topic_name($topic_ids[$i]);
            $topic_names[] = $topic_name;
            
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
                        'total_likes' => ['sum' => ['field' => 'p_likes']]
                    ]
                ]
            ];

            $results = $this->client->search($params);
            $tot_eng = $results["aggregations"]["total_shares"]["value"] + $results["aggregations"]["total_comments"]["value"] + $results["aggregations"]["total_likes"]["value"];
            
            $estimated_reach = $normal_user_followers + $influencer_user_followers + $tot_eng;

            $reach_array[$topic_name] = $estimated_reach;
        }

        echo json_encode($reach_array);
    }
}