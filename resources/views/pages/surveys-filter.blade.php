@extends('layouts.contentLayoutMaster') {{-- page Title --}} @section('title','Topic Dasbhoard') {{-- vendor css --}} @section('vendor-styles')
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/charts/apexcharts.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/swiper.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/dragula.min.css')}}">
@endsection @section('page-styles')
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/pickadate/pickadate.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/daterange/daterangepicker.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/forms/select/select2.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/dashboard-ecommerce.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/dashboard-analytics.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/widgets.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/jquery.rateyo.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/swiper.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/swiper.css')}}">
<!--<link rel="stylesheet" type="text/css" href="{{asset('css/plugins/extensions/ext-component-ratings.css')}}">-->
<link rel="stylesheet" type="text/css" href="{{asset('css/drag-and-drop.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/daterange/daterangepicker.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/leaflet/leaflet.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/leaflet/leaflet-gesture-handling.css')}}">


@endsection
@section('content')
<div class="col-sm-12" style="padding: 0px 0px 0px 0px;">
    <div class="card">
        <div style="width: 13%; float:left;">
            <div class="col-12">
                <div class="mb-1">
                    <label for="sentimenttype">Select survey</label>
                    <select class="select2 form-control" name="survey_id" id="survey_id">
                        <option value="positive">Positive</option>
                        <option value="negative">Negative</option>
                        <option value="neutral">Neutral</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="col-sm-12" style="padding-left: 0px;">
    
</div>




@endsection {{-- vendor scripts --}} @section('vendor-scripts')
<script src="{{asset('vendors/js/charts/apexcharts.js')}}"></script>
<script src="{{asset('vendors/js/extensions/moment.min.js')}}"></script>
<script src="{{asset('vendors/js/pickers/daterange/daterangepicker.js')}}"></script>
<script src="{{asset('vendors/js/extensions/swiper.min.js')}}"></script>
<script src="{{asset('vendors/js/extensions/dragula.min.js')}}"></script>
<script src="{{asset('vendors/js/pickers/pickadate/picker.js')}}"></script>
<script src="{{asset('vendors/js/pickers/pickadate/picker.date.js')}}"></script>
<script src="{{asset('vendors/js/pickers/pickadate/picker.time.js')}}"></script>
<script src="{{asset('vendors/js/pickers/pickadate/legacy.js')}}"></script>
<script src="{{asset('vendors/js/extensions/moment.min.js')}}"></script>
<script src="{{asset('vendors/js/pickers/daterange/daterangepicker.js')}}"></script>
<script src="{{asset('vendors/js/forms/select/select2.full.min.js')}}"></script>
<script src="{{asset('vendors/js/extensions/jquery.rateyo.min.js')}}"></script>
<script src="{{asset('js/scripts/extensions/ext-component-ratings.js')}}"></script>
<script src="{{asset('vendors/js/extensions/swiper.min.js')}}"></script>
<script src="{{asset('js/scripts/extensions/swiper.js')}}"></script>
@endsection @section('page-scripts')
<!--<script src="{{asset('js/scripts/extensions/drag-drop.js')}}"></script>-->
<script src="{{asset('js/scripts/popover/popover.js')}}"></script>
<script src="{{asset('js/scripts/custom.js')}}"></script>
<script src="{{asset('js/scripts/pickers/dateTime/pick-a-datetime.js')}}"></script>
<script src="{{asset('js/scripts/forms/select/form-select2.js')}}"></script>
<!--<script src="{{asset('js/scripts/pages/dashboard-analytics.js')}}"></script>-->
{{--MapScripts--}}
<script src="{{asset('js/scripts/leaflet/leaflet.js')}}"></script>
<script src="{{asset('js/scripts/leaflet/leaflet-gesture-handling.js')}}"></script>


@endsection
  