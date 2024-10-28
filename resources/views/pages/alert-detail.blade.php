@extends('layouts.contentLayoutMaster')
{{-- page Title --}}
@section('title','Manage Alert')
{{-- vendor css --}}
@section('vendor-styles')

@endsection
@section('page-styles')
<link rel="stylesheet" type="text/css" href="{{asset('css/wizard.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/pickadate/pickadate.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/daterange/daterangepicker.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/app-file-manager.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/tagsinput.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/forms/select/select2.css')}}">


@endsection
@section('content')
<section id="reports_settings">
    <div class="col-sm-12" style="padding:0px;">
      <div class="col-12" style="padding: 0px; margin-top: 16px;" data-select2-id="278">
        <div class="col-md-12" data-select2-id="277">
            <div class="card" style="box-shadow: none;" data-select2-id="276">
                <div class="card-header">
                    <h4 class="card-title">Add new filter</h4>
                </div>
                <div class="card-body" data-select2-id="275">
                  <section id="add-topic-section" style="overflow: hidden;"><!---->
                    <div class="col-12" style="padding: 0px; margin-top: 16px;">
                        <div class="col-md-12" style="padding: 0px;">
                            <div class="card" style="box-shadow: none;">
                                <div class="card-body" style="padding:0px;">
             
                                  <form name="manage_alert" id="manage_alert" autocomplete="off" action="{{ route('manage.store',$id) }}" method="post">
                                        <div class="row">
                                            <div id="error_msg" style="display: none; color: #ff0000;"></div>
                                            <div class="col-md-12">

                                                <fieldset class="form-group">
                                                    <label for="helpInputTop">Enter hashtags or keywords to be notified</label>
                                                    <small class="text-muted">eg.<i>keyword / #hashtag (Press "Enter" to add mulitple options)</i></small>
                                                    <input type="text" class="bootstrap-t" id="topic_hash_keywords" value="{{ $filter->filter_keywords }}" name="topic_hash_keywords" data-role="tagsinput" required="required">
                                                </fieldset>

                                                <fieldset class="form-group">
                                                  <label for="basicInput">Enter email addresses for notification</label>
                                                  <input type="text" class="bootstrap-t" id="filter_emails" name="filter_emails" value="{{ $filter->filter_emails }}" data-role="tagsinput" required="required">
                                                </fieldset>


                                                <div class="row">
                                                  <div class="col-4">
                                                    <fieldset class="form-group">
                                                        <label for="basicInputFile">Sentiment type</label>
                                                        <select name="post_senti[]" id="post_senti" class="select2 form-control select2-hidden-accessible" multiple="multiple">
                                                          <option value="positive" @if(in_array('positive',explode(",",$filter->filter_sentiment)))) selected @endif >Positive</option>
                                                          <option value="negative" @if(in_array('negative',explode(",",$filter->filter_sentiment)))) selected @endif >Negative</option>
                                                          <option value="neutral" @if(in_array('neutral',explode(",",$filter->filter_sentiment)))) selected @endif >Neutral</option>
                                                        </select>
                                                    </fieldset>
                                                </div>
                                                <div class="col-4">
                                                  <fieldset class="form-group">
                                                      <label for="basicInputFile">User Type</label>
                                                      <select name="user_type[]" id="user_type" class="select2 form-control select2-hidden-accessible" multiple="multiple">
                                                        <option value="normal" @if(in_array('normal',explode(",",$filter->filter_u_type)))) selected @endif >Normal</option>
                                                        <option value="influencer" @if(in_array('influencer',explode(",",$filter->filter_u_type)))) selected @endif>Influencer</option>
                                                        <option value="unverified" @if(in_array('unverified',explode(",",$filter->filter_u_type)))) selected @endif>Un-Verified</option>
                                                      </select>
                                                  </fieldset>
                                              </div>
                                              <div class="col-4">
                                                <fieldset class="form-group">
                                                    <label for="basicInputFile">Speech Type</label>
                                                    <select name="speech[]" id="speech" class="select2 form-control select2-hidden-accessible" multiple="multiple">
                                                      <option value="hate" @if(in_array('hate',explode(",",$filter->filter_speech)))) selected @endif>Hate Speech</option>
                                                      <option value="normal_lang" @if(in_array('normal_lang',explode(",",$filter->filter_speech)))) selected @endif>Normal Language</option>
                                                    </select>
                                                </fieldset>
                                            </div>
                                            <div class="col-4">
                                              <fieldset class="form-group">
                                                  <label for="basicInputFile">Political / Non Political</label>
                                                  <select name="polit_non[]" id="polit_non" class="select2 form-control select2-hidden-accessible" multiple="multiple">
                                                    <option value="polit" @if(in_array('polit',explode(",",$filter->filter_polit)))) selected @endif>Political Speech</option>
                        											      <option value="non_polit" @if(in_array('non_polit',explode(",",$filter->filter_polit)))) selected @endif>Non Political</option>
                                                  </select>
                                              </fieldset>
                                          </div>
                                                    <div class="col-4">
                                                        <fieldset class="form-group">
                                                            <label for="basicInputFile">Filter by source</label>
                                                            <select name="data_source[]" id="data_source" class="select2 form-control select2-hidden-accessible" multiple="multiple">
                                                                <option value="youtube" @if(in_array('youtube',explode(",",$filter->filter_source)))) selected @endif>Videos</option>
                                                                <option value="pinterest" @if(in_array('pinterest',explode(",",$filter->filter_source)))) selected @endif>Pinterest</option>
                                                                <option value="facebook" @if(in_array('facebook',explode(",",$filter->filter_source)))) selected @endif>Facebook</option>
                                                                <option value="twitter" @if(in_array('twitter',explode(",",$filter->filter_source)))) selected @endif>Twitter</option>
                                                                <option value="instagram" @if(in_array('instagram',explode(",",$filter->filter_source)))) selected @endif>Instagram</option>
                                                                <option value="reddit" @if(in_array('reddit',explode(",",$filter->filter_source)))) selected @endif>Reddit</option>
                                                                <option value="tumblr" @if(in_array('tumblr',explode(",",$filter->filter_source)))) selected @endif>Tumblr</option>
                                                                <option value="blogs" @if(in_array('blogs',explode(",",$filter->filter_source)))) selected @endif>Blogs</option>
                                                                <option value="news" @if(in_array('news',explode(",",$filter->filter_source)))) selected @endif>News Sources</option>
                                                            </select>
                                                        </fieldset>
                                                      </div>
                                                </div>
                                                <p>Ferquency.</p>
                                                  <ul class="list-unstyled mb-0">
                                                    <li class="d-inline-block mr-2 mb-1">
                                                      <fieldset>
                                                        <div class="radio radio-shadow">
                                                            <input type="radio" id="5m" name="filter_freq" value="5m" @if($filter->filter_freq == '5m')) checked @endif>
                                                            <label for="5m">Instant notification</label>
                                                        </div>
                                                      </fieldset>
                                                    </li>
                                                    <li class="d-inline-block mr-2 mb-1">
                                                      <fieldset>
                                                        <div class="radio radio-shadow">
                                                            <input type="radio" id="30m" name="filter_freq" value="30m" @if($filter->filter_freq == '30m')) checked @endif>
                                                            <label for="30m">30 mins</label>
                                                        </div>
                                                      </fieldset>
                                                    </li>
                                                    <li class="d-inline-block mr-2 mb-1">
                                                      <fieldset>
                                                        <div class="radio radio-shadow">
                                                            <input type="radio" id="1h" name="filter_freq" value="1h" @if($filter->filter_freq == '1h')) checked @endif>
                                                            <label for="1h">1 Hour</label>
                                                        </div>
                                                      </fieldset>
                                                    </li>
                                                    <li class="d-inline-block mr-2 mb-1">
                                                      <fieldset>
                                                        <div class="radio radio-shadow">
                                                            <input type="radio" id="3h" name="filter_freq" value="3h" @if($filter->filter_freq == '3h')) checked @endif>
                                                            <label for="3h">3 Hours</label>
                                                        </div>
                                                      </fieldset>
                                                    </li>
                                                    <li class="d-inline-block mr-2 mb-1">
                                                      <fieldset>
                                                        <div class="radio radio-shadow">
                                                            <input type="radio" id="6h" name="filter_freq" value="6h" @if($filter->filter_freq == '6h')) checked @endif>
                                                            <label for="6h">6 Hours</label>
                                                        </div>
                                                      </fieldset>
                                                    </li>
                                                    <li class="d-inline-block mr-2 mb-1">
                                                      <fieldset>
                                                        <div class="radio radio-shadow">
                                                            <input type="radio" id="12h" name="filter_freq" value="12h" @if($filter->filter_freq == '12h')) checked @endif>
                                                            <label for="12h">12 Hours</label>
                                                        </div>
                                                      </fieldset>
                                                    </li>
                                                    <li class="d-inline-block mr-2 mb-1">
                                                      <fieldset>
                                                        <div class="radio radio-shadow">
                                                            <input type="radio" id="24h" name="filter_freq" value="24h" @if($filter->filter_freq == '24h')) checked @endif>
                                                            <label for="24h">24 Hours</label>
                                                        </div>
                                                      </fieldset>
                                                    </li>

                                                  </ul>
                                                <div class="card-body">

                                                </div>
                                                <fieldset class="form-group">
                                                    <label for="basicInputFile">&nbsp;</label>
                                                    <div class="custom-file">
                                                        <div class="spinner-border" role="status" id="loading_icon_topic" style="display:none;">
                                                            <span class="sr-only">Loading...</span>
                                                        </div><button type="submit" class="btn btn-primary mr-1 mb-1">Submit</button>

                                                        <input type="hidden" name="_token" id="_token" value="{{ csrf_token() }}">
                                                    </div>

                                                </fieldset>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                    {{--Add topic modal popup--}}
    <div class="modal fade text-left w-100" id="addDashboardModal" tabindex="-1" aria-labelledby="add_topic_popup_title" style="display: none;" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-dialog-centered modal-dialog-scrollable modal-xl">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="add_topic_popup_title">Add Dashboard</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <i class="bx bx-x"></i>
                  </button>
              </div>
              <div class="modal-body">

              </div>
          </div>
      </div>
  </div>
  {{-- END: Add topic modal popup --}}
                </div>
            </div>
        </div>
    </div>
    </div>
