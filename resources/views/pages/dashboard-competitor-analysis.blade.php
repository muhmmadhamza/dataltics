@extends('layouts.contentLayoutMaster') {{-- page Title --}} @section('title','Topic Dasbhoard') {{-- vendor css --}} @section('vendor-styles')
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/charts/apexcharts.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/swiper.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/dragula.min.css')}}">
@endsection @section('page-styles')
<link rel="stylesheet" type="text/css" href="{{asset('css/dashboard-ecommerce.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/dashboard-analytics.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/pickadate/pickadate.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/daterange/daterangepicker.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/forms/select/select2.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/widgets.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/drag-and-drop.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/daterange/daterangepicker.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/daterange/daterangepicker.css')}}">
@endsection @section('content')
<!--Search Form--->
@include('pages.topic-filter-form')
<!-- Dashboard Ecommerce Starts -->

<section class="list-group">
    {{--mentions graph section--}}
    <div class="row" style="">
		<div class="col-12">
			<div class="card widget-order-activity">
				<div class="card-header d-md-flex justify-content-between align-items-center">
					<h4 class="card-title">Mentions</h4>
					<div class="heading-elements mt-md-0 mt-50 d-flex align-items-center">
						<fieldset class="d-inline-block form-group position-relative has-icon-left mb-0 mr-1">
							<div class="spinner-border" id="spinner" role="status" style="display: block">
								<span class="sr-only">Loading...</span>
							</div>
						</fieldset>						
					</div>
				</div>
				<div class="card-body">
					<div id="ca_mentions_chart"></div>
				</div>
			</div>
		</div>
	</div>
    {{--end mentions graph section--}}
    
    {{--engagement graph section--}}
    <div class="row">
		<div class="col-12">
			<div class="card widget-order-activity">
				<div class="card-header d-md-flex justify-content-between align-items-center">
					<h4 class="card-title">Engagements</h4>
					<div class="heading-elements mt-md-0 mt-50 d-flex align-items-center">
						<fieldset class="d-inline-block form-group position-relative has-icon-left mb-0 mr-1">
							<div class="spinner-border" id="spinner_eng" role="status" style="display: block">
								<span class="sr-only">Loading...</span>
							</div>
						</fieldset>						
					</div>
				</div>
				<div class="card-body">
					<div id="ca_engagement_chart"></div>
				</div>
			</div>
		</div>
	</div>
    {{--end engagement graph section--}}
    
    {{--key counts section--}}
    <div class="row">
		<div class="col-12">
			<div class="card widget-order-activity">
				<div class="card-header d-md-flex justify-content-between align-items-center">
					<h4 class="card-title">Key counts comparison</h4>
					<div class="heading-elements mt-md-0 mt-50 d-flex align-items-center">
						<fieldset class="d-inline-block form-group position-relative has-icon-left mb-0 mr-1">
							<div class="spinner-border" id="spinner_key_counts" role="status" style="display: block">
								<span class="sr-only">Loading...</span>
							</div>
						</fieldset>						
					</div>
				</div>
				<div class="card-body">
					<div id="ca_key_counts"></div>
				</div>
			</div>
		</div>
	</div>
    {{--end source channels section--}}
    
    {{--source channels section--}}
    <div class="row">
		<div class="col-12">
			<div class="card widget-order-activity">
				<div class="card-header d-md-flex justify-content-between align-items-center">
					<h4 class="card-title">Main source channels</h4>
					<div class="heading-elements mt-md-0 mt-50 d-flex align-items-center">
						<fieldset class="d-inline-block form-group position-relative has-icon-left mb-0 mr-1">
							<div class="spinner-border" id="spinner_sources" role="status" style="display: block">
								<span class="sr-only">Loading...</span>
							</div>
						</fieldset>						
					</div>
				</div>
				<div class="card-body">
					<div id="ca_source_channels_data"></div>
				</div>
			</div>
		</div>
	</div>
    {{--end source channels section--}}
    
    {{-- Sentiments --}}
    <div class="row">    
        <div class="col-6" id="senti_container">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Sentiments data</h4>
                </div>
                <div class="card-body" style="min-height: 506px;">
                    <script src="{{asset('vendors/js/charts/apexcharts.js')}}"></script>
                    <?php
                    $tids = explode(",", $ca_obj->get_ca_tids($ca_id));

                    for($i=0; $i<count($tids); $i++)
                    {

                        $senti_data = explode("|", trim($ca_obj->get_topic_sentiments_data($tids[$i])));
                        //echo '<pre>'; print_r($senti_data);
                        //$touchpoint_data = $tp_obj->get_touchpoint_data($touch_points_data[$i]->cx_tp_tp_id);

                    ?>
                    <div class="col-sm-4" style="float:left; margin-bottom: 15px;">
                        <div id="sentichart<?php echo $tids[$i]; ?>"></div>


                        <script type="text/javascript">

                            var options = {
                                  series: [<?php echo $senti_data[0]; ?>, <?php echo $senti_data[1]; ?>, <?php echo $senti_data[2]; ?>],
                                  chart: {
                                  width: 210,
                                  type: 'donut',
                                    events: {
                                            dataPointSelection: function(event, chartContext, config) {
                                                //alert(opts.w.config.xaxis.categories[opts.dataPointIndex] + " "+chartContext+" "+opts)
                                                //console.log(config);
                                                //console.log(config.w.config.series[config.seriesIndex])
                                                //console.log(config.w.config.series[config.seriesIndex].name)
                                                //console.log(config.w.config.series[config.seriesIndex].data[config.dataPointIndex])
                                                //console.log(config.w.config.series[config.dataPointIndex])
                                                //console.log("hi: "+config.w.config.labels[config.dataPointIndex].trim()+"<?php //echo $tids[$i]; ?>")
                                                var senti_topicid = config.w.config.labels[config.dataPointIndex].trim()+"~<?php echo $tids[$i]; ?>";
                                                //console.log(senti_topicid);
                                                load_posts('competitor_analysis', 'All', '{{ csrf_token() }}', 'open', senti_topicid, '');
                                            }
                                        }
                                },
                                colors: ['#3bdb8b','#fe5a5c', '#5b8eee'],
                                labels: ['Positive', 'Negative', 'Neutral'],
                                legend: false
                                };

                                var chart<?php echo $tids[$i]; ?> = new ApexCharts(document.querySelector("#sentichart<?php echo $tids[$i]; ?>"), options);
                                chart<?php echo $tids[$i]; ?>.render();
                        </script>
                        <div style="text-align: center; height: 50px;"><?php echo $ca_obj->get_topic_name($tids[$i]); ?></div>
                    </div>
                    <?php
                    }

                    ?>
                    
                </div>
            </div>
        </div>
        
        {{-- Emotions bar chart --}}
        <div class="col-6">
            <div class="card widget-order-activity">
                <div class="card-header d-md-flex justify-content-between align-items-center">
                    <h4 class="card-title">Emotions</h4>

                </div>
                <div class="card-body">
                    <div id="ca_emotions_group_bar_chart"></div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Reach and Influencers --}}
    <div class="row">
        {{--Reach--}}
        <div class="col-3">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Reach (Impressions)</h4>
                </div>
                <div class="card-body" style="min-height: 560px;">
                    <div id="reach_container">
                        
                    </div>
                </div>
            </div>
        </div>
        
        {{--Influencers--}}
        <div class="col-9">
            <div class="card marketing-campaigns">
                <div class="card-header d-flex justify-content-between align-items-center pb-1">
                    <h4 class="card-title">Influencers</h4>
                    
                </div>

                <!-- Nav tabs -->
                <ul class="nav nav-tabs nav-fill card-body pb-0" id="myTab" role="tablist" style="margin-bottom: 0px !important;">
                    <?php
                    $tids = explode(",", $ca_obj->get_ca_tids($ca_id));

                    for($i=0; $i<count($tids); $i++)
                    {
                    ?>
                    <li class="nav-item"><a class="nav-link<?php if($i==0) echo ' active'; ?>" id="tab-<?php echo $i; ?>" data-toggle="tab" href="#tab-data-<?php echo $i; ?>" role="tab" aria-controls="tab-data-<?php echo $i; ?>" aria-selected="true"> <?php echo $ca_obj->get_topic_name($tids[$i]); ?> </a></li>
                    <?php
                    }
                    ?>
                    
                </ul>
                <style>
                    .centeraligh {
                        text-align: center;
                    }

                    .show {
                        display: block !important;
                    }

                    .hide {
                        display: none !important;
                    }
                    .mytabs-height { height: 390px; }
                </style>
                <!-- Tab panes -->
                <div class="tab-content pt-1">
                    <?php
                    $pt = 100;
                    $tids = explode(",", $ca_obj->get_ca_tids($ca_id));

                    for($i=0; $i<count($tids); $i++)
                    {
                    ?>
                    <div class="tab-pane<?php if($i==0) echo ' active'; ?>" id="tab-data-<?php echo $i; ?>" role="tabpanel" aria-labelledby="tab-<?php echo $i; ?>">
                        <div class="table-responsive">
                            <!--Sub tabs-->
                            <ul class="nav nav-tabs nav-fill card-body pb-0" id="myTab" role="tablist1">
                            <?php
                            $inf_types = array("nano", "micro", "midtier", "macro", "mega", "celebrity");
                            
                            for($j=0; $j<count($inf_types); $j++)
                            {
                            ?>
                                <li class="nav-item"><a class="nav-link<?php if($j==0) echo ' active'; ?>" id="subtab-<?php echo $i.'-'.$j; ?>" data-toggle="tab" href="#subtab-data-<?php echo $i.'-'.$j; ?>" role="tab" aria-controls="subtab-data-<?php echo $i.'-'.$j; ?>" aria-selected="true" style="text-transform: capitalize;"> <?php echo $inf_types[$j]; ?> </a></li>
                            <?php
                            }
                            ?>
                            </ul>
                            <!--sub tabs content-->
                            <div class="tab-content pt-1">
                                <?php
                                
                                for($j=0; $j<count($inf_types); $j++)
                                {
                                ?>
                                <div class="tab-pane<?php if($j==0) echo ' active'; ?>" id="subtab-data-<?php echo $i.'-'.$j; ?>" role="tabpanel" aria-labelledby="subtab-<?php echo $i.'-'.$j; ?>">
                                    <div class="table-responsive mytabs-height">
                                    <!-- table start -->
                                    <table id="table-marketing-campaigns" class="table table-borderless table-marketing-campaigns mb-0">
                                        <thead>
                                            <tr>
                                                <th style="padding-left: 80px">Name</th>
                                                <th class="centeraligh">Source</th>
                                                <th class="centeraligh">Country</th>
                                                <th class="centeraligh">Followers</th>
                                                <th class="centeraligh">Posts</th>
                                            </tr>
                                        </thead>
                                        <tbody id="<?php echo $inf_types[$j]; ?>_<?php echo $tids[$i]; ?>">
                                        <?php if($i == 0) { ?>    
                                            <tr id="ca_inf_loading"><td><div class="spinner-border" role="status" style="margin-top:20px;"><span class="sr-only">Loading...</span></div></td></tr>
                                        <?php } ?>
                                        </tbody>
                                    </table>
                                    <!-- table ends -->
                                </div>
                                </div>
                                <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="ca_tids" id="ca_tids" value="<?php echo $ca_obj->get_ca_tids($ca_id); ?>">
        
</section>
<!-- Sortable lists section end -->



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
@endsection @section('page-scripts')
<script src="{{asset('js/scripts/extensions/drag-drop.js')}}"></script>
<script src="{{asset('js/scripts/popover/popover.js')}}"></script>

<script src="{{asset('js/scripts/ca-script.js')}}"></script>
<script src="{{asset('js/scripts/custom.js')}}"></script>
<script src="{{asset('js/scripts/pickers/dateTime/pick-a-datetime.js')}}"></script>
<script src="{{asset('js/scripts/forms/select/form-select2.js')}}"></script>
<script type="text/javascript">
    $(document).ready(function() {
        load_ca_data('ca_analysis', '{{ csrf_token() }}', '<?php echo $ca_id; ?>');
    });
</script>
@endsection
