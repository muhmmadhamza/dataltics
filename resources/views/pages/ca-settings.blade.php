@extends('layouts.contentLayoutMaster')
{{-- page Title --}}
@section('title','Competitor Analysis Settings')
{{-- vendor css --}}
@section('vendor-styles')
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/charts/apexcharts.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/swiper.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/dragula.min.css')}}">
@endsection
@section('page-styles')
<link rel="stylesheet" type="text/css" href="{{asset('css/dashboard-ecommerce.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/dashboard-analytics.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/widgets.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/tagsinput.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/forms/select/select2.css')}}">


@endsection
@section('content')
<!-- Dashboard Ecommerce Starts -->
<section>
    
    <!-- Add topic button -->
    <div class="col-sm-12">
        <div class="col-sm-2 col-12 dashboard-users-success" style="margin: 0px; padding: 0px;">
            <div class="card text-center">
                <div class="card-body py-1">
                    <div class="badge-circle badge-circle-lg badge-circle-light-success mx-auto mb-50">
                        <a href="javascript:void(0);" data-toggle="modal" data-target="#addCompDashboardModal"><i class="bx bx-plus-medical font-medium-5"></i></a>
                    </div>
                    <div class="text-muted" style="padding-bottom:10px;">Create a competitor analysis dashboard</div>
                </div>
            </div>
        </div>
        
        
    </div>
    <!-- End add topic button -->
    {{--Add competitor modal popup--}}
    <div class="modal fade text-left w-100" id="addCompDashboardModal" tabindex="-1" aria-labelledby="add_cdash_popup_title" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-centered modal-dialog-scrollable modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="add_cdash_popup_title">Create a competitor analysis dashboard</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="bx bx-x"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <section id="add-ca-section" style="overflow: hidden;"><!---->
                        <div class="col-12" style="padding: 0px; margin-top: 16px;">
                            <div class="col-md-12" style="padding: 0px;">
                                <div class="card" style="box-shadow: none;">
                                    <div class="card-body" style="padding:0px;">
                                        <form name="create_cdash" id="create_cdash" autocomplete="off" enctype="multipart/form-data">
                                            <div class="row">
                                                <div id="error_msg" style="display: none; color: #ff0000; padding-left:17px;"></div>
                                                <div class="col-md-12">
                                                    <fieldset class="form-group">
                                                        <label for="basicInput">Dashboard Title</label>
                                                        <input type="text" class="form-control" id="ca_title" name="ca_title" placeholder="Analysis title" required="required">
                                                    </fieldset>

                                                    <fieldset class="form-group">
                                                        <label for="basicInputFile">Select dashboards</label>
                                                        <select name="ca_tids[]" id="ca_tids" class="select2 form-control select2-hidden-accessible" multiple="multiple" required="required">
                                                            <?php
                                                            for($i=0; $i<count($topics_data); $i++)
                                                            {
                                                            ?>
                                                                <option value="<?php echo $topics_data[$i]->topic_id; ?>"><?php echo $topics_data[$i]->topic_title; ?></option>
                                                            <?php
                                                                //check if there are sub topics
                                                                $subtopics = $ca_obj->get_subtopics($topics_data[$i]->topic_id);
                                                                if($subtopics != 'NA')
                                                                {
                                                                    for($p=0; $p<count($subtopics); $p++)
                                                                    {
                                                                        echo '<option value="'.$topics_data[$i]->topic_id.'_'.$subtopics[$p]->exp_id.'">'.$topics_data[$i]->topic_title.' - '.$subtopics[$p]->exp_name.'</option>';
                                                                    }
                                                                }
                                                            }
                                                            ?>
                                                        </select>
                                                    </fieldset>
                                                    
                                                    <fieldset class="form-group">
                                                        <label for="basicInputFile">&nbsp;</label>
                                                        <div class="custom-file">
                                                            <div class="spinner-border" role="status" id="loading_icon_topic" style="display:none;">
                                                                <span class="sr-only">Loading...</span>
                                                            </div><button type="submit" class="btn btn-primary mr-1 mb-1">Create dashboard</button>&nbsp;<button type="button" class="btn btn-light mr-1 mb-1" data-dismiss="modal" aria-label="Close">Cancel</button>
                                                            <input type="hidden" name="mode" id="mode" value="create_competitor_analysis">
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
                </div>
            </div>
        </div>
    </div>
    {{-- END: Add competitor modal popup --}}
    
    {{-- Competitor analysis list --}}
    <div class="col-12"><h3>Dashboards list</h3></div>
    <div class="row" style="margin:0px !important;">
        <?php
        for($i=0; $i<count($ca_data); $i++)
        {      
        ?>
        <div class="col-sm-6" style="float: left;">
            <div class="card">
                <div class="card-header bg-light" style="margin-bottom: 15px;"><!--background: #8ac4d5; -->
                    <h4 class="greeting-text" style="color:#ffffff;">{{$ca_data[$i]->ca_title}}</h4>
                </div>
                <div class="card-body pt-0">
                    <div class="d-flex justify-content-between align-items-end">
                        <?php
                        $topic_names = '';
                        $topic_ids = explode(",", $ca_data[$i]->ca_tids);
                        for($j=0; $j<count($topic_ids); $j++)
                        {
                            $topic_names .= '<div class="badge badge-pill badge-light-secondary mr-1 mb-1" style="float:left;">'.$ca_obj->get_topic_name($topic_ids[$j]).'</div>';
                        }
                        ?>
                        <div class="col-12"">
                            <div style="min-height: 50px;"><?php echo $topic_names; ?></div>
                            <div style="clear:both;"></div>
                            <p><br>Created: <?php echo date("jS M, y h:i A", strtotime($ca_data[$i]->ca_date)); ?></p>
                            <button type="button" class="btn btn-icon btn-success mr-1 mb-1" data-toggle="popover" data-content="Load dashboard" data-trigger="hover" data-placement="top" data-original-title="" title="" onclick="javascript:load_topic('{{$ca_data[$i]->ca_id}}', '{{ csrf_token() }}', 'comp_analysis');"><i class="bx bxs-right-arrow-square"></i></button>
                            <button type="button" class="btn btn-icon btn-danger mr-1 mb-1" data-toggle="modal" data-content="Delete dashboard" data-trigger="hover" data-placement="top" data-original-title="" title="" data-target="#delete_modal" onclick="javascript:set_del_data('{{$ca_data[$i]->ca_id}}', '{{ csrf_token() }}', 'comp_analysis');"><i class="bx bx-minus-circle"></i></button>
                            
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
        <?php
        }
        ?>
    </div>
    {{-- END: Competitor analysis list --}}
  </section>
  <!-- END: Topics list -->
  
  </section>
  <!-- Sortable lists section end -->
  <!-- Dashboard Ecommerce ends -->
  @endsection
  {{-- vendor scripts --}}
  @section('vendor-scripts')
  
  
  
  @endsection
  
  @section('page-scripts')
  <!--<script src="{{asset('js/scripts/pages/dashboard-ecommerce.js')}}"></script>
  <script src="{{asset('js/scripts/pages/dashboard-analytics.js')}}"></script>
  <script src="{{asset('js/scripts/cards/widgets.js')}}"></script>-->
  <script src="{{asset('js/scripts/custom.js')}}"></script>
  <script src="{{asset('js/scripts/ca-script.js')}}"></script>
  <script src="{{asset('js/scripts/tagsinput.js')}}"></script>
  <script src="{{asset('js/scripts/popover/popover.js')}}"></script>
  <script src="{{asset('vendors/js/forms/select/select2.full.min.js')}}"></script>
  <script src="{{asset('js/scripts/forms/select/form-select2.js')}}"></script>
  
  @endsection
  