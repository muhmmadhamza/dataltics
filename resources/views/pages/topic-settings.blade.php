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
        <div class="col-sm-2 col-12 dashboard-users-success" style="margin: 0px; padding: 0px; float:left;">
            <div class="card text-center">
                <div class="card-body py-1">
                    <div class="badge-circle badge-circle-lg badge-circle-light-success mx-auto mb-50">
                        <?php if ($created_topics < $allowed_topics) { ?>
                            <a href="javascript:void(0);" data-toggle="modal" data-target="#addDashboardModal"><i class="bx bx-plus-medical font-medium-5"></i></a>
                        <?php } ?>
                    </div>
                    <div class="text-muted line-ellipsis">Add dashboard</div>
                    <h3 class="mb-0">{{ $created_topics }} / {{ $allowed_topics }}</h3>
                </div>
            </div>
        </div>
        
        <div class="col-sm-2 col-12 dashboard-users-success" style="display:none; float:left;">
            <div class="card text-center">
                <div class="card-body py-1">
                    <div class="badge-circle badge-circle-lg badge-circle-light-success mx-auto mb-50">
                        <a href="javascript:void(0);" data-toggle="modal" data-target="#addCompDashboardModal"><i class="bx bx-plus-medical font-medium-5"></i></a>
                    </div>
                    <div class="text-muted" style="padding-bottom:10px;">Create a competitor analysis dashboard</div>
                </div>
            </div>
        </div>
        <div style="clear:both;"></div>
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
                    <section id="add-topic-section" style="overflow: hidden;"><!---->
                        <div class="col-12" style="padding: 0px; margin-top: 16px;">
                            <div class="col-md-12" style="padding: 0px;">
                                <div class="card" style="box-shadow: none;">
                                    <div class="card-body" style="padding:0px;">
                                        <form name="create_cdash" id="create_cdash" autocomplete="off" enctype="multipart/form-data">
                                            <div class="row">
                                                <div id="error_msg" style="display: none; color: #ff0000;"></div>
                                                <div class="col-md-12">
                                                    <fieldset class="form-group">
                                                        <label for="basicInput">Dashboard Title</label>
                                                        <input type="text" class="form-control" id="topic_title" name="topic_title" placeholder="Topic title" required="required">
                                                    </fieldset>

                                                    <fieldset class="form-group">
                                                        <label for="basicInputFile">Filter by source</label>
                                                        <select name="topic_ids[]" id="topic_ids" class="select2 form-control select2-hidden-accessible" multiple="multiple">
                                                            <?php
                                                            for($i=0; $i<count($topics_data); $i++)
                                                            {
                                                            ?>
                                                                <option value="<?php echo $topics_data[$i]->topic_id; ?>"><?php echo $topics_data[$i]->topic_title; ?></option>
                                                            <?php
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
    
    {{--Add topic modal popup--}}
    <div class="modal fade text-left w-100" id="addDashboardModal" tabindex="-1" aria-labelledby="add_topic_popup_title" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-centered modal-dialog-scrollable modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="add_topic_popup_title">Add Dashboard</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="bx bx-x"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <section id="add-topic-section" style="overflow: hidden;"><!---->
                        <div class="col-12" style="padding: 0px; margin-top: 16px;">
                            <div class="col-md-12" style="padding: 0px;">
                                <div class="card" style="box-shadow: none;">
                                    <div class="card-body" style="padding:0px;">
                                        <form name="create_topic" id="create_topic" autocomplete="off" enctype="multipart/form-data">
                                            <div class="row">
                                                <div id="error_msg" style="display: none; color: #ff0000;"></div>
                                                <div class="col-md-12">
                                                    <fieldset class="form-group">
                                                        <label for="basicInput">Dashboard Title</label>
                                                        <input type="text" class="form-control" id="topic_title" name="topic_title" placeholder="Topic title" required="required">
                                                    </fieldset>

                                                    <fieldset class="form-group">
                                                        <label for="helpInputTop">Keywords / Hashtags</label>
                                                        <small class="text-muted">eg.<i>keyword / #hashtag (Press "Enter" to add mulitple options)</i></small>
                                                        <input type="text" class="bootstrap-t" id="topic_hash_keywords" name="topic_hash_keywords" data-role="tagsinput" required="required">
                                                    </fieldset>

                                                    <fieldset class="form-group">
                                                        <label for="helpInputTop">URLs</label>
                                                        <small class="text-muted">eg.<i>https://www.facebook.com/pagename</i></small>
                                                        <input type="text" class="bootstrap-t" id="topic_url" name="topic_url" data-role="tagsinput">
                                                    </fieldset>

                                                    <fieldset class="form-group">
                                                        <label for="helpInputTop">Exclude hashtags / keywords</label>
                                                        <small class="text-muted"><i> (Press "Enter" to add mulitple options)</i></small>
                                                        <input type="text" class="bootstrap-t" id="exclude_key_hash" name="exclude_key_hash" data-role="tagsinput">
                                                    </fieldset>

                                                    <fieldset class="form-group">
                                                        <label for="helpInputTop">Exclude accounts</label>
                                                        <small class="text-muted"><i> (Press "Enter" to add mulitple options)</i></small>
                                                        <input type="text" class="bootstrap-t" id="exclude_accounts" name="exclude_accounts" data-role="tagsinput">
                                                    </fieldset>
                                                    
                                                    <fieldset class="form-group">
                                                        <label for="helpInputTop">Google Maps / Tripadvisor URL</label>
                                                        <small class="text-muted"><i> A URL to your business on Google Maps / Tripadvisor to fetch reviews.</i></small>
                                                        <input type="text" class="form-control" id="topic_gmaps_url" name="topic_gmaps_url">
                                                    </fieldset>

                                                    <fieldset class="form-group">
                                                        <label for="basicInputFile">Upload logo</label>
                                                        <div class="custom-file">
                                                            <input type="file" class="custom-file-input" id="topic_logo" name="topic_logo">
                                                            <label class="custom-file-label" for="topic_logo">Choose file</label>
                                                        </div>
                                                    </fieldset>

                                                    <div class="row">
                                                        <div class="col-4">
                                                            <fieldset class="form-group">
                                                                <label for="basicInputFile">Filter by source</label>
                                                                <select name="data_source[]" id="data_source" class="select2 form-control select2-hidden-accessible" multiple="multiple">
                                                                    <option value="youtube">Videos</option>
                                                                    <option value="pinterest">Pinterest</option>
                                                                    <option value="facebook">Facebook</option>
                                                                    <option value="twitter">Twitter</option>
                                                                    <option value="instagram">Instagram</option>
                                                                    <option value="reddit">Reddit</option>
                                                                    <option value="tumblr">Tumblr</option>
                                                                    <option value="linkedin">Linkedin</option>
                                                                    <option value="blogs">Blogs</option>
                                                                    <option value="news">News Sources</option>
                                                                </select>
                                                            </fieldset>
                                                        </div>
                                                        <div class="col-4">
                                                            <fieldset class="form-group">
                                                                <label for="basicInputFile">Filter by location</label>
                                                                <select name="data_location[]" id="data_location" class="select2 form-control select2-hidden-accessible" multiple="multiple">
                                                                    <?php
                                                                    for ($i = 0; $i < count($country_data); $i++) {
                                                                        echo '<option value="' . $country_data[$i]->country_name . '">' . $country_data[$i]->country_name . '</option>';
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </fieldset>
                                                        </div>
                                                        <div class="col-4">
                                                            <fieldset class="form-group">
                                                                <label for="basicInputFile">Filter by language</label>
                                                                <select name="data_lang[]" id="data_lang" class="select2 form-control select2-hidden-accessible" multiple="multiple">
                                                                    <option value="en">English</option>
                                                                    <option value="ar">Arabic</option>
                                                                </select>
                                                            </fieldset>
                                                        </div>
                                                    </div>
                                                    <fieldset class="form-group">
                                                        <label for="basicInputFile">&nbsp;</label>
                                                        <div class="custom-file">
                                                            <div class="spinner-border" role="status" id="loading_icon_topic" style="display:none;">
                                                                <span class="sr-only">Loading...</span>
                                                            </div><button type="submit" class="btn btn-primary mr-1 mb-1">Create dashboard</button>&nbsp;<button type="button" class="btn btn-light mr-1 mb-1" data-dismiss="modal" aria-label="Close">Cancel</button>
                                                            <input type="hidden" name="mode" id="mode" value="create_topic">
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
    {{-- END: Add topic modal popup --}}
    
    <!-- edit topic form -->
    <?php if ($show_edit_topic == 'yes') { ?>
        <section id="edit-topic-section">
            <div class="col-12" style="padding: 0px; margin-top: 16px;">
                <div class="col-md-8">
                    <div class="card" style="box-shadow: none;">
                        <div class="card-header">
                            <h4 class="card-title">Edit Dashboard</h4>
                        </div>
                        <div class="card-body">
                            <form name="edit_topic" id="edit_topic" autocomplete="off" enctype="multipart/form-data">
                                <div class="row">
                                    <div id="error_msg" style="display: none; color: #ff0000;"></div>
                                    <div class="col-md-12">
                                        <fieldset class="form-group">
                                            <label for="basicInput">Dashboard Title</label>
                                            <input type="text" class="form-control" id="topic_title" name="topic_title" placeholder="Topic title" required="required" value="{{$edit_topic_data[0]->topic_title}}">
                                        </fieldset>

                                        <fieldset class="form-group">
                                            <label for="helpInputTop">Keywords / Hashtags</label>
                                            <small class="text-muted">eg.<i>keyword / #hashtag (Press "Enter" to add mulitple options)</i></small>
                                            <input type="text" class="bootstrap-t" id="topic_hash_keywords" name="topic_hash_keywords" data-role="tagsinput" required="required" value="<?php echo str_replace("|", ",", $edit_topic_data[0]->topic_hash_tags);
    if ($edit_topic_data[0]->topic_keywords != '') echo ',' . $edit_topic_data[0]->topic_keywords; ?>">
                                        </fieldset>

                                        <fieldset class="form-group">
                                            <label for="helpInputTop">URLs</label>
                                            <small class="text-muted">eg.<i>https://www.facebook.com/pagename</i></small>
                                            <input type="text" class="bootstrap-t" id="topic_url" name="topic_url" data-role="tagsinput" value="<?php echo str_replace("|", ",", $edit_topic_data[0]->topic_urls); ?>">
                                        </fieldset>

                                        <fieldset class="form-group">
                                            <label for="helpInputTop">Exclude hashtags / keywords</label>
                                            <small class="text-muted"><i> (Press "Enter" to add mulitple options)</i></small>
                                            <input type="text" class="bootstrap-t" id="exclude_key_hash" name="exclude_key_hash" data-role="tagsinput" value="<?php echo $edit_topic_data[0]->topic_exclude_words; ?>">
                                        </fieldset>

                                        <fieldset class="form-group">
                                            <label for="helpInputTop">Exclude accounts</label>
                                            <small class="text-muted"><i> (Press "Enter" to add mulitple options)</i></small>
                                            <input type="text" class="bootstrap-t" id="exclude_accounts" name="exclude_accounts" data-role="tagsinput" value="<?php echo $edit_topic_data[0]->topic_exclude_accounts; ?>">
                                        </fieldset>
                                        
                                        <fieldset class="form-group">
                                            <label for="helpInputTop">Google Maps / Tripadvisor URL</label>
                                            <small class="text-muted"><i> A URL to your business on Google Maps / Tripadvisor to fetch reviews.</i></small>
                                            <input type="text" class="form-control" id="topic_gmaps_url" name="topic_gmaps_url" value="<?php echo $edit_topic_data[0]->topic_gmaps_url; ?>">
                                        </fieldset>

                                        <?php
                                        if (file_exists(public_path() . '/images/topic_logos/' . $edit_topic_data[0]->topic_logo) && is_file(public_path() . '/images/topic_logos/' . $edit_topic_data[0]->topic_logo)) {
                                            ?>
                                            <fieldset class="form-group">
                                                <label for="basicInputFile">Topic logo</label>
                                                <div class="custom-file" style="height: auto;">
                                                    <img src="{{asset('images/topic_logos/')}}<?php echo '/' . $edit_topic_data[0]->topic_logo; ?>" style="width: 200px;" />
                                                    <input type="hidden" name="old_topic_logo" id="old_topic_logo" value="<?php echo $edit_topic_data[0]->topic_logo; ?>">
                                                </div>
                                            </fieldset>
                                        <?php } ?>
                                        <fieldset class="form-group">
                                            <label for="basicInputFile">Upload logo</label>
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="topic_logo" name="topic_logo">
                                                <label class="custom-file-label" for="topic_logo">Choose file</label>
                                            </div>
                                        </fieldset>
                                        <?php
                                        $src_array = explode(",", $edit_topic_data[0]->topic_data_source);
                                        ?>
                                        <div class="row">
                                            <div class="col-4">
                                                <fieldset class="form-group">
                                                    <label for="basicInputFile">Filter by source</label>
                                                    <select name="data_source[]" id="data_source" class="select2 form-control select2-hidden-accessible" multiple="multiple">
                                                        <option value="youtube"<?php if (in_array('youtube', $src_array)) echo 'selected'; ?>>Videos</option>
                                                        <option value="pinterest"<?php if (in_array('pinterest', $src_array)) echo 'selected'; ?>>Pinterest</option>
                                                        <option value="facebook"<?php if (in_array('facebook', $src_array)) echo 'selected'; ?>>Facebook</option>
                                                        <option value="twitter"<?php if (in_array('twitter', $src_array)) echo 'selected'; ?>>Twitter</option>
                                                        <option value="linkedin"<?php if (in_array('linkedin', $src_array)) echo 'selected'; ?>>Linkedin</option>
                                                        <option value="instagram"<?php if (in_array('instagram', $src_array)) echo 'selected'; ?>>Instagram</option>
                                                        <option value="reddit"<?php if (in_array('reddit', $src_array)) echo 'selected'; ?>>Reddit</option>
                                                        <option value="tumblr"<?php if (in_array('tumblr', $src_array)) echo 'selected'; ?>>Tumblr</option>
                                                        <option value="blogs"<?php if (in_array('blogs', $src_array)) echo 'selected'; ?>>Blogs</option>
                                                        <option value="news"<?php if (in_array('news', $src_array)) echo 'selected'; ?>>News Sources</option>
                                                    </select>
                                                </fieldset>
                                            </div>
                                            <?php
                                            $loc_array = explode(",", $edit_topic_data[0]->topic_data_location);
                                            ?>
                                            <div class="col-4">
                                                <fieldset class="form-group">
                                                    <label for="basicInputFile">Filter by location</label>
                                                    <select name="data_location[]" id="data_location" class="select2 form-control select2-hidden-accessible" multiple="multiple">
                                                        <?php
                                                        for ($i = 0; $i < count($country_data); $i++) {
                                                            if (in_array($country_data[$i]->country_name, $loc_array))
                                                                echo '<option value="' . $country_data[$i]->country_name . '" selected>' . $country_data[$i]->country_name . '</option>';
                                                            else
                                                                echo '<option value="' . $country_data[$i]->country_name . '">' . $country_data[$i]->country_name . '</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </fieldset>
                                            </div>
                                            <?php
                                            $lang_array = explode(",", $edit_topic_data[0]->topic_data_lang);
                                            ?>
                                            <div class="col-4">
                                                <fieldset class="form-group">
                                                    <label for="basicInputFile">Filter by language</label>
                                                    <select name="data_lang[]" id="data_lang" class="select2 form-control select2-hidden-accessible" multiple="multiple">
                                                        <option value="en"<?php if (in_array('en', $lang_array)) echo 'selected'; ?>>English</option>
                                                        <option value="ar"<?php if (in_array('ar', $lang_array)) echo 'selected'; ?>>Arabic</option>
                                                    </select>
                                                </fieldset>
                                            </div>
                                        </div>
                                        <fieldset class="form-group">
                                            <label for="basicInputFile">&nbsp;</label>
                                            <div class="custom-file">
                                                <div class="spinner-border" role="status" id="loading_icon_topic" style="display:none;">
                                                    <span class="sr-only">Loading...</span>
                                                </div><button type="submit" class="btn btn-primary mr-1 mb-1">Update dashboard</button>&nbsp;<button type="button" class="btn btn-light mr-1 mb-1" onclick="javascript:window.location = 'topic-settings';">Cancel</button>
                                                <input type="hidden" name="mode" id="mode" value="edit_topic">
                                                <input type="hidden" name="_token" id="_token" value="{{ csrf_token() }}">
                                                <input type="hidden" name="tid" id="tid" value="{{$_GET['tid']}}">
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
    <?php } ?>
    <!-- End: edit topic form -->
    
    {{-- Add sub topic --}}
    <div class="modal fade text-left w-100" id="add-subtopic-section" tabindex="-1" aria-labelledby="add_topic_popup_title" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-centered modal-dialog-scrollable modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="add_subtopic_popup_title">Add in depth analysis</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="bx bx-x"></i>
                    </button>
                </div>
                <div class="modal-body">
                        <form name="create_exp" id="create_exp" autocomplete="off" enctype="multipart/form-data">
                            <div class="row">
                                <div id="subtopic_error_msg" style="display: none; color: #ff0000;"></div>
                                <div class="col-md-12">
                                    <fieldset class="form-group">
                                        <label for="basicInput">Name</label>
                                        <input type="text" class="form-control" id="exp_name" name="exp_name" placeholder="Name" required="required">
                                    </fieldset>

                                    <fieldset class="form-group">
                                        <label for="helpInputTop">Keywords / Hashtags</label>
                                        <small class="text-muted">eg.<i>keyword / #hashtag (Press "Enter" to add mulitple options)</i></small>
                                        <input type="text" class="bootstrap-t" id="exp_keywords" name="exp_keywords" data-role="tagsinput" required="required">
                                    </fieldset>
                                    
                                    <fieldset class="form-group">
                                        <label for="helpInputTop">Exclude hashtags / keywords</label>
                                        <small class="text-muted"><i> (Press "Enter" to add mulitple options)</i></small>
                                        <input type="text" class="bootstrap-t" id="exp_exclude_keywords" name="exp_exclude_keywords" data-role="tagsinput">
                                    </fieldset>

                                    <fieldset class="form-group">
                                        <label for="helpInputTop">Exclude accounts</label>
                                        <small class="text-muted"><i> (Press "Enter" to add mulitple options)</i></small>
                                        <input type="text" class="bootstrap-t" id="exp_exclude_accounts" name="exp_exclude_accounts" data-role="tagsinput">
                                    </fieldset>

                                    <fieldset class="form-group">
                                        <label for="basicInputFile">Upload logo</label>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="sub_topic_logo" name="sub_topic_logo">
                                            <label class="custom-file-label" for="topic_logo">Choose file</label>
                                        </div>
                                    </fieldset>

                                    <div class="row">
                                        <!--<div class="col-4">
                                            <fieldset class="form-group">
                                                <label for="basicInputFile">Topic metrics to measure</label>
                                                <select name="exp_metrics[]" id="exp_metrics" class="select2 form-control select2-hidden-accessible" multiple="multiple" required="required">
                                                    <option value="sentiment">Sentiment analysis</option>
                                                    <option value="emotions">Emotions analysis</option>
                                                    <option value="csat">CSAT Score</option>
                                                    <option value="potential_loss">Potential revenue loss</option>-->
                                                    <!--<option value="hate">Hate speech</option>
                                                     <option value="polit">Political speech</option>-->
                                                <!--</select>
                                            </fieldset>
                                        </div>-->
                                        <div class="col-4">
                                            <fieldset class="form-group">
                                                <label for="basicInputFile">Select dashboard monitoring type</label>
                                                <select name="exp_type" id="exp_type" class="select2 form-control select2-hidden-accessible" onchange="javascript:show_module('subtopic_roi_add', this.value, '{{ csrf_token() }}')">
                                                    <option value="campaign_monitoring">Campaign monitoring</option>
                                                    <option value="media_monitoring">Media monitoring</option>
                                                    <option value="cx_monitoring">Customer experience monitoring</option>
                                                </select>
                                            </fieldset>
                                        </div>
                                        <div class="col-4" id="subtopic_sources_add">
                                            <fieldset class="form-group">
                                                <label for="basicInputFile">Filter by source</label>
                                                <select name="exp_source[]" id="exp_source" class="select2 form-control select2-hidden-accessible" multiple="multiple">
                                                    <option value="youtube">Videos</option>
                                                    <option value="pinterest">Pinterest</option>
                                                    <option value="facebook">Facebook</option>
                                                    <option value="twitter">Twitter</option>
                                                    <option value="instagram">Instagram</option>
                                                    <option value="reddit">Reddit</option>
                                                    <option value="tumblr">Tumblr</option>
                                                    <option value="linkedin">Linkedin</option>
                                                    <option value="blogs">Blogs</option>
                                                    <option value="news">News Sources</option>
                                                </select>
                                            </fieldset>
                                        </div>
                                    </div>

                                    <div id="roi_one" class="col-12" style="padding: 0px; display: none;"><h4 class="card-title">ROI Settings <small class="text-muted" style="font-size: 12px;">(Applicable if Potential revenue loss is selected from metrics above)</small></h4></div>

                                    <div class="row" id="roi_two" style="display: none;">
                                        <div class="col-3">
                                            <fieldset class="form-group">
                                                <label for="basicInputFile">Currency</label>
                                                <select name="roi_currency" id="roi_currency" class="select2 form-control select2-hidden-accessible">
                                                    <option value="OMR">Omani Rial</option>
                                                    <option value="USD">United States Dollar</option>
                                                </select>
                                            </fieldset>
                                        </div>
                                        <div class="col-3">
                                            <fieldset class="form-group">
                                                <label for="helpInputTop">CLV</label>
                                                <small class="text-muted"><i> (Customer lifetime value)</i></small>
                                                <input type="text" class="form-control" id="roi_avg_revenue" name="roi_avg_revenue">
                                            </fieldset>
                                        </div>
                                        <div class="col-3">
                                            <fieldset class="form-group">
                                                <label for="basicInputFile">Churn rate (%)</label>
                                                <select name="roi_churn_rate" id="roi_churn_rate" class="select2 form-control select2-hidden-accessible">
                                                    <?php
                                                    for($cr=1; $cr<=100; $cr++)
                                                    {
                                                    ?>
                                                    <option value="<?php echo $cr; ?>"><?php echo $cr; ?></option>
                                                    <?php
                                                    }
                                                    ?>
                                                </select>
                                            </fieldset>
                                        </div>
                                    </div>

                                    <fieldset class="form-group">
                                        <label for="basicInputFile">&nbsp;</label>
                                        <div class="custom-file">
                                            <div class="spinner-border" role="status" id="loading_icon_sutopic" style="display:none;">
                                                <span class="sr-only">Loading...</span>
                                            </div><button type="submit" class="btn btn-primary mr-1 mb-1">Create analysis</button>&nbsp;<button type="button" class="btn btn-light mr-1 mb-1" onclick="javascript:$('#add-subtopic-section').height(0);">Cancel</button>
                                            <input type="hidden" name="mode" id="mode" value="create_exp">
                                            <input type="hidden" name="hidden_tid" id="hidden_tid" value="">
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
    {{-- END: add sub topic --}}
    {{-- Edit sub topic --}}
    <?php if($show_edit_subtopic == 'yes') { ?>
    <section id="edit-subtopic-section"><!---->
        <div class="col-12" style="padding: 0px; margin-top: 16px;">
            <div class="col-md-8">
                <div class="card" style="box-shadow: none;">
                    <div class="card-header">
                        <h4 class="card-title">Edit in depth analysis</h4>
                    </div>
                    <div class="card-body">
                        <form name="edit_exp" id="edit_exp" autocomplete="off" enctype="multipart/form-data">
                            <div class="row">
                                <div id="subtopic_edit_error_msg" style="display: none; color: #ff0000;"></div>
                                <div class="col-md-12">
                                    <fieldset class="form-group">
                                        <label for="basicInput">Name</label>
                                        <input type="text" class="form-control" id="exp_name" name="exp_name" value="{{$edit_subtopic_data[0]->exp_name}}" placeholder="Name" required="required">
                                    </fieldset>

                                    <fieldset class="form-group">
                                        <label for="helpInputTop">Keywords / Hashtags</label>
                                        <small class="text-muted">eg.<i>keyword / #hashtag (Press "Enter" to add mulitple options)</i></small>
                                        <input type="text" class="bootstrap-t" id="exp_keywords" name="exp_keywords" data-role="tagsinput" value="{{$edit_subtopic_data[0]->exp_keywords}}" required="required">
                                    </fieldset>

                                    <fieldset class="form-group">
                                        <label for="helpInputTop">Exclude hashtags / keywords</label>
                                        <small class="text-muted"><i> (Press "Enter" to add mulitple options)</i></small>
                                        <input type="text" class="bootstrap-t" id="exp_exclude_keywords" name="exp_exclude_keywords" value="{{$edit_subtopic_data[0]->exp_exclude_keywords}}" data-role="tagsinput">
                                    </fieldset>
                                    
                                    <fieldset class="form-group">
                                        <label for="helpInputTop">Exclude accounts</label>
                                        <small class="text-muted"><i> (Press "Enter" to add mulitple options)</i></small>
                                        <input type="text" class="bootstrap-t" id="exp_exclude_accounts" name="exp_exclude_accounts" value="{{$edit_subtopic_data[0]->exp_exclude_accounts}}" data-role="tagsinput">
                                    </fieldset>
                                    
                                    <?php
                                        if (file_exists(public_path() . '/images/subtopic_logos/' . $edit_subtopic_data[0]->exp_logo) && is_file(public_path() . '/images/subtopic_logos/' . $edit_subtopic_data[0]->exp_logo)) {
                                        ?>
                                        <fieldset class="form-group">
                                            <label for="basicInputFile">Topic logo</label>
                                            <div class="custom-file" style="height: auto;">
                                                <img src="{{asset('images/subtopic_logos/')}}<?php echo '/' . $edit_subtopic_data[0]->exp_logo; ?>" style="width: 200px;" />
                                                <input type="hidden" name="old_subtopic_logo" id="old_subtopic_logo" value="<?php echo $edit_subtopic_data[0]->exp_logo; ?>">
                                            </div>
                                        </fieldset>
                                    <?php } ?>
                                    <fieldset class="form-group">
                                        <label for="basicInputFile">Upload logo</label>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="sub_topic_logo" name="sub_topic_logo">
                                            <label class="custom-file-label" for="topic_logo">Choose file</label>
                                        </div>
                                    </fieldset>
                                    <?php
                                    //$exp_metrics = explode(",", $edit_subtopic_data[0]->exp_metrics);
                                    ?>
                                    <div class="row">
                                        <!--<div class="col-4">
                                            <fieldset class="form-group">
                                                <label for="basicInputFile">Topic metrics to measure</label>
                                                <select name="exp_metrics[]" id="exp_metrics" class="select2 form-control select2-hidden-accessible" multiple="multiple" required="required">
                                                    <option value="sentiment"<?php //if (in_array('sentiment', $exp_metrics)) echo 'selected'; ?>>Sentiment analysis</option>
                                                    <option value="emotions"<?php //if (in_array('emotions', $exp_metrics)) echo 'selected'; ?>>Emotions analysis</option>
                                                    <option value="csat"<?php //if (in_array('csat', $exp_metrics)) echo 'selected'; ?>>CSAT Score</option>
                                                    <option value="potential_loss"<?php //if (in_array('potential_loss', $exp_metrics)) echo 'selected'; ?>>Potential revenue loss</option>-->
                                                    <!--<option value="hate">Hate speech</option>
                                                     <option value="polit">Political speech</option> -->
                                                <!--</select>
                                            </fieldset>
                                        </div>-->
                                        <div class="col-4">
                                            <fieldset class="form-group">
                                                <label for="basicInputFile">Select dashboard monitoring type</label>
                                                <select name="exp_type" id="exp_type" class="select2 form-control select2-hidden-accessible" onchange="javascript:show_module('subtopic_roi_edit', this.value, '{{ csrf_token() }}')">
                                                    <option value="campaign_monitoring"<?php if($edit_subtopic_data[0]->exp_type == 'campaign_monitoring') echo ' selected="selected"'; ?>>Campaign monitoring</option>
                                                    <option value="media_monitoring"<?php if($edit_subtopic_data[0]->exp_type == 'media_monitoring') echo ' selected="selected"'; ?>>Media monitoring</option>
                                                    <option value="cx_monitoring"<?php if($edit_subtopic_data[0]->exp_type == 'cx_monitoring') echo ' selected="selected"'; ?>>Customer experience monitoring</option>
                                                </select>
                                            </fieldset>
                                        </div>
                                        <?php
                                        $src_array = explode(",", $edit_subtopic_data[0]->exp_source);
                                        ?>
                                        <div class="col-4"<?php if($edit_subtopic_data[0]->exp_type == 'media_monitoring') echo ' style="display:none;"'; ?> id="subtopic_sources_edit">
                                            <fieldset class="form-group">
                                                <label for="basicInputFile">Filter by source</label>
                                                <select name="exp_source[]" id="exp_source" class="select2 form-control select2-hidden-accessible" multiple="multiple">
                                                    <option value="youtube"<?php if (in_array('youtube', $src_array)) echo 'selected'; ?>>Videos</option>
                                                    <option value="pinterest"<?php if (in_array('pinterest', $src_array)) echo 'selected'; ?>>Pinterest</option>
                                                    <option value="facebook"<?php if (in_array('facebook', $src_array)) echo 'selected'; ?>>Facebook</option>
                                                    <option value="twitter"<?php if (in_array('twitter', $src_array)) echo 'selected'; ?>>Twitter</option>
                                                    <option value="linkedin"<?php if (in_array('linkedin', $src_array)) echo 'selected'; ?>>Linkedin</option>
                                                    <option value="instagram"<?php if (in_array('instagram', $src_array)) echo 'selected'; ?>>Instagram</option>
                                                    <option value="reddit"<?php if (in_array('reddit', $src_array)) echo 'selected'; ?>>Reddit</option>
                                                    <option value="tumblr"<?php if (in_array('tumblr', $src_array)) echo 'selected'; ?>>Tumblr</option>
                                                    <option value="blogs"<?php if (in_array('blogs', $src_array)) echo 'selected'; ?>>Blogs</option>
                                                    <option value="news"<?php if (in_array('news', $src_array)) echo 'selected'; ?>>News Sources</option>
                                                </select>
                                            </fieldset>
                                        </div>
                                    </div>
                                    <?php
                                    $roi_settings = $subtopic_obj->get_roi_subtopic_data($edit_subtopic_data[0]->exp_id);
                                    
                                    if($roi_settings == 'NA')
                                    {
                                        $roi_currency = '';
                                        $roi_clv = '';
                                        $roi_churn = '';
                                    }
                                    else
                                    {
                                        $roi_currency = $roi_settings[0]->roi_currency;
                                        $roi_clv = $roi_settings[0]->roi_avg_revenue;
                                        $roi_churn = $roi_settings[0]->roi_churn_rate;
                                    }
                                    ?>
                                    <div id="roi_three" class="col-12" style="padding: 0px;<?php if($edit_subtopic_data[0]->exp_type == 'cx_monitoring') echo ' display:block;'; else echo ' display:none;'; ?>"><h4 class="card-title">ROI Settings <small class="text-muted" style="font-size: 12px;">(Applicable if Potential revenue loss is selected from metrics above)</small></h4></div>
                                    
                                    <div class="row" id="roi_four" style="<?php if($edit_subtopic_data[0]->exp_type != 'cx_monitoring') echo ' display:none;'; ?>">
                                        <div class="col-3">
                                            <fieldset class="form-group">
                                                <label for="basicInputFile">Currency</label>
                                                <select name="roi_currency" id="roi_currency" class="select2 form-control select2-hidden-accessible">
                                                    <option value="OMR"<?php if($roi_currency == 'OMR') echo ' selected="selected"'; ?>>Omani Rial</option>
                                                    <option value="USD"<?php if($roi_currency == 'USD') echo ' selected="selected"'; ?>>United States Dollar</option>
                                                </select>
                                            </fieldset>
                                        </div>
                                        <div class="col-3">
                                            <fieldset class="form-group">
                                                <label for="helpInputTop">CLV</label>
                                                <small class="text-muted"><i> (Customer lifetime value)</i></small>
                                                <input type="text" class="form-control" id="roi_avg_revenue" name="roi_avg_revenue" value="{{$roi_clv}}">
                                            </fieldset>
                                        </div>
                                        <div class="col-3">
                                            <fieldset class="form-group">
                                                <label for="basicInputFile">Churn rate (%)</label>
                                                <select name="roi_churn_rate" id="roi_churn_rate" class="select2 form-control select2-hidden-accessible">
                                                    <?php
                                                    for($cr=1; $cr<=100; $cr++)
                                                    {
                                                    ?>
                                                    <option value="<?php echo $cr; ?>"<?php if($cr == $roi_churn) echo ' selected="selected"'; ?>><?php echo $cr; ?></option>
                                                    <?php
                                                    }
                                                    ?>
                                                </select>
                                            </fieldset>
                                        </div>
                                    </div>
                                    
                                    <fieldset class="form-group">
                                        <label for="basicInputFile">&nbsp;</label>
                                        <div class="custom-file">
                                            <div class="spinner-border" role="status" id="loading_icon_editsutopic" style="display:none;">
                                                <span class="sr-only">Loading...</span>
                                            </div><button type="submit" class="btn btn-primary mr-1 mb-1">Update analysis</button>&nbsp;<button type="button" class="btn btn-light mr-1 mb-1" onclick="javascript:window.location = 'topic-settings';">Cancel</button>
                                            <input type="hidden" name="mode" id="mode" value="edit_exp">
                                            <input type="hidden" name="tid" id="tid" value="<?php echo $edit_subtopic_data[0]->exp_topic_id; ?>">
                                            <input type="hidden" name="stid" id="stid" value="{{$_GET["stid"]}}">
                                            <input type="hidden" name="old_subtopic_logo" id="old_subtopic_logo" value="{{$edit_subtopic_data[0]->exp_logo}}">
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
    {{-- END: edit sub topic --}}
    <?php
    }
    ?>
    {{-- Add touchpoint --}}
    <div class="modal fade text-left w-100" id="add-touchpoint-section" tabindex="-1" aria-labelledby="add_topic_popup_title" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-centered modal-dialog-scrollable modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="add_touchpoint_popup_title">Add Touchpoint</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="bx bx-x"></i>
                    </button>
                </div>
                <div class="modal-body">
                        <form name="create_tp" id="create_tp" autocomplete="off" enctype="multipart/form-data">
                            <div class="row">
                                <div id="error_msg" style="display: none; color: #ff0000;"></div>
                                <div class="col-md-12">
                                    <fieldset class="form-group">
                                        <label for="tp_name">Name</label>
                                        <input type="text" class="form-control" id="tp_name" name="tp_name" placeholder="Name" required="required">
                                    </fieldset>

                                    <fieldset class="form-group">
                                        <label for="tp_keywords">Keywords / Hashtags</label>
                                        <small class="text-muted">eg.<i>keyword / #hashtag (Press "Enter" to add mulitple options)</i></small>
                                        <input type="text" class="bootstrap-t" id="tp_keywords" name="tp_keywords" data-role="tagsinput" required="required">
                                    </fieldset>

                                    <div class="row">
                                        <div class="col-4">
                                            <fieldset class="form-group">
                                                <label for="sub_topic">Select in depth analysis</label>
                                                <select name="sub_topic" id="sub_topic" class="select2 form-control select2-hidden-accessible" required="required">
                                                    <option value="">-- Choose --</option>
                                                </select>
                                            </fieldset>
                                        </div>                                        
                                    </div>
                                    <fieldset class="form-group">
                                        <label for="basicInputFile">&nbsp;</label>
                                        <div class="custom-file">
                                            <div class="spinner-border" role="status" id="loading_icon_sutopic" style="display:none;">
                                                <span class="sr-only">Loading...</span>
                                            </div><button type="submit" class="btn btn-primary mr-1 mb-1">Create touchpoint</button>&nbsp;<button type="button" class="btn btn-light mr-1 mb-1" onclick="javascript:$('#add-touchpoint-section').hide(500);">Cancel</button>
                                            <input type="hidden" name="mode" id="mode" value="create_tp">
                                            <input type="hidden" name="hidden_tpid" id="hidden_tpid" value="">
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
    {{-- END: add touch point --}}
    {{-- Edit touchpoint --}}
    <?php if($show_edit_tp == 'yes') { ?>    
    <section id="add-touchpoint-section">
        <div class="col-12" style="padding: 0px; margin-top: 16px;">
            <div class="col-md-8">
                <div class="card" style="box-shadow: none;">
                    <div class="card-header">
                        <h4 class="card-title">Edit touchpoint</h4>
                    </div>
                    <div class="card-body">
                        <form name="edit_tp" id="edit_tp" autocomplete="off" enctype="multipart/form-data">
                            <div class="row">
                                <div id="error_msg" style="display: none; color: #ff0000;"></div>
                                <div class="col-md-12">
                                    <fieldset class="form-group">
                                        <label for="tp_name">Name</label>
                                        <input type="text" class="form-control" id="tp_name" name="tp_name" placeholder="Name" required="required" value="{{$touchpoint_data[0]->tp_name}}">
                                    </fieldset>

                                    <fieldset class="form-group">
                                        <label for="tp_keywords">Keywords / Hashtags</label>
                                        <small class="text-muted">eg.<i>keyword / #hashtag (Press "Enter" to add mulitple options)</i></small>
                                        <input type="text" class="bootstrap-t" id="tp_keywords" name="tp_keywords" data-role="tagsinput" required="required" value="{{$touchpoint_data[0]->tp_keywords}}">
                                    </fieldset>

                                    <div class="row">
                                        <div class="col-4">
                                            <fieldset class="form-group">
                                                <label for="sub_topic">Select in depth analysis</label>
                                                <?php
                                                $main_topic_id = Crypt::decrypt($_GET["tp_tid"]);
                                                $subtopics_list = $topic_obj->get_sub_topics($main_topic_id);
                                                ?>
                                                <select name="sub_topic" id="sub_topic" class="select2 form-control select2-hidden-accessible" required="required">
                                                    <?php
                                                    for($i=0; $i<count($subtopics_list); $i++)
                                                    {
                                                        if($subtopics_list[$i]->exp_id == $touchpoint_data[0]->tp_cx_id)
                                                            echo '<option value="'.$subtopics_list[$i]->exp_id.'" selected="selected">'.$subtopics_list[$i]->exp_name.'</option>';
                                                        else
                                                            echo '<option value="'.$subtopics_list[$i]->exp_id.'">'.$subtopics_list[$i]->exp_name.'</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </fieldset>
                                        </div>                                        
                                    </div>
                                    <fieldset class="form-group">
                                        <label for="basicInputFile">&nbsp;</label>
                                        <div class="custom-file">
                                            <div class="spinner-border" role="status" id="loading_icon_edittp" style="display:none;">
                                                <span class="sr-only">Loading...</span>
                                            </div><button type="submit" class="btn btn-primary mr-1 mb-1">Update touchpoint</button>&nbsp;<button type="button" class="btn btn-light mr-1 mb-1" onclick="javascript:window.location = 'topic-settings';">Cancel</button>
                                            <input type="hidden" name="mode" id="mode" value="edit_tp">
                                            <input type="hidden" name="tpid" id="tpid" value="{{Crypt::encrypt($touchpoint_data[0]->tp_id)}}">
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
    <?php } ?>
    {{-- END: add touch point --}}
    <!-- Topics list -->
    <section id="topics-list">
        <div class="col-12"><h3>Dashboards list</h3></div>
        <div class="row" style="margin: 0px;">
        <?php
        for($i=0; $i<count($topics_data); $i++)
        {      
        ?>
        <div class="col-sm-12 dashboard-greetings" style="padding: 0px;">
            <!-- main topic -->
            <div class="col-sm-4" style="float: left;">
                <div class="card">
                    <div class="card-header bg-light" style="margin-bottom: 0px;"><!--background: #8ac4d5; -->
                        <h4 class="greeting-text" style="color:#ffffff;">{{$topics_data[$i]->topic_title}}</h4>
                    </div>
                    <div class="card-body pt-0">
                        <div class="d-flex justify-content-between align-items-end">
                            <div class="dashboard-content-left">
                                <h1 class="text-primary font-large-2 text-bold-500">
                                <?php
                                //echo '<pre>'; print_r($_SERVER);
                                $topic_results = $topic_obj->get_topic_total_es_results($topics_data[$i]->topic_id);
                                $t_results = explode("|", $topic_results);
                                echo $t_results[0];

                                $tid = $topics_data[$i]->topic_id;
                                $get_tid = Crypt::encrypt($tid);
                                ?>                    
                                </h1>
                                <p>Mentions found.<br>Created: <?php echo date("jS M, y h:i A", strtotime($topics_data[$i]->topic_created_at)); ?></p>
                                <p><input type="checkbox" name="noti_email_chkbx_<?php echo $tid; ?>" id="noti_email_chkbx_<?php echo $tid; ?>" data-toggle="popover" data-content="Add or remove dashboard from daily notifications. Manage your settings under My Account -> Notification settings." data-trigger="hover" data-placement="top" data-original-title="" title="" onchange="javascript:set_email_notify('{{$tid}}', '{{ csrf_token() }}');"<?php if($topics_data[$i]->topic_email_notify == 'yes') echo ' checked'; ?>> Email notify </p>
                                <p><input type="checkbox" name="report_email_chkbx_<?php echo $tid; ?>" id="report_email_chkbx_<?php echo $tid; ?>" data-toggle="popover" data-content="Check this box if you want an automated report sent to you on monthly basis" data-trigger="hover" data-placement="top" data-original-title="" title="" onchange="javascript:set_monthly_report('{{$tid}}', '{{ csrf_token() }}');"<?php if($topics_data[$i]->topic_send_monthly_report == 'yes') echo ' checked'; ?>> Enable monthly report</p>
                                <button type="button" class="btn btn-icon btn-success mr-1 mb-1" data-toggle="popover" data-content="Load dashboard" data-trigger="hover" data-placement="top" data-original-title="" title="" onclick="javascript:load_topic('{{$tid}}', '{{ csrf_token() }}', 'maintopic');"><i class="bx bxs-right-arrow-square"></i></button>
                                <button type="button" class="btn btn-icon btn-warning mr-1 mb-1" data-toggle="popover" data-content="Edit dashboard" data-trigger="hover" data-placement="top" data-original-title="" title="" onclick="javascript:window.location='topic-settings?tid={{$get_tid}}';"><i class="bx bxs-edit-alt"></i></button>
                                <button type="button" class="btn btn-icon btn-danger mr-1 mb-1" data-toggle="modal" data-content="Delete dashboard" data-trigger="hover" data-placement="top" data-original-title="" title="" data-target="#delete_modal" onclick="javascript:set_del_data('{{$tid}}', '{{ csrf_token() }}', 'maintopic');"><i class="bx bx-minus-circle"></i></button>
                            </div>
                            <div class="dashboard-content-right">
                            <?php
                                //echo substr(__DIR__, strlen($_SERVER['DOCUMENT_ROOT']));
                                $topic_image = '';
                                if (file_exists($_SERVER["DOCUMENT_ROOT"] . '/images/topic_logos/' . $topics_data[$i]->topic_logo) && is_file($_SERVER["DOCUMENT_ROOT"] . '/images/topic_logos/' . $topics_data[$i]->topic_logo))
                                    $topic_image = $topics_data[$i]->topic_logo;
                                else
                                    $topic_image = 'blue.png';
                            ?>
                                <img src="{{asset('images/topic_logos/')}}<?php echo '/' . $topic_image; ?>" style="border-radius: 100px;" height="200" width="200" alt="Dashboard Ecommerce"><!--  class="img-fluid" -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- sub topic -->
            <div class="col-sm-4" style="float: left;">
                <div class="card" style="height: 349px; box-shadow: none;">
                    <div class="card-header">
                        <h4 class="greeting-text">In depth analysis</h4>
                        <span style="float: right;"><a href="javascript:void(0);" onclick="javascript:show_module('subtopic','{{$get_tid}}', '{{csrf_token()}}');"><i class="bx bxs-plus-square"></i></a></span>
                    </div>
                    <div class="card-body pt-0 hide-native-scrollbar" style="overflow-y: auto; margin-bottom: 10px;">
                        <div class="justify-content-between align-items-end" style="">
                            <?php
                            $sub_topic_data = $topic_obj->get_sub_topics($tid);
                            if(count($sub_topic_data) > 0)
                            {
                                for($k=0; $k<count($sub_topic_data); $k++)
                                {
                                    $get_stid = Crypt::encrypt($sub_topic_data[$k]->exp_id);
                            ?>
                            <div class="row bg-rgba-secondary" style="border-radius: 5px; padding: 5px 0px 5px 0px; margin: 0px 0px 10px 0px; width: 100%;">
                                <div class="col-sm-9"><a href="javascript:void(0);" onclick="javascript:load_topic('{{$sub_topic_data[$k]->exp_id}}', '{{csrf_token()}}', 'subtopic');" class="secondary"><?php echo $sub_topic_data[$k]->exp_name; ?></a></div>
                                <div class="col-sm-3" style="text-align: center;"><a href="javascript:void(0);" onclick="javascript:window.location='topic-settings?stid={{$get_stid}}';"><i class="bx bxs-edit-alt warning"></i></a>&nbsp;<a href="javascript:void(0);" data-toggle="modal" data-content="Delete analysis" data-trigger="hover" data-placement="top" data-original-title="" title="" data-target="#delete_modal" onclick="javascript:set_del_data('{{$sub_topic_data[$k]->exp_id}}', '{{ csrf_token() }}', 'subtopic');"><i class="bx bxs-trash danger"></i></a></div>
                            </div>
                            <?php
                                }
                            }
                            else
                                echo 'No record found.';
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            {{--Touchpoints--}}
            <div class="col-sm-4" style="float: left;">
                <div class="card" style="height: 349px; box-shadow: none;">
                    <div class="card-header">
                        <h4 class="greeting-text">Touchpoints</h4>
                        <span style="float: right;"><a href="javascript:void(0);" onclick="javascript:show_module('touchpoint','{{$get_tid}}', '{{csrf_token()}}');"><i class="bx bxs-plus-square"></i></a></span>
                    </div>
                    <div class="card-body pt-0 hide-native-scrollbar" style="overflow-y: auto; margin-bottom: 10px;">
                        <div class="justify-content-between align-items-end" style="">
                            <?php
                            $touch_points_data = $topic_obj->get_all_touchpoints($tid);
                            //echo $touch_points_data;
                            if(count($touch_points_data) > 0)
                            {
                                for($k=0; $k<count($touch_points_data); $k++)
                                {
                                    $touchpoint_name = $topic_obj->get_touchpoint_data($touch_points_data[$k]);
                                    $get_tpid = Crypt::encrypt($touch_points_data[$k]);
                            ?>
                            <div class="row bg-rgba-secondary" style="border-radius: 5px; padding: 5px 0px 5px 0px; margin: 0px 0px 10px 0px; width: 100%;">
                                <div class="col-sm-9"><?php echo $touchpoint_name[0]->tp_name; ?></div>
                                <div class="col-sm-3" style="text-align: center;"><a href="javascript:void(0);" onclick="javascript:window.location='topic-settings?tpid={{$get_tpid}}&tp_tid={{$get_tid}}';"><i class="bx bxs-edit-alt warning"></i></a>&nbsp;<a href="javascript:void(0);" data-toggle="modal" data-content="Delete experience" data-trigger="hover" data-placement="top" data-original-title="" title="" data-target="#delete_modal" onclick="javascript:set_del_data('{{$touch_points_data[$k]}}', '{{ csrf_token() }}', 'touchpoint');"><i class="bx bxs-trash danger"></i></a></div>
                                <!-- <a href="javascript:void(0);"
   onclick="javascript:load_edit_touchpoints('{{$get_tpid}}','{{$get_tid}}','<?php //echo csrf_token(); ?>');"><i class="bx bxs-edit-alt warning"></i></a> -->
                            </div>
                            <?php
                                }
                            }
                            else
                                echo 'No record found.';
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--<div class="col-sm-12 bg-light bg-lighten-1" style="height: 2px; margin: 0px 0px 28px 0px;"></div>-->  
        
    <?php
    }
    //Topics loop finish
    ?>
    

    </div>
    {{-- Modal popup for edit touchpoint --}}
    <div class="modal fade text-left w-100" id="edit-touchpoint-section" tabindex="-1" aria-labelledby="add_topic_popup_title" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-centered modal-dialog-scrollable modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="edit_touchpoint_popup_title">Edit Touchpoint</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="bx bx-x"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form name="edit_tp" id="edit_tp" autocomplete="off" enctype="multipart/form-data">
                        <div class="row">
                            <div id="error_msg" style="display: none; color: #ff0000;"></div>
                            <div class="col-md-12">
                                <fieldset class="form-group">
                                    <label for="tp_name">Name</label>
                                    <input type="text" class="form-control" id="tp_edit_name" name="tp_name" placeholder="Name" required="required" value="">
                                </fieldset>

                                <fieldset class="form-group">
                                    <label for="tp_keywords">Keywords / Hashtags</label>
                                    <small class="text-muted">eg.<i>keyword / #hashtag (Press "Enter" to add mulitple options)</i></small>
                                    <input type="text" class="bootstrap-t" id="tp_edit_keywords" name="tp_keywords" data-role="tagsinput" required="required" value="">
                                </fieldset>

                                <div class="row">
                                    <div class="col-4">
                                        <fieldset class="form-group">
                                            <label for="sub_topic">Select in depth analysis</label>
                                            {{--<?php--}}
                                            {{--$main_topic_id = Crypt::decrypt($_GET["tp_tid"]);--}}
                                            {{--$subtopics_list = $topic_obj->get_sub_topics($main_topic_id);--}}
                                            {{--?>--}}
                                            {{--<select name="sub_topic" id="sub_topic" class="select2 form-control select2-hidden-accessible" required="required">--}}
                                            {{--<?php--}}
                                            {{--for($i=0; $i<count($subtopics_list); $i++)--}}
                                            {{--{--}}
                                            {{--if($subtopics_list[$i]->exp_id == $touchpoint_data[0]->tp_cx_id)--}}
                                            {{--echo '<option value="'.$subtopics_list[$i]->exp_id.'" selected="selected">'.$subtopics_list[$i]->exp_name.'</option>';--}}
                                            {{--else--}}
                                            {{--echo '<option value="'.$subtopics_list[$i]->exp_id.'">'.$subtopics_list[$i]->exp_name.'</option>';--}}
                                            {{--}--}}
                                            {{--?>--}}
                                            {{--</select>--}}
                                        </fieldset>
                                    </div>
                                </div>
                                <fieldset class="form-group">
                                    <label for="basicInputFile">&nbsp;</label>
                                    <div class="custom-file">
                                        <div class="spinner-border" role="status" id="loading_icon_edittp" style="display:none;">
                                            <span class="sr-only">Loading...</span>
                                        </div><button type="submit" class="btn btn-primary mr-1 mb-1">Update touchpoint</button>&nbsp;<button type="button" class="btn btn-light mr-1 mb-1" onclick="javascript:window.location = 'topic-settings';">Cancel</button>
                                        <input type="hidden" name="mode" id="mode" value="edit_tp">
                                        {{--<input type="hidden" name="tpid" id="tpid" value="{{Crypt::encrypt($touchpoint_data[0]->tp_id)}}">--}}
                                        <input type="hidden" name="tpid" id="tpid" value="">
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
  <script src="{{asset('js/scripts/tagsinput.js')}}"></script>
  <script src="{{asset('js/scripts/popover/popover.js')}}"></script>
  <script src="{{asset('vendors/js/forms/select/select2.full.min.js')}}"></script>
  <script src="{{asset('js/scripts/forms/select/form-select2.js')}}"></script>
  
  @endsection
  