<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\GeneralFunctionsController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\CustomerController;
use Elasticsearch\ClientBuilder;

class SearchController extends Controller {

    public function __construct(Request $request)
    {
        $this->gen_func_obj = new GeneralFunctionsController();
        $this->topic_obj = new TopicController();
        $this->cus_obj = new CustomerController();
        
        $this->client = ClientBuilder::create()->setHosts([env('ELASTICSEARCH_HOST').":".env('ELASTICSEARCH_PORT')])->build();
        
        $this->search_index_name = env('ELASTICSEARCH_DEFAULTINDEX');
        $this->date_fetch_days_number = env('DATA_FETCH_DAYS_NUMBER');
    }
    public function index(Request $request) 
    {
        //check for user login
        if(!$this->gen_func_obj->validate_access())
        {
            \Session::flush(); //remove all sessions
            return redirect('/');
        }

        //Get topic list of loggedin user
        $loggedin_user_id = $this->cus_obj->get_parent_account_id();
        
        $topics_list = $this->topic_obj->get_topics_list($loggedin_user_id);

        //return view('pages.search',['pageConfigs'=>$pageConfigs,'breadcrumbs'=>$breadcrumbs]);
        return view('pages.search', ['topics_list' => $topics_list, 'default_topic_id' => $topics_list[0]->topic_id]);
    }
    
