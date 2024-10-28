@extends('layouts.contentLayoutMaster') {{-- page Title --}}
@section('title','Reports graphs') {{-- vendor css --}}
@section('vendor-styles') @endsection 
@section('page-styles') @endsection 

@section('content')
<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
<script src="{{asset('vendors/js/charts/apexcharts.js')}}"></script>
<!--This page is called hidden by Rashid or Naeem side as cron to start graphs generation of pdf document. 
Make sure to send topicid, userid and report id in all requests on server-->
<input type="hidden" name="token" id="token" value="{{ csrf_token() }}">
<script type="text/javascript">
var base_url = "https://dashboard.datalyticx.ai/";
var $primary = '#5A8DEE';
var $success = '#39DA8A';
var $danger = '#FF5B5C';
var $warning = '#FDAC41';
var $info = '#00CFDD';
var $twitter = '#00ABEA';
var $label_color = '#304156';
var $danger_light = '#FFC1C1';
var $gray_light = '#828D99';
var $bg_light = "#f2f4f4";
//var assetPath = $('html').attr('data-asset-path');
</script>
    <?php
    //Loop records for the reports that are pending
    for($i=0; $i<count($reports_list); $i++)
    {
    ?>
<!------------------------------ MAIN TOPIC MENTIONS GRAPH --------------------------->
        <div id="main_topic_mentions_graph_<?php echo $reports_list[$i]->rs_id.'_'.$reports_list[$i]->rs_tid; ?>"></div>
        <script type="text/javascript">
        from_date = '<?php echo $reports_list[$i]->rs_topic_from_date; ?>';
        to_date   = '<?php echo $reports_list[$i]->rs_topic_to_date; ?>';
        var form = new FormData();
        form.append("section", "dashboard_mentions_graph");
        form.append("pdf_report_data", "yes");
        form.append("manual_filter", "yes");
        form.append("filter_type", "custom_dates");
        form.append("from_date", from_date);
        form.append("to_date", to_date);
        form.append("_token", $("#token").val());
        form.append("tid", <?php echo $reports_list[$i]->rs_tid; ?>);

        var settings = {
          "url": base_url+"api/report-generation/get-es-data",
          "method": "POST",
          "timeout": 0,
          "processData": false,
          "mimeType": "multipart/form-data",
          "contentType": false,
          "data": form
        };

        $.ajax(settings).done(function (response) {
            //console.log(response)
            //$('#spinner').css('display','none');
            let xarray      = [];
            let yarray      = [];
            let getdata     = [];
            var split1       = response.split('~');
            var split       = split1[0].split('|');
            var realdata    = split.map(Function.prototype.call, String.prototype.trim);

            realdata.forEach(function(item) {
                getdata = item.split(",");
                xarray.push(getdata[0]);
                yarray.push(getdata[1]);
            });

            let options = {
                colors: [$primary],
                dataLabels: {
                    enabled: false,
                },
                chart: {
                    height: 350,
                    type: 'line',
                    toolbar: {
                        show: false
                    },
                    animations: {
                        enabled: false
                    }                    
                },
                noData: {  
                    text: "Loading...",  
                    align: 'center',  
                    verticalAlign: 'middle',  
                    offsetX: 0,  
                    offsetY: 0,  
                    style: {  
                        color: "#000000",  
                        fontSize: '14px',  
                        fontFamily: "Helvetica"  
                    }  
                },
                series: [{ name: 'mentions', data: yarray }],
                xaxis: { 
                    categories: xarray, 
                    type: 'datetime',
                    labels: {
                        show: true
                    }
                }
            },
            mentions_chart = new ApexCharts(document.querySelector("#main_topic_mentions_graph_<?php echo $reports_list[$i]->rs_id.'_'.$reports_list[$i]->rs_tid; ?>"), options);
            mentions_chart.render();
            mentions_chart.render().then(() => {
                window.setTimeout(function() {
                    mentions_chart.dataURI().then((uri) => {
                        //console.log(uri);
                        var form = new FormData();
                        form.append("section", "main_topic_mentions_graph");
                        form.append("graph_image", uri["imgURI"]);
                        form.append("report_id", <?php echo $reports_list[$i]->rs_id; ?>);
                        form.append("report_tid", <?php echo $reports_list[$i]->rs_tid; ?>);
                        form.append("report_uid", <?php echo $reports_list[$i]->rs_uid; ?>);
                        form.append("_token", $("#token").val());

                        var settings = {
                          "url": base_url+"api/report-generation/save-report-graph",
                          "method": "POST",
                          "timeout": 0,
                          "processData": false,
                          "mimeType": "multipart/form-data",
                          "contentType": false,
                          "data": form
                        };

                        $.ajax(settings).done(function (response) {
                            
                        });
                    })
                }, 1000) 
            })
            mentions_chart.updateSeries([{
                name: 'mentions',
                data: yarray
            }])
        });
        </script>
        <!------------------------------ END: MAIN TOPIC MENTIONS GRAPH --------------------------->
        
        <!------------------------------ MAIN TOPIC ENGAGEMENT GRAPH --------------------------->
        <div id="main_topic_engagement_graph_<?php echo $reports_list[$i]->rs_id.'_'.$reports_list[$i]->rs_tid; ?>"></div>
        <script type="text/javascript">
        from_date = '<?php echo $reports_list[$i]->rs_topic_from_date; ?>';
        to_date   = '<?php echo $reports_list[$i]->rs_topic_to_date; ?>';
        var form = new FormData();
        form.append("section", "dashboard_engagement");
        form.append("pdf_report_data", "yes");
        form.append("manual_filter", "yes");
        form.append("filter_type", "custom_engagement_dates");
        form.append("from_date", from_date);
        form.append("to_date", to_date);
        form.append("_token", $("#token").val());
        form.append("tid", <?php echo $reports_list[$i]->rs_tid; ?>);

        var settings = {
          "url": base_url+"api/report-generation/get-es-data",
          "method": "POST",
          "timeout": 0,
          "processData": false,
          "mimeType": "multipart/form-data",
          "contentType": false,
          "data": form
        };

        $.ajax(settings).done(function (response) {
            engacount   = JSON.parse(response);
            xarray      = engacount['data_dates'];
            yarray      = engacount['data_counts'];

            options = {
                colors: [$primary],
                dataLabels: {
                    enabled: false,
                },
                chart: {
                    height: 350,
                    type: 'line',
                    toolbar: {
                        show: false
                    },
                    animations: {
                        enabled: false
                    }
                },
                noData: {  
                    text: "Loading...",  
                    align: 'center',  
                    verticalAlign: 'middle',  
                    offsetX: 0,  
                    offsetY: 0,  
                    style: {  
                        color: "#000000",  
                        fontSize: '14px',  
                        fontFamily: "Helvetica"  
                    }  
                },
                series: [{ name: 'Engagements', data: yarray }],
                xaxis: { 
                    categories: xarray, 
                    type: 'datetime',
                    labels: {
                        show: true,
                    }
                }
            },
            eng_chart = new ApexCharts(document.querySelector("#main_topic_engagement_graph_<?php echo $reports_list[$i]->rs_id.'_'.$reports_list[$i]->rs_tid; ?>"), options);
            //eng_chart.render();
            eng_chart.render().then(() => {
                window.setTimeout(function() {
                    eng_chart.dataURI().then((uri) => {
                        //console.log(uri);
                        var form = new FormData();
                        form.append("section", "main_topic_engagement_graph");
                        form.append("graph_image", uri["imgURI"]);
                        form.append("report_id", <?php echo $reports_list[$i]->rs_id; ?>);
                        form.append("report_tid", <?php echo $reports_list[$i]->rs_tid; ?>);
                        form.append("report_uid", <?php echo $reports_list[$i]->rs_uid; ?>);
                        form.append("_token", $("#token").val());

                        var settings = {
                          "url": base_url+"api/report-generation/save-report-graph",
                          "method": "POST",
                          "timeout": 0,
                          "processData": false,
                          "mimeType": "multipart/form-data",
                          "contentType": false,
                          "data": form
                        };

                        $.ajax(settings).done(function (response) {
                            
                        });
                    })
                }, 1000) 
            })
            eng_chart.updateSeries([{
                name: 'Engagements',
                data: yarray
            }])
        });
        </script>
        <!------------------------------ END: MAIN TOPIC ENGAGEMENT GRAPH --------------------------->
        
        <!------------------------------ MAIN TOPIC SHARES GRAPH --------------------------->
        <div id="main_topic_shares_graph_<?php echo $reports_list[$i]->rs_id.'_'.$reports_list[$i]->rs_tid; ?>"></div>
        <script type="text/javascript">
        from_date = '<?php echo $reports_list[$i]->rs_topic_from_date; ?>';
        to_date   = '<?php echo $reports_list[$i]->rs_topic_to_date; ?>';
        var form = new FormData();
        form.append("section", "dashboard_shares");
        form.append("pdf_report_data", "yes");
        form.append("manual_filter", "yes");
        form.append("filter_type", "custom_shares_dates");
        form.append("from_date", from_date);
        form.append("to_date", to_date);
        form.append("_token", $("#token").val());
        form.append("tid", <?php echo $reports_list[$i]->rs_tid; ?>);

        var settings = {
          "url": base_url+"api/report-generation/get-es-data",
          "method": "POST",
          "timeout": 0,
          "processData": false,
          "mimeType": "multipart/form-data",
          "contentType": false,
          "data": form
        };

        $.ajax(settings).done(function (response) {
            sharecount   = JSON.parse(response);
            xarray      = sharecount['data_dates'];
            yarray      = sharecount['data_counts'];

            options = {
                colors: [$primary],
                dataLabels: {
                    enabled: false,
                },
                chart: {
                    height: 350,
                    type: 'line',
                    toolbar: {
                        show: false
                    },
                    animations: {
                        enabled: false
                    }
                },
                noData: {  
                    text: "Loading...",  
                    align: 'center',  
                    verticalAlign: 'middle',  
                    offsetX: 0,  
                    offsetY: 0,  
                    style: {  
                        color: "#000000",  
                        fontSize: '14px',  
                        fontFamily: "Helvetica"  
                    }  
                },
                series: [{ name: 'Shares', data: yarray }],
                xaxis: { 
                    categories: xarray, 
                    type: 'datetime',
                    labels: {
                        show: true,
                    }
                }
            },
            shares_chart = new ApexCharts(document.querySelector("#main_topic_shares_graph_<?php echo $reports_list[$i]->rs_id.'_'.$reports_list[$i]->rs_tid; ?>"), options);
            //shares_chart.render();
            shares_chart.render().then(() => {
                window.setTimeout(function() {
                    shares_chart.dataURI().then((uri) => {
                        //console.log(uri);
                        var form = new FormData();
                        form.append("section", "main_topic_shares_graph");
                        form.append("graph_image", uri["imgURI"]);
                        form.append("report_id", <?php echo $reports_list[$i]->rs_id; ?>);
                        form.append("report_tid", <?php echo $reports_list[$i]->rs_tid; ?>);
                        form.append("report_uid", <?php echo $reports_list[$i]->rs_uid; ?>);
                        form.append("_token", $("#token").val());

                        var settings = {
                          "url": base_url+"api/report-generation/save-report-graph",
                          "method": "POST",
                          "timeout": 0,
                          "processData": false,
                          "mimeType": "multipart/form-data",
                          "contentType": false,
                          "data": form
                        };

                        $.ajax(settings).done(function (response) {
                            
                        });
                    })
                }, 1000) 
            })
            shares_chart.updateSeries([{
                name: 'Shares',
                data: yarray
            }])
        });
        </script>
        <!------------------------------ END: MAIN TOPIC SHARES GRAPH --------------------------->
        
        <!------------------------------ MAIN TOPIC LIKES GRAPH --------------------------->
        <div id="main_topic_likes_graph_<?php echo $reports_list[$i]->rs_id.'_'.$reports_list[$i]->rs_tid; ?>"></div>
        <script type="text/javascript">
        from_date = '<?php echo $reports_list[$i]->rs_topic_from_date; ?>';
        to_date   = '<?php echo $reports_list[$i]->rs_topic_to_date; ?>';
        var form = new FormData();
        form.append("section", "dashboard_likes");
        form.append("pdf_report_data", "yes");
        form.append("manual_filter", "yes");
        form.append("filter_type", "custom_likes_dates");
        form.append("from_date", from_date);
        form.append("to_date", to_date);
        form.append("_token", $("#token").val());
        form.append("tid", <?php echo $reports_list[$i]->rs_tid; ?>);

        var settings = {
          "url": base_url+"api/report-generation/get-es-data",
          "method": "POST",
          "timeout": 0,
          "processData": false,
          "mimeType": "multipart/form-data",
          "contentType": false,
          "data": form
        };

        $.ajax(settings).done(function (response) {
            likescount   = JSON.parse(response);
            xarray      = likescount['data_dates'];
            yarray      = likescount['data_counts'];

            options = {
                colors: [$primary],
                dataLabels: {
                    enabled: false,
                },
                chart: {
                    height: 350,
                    type: 'line',
                    toolbar: {
                        show: false
                    },
                    animations: {
                        enabled: false
                    }
                },
                noData: {  
                    text: "Loading...",  
                    align: 'center',  
                    verticalAlign: 'middle',  
                    offsetX: 0,  
                    offsetY: 0,  
                    style: {  
                        color: "#000000",  
                        fontSize: '14px',  
                        fontFamily: "Helvetica"  
                    }  
                },
                series: [{ name: 'Likes', data: yarray }],
                xaxis: { 
                    categories: xarray, 
                    type: 'datetime',
                    labels: {
                        show: true,
                    }
                }
            },
            likes_chart = new ApexCharts(document.querySelector("#main_topic_likes_graph_<?php echo $reports_list[$i]->rs_id.'_'.$reports_list[$i]->rs_tid; ?>"), options);
            //likes_chart.render();
            likes_chart.render().then(() => {
                window.setTimeout(function() {
                    likes_chart.dataURI().then((uri) => {
                        //console.log(uri);
                        var form = new FormData();
                        form.append("section", "main_topic_likes_graph");
                        form.append("graph_image", uri["imgURI"]);
                        form.append("report_id", <?php echo $reports_list[$i]->rs_id; ?>);
                        form.append("report_tid", <?php echo $reports_list[$i]->rs_tid; ?>);
                        form.append("report_uid", <?php echo $reports_list[$i]->rs_uid; ?>);
                        form.append("_token", $("#token").val());

                        var settings = {
                          "url": base_url+"api/report-generation/save-report-graph",
                          "method": "POST",
                          "timeout": 0,
                          "processData": false,
                          "mimeType": "multipart/form-data",
                          "contentType": false,
                          "data": form
                        };

                        $.ajax(settings).done(function (response) {
                            
                        });
                    })
                }, 1000) 
            })
            likes_chart.updateSeries([{
                name: 'Likes',
                data: yarray
            }])
        });
        </script>
        <!------------------------------ END: MAIN TOPIC LIKES GRAPH --------------------------->
        
        <!------------------------------ END: MAIN TOPIC INFLUENCER GRAPH --------------------------->
        <div style="width: 400px;" id="main_topic_influencer_graph_<?php echo $reports_list[$i]->rs_id.'_'.$reports_list[$i]->rs_tid; ?>"></div>
        <script type="text/javascript">
            from_date = '<?php echo $reports_list[$i]->rs_topic_from_date; ?>';
            to_date   = '<?php echo $reports_list[$i]->rs_topic_to_date; ?>';
            
            var form = new FormData();
            form.append("section", "maintopic_influencers");
            form.append("_token", $("#token").val());
            form.append("pdf_report_data", "yes");
            form.append("from_date", from_date);
            form.append("to_date", to_date);
            form.append("tid", <?php echo $reports_list[$i]->rs_tid; ?>);

            var settings = {
                "url": base_url+"api/report-generation/get-es-data",
                "method": "POST",
                "timeout": 0,
                "processData": false,
                "mimeType": "multipart/form-data",
                "contentType": false,
                "data": form
            };

            $.ajax(settings).done(function (response) {
                var xarray = [];
                var yarray = [];
                var inf_data = JSON.parse(response);

                xarray.push(parseInt(inf_data["celebrity"],10));
                yarray.push("Celebrity ("+parseInt(inf_data["celebrity"],10)+")");

                xarray.push(parseInt(inf_data["mega"],10));
                yarray.push("Mega ("+parseInt(inf_data["mega"],10)+")");

                xarray.push(parseInt(inf_data["macro"],10));
                yarray.push("Macro ("+parseInt(inf_data["macro"],10)+")");

                xarray.push(parseInt(inf_data["midtier"],10));
                yarray.push("Midtier ("+parseInt(inf_data["midtier"],10)+")");

                xarray.push(parseInt(inf_data["micro"],10));
                yarray.push("Micro ("+parseInt(inf_data["micro"],10)+")");

                xarray.push(parseInt(inf_data["nano"],10));
                yarray.push("Nano ("+parseInt(inf_data["nano"],10)+")");

                var donutChartOptions = {
                    chart: {
                    type: 'donut',
                    height: 300,
                    toolbar: {
                        show: false,
                        tools: {
                            download: true,
                            selection: false,
                            zoom: false,
                            zoomin: false,
                            zoomout: false,
                            pan: false,
                            reset: false | '<img src="/static/icons/reset.png" width="20">',
                            customIcons: []
                        }
                    },
                    animations: {
                        enabled: false
                    }
                },
                //colors: ['#18A99E','#217101', '#2AE7CF','#39B200', '#0C7C76', '#60D837'],
                colors: ['#189886','#af6fff', '#96414d','#0097a3', '#eda68e', '#f2cc81'],
                series: xarray,
                labels: yarray,
                legend: {
                    itemMargin: {
                            horizontal: 2
                        },
                        position: 'bottom',
                        show: true,
                },
                responsive: [{
                breakpoint: 576,
                options: {
                    chart: {
                        width: 300
                    }
                }
                }]
            }
            var donutChart = new ApexCharts(document.querySelector("#main_topic_influencer_graph_<?php echo $reports_list[$i]->rs_id.'_'.$reports_list[$i]->rs_tid; ?>"), donutChartOptions);
            //donutChart.render();
            donutChart.render().then(() => {
                window.setTimeout(function() {
                    donutChart.dataURI().then((uri) => {
                        //console.log(uri);
                        var form = new FormData();
                        form.append("section", "main_topic_influencer_graph");
                        form.append("graph_image", uri["imgURI"]);
                        form.append("report_id", <?php echo $reports_list[$i]->rs_id; ?>);
                        form.append("report_tid", <?php echo $reports_list[$i]->rs_tid; ?>);
                        form.append("report_uid", <?php echo $reports_list[$i]->rs_uid; ?>);
                        form.append("_token", $("#token").val());

                        var settings = {
                          "url": base_url+"api/report-generation/save-report-graph",
                          "method": "POST",
                          "timeout": 0,
                          "processData": false,
                          "mimeType": "multipart/form-data",
                          "contentType": false,
                          "data": form
                        };

                        $.ajax(settings).done(function (response) {
                            
                        });
                    })
                }, 1000) 
            })
            });
        </script>
        <!------------------------------ END: MAIN TOPIC INFLUENCER GRAPH --------------------------->
        
        <!------------------------------ END: MAIN TOPIC USERS GRAPH --------------------------->
        <div style="width: 400px;" id="main_topic_user_graph_<?php echo $reports_list[$i]->rs_id.'_'.$reports_list[$i]->rs_tid; ?>"></div>
        <script type="text/javascript">
            from_date = '<?php echo $reports_list[$i]->rs_topic_from_date; ?>';
            to_date   = '<?php echo $reports_list[$i]->rs_topic_to_date; ?>';
            
            var form = new FormData();
            form.append("section", "maintopic_users_graph");
            form.append("_token", $("#token").val());
            form.append("pdf_report_data", "yes");
            form.append("from_date", from_date);
            form.append("to_date", to_date);
            form.append("tid", <?php echo $reports_list[$i]->rs_tid; ?>);

            var settings = {
                "url": base_url+"api/report-generation/get-es-data",
                "method": "POST",
                "timeout": 0,
                "processData": false,
                "mimeType": "multipart/form-data",
                "contentType": false,
                "data": form
            };

            $.ajax(settings).done(function (response) {
                var xarray = [];
                var yarray = [];
                var u_data = JSON.parse(response);

                xarray.push(parseInt(u_data["normal_users_mentions"],10));
                yarray.push("Normal user mentions ("+parseInt(u_data["normal_users_mentions"],10)+")");

                xarray.push(parseInt(u_data["inf_users_mentions"],10));
                yarray.push("Influencer user mentions ("+parseInt(u_data["inf_users_mentions"],10)+")");

                //xarray.push(parseInt(u_data["unidentified_users_mentions"],10));
                //yarray.push("Un-identified user mentions");

                var donutChartOptions = {
                    chart: {
                    type: 'pie',
                    height: 300,
                    toolbar: {
                        show: false,
                        tools: {
                            download: true,
                            selection: false,
                            zoom: false,
                            zoomin: false,
                            zoomout: false,
                            pan: false,
                            reset: false | '<img src="/static/icons/reset.png" width="20">',
                            customIcons: []
                        }
                    },
                    animations: {
                        enabled: false
                    }
                },
                //colors: ['#C1C0C2','#1FBAA6', '#082444'],
                colors: ['#009abd', '#f26f0f'],
                series: xarray,
                labels: yarray,
                legend: {
                    show: true, position: 'bottom'
                },
                responsive: [{
                breakpoint: 576,
                options: {
                    chart: {
                        width: 300
                    }
                }
                }]
            }
            var donutChart = new ApexCharts(document.querySelector("#main_topic_user_graph_<?php echo $reports_list[$i]->rs_id.'_'.$reports_list[$i]->rs_tid; ?>"), donutChartOptions);
            //donutChart.render();
            donutChart.render().then(() => {
                window.setTimeout(function() {
                    donutChart.dataURI().then((uri) => {
                        //console.log(uri);
                        var form = new FormData();
                        form.append("section", "main_topic_users_graph");
                        form.append("graph_image", uri["imgURI"]);
                        form.append("report_id", <?php echo $reports_list[$i]->rs_id; ?>);
                        form.append("report_tid", <?php echo $reports_list[$i]->rs_tid; ?>);
                        form.append("report_uid", <?php echo $reports_list[$i]->rs_uid; ?>);
                        form.append("_token", $("#token").val());

                        var settings = {
                          "url": base_url+"api/report-generation/save-report-graph",
                          "method": "POST",
                          "timeout": 0,
                          "processData": false,
                          "mimeType": "multipart/form-data",
                          "contentType": false,
                          "data": form
                        };

                        $.ajax(settings).done(function (response) {
                            
                        });
                    })
                }, 1000) 
            })
            });
        </script>
        <!------------------------------ END: MAIN TOPIC USERS GRAPH --------------------------->
        
        
        
        <!---------------------------- MAIN topic sentiment chart --------------------------------------------------------------->
        <div id="maintopic_sentiment_chart_<?php echo $reports_list[$i]->rs_id.'_'.$reports_list[$i]->rs_tid; ?>" style="width: 400px;"></div>
        <script type="text/javascript">
            var form = new FormData();
            form.append("section", "dashboard_sentiment_chart");
            form.append("_token", $("#token").val());
            form.append("pdf_report_data", "yes");
            form.append("from_date", from_date);
            form.append("to_date", to_date);
            form.append("tid", <?php echo $reports_list[$i]->rs_tid; ?>);

            var settings = {
                "url": base_url+"api/report-generation/get-es-data",
                "method": "POST",
                "timeout": 0,
                "processData": false,
                "mimeType": "multipart/form-data",
                "contentType": false,
                "data": form
            };

            $.ajax(settings).done(function (response) {
                let response2    = response.split("|");
                let xarray      = [];
                let yarray      = [];
                response2.forEach(function(item) {
                    getdata = item.split(",");
                    xarray.push(parseInt(getdata[1],10));
                    yarray.push(getdata[0]+" ("+parseInt(getdata[1],10)+")");
                });
              //console.log(response);

                var donutChartOptions = {
                    chart: {
                        type: 'donut',
                        width: 500,
                        events: {
                        dataPointSelection: function(event, chartContext, opts) {
                            //alert(opts.w.config.xaxis.categories[opts.dataPointIndex])
                        }
                    },
                    toolbar: {
                        show: false,
                        tools: {
                            download: true,
                            selection: false,
                            zoom: false,
                            zoomin: false,
                            zoomout: false,
                            pan: false,
                            reset: false | '<img src="/static/icons/reset.png" width="20">',
                            customIcons: []
                        }
                    },
                    animations: {
                        enabled: false
                    }
                },
                colors: ['#3bdb8b','#fe5a5c', '#5b8eee'],
                series: xarray,
                labels: yarray,
                legend: {
                    show: true,
                    itemMargin: {
                        horizontal: 2
                    },
                    position: 'bottom',
                },
                responsive: [{
                    breakpoint: 576,
                    options: {
                        chart: {
                            width: 500
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }]
              }
              var donutChart = new ApexCharts(document.querySelector("#maintopic_sentiment_chart_<?php echo $reports_list[$i]->rs_id.'_'.$reports_list[$i]->rs_tid; ?>"), donutChartOptions);
                //donutChart.render();
                donutChart.render().then(() => {
                    window.setTimeout(function() {
                        donutChart.dataURI().then((uri) => {
                            //console.log(uri);
                            var form = new FormData();
                            form.append("section", "main_topic_sentiment_graph");
                            form.append("graph_image", uri["imgURI"]);
                            form.append("report_id", <?php echo $reports_list[$i]->rs_id; ?>);
                            form.append("report_tid", <?php echo $reports_list[$i]->rs_tid; ?>);
                            form.append("report_uid", <?php echo $reports_list[$i]->rs_uid; ?>);
                            form.append("_token", $("#token").val());

                            var settings = {
                              "url": base_url+"api/report-generation/save-report-graph",
                              "method": "POST",
                              "timeout": 0,
                              "processData": false,
                              "mimeType": "multipart/form-data",
                              "contentType": false,
                              "data": form
                            };

                            $.ajax(settings).done(function (response) {

                            });
                        })
                    }, 1000) 
                })
            });
        </script>
        <!---------------------------- END: MAIN topic sentiment chart --------------------------------------------------------------->
        
        <div id="maintopic_emotions_chart_<?php echo $reports_list[$i]->rs_id.'_'.$reports_list[$i]->rs_tid; ?>" style="width: 400px;"></div>
        <!---------------------------- MAIN topic emotions chart --------------------------------------------------------------->
        <script type="text/javascript">
            var form = new FormData();
            form.append("section", "maintopic_emotions_chart");
            form.append("_token", $("#token").val());
            form.append("pdf_report_data", "yes");
            form.append("from_date", from_date);
            form.append("to_date", to_date);
            form.append("tid", <?php echo $reports_list[$i]->rs_tid; ?>);

            var settings = {
                "url": base_url+"api/report-generation/get-es-data",
                "method": "POST",
                "timeout": 0,
                "processData": false,
                "mimeType": "multipart/form-data",
                "contentType": false,
                "data": form
            };

            $.ajax(settings).done(function (response) {
                //console.log(response);
                var data = JSON.parse(response);
                var label_data = [];
                var counts_data = [];

                for(var i=0; i<data["emos"].length; i++)
                {
                    label_data.push(data["emos"][i]);
                    counts_data.push(data["counts"][i]);
                }
                //console.log("=====>"+label_data)
                
                //Below options are for radialBar graph
                /*var options = {
                    series: counts_data,
                    chart: {
                        width: 436,
                        type: 'radialBar',
                        toolbar: {
                                show: true,
                                tools: {
                                    download: true,
                                    selection: false,
                                    zoom: false,
                                    zoomin: false,
                                    zoomout: false,
                                    pan: false,
                                    reset: false | '<img src="/static/icons/reset.png" width="20">',
                                    customIcons: []
                                }
                            }
                    },
                    legend: {
                        show: true,
                    },
                    colors: ['#df7970','#FFA65B', '#51cda0', '#BEB145', '#BA56FF'],
                    plotOptions: {
                        radialBar: {
                            dataLabels: {
                                name: {
                                    fontSize: '22px',
                                },
                                value: {
                                    fontSize: '16px',
                                },
                                total: {
                                    show: false,
                                    label: 'Total',
                                    formatter: function (w) {
                                        // By default this function returns the average of all series. The below is just an example to show the use of custom formatter function
                                        return 249
                                    }
                                }
                            }
                        }
                    },
                    labels: label_data,
                };*/
    
                //Below options are for bar chart
                var options = {
                    series: [{
                            data: counts_data,
                            name: "Mentions"
                        }],
                    chart: {
                        width: 436,
                        type: 'bar',
                        toolbar: {
                                show: false,
                                tools: {
                                    download: true,
                                    selection: false,
                                    zoom: false,
                                    zoomin: false,
                                    zoomout: false,
                                    pan: false,
                                    reset: false | '<img src="/static/icons/reset.png" width="20">',
                                    customIcons: []
                                }
                            },
                    animations: {
                        enabled: false
                    }
                    },
                    legend: {
                        show: false,
                    },
                    colors: ['#df7970','#FFA65B', '#51cda0', '#BEB145', '#BA56FF'],
                    plotOptions: {
                            bar: {
                                columnWidth: '45%',
                                distributed: true,
                            }
                        },
                        dataLabels: {
                            enabled: true,
                        },
                        xaxis: {
                            //categories: label_data,
                            categories: [
                                ['Anger 😡'],
                                ['Fear 😨'],
                                ['Happy 😊'],
                                ['Sadness 😔'],
                                ['Surprise 😲'], 
                            ]
                        },
                };

                var chart = new ApexCharts(document.querySelector("#maintopic_emotions_chart_<?php echo $reports_list[$i]->rs_id.'_'.$reports_list[$i]->rs_tid; ?>"), options);
                //chart.render();
                chart.render().then(() => {
                    window.setTimeout(function() {
                        chart.dataURI().then((uri) => {
                            //console.log(uri);
                            var form = new FormData();
                            form.append("section", "main_topic_emotions_graph");
                            form.append("graph_image", uri["imgURI"]);
                            form.append("report_id", <?php echo $reports_list[$i]->rs_id; ?>);
                            form.append("report_tid", <?php echo $reports_list[$i]->rs_tid; ?>);
                            form.append("report_uid", <?php echo $reports_list[$i]->rs_uid; ?>);
                            form.append("_token", $("#token").val());

                            var settings = {
                              "url": base_url+"api/report-generation/save-report-graph",
                              "method": "POST",
                              "timeout": 0,
                              "processData": false,
                              "mimeType": "multipart/form-data",
                              "contentType": false,
                              "data": form
                            };

                            $.ajax(settings).done(function (response) {

                            });
                        })
                    }, 1000) 
                })
            });
        </script>
        <!---------------------------- END: MAIN topic sentiment chart --------------------------------------------------------------->
        
        <!---------------------------- MAIN topic word clout ---------------------------------------------->
        <script src="https://cdn.amcharts.com/lib/4/core.js"></script>
        <script src="https://cdn.amcharts.com/lib/4/charts.js"></script>
        <script src="https://cdn.amcharts.com/lib/4/plugins/wordCloud.js"></script>
        <script src="https://cdn.amcharts.com/lib/4/themes/animated.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/webfont/1.6.26/webfont.js"></script>
        <div id="main_topic_wordcloud_<?php echo $reports_list[$i]->rs_id.'_'.$reports_list[$i]->rs_tid; ?>" style="height: 900px;letter-spacing:normal; background: #ffffff;"></div>
        <script type="text/javascript">
        var form = new FormData();
        form.append("section", "maintopic_wordcloud");
        form.append("_token", $("#token").val());
        form.append("pdf_report_data", "yes");
        form.append("from_date", from_date);
        form.append("to_date", to_date);
        form.append("tid", <?php echo $reports_list[$i]->rs_tid; ?>);

        var settings = {
            "url": base_url+"api/report-generation/get-es-data",
            "method": "POST",
            "timeout": 0,
            "processData": false,
            "mimeType": "multipart/form-data",
            "contentType": false,
            "data": form
        };

        $.ajax(settings).done(function (response) { 
            //console.log(response);

            am4core.ready(function() {
                // Themes begin
                //am4core.useTheme(am4themes_animated);
                am4core.addLicense("ch-custom-attribution");
                // Themes end

                var chart = am4core.create("main_topic_wordcloud_<?php echo $reports_list[$i]->rs_id.'_'.$reports_list[$i]->rs_tid; ?>", am4plugins_wordCloud.WordCloud);
                var series = chart.series.push(new am4plugins_wordCloud.WordCloudSeries());

                series.accuracy = 4;
                series.step = 15;
                series.rotationThreshold = 0.7;
                series.maxCount = 200;
                series.minWordLength = 2;
                series.labels.template.margin(4,4,4,4);
                series.maxFontSize = am4core.percent(30);
                series.minFontSize = am4core.percent(4);
                series.defaultState.transitionDuration = 0;
                series.interpolationDuration = 0;
                series.hiddenState.transitionDuration = 0;

                series.data = eval(response); 
                series.dataFields.word = "tag";
                series.dataFields.value = "count";
                series.labels.template.tooltipText = "{word}: {value}";
                //series.text = response.trim();

                series.colors = new am4core.ColorSet();
                series.colors.passOptions = {}; // makes it loop

                //series.labelsContainer.rotation = 45;
                series.angles = [0,-90];
                series.fontWeight = "700";
                //chart.exporting.menu = new am4core.ExportMenu();

                series.events.on("arrangeended", function(ev) {
                    var img;
                    chart.exporting.getImage( "png" ).then( ( data ) => {
                      img = data;
                      //console.log('IMG:'+img);
                      var form = new FormData();
                        form.append("section", "main_topic_word_cloud");
                        form.append("graph_image", img);
                        form.append("report_id", <?php echo $reports_list[$i]->rs_id; ?>);
                        form.append("report_tid", <?php echo $reports_list[$i]->rs_tid; ?>);
                        form.append("report_uid", <?php echo $reports_list[$i]->rs_uid; ?>);
                        form.append("_token", $("#token").val());

                        var settings = {
                          "url": base_url+"api/report-generation/save-report-graph",
                          "method": "POST",
                          "timeout": 0,
                          "processData": false,
                          "mimeType": "multipart/form-data",
                          "contentType": false,
                          "data": form
                        };

                        $.ajax(settings).done(function (response) {

                        });
                    } );
                });
                
                
                
                
            }); // end am4core.ready()
        });
        </script>
        <!---------------------------- END: main topic word cloud ------------------------------------------>
        
        <!--------------------------- SUB TOPICS START ----------------------------------------------->
        <?php
        $selected_subtopics_ids = array();
        $subtopics_list = $topic_obj->get_subtopics_list($reports_list[$i]->rs_tid);
        //get sub topic ids
        if(!is_null($reports_list[$i]->rs_subtopic_ids) && $reports_list[$i]->rs_subtopic_ids != '' && $reports_list[$i]->rs_subtopic_ids != 'ALL')
        {
            $selected_subtopics_ids = explode(",", $reports_list[$i]->rs_subtopic_ids);
        }
        
        if(count($subtopics_list) > 0)
        {
            for($k=0; $k<count($subtopics_list); $k++)
            {
                if(!is_null($reports_list[$i]->rs_subtopic_ids) && $reports_list[$i]->rs_subtopic_ids != '' && $reports_list[$i]->rs_subtopic_ids != 'ALL')
                {
                    //subtopics are selected
                    if(!in_array($subtopics_list[$k]->exp_id, $selected_subtopics_ids))
                    {
                        //if id not found, restart the loop
                        continue;
                    }
                }
            ?>
            <!---------------- CSAT ------------------------>
            <div id="csat_chart_<?php echo $k; ?>" style="width: 200px;"></div>
            <script type="text/javascript">
            var form = new FormData();
            form.append("section", "csat_data");
            form.append("_token", $("#token").val());
            form.append("pdf_report_data", "yes");
            form.append("from_date", "<?php echo $reports_list[$i]->rs_subtopic_from_date; ?>");
            form.append("to_date", "<?php echo $reports_list[$i]->rs_subtopic_to_date; ?>");
            form.append("stid", <?php echo $subtopics_list[$k]->exp_id; ?>);
            form.append("tid", <?php echo $reports_list[$i]->rs_tid; ?>);
            
            var settings = {
            		
                "url": base_url+"api/report-generation/get-es-data",
                "method": "POST",
                "timeout": 0,
                "processData": false,
                "mimeType": "multipart/form-data",
                "contentType": false,
                "data": form
            };
            
            $.ajax(settings).done(function (response) {
                //console.log(response);
                var data = JSON.parse(response);
                //console.log(data["csat_score"]);
                
                if(data["csat_score"] == 'NA')
                {
                    //do nothing
                }
                else
                {
                    var options = {
                    series: [data["csat_score"]],
                    colors: ["#6ba75f"],
                    chart: {
                        height: 300,
                        type: 'radialBar',
                        offsetY: -10,
                        toolbar: {
                            show: false,
                            tools: {
                                download: true,
                                selection: false,
                                zoom: false,
                                zoomin: false,
                                zoomout: false,
                                pan: false,

                                reset: false | '<img src="/static/icons/reset.png" width="20">',
                                customIcons: []
                            }
                        },
                    animations: {
                        enabled: false
                    }
                    },
                    plotOptions: {
                        radialBar: {
                            startAngle: -135,
                            endAngle: 135,
                                dataLabels: {
                                    name: {
                                        fontSize: '16px',
                                        color: undefined,
                                        offsetY: 120
                                    },
                                    value: {
                                        offsetY: 76,
                                        fontSize: '22px',
                                        color: undefined,
                                        formatter: function (val) {
                                            return val + "%";
                                        }
                                    }
                                }
                            }
                        },
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shade: 'dark',
                                shadeIntensity: 0.15,
                                inverseColors: false,
                                opacityFrom: 1,
                                opacityTo: 1,
                                gradientToColors: ["#ff0000"],
                                stops: [0, 100]
                            },
                        },
                        stroke: {
                            lineCap: "butt"
                        },
                        labels: ['CSAT'],
                    };

                    var chart<?php echo $k; ?> = new ApexCharts(document.querySelector("#csat_chart_<?php echo $k; ?>"), options);
                    //chart.render();
                    chart<?php echo $k; ?>.render().then(() => {
                        window.setTimeout(function() {
                            chart<?php echo $k; ?>.dataURI().then((uri) => {
                                //console.log(uri);
                                var form = new FormData();
                                form.append("section", "subtopic_csat");
                                form.append("graph_image", uri["imgURI"]);
                                form.append("report_id", <?php echo $reports_list[$i]->rs_id; ?>);
                                form.append("report_tid", <?php echo $reports_list[$i]->rs_tid; ?>);
                                form.append("report_uid", <?php echo $reports_list[$i]->rs_uid; ?>);
                                form.append("stid", <?php echo $subtopics_list[$k]->exp_id; ?>);
                                form.append("_token", $("#token").val());

                                var settings = {
                                  "url": base_url+"api/report-generation/save-report-graph",
                                  "method": "POST",
                                  "timeout": 0,
                                  "processData": false,
                                  "mimeType": "multipart/form-data",
                                  "contentType": false,
                                  "data": form
                                };

                                $.ajax(settings).done(function (response) {

                                });
                            })
                        }, 1000) 
                    })
                }
            });
            </script>
            <!---------------- END: CSAT ------------------------>
            
            <!----------- sub topic sentiment graph ----------------->
            <div id="subtopic_sentiment_chart_<?php echo $k; ?>" style="width: 400px;"></div>
            <script type="text/javascript">
            var form = new FormData();
            form.append("section", "subtopic_sentiment_chart");
            form.append("_token", $("#token").val());
            form.append("pdf_report_data", "yes");
            form.append("from_date", "<?php echo $reports_list[$i]->rs_subtopic_from_date; ?>");
            form.append("to_date", "<?php echo $reports_list[$i]->rs_subtopic_to_date; ?>");
            form.append("stid", <?php echo $subtopics_list[$k]->exp_id; ?>);
            form.append("tid", <?php echo $reports_list[$i]->rs_tid; ?>);
            
            var settings = {
              "url": base_url+"api/report-generation/get-es-data",
              "method": "POST",
              "timeout": 0,
              "processData": false,
              "mimeType": "multipart/form-data",
              "contentType": false,
              "data": form
            };
            
            $.ajax(settings).done(function (response) {
              let response2    = response.split("|");
              let xarray      = [];
              let yarray      = [];
              response2.forEach(function(item) {
                getdata = item.split(",");
                xarray.push(parseInt(getdata[1],10));
                yarray.push(getdata[0]+" ("+parseInt(getdata[1],10)+")");
              });
              // console.log(yarray);return;
              
            var sentiChartOptions = {
                chart: {
                    type: 'donut',
                    height: 320,
                    toolbar: {
                        show: false,
                        tools: {
                            download: true,
                            selection: false,
                            zoom: false,
                            zoomin: false,
                            zoomout: false,
                            pan: false,

                            reset: false | '<img src="/static/icons/reset.png" width="20">',
                            customIcons: []
                        }
                    },
                    animations: {
                        enabled: false
                    }
                },
                colors: ['#3bdb8b','#fe5a5c', '#5b8eee'],
                series: xarray,
                labels: yarray,
                legend: {
                    itemMargin: {
                        horizontal: 2
                    },
                    position: 'bottom',
                },
                responsive: [{
                    breakpoint: 576,
                    options: {
                        chart: {
                            width: 300
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }]
              }
              var sentiChart<?php echo $k; ?> = new ApexCharts(
                document.querySelector("#subtopic_sentiment_chart_<?php echo $k; ?>"),
                sentiChartOptions
                );
              //sentiChart<?php //echo $k; ?>.render();
              sentiChart<?php echo $k; ?>.render().then(() => {
                        window.setTimeout(function() {
                            sentiChart<?php echo $k; ?>.dataURI().then((uri) => {
                                //console.log(uri);
                                var form = new FormData();
                                form.append("section", "subtopic_senti_chart");
                                form.append("graph_image", uri["imgURI"]);
                                form.append("report_id", <?php echo $reports_list[$i]->rs_id; ?>);
                                form.append("report_tid", <?php echo $reports_list[$i]->rs_tid; ?>);
                                form.append("report_uid", <?php echo $reports_list[$i]->rs_uid; ?>);
                                form.append("stid", <?php echo $subtopics_list[$k]->exp_id; ?>);
                                form.append("_token", $("#token").val());

                                var settings = {
                                  "url": base_url+"api/report-generation/save-report-graph",
                                  "method": "POST",
                                  "timeout": 0,
                                  "processData": false,
                                  "mimeType": "multipart/form-data",
                                  "contentType": false,
                                  "data": form
                                };

                                $.ajax(settings).done(function (response) {

                                });
                            })
                        }, 1000) 
                    })
              });
            </script>
            <!------------ END: sub topic sentiment graph ---------->
            
            <!----------- sub topic emotions graph ----------------->
            <div id="subtopic_emo_chart_<?php echo $k; ?>" style="width: 400px;"></div>
            <script type="text/javascript">
            var form = new FormData();
            form.append("section", "subtopic_emotions_chart");
            form.append("_token", $("#token").val());
            form.append("pdf_report_data", "yes");
            form.append("from_date", "<?php echo $reports_list[$i]->rs_subtopic_from_date; ?>");
            form.append("to_date", "<?php echo $reports_list[$i]->rs_subtopic_to_date; ?>");
            form.append("stid", <?php echo $subtopics_list[$k]->exp_id; ?>);
            form.append("tid", <?php echo $reports_list[$i]->rs_tid; ?>);
            
            var settings = {
            		
              "url": base_url+"api/report-generation/get-es-data",
              "method": "POST",
              "timeout": 0,
              "processData": false,
              "mimeType": "multipart/form-data",
              "contentType": false,
              "data": form
            };
            
            $.ajax(settings).done(function (response) {
            	//console.log(response);
            	var data = JSON.parse(response);
            	var label_data = [];
            	var counts_data = [];
            	
            	for(var i=0; i<data["emos"].length; i++)
        		{
            		label_data.push(data["emos"][i]);
            		counts_data.push(data["counts"][i]);
        		}
            	//console.log("=====>"+label_data)
              
            	//Following setting for radialBar chart
                /*var options = {
            		series: counts_data,
            		chart: {
                        width: 436,
                        type: 'radialBar',
                        toolbar: {
                            show: false,
                            tools: {
                                download: true,
                                selection: false,
                                zoom: false,
                                zoomin: false,
                                zoomout: false,
                                pan: false,

                                reset: false | '<img src="/static/icons/reset.png" width="20">',
                                customIcons: []
                            }
                        }
                    },
                    legend: {
                        show: true,
                    },
                    colors: ['#df7970','#FFA65B', '#51cda0', '#BEB145', '#BA56FF'],
            		plotOptions: {
            			radialBar: {
            				dataLabels: {
            					name: {
            						fontSize: '22px',
            					},
            					value: {
            						fontSize: '16px',
            					},
            					total: {
            						show: false,
            						label: 'Total',
            						formatter: function (w) {
            							// By default this function returns the average of all series. The below is just an example to show the use of custom formatter function
            							return 249
            						}
            					}
            				}
            			}
            		},
            		labels: label_data,
            	};*/
                
                //Below options are for bar chart
                var options = {
                    series: [{
                            data: counts_data,
                            name: "Mentions"
                        }],
                    chart: {
                        width: 436,
                        type: 'bar',
                        toolbar: {
                                show: false,
                                tools: {
                                    download: true,
                                    selection: false,
                                    zoom: false,
                                    zoomin: false,
                                    zoomout: false,
                                    pan: false,
                                    reset: false | '<img src="/static/icons/reset.png" width="20">',
                                    customIcons: []
                                }
                            },
                    animations: {
                        enabled: false
                    }
                    },
                    legend: {
                        show: false,
                    },
                    colors: ['#df7970','#FFA65B', '#51cda0', '#BEB145', '#BA56FF'],
                    plotOptions: {
                            bar: {
                                columnWidth: '45%',
                                distributed: true,
                            }
                        },
                        dataLabels: {
                            enabled: true,
                        },
                        xaxis: {
                            //categories: label_data,
                            categories: [
                                ['Anger 😡'],
                                ['Fear 😨'],
                                ['Happy 😊'],
                                ['Sadness 😔'],
                                ['Surprise 😲'], 
                            ]
                        },
                };
	
	        var emochart<?php echo $k; ?> = new ApexCharts(document.querySelector("#subtopic_emo_chart_<?php echo $k; ?>"), options);
	        	//chart<?php //echo $k; ?>.render();
                emochart<?php echo $k; ?>.render().then(() => {
                    window.setTimeout(function() {
                        emochart<?php echo $k; ?>.dataURI().then((uri) => {
                            //console.log(uri);
                            var form = new FormData();
                            form.append("section", "subtopic_emo_chart");
                            form.append("graph_image", uri["imgURI"]);
                            form.append("report_id", <?php echo $reports_list[$i]->rs_id; ?>);
                            form.append("report_tid", <?php echo $reports_list[$i]->rs_tid; ?>);
                            form.append("report_uid", <?php echo $reports_list[$i]->rs_uid; ?>);
                            form.append("stid", <?php echo $subtopics_list[$k]->exp_id; ?>);
                            form.append("_token", $("#token").val());

                            var settings = {
                              "url": base_url+"api/report-generation/save-report-graph",
                              "method": "POST",
                              "timeout": 0,
                              "processData": false,
                              "mimeType": "multipart/form-data",
                              "contentType": false,
                              "data": form
                            };

                            $.ajax(settings).done(function (response) {

                            });
                        })
                    }, 1000) 
                })
            });
            </script>
            <!------------ END: sub topic emotions graph ---------->
            
            <!--------------------- sub topic word cloud --------------------------------->
            <div id="sub_topic_wordcloud_<?php echo $reports_list[$i]->rs_id.'_'.$subtopics_list[$k]->exp_id; ?>" style="height: 700px;letter-spacing:normal; background: #ffffff;"></div>
            <script type="text/javascript">
            var form = new FormData();
            form.append("section", "subtopic_wordcloud");
            form.append("_token", $("#token").val());
            form.append("pdf_report_data", "yes");
            form.append("from_date", "<?php echo $reports_list[$i]->rs_subtopic_from_date; ?>");
            form.append("to_date", "<?php echo $reports_list[$i]->rs_subtopic_to_date; ?>");
            form.append("stid", <?php echo $subtopics_list[$k]->exp_id; ?>);
            form.append("tid", <?php echo $reports_list[$i]->rs_tid; ?>);

            var settings = {
                "url": base_url+"api/report-generation/get-es-data",
                "method": "POST",
                "timeout": 0,
                "processData": false,
                "mimeType": "multipart/form-data",
                "contentType": false,
                "data": form
            };

            $.ajax(settings).done(function (response) { 
                //console.log(response);

                am4core.ready(function() {
                    // Themes begin
                    //am4core.useTheme(am4themes_animated);
                    am4core.addLicense("ch-custom-attribution");
                    // Themes end

                    var chart = am4core.create("sub_topic_wordcloud_<?php echo $reports_list[$i]->rs_id.'_'.$subtopics_list[$k]->exp_id; ?>", am4plugins_wordCloud.WordCloud);
                    var series = chart.series.push(new am4plugins_wordCloud.WordCloudSeries());

                    series.accuracy = 4;
                    series.step = 15;
                    series.rotationThreshold = 0.7;
                    series.maxCount = 200;
                    series.minWordLength = 2;
                    series.labels.template.margin(4,4,4,4);
                    series.maxFontSize = am4core.percent(30);
                    series.minFontSize = am4core.percent(4);
                    series.defaultState.transitionDuration = 0;
                    series.interpolationDuration = 0;
                    series.hiddenState.transitionDuration = 0;

                    series.data = eval(response); 
                    series.dataFields.word = "tag";
                    series.dataFields.value = "count";
                    series.labels.template.tooltipText = "{word}: {value}";
                    //series.text = response.trim();

                    series.colors = new am4core.ColorSet();
                    series.colors.passOptions = {}; // makes it loop

                    //series.labelsContainer.rotation = 45;
                    series.angles = [0,-90];
                    series.fontWeight = "700";
                    //chart.exporting.menu = new am4core.ExportMenu();

                    series.events.on("arrangeended", function(ev) {
                        var img;
                        chart.exporting.getImage( "png" ).then( ( data ) => {
                          img = data;
                          //console.log('IMG:'+img);
                          var form = new FormData();
                            form.append("section", "sub_topic_word_cloud");
                            form.append("graph_image", img);
                            form.append("report_id", <?php echo $reports_list[$i]->rs_id; ?>);
                            form.append("report_tid", <?php echo $reports_list[$i]->rs_tid; ?>);
                            form.append("report_uid", <?php echo $reports_list[$i]->rs_uid; ?>);
                            form.append("stid", <?php echo $subtopics_list[$k]->exp_id; ?>);
                            form.append("_token", $("#token").val());

                            var settings = {
                              "url": base_url+"api/report-generation/save-report-graph",
                              "method": "POST",
                              "timeout": 0,
                              "processData": false,
                              "mimeType": "multipart/form-data",
                              "contentType": false,
                              "data": form
                            };

                            $.ajax(settings).done(function (response) {

                            });
                        } );
                    });




                }); // end am4core.ready()
            });
            </script>
            <!---------------------- END: sub topic word cloud --------------------------------->
            
            <!---------------- touch points ------------------------->
            <?php
            //get all touch points
            $touchpoints_data = $touchpoint_obj->get_all_touchpoints_data($subtopics_list[$k]->exp_id);
            //var_dump($touchpoints_data);
            if($touchpoints_data != 'NA')
            {
            ?>
                <div id="tp_barchart_<?php echo $k; ?>" style="width: 500px;"></div>
                <script type="text/javascript">
                var form = new FormData();
                form.append("section", "touchpoints_bar_chart");
                form.append("_token", $("#token").val());
                form.append("pdf_report_data", "yes");
                form.append("from_date", "<?php echo $reports_list[$i]->rs_subtopic_from_date; ?>");
                form.append("to_date", "<?php echo $reports_list[$i]->rs_subtopic_to_date; ?>");
                form.append("stid", <?php echo $subtopics_list[$k]->exp_id; ?>);
                form.append("tid", <?php echo $reports_list[$i]->rs_tid; ?>);

                var settings = {

                  "url": base_url+"api/report-generation/get-es-data",
                  "method": "POST",
                  "timeout": 0,
                  "processData": false,
                  "mimeType": "multipart/form-data",
                  "contentType": false,
                  "data": form
                };

                $.ajax(settings).done(function (response) {
                    //console.log(response);
                    if(response.trim() == 'NA')
                    {
                        $("#touchpoint_container").hide();
                        $("#touchpoint_senti_container").hide();
                    }
                    else
                    {
                        var data = JSON.parse(response);
                        var tpnames = [];
                        var tpcounts = [];

                        for(var i=0; i<data["tp_names"].length; i++)
                        {
                            tpnames.push(data["tp_names"][i]);
                            tpcounts.push(data["tp_counts"][i]);
                        }

                        var options = {
                                series: [{
                                data: tpcounts,
                                name: "Mentions"
                            }],
                            chart: {
                                type: 'bar',
                                toolbar: {
                                    show: false
                                },
                    animations: {
                        enabled: false
                    }
                            },
                            plotOptions: {
                                bar: {
                                    borderRadius: 1,
                                    horizontal: true,
                                    barHeight: '40%',
                                    columnWidth: '30%'
                                }
                            },
                            dataLabels: {
                                enabled: false
                            },
                            xaxis: {
                                categories: tpnames,
                            }
                        };

                        var tpchart<?php echo $k; ?> = new ApexCharts(document.querySelector("#tp_barchart_<?php echo $k; ?>"), options);
                        //chart.render();
                        tpchart<?php echo $k; ?>.render().then(() => {
                            window.setTimeout(function() {
                                tpchart<?php echo $k; ?>.dataURI().then((uri) => {
                                    //console.log(uri);
                                    var form = new FormData();
                                    form.append("section", "touchpoint_chart");
                                    form.append("graph_image", uri["imgURI"]);
                                    form.append("report_id", <?php echo $reports_list[$i]->rs_id; ?>);
                                    form.append("report_tid", <?php echo $reports_list[$i]->rs_tid; ?>);
                                    form.append("report_uid", <?php echo $reports_list[$i]->rs_uid; ?>);
                                    form.append("stid", <?php echo $subtopics_list[$k]->exp_id; ?>);
                                    form.append("_token", $("#token").val());

                                    var settings = {
                                      "url": base_url+"api/report-generation/save-report-graph",
                                      "method": "POST",
                                      "timeout": 0,
                                      "processData": false,
                                      "mimeType": "multipart/form-data",
                                      "contentType": false,
                                      "data": form
                                    };

                                    $.ajax(settings).done(function (response) {

                                    });
                                })
                            }, 1000) 
                        })
                    }

                });    
                </script>
            <?php
            }
            //touch points sentiment graphs
            $touchpoints_data = $touchpoint_obj->get_all_touchpoints_data($subtopics_list[$k]->exp_id);
            if($touchpoints_data != 'NA')
            {
                for($l=0; $l<count($touchpoints_data); $l++)
                {
                    $senti_data = $touchpoint_obj->get_touchpoint_sentiments_data_report($reports_list[$i]->rs_tid, $subtopics_list[$k]->exp_id, $touchpoints_data[$l]->tp_id, $reports_list[$i]->rs_subtopic_from_date, $reports_list[$i]->rs_subtopic_to_date);
            ?>
                <div id="tp_sentichart_<?php echo $k.'_'.$l; ?>"></div>
                <script type="text/javascript">
		                          	
                    var options = {
                          series: [<?php echo $senti_data["pos"]; ?>, <?php echo $senti_data["neg"]; ?>, <?php echo $senti_data["neu"]; ?>],
                          chart: {
                          width: 250,
                          type: 'pie',
                            animations: {
                                enabled: false
                            }
                        },
                        colors: ['#3bdb8b','#fe5a5c', '#5b8eee'],
                        labels: ['Positive', 'Negative', 'Neutral'],
                        legend: false
                        };

                        var chart<?php echo $k.'_'.$l; ?> = new ApexCharts(document.querySelector("#tp_sentichart_<?php echo $k.'_'.$l; ?>"), options);
                        //chart<?php echo $k.'_'.$l; ?>.render();
                        chart<?php echo $k.'_'.$l; ?>.render().then(() => {
                            window.setTimeout(function() {
                                chart<?php echo $k.'_'.$l; ?>.dataURI().then((uri) => {
                                    //console.log(uri);
                                    var form = new FormData();
                                    form.append("section", "tp_senti_chart");
                                    form.append("graph_image", uri["imgURI"]);
                                    form.append("report_id", <?php echo $reports_list[$i]->rs_id; ?>);
                                    form.append("report_tid", <?php echo $reports_list[$i]->rs_tid; ?>);
                                    form.append("report_uid", <?php echo $reports_list[$i]->rs_uid; ?>);
                                    form.append("stid", <?php echo $subtopics_list[$k]->exp_id; ?>);
                                    form.append("tpid", <?php echo $touchpoints_data[$l]->tp_id; ?>);
                                    form.append("_token", $("#token").val());

                                    var settings = {
                                      "url": base_url+"api/report-generation/save-report-graph",
                                      "method": "POST",
                                      "timeout": 0,
                                      "processData": false,
                                      "mimeType": "multipart/form-data",
                                      "contentType": false,
                                      "data": form
                                    };

                                    $.ajax(settings).done(function (response) {

                                    });
                                })
                            }, 1000) 
                        })
                </script>
            <?php
                }
            }
            ?>
            <!---------------- END: touch points ----------------------------------------->
            <?php
            } //end for loop sub topics
        }
        ?>
        <!--------------------------- END: SUB TOPICS ----------------------------------------------->
    <?php
    }
    ?>
    <script type="text/javascript">
        setTimeout(function(){ window.location = base_url+'gen-pdf-report?tok='+$("#token").val()+"&rid=<?php echo base64_encode($reports_list[0]->rs_id); ?>"; }, 540000);
        //360000
    </script>
    <div><button onclick="javascript:window.location='gen-pdf-report?rid=<?php echo base64_encode($reports_list[0]->rs_id); ?>';">Go Ahead</button></div>
@endsection 

{{-- vendor scripts --}} 
@section('vendor-scripts')
<script src="{{asset('vendors/js/charts/apexcharts.js')}}"></script>
@endsection 
@section('page-scripts')
<!--<script src="{{asset('js/scripts/custom.js')}}"></script>-->
@endsection