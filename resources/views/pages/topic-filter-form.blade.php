<?php $topic_khu = ''; ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body" style="padding-bottom: 0px;">
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
                        <!--<label for="hashtags">Select / un-select keywords or hashtags <span style="text-transform:none; color:silver; font-weight: normal;">(Un-selecting all will by default include all keywords / hashtags of the topic)</span></label>-->
                        <!--<div class="row">
                            <div class="col-12">
                                <?php
                                /*$topic_id = Helper::get_loaded_topic_id();
                                $topic_khu = Helper::get_topic_khu($topic_id);
                                $khu = explode(",", $topic_khu);
                                for ($i = 0; $i < count($khu); $i ++)
                                {
                                    echo '<div style="float: left; background: #5A8DEE; border-radius: 5px; color: #fff; padding: 3px 5px 3px 5px; margin: 0px 5px 5px 0px; cursor: pointer;" id="tag' . $i . '" onclick="javascript:set_custom_selection(\'' . $i . '\');">' . str_replace("'", "", $khu[$i]) . '</div>';
                                }*/
                                ?>
                            </div>
                        </div>-->
                        <div style="" onmouseover="javascript:this.style.cursor='pointer';">
                            <div class="accordion collapse-icon accordion-icon-rotate" id="accordionWrapa2" onmouseover="javascript:this.style.cursor='pointer';">
                                <div class="card collapse-header open" style="box-shadow: none !important;" onmouseover="javascript:this.style.cursor='pointer';">
                                    <div id="heading5" class="card-header" data-toggle="collapse" data-target="#accordion5" aria-expanded="true"
                                         aria-controls="accordion5" role="tablist" style="border:0px !important; padding:0px;" onmouseover="javascript:this.style.cursor='pointer';">
                                      <span class="collapse-title" onmouseover="javascript:this.style.cursor='pointer';">
                                          <span class="align-middle" onmouseover="javascript:this.style.cursor='pointer';"><label>Select / un-select keywords or hashtags <span style="text-transform:none; color:silver; font-weight: normal;">(Un-selecting all will by default include all keywords / hashtags of the topic)</span></label></span>
                                      </span>
                                    </div>
                                    <div id="accordion5" role="tabpanel" data-parent="#accordionWrapa2" aria-labelledby="heading5" class="collapse show">
                                        <div class="card-body" style="padding:0px;">
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
                        </div>
                    </div>
                </div>
                <?php } ?>
                <div class="row" style="padding-left:15px;">
                    <div style="width: 13%; float:left;">
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

                    <div style="width:13%; float:left; padding-left: 10px;">
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-0">
                                    <label for="datasource">Data Source</label>
                                    <select class="select2 form-control data_source_placeholder" multiple="multiple" name="data_source[]" id="data_source">
                                        <option value="youtube">YouTube</option>
                                        <option value="pinterest">Pinterest</option>
                                        <option value="twitter">Twitter</option>
                                        <option value="facebook">Facebook</option>
                                        <option value="linkedin">Linkedin</option>
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
                    
                    <div style="width:13%; float:left; padding-left: 10px;">
                        <div class="form-group">
                            <label for="filterbylocation">Location</label>
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
                    
                    <div style="width:13%; float:left; padding-left: 10px;">
                        <div class="form-group">
                            <label for="filterbytopic">Language</label>
                            <select class="select2 form-control filter_by_language_placeholder" multiple="multiple" name="data_lang[]" id="data_lang">
                                <option value="en">English</option>
                                <option value="ar">Arabic</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="width:13%; float:left; padding-left: 10px; padding-right: 10px;">
                        <div class="form-group">
                            <label for="filterbytopic">Time slot</label>
                            <select class="select2 form-control filter_by_timeslot_placeholder" data-live-search="false" id="time_slot" onchange="javascript:check_time_slot(this.value);">
                                <option value="custom">Custom dates</option>
                                <option value="today">Today</option>
                                <option value="24h">Last 24 hours</option>
                                <option value="7">Last 7 days</option>
                                <option value="30">Last 30 days</option>
                                <option value="60">Last 60 days</option>
                                <option value="90">Last 90 days</option>
                                <option value="120">Last 120 days</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2 col-sm-2" style="width: 160px; max-width: 160px;" id="date_from">
                        <label for="fromdate">From Date</label>
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-1">
                                    <fieldset class="form-group position-relative has-icon-left">
                                        <input type="text" class="form-control pickadate-months-year" name="from_date" id="from_date" placeholder="Select Date">
                                        <div class="form-control-position">
                                            <i class='bx bx-calendar'></i>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-2" style="width: 160px; max-width: 160px;" id="date_to">
                        <label for="todate">To Date</label>
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-1">
                                    <fieldset class="form-group position-relative has-icon-left">
                                        <input type="text" class="form-control pickadate-months-year" name="to_date" id="to_date" placeholder="Select Date">
                                        <div class="form-control-position">
                                            <i class='bx bx-calendar'></i>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="" style="padding-top: 27px; width: 155px;">
                        <?php if (stristr($_SERVER["REQUEST_URI"], '/topic-dashboard') !== FALSE) { ?>
                        <button type="button" class="btn mr-1 btn-light btn-sm" onclick="javascript:filter_dashboard_results('dashboard', '{{ csrf_token() }}');">Apply filters</button>
                        <?php } else if(stristr($_SERVER["REQUEST_URI"], '/subtopic-dashboard') !== FALSE) { ?>
                        <button type="button" class="btn mr-1 btn-light btn-sm" onclick="javascript:filter_dashboard_results('dashboard_subtopic', '{{ csrf_token() }}');">Apply filters</button>
                        <?php } else if(stristr($_SERVER["REQUEST_URI"], '/dashboard-competitor-analysis') !== FALSE) { ?>
                        <button type="button" class="btn mr-1 btn-light btn-sm" onclick="javascript:filter_ca_results('ca_analysis', '{{ csrf_token() }}', '<?php echo \Session::get('_loaded_ca_id'); ?>');">Apply filters</button>
                        
                        <?php } ?>
                        <input type="hidden" name="dash_filters_applied" id="dash_filters_applied" value="no">
                        <input type="hidden" name="selected_hash_key" id="selected_hash_key" value="<?php echo $topic_khu; ?>">
                        <div class="spinner-border" role="status" id="filters_loading" style="display:none; width:1.5rem; height: 1.5rem;"><span class="sr-only">Loading...</span></div>
                    </div>
                </div>
                <?php if (stristr($_SERVER["REQUEST_URI"], '/topic-dashboard') !== FALSE) { ?>
                <div class="row" style="display: none; margin-bottom: 5px;" id="excel_download">
                    <div class="col-sm-12"><a href="javascript:download_excel_data('maintopic', '{{ csrf_token() }}');" style="color: indigo;"><i class="bx bx-download"></i> Download</a> (Click on the download link to get data in Microsoft Excel format)</div>
                </div>
                <?php } ?>
                <?php if (stristr($_SERVER["REQUEST_URI"], '/dashboard-competitor-analysis') !== FALSE) { ?>
                <div class="row" style="margin-bottom: 5px;">
                    <div class="col-sm-12"><a href="javascript:void(0);" onclick="javascript:get_ca_report('{{ csrf_token() }}', '<?php echo \Session::get('_loaded_ca_id'); ?>');" style="color: indigo;"><i class="bx bx-download"></i> Download</a> (Click on the download link to get data a PDF report)</div>
                </div>
                <?php } ?>
            </div>



        </div>
    </div>
</div>

<script type="text/javascript"></script>
<script>
    setTimeout(function()
    {
    loadplaceholderfroselect();
    },4000);
    function loadplaceholderfroselect(){
        $(".sentiment_type_placeholder").select2({
            placeholder: "Select...",
            allowClear: true
        });
        $(".data_source_placeholder").select2({
            placeholder: "Select...",
            allowClear: true
        });
        $(".filter_by_location_placeholder").select2({
            placeholder: "Select...",
            allowClear: true
        });
        $(".filter_by_language_placeholder").select2({
            placeholder: "Select...",
            allowClear: true
        });
    }
    
    function check_time_slot(v)
    {
        console.log("Val: "+v);
        if(v == 'custom')
        {
            $("#date_from").show();
            $("#date_to").show();
        }
        else
        {
            $("#date_from").hide();
            $("#date_to").hide();
        }
    }
</script>