<!doctype html>
<html>
    <head>
        <meta  charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
        <title>Survey - <?php //echo htmlspecialchars($survey->survey_name); ?></title>

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

        <link rel="stylesheet" type="text/css" href="{{asset('assets/survey/js/survey_form.js')}}">
        <link rel="stylesheet" type="text/css" href="{{asset('assets/survey/css/jquery-steps.css')}}">
        <link rel="stylesheet" type="text/css" href="{{asset('assets/survey/css/smiley-style.css')}}">

    </head>
    <body style="background-color: #ffffff;">
    <?php 
      use Illuminate\Http\Request;
    ?>
      <div id="main">
        <?php //$title = htmlspecialchars($survey->survey_name); 
            $title = '';
            if(!empty($survey))
              $title = htmlspecialchars($survey->survey_name);
         ?> 
        <?php $subtitle = 'Survey Response Form'; ?>
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

        <div id="site_content" style="padding-top:0px;">
          <div id="content" style="margin: 0px auto;">
            <?php if(!empty($survey)) { ?>
            <form id="survey_form" action="{{route('post.survey')}}" method="post">
              @csrf
                <input type="hidden" id="action" name="action" value="add_survey_response" />
                <input type="hidden" id="survey_id" name="survey_id" value="<?php echo htmlspecialchars($survey->survey_id); ?>" />
                <?php
                if(file_exists(getcwd().'/survey_logos/'.$survey->survey_logo) && is_file(getcwd().'/survey_logos/'.$survey->survey_logo)) {
                ?>
                <div style="width:100px; height: 100px; background-image: url('https://dashboard.datalyticx.ai/surveys/survey_logos/<?php echo $survey->survey_logo; ?>'); background-size: contain; background-repeat: no-repeat; background-position: center; margin:0px auto;"></div>
                <?php } ?>
                <?php
                if($survey->survey_ask_customer_details == 'y')
                {
                ?>
                    <div style="padding:15px 0px 25px 0px;">
                        <div style="text-align: center; font-size: 16px; font-weight: bold;">Personal details</div>
                        <div style="">
                            <label>Name:</label>
                            <input type="text" name="survey_customer_name" id="survey_customer_name" style="width: 100%;">
                        </div>
                        <div style="padding-top:5px;">
                            <label>Email:</label>
                            <input type="text" name="survey_customer_email" id="survey_customer_email" style="width: 100%;">
                        </div>
                        <div style="padding-top:5px;">
                            <label>Phone:</label>
                            <input type="text" name="survey_customer_phone" id="survey_customer_phone" style="width: 100%;">
                        </div>
                    </div>
                <?php    
                }
                ?>
                <div class="step-app" id="demo">
                  <ul class="step-steps" style="">
                      <?php $ii = 1; foreach ($survey->questions as $i => $question) { ?>
                      <li data-step-target="step<?php echo $ii; ?>">Q<?php echo $ii; ?></li>
                      <?php $ii += 1; } ?>
                  </ul>
                  <div class="step-content">
                      <?php $ii = 1; foreach ($survey->questions as $i => $question) { ?>
                      <div class="step-tab-panel" data-step="step<?php echo $ii; ?>">
                          <div style="border-bottom: 1px dotted; padding-bottom: 15px;">
                              <h4 class="question_text" style="color:#6cafc4; font-size: 19px; padding-bottom: 12px;" data-question_id="<?php echo htmlspecialchars($question->question_id); ?>" data-question_type="<?php echo htmlspecialchars($question->question_type); ?>" data-is_required="<?php echo htmlspecialchars($question->is_required); ?>"><?php echo htmlspecialchars($question->question_text); ?></h4>
                              <?php if (in_array($question->question_type, ['radio', 'checkbox', 'csat', 'nps', 'rate15'])): ?>
                                  <span <?php if ($question->is_rate_service) echo 'id="smileys"'; ?>>
                                          <?php
                                          foreach ($question->choices as $j => $choice):
                                              $question_html_id = 'choice_' . htmlspecialchars($question->question_id) . '_' . htmlspecialchars($choice->choice_id);
                                          
                                              if ($question->is_rate_service == 1 && $question->question_type == 'csat') 
                                              {                                                            
                                                    ?>
                                              <input id="<?php echo $question_html_id; ?>" type="radio" name="question_id[<?php echo htmlspecialchars($question->question_id); ?>][]" value="<?php echo htmlspecialchars($choice->choice_text); ?>" class="<?php echo htmlspecialchars($choice->choice_text); ?>" />
                                              
                                                  <?php
                                              } else if ($question->is_nps_service == 1 && $question->question_type == 'nps') { 
                                                  ?>
                                              <label style="display:inline-block;">
                                                  <input id="<?php echo $question_html_id; ?>" type="radio" name="question_id[<?php echo htmlspecialchars($question->question_id); ?>][]" value="<?php echo htmlspecialchars($choice->choice_text); ?>">
                                                  <span style="font-size: 0.9rem;"><?php echo htmlspecialchars($choice->choice_text); ?></span>
                                              </label>
                                              <?php
                                              } else if ($question->question_type == 'rate15') { 
                                                  ?>
                                              <label style="display:inline-block;">
                                                  <input id="<?php echo $question_html_id; ?>" type="radio" name="question_id[<?php echo htmlspecialchars($question->question_id); ?>][]" value="<?php echo htmlspecialchars($choice->choice_text); ?>">
                                                  <span style="font-size: 0.9rem;"><?php echo htmlspecialchars($choice->choice_text); ?></span>
                                              </label>
                                              <?php } else { ?>
                                              <label>
                                                  <input id="<?php echo $question_html_id; ?>" type="<?php echo htmlspecialchars($question->question_type); ?>" name="question_id[<?php echo htmlspecialchars($question->question_id); ?>][]" value="<?php echo htmlspecialchars($choice->choice_text); ?>">
                                                  <span style="font-size: 0.9rem;"><?php echo htmlspecialchars($choice->choice_text); ?></span>
                                              </label>
                                              <?php
                                          }
                                          ?>

                                  <?php endforeach; ?></span>
                              <?php elseif ($question->question_type == 'input'): ?>
                                  <input type="text" name="question_id[<?php echo htmlspecialchars($question->question_id); ?>]" value="" style="width:100%;" />
                          <?php elseif ($question->question_type == 'textarea'): ?>
                                  <textarea name="question_id[<?php echo htmlspecialchars($question->question_id); ?>]" style="width:100%; height:120px;"></textarea>
                              <?php endif; ?>
                          </div>
                      </div>
                      <?php $ii += 1; } ?>
                  </div>
                  <div class="step-footer">
                      <button data-step-action="prev" class="step-btn">Previous</button>
                      <button data-step-action="next" class="step-btn">Next</button>
                      <?php if (!isset($_GET["p"])) { ?>
                          <button data-step-action="finish" class="step-btn">Finish</button><!--<button id="submitButton" name="submitButton" <?php if (isset($_GET["p"])) echo 'style="display:none;"'; ?>>Submit</button>-->
                      <?php } ?>
                      
                  </div>
                </div>
                
            </form>
            <?php } else {  
                echo "No survay found!";
            } ?>

            <?php if (isset($statusMessage)): ?>
                <p class="error"><?php echo htmlspecialchars($statusMessage); ?></p>
            <?php endif; ?>
            
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
      <script src="https://code.jquery.com/jquery-latest.min.js"></script>
      <script src="https://cdn.jsdelivr.net/npm/jquery.steps@1.1.1/dist/jquery-steps.min.js"></script>
      <link rel="stylesheet" type="text/css" href="{{asset('assets/survey/js/smiley-rating.js')}}">
      <script>
          $('#demo').steps({
              onFinish: function () {
                  //alert('Wizard Completed');
                  $("#survey_form").submit();
              }
          });
      </script>
    </body>
</html>