    public function handle_search_results(Request $request)
    {
        $search_results_query = '';
        $days_difference = '';

        if(isset($request["from_date"]) && !empty($request["from_date"]) && !is_null($request["from_date"]))
        {
            //$greater_than_time = date("Y-m-d", strtotime($request["from_date"]));
            $greater_than_time = date_create_from_format('j F, Y', $request["from_date"]);
            $greater_than_time = date_format($greater_than_time, 'Y-m-d');
        }
        else
            $greater_than_time = date("Y-m-d", strtotime('-'.$this->date_fetch_days_number.' day', strtotime(date("Y-m-d"))));

        if(isset($request["to_date"]) && !empty($request["to_date"]) && !is_null($request["to_date"]))
        {
            //$less_than_time = date("Y-m-d", strtotime($request["to_date"]));
            $less_than_time = date_create_from_format('j F, Y', $request["to_date"]);
            $less_than_time = date_format($less_than_time, 'Y-m-d');
        }
        else
            $less_than_time = date("Y-m-d");

        $days_difference = $this->gen_func_obj->date_difference($less_than_time, $greater_than_time);

        //keywords / hashtags selected
        if (isset($request["selected_hash_key"]) && !empty($request["selected_hash_key"]))
        {
            $topic_urls = '';
            $topic_key_hash = '';

            $tags_str = $request["selected_hash_key"];

            $tags_array = explode(",", $tags_str);

            for ($i = 0; $i < count($tags_array); $i++)
            {
                if(isset($tags_array[$i]) && !empty($tags_array[$i]))
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
                $str_to_search = '(p_message_text:(' . $topic_key_hash . ' OR ' . $topic_urls . ') OR u_source:(' . $topic_urls . '))';

            if (!empty($topic_key_hash) && empty($topic_urls))
                $str_to_search = 'p_message_text:(' . $topic_key_hash . ')';

            if (empty($topic_key_hash) && !empty($topic_urls))
                $str_to_search = 'u_source:(' . $topic_urls . ')';

            $search_results_query = $str_to_search;
        }

        if(isset($request->search_text) && !empty($request->search_text))
        {
            $search_results_query .= ' AND (p_message_text:("'.$request->search_text.'") OR u_source:("'.$request->search_text.'") OR u_fullname:("'.$request->search_text.'") )';
        }

        if (isset($request["post_senti"]) && !empty($request["post_senti"]) && !is_null($request["post_senti"]) && $request["post_senti"] != 'null')
        {
            $senti = explode(",", $request["post_senti"]);

            $temp_str = '';

            for ($i = 0; $i < count($senti); $i++)
            {
                $temp_str .= '"' . $senti[$i] . '" OR ';
            }

            $search_results_query .= ' AND predicted_sentiment_value:(' . substr($temp_str, 0, -4) . ')';
        }

        if (isset($request["user_type"]) && !empty($request["user_type"]) && !is_null($request["user_type"]) && $request["user_type"] != 'null')
        {
            $user_str = '';
            $user_type = explode(",", $request["user_type"]);

            if (in_array('normal', $user_type))
                $user_str .= '(u_followers:>0 AND u_followers:<1000) AND ';

            if (in_array('influencer', $user_type))
                $user_str .= 'u_followers:>1000 AND ';

            if (in_array('unverified', $user_type))
                $user_str .= 'account_type:("1") AND ';

            if (!empty($user_str))
            {
                if (in_array('normal', $user_type) || in_array('influencer', $user_type))
                    $search_results_query .= ' AND (' . substr($user_str, 0, -4) . ' AND NOT account_type:("1"))';
                else
                    $search_results_query .= ' AND ' . substr($user_str, 0, -4);
            }
        }

        /*if (isset($request["speech"]) && !empty($request["speech"]) && !is_null($request["speech"]) && $request["speech"] != 'null')
        {
            $speech_str = '';
            $speech = explode(",", $request["speech"]);

            if (in_array('hate', $speech))
                $speech_str .= '"hate" OR "offensive_language" OR ';

            if (in_array('normal_lang', $speech))
                $speech_str .= '"neither" OR ';

            if (!empty($speech_str))
                $search_results_query .= ' AND hatespeech:(' . substr($speech_str, 0, -4) . ')';
        }

        if (isset($request["polit_non"]) && !empty($request["polit_non"]) && !is_null($request["polit_non"]) && $request["polit_non"] != 'null')
        {
            $polit_str = '';
            $polit_non = explode(",", $request["polit_non"]);

            if (in_array('polit', $polit_non))
                $polit_str .= '"POLIT" OR ';

            if (in_array('non_polit', $polit_non))
                $polit_str .= '"NOT" OR ';

            if (!empty($polit_str))
                $search_results_query .= ' AND predicted_political_category:(' . substr($polit_str, 0, -4) . ')';
        }*/

        if (isset($request["data_source"]) && !empty($request["data_source"]) && !is_null($request["data_source"]) && $request["data_source"] != 'null')
        {
            $source_str = '';
            $data_source = explode(",", $request["data_source"]);

            if (in_array('youtube', $data_source))
                $source_str .= '"Youtube" OR "Vimeo" OR ';

            if (in_array('vimeo', $data_source))
                $source_str .= '"Vimeo" OR ';

            if (in_array('pinterest', $data_source))
                $source_str .= '"Pinterest" OR ';

            if (in_array('facebook', $data_source))
                $source_str .= '"Facebook" OR ';

            if (in_array('twitter', $data_source))
                $source_str .= '"Twitter" OR ';

            if (in_array('instagram', $data_source))
                $source_str .= '"Instagram" OR ';

            if (in_array('reddit', $data_source))
                $source_str .= '"Reddit" OR ';

            if (in_array('tumblr', $data_source))
                $source_str .= '"Tumblr" OR ';

            if (in_array('blogs', $data_source))
                $source_str .= '"Blogs" OR ';

            if (in_array('news', $data_source))
                $source_str .= '"FakeNews" OR "News" OR ';
            
            if (in_array('web', $data_source))
                $source_str .= '"Web" OR ';
            
            if (in_array('linkedin', $data_source))
                $source_str .= '"Linkedin" OR ';

            if (!empty($source_str))
                $search_results_query .= ' AND source:(' . substr($source_str, 0, -4) . ')';
        }

        if (isset($request["data_category"]) && !empty($request["data_category"]) && !is_null($request["data_category"]) && $request["data_category"] != 'null')
        {
            $cat_str = '';
            $data_category = explode(",", $request["data_category"]);

            if (in_array('Business', $data_category))
                $cat_str .= '"Business" OR ';

            if (in_array('Education', $data_category))
                $cat_str .= '"Education" OR ';

            if (in_array('Entertainment', $data_category))
                $cat_str .= '"Entertainment" OR ';

            if (in_array('Fashion', $data_category))
                $cat_str .= '"Fashion" OR ';

            if (in_array('Food', $data_category))
                $cat_str .= '"Food" OR ';

            if (in_array('Health', $data_category))
                $cat_str .= '"Health" OR ';

            if (in_array('Politics', $data_category))
                $cat_str .= '"Politics" OR ';

            if (in_array('Sports', $data_category))
                $cat_str .= '"Sports" OR ';

            if (in_array('Technology', $data_category))
                $cat_str .= '"Technology" OR ';

            if (in_array('Transport', $data_category))
                $cat_str .= '"Transport" OR ';

            if (in_array('Weather', $data_category))
                $cat_str .= '"Weather" OR ';

            if (!empty($cat_str))
                $search_results_query .= ' AND predicted_category:(' . substr($cat_str, 0, -4) . ')';
        }

        if (isset($request["dloc"]) && !empty($request["dloc"]) && !is_null($request["dloc"]) && $request["dloc"] != 'null')
        {
            $dloc = explode(",", $request["dloc"]);

            $temp_str = '';

            for ($i = 0; $i < count($dloc); $i++)
            {
                $temp_str .= '"' . $dloc[$i] . '" OR ';
            }

            $search_results_query .= ' AND u_country:(' . substr($temp_str, 0, -4) . ')';
        }

        if (isset($request["dlang"]) && !empty($request["dlang"]) && !is_null($request["dlang"]) && $request["dlang"] != 'null')
        {
            $dlang = explode(",", $request["dlang"]);

            $temp_str = '';

            for ($i = 0; $i < count($dlang); $i++)
            {
                $temp_str .= '"' . $dlang[$i] . '" OR ';
            }

            $search_results_query .= ' AND lange_detect:(' . substr($temp_str, 0, -4) . ')'; //' AND u_country:('.substr($temp_str, 0, -4).')';
        }
        
        //$search_results_query .=  ' AND NOT source:("Web")';
            
        if(isset($request->section) && $request->section == 'get_search_results')
        {
            $page_no = 0;
            $from_results = 0;
            $window_size = 20;
            
            //if(isset($request->results_page_no))
                //$page_no = $request->results_page_no;
            
                       
            //Pagination generation
            $offset = 0;
            $page_no = 0;
            $previous_page = 0;
            $next_page = 0;
            $adjacents = "0";
            
            $page_no = $request->results_page_no;
            $total_records_per_page = $window_size;
            $offset = ($page_no-1) * $total_records_per_page;
            $previous_page = $page_no - 1;
            $next_page = $page_no + 1;
            $adjacents = "2";

            if (isset($request->results_page_no) && $request->results_page_no!="") 
            {
                $page_no = $request->results_page_no;
            } 
            else 
            {
                $page_no = 1;
            }
            
            if($page_no == 1)
                $from_results = 0;
            else
                $from_results = $page_no * $window_size;
                        
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $search_results_query ]],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];
            //echo '<pre>'; print_r($params);
            $results = $this->client->count($params);
            
