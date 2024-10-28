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

{{--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">--}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

@endsection @section('content')
<!--Search Form--->
@include('pages.topic-filter-form')
<!-- Dashboard Ecommerce Starts -->
<section class="list-group" id="card-drag-area">
    <div class="row" style="margin-right:0px;">
        <!-- Order Activity Chart Starts -->
        <div class="col-10">
            <div class="card widget-order-activity">
                <div class="card-header d-md-flex justify-content-between align-items-center">
                    <h4 class="card-title font_size_1point5_rem">M trends <span><i class="bx bx-info-circle" data-toggle="tooltip" title="" data-trigger="hover" data-original-title="Track trend of mentions around the topic"></i></span></h4>
                    <div class="heading-elements mt-md-0 mt-50 d-flex align-items-center">
                        <fieldset class="d-inline-block form-group position-relative has-icon-left mb-0 mr-1">
                            <div class="spinner-border" id="spinner" role="status" style="display: block">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </fieldset>
                        <fieldset class="d-inline-block form-group position-relative has-icon-left mb-0 mr-1" style="display:none !important;">
                            <input type="text" class="form-control daterange">
                            <div class="form-control-position">
                                <i class='bx bx-calendar'></i>
                            </div>
                        </fieldset>
                        <fieldset class="d-inline-block form-group position-relative has-icon-left mb-0 mr-1" style="display:none !important;">OR</fieldset>
                        <fieldset class="d-inline-block form-group position-relative has-icon-left mb-0 mr-1" style="display:none !important;">
                            <select class="custom-select" id="mentionselect">
                                <option selected="default" value="default">Select Filter</option>
                                <option value="mentions_today">Today</option>
                                <option value="mentions_this_week">This Week</option>
                                <option value="mentions_this_month">This Month</option>
                                <option value="10">Last 10 days</option>
                                <option value="20">Last 20 days</option>
                                <option value="30">Last 30 days</option>
                                <option value="90">Last 3 months</option>
                                <option value="180">Last 6 months</option>
                            </select>
                        </fieldset>
                        
                    </div>
                </div>
                <div class="card-body">
                    <div id="mention-activity-line-chart"></div>
                </div>
            </div>
        </div>
<!--i class='bx bx-info-circle' data-toggle="popover" data-content="Number of posts around the topic" data-trigger="hover" data-placement="top"></i> 
<button type="button" class="btn btn-primary" data-toggle="tooltip" title="" data-trigger="click" data-original-title="Click Triggered" aria-describedby="tooltip412318">-->
        <div class="col-sm-2 col-12 dashboard-users-success" style="margin: 0px; padding: 0px;">
            <div class="card text-center" style="margin-bottom:15px;">
                <div class="card-body py-1">
                    <div style="text-align:right;"><span><i class="bx bx-info-circle" data-toggle="tooltip" title="" data-trigger="hover" data-original-title="Number of posts around the topic"></i></span></div>
                    <div class="badge-circle badge-circle-lg badge-circle-light-secondary mx-auto mb-50" style="width: 80px; height: 80px;">
                        <i class="bx bx-stats font-large-3"></i>
                    </div>
                    <div class="text-muted line-ellipsis my_text1" style="padding: 8px 0px 9px 0px;">Mentions</div>
                    <h2 class="mb-0" id="total_mentions"></h2><small class="text-muted" id="total_mentions_uf"></small>
                </div>
            </div>
            <div class="card text-center">
                <div class="card-body py-1">
                    <div style="text-align:right;"><span><i class="bx bx-info-circle" data-toggle="tooltip" title="" data-trigger="hover" data-original-title="Number of views and impressions around the topic"></i></span></div>
                    <div class="badge-circle badge-circle-lg badge-circle-light-secondary mx-auto mb-50" style="width: 80px; height: 80px;">
                        <i class="bx bx-shuffle font-large-3"></i>
                    </div>
                    <div class="text-muted line-ellipsis my_text1" style="padding: 2px 0px 9px 0px;">Estimated reach</div>
                    <h2 class="mb-0" id="est_reach"></h2><small class="text-muted" id="est_reach_uf"></small>
                </div>
            </div>
        </div>
        <!-- Order Activity Chart Ends -->
    </div>
    <!-- Expand Order Activity Chart Starts -->
    <div class="row" id="engage_graph">
        <div class="col-12">
            <div class="card widget-order-activity hide" id="card">
                <div class="card-header d-md-flex justify-content-between align-items-center container">
                    <h4 class="card-title">Engagements Graph</h4>
                    <div class="heading-elements mt-md-0 mt-50 d-flex align-items-center">
                        <fieldset class="d-inline-block form-group position-relative has-icon-left mb-0 mr-1">
                            <div class="spinner-border" id="engage" role="status" style="display: block">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </fieldset>
                        <fieldset class="d-inline-block form-group position-relative has-icon-left mb-0 mr-1">
                            <input type="text" class="form-control" id="daterange">
                            <div class="form-control-position">
                                <i class='bx bx-calendar'></i>
                            </div>
                        </fieldset>
                        <fieldset class="d-inline-block form-group position-relative has-icon-left mb-0 mr-1">OR</fieldset>
                        <fieldset class="d-inline-block form-group position-relative has-icon-left mb-0 mr-1">
                            <select class="custom-select" id="engagementselect">
                                <option selected="default" value="default">Select Filter</option>
                                <option value="engagement_today">Today</option>
                                <option value="engagements_this_week">This Week</option>
                                <option value="engagements_this_month">This Month</option>
                                <option value="10">Last 10 days</option>
                                <option value="20">Last 20 days</option>
                                <option value="30">Last 30 days</option>
                                <option value="90">Last 3 months</option>
                                <option value="180">Last 6 months</option>
                            </select>
                        </fieldset>
                        <fieldset>
                            <ul class="list-inline mb-0">
                                <li><a data-action="fullscreen"> <i class="bx bx-fullscreen" id="closed"></i>
                                    </a></li>
                            </ul>
                        </fieldset>
                    </div>
                </div>
                <div class="card-content collapse show container">
                    <div class="card-body">
                        <p>
                            Click on <i class="bx bx-exit-fullscreen align-middle"></i> icon to see close card in action.
                        </p>
                        <div id="engage-activity-line-chart"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Engage Activity Chart Ends -->
    <!-- Expand Share Activity Chart Starts -->
    <div class="row" id="share_graph">
        <div class="col-12">
            <div class="card widget-order-activity hide" id="share_card">
                <div class="card-header d-md-flex justify-content-between align-items-center container">
                    <h4 class="card-title">Shares Graph</h4>
                    <div class="heading-elements mt-md-0 mt-50 d-flex align-items-center">
                        <fieldset class="d-inline-block form-group position-relative has-icon-left mb-0 mr-1">
                            <div class="spinner-border" id="shares_loading" role="status" style="display: block">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </fieldset><fieldset class="d-inline-block form-group position-relative has-icon-left mb-0 mr-1">
                            <input type="text" class="form-control" id="shares_daterange">
                            <div class="form-control-position">
                                <i class='bx bx-calendar'></i>
                            </div>
                        </fieldset>
                        <fieldset class="d-inline-block form-group position-relative has-icon-left mb-0 mr-1">OR</fieldset>
                        <fieldset class="d-inline-block form-group position-relative has-icon-left mb-0 mr-1">
                            <select class="custom-select" id="sharesselect">
                                <option selected="default" value="default">Select Filter</option>
                                <option value="shares_today">Today</option>
                                <option value="shares_this_week">This Week</option>
                                <option value="shares_this_month">This Month</option>
                                <option value="10">Last 10 days</option>
                                <option value="20">Last 20 days</option>
                                <option value="30">Last 30 days</option>
                                <option value="90">Last 3 months</option>
                                <option value="180">Last 6 months</option>
                            </select>
                        </fieldset>
                        <fieldset>
                            <ul class="list-inline mb-0">
                                <li><a data-action="sharescreen"> <i class="bx bx-fullscreen" id="closed"></i>
                                    </a></li>
                            </ul>
                        </fieldset>
                    </div>
                </div>
                <div class="card-content collapse show container">
                    <div class="card-body">
                        <p>
                            Click on <i class="bx bx-exit-fullscreen align-middle"></i> icon to see close card in action.
                        </p>
                        <div id="share-activity-line-chart"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Share Activity Chart Ends -->
    <!-- Like Activity Chart Starts -->
    <div class="row" id="like_graph">
        <div class="col-12">
            <div class="card widget-order-activity hide" id="like_card">
                <div class="card-header d-md-flex justify-content-between align-items-center container">
                    <h4 class="card-title">Likes Graph</h4>
                    <div class="heading-elements mt-md-0 mt-50 d-flex align-items-center">
                        <fieldset class="d-inline-block form-group position-relative has-icon-left mb-0 mr-1">
                            <div class="spinner-border" id="likes_loading" role="status" style="display: block">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </fieldset><fieldset class="d-inline-block form-group position-relative has-icon-left mb-0 mr-1">
                            <input type="text" class="form-control" id="likes_daterange">
                            <div class="form-control-position">
                                <i class='bx bx-calendar'></i>
                            </div>
                        </fieldset>
                        <fieldset class="d-inline-block form-group position-relative has-icon-left mb-0 mr-1">OR</fieldset>
                        <fieldset class="d-inline-block form-group position-relative has-icon-left mb-0 mr-1">
                            <select class="custom-select" id="likesselect">
                                <option selected="default" value="default">Select Filter</option>
                                <option value="likes_today">Today</option>
                                <option value="likes_this_week">This Week</option>
                                <option value="likes_this_month">This Month</option>
                                <option value="10">Last 10 days</option>
                                <option value="20">Last 20 days</option>
                                <option value="30">Last 30 days</option>
                                <option value="90">Last 3 months</option>
                                <option value="180">Last 6 months</option>
                            </select>
                        </fieldset>
                        <fieldset>
                            <ul class="list-inline mb-0">
                                <li><a data-action="likescreen"> <i class="bx bx-fullscreen" id="closed"></i>
                                    </a></li>
                            </ul>
                        </fieldset>
                    </div>
                </div>
                <div class="card-content collapse show container">
                    <div class="card-body">
                        <p>
                            Click on <i class="bx bx-exit-fullscreen align-middle"></i> icon to see close card in action.
                        </p>
                        <div id="likes-activity-line-chart"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Like Activity Chart Ends -->
    <!-- Expand And Remove Actions Ends -->
    <div class="row">
        <!-- Followers Danger Line Chart Starts -->
        <div class="col-xl-3 col-md-6">
            <div class="card widget-followers">
                <ul class="list-inline d-flex align-items-center justify-content-between" style="margin-bottom: 0px;">
                    <li style="text-align: left; margin: 15px 12px 0px 20px">
                        <h4 class="card-title font_size_1point5_rem">Engagements <span><i class="bx bx-info-circle" data-toggle="tooltip" title="" data-trigger="hover" data-original-title="Total Engagement earned across various social channels on posts around the topic"></i></span></h4>
                    </li>
                    <li style="text-align: end; margin: 20px; cursor: pointer;"><a data-action="fullscreen"> <i class="bx bx-fullscreen"></i>
                        </a></li>
                </ul>
                <div class="card-header d-flex align-items-center justify-content-between" style="padding: 0px 1.7rem">
                    <div class="d-flex align-items-center widget-followers-heading-right">
                        <h3 class="mr-2 font-weight-normal mb-0" id="eng_count"></h3>
                        <div class="d-flex flex-column align-items-center">
                            <i class='bx font-medium-1' id="engstatus"></i> <small class="text-muted" id="eng_per"></small>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="engagement_chart"></div>
                </div>
            </div>
        </div>
        <!-- Followers Danger Line Chart Ends -->
        <!-- Followers Primary Line Chart Starts -->
        <div class="col-xl-3 col-md-6">
            <div class="card widget-followers">
                <ul class="list-inline d-flex align-items-center justify-content-between" style="margin-bottom: 0px;">
                    <li style="text-align: left; margin: 15px 12px 0px 20px">
                        <h4 class="card-title font_size_1point5_rem">Shares <span><i class="bx bx-info-circle" data-toggle="tooltip" title="" data-trigger="hover" data-original-title="Total Shares earned across various social channels on posts around the topic"></i></span></h4>
                    </li>
                    <li style="text-align: end; margin: 20px; cursor: pointer;"><a data-action="sharescreen"> <i class="bx bx-fullscreen"></i>
                        </a></li>
                </ul>
                <div class="card-header d-flex align-items-center justify-content-between" style="padding: 0px 1.7rem">
                    <div class="d-flex align-items-center widget-followers-heading-right">
                        <h3 class="mr-2 font-weight-normal mb-0" id="share_count"></h3>
                        <div class="d-flex flex-column align-items-center">
                            <i class='bx font-medium-1' id="shrstatus"></i> <small class="text-muted" id="share_per"></small>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="share_chart"></div>
                </div>
            </div>
        </div>
        <!-- Followers Primary Line Chart Ends -->
        <!-- Likes box start -->
        <div class="col-xl-3 col-md-6">
            <div class="card widget-followers">
                <ul class="list-inline d-flex align-items-center justify-content-between" style="margin-bottom: 0px;">
                    <li style="text-align: left; margin: 15px 12px 0px 20px">
                        <h4 class="card-title font_size_1point5_rem">Likes <span><i class="bx bx-info-circle" data-toggle="tooltip" title="" data-trigger="hover" data-original-title="Total Likes earned across various social channels on posts around the topic"></i></span></h4>
                    </li>
                    <li style="text-align: end; margin: 20px; cursor: pointer;"><a data-action="likescreen"> <i class="bx bx-fullscreen"></i>
                        </a></li>
                </ul>
                <div class="card-header d-flex align-items-center justify-content-between" style="padding: 0px 1.7rem">
                    <div class="d-flex align-items-center widget-followers-heading-right">
                        <h3 class="mr-2 font-weight-normal mb-0" id="like_count"></h3>
                        <div class="d-flex flex-column align-items-center">
                            <i class='bx font-medium-1' id="likestatus"></i> <small class="text-muted" id="like_per"></small>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="likes_chart"></div>
                </div>
            </div>
        </div>
        <!-- END: likes box -->
        <!-- Comments box start -->
        <div class="col-xl-3 col-md-6">
            <div class="card widget-followers">
                <ul class="list-inline d-flex align-items-center justify-content-between" style="margin-bottom: 0px;">
                    <li style="text-align: left; margin: 15px 12px 0px 20px">
                        <h4 class="card-title font_size_1point5_rem">Comments <span><i class="bx bx-info-circle" data-toggle="tooltip" title="" data-trigger="hover" data-original-title="Total Comments earned across various social channels on posts around the topic"></i></span></h4>
                    </li>
                    <li style="text-align: end; margin: 20px; cursor: pointer;"><a data-action="commentsscreen"> <i class="bx bx-fullscreen"></i>
                        </a></li>
                </ul>
                <div class="card-header d-flex align-items-center justify-content-between" style="padding: 0px 1.7rem">
                    <div class="d-flex align-items-center widget-followers-heading-right">
                        <h3 class="mr-2 font-weight-normal mb-0" id="comments_count"></h3>
                        <div class="d-flex flex-column align-items-center">
                            <i class='bx font-medium-1' id="commentsstatus"></i> <small class="text-muted" id="comments_per"></small>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="comments_chart"></div>
                </div>
            </div>
        </div>
        <!-- END: Comments box -->
    </div>
    <div class="row">
        <!-- Activity Card Starts-->
        <div class="col-xl-4 col-md-4 col-12 activity-card">
            <div class="card hide-native-scrollbar" style="overflow-y:auto; height: 508px;">
                <div class="card-header">
                    <h4 class="card-title font_size_1point5_rem">Main Source Channels <span><i class="bx bx-info-circle" data-toggle="tooltip" title="" data-trigger="hover" data-original-title="Distribution of mentions by different source channels"></i></span></h4>
                </div>
                <div class="card-body pt-1" id="main_social_channel">
                    {{--TwitterDM--}}
                    <?php
                    $twitter_dm_access = Helper::get_module_access('Twitter');
                    if ($twitter_dm_access) {
                        $twitter_dm_handle_name = Helper::get_source_handle_name('Twitter');
                        ?>
                        <div class="d-flex activity-content" id="twitter_dm">
                            <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                                <div class="avatar-content">
                                    <i class="fa-brands fa-x-twitter" style="color: #000000 !important" onclick="javascript:load_posts('twiiter_dm', '', '{{ csrf_token() }}', 'open', 'Twitter', '<?php echo $twitter_dm_handle_name; ?>');"></i>
                                </div>
                            </div>
                            <div class="activity-progress flex-grow-1">
                                <small class="text-muted d-inline-block mb-50" id="twitterdm" style="font-size:90%; color: #000000 !important;">@<?php echo $twitter_dm_handle_name; ?></small> <small class="float-right"></small>
                                <div class="progress progress-bar-warning progress-sm">
                                    <div class="progress-bar" role="progressbar" aria-valuenow="80" style="width: 80%; background: #000000 !important; box-shadow: none;"></div>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                    <div class="d-flex activity-content" id="printmedia_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bx-news text-primary" style="color: #999999 !important" onclick="javascript:load_printmedia_posts('{{ csrf_token() }}', 'open');"></i>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="printmedia" style="font-size:90%; color: #000000 !important;">Printmedia posts</small><small class="float-right"></small>
                            <div class="progress progress-bar-primary progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="40" style="width: 40%; background: #999999; box-shadow: none;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex activity-content" id="web_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bx-globe text-primary" style="color: #b6aa6e !important" onclick="javascript:load_posts('maintopic', 'Web', '{{ csrf_token() }}', 'open', '', '');"></i>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="web" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-primary progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="5" id="web_progress_bar" style="width: 25%; background: #b6aa6e; box-shadow: none;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex activity-content" id="pinterest_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bxl-pinterest text-primary" style="color: #E60023 !important" onclick="javascript:load_posts('maintopic', 'Pinterest', '{{ csrf_token() }}', 'open', '', '');"></i>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="pinterest" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-primary progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="5" id="pinterest_progress_bar" style="width: 18%; background: #E60023; box-shadow: none;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex activity-content" id="facebook_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bxl-facebook-square text-primary" style="color: #3B5998 !important" onclick="javascript:load_posts('maintopic', 'Facebook', '{{ csrf_token() }}', 'open', '', '');"></i>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="facebook" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-primary progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="5" id="facebook_progress_bar" style="width: 5%; background: #3B5998; box-shadow: none;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex activity-content" id="insta_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bxl-instagram-alt text-success" style="color: #E4405F !important" onclick="javascript:load_posts('maintopic', 'Instagram', '{{ csrf_token() }}', 'open', '', '');"></i>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="insta" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-success progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="15" id="instagram_progress_bar" style="width: 15%; background: #E4405F !important; box-shadow: none;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex activity-content" id="twitter_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="fa-brands fa-x-twitter text-warning" style="color: #000000 !important" onclick="javascript:load_posts('maintopic', 'Twitter', '{{ csrf_token() }}', 'open', '', '');"></i>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="twitter" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-warning progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="80" id="twitter_progress_bar" style="width: 80%; background: #000000 !important; box-shadow: none;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex activity-content" id="video_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bxl-youtube text-danger" onclick="javascript:load_posts('maintopic', 'Youtube', '{{ csrf_token() }}', 'open', '', '');"></i>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="video" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-danger progress-sm">
                                <div class="progress-bar" role="progressbar" id="youtube_progress_bar" aria-valuenow="40" style="width: 40%; box-shadow: none"></div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex activity-content" id="googlemaps_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bxs-map text-primary" style="color: #8a2be2 !important" onclick="javascript:load_posts('maintopic', 'GoogleMaps', '{{ csrf_token() }}', 'open', '', '');"></i>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="googlemaps" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-primary progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="30" id="googlemaps_progress_bar" style="width: 30%; background: #8a2be2 !important; box-shadow: none"></div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex activity-content" id="tripadvisor_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bxl-trip-advisor text-primary" style="color: #00AF87 !important" onclick="javascript:load_posts('maintopic', 'Tripadvisor', '{{ csrf_token() }}', 'open', '', '');"></i>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="tripadvisor" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-primary progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="30" style="width: 35%; background: #00AF87 !important; box-shadow: none"></div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex activity-content" id="linkedin_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bxl-linkedin-square text-primary" style="color: #0072b1 !important" onclick="javascript:load_posts('maintopic', 'Linkedin', '{{ csrf_token() }}', 'open', '', '');"></i>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="linkedin" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-primary progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="30" id="linkedin_progress_bar" style="width: 30%; background: #0072b1 !important; box-shadow: none"></div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex activity-content" id="reddit_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bxl-reddit text-warning" style="color: #FF4301 !important" onclick="javascript:load_posts('maintopic', 'Reddit', '{{ csrf_token() }}', 'open', '', '');"></i>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="reddit" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-warning progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="15" id="reddit_progress_bar" style="width: 15%; background: #FF4301 !important; box-shadow: none"></div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex activity-content" id="blog_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bxl-blogger text-success" style="color: #F57D00 !important" onclick="javascript:load_posts('maintopic', 'Blogs', '{{ csrf_token() }}', 'open', '', '');"></i>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="blog" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-success progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="15" id="blogs_progress_bar" style="width: 15%; background: #F57D00 !important; box-shadow: none"></div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex activity-content" id="tumblr_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bxl-tumblr text-danger" style="color: #34526F !important" onclick="javascript:load_posts('maintopic', 'Tumblr', '{{ csrf_token() }}', 'open', '', '');"></i>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="tumblr" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-danger progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="5" id="tumblr_progress_bar" style="width: 5%; background: #34526F !important; box-shadow: none"></div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex activity-content" id="news_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bx-news text-primary" style="color: #77BD9D !important" onclick="javascript:load_posts('maintopic', 'News', '{{ csrf_token() }}', 'open', '', '');"></i>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="news" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-primary progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="30" id="news_progress_bar" style="width: 30%; background: #77BD9D !important; box-shadow: none"></div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex activity-content" id="tiktok_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bx-text text-primary" style="color: #000000 !important" onclick="javascript:load_posts('maintopic', 'Tiktok', '{{ csrf_token() }}', 'open', '', '');"></i>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="tiktok" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-primary progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="10" id="tiktok_progress_bar" style="width: 10%; background: #000000 !important; box-shadow: none"></div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex activity-content" id="google_play_reviews_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bxl-play-store text-primary" style="color: #F4B400 !important" onclick="javascript:load_reviews_data('GooglePlayStore', '{{ csrf_token() }}', 'open', '', '');"></i>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="google_play_reviews" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-primary progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="45" style="width: 45%; background: #F4B400 !important; box-shadow: none"></div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex activity-content" id="google_my_business_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bxs-store-alt text-primary" style="color: #4285f4 !important" onclick="javascript:load_reviews_data('GoogleMyBusiness', '{{ csrf_token() }}', 'open', '', '');"></i>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="google_my_business_reviews" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-primary progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="30" style="width: 30%; background: #4285f4 !important; box-shadow: none"></div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex activity-content" id="apple_app_store_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bxl-apple text-primary" style="color: #666666 !important" onclick="javascript:load_reviews_data('AppleAppStore', '{{ csrf_token() }}', 'open', '', '');"></i>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="apple_app_store_reviews" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-primary progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="35" style="width: 35%; background: #666666 !important; box-shadow: none"></div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex activity-content" id="huawei_gallery_reviews_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bxs-category-alt text-primary" style="color: #cf0a2c !important" onclick="javascript:load_reviews_data('HuaweiAppGallery', '{{ csrf_token() }}', 'open', '', '');"></i>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="huawei_gallery_reviews" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-primary progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="20" style="width: 20%; background: #cf0a2c !important; box-shadow: none"></div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex activity-content" id="glassdoor_reviews_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bxs-book-content text-primary" style="color: #1C6A26 !important" onclick="javascript:load_reviews_data('Glassdoor', '{{ csrf_token() }}', 'open', '', '');"></i>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="glassdoor_reviews" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-primary progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="20" style="width: 20%; background: #1C6A26 !important; box-shadow: none"></div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex activity-content" id="zomato_reviews_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bx-restaurant text-primary" style="color: #cb202d !important" onclick="javascript:load_reviews_data('Zomato', '{{ csrf_token() }}', 'open', '', '');"></i>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="zomato_reviews" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-primary progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="20" style="width: 20%; background: #cb202d !important; box-shadow: none"></div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex activity-content" id="talabat_reviews_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bxl-tumblr text-primary" style="color: #ff6100 !important" onclick="javascript:load_reviews_data('Talabat', '{{ csrf_token() }}', 'open', '', '');"></i>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="talabat_reviews" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-primary progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="20" style="width: 20%; background: #ff6100 !important; box-shadow: none"></div>
                            </div>
                        </div>
                    </div>
                    <div style="padding: 20px;"></div>
                </div>
            </div>
        </div>
        {{--Sources sentiment--}}
        <div class="col-sm-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title font_size_1point5_rem">Channel sentiments <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Sentiment associated with mentions by sources" data-trigger="hover" data-placement="top"></i></span></h4>
                </div>
                <div class="card-body">
                    <div id="maintopic_channel_sentiments" class="d-flex justify-content-center"></div>
                </div>
            </div>
        </div>
        {{--End Sources sentiment--}}		
    </div>
    
    <div class="row">
        {{-- Comments sentiment chart--}}
        <div class="col-sm-3">
            <div class="row">
                <div class="col-md-12 col-sm-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title font_size_1point5_rem">Comments sentiments <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Sentiments against all posts comments" data-trigger="hover" data-placement="top"></i></span></h4>
                        </div>
                        <div class="card-body">
                            <div id="comments_sentiment_graph" class="d-flex justify-content-center"></div>
 <h6 class="mr-2 font-weight-normal mb-0" id="comments_sentiment_count"></h6>		       
	</div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Emotions analaysis--}}
        <div class="col-sm-6">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title font_size_1point5_rem">Top associated emotions <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Overall Emotions of posts around the topic" data-trigger="hover" data-placement="top"></i></span></h4>
                        </div>
                        <div class="card-body">
                            <div id="maintopic_emotions_chart" class="d-flex justify-content-center"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Sentiment analaysis--}}
        <div class="col-sm-3">
            <div class="row">
                <div class="col-md-12 col-sm-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title font_size_1point5_rem">Sentiment Summary <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Overall sentiment around the topic" data-trigger="hover" data-placement="top"></i></span></h4>
                        </div>
                        <div class="card-body">
                            <div id="donut-chart" class="d-flex justify-content-center"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
    
    
    {{--Languages and AV--}}
    <div class="row">
        <div class="col-sm-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title font_size_1point5_rem">Languages <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Languages of mentions around the topic" data-trigger="hover" data-placement="top"></i></span></h4>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-around align-items-center flex-wrap">
                        <div class="user-analytics mr-2">
                            <!--<i class="bx bx-radio-circle mr-25 align-middle"></i>-->
                            <span class="align-middle text-muted my_text1">English</span>
                            <div class="d-flex">
                                <div id="radial-english-chart"></div>
                                <h3 class="mt-1 ml-50" id="lang_eng"></h3>
                            </div>
                        </div>
                        <div class="sessions-analytics mr-2">
                            <!--<i class="bx bx-radio-circle align-middle mr-25"></i>-->
                            <span class="align-middle text-muted my_text1">Arabic</span>
                            <div class="d-flex">
                                <div id="radial-arabic-chart"></div>
                                <h3 class="mt-1 ml-50" id="lang_ar"></h3>
                            </div>
                        </div>
                        <div class="bounce-rate-analytics">
                            <!--<i class="bx bx-radio-circle align-middle mr-25"></i>-->
                            <span class="align-middle text-muted my_text1">Others</span>
                            <div class="d-flex">
                                <div id="radial-other-chart"></div>
                                <h3 class="mt-1 ml-50" id="lang_other"></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title font_size_1point5_rem">AVE (USD) <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Total advertisement value equivalent of media publications" data-trigger="hover" data-placement="top"></i></span></h4>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-around align-items-center flex-wrap" style="padding-bottom: 0px;">
                        <div class="user-analytics mr-2" style="float:left;" id="digital_metions_container">
                            <i class="bx bxs-calculator mr-25 align-middle"></i>
                            <span class="align-middle text-muted my_text1">Digital</span>
                            <div class="d-flex">
                                <h3 class="mt-1 ml-50" style="margin-left:0px !important;" id="digital_mentions"></h3>
                            </div>
                        </div>
                        <div class="sessions-analytics mr-2" style="float:left;" id="conventional_mentions_container">
                            <i class="bx bx-news align-middle mr-25"></i>
                            <span class="align-middle text-muted my_text1">Conventional</span>
                            <div class="d-flex">
                                <h3 class="mt-1 ml-50" style="margin-left:0px !important;" id="conventional_mentions"></h3>
                            </div>
                        </div>
                    </div>
                    <!--<table width="100%" cellspacing="0" style="border: 0px;">
                        <tr>
                            <td width="50%" style="text-align:center; background:#0087b5; color:#ffffff; padding:5px; font-size: 18px; height: 30px;">Digital</td>
                            <td style="text-align:center; background:#00b6f0; color:#ffffff; padding:5px; font-size: 18px; height: 30px;">Conventional</td>
                        </tr>
                        <tr>
                            <td style="text-align:center; background:#0087b5; color:#ffffff; padding:10px; font-size:18px; height: 35px; font-weight: bold;" id="digital_mentions"></td>
                            <td style="text-align:center; background:#00b6f0; color:#ffffff; padding:10px; font-size:18px; height: 35px; font-weight: bold;" id="conventional_mentions"></td>
                        </tr>
                    </table>-->
                </div>
            </div>
        </div>
    </div>
        
    <div class="row">
        <div class="col-sm-7 dashboard-marketing-campaign">
            <div class="card marketing-campaigns">
                <div class="card-header d-flex justify-content-between align-items-center pb-1">
                    <h4 class="card-title font_size_1point5_rem">Top Influencers <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Top influentials account mentions around the topic" data-trigger="hover" data-placement="top"></i></span></h4>
                    <i class="bx bx-dots-vertical-rounded font-medium-3 cursor-pointer"></i>
                </div>

                <!-- Nav tabs -->
                <!--<ul class="nav nav-tabs nav-fill card-body pb-0" id="myTab" role="tablist">
                    <li class="nav-item"><a class="nav-link active" id="home-tab-fill" data-toggle="tab" href="#home-fill" role="tab" aria-controls="home-fill" aria-selected="true"> Nano </a></li>
                    <li class="nav-item"><a class="nav-link" id="profile-tab-fill" data-toggle="tab" href="#profile-fill" role="tab" aria-controls="profile-fill" aria-selected="false"> Micro </a></li>
                    <li class="nav-item"><a class="nav-link" id="messages-tab-fill" data-toggle="tab" href="#messages-fill" role="tab" aria-controls="messages-fill" aria-selected="false"> Midtier </a></li>
                    <li class="nav-item"><a class="nav-link" id="settings-tab-fill" data-toggle="tab" href="#settings-fill" role="tab" aria-controls="settings-fill" aria-selected="false"> Macro </a></li>
                    <li class="nav-item"><a class="nav-link" id="mega-tab-fill" data-toggle="tab" href="#mega-fill" role="tab" aria-controls="mega-fill" aria-selected="false"> Mega </a></li>
                    <li class="nav-item"><a class="nav-link" id="cele-tab-fill" data-toggle="tab" href="#cele-fill" role="tab" aria-controls="cele-fill" aria-selected="false"> Celebrity </a></li>
                </ul> -->
                <ul class="nav nav-tabs nav-fill card-body pb-0" id="myTab" role="tablist">
                    <li class="nav-item"><a class="nav-link active" id="home-tab-fill" data-toggle="tab" href="#home-fill" role="tab" aria-controls="home-fill" aria-selected="true" style="box-shadow: 0 2px 4px 0 rgb(94.9 80 50.6 / 50%); background-color: #f2cc81 !important; color:white;"> Nano </a></li>
                    <li class="nav-item"><a class="nav-link" id="profile-tab-fill" data-toggle="tab" href="#profile-fill" role="tab" aria-controls="profile-fill" aria-selected="false" style="box-shadow: 0 2px 4px 0 rgb(92.9 65.1 55.7 / 50%); background-color: #eda68e !important; color:white;"> Micro </a></li>
                    <li class="nav-item"><a class="nav-link" id="messages-tab-fill" data-toggle="tab" href="#messages-fill" role="tab" aria-controls="messages-fill" aria-selected="false" style="box-shadow: 0 2px 4px 0 rgb(0.0 59.2 63.9 / 50%); background-color: #0097a3 !important; color:white;"> Midtier </a></li>
                    <li class="nav-item"><a class="nav-link" id="settings-tab-fill" data-toggle="tab" href="#settings-fill" role="tab" aria-controls="settings-fill" aria-selected="false" style="box-shadow: 0 2px 4px 0 rgb(58.5 25.5 30.2 / 50%); background-color: #96414d !important; color:white;"> Macro </a></li>
                    <li class="nav-item"><a class="nav-link" id="mega-tab-fill" data-toggle="tab" href="#mega-fill" role="tab" aria-controls="mega-fill" aria-selected="false" style="box-shadow: 0 2px 4px 0 rgb(68.6 43.5 100.0 / 50%); background-color: #af6fff !important; color:white;"> Mega </a></li>
                    <li class="nav-item"><a class="nav-link" id="cele-tab-fill" data-toggle="tab" href="#cele-fill" role="tab" aria-controls="cele-fill" aria-selected="false" style="box-shadow: 0 2px 4px 0 rgb(9.4 59.6 52.5 / 50%); background-color: #189886 !important; color:white;"> Celebrity </a></li>
                </ul> 
                <!-- Tab panes -->
                <div class="tab-content pt-1">
                    <div class="tab-pane active" id="home-fill" role="tabpanel" aria-labelledby="home-tab-fill">
                        <div class="table-responsive" style="height: 332px;">
                            <!-- table start -->
                            <table id="table-marketing-campaigns" class="table table-borderless table-marketing-campaigns mb-0" style="font-size:1.1rem !important;">
                                <thead>
                                    <tr>
                                        <th style="padding-left: 80px">Name</th>
                                        <th class="centeraligh">Source</th>
                                        <th class="centeraligh">Country</th>
                                        <th class="centeraligh">Followers</th>
                                        <th class="centeraligh">Posts</th>
                                    </tr>
                                </thead>
                                <tbody id="nano">
                                </tbody>
                            </table>
                            <!-- table ends -->
                        </div>
                    </div>
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
                    </style>
                    <div class="tab-pane" id="profile-fill" role="tabpanel" aria-labelledby="profile-tab-fill">
                        <div class="table-responsive" style="height: 332px;">
                            <!-- table start -->
                            <table id="table-marketing-campaigns" class="table table-borderless table-marketing-campaigns mb-0" style="font-size:1.1rem !important;">
                                <thead>
                                    <tr>
                                        <th style="padding-left: 80px">Name</th>
                                        <th class="centeraligh">Source</th>
                                        <th class="centeraligh">Country</th>
                                        <th class="centeraligh">Followers</th>
                                        <th class="centeraligh">Posts</th>
                                    </tr>
                                </thead>
                                <tbody id="micro">
                                </tbody>
                            </table>
                            <!-- table ends -->
                        </div>
                    </div>
                    <div class="tab-pane" id="messages-fill" role="tabpanel" aria-labelledby="messages-tab-fill">
                        <div class="table-responsive" style="height: 332px;">
                            <!-- table start -->
                            <table id="table-marketing-campaigns" class="table table-borderless table-marketing-campaigns mb-0" style="font-size:1.1rem !important;">
                                <thead>
                                    <tr>
                                        <th style="padding-left: 80px">Name</th>
                                        <th class="centeraligh">Source</th>
                                        <th class="centeraligh">Country</th>
                                        <th class="centeraligh">Followers</th>
                                        <th class="centeraligh">Posts</th>
                                    </tr>
                                </thead>
                                <tbody id="midtier">
                                </tbody>
                            </table>
                            <!-- table ends -->
                        </div>
                    </div>
                    <div class="tab-pane" id="settings-fill" role="tabpanel" aria-labelledby="settings-tab-fill">
                        <div class="table-responsive" style="height: 332px;">
                            <!-- table start -->
                            <table id="table-marketing-campaigns" class="table table-borderless table-marketing-campaigns mb-0" style="font-size:1.1rem !important;">
                                <thead>
                                    <tr>
                                        <th style="padding-left: 80px">Name</th>
                                        <th class="centeraligh">Source</th>
                                        <th class="centeraligh">Country</th>
                                        <th class="centeraligh">Followers</th>
                                        <th class="centeraligh">Posts</th>
                                    </tr>
                                </thead>
                                <tbody id="macro">
                                </tbody>
                            </table>
                            <!-- table ends -->
                        </div>
                    </div>
                    <div class="tab-pane" id="mega-fill" role="tabpanel" aria-labelledby="mega-tab-fill">
                        <div class="table-responsive" style="height: 332px;">
                            <!-- table start -->
                            <table id="table-marketing-campaigns" class="table table-borderless table-marketing-campaigns mb-0" style="font-size:1.1rem !important;">
                                <thead>
                                    <tr>
                                        <th style="padding-left: 80px">Name</th>
                                        <th class="centeraligh">Source</th>
                                        <th class="centeraligh">Country</th>
                                        <th class="centeraligh">Followers</th>
                                        <th class="centeraligh">Posts</th>
                                    </tr>
                                </thead>
                                <tbody id="mega">
                                </tbody>
                            </table>
                            <!-- table ends -->
                        </div>
                    </div>
                    <div class="tab-pane" id="cele-fill" role="tabpanel" aria-labelledby="cele-tab-fill">
                        <div class="table-responsive" style="height: 332px;">
                            <!-- table start -->
                            <table id="table-marketing-campaigns" class="table table-borderless table-marketing-campaigns mb-0" style="font-size:1.1rem !important;">
                                <thead>
                                    <tr>
                                        <th style="padding-left: 80px">Name</th>
                                        <th class="centeraligh">Source</th>
                                        <th class="centeraligh">Country</th>
                                        <th class="centeraligh">Followers</th>
                                        <th class="centeraligh">Posts</th>
                                    </tr>
                                </thead>
                                <tbody id="celebrity">
                                </tbody>
                            </table>
                            <!-- table ends -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{--Inflencer info--}}
        <div class="col-sm-1" style="height: 350px; color:white;">
            <div style="background:#189886; padding: 5px 5px 5px 5px; text-align: center;">
                <span><h6 style="color:white;">Celebrity</h6></span>
                <span><small class="muted-text">Above 5M<br>Followers</small></span>
            </div>
            <div style="background:#af6fff; padding: 5px 5px 5px 5px; text-align: center;">
                <span><h6 style="color:white;">Mega</h6></span>
                <span><small class="muted-text">1M to 5M<br>Followers</small></span>
            </div>
            <div style="background:#96414d; padding: 5px 5px 5px 5px; text-align: center;">
                <span><h6 style="color:white;">Macro</h6></span>
                <span><small class="muted-text">500K to 1M<br>Followers</small></span>
            </div>
            <div style="background:#0097a3; padding: 5px 5px 5px 5px; text-align: center;">
                <span><h6 style="color:white;">Midtier</h6></span>
                <span><small class="muted-text">50K to 500K<br>Followers</small></span>
            </div>
            <div style="background:#eda68e; padding: 5px 5px 5px 5px; text-align: center;">
                <span><h6 style="color:white;">Micro</h6></span>
                <span><small class="muted-text">10K to 50K<br>Followers</small></span>
            </div>
            <div style="background:#f2cc81; padding: 9px 5px 9px 5px; text-align: center;">
                <span><h6 style="color:white;">Nano</h6></span>
                <span><small class="muted-text">1K to 10K<br>Followers</small></span>
            </div>
        </div>
        {{--Inflencer chart--}}
        <div class="col-sm-4">
            <div class="row">
                <div class="col-md-12 col-sm-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title font_size_1point5_rem">Influencers category <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Type of influencers based on number of followers mentions around the topic" data-trigger="hover" data-placement="top"></i></span></h4>
                        </div>
                        <div class="card-body">
                            <div id="influencers_chart" class="d-flex justify-content-center"></div>
                            <!--<div style="background:url('{{asset('images/pages/influencer-cat.jpg')}}'); height: 60px; background-size: contain;"></div>-->
                        </div>
                    </div>
                </div>

            </div>
        </div>
        {{--END: Inflencer chart--}}
    </div>

    <div class="row">
        {{--Users chart--}}
        <div class="col-sm-4">
            <div class="row">
                <div class="col-md-12 col-sm-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title font_size_1point5_rem">Influencers coverage <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Influencers versus normal accounts mentions around the topic" data-trigger="hover" data-placement="top"></i></span></h4>
                        </div>
                        <div class="card-body">
                            <div id="users_chart" class="d-flex justify-content-center"></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        {{--END: Users chart--}}
        {{--Active users list--}}
        <div class="col-sm-8">
            <div class="row">
                <div class="col-md-12 col-sm-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title font_size_1point5_rem">Active audience <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Accounts with most mentions around the topic" data-trigger="hover" data-placement="top"></i></span></h4>
                        </div>
                        <div class="card-body">
                            <div id="active_users_list" class="hide-native-scrollbar" style="overflow-y:auto; height: 320px;"></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        {{--END: Active users list--}}
    </div>
    {{--Map view--}}
    <!--<div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title font_size_1point5_rem">Map view (Press Ctrl key to move or zoom)</h4>

                </div>
                <div class="card-body">
                    <div id="worldmap" style="height: 500px;"></div>
                </div>
            </div>
        </div>
    </div>-->
    {{--END: Mapview--}}
    
    {{--Heatmap--}}
    <!--<div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title font_size_1point5_rem">Keyword's mentions count (last 30 days)</h4>

                </div>
                <div class="card-body">
                    <div id="maintopic_heatmap" style="letter-spacing:normal;"></div>
                </div>
            </div>
        </div>
    </div>-->
    {{--END: Heatmap--}}
    
    {{--Keywords chart and countries chart--}}
    <div class="row">
        <div class="col-sm-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title font_size_1point5_rem">Dashboard keywords <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Topic keywords mentions around the topic" data-trigger="hover" data-placement="top"></i></span></h4>

                </div>
                <div class="card-body">
                    <div id="maintopic_keywords_chart" style="letter-spacing:normal;"></div>
                </div>
            </div>
        </div>
        
        <div class="col-sm-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title font_size_1point5_rem">Audience distribution by country <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Distribution of audience talking around the themes by country" data-trigger="hover" data-placement="top"></i></span></h4>

                </div>
                <div class="card-body">
                    <div id="maintopic_country_chart" style="letter-spacing:normal;"></div>
                </div>
            </div>
        </div>
    </div>
    {{--END: Keywords chart and countries chart--}}
    
    {{--Popular posts--}}
    <div class="col-sm-12" style="padding:0px;">
        <div class="card">
            <div class="card-header">
                <div style="width: 70%; float: left;"><h4 class="card-title font_size_1point5_rem">Popular posts <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Most liked posts" data-trigger="hover" data-placement="top"></i></span></h4></div>
                    <div style="width: 20%; float: right;"><div class="spinner-border" role="status" id="popular_posts_loading" style="margin-top:20px; float: right;"><span class="sr-only">Loading...</span></div></div>
                    <div style="clear:both;"></div>
            </div>
            <div class="card-body" style="padding-left: 5px;">
                <div class="swiper-centered-slides swiper-container p-1">
                    <div class="swiper-wrapper" id="popular_posts_list">
                        <!--<div class="swiper-slide rounded swiper-shadow"> <i class="bx bxl-google font-large-1"></i>
                            <div class="swiper-text pt-md-1 pt-sm-50">Getting Started</div>
                        </div>
                        <div class="swiper-slide rounded swiper-shadow"> <i class="bx bxl-facebook font-large-1"></i>
                            <div class="swiper-text pt-md-1 pt-sm-50">Pricing & Plans</div>
                        </div>
                        <div class="swiper-slide rounded swiper-shadow"> <i class="bx bxl-twitter font-large-1"></i>
                            <div class="swiper-text pt-md-1 pt-sm-50">Sales Question</div>
                        </div>
                        <div class="swiper-slide rounded swiper-shadow"> <i class="bx bxl-instagram font-large-1"></i>
                            <div class="swiper-text pt-md-1 pt-sm-50">Usage Guides</div>
                        </div>
                        <div class="swiper-slide rounded swiper-shadow"> <i class="bx bxl-google font-large-1"></i>
                            <div class="swiper-text pt-md-1 pt-sm-50">General Guide</div>
                        </div>-->
                    </div>
                    <!-- Add Arrows -->
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>
            </div>
        </div>

    </div>
    {{--END: Popular posts--}}
    
    {{-- word cloud --}}
    <script src="https://cdn.amcharts.com/lib/4/core.js"></script>
    <script src="https://cdn.amcharts.com/lib/4/charts.js"></script>
    <script src="https://cdn.amcharts.com/lib/4/plugins/wordCloud.js"></script>
    <script src="https://cdn.amcharts.com/lib/4/themes/animated.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/webfont/1.6.26/webfont.js"></script>
    <div class="col-sm-12 growth-card" style="padding:0px;">
        <div class="card">
            <div class="card-body">
                <div style="padding-bottom: 30px;">
                    <div style="width: 70%; float: left;"><h4 class="card-title font_size_1point5_rem">Top used words <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Identify prominent words mentioned around the topic" data-trigger="hover" data-placement="top"></i></span></h4></div>
                    <div style="width: 20%; float: right;"><div class="spinner-border" role="status" id="wordcloud_loading" style="margin-top:20px; float: right;"><span class="sr-only">Loading...</span></div></div>
                    <div style="clear:both;"></div>
                </div>
                <div style="width:100%;">
                    <ul class="nav nav-tabs pb-0" id="myTab" role="tablist">
                        <li class="nav-item"><a class="nav-link active" id="list_view" data-toggle="tab" href="#list_view_container" role="tab" aria-controls="list_view" aria-selected="true"> List view </a></li>
                        <li class="nav-item"><a class="nav-link" id="cloud_view" data-toggle="tab" href="#cloud_view_container" role="tab" aria-controls="cloud_view" aria-selected="false"> Cloud view </a></li>
                    </ul>
                    <!--Tab pans -->
                    <div class="tab-content pt-1">
                        <div class="tab-pane active" id="list_view_container" role="tabpanel" aria-labelledby="list_view_container">
                            Loading ....
                        </div>

                        <div class="tab-pane" id="cloud_view_container" role="tabpanel" aria-labelledby="cloud_view_container">
                            <div id="wordcloud" style="width: 100%; height: 500px;letter-spacing:normal;"></div>
                        </div>
                    </div>
                </div>
                <!--<div id="wordcloud" style="width: 100%; height: 500px;letter-spacing:normal;"></div>-->
            </div>
        </div>

    </div>
