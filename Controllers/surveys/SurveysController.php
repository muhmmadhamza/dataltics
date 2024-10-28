<?php

namespace App\Http\Controllers\Surveys;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyResponse;

class SurveysController extends Controller
{
    //
    public function CustomerSurveys(Request $request)
    {
        return view('surveys.customer-surveys');
    }
    
    public function loadSurvey(Request $request)
    {
        $surveyid = substr(substr($request->survey_id, 4), 0, -4); //trim first & last 4 numbers
        $survey = Survey::where('survey_id', $surveyid)->first();
        
        if((isset($survey->survey_arabic_id) && !is_null($survey->survey_arabic_id)) && !isset($request->ln))
        {
            return view('surveys.suvery-form-select-lang', ['eng_sid' => $survey->survey_id, 'ar_sid' => $survey->survey_arabic_id, 's_logo' => $survey->survey_logo]);
        }
        else
            return view('surveys.suvery-form', compact('survey'));
    }
    
    public function PostSurvey(Request $request)
    {
        // $surveyid = substr(substr($request->survey_id, 4), 0, -4); //trim first & last 4 numbers
        $surveyResponse = new SurveyResponse();
        $surveyResponse->survey_id = $request->survey_id ;
        $surveyResponse->time_taken = gmdate('Y-m-d H:i:s');
        $surveyResponse->survey_customer_name = $request->survey_customer_name;
        $surveyResponse->survey_customer_email = $request->survey_customer_email;
        $surveyResponse->survey_customer_phone = $request->survey_customer_phone;
        $surveyResponse->save();

        if($surveyResponse->id) {
            if (! empty($request->question_id)) {
                foreach ($request->question_id as $questionID => $answerArray) {
                    if (! is_array($answerArray)) {
                        $answerArray = [$answerArray];
                    }
                    foreach ($answerArray as $answerValue) {

                        $SurveyAnswer = new SurveyAnswer();
                        $SurveyAnswer->survey_response_id = $surveyResponse->id;
                        $SurveyAnswer->question_id = $questionID;
                        $SurveyAnswer->answer_value = $answerValue;
                        $SurveyAnswer->save();
                    }
                }
            }
        }
        // $this->redirect('survey.Thankyou.php?survey_id=' . rand(1001, 9999).$survey->survey_id.rand(1001, 9999));
        return redirect()->route('survey.Thankyou', rand(1001, 9999).$request->survey_id.rand(1001, 9999));
    }
    
    public function SurveyThankyou(Request $request) 
    {
        $surveyid = substr(substr($request->survey_id, 4), 0, -4); //trim first & last 4 numbers
        $survey = Survey::where('survey_id', $surveyid)->first();
        return view('surveys.survey-thankyou', compact('survey'));
    }
   

}
