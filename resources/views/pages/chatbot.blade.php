@extends('layouts.contentLayoutMaster')
{{-- page Title --}}
@section('title','Chatbot')
{{-- vendor css --}}
@section('vendor-styles')

@endsection
@section('page-styles')
<link rel="stylesheet" type="text/css" href="{{asset('css/wizard.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/pickadate/pickadate.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/daterange/daterangepicker.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/app-file-manager.css')}}">
<style type="text/css">
  .link_button {
    -webkit-border-radius: 4px;
    -moz-border-radius: 4px;
    border-radius: 4px;
    border: solid 1px #20538D;
    text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.4);
    -webkit-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4), 0 1px 1px rgba(0, 0, 0, 0.2);
    -moz-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4), 0 1px 1px rgba(0, 0, 0, 0.2);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4), 0 1px 1px rgba(0, 0, 0, 0.2);
    background: #4479BA;
    color: #FFF;
    padding: 5px 12px;
    text-decoration: none;
}

.link_button:hover
{
  color: #ffffff !important;
}
</style>

@endsection
@section('content')
<section id="reports_settings">
    <div class="col-sm-12" style="padding:0px;">
      <div class="row" id="basic-table">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h4 class="card-title">Chatbot</h4>
            </div>
            
            <div class="card-body">
              Please check our Yellowpages bot using WhatsApp at <a href="https://wa.me/923060161040" target="_blank">+923060161040</a>. <br><br>Follow the prompts provided by the bot for guidance. You have the option to send both text and voice messages to receive responses.
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

@endsection