</section>
<!-- Sortable lists section end -->
<!-- Dashboard Ecommerce ends -->
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
<script src="{{asset('js/scripts/testing.js')}}"></script>
<script src="{{asset('js/scripts/pickers/dateTime/pick-a-datetime.js')}}"></script>
<script src="{{asset('js/scripts/forms/select/form-select2.js')}}"></script>
<!--<script src="{{asset('js/scripts/pages/dashboard-analytics.js')}}"></script>-->
{{--MapScripts--}}
<script src="{{asset('js/scripts/leaflet/leaflet.js')}}"></script>
<script src="{{asset('js/scripts/leaflet/leaflet-gesture-handling.js')}}"></script>
<script type="text/javascript">
                                    $(function(){
                                    load_data('dashboard', '{{ csrf_token() }}');
                                    });
                                    var server_url = 'https://dashboard.datalyticx.ai/';
                                    //Script for Mapview
                                    /*var map = L.map('worldmap', {
                                    center: [30, 30],
                                            gestureHandling: true,
                                            zoom: 5
                                    });
                                    var myIcon = L.icon({
                                    iconUrl: server_url + 'images/leaflet/marker-icon.png',
                                            iconSize: [15, 25],
                                            iconAnchor: [22, 94],
                                            popupAnchor: [ - 13, - 92],
                                            shadowUrl: server_url + 'images/leaflet/marker-shadow.png',
                                            shadowSize: [25, 25],
                                            shadowAnchor: [22, 94]
                                    });
                                    var myIconGreen = L.icon({
                                    iconUrl: server_url + 'images/leaflet/marker-icon-green.png',
                                            iconSize: [15, 25],
                                            iconAnchor: [22, 94],
                                            popupAnchor: [ - 13, - 92],
                                            shadowUrl: server_url + 'images/leaflet/marker-shadow.png',
                                            shadowSize: [25, 25],
                                            shadowAnchor: [22, 94]
                                    });
                                    var myIconRed = L.icon({
                                    iconUrl: server_url + 'images/leaflet/marker-icon-red.png',
                                            iconSize: [15, 25],
                                            iconAnchor: [22, 94],
                                            popupAnchor: [ - 13, - 92],
                                            shadowUrl: server_url + 'images/leaflet/marker-shadow.png',
                                            shadowSize: [25, 25],
                                            shadowAnchor: [22, 94]
                                    });
                                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                                    }).addTo(map);
                                    //call map data
                                    get_worldmap_data('maintopic', '{{ csrf_token() }}');*/
</script>

@endsection

