<?php $topic_khu = ''; ?>
<div class="row" style="display: none;">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php if (stristr($_SERVER["REQUEST_URI"], '/topic-dashboard') !== FALSE) { ?>
                <div class="row mb-1">
                    <div class="col-md-2 col-sm-2">
                        <label for="operator">Select operator</label><br>
                        <div class="btn-group btn-group-toggle btn-group-sm" role="group" data-toggle="buttons" >
                            <label class="btn btn-outline-primary active" aria-label="Size Small">
                                <input type="radio" name="op_option" value="OR" id="oropr" autocomplete="off" checked> OR
                            </label>
                            <label class="btn btn-outline-primary" aria-label="Size Small">
                                <input type="radio" name="op_option" value="AND" id="andopr" autocomplete="off"> AND
                            </label>
                        </div>
                    </div>
                    <div class="col-md-10 col-sm-10">
                        <label for="hashtags">Select / un-select keywords or hashtags</label>
                        <div class="row">
                            <div class="col-12">
                                <?php
                                $topic_id = Helper::get_loaded_topic_id();
                                $topic_khu = Helper::get_topic_khu($topic_id);
                                $khu = explode(",", $topic_khu);
                                for ($i = 0; $i < count($khu); $i ++)
                                {
                                    echo '<div style="float: left; background: #5A8DEE; border-radius: 5px; color: #fff; padding: 3px 5px 3px 5px; margin: 0px 5px 5px 0px; cursor: pointer;" id="tag' . $i . '" onclick="javascript:set_custom_selection(\'' . $i . '\');">' . str_replace("'", "", $khu[$i]) . '</div>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>
                <div class="row mb-1">
                    <div class="col-md-2 col-sm-2">
                        <div class="row">
                        <div class="col-12">
                            <div class="mb-1">
                                <label for="sentimenttype">Sentiment Type</label>
                                <select class="select2 form-control sentiment_type_placeholder1" multiple="multiple" name="post_senti[]" id="post_senti">
                                    <option value="positive">Positive</option>
                                    <option value="negative">Negative</option>
                                    <option value="neutral">Neutral</option>
                                </select>
                            </div>
                        </div>
                        </div>
                    </div>

                    <div class="col-md-2 col-sm-2">
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-0">
                                    <label for="datasource">Select Data Source</label>
                                    <select class="select2 form-control data_source_placeholder1" multiple="multiple" name="data_source[]" id="data_source">
                                        <option value="youtube">Videos</option>
                                        <option value="pinterest">Pinterest</option>
                                        <option value="twitter">Twitter</option>
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
                    <div class="col-md-2 col-sm-2">
                        <div class="form-group">
                            <label for="filterbylocation">Filter By Location</label>
                            <select class="select2 form-control filter_by_location_placeholder1" multiple="multiple" name="data_location[]" id="data_location">
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
                    <div class="col-md-2 col-sm-2">
                        <div class="form-group">
                            <label for="filterbytopic">Filter By Language</label>
                            <select class="select2 form-control filter_by_language_placeholder1" multiple="multiple" name="data_lang[]" id="data_lang">
                                <option value="en">English</option>
                                <option value="ar">Arabic</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2 col-sm-2" style="width: 160px; max-width: 160px;">
                        <label for="fromdate">From Date</label>
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-1">
                                    <fieldset class="form-group position-relative has-icon-left">
                                        <input type="text" class="form-control pickadate" name="from_date" id="from_date" placeholder="Select Date">
                                        <div class="form-control-position">
                                            <i class='bx bx-calendar'></i>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-2" style="width: 160px; max-width: 160px;">
                        <label for="todate">To Date</label>
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-1">
                                    <fieldset class="form-group position-relative has-icon-left">
                                        <input type="text" class="form-control pickadate" name="to_date" id="to_date" placeholder="Select Date">
                                        <div class="form-control-position">
                                            <i class='bx bx-calendar'></i>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="" style="padding-top: 27px; width: 130px;">
                        <?php if (stristr($_SERVER["REQUEST_URI"], '/topic-dashboard') !== FALSE) { ?>
                        <button type="button" class="btn mr-1 btn-light btn-sm" onclick="javascript:filter_dashboard_results('dashboard', '{{ csrf_token() }}');">Apply filters</button>
                        <?php } else if(stristr($_SERVER["REQUEST_URI"], '/subtopic-dashboard') !== FALSE) { ?>
                        <button type="button" class="btn mr-1 btn-light btn-sm" onclick="javascript:filter_dashboard_results('dashboard_subtopic', '{{ csrf_token() }}');">Apply filters</button>
                        <?php } else if(stristr($_SERVER["REQUEST_URI"], '/dashboard-competitor-analysis') !== FALSE) { ?>
                        <button type="button" class="btn mr-1 btn-light btn-sm" onclick="javascript:filter_ca_results('ca_analysis', '{{ csrf_token() }}', '<?php echo \Session::get('_loaded_ca_id'); ?>');">Apply filters</button>
                        
                        <?php } ?>
                        <input type="hidden" name="dash_filters_applied" id="dash_filters_applied" value="no">
                        <input type="hidden" name="selected_hash_key" id="selected_hash_key" value="<?php echo $topic_khu; ?>">
                        <div class="spinner-border" role="status" id="filters_loading" style="display:none; position: relative; z-index: 1000; margin-top: 15px;"><span class="sr-only">Loading...</span></div>
                    </div>
                </div>
                
            </div>



        </div>
    </div>
</div>

<script type="text/javascript"></script>
<script>
    setTimeout(function()
    {
    loadplaceholderfroselect1();
    },4000);
    function loadplaceholderfroselect1(){
        $(".sentiment_type_placeholder1").select2({
            placeholder: "Select Sentiment Type",
            allowClear: true
        });
        $(".data_source_placeholder1").select2({
            placeholder: "Select Data Source",
            allowClear: true
        });
        $(".filter_by_location_placeholder1").select2({
            placeholder: "Select Location",
            allowClear: true
        });
        $(".filter_by_language_placeholder1").select2({
            placeholder: "Select Language",
            allowClear: true
        });
    }
</script>