            $total_no_of_pages = floor($results["count"] / $total_records_per_page);
            $second_last = $total_no_of_pages - 1; // total pages minus 1
                
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'from' => $from_results,
                'size' => $window_size,
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $search_results_query ]],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ],
                    'sort' => [
                        ['p_created_time' => ['order' => 'desc']]
                    ]
                ]
            ];
            //echo '<pre>'; print_r($params);
            $results = $this->client->search($params);
            
            $posts_html = '';
            
            if(count($results["hits"]["hits"]) > 0)
            {
                for($ii=0; $ii<count($results["hits"]["hits"]); $ii++)
                {
                    $posts_html .= $this->gen_func_obj->get_postview_simple_html($results["hits"]["hits"][$ii]["_source"]);

                }
                
                
            }
            else
            {
                $posts_html = trim('NA');
            }
            
            //pagination html
            $pagination_html = '';
            $pagination_html .= '<ul class="pagination pagination-borderless justify-content-center mt-2">';
            
            if($page_no > 1)
                $pagination_html .= '<li class="page-item"><a class="page-link" href="javascript:void(0);" onClick="javascript:get_search_results(\'load_more\', 1);">First Page</a></li>'; //href="?page_no=1"
            
            if($page_no <=1)
                $pagination_html .= '<li  class="page-item disabled">';
            else
                $pagination_html .= '<li class="page-item">';

            if($page_no > 1)
                $pagination_html .= '<a class="page-link" href="javascript:void(0);" onClick="javascript:get_search_results(\'load_more\', '.$previous_page.');"><i class="bx bx-chevron-left"></i></a></li>'; //href="?page_no='.$previous_page.'"
            else
                $pagination_html .= '<a class="page-link">Previous</a></li>';
            
            if ($total_no_of_pages <= 10)
            {  	 
                for ($counter = 1; $counter <= $total_no_of_pages; $counter++)
                {
                    if ($counter == $page_no) 
                    {
                        $pagination_html .= '<li class="page-item active"><a class="page-link">'.$counter.'</a></li>';	
                    }
                    else
                    {
                        $pagination_html .= '<li class="page-item"><a class="page-link" href="javascript:void(0);" onClick="javascript:get_search_results(\'load_more\', '.$counter.');">'.$counter.'</a></li>'; // href="?page_no='.$counter.'"
                    }
                }
            }
            elseif ($total_no_of_pages > 10)
            {
                if($page_no <= 4) 
                {			
                    for ($counter = 1; $counter < 8; $counter++)
                    {		 
                        if ($counter == $page_no) 
                        {
                            $pagination_html .= '<li class="page-item active"><a class="page-link">'.$counter.'</a></li>';	
                        }
                        else
                        {
                            $pagination_html .= '<li class="page-item"><a class="page-link" href="javascript:void(0);" onClick="javascript:get_search_results(\'load_more\', '.$counter.');">'.$counter.'</a></li>'; // href="?page_no='.$counter.'"
                        }
                   }
                   
                   $pagination_html .= '<li class="page-item"><a class="page-link">...</a></li>';
                   $pagination_html .= '<li class="page-item"><a class="page-link" href="javascript:void(0);" onClick="javascript:get_search_results(\'load_more\', '.$second_last.');">'.$second_last.'</a></li>'; // href="?page_no='.$second_last.'"
                   $pagination_html .= '<li class="page-item"><a class="page-link" href="javascript:void(0);" onClick="javascript:get_search_results(\'load_more\', '.$total_no_of_pages.');">'.$total_no_of_pages.'</a></li>'; // href="?page_no='.$total_no_of_pages.'"
                }
                elseif($page_no > 4 && $page_no < $total_no_of_pages - 4) 
                {		 
                    $pagination_html .= '<li class="page-item"><a class="page-link" href="javascript:void(0);" onClick="javascript:get_search_results(\'load_more\', 1);">1</a></li>'; // href="?page_no=1"
                    $pagination_html .= '<li class="page-item"><a class="page-link" href="javascript:void(0);" onClick="javascript:get_search_results(\'load_more\', 2);">2</a></li>'; // href="?page_no=2"
                    $pagination_html .= '<li class="page-item"><a class="page-link">...</a></li>';
                    for ($counter = $page_no - $adjacents; $counter <= $page_no + $adjacents; $counter++) 
                    {		
                        if ($counter == $page_no) 
                        {
                            $pagination_html .= '<li class="page-item active"><a class="page-link">'.$counter.'</a></li>';	
                        }
                        else
                        {
                            $pagination_html .= '<li class="page-item"><a class="page-link" href="javascript:void(0);" onClick="javascript:get_search_results(\'load_more\', '.$counter.');">'.$counter.'</a></li>'; // href="?page_no='.$counter.'"
                        }                  
                    }
                    
                    $pagination_html .= '<li class="page-item"><a class="page-link">...</a></li>';
                    $pagination_html .= '<li class="page-item"><a class="page-link" href="javascript:void(0);" onClick="javascript:get_search_results(\'load_more\', '.$second_last.');">'.$second_last.'</a></li>'; // href="?page_no='.$second_last.'"
                    $pagination_html .= '<li class="page-item"><a class="page-link" href="javascript:void(0);" onClick="javascript:get_search_results(\'load_more\', '.$total_no_of_pages.');">'.$total_no_of_pages.'</a></li>'; // href="?page_no='.$total_no_of_pages.'"
                }
                else 
                {
                    $pagination_html .= '<li class="page-item"><a class="page-link" href="javascript:void(0);" onClick="javascript:get_search_results(\'load_more\', 1);">1</a></li>'; // href="?page_no=1"
                    $pagination_html .= '<li class="page-item"><a class="page-link" href="javascript:void(0);" onClick="javascript:get_search_results(\'load_more\', 2);">2</a></li>'; // href="?page_no=2"
                    $pagination_html .= '<li class="page-item"><a class="page-link">...</a></li>';
                    for ($counter = $total_no_of_pages - 6; $counter <= $total_no_of_pages; $counter++) 
                    {
                        if ($counter == $page_no) 
                        {
                            $pagination_html .= '<li class="page-item active"><a class="page-link">'.$counter.'</a></li>';	
                        }
                        else
                        {
                            $pagination_html .= '<li class="page-item"><a class="page-link" href="javascript:void(0);" onClick="javascript:get_search_results(\'load_more\', '.$counter.');">'.$counter.'</a></li>'; // href="?page_no='.$counter.'"
                        }                   
                    }
                }
            }
            
            if($page_no >= $total_no_of_pages)
                $pagination_html .= '<li class="page-item disabled">';
            else
                $pagination_html .= '<li class="page-item">';
            
            if($page_no < $total_no_of_pages)
                $pagination_html .= '<a class="page-link" href="javascript:void(0);" onclick="javascript:get_search_results(\'load_more\', '.$next_page.');"><i class="bx bx-chevron-right"></i></a></li>';//'<a class="page-link" href="?page_no='.$next_page.'"><i class="bx bx-chevron-right"></i></a></li>';
            else
                $pagination_html .= '<a class="page-link">Next</a></li>';
            
            if($page_no < $total_no_of_pages)
                $pagination_html .= '<li class="page-item"><a class="page-link" href="javascript:void(0);" onClick="javascript:get_search_results(\'load_more\', '.$total_no_of_pages.');">Last &rsaquo;&rsaquo;</a></li>'; // href="?page_no='.$total_no_of_pages.'"

            $pagination_html .= '</ul>';
            
            $resp_html = $posts_html.'~|~'.$pagination_html;
            //$resp_html = $posts_html;
            echo $resp_html;
            //echo $posts_html;
        }
        else if(isset($request->section) && $request->section == 'get_search_sources_count')
        {
            $response_output = '';
            //videos
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $search_results_query.' AND source:("Youtube" OR "Vimeo")']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);

            $response_output .= $results["count"].'|';
            
            //news sources
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $search_results_query.' AND source:("FakeNews" OR "News")']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);

            $response_output .= $results["count"].'|';

            //twitter
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $search_results_query.' AND source:("Twitter")']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);

            $response_output .= $results["count"].'|';

            //Instagram
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $search_results_query.' AND source:("Instagram")']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);

            $response_output .= $results["count"].'|';

            //Blogs
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $search_results_query.' AND source:("Blogs")']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);

            $response_output .= $results["count"].'|';

            //Reddit
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $search_results_query.' AND source:("Reddit")']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);

            $response_output .= $results["count"].'|';

            //Tumblr
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $search_results_query.' AND source:("Tumblr")']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);

            $response_output .= $results["count"].'|';

            //Facebook
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $search_results_query.' AND source:("Facebook")']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);

            $response_output .= $results["count"].'|';
            
            //Pinterest
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $search_results_query.' AND source:("Pinterest")']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);

            $response_output .= $results["count"].'|';
            
            //Web
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $search_results_query.' AND source:("Web")']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);

            $response_output .= $results["count"].'|';
            
            //Linkedin
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $search_results_query.' AND source:("Linkedin")']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);

            $response_output .= $results["count"].'|';

            $response_output = substr($response_output, 0, -1);

            echo trim($response_output);
        }
        else if(isset($request->section) && $request->section == 'get_search_misc_count')
        {
            $response_output = '';
            //total results
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $search_results_query ]],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);

            $response_output .= $results["count"].'|';
            
            //social results
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $search_results_query.' AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);

            $response_output .= $results["count"].'|';

            //non social
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $search_results_query.' AND source:("khaleej_times" OR "Omanobserver" OR "Time of oman" OR "Blogs" OR "FakeNews" OR "News")']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);

            $response_output .= $results["count"].'|';

            
            $response_output = substr($response_output, 0, -1);

            echo trim($response_output.$search_results_query);
        }
    }
    
    public function get_topic_hash_key(Request $request)
    {
        if(isset($request->tid) && !empty($request->tid))
        {
            $keys_hash = '';
            $topic_id = base64_decode($request->tid);
            $topic_key_hash = $this->topic_obj->get_topic_hash_keywords_urls_str($topic_id);
            
            $khu = explode(",", $topic_key_hash);
            
            for ($i = 0; $i < count($khu); $i++) 
            {
                $keys_hash .= '<div style="float: left; background: #5A8DEE; border-radius: 5px; color: #fff; padding: 3px 5px 3px 5px; margin: 0px 5px 5px 0px; cursor: pointer;" id="tag' . $i . '" onclick="javascript:set_custom_selection(\'' . $i . '\');">' . str_replace("'", "", $khu[$i]) . '</div>';
            }
            
            echo $keys_hash.'|'.$topic_key_hash;
        }
    }
}
?>
