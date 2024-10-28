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

class MentionsSummaryController extends Controller
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

    public function load_ms_settings_page(Request $request)
    {
        //check for user login
        if(!$this->gen_func_obj->validate_access())
        {
            return redirect('/');
        }
        
        $loggedin_user_id = $this->cus_obj->get_parent_account_id();
         
        $this->topic_obj = new TopicController();
              
         $topics_data = DB::select("SELECT * FROM customer_topics WHERE customer_portal = 'D24' AND topic_is_deleted != 'Y' AND topic_user_id = " . $loggedin_user_id . " ORDER BY topic_order ASC");
       
    $topics_summary=[];

	if($request->has('topic_id')){
	;
		$topicId=$request->topic_id;

            $topics_summary = DB::select("SELECT topic_title,topic_summary,topic_summary_twitter,topic_summary_fb,topic_summary_insta  FROM customer_topics WHERE topic_id = $topicId ");
       
        }
    
	// $topics_summary = DB::select("SELECT topic_summary,topic_summary_twitter,topic_summary_fb,topic_summary_insta  FROM customer_topics WHERE topic_id = '2451' ");
        return view('pages.ms-settings', ['topics_data' => $topics_data,'topics_summary' => $topics_summary ]);
    
    }
    
   
   
}
?>
