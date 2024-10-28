<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Session;
use App\Http\Controllers\GeneralFunctionsController;
use Illuminate\Support\Facades\Log;

class EmailsAnalysisController extends Controller
{
    public function __construct()
    {
        $this->gen_func_obj = new GeneralFunctionsController();
    }

    public function emails_analysis_list()
    {
        if(!$this->gen_func_obj->validate_access() || (\Session::get('_loggedin_customer_id') != 418 && \Session::get('_loggedin_customer_id') != 419 && \Session::get('_loggedin_customer_id') != 420))
        {
            return redirect('/');
        }

        $emais = DB::select("SELECT * FROM `email_extraction` ORDER BY id DESC LIMIT 10");

        return view('pages.emails-analysis', ['emails_data' => $emais]);
    }

    public function get_email_analysis_data(Request $request)
    {
        if(isset($request->mode) && $request->mode == 'get_email_analysis')
        {
            if(isset($request->eid) && !empty($request->eid))
            {
                $e_content = DB::select("SELECT content FROM email_extraction WHERE id = ".decrypt($request->eid));

                if(count($e_content) > 0)
                {
                    $this->gen_func_obj = new GeneralFunctionsController();

                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                      CURLOPT_URL => 'http://3.28.184.237:8001/analyze_email/?text='.urlencode($e_content[0]->content),
                      CURLOPT_RETURNTRANSFER => true,
                      CURLOPT_ENCODING => '',
                      CURLOPT_MAXREDIRS => 10,
                      CURLOPT_TIMEOUT => 0,
                      CURLOPT_FOLLOWLOCATION => true,
                      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                      CURLOPT_CUSTOMREQUEST => 'POST',
                    ));

                    $resp = curl_exec($curl);

                    curl_close($curl);

                    $data_array = json_decode(json_decode($resp), true);
                    //Log::info($data_array["Summary"]);

                    $html_response = ''; $counter = 1;

                    foreach($data_array as $key => $value)
                    {
                        if($key == 'Summary')
                            $heading = 'Summary';
                        else if($key == 'PurposeIdentification')
                            $heading = 'Purpose Identification';
                        else if($key == 'ToneAnalysis')
                            $heading = 'Tone Analysis';
                        else if($key == 'SentimentAnalysis')
                            $heading = 'Sentiment Analysis';
                        else if($key == 'ContextualAnalysis')
                            $heading = 'Contextual Analysis';
                        else if($key == 'ActionableItemsIdentification')
                            $heading = 'Actionable Items Identification';
                        else if($key == 'StakeholderAnalysis')
                            $heading = 'Stakeholder Analysis';
                        else if($key == 'FollowupPotential')
                            $heading = 'Followup Potential';
                        else if($key == 'AudienceAnalysis')
                            $heading = 'Audience Analysis';
                        else if($key == 'PrivacyandSecurityAssessment')
                            $heading = 'Privacy and Security Assessment';
                        else if($key == 'CulturalSensitivityCheck')
                            $heading = 'Cultural Sensitivity Check';
                        else if($key == 'SpamAnalysis')
                            $heading = 'Spam Analysis';
                        else
                            $heading = $key;

                        if($counter%2 == 0)
                        {
                            $float = 'right';
                            $counter = 1;
                        }
                        else
                        {
                            $float = 'left';
                            $counter = $counter + 1;
                        }

                        $html_response .= '<div style="width: 48%; float: '.$float.'; margin: 0px 0px 30px 0px; border: 1px solid #f0f0f0; border-radius: 8px;">';
                        $html_response .= '<div style="padding: 10px; background: #f0f0f0;">'.$heading.'</div>';
                        $html_response .= '<div style="padding: 10px; height: 150px; max-height: 200px; overflow-y: auto;">'.nl2br($value).'</div>';
                        $html_response .= '</div>';
                    }

                    $response = $html_response;
                }
                else
                    $response = 'No data found.';

                return response()->json([
                    'adata' => $response,
                    //'test' => $resp_data["Summary"]
                ]); 
            }
            
        }
    }


    //chatbot page
    public function chatbot()
    {
        return view('pages.chatbot');
    }
}
?>