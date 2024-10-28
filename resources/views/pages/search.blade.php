@extends('layouts.contentLayoutMaster')
{{-- page title --}}
@section('title','Search')
{{-- vendor style --}}
@section('vendor-styles')
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/swiper.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/pickadate/pickadate.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/daterange/daterangepicker.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/forms/select/select2.min.css')}}">
@endsection

@section('page-styles')
<link rel="stylesheet" type="text/css" href="{{asset('css/swiper.css')}}">
@endsection

@section('content')
<div class="card">
    <div class="card-body" style="padding-bottom: 0px;">
        <div class="col-sm-12" id="message_area" style="color:#ff0000; display: none; padding-left: 0px; height: 30px;"></div>
        <div class="row">
            <div class="col-sm-3">
                <label for="post_dashboard">Select Dashboard</label>
                <select class="select2 form-control"  name="topics_list" id="topics_list" onchange="javascript:load_topic_keywords_hash(this.value);">
                    <?php
                    for ($i = 0; $i < count($topics_list); $i++) {
                        ?>
                        <option value="<?php echo base64_encode($topics_list[$i]->topic_id); ?>"><?php echo $topics_list[$i]->topic_title; ?></option>
                        <?php
                    }
                    ?>
                </select>
            </div>
            <div class="col-sm-7">
                <label for="search_text">Enter text to search</label>
                <input type="text" class="form-control" id="search_text" name="search_text" placeholder="Search">
            </div>
            <div class="col-sm-1" style="text-align: center;">
                <button type="button" class="btn mr-1 btn-primary form-control" style="width: 150px; margin-top: 23px;" onclick="javascript:get_search_results('everything', 1);">Search</button>
            </div>
            <div class="col-sm-1" style="text-align: center;">
                <div class="spinner-border" role="status" id="filters_loading" style="display:none; margin-top: 30px; width: 20px; height: 20px;"><span class="sr-only">Loading...</span></div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card collapse-header" style="margin-bottom: 0px;">
                    <div id="headingCollapse1" class="card-header" data-toggle="collapse" role="button" data-target="#collapse1" aria-expanded="false" aria-controls="collapse1" style="padding-left:0px; padding-top: 7px; padding-bottom: 0px; text-transform: uppercase; color: #BAC0C8; font-size: 13px;">
                        <span class="collapse-title">
                            <b>Show Filters</b>
                        </span>
                    </div>

                    <div id="collapse1" role="tabpanel" aria-labelledby="headingCollapse1" class="collapse">
                        <div class="row">
                            <div class="col-12">
                                <div class="card" style="margin-bottom: 0px">
                                    <div class="card-body" style="padding-left:0px; padding-top:15px; padding-bottom: 0px;">
                                        <div class="row">
                                            <div class="col-md-2 col-sm-2 mb-2">
                                                <label for="operator">Select operator</label>
                                                <div class="btn-group btn-group-toggle btn-group-sm" role="group" data-toggle="buttons" >
                                                    <label class="btn btn-outline-primary active" aria-label="Size Small">
                                                        <input type="radio" name="op_option" value="OR" id="oropr" autocomplete="off" checked> OR
                                                    </label>
                                                    <label class="btn btn-outline-primary" aria-label="Size Small">
                                                        <input type="radio" name="op_option" value="AND" id="andopr" autocomplete="off"> AND
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-10 col-sm-10 mb-2">
                                                <label for="hashtags">Select / un-select keywords or hashtags</label>
                                                <div class="row">
                                                    <div class="col-12" id="keys_hash_urls">
                                                        <?php
                                                        $topic_khu = Helper::get_topic_khu($default_topic_id);
                                                        $khu = explode(",", $topic_khu);
                                                        for ($i = 0; $i < count($khu); $i++) {
                                                            echo '<div style="float: left; background: #5A8DEE; border-radius: 5px; color: #fff; padding: 3px 5px 3px 5px; margin: 0px 5px 5px 0px; cursor: pointer;" id="tag' . $i . '" onclick="javascript:set_custom_selection(\'' . $i . '\');">' . str_replace("'", "", $khu[$i]) . '</div>';
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-2 col-sm-3">
                                                <label for="fromdate">From Date</label>
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="mb-1">
                                                            <fieldset class="form-group position-relative has-icon-left">
                                                                <input type="text" class="form-control pickadate" name="from_date" id="from_date" placeholder="Select Date">
                                                                <div class="form-control-position">
                                                                    <i class="bx bx-calendar"></i>
                                                                </div>
                                                            </fieldset>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-2 col-sm-3">
                                                <label for="todate">To Date</label>
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="mb-1">
                                                            <fieldset class="form-group position-relative has-icon-left">
                                                                <input type="text" class="form-control pickadate" name="to_date" id="to_date" placeholder="Select Date">
                                                                <div class="form-control-position">
                                                                    <i class="bx bx-calendar"></i>
                                                                </div>
                                                            </fieldset>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-2 col-sm-3">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="mb-1">
                                                            <label for="sentimenttype">Sentiment Type</label>
                                                            <select class="select2 form-control sentiment_type_placeholder" multiple="multiple" name="post_senti[]" id="post_senti">
                                                                <option value="positive">Positive</option>
                                                                <option value="negative">Negative</option>
                                                                <option value="neutral">Neutral</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-2 col-sm-3">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="mb-1">
                                                            <label for="usertype">User Type</label>
                                                            <select name="user_type[]" id="user_type" class="select2 form-control" multiple="multiple">
                                                                <option value="normal">Normal</option>
                                                                <option value="influencer">Influencer</option>
                                                                <option value="unverified">Un-Verified</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-2 col-sm-3">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="mb-0">
                                                            <label for="datasource">Select Data Source</label>
                                                            <select class="select2 form-control data_source_placeholder" multiple="multiple" name="data_source[]" id="data_source">
                                                                <option value="youtube">Videos</option>
                                                                <option value="pinterest">Pinterest</option>
                                                                <option value="linkedin">Linkedin</option>
                                                                <option value="twitter">Twitter</option>
                                                                <option value="facebook">Facebook</option>
                                                                <option value="instagram">Instagram</option>
                                                                <option value="reddit">Reddit</option>
                                                                <option value="tumblr">Tumblr</option>
                                                                <option value="blogs">Blogs</option>
                                                                <option value="news">News Sources</option>
                                                                <option value="web">Web</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-2 col-sm-3">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="mb-0">
                                                            <label for="category">Select Category</label>
                                                            <select class="select2 form-control" multiple="multiple" name="data_category[]" id="data_category">
                                                                <option value="Business">Business</option>
                                                                <option value="Education">Education</option>
                                                                <option value="Entertainment">Entertainment</option>
                                                                <option value="Fashion">Fashion</option>
                                                                <option value="Food">Food</option>
                                                                <option value="Health">Health</option>
                                                                <option value="Politics">Politics</option>
                                                                <option value="Sports">Sports</option>
                                                                <option value="Technology">Technology</option>
                                                                <option value="Transport">Transport</option>
                                                                <option value="Weather">Weather</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!--<div class="col-md-2 col-sm-3">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="mb-1">
                                                            <label for="speechtype">Speech Type</label>
                                                            <select name="speech[]" id="speech" class="select2 form-control" multiple="multiple">
                                                                <option value="hate">Hate Speech</option>
                                                                <option value="normal_lang">Normal Language</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-2 col-sm-3">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="mb-1">
                                                            <label for="politicalnonpolitical">Political / Non Political</label>
                                                            <select class="select2 form-control" multiple="multiple" name="polit_non[]" id="polit_non">
                                                                <option value="polit">Political Speech</option>
                                                                <option value="non_polit">Non Political</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>-->
                                        </div>
                                        <div class="row">
                                            
                                            
                                            <div class="col-md-2 col-sm-3">
                                                <div class="form-group">
                                                    <label for="filterbylocation">Filter By Location</label>
                                                    <select class="select2 form-control filter_by_location_placeholder" multiple="multiple" name="data_location[]" id="data_location">
                                                        <?php
                                                        $countries_list = Helper::get_countries_list();
                                                        for($i=0; $i<count($countries_list); $i++)
                                                        {
                                                        ?>
                                                        <option value="<?php echo $countries_list[$i]->country_name; ?>"><?php echo $countries_list[$i]->country_name; ?></option>
                                                        <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2 col-sm-3">
                                                <div class="form-group">
                                                    <label for="filterbytopic">Filter By Language</label>
                                                    <select class="select2 form-control filter_by_language_placeholder" multiple="multiple" name="data_lang[]" id="data_lang">
                                                        <option value="en">English</option>
                                                        <option value="ar">Arabic</option>
                                                    </select>
                                                </div>
                                            </div>


                                        </div>
                                        <div class="row mb-1"><a id="results_anchor"></a>
                                                                                        

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <div class="row">
            <div class="col-sm-2" style="margin-top: 15px;">
                
                <input type="hidden" name="selected_hash_key" id="selected_hash_key" value="<?php echo $topic_khu; ?>">
                <input type="hidden" name="token" id="token" value="{{ csrf_token() }}">
                <input type="hidden" name="results_page_no" id="results_page_no" value="1">
            </div>
            
        </div>
    </div>
</div>

<div class="row" style="padding-right: 15px;">
    <div class="col-sm-7" style="padding-left: 0px; display: none;" id="search_results_container">
        <!--<div class="hide-native-scrollbar" style="max-height: 700px; overflow-x: hidden; overflow-y: auto; padding-left: 15px;" id="search_results"></div>-->
        <div style="padding-left: 15px;" id="search_results"></div>
        <div style="float: left; padding-left: 15px; display: none;" id="search_results_load_more"><!--<button class="btn btn-primary" onclick="javascript:get_search_results('load_more', $('#results_page_no').val());">Load more</button>--></div>
        <div class="spinner-border" role="status" id="loadmore_loading" style="width: 20px; height: 20px; float: left; margin: 10px 0px 0px 20px; display: none;"><span class="sr-only">Loading...</span></div>
        <div id="pagination_div"></div>
    </div>
    <div class="col-sm-5 card" style="display: none; height: 700px; overflow: hidden;" id="counts_div_container">
        <div class="card-header" style="padding-left: 0px !important;">
            <h4 class="card-title">Counts by sources</h4>
        </div>
        <div class="col-sm-12">
            <div class="row search-results-count-boxes" id="facebook_con">
                <div class="card text-center" style="box-shadow: -8px 12px 18px 0 rgb(25 42 70 / 13%) !important;">
                    <div class="card-body py-1">
                        <div class="badge-circle badge-circle-lg badge-circle-light-secondary mx-auto mb-50" style="width: 30px; height: 30px;">
                            <i class="bx bxl-facebook font-medium-3" style="color: #3B5998;"></i>
                        </div>
                        <div class="text-muted line-ellipsis" id="facebook_count" style="padding: 9px 0px 9px 0px;"></div>
                    </div>
                </div>
            </div>
            <div class="row search-results-count-boxes" id="twitter_con">
                <div class="card text-center" style="box-shadow: -8px 12px 18px 0 rgb(25 42 70 / 13%) !important;">
                    <div class="card-body py-1">
                        <div class="badge-circle badge-circle-lg badge-circle-light-secondary mx-auto mb-50" style="width: 30px; height: 30px;">
                            <i class="bx bxl-twitter font-medium-3" style="color: #00ABEA;"></i>
                        </div>
                        <div class="text-muted line-ellipsis" id="twitter_count" style="padding: 9px 0px 9px 0px;"></div>
                    </div>
                </div>
            </div>
            <div class="row search-results-count-boxes" id="pinterest_con">
                <div class="card text-center" style="box-shadow: -8px 12px 18px 0 rgb(25 42 70 / 13%) !important;">
                    <div class="card-body py-1">
                        <div class="badge-circle badge-circle-lg badge-circle-light-secondary mx-auto mb-50" style="width: 30px; height: 30px;">
                            <i class="bx bxl-pinterest font-medium-3" style="color: #bd081c;"></i>
                        </div>
                        <div class="text-muted line-ellipsis" id="pinterest_count" style="padding: 9px 0px 9px 0px;"></div>
                    </div>
                </div>
            </div>
            <div class="row search-results-count-boxes" id="tumblr_con">
                <div class="card text-center" style="box-shadow: -8px 12px 18px 0 rgb(25 42 70 / 13%) !important;">
                    <div class="card-body py-1">
                        <div class="badge-circle badge-circle-lg badge-circle-light-secondary mx-auto mb-50" style="width: 30px; height: 30px;">
                            <i class="bx bxl-tumblr font-medium-3" style="color: #34526F;"></i>
                        </div>
                        <div class="text-muted line-ellipsis" id="tumblr_count" style="padding: 9px 0px 9px 0px;"></div>
                    </div>
                </div>
            </div>
            <div class="row search-results-count-boxes" id="videos_con">
                <div class="card text-center" style="box-shadow: -8px 12px 18px 0 rgb(25 42 70 / 13%) !important;">
                    <div class="card-body py-1">
                        <div class="badge-circle badge-circle-lg badge-circle-light-secondary mx-auto mb-50" style="width: 30px; height: 30px;">
                            <i class="bx bxl-youtube font-medium-3" style="color: #FF5B5C;"></i>
                        </div>
                        <div class="text-muted line-ellipsis" id="videos_count" style="padding: 9px 0px 9px 0px;"></div>
                    </div>
                </div>
            </div>
            <div class="row search-results-count-boxes" id="reddit_con">
                <div class="card text-center" style="box-shadow: -8px 12px 18px 0 rgb(25 42 70 / 13%) !important;">
                    <div class="card-body py-1">
                        <div class="badge-circle badge-circle-lg badge-circle-light-secondary mx-auto mb-50" style="width: 30px; height: 30px;">
                            <i class="bx bxl-reddit font-medium-3" style="color: #FF4301;"></i>
                        </div>
                        <div class="text-muted line-ellipsis" id="reddit_count" style="padding: 9px 0px 9px 0px;"></div>
                    </div>
                </div>
            </div>
            <div class="row search-results-count-boxes" id="instagram_con">
                <div class="card text-center" style="box-shadow: -8px 12px 18px 0 rgb(25 42 70 / 13%) !important;">
                    <div class="card-body py-1">
                        <div class="badge-circle badge-circle-lg badge-circle-light-secondary mx-auto mb-50" style="width: 30px; height: 30px;">
                            <i class="bx bxl-instagram font-medium-3" style="color: #E4405F;"></i>
                        </div>
                        <div class="text-muted line-ellipsis" id="instagram_count" style="padding: 9px 0px 9px 0px;"></div>
                    </div>
                </div>
            </div>
            <div class="row search-results-count-boxes" id="news_con">
                <div class="card text-center" style="box-shadow: -8px 12px 18px 0 rgb(25 42 70 / 13%) !important;">
                    <div class="card-body py-1">
                        <div class="badge-circle badge-circle-lg badge-circle-light-secondary mx-auto mb-50" style="width: 30px; height: 30px;">
                            <i class="bx bx-news font-medium-3" style="color: #77BD9D;"></i>
                        </div>
                        <div class="text-muted line-ellipsis" id="news_count" style="padding: 9px 0px 9px 0px;"></div>
                    </div>
                </div>
            </div>
            <div class="row search-results-count-boxes" id="blogs_con">
                <div class="card text-center" style="box-shadow: -8px 12px 18px 0 rgb(25 42 70 / 13%) !important;">
                    <div class="card-body py-1">
                        <div class="badge-circle badge-circle-lg badge-circle-light-secondary mx-auto mb-50" style="width: 30px; height: 30px;">
                            <i class="bx bx-news font-medium-3" style="color: #F57D00;"></i>
                        </div>
                        <div class="text-muted line-ellipsis" id="blogs_count" style="padding: 9px 0px 9px 0px;"></div>
                    </div>
                </div>
            </div>
            <div class="row search-results-count-boxes" id="web_con">
                <div class="card text-center" style="box-shadow: -8px 12px 18px 0 rgb(25 42 70 / 13%) !important;">
                    <div class="card-body py-1">
                        <div class="badge-circle badge-circle-lg badge-circle-light-secondary mx-auto mb-50" style="width: 30px; height: 30px;">
                            <i class="bx bx-globe font-medium-3" style="color: #b6aa6e;"></i>
                        </div>
                        <div class="text-muted line-ellipsis" id="web_count" style="padding: 9px 0px 9px 0px;"></div>
                    </div>
                </div>
            </div>
            <div class="row search-results-count-boxes" id="linkedin_con">
                <div class="card text-center" style="box-shadow: -8px 12px 18px 0 rgb(25 42 70 / 13%) !important;">
                    <div class="card-body py-1">
                        <div class="badge-circle badge-circle-lg badge-circle-light-secondary mx-auto mb-50" style="width: 30px; height: 30px;">
                            <i class="bx bxl-linkedin-square font-medium-3" style="color: #0072b1;"></i>
                        </div>
                        <div class="text-muted line-ellipsis" id="linkedin_count" style="padding: 9px 0px 9px 0px;"></div>
                    </div>
                </div>
            </div>
            <!-- Misc counts -->
            <div style="clear:both;"></div>
            <div class="row" style="padding-left: 0px !important; display:none;">
                <h4 class="card-title">Results count</h4>
            </div>
            <div class="col-sm-12" style="display:none; padding-left:0px;">
                <div class="row search-results-count-boxes" id="total_results_con">
                    <div class="card text-center" style="box-shadow: -8px 12px 18px 0 rgb(25 42 70 / 13%) !important;">
                        <div class="card-body py-1">
                            <div class="badge-circle badge-circle-lg badge-circle-light-secondary mx-auto mb-50" style="width: 30px; height: 30px;">
                                <i class="bx bx-health font-medium-3" style="color:lightblue;"></i>
                            </div>
                            <div class="text-muted line-ellipsis" id="total_results_count" style="padding: 9px 0px 9px 0px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!--<div class="card-body">
            <div class="swiper-centered-slides swiper-container p-1">
                <div class="swiper-wrapper">
                    <div class="swiper-slide rounded swiper-shadow" id="facebook_con" style="display: none;"> <i class="bx bxl-facebook font-large-2" style="color: #3B5998;"></i>
                        <div class="swiper-text pt-md-1 pt-sm-50" id="facebook_count">Facebook</div>
                    </div>
                    <div class="swiper-slide rounded swiper-shadow" id="twitter_con" style="display: none;"> <i class="bx bxl-twitter font-large-2" style="color: #00ABEA;"></i>
                        <div class="swiper-text pt-md-1 pt-sm-50" id="twitter_count">Twitter</div>
                    </div>
                    <div class="swiper-slide rounded swiper-shadow" id="pinterest_con" style="display: none;"> <i class="bx bxl-pinterest font-large-2" style="color: #bd081c;"></i>
                        <div class="swiper-text pt-md-1 pt-sm-50" id="pinterest_count">Pinterest</div>
                    </div>
                    <div class="swiper-slide rounded swiper-shadow" id="tumblr_con" style="display: none;"> <i class="bx bxl-tumblr font-large-2" style="color: #34526F;"></i>
                        <div class="swiper-text pt-md-1 pt-sm-50" id="tumblr_count">Tumblr</div>
                    </div>
                    <div class="swiper-slide rounded swiper-shadow" id="youtube_con" style="display: none;"> <i class="bx bxl-youtube font-large-2" style="color: #FF5B5C;"></i>
                        <div class="swiper-text pt-md-1 pt-sm-50" id="videos_count">Videos</div>
                    </div>
                    <div class="swiper-slide rounded swiper-shadow" id="reddit_con" style="display: none;"> <i class="bx bxl-reddit font-large-2" style="color: #FF4301;"></i>
                        <div class="swiper-text pt-md-1 pt-sm-50" id="reddit_count">Reddit</div>
                    </div>
                    <div class="swiper-slide rounded swiper-shadow" id="instagram_con" style="display: none;"> <i class="bx bxl-instagram font-large-2" style="color: #E4405F;"></i>
                        <div class="swiper-text pt-md-1 pt-sm-50" id="instagram_count">Instagram</div>
                    </div>
                    <div class="swiper-slide rounded swiper-shadow" id="news_con" style="display: none;"> <i class="bx bx-news font-large-2" style="color: #77BD9D;"></i>
                        <div class="swiper-text pt-md-1 pt-sm-50" id="news_count">News</div>
                    </div>
                    <div class="swiper-slide rounded swiper-shadow" id="blogs_con" style="display: none;"> <i class="bx bx-news font-large-2" style="color: #F57D00;"></i>
                        <div class="swiper-text pt-md-1 pt-sm-50" id="blogs_count">Blogs</div>
                    </div>
                </div>
                <!-- Add Arrows 
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
            </div>
        </div>-->        
    </div>
</div>

<!--/ Search result section -->
@endsection

{{-- vendor scripts --}}
@section('vendor-scripts')
<script src="{{asset('vendors/js/pickers/pickadate/picker.js')}}"></script>
<script src="{{asset('vendors/js/pickers/pickadate/picker.date.js')}}"></script>
<script src="{{asset('vendors/js/pickers/pickadate/picker.time.js')}}"></script>
<script src="{{asset('vendors/js/pickers/pickadate/legacy.js')}}"></script>
<script src="{{asset('vendors/js/extensions/moment.min.js')}}"></script>
<script src="{{asset('vendors/js/pickers/daterange/daterangepicker.js')}}"></script>
<script src="{{asset('vendors/js/forms/select/select2.full.min.js')}}"></script>
<script src="{{asset('vendors/js/extensions/swiper.min.js')}}"></script>
@endsection

{{-- page scripts --}}
@section('page-scripts')
<script src="{{asset('js/scripts/pickers/dateTime/pick-a-datetime.js')}}"></script>
<script src="{{asset('js/scripts/forms/select/form-select2.js')}}"></script>
<script src="{{asset('js/scripts/extensions/swiper.js')}}"></script>
<script src="{{asset('js/scripts/custom.js')}}"></script>
@endsection