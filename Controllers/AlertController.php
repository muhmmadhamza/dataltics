<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\TopicsNotificationFilter;
use Illuminate\Contracts\Session\Session as SessionSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AlertController extends Controller
{
    public function __construct()
    {
        $this->gen_func_obj = new GeneralFunctionsController();

        //check for user login
        $this->gen_func_obj->validate_access();
    }

    //
    public function index(Request $request)
    {
        //check for user login
        // if(!$this->gen_func_obj->validate_access())
        // {
        //     return redirect('/');
        // }
        $filters = Topic::with('filter')->where('topic_is_deleted','N')->where('topic_user_id',Session::get('_loggedin_customer_id'))->orderBy('topic_id','desc')->get();
        return view('pages.alerts',['filters' => $filters]);
    }


    public function detail($id ,Request $request)
    {
        if(!$this->gen_func_obj->validate_access())
        {
            return redirect('/');
        }
        
        $filter = TopicsNotificationFilter::where('filter_topic_id',$id)->first();
        
        if(!$filter)
        {
            $counter = TopicsNotificationFilter::count();
            $filter = new TopicsNotificationFilter();
            //$filter->filter_id    = $counter;
            $filter->filter_topic_id    = $id;
            $filter->filter_keywords    = '';
            $filter->filter_freq    = '5m';
            $filter->save();
        }

        # code...
        $filter = TopicsNotificationFilter::with('topic')->where('filter_topic_id',$id)->first();
        //dd($filter);
        return view('pages.alert-detail',['filter' => $filter,'id' => $id]);

    }

    public function delete($id ,Request $request)
    {
      if(!$this->gen_func_obj->validate_access())
      {
          return redirect('/');
      }
      # code...
      $filter = TopicsNotificationFilter::where('filter_id',$request->id)->delete();
      //$filter->filter_freq = '';
      //$filter->save();
      Session::flash('message', 'Filter reset Successfully.');
      Session::flash('alert-class', 'alert-success');
      return true;

    }

    public function store($id,Request $request)
    {

      if(!$this->gen_func_obj->validate_access())
      {
          return redirect('/');
      }

      $filter = TopicsNotificationFilter::where('filter_topic_id',$request->id)->first();
      if(!$filter){
        $filter = new TopicsNotificationFilter();
        $filter->filter_topic_id    = $request->id;
      }

      $filter->filter_keywords  = $request->topic_hash_keywords;
      $filter->filter_emails    = $request->filter_emails;
      $filter->filter_sentiment = ($request->post_senti) ? implode(",",$request->post_senti):'';
      $filter->filter_u_type    = ($request->user_type) ? implode(",",$request->user_type):'';
      $filter->filter_speech    = ($request->speech) ? implode(",",$request->speech):'';
      $filter->filter_polit     = ($request->polit_non) ? implode(",",$request->polit_non):'';
      $filter->filter_source    = ($request->data_source) ? implode(",",$request->data_source):'';
      $filter->filter_freq = $request->filter_freq;
      $filter->save();
      Session::flash('message', 'Filter updated Successfully.');
      Session::flash('alert-class', 'alert-success');

      return redirect()->route('alert.sindex');

    }
}
