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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
@endsection @section('content')
<!--Search Form--->
@include('pages.topic-filter-form')
<!-- Dashboard Ecommerce Starts -->
<input type="hidden" name="st_type" id="st_type" value="<?php echo $st_type; ?>">
<?php
    $st_type_heading = '';
    if($st_type == 'campaign_monitoring')
        $st_type_heading = '<span style="color: blue; font-size: 18px; padding-left: 10px;">Campaign monitoring</span>';
    else if($st_type == 'media_monitoring')
        $st_type_heading = '<span style="color: red; font-size: 18px; padding-left: 10px;">Media monitoring</span>';
    else if($st_type == 'cx_monitoring')
        $st_type_heading = '<span style="color: green; font-size: 18px; padding-left: 10px;">Customer experience monitoring</span>';
?>
<script>document.getElementById("st_type_heading").innerHTML = '<?php echo $st_type_heading; ?>';</script>
<section class="list-group" id="card-drag-area">
    <?php
    if($st_type == 'campaign_monitoring' || $st_type == 'media_monitoring' || $st_type == 'cx_monitoring')
    {
    ?>
    {{--mentions graph section--}}
    <div class="row" style="">
		<div class="col-12">
			<div class="card widget-order-activity">
				<div class="card-header d-md-flex justify-content-between align-items-center">
					<h4 class="card-title font_size_1point5_rem">Mentions trends <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Track trend of mentions around the dashboard" data-trigger="hover" data-placement="top"></i></span></h4>
					<div class="heading-elements mt-md-0 mt-50 d-flex align-items-center">
						<fieldset class="d-inline-block form-group position-relative has-icon-left mb-0 mr-1">
							<div class="spinner-border" id="spinner" role="status" style="display: block">
								<span class="sr-only">Loading...</span>
							</div>
						</fieldset>						
					</div>
				</div>
				<div class="card-body">
					<div id="subtopic_mentions_chart"></div>
				</div>
			</div>
		</div>
	</div>
    {{--end mentions graph section--}}
    <?php
    }
    ?>
    <?php
    if($st_type == 'campaign_monitoring') // || $st_type == 'media_monitoring'
    {
    ?>
	<div class="row">
		<!-- Mentions -->
		<div class="col-xl-3 col-md-6">
			<div class="card widget-followers">
				<ul class="list-inline d-flex align-items-center justify-content-between" style="margin-bottom: 0px;">
					<li style="text-align: left; margin: 15px 12px 0px 20px">
						<h4 class="card-title font_size_1point5_rem">Mentions <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Number of posts around the in-depth analysis" data-trigger="hover" data-placement="top"></i></span></h4>
					</li> 
				</ul>
				<div class="card-header d-flex align-items-center justify-content-between" style="padding: 0px 1.7rem">
					<div>&nbsp;</div>
					<div class="d-flex align-items-center widget-followers-heading-right">
						<h4 class="mr-2 font-weight-normal mb-0" id="men_count"></h4>
						<div class="d-flex flex-column align-items-center">
							<i class='bx font-medium-1' id="menstatus"></i> <small class="text-muted" id="men_per"></small>
						</div>
					</div>
				</div>
				<div class="card-body">
					<div id="mentions_chart"></div>
				</div>
			</div>
		</div>
		<!-- Mentions Ends -->
		<!-- Engagement -->
		<div class="col-xl-3 col-md-6">
			<div class="card widget-followers">
				<ul class="list-inline d-flex align-items-center justify-content-between" style="margin-bottom: 0px;">
					<li style="text-align: left; margin: 15px 12px 0px 20px">
						<h4 class="card-title font_size_1point5_rem">Engagements <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Total Engagement earned across various social channels on posts around the in-depth analysis" data-trigger="hover" data-placement="top"></i></span></h4>
					</li> 
				</ul>
				<div class="card-header d-flex align-items-center justify-content-between" style="padding: 0px 1.7rem">
					<div>&nbsp;</div>
					<div class="d-flex align-items-center widget-followers-heading-right">
						<h4 class="mr-2 font-weight-normal mb-0" id="eng_count"></h4>
						<div class="d-flex flex-column align-items-center">
							<i class='bx font-medium-1' id="engstatus"></i> <small class="text-muted" id="eng_per"></small>
						</div>
					</div>
				</div>
				<div class="card-body">
					<div id="eng_chart"></div>
				</div>
			</div>
		</div>
		<!-- Engagement Ends -->
		<!-- Shares -->
		<div class="col-xl-3 col-md-6">
			<div class="card widget-followers">
				<ul class="list-inline d-flex align-items-center justify-content-between" style="margin-bottom: 0px;">
					<li style="text-align: left; margin: 15px 12px 0px 20px">
						<h4 class="card-title font_size_1point5_rem">Shares <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Total Shares earned across various social channels on posts around the in-depth analysis" data-trigger="hover" data-placement="top"></i></span></h4>
					</li> 
				</ul>
				<div class="card-header d-flex align-items-center justify-content-between" style="padding: 0px 1.7rem">
					<div>&nbsp;</div>
					<div class="d-flex align-items-center widget-followers-heading-right">
						<h4 class="mr-2 font-weight-normal mb-0" id="shares_count"></h4>
						<div class="d-flex flex-column align-items-center">
							<i class='bx font-medium-1' id="sharesstatus"></i> <small class="text-muted" id="shares_per"></small>
						</div>
					</div>
				</div>
				<div class="card-body">
					<div id="shares_chart"></div>
				</div>
			</div>
		</div>
		<!-- Shares Ends -->
		<!-- Likes -->
		<div class="col-xl-3 col-md-6">
			<div class="card widget-followers">
				<ul class="list-inline d-flex align-items-center justify-content-between" style="margin-bottom: 0px;">
					<li style="text-align: left; margin: 15px 12px 0px 20px">
						<h4 class="card-title font_size_1point5_rem">Likes <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Total Likes earned across various social channels on posts around the in-depth analysis" data-trigger="hover" data-placement="top"></i></span></h4>
					</li> 
				</ul>
				<div class="card-header d-flex align-items-center justify-content-between" style="padding: 0px 1.7rem">
					<div>&nbsp;</div>
					<div class="d-flex align-items-center widget-followers-heading-right">
						<h4 class="mr-2 font-weight-normal mb-0" id="likes_count"></h4>
						<div class="d-flex flex-column align-items-center">
							<i class='bx font-medium-1' id="likesstatus"></i> <small class="text-muted" id="likes_per"></small>
						</div>
					</div>
				</div>
				<div class="card-body">
					<div id="likes_chart"></div>
				</div>
			</div>
		</div>
		<!-- Likes Ends -->
	</div>
    <?php
    }
    ?>
    
    <?php
    if($st_type == 'campaign_monitoring' || $st_type == 'media_monitoring')
    {
    ?>
    <div class="row">
        <div class="col-xl-6 col-md-6 col-12 activity-card">
            <div class="card hide-native-scrollbar" style="overflow-y:auto; height: 413px;">
                <div class="card-header">
                    <h4 class="card-title font_size_1point5_rem">Main Social Channel <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Distribution of mentions by social channels" data-trigger="hover" data-placement="top"></i></span></h4>
                </div>
                <div class="card-body pt-1" id="main_social_channel">
                    <div class="d-flex activity-content" id="web_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bx-globe text-primary" style="color: #b6aa6e !important" onclick="javascript:load_posts('subtopic', 'Web', '{{ csrf_token() }}', 'open', '', '');"></i>
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
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c3 !important">
                            <div class="avatar-content">
                                <i class="bx bxl-facebook-square text-primary" style="color: #3B5998 !important" onclick="javascript:load_posts('subtopic', 'Facebook', '{{ csrf_token() }}', 'open', '', '');"></i>
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
                                <i class="bx bxl-instagram-alt text-success" style="color: #E4405F !important" onclick="javascript:load_posts('subtopic', 'Instagram', '{{ csrf_token() }}', 'open', '', '');"></i>
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
                                <i class="fa-brands fa-x-twitter text-warning" style="color: #000000 !important" onclick="javascript:load_posts('subtopic', 'Twitter', '{{ csrf_token() }}', 'open', '', '');"></i>
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
                                <i class="bx bxl-youtube text-danger" onclick="javascript:load_posts('subtopic', 'Youtube', '{{ csrf_token() }}', 'open', '', '');"></i>
                                <!-- javascript:load_posts('subtopic', 'videos', '{{ csrf_token() }}', 'open'); -->
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="video" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-danger progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="40" id="youtube_progress_bar" style="width: 40%; box-shadow: none"></div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex activity-content" id="reddit_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bxl-reddit text-warning" style="color: #FF4301 !important" onclick="javascript:load_posts('subtopic', 'Reddit', '{{ csrf_token() }}', 'open', '', '');"></i>
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
                                <i class="bx bxl-blogger text-success" style="color: #F57D00 !important" onclick="javascript:load_posts('subtopic', 'Blogs', '{{ csrf_token() }}', 'open', '', '');"></i>
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
                                <i class="bx bxl-tumblr text-danger" style="color: #34526F !important" onclick="javascript:load_posts('subtopic', 'Tumblr', '{{ csrf_token() }}', 'open', '', '');"></i>
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
                                <i class="bx bx-news text-primary" style="color: #77BD9D !important" onclick="javascript:load_posts('subtopic', 'News', '{{ csrf_token() }}', 'open', '', '');"></i>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="news" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-primary progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="30" id="news_progress_bar" style="width: 30%; background: #77BD9D !important; box-shadow: none"></div>
                            </div>
                        </div>
                    </div>
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
                    <div class="d-flex activity-content" id="googlemaps_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bxs-map text-primary" style="color: #8a2be2 !important" onclick="javascript:load_posts('subtopic', 'GoogleMaps', '{{ csrf_token() }}', 'open', '', '');"></i>
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
                                <i class="bx bxl-trip-advisor text-primary" style="color: #00AF87 !important" onclick="javascript:load_posts('subtopic', 'Tripadvisor', '{{ csrf_token() }}', 'open', '', '');"></i>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="tripadvisor" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-primary progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="30" id="tripadvisor_data" style="width: 35%; background: #00AF87 !important; box-shadow: none"></div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex activity-content" id="linkedin_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bxl-linkedin-square text-primary" style="color: #0072b1 !important" onclick="javascript:load_posts('subtopic', 'Linkedin', '{{ csrf_token() }}', 'open', '', '');"></i>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="linkedin" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-primary progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="30" id="linkedin_progress_bar" style="width: 30%; background: #0072b1 !important; box-shadow: none"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="padding: 20px;"></div>
                </div>
            </div>
        </div>
        
        {{-- Sentiment analaysis--}}
		<div class="col-sm-4 profit-report-card">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title font_size_1point5_rem">Sentiment Summary <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Sentiments summary" data-trigger="hover" data-placement="top"></i></span></h4>
                        </div>
                        <div class="card-body">
                            <div id="subtopic_donut_chart" class="d-flex justify-content-center"></div>
                        </div>
                    </div>
                </div>
            </div>
		</div>
        
        {{--Mentions and Estimated reach--}}
		<div class="col-sm-2 col-12" style="">
            <div class="card text-center" style="margin-bottom: 1.3rem;">
                <div style="text-align:right; padding: 5px 5px 0px 0px;"><span><i class='bx bx-info-circle' data-toggle="popover" data-content="Number of posts around the in-depth analysis" data-trigger="hover" data-placement="top"></i></span></div>
                <div class="card-body py-1" style="padding-top: 0px !important;">
                    <div class="badge-circle badge-circle-lg badge-circle-light-secondary mx-auto mb-50" style="margin-top: 0px; width: 60px; height: 60px;">
                        <i class="bx bx-stats font-large-2"></i>
                    </div>
                    <div class="text-muted line-ellipsis my_text1" style="padding: 3px 0px 3px 0px;">Mentions</div>
                    <h2 class="mb-0" id="st_total_mentions"></h2><small class="text-muted" id="st_mentions_uf"></small>
                </div>
            </div>
            <div class="card text-center">
                <div style="text-align:right; padding: 5px 5px 0px 0px;"><span><i class='bx bx-info-circle' data-toggle="popover" data-content="Number of views and impressions around the in-depth analysis" data-trigger="hover" data-placement="top"></i></span></div>
                <div class="card-body py-1" style="padding-top: 0px !important;">
                    <div class="badge-circle badge-circle-lg badge-circle-light-secondary mx-auto mb-50" style="margin-top: 0px; width: 60px; height: 60px;">
                        <i class="bx bx-shuffle font-large-2"></i>
                    </div>
                    <div class="text-muted line-ellipsis my_text1" style="padding: 3px 0px 3px 0px;">Estimated reach</div>
                    <h2 class="mb-0" id="st_est_reach"></h2><small class="text-muted" id="st_est_reach_uf"></small>
                </div>
            </div>
        </div>
        
    </div>
    <?php
    }
    ?>
    
    <?php
    if($st_type == 'cx_monitoring')
    {
    ?>
    <div class="row">
        <div class="col-xl-5 col-md-5 col-12 activity-card">
            <div class="card hide-native-scrollbar" style="overflow-y:auto; height: 413px;">
                <div class="card-header">
                    <h4 class="card-title font_size_1point5_rem">Main Social Channel <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Distribution of mentions by sources" data-trigger="hover" data-placement="top"></i></span></h4>
                </div>
                <div class="card-body pt-1" id="main_social_channel">
                    <div class="d-flex activity-content" id="web_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bx-globe text-primary" style="color: #b6aa6e !important" onclick="javascript:load_posts('subtopic', 'Web', '{{ csrf_token() }}', 'open', '', '');"></i>
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
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c3 !important">
                            <div class="avatar-content">
                                <i class="bx bxl-facebook-square text-primary" style="color: #3B5998 !important" onclick="javascript:load_posts('subtopic', 'Facebook', '{{ csrf_token() }}', 'open', '', '');"></i>
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
                                <i class="bx bxl-instagram-alt text-success" style="color: #E4405F !important" onclick="javascript:load_posts('subtopic', 'Instagram', '{{ csrf_token() }}', 'open', '', '');"></i>
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
                                <i class="fa-brands fa-x-twitter text-warning" style="color: #000000 !important" onclick="javascript:load_posts('subtopic', 'Twitter', '{{ csrf_token() }}', 'open', '', '');"></i>
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
                                <i class="bx bxl-youtube text-danger" onclick="javascript:load_posts('subtopic', 'Youtube', '{{ csrf_token() }}', 'open', '', '');"></i>
                                <!-- javascript:load_posts('subtopic', 'videos', '{{ csrf_token() }}', 'open'); -->
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="video" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-danger progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="40" id="youtube_progress_bar" style="width: 40%; box-shadow: none"></div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex activity-content" id="reddit_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bxl-reddit text-warning" style="color: #FF4301 !important" onclick="javascript:load_posts('subtopic', 'Reddit', '{{ csrf_token() }}', 'open', '', '');"></i>
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
                                <i class="bx bxl-blogger text-success" style="color: #F57D00 !important" onclick="javascript:load_posts('subtopic', 'Blogs', '{{ csrf_token() }}', 'open', '', '');"></i>
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
                                <i class="bx bxl-tumblr text-danger" style="color: #34526F !important" onclick="javascript:load_posts('subtopic', 'Tumblr', '{{ csrf_token() }}', 'open', '', '');"></i>
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
                                <i class="bx bx-news text-primary" style="color: #77BD9D !important" onclick="javascript:load_posts('subtopic', 'News', '{{ csrf_token() }}', 'open', '', '');"></i>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="news" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-primary progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="30" id="news_progress_bar" style="width: 30%; background: #77BD9D !important; box-shadow: none"></div>
                            </div>
                        </div>
                    </div>
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
                    <div class="d-flex activity-content" id="googlemaps_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bxs-map text-primary" style="color: #8a2be2 !important" onclick="javascript:load_posts('subtopic', 'GoogleMaps', '{{ csrf_token() }}', 'open', '', '');"></i>
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
                                <i class="bx bxl-trip-advisor text-primary" style="color: #00AF87 !important" onclick="javascript:load_posts('subtopic', 'Tripadvisor', '{{ csrf_token() }}', 'open', '', '');"></i>
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
                                <i class="bx bxl-linkedin-square text-primary" style="color: #0072b1 !important" onclick="javascript:load_posts('subtopic', 'Linkedin', '{{ csrf_token() }}', 'open', '', '');"></i>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="linkedin" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-primary progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="30" id="linkedin_progress_bar" style="width: 30%; background: #0072b1 !important; box-shadow: none"></div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex activity-content" id="google_play_reviews_data" style="display: none !important;">
                        <div class="avatar m-0 mr-75" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bxl-play-store text-primary" style="color: #F4B400 !important" onclick="javascript:load_reviews_data('GooglePlayStore', '{{ csrf_token() }}', 'open', 'subtopic', '');"></i>
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
                                <i class="bx bxs-store-alt text-primary" style="color: #4285f4 !important" onclick="javascript:load_reviews_data('GoogleMyBusiness', '{{ csrf_token() }}', 'open', 'subtopic', '');"></i>
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
                                <i class="bx bxl-apple text-primary" style="color: #666666 !important" onclick="javascript:load_reviews_data('AppleAppStore', '{{ csrf_token() }}', 'open', 'subtopic', '');"></i>
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
                                <i class="bx bxs-category-alt text-primary" style="color: #cf0a2c !important" onclick="javascript:load_reviews_data('HuaweiAppGallery', '{{ csrf_token() }}', 'open', 'subtopic', '');"></i>
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
                                <i class="bx bxs-book-content text-primary" style="color: #1C6A26 !important" onclick="javascript:load_reviews_data('Glassdoor', '{{ csrf_token() }}', 'open', 'subtopic', '');"></i>
                            </div>
                        </div>
                        <div class="activity-progress flex-grow-1">
                            <small class="text-muted d-inline-block mb-50" id="glassdoor_reviews" style="font-size:90%; color: #000000 !important;"></small> <small class="float-right"></small>
                            <div class="progress progress-bar-primary progress-sm">
                                <div class="progress-bar" role="progressbar" aria-valuenow="20" style="width: 20%; background: #1C6A26 !important; box-shadow: none"></div>
                            </div>
                        </div>
                    </div>
                    <div style="padding: 20px;"></div>
                </div>
            </div>
        </div>
        
        {{-- Reviews sentiment--}}
        <div class="col-sm-4 growth-card" id="reviews_senti_container">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title font_size_1point5_rem">Reviews sentiment <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Reviews sentiment around the in-depth analysis" data-trigger="hover" data-placement="top"></i></span></h4>
                        </div>
                        <div class="card-body">
                            <div id="subtopic_reviews_sentiments" class="d-flex justify-content-center"></div>
                        </div>
                    </div>
                </div>
            </div>
	    </div>
        
        {{-- CSAT Score--}}
		<div class="col-sm-3 growth-card" id="csat_container">
	      <div class="card">
	        <div class="card-body text-center">
                <div style="padding-bottom: 40px; text-align: left;"><h5>Customer satisfaction score <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Customer satisfaction score around the in-depth analysis" data-trigger="hover" data-placement="top"></i></span></h5></div>
	        	<div class="spinner-border" role="status" id="csat_loading_icon"><span class="sr-only">Loading...</span></div>
	          <div id="csat_chart"></div>
	        </div>
	      </div>
	    </div>  
    </div>
    <?php
    }
    ?>
    
    <?php
    if($st_type == 'cx_monitoring')
    {
    ?>
    <div class="row">
        {{-- Sentiment analaysis--}}
		<div class="col-sm-4 profit-report-card">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title font_size_1point5_rem">Sentiment Summary <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Overall sentiment around the in-depth analysis" data-trigger="hover" data-placement="top"></i></span></h4>
                        </div>
                        <div class="card-body">
                            <div id="subtopic_donut_chart" class="d-flex justify-content-center"></div>
                        </div>
                    </div>
                </div>
            </div>
		</div>
        {{-- Sentiment area chart--}}
        <div class="col-8">
			<div class="card widget-order-activity">
				<div class="card-header d-md-flex justify-content-between align-items-center">
					<h4 class="card-title font_size_1point5_rem">Sentiment trends <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Sentiment trend for in-depth analysis" data-trigger="hover" data-placement="top"></i></span></h4>
					
				</div>
				<div class="card-body">
					<div id="subtopic_senti_area_chart"></div>
				</div>
			</div>
		</div>
    </div>
    <?php
    }
    ?>
    
    <?php
    if($st_type == 'cx_monitoring')
    {
    ?>
    <div class="row">
        {{-- Emontions line chart --}}
		<div class="col-sm-8 profit-report-card">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title font_size_1point5_rem">Emotion trends <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Emotion trend for in-depth analysis" data-trigger="hover" data-placement="top"></i></span></h4>
                        </div>
                        <div class="card-body">
                            <div id="subtopic_emo_line_area_chart" class="d-flex justify-content-center"></div>
                        </div>
                    </div>
                </div>
            </div>
		</div>
        {{-- Emotions bar chart --}}
        <div class="col-4">
			<div class="card widget-order-activity">
				<div class="card-header d-md-flex justify-content-between align-items-center">
					<h4 class="card-title font_size_1point5_rem">Emotion mention counts <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Emotions mentions count for in-depth analysis" data-trigger="hover" data-placement="top"></i></span></h4>
					
				</div>
				<div class="card-body">
					<div id="subtopic_emotions_bar_chart"></div>
				</div>
			</div>
		</div>
        {{-- Emotions radar chart --}}
        <!--<div class="col-sm-4 profit-report-card">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Emotions Analysis</h4>
                        </div>
                        <div class="card-body">
                            <div id="subtopic_emotions_radar_chart" class="d-flex justify-content-center"></div>
                        </div>
                    </div>
                </div>
            </div>
		</div>-->
    </div>
    <?php
    }
    ?>
    
    <?php
    if($st_type == 'cx_monitoring')
    {
    ?>
    <div class="row" id="touchpoint_container">
        {{-- Touchpoints listing--}}
		<div class="col-sm-6 profit-report-card">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title font_size_1point5_rem">Touchpoint mentions <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Touchpoint mentions for in-depth analysis" data-trigger="hover" data-placement="top"></i></span></h4>
                        </div>
                        <div class="card-body">
                            <div id="touchpoints_chart" class="d-flex justify-content-center"></div>
                        </div>
                    </div>
                </div>
            </div>
		</div>
        {{--touchpoints emotions--}}
        <div class="col-sm-6 profit-report-card">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title font_size_1point5_rem">Emotions associated with Touchpoint <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Emotions associated with Touchpoints" data-trigger="hover" data-placement="top"></i></span></h4>
                        </div>
                        <div class="card-body">
                            <div id="touchpoints_emotions_chart" class="d-flex justify-content-center"></div>
                        </div>
                    </div>
                </div>
            </div>
		</div>
		
		<!--<div class="col-sm-6 profit-report-card" id="touchpoint_senti_container">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title font_size_1point5_rem">Touchpoint sentiments summary <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Touchpoint sentiments division for in-depth analysis" data-trigger="hover" data-placement="top"></i></span></h4>
                        </div>
                        <div class="card-body">
                        	<script src="{{asset('vendors/js/charts/apexcharts.js')}}"></script>
                        	<?php
                        	if($touch_points_data != 'NA' && 1 == 2)
                        	{
	                        	for($i=0; $i<count($touch_points_data); $i++)
	                        	{
	                        		$senti_data = $tp_obj->get_touchpoint_sentiments_data($touch_points_data[$i]->cx_tp_tp_id);
	                        		$touchpoint_data = $tp_obj->get_touchpoint_data($touch_points_data[$i]->cx_tp_tp_id);
	                        	?>
	                        	<div class="col-sm-4" style="float:left;">
	                        		<div style="text-align: center;"><?php echo $touchpoint_data[0]->tp_name; ?></div>
	                        		<div><pre><?php //print_r($senti_data); ?></pre></div>
	                        		<div id="sentichart<?php echo $i; ?>"></div>
	                        		
		                        	
		                            <script type="text/javascript">
		                            	
		                            	var options = {
									          series: [<?php echo $senti_data["pos"]; ?>, <?php echo $senti_data["neg"]; ?>, <?php echo $senti_data["neu"]; ?>],
									          chart: {
									          width: 250,
									          type: 'pie',
                                                events: {
                                                        dataPointSelection: function(event, chartContext, config) {
                                                            //alert(opts.w.config.xaxis.categories[opts.dataPointIndex] + " "+chartContext+" "+opts)
                                                            //console.log(config);
                                                            //console.log(config.w.config.series[config.seriesIndex])
                                                            //console.log(config.w.config.series[config.seriesIndex].name)
                                                            //console.log(config.w.config.series[config.seriesIndex].data[config.dataPointIndex])
                                                            //console.log(config.w.config.series[config.dataPointIndex])
                                                            //console.log(config.w.config.labels[config.dataPointIndex].trim())
                                                            load_posts('touchpoint', 'All', '{{ csrf_token() }}', 'open', config.w.config.labels[config.dataPointIndex].trim(), '<?php echo $touchpoint_data[0]->tp_id; ?>');
                                                        }
                                                    }
									        },
									        colors: ['#67c99c','#ce3a60', '#f8d774'],
									        labels: ['Positive', 'Negative', 'Neutral'],
									        legend: false
									        };

									        var chart<?php echo $i; ?> = new ApexCharts(document.querySelector("#sentichart<?php echo $i; ?>"), options);
									        chart<?php echo $i; ?>.render();
		                            </script>	
	                        	</div>
                        	<?php
                        		}
                        	}
                        	?>
                        	
                        </div>
                    </div>
                </div>
            </div>
		</div>-->
    </div>
    <div class="row">
        {{-- Touchpoints sentiments--}}
        <div class="col-sm-12 profit-report-card" id="touchpoint_senti_container" style="padding:0px;">
            <script src="{{asset('vendors/js/charts/apexcharts.js')}}"></script>
            <?php
            if($touch_points_data != 'NA')
            {
                $j=10;
                for($i=0; $i<count($touch_points_data); $i++)
                {
                    $senti_data = $tp_obj->get_touchpoint_sentiments_data($touch_points_data[$i]->cx_tp_tp_id);
                    $touchpoint_data = $tp_obj->get_touchpoint_data($touch_points_data[$i]->cx_tp_tp_id);
                ?>
                <div class="col-sm-3" style="float:left;">
                    <div class="card" style="padding-bottom: 15px;">
                        <div class="card-header" style="padding-bottom: 0px;">
                            <h4 class="card-title font_size_1point5_rem" style="font-size:1.3rem !important;"><?php echo $touchpoint_data[0]->tp_name; ?></h4>
                        </div>

                        <div id="sentichart<?php echo $i; ?>"></div>


                        <script type="text/javascript">

                            var options = {
                                  series: [<?php echo $senti_data["pos"]; ?>, <?php echo $senti_data["neg"]; ?>, <?php echo $senti_data["neu"]; ?>],
                                  chart: {
                                  height: 290,
                                  type: 'donut',
                                    events: {
                                            dataPointSelection: function(event, chartContext, config) {
                                                load_posts('touchpoint', 'All', '{{ csrf_token() }}', 'open', config.w.config.labels[config.dataPointIndex].trim(), '<?php echo $touchpoint_data[0]->tp_id; ?>');
                                            }
                                        }
                                },
                                colors: ['#67c99c','#ce3a60', '#f8d774'],
                                labels: ['Positive', 'Negative', 'Neutral'],
                                legend: {
                                    itemMargin: {
                                        horizontal: 2
                                    },
                                    position: 'bottom',
                                }
                                };

                                var chart<?php echo $i; ?> = new ApexCharts(document.querySelector("#sentichart<?php echo $i; ?>"), options);
                                chart<?php echo $i; ?>.render();
                        </script>
                    </div>
                </div>
            <?php
                    $j = $j+1;
                }
            }
            ?>
		</div>
    </div>
    <?php
    }
    ?>
    
    <?php
    if($st_type == 'media_monitoring')
    {
    ?>
    {{--Languages and AV--}}
    <div class="row">
        <div class="col-sm-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title font_size_1point5_rem">Languages <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Languages of mentions around the in-depth analysis" data-trigger="hover" data-placement="top"></i></span></h4>
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
                </div>
            </div>
        </div>
    </div>
    <?php
    }
    ?>
    
    <?php
    if($st_type == 'campaign_monitoring')
    {
    ?>
    <div class="row">
        <div class="col-sm-7 dashboard-marketing-campaign">
            <div class="card marketing-campaigns">
                <div class="card-header d-flex justify-content-between align-items-center pb-1">
                    <h4 class="card-title font_size_1point5_rem">Top Influencers <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Top influentials account mentions around the in-depth analysis" data-trigger="hover" data-placement="top"></i></span></h4>
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
                </ul>-->
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
                            <h4 class="card-title font_size_1point5_rem">Influencer category <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Type of influencers based on number of followers mentions around the in-depth analysis" data-trigger="hover" data-placement="top"></i></span></h4>
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
    <?php
    }
    ?>
    
    <?php
    if($st_type == 'campaign_monitoring' || $st_type == 'media_monitoring')
    {
    ?>
    {{--Active users list--}}
    <div class="row">
        <div class="col-md-12 col-sm-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title font_size_1point5_rem">Active audience <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Accounts with most mentions around the in-depth analysis" data-trigger="hover" data-placement="top"></i></span></h4>
                </div>
                <div class="card-body">
                    <div id="active_users_list" class="hide-native-scrollbar" style="overflow-y:auto; height: 320px;"></div>
                </div>
            </div>
        </div>
    </div>
    <?php
    }
    ?>
    
    <?php
    if($st_type == 'cx_monitoringpppp' && $touch_points_data != 'NA')
    {
    ?>
    {{-- word cloud --}}
    <script src="https://cdn.amcharts.com/lib/4/core.js"></script>
    <script src="https://cdn.amcharts.com/lib/4/charts.js"></script>
    <script src="https://cdn.amcharts.com/lib/4/plugins/wordCloud.js"></script>
    <script src="https://cdn.amcharts.com/lib/4/themes/animated.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/webfont/1.6.26/webfont.js"></script>
    <div class="row" style="display:none;">
        <div class="col-sm-12 growth-card">
            <div class="card">
                <div class="card-body">
                    <div style="padding-bottom: 30px;">
                        <div style="width: 70%; float: left;"><h4 class="card-title font_size_1point5_rem">Top used words  (Touch points)</h4></div>
                        <div style="width: 20%; float: right;"><div class="spinner-border" role="status" id="wordcloud_loading_tp" style="margin-top:20px; float: right;"><span class="sr-only">Loading...</span></div></div>
                        <div style="clear:both;"></div>
                    </div>
                    <div style="width:100%;">
                        <ul class="nav nav-tabs pb-0" id="myTab" role="tablist">
                            <?php
                            for($i=0; $i<count($touch_points_data); $i++)
                            {
	                        	$touchpoint_data = $tp_obj->get_touchpoint_data($touch_points_data[$i]->cx_tp_tp_id);
                                
                                
                            ?>
                            <li class="nav-item"><a class="nav-link<?php if($i==0) echo ' active'; ?>" id="list_view_<?php echo $touch_points_data[$i]->cx_tp_tp_id; ?>" data-toggle="tab" href="#list_view_container_<?php echo $touch_points_data[$i]->cx_tp_tp_id; ?>" role="tab" aria-controls="list_view" aria-selected="true"> <?php echo $touchpoint_data[0]->tp_name; ?></a></li>
                            <?php
                            }
                            ?>                            
                        </ul>
                        <!--Tab pans -->
                        <div class="tab-content pt-1">
                            <?php
                            for($i=0; $i<count($touch_points_data); $i++)
                            {
	                        	$touchpoint_data = $tp_obj->get_touchpoint_data($touch_points_data[$i]->cx_tp_tp_id);
                                
                                
                            ?>
                            <div class="tab-pane<?php if($i==0) echo ' active'; ?>" id="list_view_container_<?php echo $touch_points_data[$i]->cx_tp_tp_id; ?>" role="tabpanel" aria-labelledby="list_view_container_<?php echo $touch_points_data[$i]->cx_tp_tp_id; ?>">
                                <div style="width:100%;">
                                    <ul class="nav nav-tabs pb-0" id="myTab" role="tablist">
                                        <li class="nav-item"><a class="nav-link active" id="list_view1" data-toggle="tab" href="#list_view_container1" role="tab" aria-controls="list_view1" aria-selected="true"> List view </a></li>
                                        <li class="nav-item"><a class="nav-link" id="cloud_view1" data-toggle="tab" href="#cloud_view_container1" role="tab" aria-controls="cloud_view1" aria-selected="false"> Cloud view </a></li>
                                    </ul>
                                    <!--Tab pans -->
                                    <div class="tab-content pt-1">
                                        <div class="tab-pane active" id="list_view_container1" role="tabpanel" aria-labelledby="list_view_container1">
                                            Loading ....
                                        </div>

                                        <div class="tab-pane" id="cloud_view_container1" role="tabpanel" aria-labelledby="cloud_view_container1">
                                            <div id="wordcloud1" style="width: 100%; height: 500px;letter-spacing:normal;"></div>
                                        </div>
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

        </div>
    </div>
    
    <?php
    }
    ?>
    
    <?php
    if($st_type == 'campaign_monitoring' || $st_type == 'cx_monitoring')
    {
    ?>
    {{-- word cloud --}}
    <script src="https://cdn.amcharts.com/lib/4/core.js"></script>
    <script src="https://cdn.amcharts.com/lib/4/charts.js"></script>
    <script src="https://cdn.amcharts.com/lib/4/plugins/wordCloud.js"></script>
    <script src="https://cdn.amcharts.com/lib/4/themes/animated.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/webfont/1.6.26/webfont.js"></script>
    <div class="row">
        <div class="col-sm-12 growth-card">
            <div class="card">
                <div class="card-body">
                    <div style="padding-bottom: 30px;">
                        <div style="width: 70%; float: left;"><h4 class="card-title font_size_1point5_rem">Top used words <span><i class='bx bx-info-circle' data-toggle="popover" data-content="Identify prominent words mentioned around the in-depth analysis" data-trigger="hover" data-placement="top"></i></span></h4></div>
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


                </div>
              </div>

        </div>
    </div>
    
    <?php
    }
    ?>
    
	
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
<script src="{{asset('js/scripts/custom.js')}}"></script>
<script src="{{asset('js/scripts/pickers/dateTime/pick-a-datetime.js')}}"></script>
<script src="{{asset('js/scripts/forms/select/form-select2.js')}}"></script>
<script type="text/javascript">
  $(function(){
    load_data('dashboard_subtopic', '{{ csrf_token() }}');
  });
  </script>
@endsection