</section>

@endsection
{{-- vendor scripts --}}
@section('vendor-scripts')
<script src="{{asset('vendors/js/extensions/jquery.steps.min.js')}}"></script>
<script src="{{asset('vendors/js/forms/validation/jquery.validate.min.js')}}"></script>

<script src="{{asset('vendors/js/pickers/pickadate/picker.js')}}"></script>
<script src="{{asset('vendors/js/pickers/pickadate/picker.date.js')}}"></script>
<script src="{{asset('vendors/js/pickers/pickadate/picker.time.js')}}"></script>
<script src="{{asset('vendors/js/pickers/pickadate/legacy.js')}}"></script>
<script src="{{asset('vendors/js/extensions/moment.min.js')}}"></script>
<script src="{{asset('vendors/js/pickers/daterange/daterangepicker.js')}}"></script>


@endsection

@section('page-scripts')
<script src="{{asset('js/scripts/custom.js')}}"></script>
<script src="{{asset('js/scripts/pages/app-file-manager.js')}}"></script>
<script src="{{asset('js/scripts/tagsinput.js')}}"></script>
  <script src="{{asset('js/scripts/popover/popover.js')}}"></script>
  <script src="{{asset('vendors/js/forms/select/select2.full.min.js')}}"></script>
  <script src="{{asset('js/scripts/forms/select/form-select2.js')}}"></script>

<script type="text/javascript">
$('.pickadate').pickadate();
</script>
@endsection
