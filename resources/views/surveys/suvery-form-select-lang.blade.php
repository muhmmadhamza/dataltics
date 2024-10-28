<!doctype html>
<html>
    <head>
        <meta  charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
        <title>Survey - Select language</title>

        <link rel="stylesheet" type="text/css" href="{{asset('assets/survey/css/template.css')}}">
        <link rel="stylesheet" type="text/css" href="{{asset('assets/survey/css/style.css')}}">
        <link href="{{asset('assets/survey/css/jquery-ui-1.10.2.custom.min.css')}}" rel="stylesheet" />
        <link href="{{asset('css/bootstrap.min.css')}}" rel="stylesheet" />
        <link rel="stylesheet" type="text/css" href="{{asset('assets/survey/css/rating.css')}}">

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/meyer-reset/2.0/reset.min.css">
        <!-- <link rel="stylesheet" href="css/style3.css"> -->
        <link rel="stylesheet" type="text/css" href="{{asset('assets/survey/css/style3.css')}}">

        <script type="text/javascript" src="{{asset('assets/survey/js/jquery-1.9.1.min.js')}}"></script>
        <script type="text/javascript" src="{{asset('assets/survey/js/jquery-ui-1.10.2.custom.min.js')}}"></script>

        <script type="text/javascript" src="{{asset('assets/survey/js/survey_form.js')}}"></script>
        <link rel="stylesheet" type="text/css" href="{{asset('assets/survey/css/jquery-steps.css')}}">
        <link rel="stylesheet" type="text/css" href="{{asset('assets/survey/css/smiley-style.css')}}">

    </head>
    <body style="background-color: #ffffff;">
        <div id="main">
        <div id="header" style="height: 70px; padding: 6px 0px 0px 0px;">
          <div id="logo" style="height: 0px;">
              <div id="logo_text" style="top:0px;">
                  <h1 style="padding:0px 10px 0px 10px; font: normal 1.5em 'century gothic', arial, sans-serif;"><span class="logo_colour" style="color:white;">Language selection</span></h1>
                  <h2 style="font-size: 85%; color:#f0f0f0; padding-left: 10px;">&nbsp;</h2>
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

        <div id="site_content" style="padding-top:0px;">
            <div id="content" style="margin: 0px auto;">
                <?php 
                if(isset($eng_sid) && !is_null($eng_sid) && isset($ar_sid) && !is_null($ar_sid)) 
                { 
                    if(file_exists(getcwd().'/surveys/survey_logos/'.$s_logo) && is_file(getcwd().'/surveys/survey_logos/'.$s_logo)) 
                    {
                ?>
                        <div style="width:100px; height: 100px; background-image: url('https://dashboard.datalyticx.ai/surveys/survey_logos/<?php echo $s_logo; ?>'); background-size: contain; background-repeat: no-repeat; background-position: center; margin:10px auto;"></div>
                <?php } ?>
                            
                        <div style="text-align: center; padding: 25px 0px 25px 0px;">Select language for the Survey</div>
                        <div style="text-align: center;">
                            <button class="btn btn-primary" onclick="javascript:window.location='https://dashboard.datalyticx.ai/load-survey/<?php echo random_int(999, 9999).$eng_sid.random_int(999, 9999); ?>?ln=<?php echo encrypt('en'); ?>';">English</button>&nbsp;&nbsp;&nbsp;<button class="btn btn-secondary" onclick="javascript:window.location='https://dashboard.datalyticx.ai/load-survey/<?php echo random_int(999, 9999).$ar_sid.random_int(999, 9999); ?>?ln=<?php echo encrypt('ar'); ?>';">عربي</button>
                        </div>
                <?php 
                } 
                else 
                {  
                    echo "Invalid page access";
                } 
                ?>
                
          </div>
        </div>

        <!-- footer -->
        <?php 
        $request = Request::capture();
        $uri = $request->segment(1);
        if($uri == 'load-survey') { ?>
        <!--<div id="footer" style="bottom:0px; position: relative;">Powered by <a href="https://www.datalyticx.ai" target="_blank">Datalyticx</a></div>-->
        <div style="margin: 70px auto; width:255px; height:45px;">
            <div><a href="https://www.datalyticx.ai" target="_blank"><img src="https://dashboard.datalyticx.ai/images/logo/logo-poweredby.png" width="245" height="45" style="border:0px;"></a></div>
        </div>
        <?php } ?>

      </div>
      
    </body>
</html>
