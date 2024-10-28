@extends('layouts.contentLayoutMaster')
{{-- page Title --}}
@section('title','Reports')
{{-- vendor css --}}
@section('vendor-styles')

@endsection
@section('page-styles')


<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/pickadate/pickadate.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/daterange/daterangepicker.css')}}">
<!--<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/forms/select/select2.min.css')}}">-->
<link rel="stylesheet" type="text/css" href="{{asset('vendors/bootstrap-multiselect/css/bootstrap-multiselect.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/app-file-manager.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/dashboard-ecommerce.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/dashboard-analytics.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/widgets.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/wizard.css')}}">

@endsection
@section('content')
<section id="reports_settings">
    <div class="col-sm-12 card" style="padding:0px;">
        {{--Left area reports--}}
        <!-- File Manager app overlay -->
        <div class="app-file-overlay"></div>
        <div class="app-file-area">
            <!-- File App Content Area -->
            <!-- App File Header Starts -->
            <div class="app-file-header">
                <!-- Header search bar starts -->

                <!-- Header search bar Ends -->
                <!-- Header Icons Starts -->
                
                <!-- Header Icons Ends -->
            </div>
            <!-- App File Header Ends -->

            <!-- App File Content Starts -->
            <div class="app-file-content p-2">
                <h5>Existing generated reports <a href="#" data-toggle="modal" data-target="#addReportModal" style="float: right;">Generate new report</a></h5>

                <!-- App File - Recent Accessed Files Section Starts -->
                <label class="app-file-label">&nbsp;</label>
                <div class="row app-file-recent-access">
                    <?php
                    if(count($reports_list) > 0)
                    {
                        $first_img = '';
                        $logo = '';
                        $filesize = '';
                        
                        for($i=0; $i<count($reports_list); $i++)
                        {
                            if(file_exists(public_path(). '/reports-images/'.$reports_list[$i]->rs_bg_image_first_page) && is_file(public_path(). '/reports-images/'.$reports_list[$i]->rs_bg_image_first_page))
                                $first_img = env('SERVER_HTTP_PATH'). 'reports-images/'.$reports_list[$i]->rs_bg_image_first_page;
                            else
                                $first_img = '';
                            
                            if(file_exists(public_path(). '/reports-images/'.$reports_list[$i]->rs_logo) && is_file(public_path(). '/reports-images/'.$reports_list[$i]->rs_logo))
                                $logo = '<img src="'.env('SERVER_HTTP_PATH'). 'reports-images/'.$reports_list[$i]->rs_logo.'" width="50" style="margin: 5px;">';
                            else
                                $logo = '';
                            
                            if(file_exists(public_path(). '/dashboard-reports/'.$reports_list[$i]->rs_filename) && is_file(public_path(). '/dashboard-reports/'.$reports_list[$i]->rs_filename))
                                $filesize = filesize(public_path(). '/dashboard-reports/'.$reports_list[$i]->rs_filename);
                            else
                                $filesize = 0;
                    ?>
                    <div class="col-md-3 col-6">
                        <div class="card border shadow-none mb-1 app-file-info">
                            <div class="app-file-content-logo card-img-top bg-rgba-secondary" style="height: 150px; border-bottom: 1px solid #dfe3e7; background: url('<?php echo $first_img; ?>') !important; background-size: cover !important; background-repeat: no-repeat;">
                                
                                <?php echo $logo; ?>
                            </div>
                            <div class="card-body p-50">
                                <?php
                                if($reports_list[$i]->rs_status == 'c')
                                {
                                ?>
                                    <div class="app-file-recent-details">
                                        <div class="app-file-name font-size-small font-weight-bold"><a href="https://dashboard.datalyticx.ai/dashboard-reports/<?php echo $reports_list[$i]->rs_filename; ?>" download><?php echo $reports_list[$i]->rs_filename; ?></a></div>
                                        <div class="app-file-size font-size-small text-muted mb-25"><?php echo number_format(($filesize/1024)/1024, 2); ?> MB
                                        <?php
                                        if(isset($reports_list[$i]->rs_uid_loggedin))
                                            echo ' | By: '.$cust_obj->get_customer_name($reports_list[$i]->rs_uid_loggedin);
                                        ?>
                                        </div>
                                        <div class="app-file-last-access font-size-small text-muted">Generated : <?php echo date("jS F, Y", strtotime($reports_list[$i]->rs_completed_time)); ?> | <a href="javascript:void(0);" data-toggle="modal" data-content="Delete report" data-trigger="hover" data-placement="top" data-original-title="" title="" data-target="#delete_modal" onclick="javascript:set_del_data('<?php echo $reports_list[$i]->rs_id; ?>', '{{ csrf_token() }}', 'report');" style="color:red;">Delete</a></div>
                                    </div>
                                <?php
                                }
                                else
                                {
                                ?>
                                    <div class="app-file-recent-details">
                                        <div class="app-file-name font-size-small font-weight-bold">Processing ...</div>
                                        <div class="app-file-size font-size-small text-muted mb-25"><?php echo $topic_obj->get_topic_name($reports_list[$i]->rs_tid); ?></div>
                                        <div class="app-file-last-access font-size-small text-muted">Initiated : <?php echo date("jS, F Y", strtotime($reports_list[$i]->rs_req_time)); ?></div>
                                    </div>
                                <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php
                        }
                    }
                    else
                    {
                    ?>
                    <div class="col-md-3 col-6">
                        No data found.
                    </div>
                    <?php
                    }
                    ?>                    
                </div>
                <!-- App File - Recent Accessed Files Section Ends -->
            </div>
        </div>
        {{--END: Left area reports--}}
    </div>
    {{--Add report modal popup--}}
    <div class="modal fade text-left w-100" id="addReportModal" aria-labelledby="add_report_popup_title" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-centered modal-dialog-scrollable modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="add_report_popup_title">Generate new report</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="bx bx-x"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <section id="vertical-wizard">
                        <div class="card">
                            <div class="card-header" style="padding-bottom: 30px;">
                                <h4 class="card-title" style="text-transform: none;">Provide respective information in following steps to generate the dashboard report.</h4>
                            </div>
                            <div class="card-body">
                                <form action="#" class="wizard-vertical" name="add_report_form" id="add_report_form" enctype="multipart/form-data">
                                    <!-- step 1 -->
                                    <h3>
                                        <span class="fonticon-wrap mr-1">
                                            <i class="livicon-evo" data-options="name:gear.svg; size: 50px; style:lines; strokeColor:#adb5bd;"></i>
                                        </span>
                                        <span class="icon-title">
                                            <span class="d-block">General</span>
                                            <small class="text-muted">Information about PDF report</small>
                                        </span>
                                    </h3>
                                    <!-- step 1 end-->
                                    <!-- step 1 content -->
                                    <fieldset class="pt-0">
                                        <h6 class="pb-50">Enter general details</h6>
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label for="rs_bg_color">Background color (#000000)</label>
                                                    <input type="text" class="form-control" id="rs_bg_color" name="rs_bg_color">
                                                    <small class="text-muted form-text">Please enter hexa decimal color code</small>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label for="rs_font_color">Font color for the background color (#ffffff)</label>
                                                    <input type="text" class="form-control" id="rs_font_color" name="rs_font_color">
                                                    <small class="text-muted form-text">Please enter hexa decimal color code</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label for="rs_bg_image_first_page">First page background image</label>
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="rs_bg_image_first_page" name="rs_bg_image_first_page">
                                                        <label class="custom-file-label" for="inputGroupFile01">Choose file</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label for="rs_bg_image_last_page">Last page background image</label>
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="rs_bg_image_last_page" name="rs_bg_image_last_page">
                                                        <label class="custom-file-label" for="inputGroupFile01">Choose file</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label for="rs_logo">Logo</label>
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="rs_logo" name="rs_logo">
                                                        <label class="custom-file-label" for="rs_logo">Choose file</label>
                                                    </div>
                                                </div>
                                            </div>                                            
                                        </div>
                                    </fieldset>
                                    <!-- step 1 content end-->
                                    <!-- step 2 -->
                                    <h3>
                                        <span class="fonticon-wrap mr-1">
                                            <i class="livicon-evo"
                                               data-options="name:desktop.svg; size: 50px; style:lines; strokeColor:#adb5bd;"></i>
                                        </span>
                                        <span class="icon-title">
                                            <span class="d-block">Dashboard</span>
                                            <small class="text-muted">Select and choose dates</small>
                                        </span>
                                    </h3>
                                    <!-- step 2 end-->
                                    <!-- step 2 content -->
                                    <fieldset class="pt-0">
                                        <h6 class="py-50">Select a dashboard from the list below</h6>
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label for="proposalTitle1">Select dashboard</label>
                                                    <select name="rs_tid" id="rs_tid" class="form-control" onchange="javascript:load_subtopics_list(this.value);">
                                                        <?php
                                                        $initial_topic_id = 0;
                                                        for($i=0; $i<count($topics_data); $i++)
                                                        {
                                                            if($i==0)
                                                                $initial_topic_id = $topics_data[$i]->topic_id;
                                                            
                                                            echo '<option value="'.$topics_data[$i]->topic_id.'">'.$topics_data[$i]->topic_title.'</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <label for="rs_topic_from_date">From date</label>
                                                <fieldset class="form-group position-relative has-icon-left">
                                                    <input type="text" class="form-control pickadate" placeholder="Select Date" name="rs_topic_from_date" id="rs_topic_from_date">
                                                    <div class="form-control-position">
                                                      <i class='bx bx-calendar'></i>
                                                    </div>
                                                </fieldset>                           
                                            </div>
                                            <div class="col-sm-6">
                                                <label for="rs_topic_to_date">To date</label>
                                                <fieldset class="form-group position-relative has-icon-left">
                                                    <input type="text" class="form-control pickadate" placeholder="Select Date" name="rs_topic_to_date" id="rs_topic_to_date">
                                                    <div class="form-control-position">
                                                      <i class='bx bx-calendar'></i>
                                                    </div>
                                                </fieldset>                           
                                            </div>
                                        </div>
                                        <div class="row" style="height: 136px;"></div>
                                    </fieldset>
                                    <!-- step 2 content end-->
                                    <!-- section 3 -->
                                    <h3>
                                        <span class="fonticon-wrap mr-1">
                                            <i class="livicon-evo"
                                               data-options="name:morph-stack.svg; size: 50px; style:lines; strokeColor:#adb5bd;"></i>
                                        </span>
                                        <span class="icon-title">
                                            <span class="d-block">Indepth analysis</span>
                                            <small class="text-muted">Set date & background</small>
                                        </span>
                                    </h3>
                                    <!-- section 3 end-->
                                    <!-- step 3 content -->
                                    <fieldset class="pt-0">
                                        <h6 class="py-50">Set indepth analysis settings below</h6>
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label for="rs_subtopic_font_color">Font color for title page (#000000)</label>
                                                    <input type="text" class="form-control" id="rs_subtopic_font_color" name="rs_subtopic_font_color">
                                                    <small class="text-muted form-text">Please enter hexa decimal color code</small>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label for="rs_subtopic_bg_image">Title page background image</label>
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="rs_subtopic_bg_image" name="rs_subtopic_bg_image">
                                                        <label class="custom-file-label" for="rs_subtopic_bg_image">Choose file</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label for="rs_subtopic_bg_color">Bg color in-depth analysis (#000000)</label>
                                                    <input type="text" class="form-control" id="rs_subtopic_bg_color" name="rs_subtopic_bg_color">
                                                    <small class="text-muted form-text">Please enter hexa decimal color code</small>
                                                </div>
                                            </div>
                                            <!--<div class="col-sm-6">
                                                <label for="sub_topics">Select in-depth analysis</label>
                                                <select class="form-control" multiple="multiple" name="sub_topics[]" id="sub_topics">
                                                    <?php
                                                    //$sub_topics = Helper::get_subtopics($initial_topic_id);

                                                    //if(count($sub_topics) > 0)
                                                    //{
                                                    ?>

                                                        <?php
                                                        //for($j=0; $j<count($sub_topics); $j++)
                                                        //{
                                                    ?>
                                                    <option value="<?php //echo $sub_topics[$j]->exp_id; ?>"><?php //echo $sub_topics[$j]->exp_name; ?></option>
                                                    <?php

                                                        //}
                                                    //}
                                                    ?>
                                                </select>
                                                
                                            </div>-->
                                            <div class="col-sm-6" style="height: 100px; overflow-y: auto;" id="subtopics_list_con">
                                                <label for="sub_topics">Select in-depth analysis</label>
                                                <?php
                                                    $sub_topics = Helper::get_subtopics($initial_topic_id);
                                                    
                                                    if(count($sub_topics) > 0)
                                                    {
                                                        for($j=0; $j<count($sub_topics); $j++) 
                                                        {
                                                ?>
                                                            <div><input type="checkbox" name="sub_topics[]" id="sub_topics<?php echo $sub_topics[$j]->exp_id; ?>" value="<?php echo $sub_topics[$j]->exp_id; ?>"><label style="text-transform: capitalize; padding-left: 5px;"><?php echo $sub_topics[$j]->exp_name; ?></label></div>
                                                <?php
                                                        }
                                                    }
                                                 ?>
                                                </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <label for="rs_subtopic_from_date">From date</label>
                                                <fieldset class="form-group position-relative has-icon-left">
                                                    <input type="text" class="form-control pickadate" placeholder="Select Date" name="rs_subtopic_from_date" id="rs_subtopic_from_date">
                                                    <div class="form-control-position">
                                                      <i class='bx bx-calendar'></i>
                                                    </div>
                                                </fieldset>                           
                                            </div>
                                            <div class="col-sm-6">
                                                <label for="rs_subtopic_to_date">To date</label>
                                                <fieldset class="form-group position-relative has-icon-left">
                                                    <input type="text" class="form-control pickadate" placeholder="Select Date" name="rs_subtopic_to_date" id="rs_subtopic_to_date">
                                                    <div class="form-control-position">
                                                      <i class='bx bx-calendar'></i>
                                                    </div>
                                                </fieldset>                           
                                            </div>
                                        </div>
                                        <div class="row" style="height: 116px;"></div>
                                    </fieldset>
                                    <!-- step 3 content end-->
                                    <!-- step 4 -->
                                    <h3>
                                        <span class="fonticon-wrap mr-1">
                                            <i class="livicon-evo" data-options="name:hand-right.svg; size: 50px; style:lines; strokeColor:#adb5bd;"></i>
                                        </span>
                                        <span class="icon-title">
                                            <span class="d-block">Confirm</span>
                                            <small class="text-muted">Initiate report generation</small>
                                        </span>
                                    </h3>
                                    <!-- step 4 end-->
                                    <!-- step 4 content -->
                                    <fieldset class="pt-0">
                                        <h6 class="py-50">Confirm and initiate report generation process</h6>
                                        <div class="row">
                                            <div class="col-12">
                                                <br>You can go back to previous step and make changes if required before initiating report generation process.<br><br>Report generation process can take variable time deponding on the dashboard selected.<br><br>Once the process is complete, you will be notified via email.<br><br>Click the "Submit" button below to start the process.
                                            </div>
                                        </div>
                                        <div class="row" style="height: 110px;"></div>
                                        <div class="spinner-border" role="status" id="loading_icon_report" style="display:none;">
                                            <span class="sr-only">Loading...</span>
                                        </div>
                                    </fieldset>
                                    <!-- step 4 content end-->
                                    <input type="hidden" name="_token" id="_token" value="{{ csrf_token() }}">
                                    <input type="hidden" name="mode" id="mode" value="initiate_report">
                                </form>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
    {{-- END: Add report modal popup --}}
</section>

@endsection
{{-- vendor scripts --}}
@section('vendor-scripts')
<script src="{{asset('vendors/js/extensions/jquery.steps.min.js')}}"></script>
<script src="{{asset('vendors/js/forms/validation/jquery.validate.min.js')}}"></script>
<script src="{{asset('vendors/js/pickers/pickadate/picker.js')}}"></script>
<script src="{{asset('vendors/js/pickers/pickadate/picker.date.js')}}"></script>
<!--<script src="{{asset('vendors/js/pickers/pickadate/picker.time.js')}}"></script>-->
<script src="{{asset('vendors/js/pickers/pickadate/legacy.js')}}"></script>
<script src="{{asset('vendors/js/extensions/moment.min.js')}}"></script>
<script src="{{asset('vendors/js/pickers/daterange/daterangepicker.js')}}"></script>

<script src="{{asset('js/scripts/custom.js')}}"></script>
<script src="{{asset('vendors/bootstrap-multiselect/js/bootstrap-multiselect.js')}}"></script>

<!--<script src="{{asset('vendors/js/forms/select/select2.full.min.js')}}"></script>
<script src="{{asset('js/scripts/forms/select/form-select2.js')}}"></script>-->


@endsection

@section('page-scripts')
<!--<script src="{{asset('js/scripts/custom.js')}}"></script>
<script src="{{asset('js/scripts/pages/app-file-manager.js')}}"></script>-->
<script type="text/javascript">
$(document).ready(function () {
    
    /*$('#sub_topics').multiselect({
        nonSelectedText: 'All',
        enableFiltering: false,
        enableCaseInsensitiveFiltering: false,
        buttonWidth:'100%'
    });*/
        
    var base_url = "https://dashboard.datalyticx.ai/";
    
    $(".wizard-vertical").steps({
        headerTag: "h3",
        bodyTag: "fieldset",
        transitionEffect: "fade",
        enableAllSteps: false,
        stepsOrientation: "vertical",
        labels: {
          finish: 'Submit'
        },
        onFinished: function (event, currentIndex) {
            //alert("1Form will be submitted"+$("#_token").val());
            event.preventDefault();
            $("#loading_icon_report").show();
            //console.log("here");
            
            var formData = new FormData($(this)[0]);

            $.ajax({
                type: "POST",
                cache: false,
                contentType: false,
                processData: false,
                url: base_url+"handle-report",
                data: formData,
                success : function(response){ 
                    $("#loading_icon_topic").hide();
                    if(response.trim() == 'Success')
                        window.location = base_url+'reports';
                    else
                    {
                        $("#error_msg").html(response);
                        //$("#error_msg").html("Some problem occured, try later.");
                        $("#error_msg").show();
                    }
                }
            });
        }
    });
    
    //////////////////////////////////
    $(".current").find(".step-icon").addClass("bx bx-time-five");
    $(".current").find(".fonticon-wrap .livicon-evo").updateLiviconEvo({
      strokeColor: '#5A8DEE'
    });
    
    // Icon change on state
    // if click on next button icon change
    $(".actions [href='#next']").click(function () {
        $(".done").find(".step-icon").removeClass("bx bx-time-five").addClass("bx bx-check-circle");
        $(".current").find(".step-icon").removeClass("bx bx-check-circle").addClass("bx bx-time-five");
        // live icon color change on next button's on click
        $(".current").find(".fonticon-wrap .livicon-evo").updateLiviconEvo({
          strokeColor: '#5A8DEE'
        });
        $(".current").prev("li").find(".fonticon-wrap .livicon-evo").updateLiviconEvo({
          strokeColor: '#39DA8A'
        });
    });
    $(".actions [href='#previous']").click(function () {
        // live icon color change on next button's on click
        $(".current").find(".fonticon-wrap .livicon-evo").updateLiviconEvo({
          strokeColor: '#5A8DEE'
        });
        $(".current").next("li").find(".fonticon-wrap .livicon-evo").updateLiviconEvo({
          strokeColor: '#adb5bd'
        });
    });
    // if click on  submit   button icon change
    $(".actions [href='#finish']").click(function () {
        $(".done").find(".step-icon").removeClass("bx-time-five").addClass("bx bx-check-circle");
        $(".last.current.done").find(".fonticon-wrap .livicon-evo").updateLiviconEvo({
          strokeColor: '#39DA8A'
        });
    });
    // add primary btn class
    $('.actions a[role="menuitem"]').addClass("btn btn-primary");
    $('.icon-tab [role="menuitem"]').addClass("glow ");
    $('.wizard-vertical [role="menuitem"]').removeClass("btn-primary").addClass("btn-light-primary");
    
    //initiate pickadate calendar
    $('.pickadate').pickadate();
});
</script>
@endsection
