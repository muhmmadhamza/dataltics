@extends('layouts.fullLayoutMaster')
{{-- page Title --}}
@section('title','Sohar report stats') 
{{-- vendor css --}}
@section('vendor-styles') @endsection 
@section('page-styles') @endsection 

@section('content')

<div class="col-sm-12">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td colspan="10" style="font-size: 18px; font-weight: bold;">&nbsp;</td>
        </tr>
        <tr>
            <td colspan="10" style="font-size: 18px; font-weight: bold; text-align: center;">Community traffic</td>
        </tr>
        <tr>
            <td colspan="10" style="font-size: 18px; font-weight: bold;">&nbsp;</td>
        </tr>
        <tr style="border-bottom:1px solid #000;">
            <td style="text-align: center; font-weight: bold; line-height: 30px;">Community size</td>
            <td style="text-align: center; font-weight: bold; line-height: 30px;">Engagement</td>
            <td style="text-align: center; font-weight: bold; line-height: 30px;">Shares</td>
            <td style="text-align: center; font-weight: bold; line-height: 30px;">Likes</td>
            <td style="text-align: center; font-weight: bold; line-height: 30px;">Comments</td>
            <td style="text-align: center; font-weight: bold; line-height: 30px;">Impressions</td>
            <td style="text-align: center; font-weight: bold; line-height: 30px;">No of posts</td>
            <td style="text-align: center; font-weight: bold; line-height: 30px;">Enquiries / Complaints</td>
            <td style="text-align: center; font-weight: bold; line-height: 30px;">Website users</td>
            <td style="text-align: center; font-weight: bold; line-height: 30px;">Branch CSAT score</td>
        </tr>
        <tr style="border-bottom:1px solid #ccc;">
            <td style="text-align: center; line-height: 30px;"><?php echo $followers_per_diff; ?></td>
            <td style="text-align: center; line-height: 30px;"><?php echo $eng_per_diff; ?></td>
            <td style="text-align: center; line-height: 30px;"><?php echo $shares_per_diff; ?></td>
            <td style="text-align: center; line-height: 30px;"><?php echo $likes_per_diff; ?></td>
            <td style="text-align: center; line-height: 30px;"><?php echo $comments_per_diff; ?></td>
            <td style="text-align: center; line-height: 30px;">---</td>
            <td style="text-align: center; line-height: 30px;"><?php echo $mentions_per_diff; ?></td>
            <td style="text-align: center; line-height: 30px;">---</td>
            <td style="text-align: center; line-height: 30px;">---</td>
            <td style="text-align: center; line-height: 30px;"><?php echo $surveys_csat; ?></td>
        </tr>
        <tr style="border-bottom:1px solid #ccc;">
            <td style="text-align: center; line-height: 30px;"><?php echo $current_followers; ?></td>
            <td style="text-align: center; line-height: 30px;"><?php echo $engagement; ?></td>
            <td style="text-align: center; line-height: 30px;"><?php echo $shares; ?></td>
            <td style="text-align: center; line-height: 30px;"><?php echo $likes; ?></td>
            <td style="text-align: center; line-height: 30px;"><?php echo $comments; ?></td>
            <td style="text-align: center; line-height: 30px;">---</td>
            <td style="text-align: center; line-height: 30px;"><?php echo $mentions; ?></td>
            <td style="text-align: center; line-height: 30px;">---</td>
            <td style="text-align: center; line-height: 30px;">---</td>
            <td style="text-align: center; line-height: 30px;"><?php echo $surveys_info; ?></td>
        </tr>
    </table>
</div>

{{--channel sentiments--}}
<div class="row" style="margin-top: 40px;">
    <div class="col-sm-8">
        <div id="channel_sentiments"></div>
    </div>
</div>

{{-- Sentiments --}}
<div class="row" style="margin-top:50px;">    
    <div class="col-6" id="senti_container">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Sentiments data</h4>
            </div>
            <div class="card-body" style="min-height: 506px;">
                <script src="{{asset('vendors/js/charts/apexcharts.js')}}"></script>
                <?php
                $tids = explode(",", "2325,2324,2321,2320,2319,2318"); //These competitor ids taken from Share of voice competitor analysis

                for($i=0; $i<count($tids); $i++)
                {

                    $senti_data = explode("|", trim($sohar_report_obj->get_topic_sentiments_data($tids[$i], $month_name)));
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
                    <div style="text-align: center; height: 50px;"><?php echo $sohar_report_obj->get_topic_name($tids[$i]); ?></div>
                </div>
                <?php
                }

                ?>

            </div>
        </div>
    </div>

    
