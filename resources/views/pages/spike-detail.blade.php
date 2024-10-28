@extends('layouts.contentLayoutMaster')
{{-- page Title --}}
@section('title','Manage Spike')
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
                    <h4 class="card-title">Add new spike</h4>
                </div>
                <div class="card-body" data-select2-id="275">
                  <section id="add-topic-section" style="overflow: hidden;"><!---->
                    <div class="col-12" style="padding: 0px; margin-top: 16px;">
                        <div class="col-md-12" style="padding: 0px;">
                            <div class="card" style="box-shadow: none;">
                                <div class="card-body" style="padding:0px;">
                                    <form name="manage_alert" id="manage_alert" autocomplete="off" action="{{ route('spike.store',$id) }}" method="post">
                                        <div class="row">
                                            <div id="error_msg" style="display: none; color: #ff0000;"></div>
                                            <div class="col-md-12">

                                                <fieldset class="form-group">
                                                  <label for="basicInput">Number of mentions</label>
                                                  <input type="text" class="form-control" id="total_mentions" name="total_mentions" value="{{ $filter->total_mentions }}" required="required">
                                                </fieldset>

                                                <p>Ferquency.</p>
                                                  <ul class="list-unstyled mb-0">
                                                    <li class="d-inline-block mr-2 mb-1">
                                                      <fieldset>
                                                        <div class="radio radio-shadow">
                                                            <input type="radio" id="7d" name="filter_freq" value="5m" @if($filter->filter_freq == '7d')) checked @endif>
                                                            <label for="7d">7 Days</label>
                                                        </div>
                                                      </fieldset>
                                                    </li>
                                                    {{-- <li class="d-inline-block mr-2 mb-1">
                                                      <fieldset>
                                                        <div class="radio radio-shadow">
                                                            <input type="radio" id="14d" name="filter_freq" value="14d" @if($filter->filter_freq == '14d')) checked @endif>
                                                            <label for="14d">14 Days</label>
                                                        </div>
                                                      </fieldset>
                                                    </li> --}}
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
