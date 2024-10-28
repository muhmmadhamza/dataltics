<!doctype html>
<html>
    <head>
        <meta  charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
        <title>Thank You</title>
        <link rel="stylesheet" type="text/css" href="{{asset('assets/survey/css/template.css')}}">
        <link rel="stylesheet" type="text/css" href="{{asset('assets/survey/css/style.css')}}">

        <link href="{{asset('assets/survey/css/jquery-ui-1.10.2.custom.min.css')}}" rel="stylesheet" />
        <link href="{{asset('css/bootstrap.min.css')}}" rel="stylesheet" />

        <link rel="stylesheet" type="text/css" href="{{asset('assets/survey/css/rating.css')}}">

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css">

        <script type="text/javascript" src="{{asset('assets/survey/js/jquery-1.9.1.min.js')}}"></script>
        <script type="text/javascript" src="{{asset('assets/survey/js/jquery-ui-1.10.2.custom.min.js')}}"></script>
        <script type="text/javascript" src="{{asset('assets/survey/js/survey_form.js')}}"></script>
    </head>
    <body>
        <div id="main">
            <?php $title = htmlspecialchars($survey->survey_name); ?>
            <?php $subtitle = 'Survey Response Submitted'; ?>
            <div id="header" style="height: 70px; padding: 6px 0px 0px 0px;">
                <div id="logo" style="height: 0px;">
                    <div id="logo_text" style="top:0px;">
                        <h1 style="padding:0px 10px 0px 10px; font: normal 1.5em 'century gothic', arial, sans-serif;"><span class="logo_colour" style="color:white;"><?php echo $title; ?></span></h1>
                        <h2 style="font-size: 85%; color:#f0f0f0; padding-left: 10px;"><?php echo $subtitle;  ?></h2>
                    </div>
                </div>
                <div id="menubar" style="display:none;">
                    <ul id="menu">
                        <li class="selected">
                            <a href="#"><?php //echo $subtitle; ?></a>
                        </li>
                    </ul>
                </div>
            </div>

            <div id="site_content_thanks" style="margin: 0px auto;">
                
                <div id="content">
                    <h2>Response submitted!</h2>
                    <p>Thank you for taking the time to complete the survey. Your feedback is very valuable to us.</p>
                </div>
               
            </div>
            <?php 
            $request = Request::capture();
            $uri = $request->segment(1);
            if($uri == 'survey_thank_you') { ?>
            <!--<div id="footer" style="bottom:0px; position: relative;">Powered by <a href="https://www.datalyticx.ai" target="_blank">Datalyticx</a></div>-->
            <div style="margin: 70px auto; width:255px; height:45px;">
                <div><a href="https://www.datalyticx.ai" target="_blank"><img src="https://dashboard.datalyticx.ai/images/logo/logo-poweredby.png" width="245" height="45" style="border:0px;"></a></div>
            </div>
            <?php } ?>
        </div>
        
        
    </body>
</html>
