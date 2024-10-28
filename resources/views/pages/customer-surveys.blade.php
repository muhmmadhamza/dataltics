@extends('layouts.contentLayoutMaster')
{{-- page Title --}}
@section('title','Surveys')
{{-- vendor css --}}
@section('vendor-styles')

@endsection
@section('page-styles')
<link rel="stylesheet" type="text/css" href="{{asset('css/dashboard-ecommerce.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/dashboard-analytics.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/widgets.min.css')}}">


@endsection
@section('content')
<div class="col-sm-12" style="padding: 0px 0px 0px 0px;">
    <div class="card">
        <div class="card-header">Here you can create different surveys according to your need.</div>
    </div>
</div>

<div class="col-sm-12" style="padding-left: 0px;">
    <?php
    if($load_dashpage == 'yes')
    {
    ?>
    <embed src="/surveys/survey-dashboard.php?cid=<?php echo base64_encode($cid); ?>&pid=<?php echo base64_encode($pid); ?>" style="width:100%; height: 700px; overflow-x:auto; overflow-y: hidden; border:0px;" />
    <?php
    }
    else if($load_region_data == 'yes')
    {
    ?>
    <embed src="/surveys/survey-dashboard-region.php?cid=<?php echo base64_encode($cid); ?>&pid=<?php echo base64_encode($pid); ?>" style="width:100%; height: 700px; overflow-x:auto; overflow-y: hidden; border:0px;" />
    <?php
    }
    else if($load_customer_dashboard == 'yes')
    {
    ?>
    <embed src="/surveys/customer-dashboard.php?cid=<?php echo base64_encode($cid); ?>&pid=<?php echo base64_encode($pid); ?>" style="width:100%; height: 700px; overflow-x:auto; overflow-y: hidden; border:0px;" />
    <?php
    }
    else
    {
    ?>
    <embed src="/surveys/surveys.php?cid=<?php echo base64_encode($cid); ?>&pid=<?php echo base64_encode($pid); ?>&load_templates=<?php echo $load_templates; ?>&stype=<?php echo $stype ?>" style="width:100%; height: 700px; overflow-x:auto; overflow-y: hidden; border:0px;" />
    <?php    
    }
    ?>
</div>




@endsection
{{-- vendor scripts --}}
@section('vendor-scripts')
 
@endsection
  
@section('page-scripts')
<script src="{{asset('js/scripts/custom.js')}}"></script>
@endsection
  