</div>
{{--reach chart--}}
<div class="row" style="margin-top:100px;">
    <div class="col-sm-6">
        <div id="reach_chart"></div>
    </div>
</div>
<input type="hidden" name="month_name" id="month_name" value="<?php echo $month_name; ?>">
@endsection 

{{-- vendor scripts --}} 
@section('vendor-scripts')
<script src="{{asset('vendors/js/charts/apexcharts.js')}}"></script>
@endsection 
@section('page-scripts')
<script type="text/javascript">
    $(document).ready(function() { 
        var base_url = 'https://dashboard.datalyticx.ai/';
        //sentiments chart
        var form = new FormData();
        form.append("section", "channel_sentiments");
        form.append("_token", '{{csrf_token()}}');
        form.append("month", $("#month_name").val());
        
        var settings = {
            "url": base_url+"sohar-report-statistics/sources-sentiments-data",
            "method": "POST",
            "timeout": 0,
            "processData": false,
            "mimeType": "multipart/form-data",
            "contentType": false,
            "data": form
        };

        $.ajax(settings).done(function (response) {
            
            var resp_data = JSON.parse(response);
            
            var my_series = [];
            var sdata = [];
            
            var mykeys = Object.keys(resp_data);
            var pos_array = []; var neg_array = []; var neu_array = [];
            
            for(i=0; i<mykeys.length; i++)
            {
                pos_array.push(resp_data[mykeys[i]]["positive"]);
                neg_array.push(resp_data[mykeys[i]]["negative"]);
                neu_array.push(resp_data[mykeys[i]]["neutral"]);
            }
            
            var options = {
                series: [{
                    name: 'Positive',
                    data: pos_array
                  }, {
                    name: 'Negative',
                    data: neg_array
                  }, {
                    name: 'Neutral',
                    data: neu_array
                  }],
                chart: {
                    type: 'bar',
                    height: 400,
                    stacked: true,
                    events: {
                        dataPointSelection: function(event, chartContext, config) {
                        }
                    }
                    //stackType: '100%'
                },
                colors: ['#67c99c','#ce3a60', '#f8d774'],
                labels: mykeys,
                grid: {
                    show: false,
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 4,
                    },
                },
                stroke: {
                    width: 1,
                    colors: ['#fff']
                },
                title: {
                    //text: '100% Stacked Bar'
                },
                xaxis: {
                    categories: mykeys,
                    title: {
                        text: 'Mentions',
                        offsetX: 0,
                        offsetY: 0,
                        style: {
                            color: undefined,
                            fontSize: '12px',
                            fontFamily: 'Helvetica, Arial, sans-serif',
                            fontWeight: 600,
                            cssClass: 'apexcharts-xaxis-title',
                        },
                    },
                },
                yaxis: {
                    title: {
                        text: 'Source',
                        offsetX: 0,
                        offsetY: 0,
                        style: {
                            color: undefined,
                            fontSize: '12px',
                            fontFamily: 'Helvetica, Arial, sans-serif',
                            fontWeight: 600,
                            cssClass: 'apexcharts-xaxis-title',
                        },
                    },
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return val;
                        }
                    }
                },
                fill: {
                    opacity: 1
                },
                legend: {
                    position: 'bottom',
                    horizontalAlign: 'left',
                    offsetX: 40
                }
            };

            var chart = new ApexCharts(document.querySelector("#channel_sentiments"), options);
            chart.render();
            
        });
        //sentiments chart end
        
        //reach chart
        var form = new FormData();
        form.append("section", "topics_reach");
        form.append("_token", '{{csrf_token()}}');
        form.append("month", $("#month_name").val());
        
        var settings = {
            "url": base_url+"sohar-report-statistics/topics-reach",
            "method": "POST",
            "timeout": 0,
            "processData": false,
            "mimeType": "multipart/form-data",
            "contentType": false,
            "data": form
        };

        $.ajax(settings).done(function (response) {
            var resp_data = JSON.parse(response);
            var data_array = []; var keys_array = [];
            
            for (var key in resp_data) //gives keys of array
            {
                data_array.push(resp_data[key]);
                keys_array.push(key);
            }
            
            var options = {
                series: data_array,
                chart: {
                    type: 'donut',
                    toolbar: {
                       show: true
                    }
                },
                labels: keys_array,
                colors: ['#3bdb8b','#fe5a5c', '#5b8eee', '#FA5B00', '#AD6CFA', '#FAC66C'],
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            width: 250
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }]
            };

            var chart = new ApexCharts(document.querySelector("#reach_chart"), options);
            chart.render();
        });
        //reach chart end
    });
</script>
@endsection