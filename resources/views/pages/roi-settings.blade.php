@extends('layouts.contentLayoutMaster')

{{-- page Title --}}
@section('title','Topic Settings')

{{-- vendor css --}}
@section('vendor-styles')
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/charts/apexcharts.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/swiper.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/dragula.min.css')}}">
@endsection
@section('page-styles')
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/pickadate/pickadate.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/daterange/daterangepicker.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/forms/select/select2.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/dashboard-ecommerce.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/dashboard-analytics.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/widgets.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/drag-and-drop.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/daterange/daterangepicker.css')}}">
@endsection
@section('content')

<div class="col-sm-12" style="padding-left: 0px;"><h5>Public sources</h5></div>

<div class="row">
    <div class="col-sm-3" style="float: left;">
        <div class="card">
            <div class="card-header">
                <table style="width: 100%; padding: 0px; margin: 0px;">
                    <tr>
                        <td style="width: 25%; vertical-align: top;"><i class="bx bxs-group" style="font-size: 4rem; color:#6e82b6 !important"></i></td>
                        <td style="width: 75%;">
                            <div><h4>Social media</h4></div>
                            <div style="height: 70px;">Data from all popular social channels is default activated.</div>
                            <div style="padding-top: 35px;"><button type="button" class="btn mr-1 mb-1 btn-primary btn-sm" style="background-color: #6e82b6 !important;">Activated</button></div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-sm-3" style="float: left;">
        <div class="card">
            <div class="card-header">
                <table style="width: 100%; padding: 0px; margin: 0px;">
                    <tr>
                        <td style="width: 25%; vertical-align: top;"><i class="bx bx-globe" style="font-size: 4rem; color:#b6aa6e !important"></i></td>
                        <td style="width: 75%;">
                            <div><h4>Web</h4></div>
                            <div style="height: 70px;">Data from Web is currently not activated by default.</div>
                            <div style="padding-top: 35px;"><button type="button" class="btn mr-1 mb-1 btn-primary btn-sm" style="background-color: #b6aa6e !important;">Not activated</button></div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-sm-3" style="float: left;">
        <div class="card">
            <div class="card-header">
                <table style="width: 100%; padding: 0px; margin: 0px;">
                    <tr>
                        <td style="width: 25%; vertical-align: top;"><i class="bx bx-news" style="font-size: 4rem; color:#da9968 !important"></i></td>
                        <td style="width: 75%;">
                            <div><h4>Print media</h4></div>
                            <div style="height: 70px;">Data from Print media is default activated.</div>
                            <div style="padding-top: 35px;"><button type="button" class="btn mr-1 mb-1 btn-primary btn-sm" style="background-color: #da9968 !important;">Activated</button></div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
</div>

<div class="col-sm-12" style="padding: 30px 0px 0px 0px;"><h5>Private sources</h5></div>

@endsection
@section('vendor-scripts')
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
@endsection @section('page-scripts')
<script src="{{asset('js/scripts/extensions/drag-drop.js')}}"></script>
<script src="{{asset('js/scripts/popover/popover.js')}}"></script>
<script src="{{asset('js/scripts/custom.js')}}"></script>
<script src="{{asset('js/scripts/pickers/dateTime/pick-a-datetime.js')}}"></script>
<script src="{{asset('js/scripts/forms/select/form-select2.js')}}"></script>
<script>

</script>
@endsection
