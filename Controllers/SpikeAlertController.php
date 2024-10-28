<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\TopicNotificationSpike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SpikeAlertController extends Controller
{
    //
    public function index(Request $request)
    {
        //check for user login
        // if(!$this->gen_func_obj->validate_access())
        // {
        //     return redirect('/');
        // }
        $filters = Topic::with('spike')->where('topic_is_deleted','N')->where('topic_user_id',Session::get('_loggedin_customer_id'))->orderBy('topic_id','desc')->get();
        //dd($filters);
        return view('pages.spikes',['filters' => $filters]);
    }


    public function detail($id ,Request $request)
    {
      // if(!$this->gen_func_obj->validate_access())
      // {
      //     return redirect('/');
      // }

      $filter = TopicNotificationSpike::where('filter_topic_id',$id)->first();
      if(!$filter){
        $counter = TopicNotificationSpike::count();
        $filter = new TopicNotificationSpike();
        $filter->id    = $counter + 1;
        $filter->filter_topic_id    = $id;
        //$filter->filter_keywords    = '';
        $filter->filter_freq    = '7d';
        $filter->save();
      }

      # code...
      $filter = TopicNotificationSpike::where('filter_topic_id',$id)->first();
      return view('pages.spike-detail',['filter' => $filter,'id' => $id]);

    }

    public function store($id,Request $request)
    {

      // if(!$this->gen_func_obj->validate_access())
      // {
      //     return redirect('/');
      // }

      $filter = TopicNotificationSpike::where('filter_topic_id',$request->id)->first();
      if(!$filter){
        $filter = new TopicNotificationSpike();
        $filter->filter_topic_id    = $request->id;
      }

      $filter->total_mentions  = $request->total_mentions;
      $filter->filter_freq = $request->filter_freq;
      $filter->save();
      Session::flash('message', 'Filter updated Successfully.');
      Session::flash('alert-class', 'alert-success');

      return redirect()->route('spike.index');

    }


    public function delete(Request $request)
    {
      // if(!$this->gen_func_obj->validate_access())
      // {
      //     return redirect('/');
      // }
      # code...
      $filter = TopicNotificationSpike::find($request->id);
      $filter->delete();

      Session::flash('message', 'Filter reset Successfully.');
      Session::flash('alert-class', 'alert-success');
      return true;

    }

    public function charts(Request $request){

      return view('pages.spikes-notification');

    }

}
