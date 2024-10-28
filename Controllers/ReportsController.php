<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Elasticsearch\ClientBuilder;
use App\Http\Controllers\GeneralFunctionsController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\SubTopicController;
use App\Http\Controllers\TouchpointController;
use Crypt;
use Mpdf\Mpdf;
use App\Http\Controllers\EmailListingController;
//use Mpdf;

class ReportsController extends Controller
{
    public function __construct()
    {
        $this->gen_func_obj = new GeneralFunctionsController();
        $this->cus_obj = new CustomerController();
        $this->topic_obj = new TopicController();
        $this->subtopic_obj = new SubTopicController();
        $this->touchpoint_obj = new TouchpointController();
        $this->login_obj = new LoginController();
        
        \Session::put('_loggedin_customer_id', '001'); //This line is added for report auto generate process.
    }

    public function load_reports_page(Request $request)
    {
         $loggedin_user_id = $this->cus_obj->get_parent_account_id();
         
        //check for user login
        if(!$this->gen_func_obj->validate_access() || $loggedin_user_id == '001')
        {
            $this->login_obj->logout_user();
            return redirect('/');
        }     
        
        if($loggedin_user_id != '001')
        {
            $topics_list = $this->topic_obj->get_topics_list($loggedin_user_id);
        
            $reports_list = DB::select("SELECT * FROM reports_settings WHERE rs_uid = ".$loggedin_user_id." AND rs_is_auto_monthly_report != 'yes' ORDER BY rs_id DESC");

            return view('pages.reports', ['topics_data' => $topics_list, 'reports_list' => $reports_list, 'topic_obj' => $this->topic_obj, 'cust_obj' => $this->cus_obj]);
        }
        else
        {
            $this->login_obj->logout_user();
            return redirect('/');
        }
    }
    
    public function save_report_graph(Request $request) //called from auto generated graphs page
    {
        if(isset($request["stid"]) && !empty($request["stid"]))
        {
            if(isset($request["tpid"]) && !empty($request["tpid"])) //mean touch point data coming
                DB::insert("INSERT INTO reports_data SET data_rid = ".$request["report_id"].", data_topic_id = ".$request["report_tid"].", data_cx_id = ".$request["stid"].", data_tp_id = ".$request["tpid"].", data_uid = ".$request["report_uid"].", data_section = '".$request["section"]."', data_image = '".$request["graph_image"]."'");
            else //means sub topic data coming
                DB::insert("INSERT INTO reports_data SET data_rid = ".$request["report_id"].", data_topic_id = ".$request["report_tid"].", data_cx_id = ".$request["stid"].", data_uid = ".$request["report_uid"].", data_section = '".$request["section"]."', data_image = '".$request["graph_image"]."'");
        }
        else
        {
            //means main topic data is coming
            DB::insert("INSERT INTO reports_data SET data_rid = ".$request["report_id"].", data_topic_id = ".$request["report_tid"].", data_uid = ".$request["report_uid"].", data_section = '".$request["section"]."', data_image = '".$request["graph_image"]."'");
        }
    }
    
    public function get_subtopics_list_string(Request $request)
    {
        $subtopics_list = $this->topic_obj->get_sub_topics($request["tid"]);
        
        if(count($subtopics_list) > 0)
        {
            $st_str = '';
            
            for($i=0; $i<count($subtopics_list); $i++)
            {
                $st_str .= $subtopics_list[$i]->exp_id.','.$subtopics_list[$i]->exp_name.'|';
            }
            
            return response()->json([
                            'st_str' => substr($st_str, 0, -1)
                        ]);
        }
        else
        {
            return response()->json([
                            'st_str' => 'NA'
                        ]);
        }
            
    }
    
    public function create_auto_monthly_report_record(Request $request)
    {
        //This code is run via cron on monthly basis.
        //Purpose of this is to add auto report generation record for specific topic & customer in reports db
        
        $tlist = DB::select("SELECT topic_id, topic_user_id FROM customer_topics WHERE topic_send_monthly_report = 'yes'");
        
        if(count($tlist) > 0)
        {
            for($i=0; $i<count($tlist); $i++)
            {
                DB::insert("INSERT INTO reports_settings SET rs_uid = ".$tlist[$i]->topic_user_id.", rs_uid_loggedin = ".$tlist[$i]->topic_user_id.", rs_tid = ".$tlist[$i]->topic_id.", rs_bg_color = '#000000', rs_font_color = '#ffffff', rs_logo = '', rs_bg_image_first_page = 'default-report-img.jpg', rs_bg_image_last_page = 'default-report-img.jpg', rs_topic_from_date = '".date('Y-m-01', strtotime('-1 MONTH'))."', rs_topic_to_date = '".date('Y-m-t', strtotime('-1 MONTH'))."', rs_subtopic_ids = 'ALL', rs_subtopic_bg_color = '#000000', rs_subtopic_font_color = '#ffffff', rs_subtopic_bg_image = 'default-report-img.jpg', rs_subtopic_from_date = '".date('Y-m-01', strtotime('-1 MONTH'))."', rs_subtopic_to_date = '".date('Y-m-t', strtotime('-1 MONTH'))."', rs_req_time = NOW(), rs_status = 'p', rs_is_auto_monthly_report = 'yes'"); //default
            }
        }
    }
    
    public function generate_graphs_for_reports(Request $request) //called from cron page
    {
        //Get only one report generation setting at a time.
        $reports_list = DB::select("SELECT * FROM reports_settings WHERE rs_status = 'p' ORDER BY rs_id ASC LIMIT 1");  //AND rs_uid = 4
        //$reports_list = DB::select("SELECT * FROM reports_settings WHERE rs_id = 24");  //AND rs_uid = 4
        
        if(count($reports_list) > 0)
        {
            return view('pages.ggfr', ["reports_list" => $reports_list, "topic_obj" => $this->topic_obj, "touchpoint_obj" => $this->touchpoint_obj]);
        }
        else
            echo 'No pending report to generate';
    }
    
    public function generate_pdf_report(Request $request)
    {
        if(1) //csrf_token() == $request["tok"]
        {
            $report_id = base64_decode($request["rid"]);
            $pdf_save_path = public_path(). '/dashboard-reports/';
            $report_images = public_path(). '/reports-images/';
            $report_image_url = 'https://'.$request->getHost().'/reports-images/';
            
            $rs_bg_image_first_page = '';
            
            $html_opening_tags = '<html><head><title></title><body>';

            $html_closing_tags = '</body></html>';
            
            

            $report_data = DB::select("SELECT * FROM reports_settings WHERE rs_id = ".$report_id);
            
            if(count($report_data) > 0)
            {
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'orientation' => 'L', 
                    'format' => 'A4',
                    'margin_top' => 0,
                    'margin_right' => 0,
                    'margin_bottom' => 0,
                    'margin_left' => 0,
                    'default_font' => 'Avenir'
                ]);
                $mpdf->SetFont('Avenir');
                                
                $stylesheet = file_get_contents(public_path().'/css/pdf-css/pdf-css.css'); // external css
                $mpdf->WriteHTML($stylesheet,1);
                
                $footer_html = '<div style="width: 100%; height: 30px; background:#f0f0f0; position: absolute; margin-top: 700px;">Hi</div>';
                
                //First page
                $mpdf->AddPage();
                
                if(file_exists($report_images.$report_data[0]->rs_bg_image_first_page) && is_file($report_images.$report_data[0]->rs_bg_image_first_page))
                {
                    $rs_bg_image_first_page = $report_images.$report_data[0]->rs_bg_image_first_page;
                    $mpdf->SetDefaultBodyCSS('background', "url('".$rs_bg_image_first_page."')");
                    $mpdf->SetDefaultBodyCSS('background-image-resize', 6);
                }
                
                $content_html = '<div style="width: 100%; height: 100%; min-height: 100%;">';
                
                //logo
                $rs_logo = '';
                if(file_exists($report_images.$report_data[0]->rs_logo) && is_file($report_images.$report_data[0]->rs_logo))
                {
                    $rs_logo = $report_images.$report_data[0]->rs_logo;
                }
                
                $content_html .= '<div style="width: 100%; padding-top: 100px; padding-left: 50px;">'; //This is done because margin are not supported on floating divs
                    $content_html .= '<div style="float: left; width: 250px; height: 250px; background: url(\''.$rs_logo.'\') no-repeat; background-position: center; background-size: contain;"></div>';
                    $content_html .= '<div style="float: right; font-size: 35px; width: 500px; text-align: right; padding-right: 50px; color: '.$report_data[0]->rs_font_color.';">Media monitoring report<br><span style="font-size: 25px;">'.date("jS M, Y", strtotime($report_data[0]->rs_topic_from_date)).' - '.date("jS M, Y", strtotime($report_data[0]->rs_topic_to_date)).'</span></div>';
                    $content_html .= '<div style="margin-top: 360px; width: 100%; text-align: right; padding-right: 50px;"><img src="'.$report_images.'datalyticx-logo.png" style="width: 250px;"></div>';
                $content_html .= '</div>';
                
                $content_html .= '</div>';
                
                $pdf_html = mb_convert_encoding($html_opening_tags.$content_html.$html_closing_tags, 'UTF-8', 'UTF-8');                
                $mpdf->WriteHTML($pdf_html, 2);
                //END: First page
                
                //Mentions for main topic
                $content_html = '<div style="width: 100%; height: 100%; min-height: 100%;">';
                
                $mentions_data = explode("|", $this->get_data_for_report_from_es('main_topic_mentions', $report_data[0]->rs_tid, '', $report_data[0]->rs_topic_from_date, $report_data[0]->rs_topic_to_date, ''));
                
                $comments_data = explode("|", $this->get_data_for_report_from_es('main_topic_comments', $report_data[0]->rs_tid, '', $report_data[0]->rs_topic_from_date, $report_data[0]->rs_topic_to_date, ''));
                
                $reach_data = explode("|", $this->get_data_for_report_from_es('main_topic_reach', $report_data[0]->rs_tid, '', $report_data[0]->rs_topic_from_date, $report_data[0]->rs_topic_to_date, ''));
                
                $content_html .= '<div style="width: 30%; height: 100%; float: left; color: '.$report_data[0]->rs_font_color.'; background:'.$report_data[0]->rs_bg_color.';">'
                    . '<div style="width: 100%; text-align: center; font-size: 40px; padding-top: 20px;">Mentions</div>'
                    . '<div style="width: 250px; height: 50px; margin: 100px auto 0px auto;">'
                    . '<div style="font-size: 24px;">HIGHLIGHTS</div>'
                    . '<div style="margin-top: 50px; font-size: 20px;">&bull; '.$mentions_data[0].' <b>mentions</b> found with estimated reach of '.$reach_data[0].' <b>impressions</b></div>'
                    . '<div style="margin-top: 50px; font-size: 20px;">&bull; '.$mentions_data[1].' in <b>mentions</b> compared to previous period.</div>'
                    . '<div style="margin-top: 50px; font-size: 20px;">&bull; '.$reach_data[1].' in <b>estimated reach</b> compared to previous period.</div>'
                    . '</div>'
                    . '<div style="margin-top: 115px; margin-left: 13px;"><img src="'.$report_images.'logo-d.png" style="width: 80px;"></div>'
                    . '</div>';
                
                //fetch main mentions graph
                $men_graph = DB::select("SELECT data_image FROM reports_data WHERE data_section = 'main_topic_mentions_graph' AND data_rid = ".$report_id);                
                if(count($men_graph) > 0)
                    $src_img = $men_graph[0]->data_image;
                else
                    $src_img = '';
                
                $content_html .= '<div style="width: 70%; height: 100%; float: left;">'
                    . '<div style="width: 100%; height: 10px;"></div>'
                    . '<div style="width: 100px; height: 100px; background: url(\''.$rs_logo.'\') no-repeat; background-position: center; background-size: contain; position: absolute; margin-right: 10px; float: right;"></div>'
                    . '<div style="width: 96%; height: 50px; margin: 30px auto 0px auto;">'
                    . '<div style="width: 33%; height: 50px; float: left;"><span style="font-size: 16px;">Mentions:</span> <span style="font-size: 19px;">'.$mentions_data[0].'</span><br><span style="font-size: 13px; color: #d4d4d4;">'.$mentions_data[1].'</span></div>'
                    . '<div style="width: 33%; height: 50px; float: left;"><span style="font-size: 16px;">Comments:</span> <span style="font-size: 19px;">'.$comments_data[0].'</span><br><span style="font-size: 13px; color: #d4d4d4;">'.$comments_data[1].'</span></div>'
                    . '<div style="width: 34%; height: 50px; float: right; text-align: right;"><span style="font-size: 16px;">Estimated reach:</span> <span style="font-size: 19px;">'.$reach_data[0].'</span><br><span style="font-size: 13px; color: #d4d4d4;">'.$reach_data[1].'</span></div>'
                    . '<div style="clear: both;"></div>'
                    . '<div style="width: 100%;"><img src="'.$src_img.'"></div>'
                    . '<div style="width: 100%; margin-top: 40px;"></div>'
                    . '<div style="font-size: 20px;">Main data channels</div>';
                
                $channels_data = explode("|", $this->get_data_for_report_from_es('main_topic_chanels', $report_data[0]->rs_tid, '', $report_data[0]->rs_topic_from_date, $report_data[0]->rs_topic_to_date, ''));
                
                $content_html .= '<div><table style="width: 100%;">'
                    . '<tr><td style="height: 70px; text-align: center; border-right: 1px solid #d4d4d4; border-bottom: 1px solid #d4d4d4;"><span style="font-size: 20px;">'.number_format($channels_data[7]).'</span><br><span style="font-size: 14px; color: #d4d4d4;">Facebook</span></td>'
                    . '<td style="text-align: center; border-right: 1px solid #d4d4d4; border-bottom: 1px solid #d4d4d4;"><span style="font-size: 20px;">'.number_format($channels_data[2]).'</span><br><span style="font-size: 14px; color: #d4d4d4;">Twitter</span></td>'
                    . '<td style="text-align: center; border-right: 1px solid #d4d4d4; border-bottom: 1px solid #d4d4d4;"><span style="font-size: 20px;">'.number_format($channels_data[3]).'</span><br><span style="font-size: 14px; color: #d4d4d4;">Instagram</span></td>'
                    . '<td style="text-align: center; border-bottom: 1px solid #d4d4d4; border-right: 1px solid #d4d4d4;"><span style="font-size: 20px;">'. number_format($channels_data[5]).'</span><br><span style="font-size: 14px; color: #d4d4d4;">Reddit</span></td>'
                    . '<td style="text-align: center; border-bottom: 1px solid #d4d4d4;"><span style="font-size: 20px;">'. number_format($channels_data[8]).'</span><br><span style="font-size: 14px; color: #d4d4d4;">Pinterest</span></td></tr>'
                    . '<tr><td style="height: 70px; text-align: center; border-right: 1px solid #d4d4d4;"><span style="font-size: 20px;">'.number_format($channels_data[0]).'</span><br><span style="font-size: 14px; color: #d4d4d4;">Videos</span></td>'
                    . '<td style="text-align: center; border-right: 1px solid #d4d4d4;"><span style="font-size: 20px;">'.number_format($channels_data[1]).'</span><br><span style="font-size: 14px; color: #d4d4d4;">News</span></td>'
                    . '<td style="text-align: center; border-right: 1px solid #d4d4d4;"><span style="font-size: 20px;">'.number_format($channels_data[4]).'</span><br><span style="font-size: 14px; color: #d4d4d4;">Blogs</span></td>'
                    . '<td style="text-align: center; border-right: 1px solid #d4d4d4;"><span style="font-size: 20px;">'.number_format($channels_data[6]).'</span><br><span style="font-size: 14px; color: #d4d4d4;">Tumblr</span></td>'
                    . '<td style="text-align: center;"><span style="font-size: 20px;">'.number_format($channels_data[9]).'</span><br><span style="font-size: 14px; color: #d4d4d4;">Printmedia</span></td></tr>'
                    . '</table></div>';
                
                $lang_data = explode("|", $this->get_data_for_report_from_es('main_topic_language', $report_data[0]->rs_tid, '', $report_data[0]->rs_topic_from_date, $report_data[0]->rs_topic_to_date, ''));
                
                $content_html .= '<div style="width: 100%; margin-top: 20px;"></div>';
                /*$content_html .= '<div style="width: 50%;"><table width="100%">'
                    . '<tr><td style="padding-left: 5px; font-size: 20px;">Languages</td></tr>'
                    . '<tr><td>'
                    . '<table width="100%" cellspacing="0" style="border: 0px;"><tr><td width="33%" style="text-align:center; background:#ababab; color:#ffffff; padding:5px; font-size: 18px; height: 30px;">English</td><td style="text-align:center; background:#6b6b6b; color:#ffffff; padding:5px; font-size: 18px; height: 30px;">Arabic</td><td style="text-align:center; background:#575757; color:#ffffff; padding:5px; font-size: 18px; height: 30px;">Others</td></tr><tr><td style="text-align:center; background:#ababab; color:#ffffff; padding:10px; font-size:18px; height: 35px; font-weight: bold;">'.$lang_data[0].'%</td><td style="text-align:center; background:#6b6b6b; color:#ffffff; padding:10px; font-size:18px; height: 35px; font-weight: bold;">'.$lang_data[1].'%</td><td style="text-align:center; background:#575757; color:#ffffff; padding:10px; font-size:18px; height: 35px; font-weight: bold;">'.$lang_data[2].'%</td></tr></table>'
                    . '</td></tr>'
                    . '</table></div>';*/
                
                //Language and AVE
                $digital_mentions = $this->get_data_for_report_from_es('digital_mentions', $report_data[0]->rs_tid, '', $report_data[0]->rs_topic_from_date, $report_data[0]->rs_topic_to_date, '');
                
                $conventional_mentions = $this->get_data_for_report_from_es('conventional_mentions', $report_data[0]->rs_tid, '', $report_data[0]->rs_topic_from_date, $report_data[0]->rs_topic_to_date, '');
                
                $content_html .= '<div style="width: 100%; padding: 20px 0px 20px 0px;">';
                $content_html .= '<table width="100%" cellspacing="0" style="border: 0px;">'
                    . '<tr><td width="60%" style="padding-left: 5px; font-size: 20px;">Languages</td><td width="40%" style="padding-left: 5px; font-size: 20px;">AVE (USD)</td></tr>'
                    . '<tr><td width="60%"><table width="100%" cellspacing="0" style="border: 0px;"><tr><td width="33%" style="text-align:center; background:#ababab; color:#ffffff; padding:5px; font-size: 18px; height: 30px;">English</td><td style="text-align:center; background:#6b6b6b; color:#ffffff; padding:5px; font-size: 18px; height: 30px;">Arabic</td><td style="text-align:center; background:#575757; color:#ffffff; padding:5px; font-size: 18px; height: 30px;">Others</td></tr><tr><td style="text-align:center; background:#ababab; color:#ffffff; padding:10px; font-size:18px; height: 35px; font-weight: bold;">'.$lang_data[0].'%</td><td style="text-align:center; background:#6b6b6b; color:#ffffff; padding:10px; font-size:18px; height: 35px; font-weight: bold;">'.$lang_data[1].'%</td><td style="text-align:center; background:#575757; color:#ffffff; padding:10px; font-size:18px; height: 35px; font-weight: bold;">'.$lang_data[2].'%</td></tr></table></td>'
                    . '<td width="40%"><table width="100%" cellspacing="0" style="border: 0px;"><tr><td width="50%" style="text-align:center; background:#0087b5; color:#ffffff; padding:5px; font-size: 18px; height: 30px;">Digital</td><td style="text-align:center; background:#00b6f0; color:#ffffff; padding:5px; font-size: 18px; height: 30px;">Conventional</td></tr><tr><td style="text-align:center; background:#0087b5; color:#ffffff; padding:10px; font-size:18px; height: 35px; font-weight: bold;">'. number_format($digital_mentions*735.76).'</td><td style="text-align:center; background:#00b6f0; color:#ffffff; padding:10px; font-size:18px; height: 35px; font-weight: bold;">'.number_format($conventional_mentions*3276.45).'</td></tr></table></td></tr>'
                    . '</table>';
                $content_html .= '</div>';
                
                $content_html .= '<div style="width: 90px; height: 30px; float: right; margin-top: 30px; text-align:right;">{PAGENO}</div>';
                $content_html .= '</div></div></div>';
                
                
                $mpdf->AddPage();
                $pdf_html = mb_convert_encoding($content_html, 'UTF-8', 'UTF-8');
                $mpdf->SetDefaultBodyCSS('background', "url('".$report_images."white-bg.png"."')");
                $mpdf->WriteHTML($pdf_html, 2);
                //END: mentions for main topic
                
                //Engagments for main topic
                $content_html = '<div style="width: 100%; height: 100%; min-height: 100%;">';
                
                $eng_data = explode("|", $this->get_data_for_report_from_es('main_topic_eng', $report_data[0]->rs_tid, '', $report_data[0]->rs_topic_from_date, $report_data[0]->rs_topic_to_date, ''));
                
                $shares_data = explode("|", $this->get_data_for_report_from_es('main_topic_shares', $report_data[0]->rs_tid, '', $report_data[0]->rs_topic_from_date, $report_data[0]->rs_topic_to_date, ''));
                
                $likes_data = explode("|", $this->get_data_for_report_from_es('main_topic_likes', $report_data[0]->rs_tid, '', $report_data[0]->rs_topic_from_date, $report_data[0]->rs_topic_to_date, ''));
                
                $content_html .= '<div style="width: 30%; height: 100%; float: left; color: '.$report_data[0]->rs_font_color.'; background:'.$report_data[0]->rs_bg_color.';">'
                    . '<div style="width: 100%; text-align: center; font-size: 40px; padding-top: 20px;">Engagement</div>'
                    . '<div style="width: 250px; height: 50px; margin: 100px auto 0px auto;">'
                    . '<div style="font-size: 24px;">HIGHLIGHTS</div>'
                    . '<div style="margin-top: 50px; font-size: 20px;">&bull; Total <b>Engagement</b> of '.$eng_data[0].' ('.$eng_data[1].' from the previous period)</div>'
                    . '<div style="margin-top: 50px; font-size: 20px;">&bull; Total <b>Shares</b> of '.$shares_data[0].' ('.$shares_data[1].' from the previous period)</div>'
                    . '<div style="margin-top: 50px; font-size: 20px;">&bull; Total <b>Likes</b> of '.$likes_data[0].' ('.$likes_data[1].' from the previous period)</div>'
                    . '</div>'
                    . '<div style="margin-top: 115px; margin-left: 13px;"><img src="'.$report_images.'logo-d.png" style="width: 80px;"></div>'
                    . '</div>';
                
                $eng_graph = DB::select("SELECT data_image FROM reports_data WHERE data_section = 'main_topic_engagement_graph' AND data_rid = ".$report_id);
                $shares_graph = DB::select("SELECT data_image FROM reports_data WHERE data_section = 'main_topic_shares_graph' AND data_rid = ".$report_id);
                $likes_graph = DB::select("SELECT data_image FROM reports_data WHERE data_section = 'main_topic_likes_graph' AND data_rid = ".$report_id);
                
                $content_html .= '<div style="width: 70%; height: 100%; float: left;">'
                    . '<div style="width: 100%; height: 10px;"></div>'
                    . '<div style="width: 50px; height: 50px; background: url(\''.$rs_logo.'\') no-repeat; background-position: center; background-size: contain; position: absolute; margin-right: 10px; float: right;"></div>'
                    . '<div style="width: 96%; margin: 0px auto 0px auto;">'
                    . '<div style="width: 100%;"><span style="font-size: 15px;">Engagements:</span> <span style="font-size: 18px;">'.$eng_data[0].'</span> <span style="font-size: 13px; color: #d4d4d4;">'.$eng_data[1].'</span></div>'
                    . '<div style="width: 100%;"><img src="'.$eng_graph[0]->data_image.'"></div>'
                    . '<div style="width: 100%; margin-top: 20px;"><span style="font-size: 15px;">Shares:</span> <span style="font-size: 18px;">'.$shares_data[0].'</span> <span style="font-size: 13px; color: #d4d4d4;">'.$shares_data[1].'</span></div>'
                    . '<div style="width: 100%;"><img src="'.$shares_graph[0]->data_image.'"></div>'
                    . '<div style="width: 100%; margin-top: 20px;"><span style="font-size: 15px;">Likes:</span> <span style="font-size: 18px;">'.$likes_data[0].'</span> <span style="font-size: 13px; color: #d4d4d4;">'.$likes_data[1].'</span></div>'
                    . '<div style="width: 100%;"><img src="'.$likes_graph[0]->data_image.'"></div>';
                
                $content_html .= '<div style="width: 90px; height: 30px; float: right; margin-top: 80px; text-align:right;">{PAGENO}</div>';
                $content_html .= '</div>'
                    . '</div>'
                    . '</div>';
                //$content_html .= '<div style="width: 100px; height: 50px; background: #ff0; position: fixed; margin-top: 200px;">{PAGENO}</div>';
                $content_html .= '</div>';
                
                
                $mpdf->AddPage();
                $pdf_html = mb_convert_encoding($content_html, 'UTF-8', 'UTF-8');
                $mpdf->SetDefaultBodyCSS('background', "url('".$report_images."white-bg.png"."')");
                $mpdf->WriteHTML($pdf_html, 2);
                //END: Engagments for main topic
                
                //Influencers for main topic
                $content_html = '<div style="width: 100%; height: 100%; min-height: 100%;">';
                
                $content_html .= '<div style="width: 30%; height: 100%; float: left; color: '.$report_data[0]->rs_font_color.'; background:'.$report_data[0]->rs_bg_color.';">'
                    . '<div style="width: 100%; text-align: center; font-size: 40px; padding-top: 300px;">Top Influencers</div>'
                    . '<div style="font-size: 24px; text-align: center;">Social Media</div>'
                    . '<div style="margin-top: 350px; margin-left: 13px;"><img src="'.$report_images.'logo-d.png" style="width: 80px;"></div>'
                    . '</div>';
                
                $inf_graph = DB::select("SELECT data_image FROM reports_data WHERE data_section = 'main_topic_influencer_graph' AND data_rid = ".$report_id);
                
                $user_graph = DB::select("SELECT data_image FROM reports_data WHERE data_section = 'main_topic_users_graph' AND data_rid = ".$report_id);
                
                $content_html .= '<div style="width: 70%; height: 100%; float: left;">'
                    . '<div style="width: 100%; height: 10px;"></div>'
                    . '<div style="width: 50px; height: 50px; background: url(\''.$rs_logo.'\') no-repeat; background-position: center; background-size: contain; position: absolute; margin-right: 10px; float: right;"></div>'
                    . '<div style="width: 96%; margin: 0px auto 0px auto;">'
                    . '<div style="width: 350px; float: left;"><div><img src="'.$inf_graph[0]->data_image.'"></div></div>'
                    . '<div style="width: 350px; float: left;"><img src="'.$user_graph[0]->data_image.'"></div>'
                    . '<div style="clear: both;"></div>';
                
                    //<div><img src="'.public_path().'/images/pages/influencer-cat.jpg" width="300"></div>
                $content_html .= '<div style="width: 100%; font-size: 22px;">Top influencer accounts</div>';
                
                $inf_data = $this->get_data_for_report_from_es('inf_data', $report_data[0]->rs_tid, '', $report_data[0]->rs_topic_from_date, $report_data[0]->rs_topic_to_date, 'nano');
                if(count($inf_data) > 0)
                {
                    $content_html .= '<div style="width: 30%; float: left; height: 150px; padding: 5px;">'
                        . '<div style="width: 100%; height: 180px; border: 1px solid #d4d4d4; padding: 5px;">'
                        . '<div style="font-size: 14px; text-decoration: underline;">Nano</div>';
                    
                    for($i=0; $i<count($inf_data); $i++)
                    {
                        $content_html .= '<table width="100%" style="border-bottom: 1px solid #f0f0f0;">'
                            . '<tr><td width="25%"><div style="width: 25px; height: 25px;"><img src="'.$inf_data[$i]["profile_image"].'" style="width: 25px; height: 25px;"></div></td><td width="75%" style="font-size: 13px; color: #999;">'.$inf_data[$i]["fullname"].'</td></tr>'
                            . '<tr><td width="25%">&nbsp;</td><td width="75%" style="font-size: 12px;">'.$this->gen_func_obj->format_number_data($inf_data[$i]["followers"]).' <span style="color: #d4d4d4; font-size: 11px;">Followers</span> | '.$inf_data[$i]["country"].'</td></tr>'
                            . '</table>';
                    }
                    
                    $content_html .= '</div></div>';
                }
                
                
                $inf_data = $this->get_data_for_report_from_es('inf_data', $report_data[0]->rs_tid, '', $report_data[0]->rs_topic_from_date, $report_data[0]->rs_topic_to_date, 'micro');
                if(count($inf_data) > 0)
                {
                    $content_html .= '<div style="width: 30%; float: left; height: 150px; padding: 5px;">'
                        . '<div style="width: 100%; height: 180px; border: 1px solid #d4d4d4; padding: 5px;">'
                        . '<div style="font-size: 14px; text-decoration: underline;">Micro</div>';
                    
                    for($i=0; $i<count($inf_data); $i++)
                    {
                        $content_html .= '<table width="100%" style="border-bottom: 1px solid #f0f0f0;">'
                            . '<tr><td width="25%"><div style="width: 25px; height: 25px;"><img src="'.$inf_data[$i]["profile_image"].'" style="width: 25px; height: 25px;"></div></td><td width="75%" style="font-size: 13px; color: #999;">'.$inf_data[$i]["fullname"].'</td></tr>'
                            . '<tr><td width="25%">&nbsp;</td><td width="75%" style="font-size: 12px;">'.$this->gen_func_obj->format_number_data($inf_data[$i]["followers"]).' <span style="color: #d4d4d4; font-size: 11px;">Followers</span> | '.$inf_data[$i]["country"].'</td></tr>'
                            . '</table>';
                    }
                    
                    $content_html .= '</div></div>';
                }
                
                $inf_data = $this->get_data_for_report_from_es('inf_data', $report_data[0]->rs_tid, '', $report_data[0]->rs_topic_from_date, $report_data[0]->rs_topic_to_date, 'midtier');
                if(count($inf_data) > 0)
                {
                    $content_html .= '<div style="width: 30%; float: left; height: 150px; padding: 5px;">'
                        . '<div style="width: 100%; height: 180px; border: 1px solid #d4d4d4; padding: 5px;">'
                        . '<div style="font-size: 14px; text-decoration: underline;">Midtier</div>';
                    
                    for($i=0; $i<count($inf_data); $i++)
                    {
                        $content_html .= '<table width="100%" style="border-bottom: 1px solid #f0f0f0;">'
                            . '<tr><td width="25%"><div style="width: 25px; height: 25px;"><img src="'.$inf_data[$i]["profile_image"].'" style="width: 25px; height: 25px;"></div></td><td width="75%" style="font-size: 13px; color: #999;">'.$inf_data[$i]["fullname"].'</td></tr>'
                            . '<tr><td width="25%">&nbsp;</td><td width="75%" style="font-size: 12px;">'.$this->gen_func_obj->format_number_data($inf_data[$i]["followers"]).' <span style="color: #d4d4d4; font-size: 11px;">Followers</span> | '.$inf_data[$i]["country"].'</td></tr>'
                            . '</table>';
                    }
                    
                    $content_html .= '</div></div>';
                }
                
                $inf_data = $this->get_data_for_report_from_es('inf_data', $report_data[0]->rs_tid, '', $report_data[0]->rs_topic_from_date, $report_data[0]->rs_topic_to_date, 'macro');
                if(count($inf_data) > 0)
                {
                    $content_html .= '<div style="width: 30%; float: left; height: 150px; padding: 5px;">'
                        . '<div style="width: 100%; height: 180px; border: 1px solid #d4d4d4; padding: 5px;">'
                        . '<div style="font-size: 14px; text-decoration: underline;">Macro</div>';
                    
                    for($i=0; $i<count($inf_data); $i++)
                    {
                        $content_html .= '<table width="100%" style="border-bottom: 1px solid #f0f0f0;">'
                            . '<tr><td width="25%"><div style="width: 25px; height: 25px;"><img src="'.$inf_data[$i]["profile_image"].'" style="width: 25px; height: 25px;"></div></td><td width="75%" style="font-size: 13px; color: #999;">'.$inf_data[$i]["fullname"].'</td></tr>'
                            . '<tr><td width="25%">&nbsp;</td><td width="75%" style="font-size: 12px;">'.$this->gen_func_obj->format_number_data($inf_data[$i]["followers"]).' <span style="color: #d4d4d4; font-size: 11px;">Followers</span> | '.$inf_data[$i]["country"].'</td></tr>'
                            . '</table>';
                    }
                    
                    $content_html .= '</div></div>';
                }
                
                $inf_data = $this->get_data_for_report_from_es('inf_data', $report_data[0]->rs_tid, '', $report_data[0]->rs_topic_from_date, $report_data[0]->rs_topic_to_date, 'mega');
                if(count($inf_data) > 0)
                {
                    $content_html .= '<div style="width: 30%; float: left; height: 150px; padding: 5px;">'
                        . '<div style="width: 100%; height: 180px; border: 1px solid #d4d4d4; padding: 5px;">'
                        . '<div style="font-size: 14px; text-decoration: underline;">Mega</div>';
                    
                    for($i=0; $i<count($inf_data); $i++)
                    {
                        $content_html .= '<table width="100%" style="border-bottom: 1px solid #f0f0f0;">'
                            . '<tr><td width="25%"><div style="width: 25px; height: 25px;"><img src="'.$inf_data[$i]["profile_image"].'" style="width: 25px; height: 25px;"></div></td><td width="75%" style="font-size: 13px; color: #999;">'.$inf_data[$i]["fullname"].'</td></tr>'
                            . '<tr><td width="25%">&nbsp;</td><td width="75%" style="font-size: 12px;">'.$this->gen_func_obj->format_number_data($inf_data[$i]["followers"]).' <span style="color: #d4d4d4; font-size: 11px;">Followers</span> | '.$inf_data[$i]["country"].'</td></tr>'
                            . '</table>';
                    }
                    
                    $content_html .= '</div></div>';
                }
                
                $inf_data = $this->get_data_for_report_from_es('inf_data', $report_data[0]->rs_tid, '', $report_data[0]->rs_topic_from_date, $report_data[0]->rs_topic_to_date, 'celebrity');
                if(count($inf_data) > 0)
                {
                    $content_html .= '<div style="width: 30%; float: left; height: 150px; padding: 5px;">'
                        . '<div style="width: 100%; height: 180px; border: 1px solid #d4d4d4; padding: 5px;">'
                        . '<div style="font-size: 14px; text-decoration: underline;">Celebrity</div>';
                    
                    for($i=0; $i<count($inf_data); $i++)
                    {
                        $content_html .= '<table width="100%" style="border-bottom: 1px solid #f0f0f0;">'
                            . '<tr><td width="25%"><div style="width: 25px; height: 25px;"><img src="'.$inf_data[$i]["profile_image"].'" style="width: 25px; height: 25px;"></div></td><td width="75%" style="font-size: 13px; color: #999;">'.$inf_data[$i]["fullname"].'</td></tr>'
                            . '<tr><td width="25%">&nbsp;</td><td width="75%" style="font-size: 12px;">'.$this->gen_func_obj->format_number_data($inf_data[$i]["followers"]).' <span style="color: #d4d4d4; font-size: 11px;">Followers</span> | '.$inf_data[$i]["country"].'</td></tr>'
                            . '</table>';
                    }
                    
                    $content_html .= '</div></div>';
                }
                    
                
                //$content_html .= '<div style="width: 90px; height: 30px; float: right; margin-top: 20px; text-align:right;">{PAGENO}</div>';
                $content_html .= '</div>'
                    . '</div>'
                    . '</div>';
               
                $content_html .= '</div>';
                                
                $mpdf->AddPage();
                $pdf_html = mb_convert_encoding($content_html, 'UTF-8', 'UTF-8');
                $mpdf->SetDefaultBodyCSS('background', "url('".$report_images."white-bg.png"."')");
                $mpdf->WriteHTML($pdf_html, 2);
                //END: influencer for main topic
                
                //Sources for main topic
                $sources_array = array("Twitter", "Instagram", "Facebook", "Reddit", "Youtube", "News", "Blogs", "Tumblr", "Pinterest", "Linkedin");
                //$sources_array = array("Twitter", "Instagram", "Facebook", "Reddit", "Youtube", "News", "Linkedin");
                
                for($i=0; $i<count($sources_array); $i++)
                {
                    $source_data = $this->get_data_for_report_from_es('source_channel_data', $report_data[0]->rs_tid, '', $report_data[0]->rs_topic_from_date, $report_data[0]->rs_topic_to_date, $sources_array[$i]);
                    //echo $source_data.'<br>';
                    if(!empty($source_data))
                    {
                        $source_img = '';
                        
                        if($sources_array[$i] == 'Twitter')
                            $source_img = 'https://'.$request->getHost().'/images/logo/icons8-twitter.png';
                        else if($sources_array[$i] == 'Instagram')
                            $source_img = 'https://'.$request->getHost().'/images/logo/icons8-instagram.png';
                        else if($sources_array[$i] == 'Facebook')
                            $source_img = 'https://'.$request->getHost().'/images/logo/icons8-facebook.png';
                        else if($sources_array[$i] == 'Reddit')
                            $source_img = 'https://'.$request->getHost().'/images/logo/icons8-reddit.png';
                        else if($sources_array[$i] == 'Youtube')
                            $source_img = 'https://'.$request->getHost().'/images/logo/icons8-youtube.png';
                        else if($sources_array[$i] == 'News')
                            $source_img = 'https://'.$request->getHost().'/images/logo/icons8-news.png';
                        else if($sources_array[$i] == 'Blogs')
                            $source_img = 'https://'.$request->getHost().'/images/logo/icons8-blogs.png';
                        else if($sources_array[$i] == 'Tumblr')
                            $source_img = 'https://'.$request->getHost().'/images/logo/icons8-tumblr.png';
                        else if($sources_array[$i] == 'Pinterest')
                            $source_img = 'https://'.$request->getHost().'/images/logo/icons8-pinterest.png';
                        else if($sources_array[$i] == 'Linkedin')
                            $source_img = 'https://'.$request->getHost().'/images/logo/icons8-linkedin.png';
                            
                        $content_html = '<div style="width: 100%; height: 100%; min-height: 100%;">';
                
                        $content_html .= '<div style="width: 30%; height: 100%; float: left; color: '.$report_data[0]->rs_font_color.'; background:'.$report_data[0]->rs_bg_color.';">'
                            . '<div style="padding-top: 10px; padding-left: 10px;"><img src="'.$source_img.'" width="60" height="60"></div>'
                            . '<div style="width: 100%; text-align: center; font-size: 40px; padding-top: 200px;">Top mentions</div>'
                            . '<div style="font-size: 24px; text-align: center;">'.$sources_array[$i].'</div>'
                            . '<div style="margin-top: 350px; margin-left: 13px;"><img src="'.$report_images.'logo-d.png" style="width: 80px;"></div>'
                            . '</div>';

                        $content_html .= '<div style="width: 70%; height: 100%; float: left;">'
                            . '<div style="width: 100%; height: 10px;"></div>'
                            . '<div style="width: 50px; height: 50px; background: url(\''.$rs_logo.'\') no-repeat; background-position: center; background-size: contain; position: absolute; margin-right: 10px; float: right;"></div>'
                            . '<div style="width: 96%; margin: 0px auto 0px auto;">';



                        $content_html .= $source_data;

                        $content_html .= '</div><div style="width: 90px; height: 30px; float: right; margin-top: 20px; text-align:right;">{PAGENO}</div>'
                            . '</div>'
                            . '</div>';

                        $content_html .= '</div>';

                        $mpdf->AddPage();
                        $pdf_html = mb_convert_encoding($content_html, 'UTF-8', 'UTF-8'); //echo $pdf_html.'<br>';
                        $mpdf->SetDefaultBodyCSS('background', "url('".$report_images."white-bg.png"."')");
                        $mpdf->WriteHTML($pdf_html, 2);
                    }                    
                }
                //dd($content_html);
                //END: different sources for main topic
                
                //Printmedia posts
                $content_html = '<div style="width: 100%; height: 100%; min-height: 100%;">';
                
                $content_html .= '<div style="width: 30%; height: 100%; float: left; color: '.$report_data[0]->rs_font_color.'; background:'.$report_data[0]->rs_bg_color.';">'
                    . '<div style="padding-top: 10px; padding-left: 10px;"><img src="https://'.$request->getHost().'/images/logo/icons8-news.png" width="60" height="60"></div>'
                    . '<div style="width: 100%; text-align: center; font-size: 40px; padding-top: 200px;">Mentions</div>'
                    . '<div style="font-size: 24px; text-align: center;">Print Media</div>'
                    . '<div style="margin-top: 350px; margin-left: 13px;"><img src="'.$report_images.'logo-d.png" style="width: 80px;"></div>'
                    . '</div>';

                $content_html .= '<div style="width: 70%; height: 100%; float: left;">'
                    . '<div style="width: 100%; height: 10px;"></div>'
                    . '<div style="width: 50px; height: 50px; background: url(\''.$rs_logo.'\') no-repeat; background-position: center; background-size: contain; position: absolute; margin-right: 10px; float: right;"></div>'
                    . '<div style="width: 96%; margin: 0px auto 0px auto;">';

                $printmedia_data = $this->get_data_for_report_from_es('printmedia_data', $report_data[0]->rs_tid, '', $report_data[0]->rs_topic_from_date, $report_data[0]->rs_topic_to_date, '');

                if(!empty($printmedia_data))
                {
                    $content_html .= $printmedia_data;

                    $content_html .= '</div><div style="width: 90px; height: 30px; float: right; margin-top: 20px; text-align:right;">{PAGENO}</div>'
                        . '</div>'
                        . '</div>';

                    $content_html .= '</div>';

                    $mpdf->AddPage();
                    $pdf_html = mb_convert_encoding($content_html, 'UTF-8', 'UTF-8');
                    $mpdf->SetDefaultBodyCSS('background', "url('".$report_images."white-bg.png"."')");
                    $mpdf->WriteHTML($pdf_html, 2);
                }
                
                //END: Printmedia posts
                
                //Main topic sentiments / emotions
                $content_html = '<div style="width: 100%; height: 100%; min-height: 100%;">';
                
                $pos_senti = $this->get_data_for_report_from_es('main_topic_sentiments', $report_data[0]->rs_tid, '', $report_data[0]->rs_topic_from_date, $report_data[0]->rs_topic_to_date, 'positive');
                
                $neg_senti = $this->get_data_for_report_from_es('main_topic_sentiments', $report_data[0]->rs_tid, '', $report_data[0]->rs_topic_from_date, $report_data[0]->rs_topic_to_date, 'negative');
                
                $neu_senti = $this->get_data_for_report_from_es('main_topic_sentiments', $report_data[0]->rs_tid, '', $report_data[0]->rs_topic_from_date, $report_data[0]->rs_topic_to_date, 'neutral');
                
                $total_senti = $pos_senti + $neg_senti + $neu_senti;
                if($total_senti == 0)
                    $total_senti = 1;
                
                $content_html .= '<div style="width: 30%; height: 100%; float: left; color: '.$report_data[0]->rs_font_color.'; background:'.$report_data[0]->rs_bg_color.';">'
                    . '<div style="width: 100%; text-align: center; font-size: 40px; padding-top: 20px;">Sentiment analysis</div>'
                    . '<div style="width: 250px; height: 50px; margin: 100px auto 0px auto;">'
                    . '<div style="font-size: 19px;">In the respective dates selected, we found '.number_format(($pos_senti/$total_senti)*100).'% Positive, '.number_format(($neg_senti/$total_senti)*100).'% of mentions found were Negative, while '.number_format(($neu_senti/$total_senti)*100).'% mentions were with Neutral sentiment.</div>'
                    . '</div>'
                    . '<div style="margin-top: 350px; margin-left: 13px;"><img src="'.$report_images.'logo-d.png" style="width: 80px;"></div>'
                    . '</div>';
                
                $senti_graph = DB::select("SELECT data_image FROM reports_data WHERE data_section = 'main_topic_sentiment_graph' AND data_rid = ".$report_id);
                $emo_graph = DB::select("SELECT data_image FROM reports_data WHERE data_section = 'main_topic_emotions_graph' AND data_rid = ".$report_id);
                
                $content_html .= '<div style="width: 70%; height: 100%; float: left;">'
                    . '<div style="width: 100%; height: 10px;"></div>'
                    . '<div style="width: 50px; height: 50px; background: url(\''.$rs_logo.'\') no-repeat; background-position: center; background-size: contain; position: absolute; margin-right: 10px; float: right;"></div>'
                    . '<div style="width: 96%; margin: 0px auto 0px auto;">'
                    . '<div style="width: 100%;"><span style="font-size: 15px;">Sentiments data chart</span></div>'
                    . '<div style="width: 100%;"><img src="'.$senti_graph[0]->data_image.'" style="height: 300px;"></div>'
                    . '<div style="width: 100%; margin-top: 20px;"><span style="font-size: 15px;">Emotions data chart</span></div>'
                    . '<div style="width: 100%;"><img src="'.$emo_graph[0]->data_image.'" style="height: 300px;"></div>';
                
                $content_html .= '<div style="width: 90px; height: 30px; float: right; margin-top: 50px; text-align:right;">{PAGENO}</div>';
                $content_html .= '</div>'
                    . '</div>'
                    . '</div>';
                //$content_html .= '<div style="width: 100px; height: 50px; background: #ff0; position: fixed; margin-top: 200px;">{PAGENO}</div>';
                $content_html .= '</div>';
                
                
                $mpdf->AddPage();
                $pdf_html = mb_convert_encoding($content_html, 'UTF-8', 'UTF-8');
                $mpdf->SetDefaultBodyCSS('background', "url('".$report_images."white-bg.png"."')");
                $mpdf->WriteHTML($pdf_html, 2);
                //END: Main topic sentiments / emotions
                
                //Main topic word cloud
                $content_html = '<div style="width: 100%; height: 100%; min-height: 100%;">';
                
                $content_html .= '<div style="width: 30%; height: 100%; float: left; color: '.$report_data[0]->rs_font_color.'; background:'.$report_data[0]->rs_bg_color.';">'
                    . '<div style="width: 100%; text-align: center; font-size: 40px; padding-top: 200px;">Word Cloud</div>'
                    . '<div style="width: 250px; height: 50px; margin: 20px auto 0px auto;">'
                    . '<div style="font-size: 19px; text-align:center;">Social media</div>'
                    . '</div>'
                    . '<div style="margin-top: 350px; margin-left: 13px;"><img src="'.$report_images.'logo-d.png" style="width: 80px;"></div>'
                    . '</div>';
                
                $wordcloud_image = DB::select("SELECT data_image FROM reports_data WHERE data_section = 'main_topic_word_cloud' AND data_rid = ".$report_id);
                
                if(count($wordcloud_image) > 0)
                    $wc_image = $wordcloud_image[0]->data_image;
                else
                    $wc_image = '';
                
                $content_html .= '<div style="width: 70%; height: 100%; float: left;">'
                    . '<div style="width: 100%; height: 10px;"></div>'
                    . '<div style="width: 50px; height: 50px; background: url(\''.$rs_logo.'\') no-repeat; background-position: center; background-size: contain; position: absolute; margin-right: 10px; float: right;"></div>'
                    . '<div style="width: 96%; margin: 0px auto 0px auto;">'
                    . '<div style="width: 100%; padding-top: 100px;"><img src="'.$wc_image.'"></div>';
                
                $content_html .= '<div style="width: 90px; height: 30px; float: right; margin-top: 100px; text-align:right;">{PAGENO}</div>';
                $content_html .= '</div>'
                    . '</div>'
                    . '</div>';
                //$content_html .= '<div style="width: 100px; height: 50px; background: #ff0; position: fixed; margin-top: 200px;">{PAGENO}</div>';
                $content_html .= '</div>';
                
                
                $mpdf->AddPage();
                $pdf_html = mb_convert_encoding($content_html, 'UTF-8', 'UTF-8');
                $mpdf->SetDefaultBodyCSS('background', "url('".$report_images."white-bg.png"."')");
                $mpdf->WriteHTML($pdf_html, 2);
                $content_html = '';
                //END: Main topic word cloud
                
                //Main topic most active users
                $content_html = '<div style="width: 100%; height: 100%; min-height: 100%;">';
                
                $content_html .= '<div style="width: 30%; height: 100%; float: left; color: '.$report_data[0]->rs_font_color.'; background:'.$report_data[0]->rs_bg_color.';">'
                    . '<div style="width: 100%; text-align: center; font-size: 40px; padding-top: 250px;">Most mentions</div>'
                    . '<div style="width: 250px; height: 50px; margin: 20px auto 0px auto;">'
                    . '<div style="font-size: 19px; text-align:center;">Social media</div>'
                    . '</div>'
                    . '<div style="margin-top: 350px; margin-left: 13px;"><img src="'.$report_images.'logo-d.png" style="width: 80px;"></div>'
                    . '</div>';
                
                $active_users_html = $this->get_data_for_report_from_es('most_active_users', $report_data[0]->rs_tid, '', $report_data[0]->rs_topic_from_date, $report_data[0]->rs_topic_to_date, '');
                
                $content_html .= '<div style="width: 70%; height: 100%; float: left;">'
                    . '<div style="width: 100%; height: 10px;"></div>'
                    . '<div style="width: 50px; height: 50px; background: url(\''.$rs_logo.'\') no-repeat; background-position: center; background-size: contain; position: absolute; margin-right: 10px; float: right;"></div>'
                    . '<div style="width: 96%; margin: 0px auto 0px auto;">'
                    . '<div style="font-size: 18px;">Most active users</div>'
                    . '<div style="width: 100%; padding-top: 10px;">'.$active_users_html.'</div>';
                
                $content_html .= '<div style="width: 90px; height: 30px; float: right; margin-top: 50px; text-align:right;">{PAGENO}</div>';
                $content_html .= '</div>'
                    . '</div>'
                    . '</div>';
                //$content_html .= '<div style="width: 100px; height: 50px; background: #ff0; position: fixed; margin-top: 200px;">{PAGENO}</div>';
                $content_html .= '</div>';
                
                
                $mpdf->AddPage();
                $pdf_html = mb_convert_encoding($content_html, 'UTF-8', 'UTF-8');
                $mpdf->SetDefaultBodyCSS('background', "url('".$report_images."white-bg.png"."')");
                $mpdf->WriteHTML($pdf_html, 2);
                $content_html = '';
                //END: Main topic most active users
                
                /*********************** sub topics start *****************************/
                $selected_subtopics_ids = array();
                
                //get sub topic ids
                if(!is_null($report_data[0]->rs_subtopic_ids) && $report_data[0]->rs_subtopic_ids != '' && $report_data[0]->rs_subtopic_ids != 'ALL')
                {
                    $selected_subtopics_ids = explode(",", $report_data[0]->rs_subtopic_ids);
                }
        
                $sub_topic_obj = DB::select("SELECT * FROM customer_experience WHERE exp_topic_id = ".$report_data[0]->rs_tid);
                
                if(count($sub_topic_obj) > 0)
                {
                    for($k=0; $k<count($sub_topic_obj); $k++)
                    {
                        if(!is_null($report_data[0]->rs_subtopic_ids) && $report_data[0]->rs_subtopic_ids != '' && $report_data[0]->rs_subtopic_ids != 'ALL')
                        {
                            //subtopics are selected
                            if(!in_array($sub_topic_obj[$k]->exp_id, $selected_subtopics_ids))
                            {
                                //if id not found, restart the loop
                                continue;
                            }
                        }
                
                        //sub topic title page
                        if(file_exists($report_images.$report_data[0]->rs_subtopic_bg_image) && is_file($report_images.$report_data[0]->rs_subtopic_bg_image))
                        {
                            $subtopic_bg_image = $report_image_url.$report_data[0]->rs_subtopic_bg_image;
                            $mpdf->SetDefaultBodyCSS('background', "url('".$subtopic_bg_image."')");
                            $mpdf->SetDefaultBodyCSS('background-image-resize', 6);
                        }
                        else
                        {
                            $mpdf->SetDefaultBodyCSS('background', "url('".$report_images."white-bg.png"."')");
                            $mpdf->WriteHTML($pdf_html, 2);
                        }

                        $content_html = '<div style="width: 100%; height: 100%; min-height: 100%; padding-top: 10px;">';
                        
                        $content_html .= '<div style="width: 50px; height: 50px; background: url(\''.$rs_logo.'\') no-repeat; background-position: center; background-size: contain; position: absolute; margin-right: 10px; float: right;"></div>';
                        $content_html .= '<div style="margin-top: 270px; margin-left: 50px; color: '.$report_data[0]->rs_subtopic_font_color.';"><span style="font-size: 30px;">In-depth analysis</span><br><span style="font-size: 36px; font-weight: bold;">'.$sub_topic_obj[$k]->exp_name.'</span><br><br><span style="font-size: 16px;">'.$sub_topic_obj[$k]->exp_detail.'&nbsp;</span></div>';
                        
                        $content_html .= '<div style="margin-top: 300px; margin-left: 13px;"><img src="'.$report_images.'logo-d.png" style="width: 80px;"></div>';
                        
                        $content_html .= '</div>';

                        $mpdf->AddPage();
                        $pdf_html = mb_convert_encoding($content_html, 'UTF-8', 'UTF-8');
                        $mpdf->WriteHTML($pdf_html, 2);
                        //End sub topic title page
                        
                        //sub topic summary page
                        $content_html = '<div style="width: 100%; height: 100%; min-height: 100%;">';
                
                        $content_html .= '<div style="width: 30%; height: 100%; float: left; color: '.$report_data[0]->rs_subtopic_font_color.'; background:'.$report_data[0]->rs_subtopic_bg_color.';">'
                            . '<div style="width: 100%; padding-top: 20px; padding-left: 10px;"><span style="font-size: 17px;">In-depth analysis</span><br><span style="font-size: 22px;">'.$sub_topic_obj[$k]->exp_name.'</span></div>'
                            . '<div style="margin-top: 260px; font-size: 32px; text-align: center;">Summary</div>'
                            . '<div style="margin-top: 350px; margin-left: 13px;"><img src="'.$report_images.'logo-d.png" style="width: 80px;"></div>'
                            . '</div>';

                        $content_html .= '<div style="width: 70%; height: 100%; float: left;">'
                            . '<div style="width: 100%; height: 10px;"></div>'
                            . '<div style="width: 50px; height: 50px; background: url(\''.$rs_logo.'\') no-repeat; background-position: center; background-size: contain; position: absolute; margin-right: 10px; float: right;"></div>'
                            . '<div style="width: 96%; margin: 10px auto 0px auto;">';

                        $st_mentions = explode("|", $this->get_data_for_report_from_es('subtopic_mentions', $report_data[0]->rs_tid, $sub_topic_obj[$k]->exp_id, $report_data[0]->rs_subtopic_from_date, $report_data[0]->rs_subtopic_to_date, ''));
                      
                        $content_html .= '<div style="width: 170px; height: 150px; float: left; background-image: linear-gradient(top, #FCFCFC, #f0f0f0); border-right: 15px solid #ffffff;">'
                            . '<div style="font-size: 30px; font-weight: bold; text-align: center; padding-top: 25px; color: #666666;">'.$st_mentions[0].'</div><div style="font-size: 11px; color: #999999; text-align: center;">'.$st_mentions[1].'</div><div style="color: #999999; text-align: center; padding-top: 15px;">Mentions</div>'
                            . '</div>';
                        
                        $st_engagements = explode("|", $this->get_data_for_report_from_es('subtopic_engagements', $report_data[0]->rs_tid, $sub_topic_obj[$k]->exp_id, $report_data[0]->rs_subtopic_from_date, $report_data[0]->rs_subtopic_to_date, ''));
                        
                        $content_html .= '<div style="width: 170px; height: 150px; float: left; background-image: linear-gradient(top, #FCFCFC, #f0f0f0); border-right: 15px solid #ffffff;">'
                            . '<div style="font-size: 30px; font-weight: bold; text-align: center; padding-top: 25px; color: #666666;">'.$st_engagements[0].'</div><div style="font-size: 11px; color: #999999; text-align: center;">'.$st_engagements[1].'</div><div style="color: #999999; text-align: center; padding-top: 15px;">Engagements</div>'
                            . '</div>';
                        
                        $st_shares = explode("|", $this->get_data_for_report_from_es('subtopic_shares', $report_data[0]->rs_tid, $sub_topic_obj[$k]->exp_id, $report_data[0]->rs_subtopic_from_date, $report_data[0]->rs_subtopic_to_date, ''));
                        
                        $content_html .= '<div style="width: 170px; height: 150px; float: left; background-image: linear-gradient(top, #FCFCFC, #f0f0f0); border-right: 15px solid #ffffff;">'
                            . '<div style="font-size: 30px; font-weight: bold; text-align: center; padding-top: 25px; color: #666666;">'.$st_shares[0].'</div><div style="font-size: 11px; color: #999999; text-align: center;">'.$st_shares[1].'</div><div style="color: #999999; text-align: center; padding-top: 15px;">Shares</div>'
                            . '</div>';
                        
                        $st_likes = explode("|", $this->get_data_for_report_from_es('subtopic_likes', $report_data[0]->rs_tid, $sub_topic_obj[$k]->exp_id, $report_data[0]->rs_subtopic_from_date, $report_data[0]->rs_subtopic_to_date, ''));
                        
                        $content_html .= '<div style="width: 170px; height: 150px; float: left; background-image: linear-gradient(top, #FCFCFC, #f0f0f0); border-right: 15px solid #ffffff;">'
                            . '<div style="font-size: 30px; font-weight: bold; text-align: center; padding-top: 25px; color: #666666;">'.$st_likes[0].'</div><div style="font-size: 11px; color: #999999; text-align: center;">'.$st_likes[1].'</div><div style="color: #999999; text-align: center; padding-top: 15px;">Likes</div>'
                            . '</div>';
                        
                        $content_html .= '<div style="clear: both;"></div>';
                        
                        $channels_data = explode("|", $this->get_data_for_report_from_es('main_topic_chanels', $report_data[0]->rs_tid, $sub_topic_obj[$k]->exp_id, $report_data[0]->rs_subtopic_from_date, $report_data[0]->rs_subtopic_to_date, 'subtopic_channels'));
                
                        $content_html .= '<div style="font-size: 22px; padding-top: 25px; padding-bottom: 25px;">Main data channel counts</div><div style="padding-top: 10px;"><table style="width: 100%;">'
                            . '<tr><td style="height: 70px; text-align: center; border-right: 1px solid #d4d4d4; border-bottom: 1px solid #d4d4d4;"><span style="font-size: 20px;">'.number_format($channels_data[7]).'</span><br><span style="font-size: 14px; color: #d4d4d4;">Facebook</span></td>'
                            . '<td style="text-align: center; border-right: 1px solid #d4d4d4; border-bottom: 1px solid #d4d4d4;"><span style="font-size: 20px;">'.number_format($channels_data[2]).'</span><br><span style="font-size: 14px; color: #d4d4d4;">Twitter</span></td>'
                            . '<td style="text-align: center; border-right: 1px solid #d4d4d4; border-bottom: 1px solid #d4d4d4;"><span style="font-size: 20px;">'.number_format($channels_data[3]).'</span><br><span style="font-size: 14px; color: #d4d4d4;">Instagram</span></td>'
                            . '<td style="text-align: center; border-bottom: 1px solid #d4d4d4;"><span style="font-size: 20px;">'. number_format($channels_data[5]).'</span><br><span style="font-size: 14px; color: #d4d4d4;">Reddit</span></td></tr>'
                            . '<tr><td style="height: 70px; text-align: center; border-right: 1px solid #d4d4d4;"><span style="font-size: 20px;">'.number_format($channels_data[0]).'</span><br><span style="font-size: 14px; color: #d4d4d4;">Videos</span></td>'
                            . '<td style="text-align: center; border-right: 1px solid #d4d4d4;"><span style="font-size: 20px;">'.number_format($channels_data[1]).'</span><br><span style="font-size: 14px; color: #d4d4d4;">News</span></td>'
                            . '<td style="text-align: center; border-right: 1px solid #d4d4d4;"><span style="font-size: 20px;">'.number_format($channels_data[4]).'</span><br><span style="font-size: 14px; color: #d4d4d4;">Blogs</span></td>'
                            . '<td style="text-align: center;"><span style="font-size: 20px;">'.number_format($channels_data[6]).'</span><br><span style="font-size: 14px; color: #d4d4d4;">Tumblr</span></td></tr>'
                            . '</table></div><div style="width: 100%; height: 20px;"></div>';
                
                        //csat
                        $csat_data = DB::select("SELECT data_image FROM reports_data WHERE data_section = 'subtopic_csat' AND data_cx_id = ".$sub_topic_obj[$k]->exp_id." AND data_topic_id = ".$report_data[0]->rs_tid);
                        if(count($csat_data) > 0)
                        {
                            $content_html .= '<div style="width: 200px; float: left; border-right: 30px solid #ffffff;"><img src="'.$csat_data[0]->data_image.'"></div>';
                        }
                        
                        //revenue loss
                        $loss_data = explode("|", $this->get_data_for_report_from_es('revenue_loss', $report_data[0]->rs_tid, $sub_topic_obj[$k]->exp_id, $report_data[0]->rs_subtopic_from_date, $report_data[0]->rs_subtopic_to_date, ''));
                        
                        if($loss_data[0] != 'NA' && count($loss_data) > 1)
                        {
                            $content_html .= '<div style="float: left; margin-left: 20px; border: 1px solid #f0f0f0; width: 250px; height: 250px;">
                                <div style="padding-bottom: 30px; color: #999999; text-align: center;"><h5>Potential revenue loss</h5></div>
                                <div style="width: 64px; height: 64px; margin: 0px auto;">
                                    <img src="'.$report_images.'icons8-money-64.png">
                                </div>
                                <div style="text-align: center;"><h2 style="color: #ff0000;">'.$loss_data[0].'</h2></div>
                                <div style="text-align: center; font-size: 12px;">Based on '.$loss_data[1].'% churn rate</div>
                            </div>';
                        }
                        
                        $content_html .= '<div style="clear: both;"></div>';
                        $content_html .= '<div style="width: 90px; height: 20px; float: right; margin-top: 0px; text-align:right;">{PAGENO}</div>';
                        
                        $content_html .= '</div>'
                            . '</div>'
                            . '</div>';
                        //$content_html .= '<div style="width: 100px; height: 50px; background: #ff0; position: fixed; margin-top: 200px;">{PAGENO}</div>';
                        $content_html .= '</div>';


                        $mpdf->AddPage();
                        $pdf_html = mb_convert_encoding($content_html, 'UTF-8', 'UTF-8');
                        $mpdf->SetDefaultBodyCSS('background', "url('".$report_images."white-bg.png"."')");
                        $mpdf->WriteHTML($pdf_html, 2);
                        //END: sub topic summary page
                        
                        //sub topic senti emo
                        $content_html = '<div style="width: 100%; height: 100%; min-height: 100%;">';
                
                        $content_html .= '<div style="width: 30%; height: 100%; float: left; color: '.$report_data[0]->rs_subtopic_font_color.'; background:'.$report_data[0]->rs_subtopic_bg_color.';">'
                            . '<div style="width: 100%; padding-top: 20px; padding-left: 10px;"><span style="font-size: 17px;">In-dept analysis</span><br><span style="font-size: 22px;">'.$sub_topic_obj[$k]->exp_name.'</span></div>'
                            . '<div style="margin-top: 650px; margin-left: 13px;"><img src="'.$report_images.'logo-d.png" style="width: 80px;"></div>'
                            . '</div>';

                        $content_html .= '<div style="width: 70%; height: 100%; float: left;">'
                            . '<div style="width: 100%; height: 10px;"></div>'
                            . '<div style="width: 50px; height: 50px; background: url(\''.$rs_logo.'\') no-repeat; background-position: center; background-size: contain; position: absolute; margin-right: 10px; float: right;"></div>'
                            . '<div style="width: 96%; margin: 10px auto 0px auto;">';

                        //senti graph
                        $senti_data = DB::select("SELECT data_image FROM reports_data WHERE data_section = 'subtopic_senti_chart' AND data_cx_id = ".$sub_topic_obj[$k]->exp_id." AND data_topic_id = ".$report_data[0]->rs_tid);
                        if(count($senti_data) > 0)
                        {
                            $content_html .= '<div style="width: 330px; float: left; border-right: 30px solid #ffffff; text-align: center;"><span>Sentiment analysis</span><br><img src="'.$senti_data[0]->data_image.'"></div>';
                        }
                        
                        //emo graph
                        $emo_data = DB::select("SELECT data_image FROM reports_data WHERE data_section = 'subtopic_emo_chart' AND data_cx_id = ".$sub_topic_obj[$k]->exp_id." AND data_topic_id = ".$report_data[0]->rs_tid);
                        if(count($emo_data) > 0)
                        {
                            $content_html .= '<div style="width: 330px; float: left; border-right: 30px solid #ffffff; text-align: center;"><span>Emotions analysis</span><br><img src="'.$emo_data[0]->data_image.'"></div>';
                        }
                        
                        $content_html .= '<div style="clear: both;"></div>';
                        
                        //word cloud
                        $wordcloud_image = DB::select("SELECT data_image FROM reports_data WHERE data_section = 'sub_topic_word_cloud' AND data_cx_id = ".$sub_topic_obj[$k]->exp_id." AND data_rid = ".$report_id);
                        
                        if(count($wordcloud_image) > 0)
                            $content_html .= '<div style="font-size: 16px; margin-top: 30px;">Most frequently used words cloud</div><div style="margin-top: 10px;"><img src="'.$wordcloud_image[0]->data_image.'"></div>';
                        
                        $content_html .= '<div style="width: 90px; height: 20px; float: right; margin-top: 10px; text-align:right;">{PAGENO}</div>';
                        
                        $content_html .= '</div>'
                            . '</div>'
                            . '</div>';
                        //$content_html .= '<div style="width: 100px; height: 50px; background: #ff0; position: fixed; margin-top: 200px;">{PAGENO}</div>';
                        $content_html .= '</div>';


                        $mpdf->AddPage();
                        $pdf_html = mb_convert_encoding($content_html, 'UTF-8', 'UTF-8');
                        $mpdf->SetDefaultBodyCSS('background', "url('".$report_images."white-bg.png"."')");
                        $mpdf->WriteHTML($pdf_html, 2);
                        //END: sub topic senti emo
                        
                        //touch points
                        $content_html = '<div style="width: 100%; height: 100%; min-height: 100%;">';
                
                        $content_html .= '<div style="width: 30%; height: 100%; float: left; color: '.$report_data[0]->rs_subtopic_font_color.'; background:'.$report_data[0]->rs_subtopic_bg_color.';">'
                            . '<div style="width: 100%; padding-top: 20px; padding-left: 10px;"><span style="font-size: 17px;">In-dept analysis</span><br><span style="font-size: 22px;">'.$sub_topic_obj[$k]->exp_name.'</span></div>'
                            . '<div style="margin-top: 650px; margin-left: 13px;"><img src="'.$report_images.'logo-d.png" style="width: 80px;"></div>'
                            . '</div>';

                        $content_html .= '<div style="width: 70%; height: 100%; float: left;">'
                            . '<div style="width: 100%; height: 10px;"></div>'
                            . '<div style="width: 50px; height: 50px; background: url(\''.$rs_logo.'\') no-repeat; background-position: center; background-size: contain; position: absolute; margin-right: 10px; float: right;"></div>'
                            . '<div style="width: 96%; margin: 10px auto 0px auto;">';

                        $tp_found = false;
                        //bar chart
                        $tp_bar_data = DB::select("SELECT data_image FROM reports_data WHERE data_section = 'touchpoint_chart' AND data_cx_id = ".$sub_topic_obj[$k]->exp_id." AND data_topic_id = ".$report_data[0]->rs_tid);
                        if(count($tp_bar_data) > 0)
                        {
                            $content_html .= '<div style="width: 400px; text-align: center; padding-bottom: 20px;"><span>Touch points results count</span><br><img src="'.$tp_bar_data[0]->data_image.'"></div>';
                            $tp_found = true;
                        }
                        
                        //Touchpoint sentiment graphs
                        if($tp_found == true)
                        {
                            $tp_ids = $this->touchpoint_obj->get_all_touchpoints_data($sub_topic_obj[$k]->exp_id);
                            for($tt=0; $tt<count($tp_ids); $tt++)
                            {
                                $tp_senti = DB::select("SELECT data_image FROM reports_data WHERE data_section = 'tp_senti_chart' AND data_cx_id = ".$sub_topic_obj[$k]->exp_id." AND data_tp_id = ".$tp_ids[$tt]->tp_id);
                                
                                $content_html .= '<div style="float: left; width: 150px; text-align: center; border-bottom: 20px solid #ffffff;"><span style="font-size: 11px;">'.$tp_ids[$tt]->tp_name.'</span><br><img src="'.$tp_senti[0]->data_image.'" width="150"></div>';
                            }
                            
                        }
                        
                        $content_html .= '<div style="clear: both;"></div>';
                        //$content_html .= '<div style="width: 90px; height: 20px; float: right; margin-top: 410px; text-align:right;">{PAGENO}</div>';
                        
                        $content_html .= '</div>'
                            . '</div>'
                            . '</div>';
                        //$content_html .= '<div style="width: 100px; height: 50px; background: #ff0; position: fixed; margin-top: 200px;">{PAGENO}</div>';
                        $content_html .= '</div>';


                        if($tp_found == true)
                        {
                            $mpdf->AddPage();
                            $pdf_html = mb_convert_encoding($content_html, 'UTF-8', 'UTF-8');
                            $mpdf->SetDefaultBodyCSS('background', "url('".$report_images."white-bg.png"."')");
                            $mpdf->WriteHTML($pdf_html, 2);
                        }
                        
                        //END: touch points
                    }
                    
                }
                
                /*********************** END: Sub topics *****************************/
                
                //Last page of report
                $mpdf->AddPage();
                
                if(file_exists($report_images.$report_data[0]->rs_bg_image_last_page) && is_file($report_images.$report_data[0]->rs_bg_image_last_page))
                {
                    $rs_bg_image_last_page = $report_images.$report_data[0]->rs_bg_image_last_page;
                    $mpdf->SetDefaultBodyCSS('background', "url('".$rs_bg_image_last_page."')");
                    $mpdf->SetDefaultBodyCSS('background-image-resize', 6);
                }
                else
                {
                    $mpdf->SetDefaultBodyCSS('background', "url('".$report_images."white-bg.png"."')");
                    $mpdf->WriteHTML($pdf_html, 2);
                }
                
                $content_html = '<div style="width: 100%; height: 100%; min-height: 100%;">&nbsp;</div>';
                
                $pdf_html = mb_convert_encoding($html_opening_tags.$content_html.$html_closing_tags, 'UTF-8', 'UTF-8');                
                $mpdf->WriteHTML($pdf_html, 2);
                //END: Last page of report
                
                // Output a PDF file directly to the browser
                $pdf_filename = $this->topic_obj->get_topic_name($report_data[0]->rs_tid).'-'.uniqid().'.pdf';
                
                
                //DB::delete("DELETE FROM reports_data WHERE data_rid = ".$report_id);
                DB::table('reports_data')->truncate();
                
                $mpdf->Output($pdf_save_path.str_replace("'","", $pdf_filename), 'F');
                
                DB::update("UPDATE reports_settings SET rs_status = 'c', rs_filename = '".addslashes(str_replace("'","", $pdf_filename))."', rs_completed_time = NOW() WHERE rs_id = ".$report_id);
                
                //Check if monthly report was generated. If yes, email link to customer.
                if($report_data[0]->rs_is_auto_monthly_report == 'yes')
                {
                    //$mpdf->SetProtection(array(), 'UserPassword', '123456');
                    $email_send_obj = new EmailListingController();
                    
                    $to_email = $this->cus_obj->get_customer_email($report_data[0]->rs_uid);
                    $to_name = $this->cus_obj->get_customer_name($report_data[0]->rs_uid);
                    $attachment = $pdf_save_path.str_replace("'","", $pdf_filename);
                    
                    $email_send_obj->sendingMail($to_email, $to_name, 'monthly_report', $attachment, $this->topic_obj->get_topic_name($report_data[0]->rs_tid), $pdf_filename);
                }
            }
            
        }
    }
    
    public function handle_report(Request $request)
    {
        if(isset($request["mode"]) && $request["mode"] == 'initiate_report')
        {
            $rs_bg_color = '#000000';
            $rs_font_color = '#ffffff';
            $rs_bg_image_first_page = 'NA';
            $rs_bg_image_last_page = 'NA';
            $rs_logo = 'NA';
            $rs_tid = $request["rs_tid"];
            $rs_topic_from_date = date("Y-m-d", strtotime(date("Y-m-d") . " -90 day"));
            $rs_topic_to_date = date("Y-m-d");
            $rs_subtopic_font_color = '#ffffff';
            $rs_subtopic_bg_color = '#000000';
            $rs_subtopic_bg_image = 'NA';
            $rs_subtopic_from_date = date("Y-m-d", strtotime(date("Y-m-d") . " -90 day"));
            $rs_subtopic_to_date = date("Y-m-d");
            
            $loggedin_user_id = $this->cus_obj->get_parent_account_id();
            $login_user_id = \Session::get('_loggedin_customer_id');
            
            if(isset($request["rs_bg_color"]) && !empty($request["rs_bg_color"]))
                $rs_bg_color = '#'.str_replace('#', '', $request["rs_bg_color"]);
            
            if(isset($request["rs_font_color"]) && !empty($request["rs_font_color"]))
                $rs_font_color = '#'.str_replace('#', '', $request["rs_font_color"]);
            
            if(isset($request["rs_topic_from_date"]) && !empty($request["rs_topic_from_date"]))
            {
                //$rs_topic_from_date = date("Y-m-d", strtotime ($request["rs_topic_from_date"]));
                $rs_topic_from_date = date_create_from_format('j F, Y', $request["rs_topic_from_date"]);
                $rs_topic_from_date = date_format($rs_topic_from_date, 'Y-m-d');
            }
            
            if(isset($request["rs_topic_to_date"]) && !empty($request["rs_topic_to_date"]))
            {
                //$rs_topic_to_date = date("Y-m-d", strtotime ($request["rs_topic_to_date"]));
                $rs_topic_to_date = date_create_from_format('j F, Y', $request["rs_topic_to_date"]);
                $rs_topic_to_date = date_format($rs_topic_to_date, 'Y-m-d');
            }
            
            if(isset($request["rs_subtopic_font_color"]) && !empty($request["rs_subtopic_font_color"]))
                $rs_subtopic_font_color = '#'.str_replace('#', '', $request["rs_subtopic_font_color"]);
            
            if(isset($request["rs_subtopic_bg_color"]) && !empty($request["rs_subtopic_bg_color"]))
                $rs_subtopic_bg_color = '#'.str_replace('#', '', $request["rs_subtopic_bg_color"]);
            
            if(isset($request["rs_subtopic_from_date"]) && !empty($request["rs_subtopic_from_date"]))
            {
                //$rs_subtopic_from_date = date("Y-m-d", strtotime ($request["rs_subtopic_from_date"]));
                $rs_subtopic_from_date = date_create_from_format('j F, Y', $request["rs_subtopic_from_date"]);
                $rs_subtopic_from_date = date_format($rs_subtopic_from_date, 'Y-m-d');
            }
            
            if(isset($request["rs_subtopic_to_date"]) && !empty($request["rs_subtopic_to_date"]))
            {
                //$rs_subtopic_to_date = date("Y-m-d", strtotime ($request["rs_subtopic_to_date"]));
                $rs_subtopic_to_date = date_create_from_format('j F, Y', $request["rs_subtopic_to_date"]);
                $rs_subtopic_to_date = date_format($rs_subtopic_to_date, 'Y-m-d');
            }
            
            //First page bg image
            if($request->hasFile('rs_bg_image_first_page')) 
            {
                $image = $request->file('rs_bg_image_first_page');
                $file_extension = strtolower($image->getClientOriginalExtension());
                $path = public_path(). '/reports-images/';
                $rs_bg_image_first_page = md5(time() . $image->getClientOriginalName()) . '.' . $image->getClientOriginalExtension();

                $allowed_extensions = array('jpg', 'png', 'jpeg');

                if(in_array($file_extension, $allowed_extensions))
                {

                    $image->move($path, $rs_bg_image_first_page);
                }
            }
            
            //Last page bg image
            if($request->hasFile('rs_bg_image_last_page')) 
            {
                $image = $request->file('rs_bg_image_last_page');
                $file_extension = strtolower($image->getClientOriginalExtension());
                $path = public_path(). '/reports-images/';
                $rs_bg_image_last_page = md5(time() . $image->getClientOriginalName()) . '.' . $image->getClientOriginalExtension();

                $allowed_extensions = array('jpg', 'png', 'jpeg');

                if(in_array($file_extension, $allowed_extensions))
                {

                    $image->move($path, $rs_bg_image_last_page);
                }
            }
            
            //Logo
            if($request->hasFile('rs_logo')) 
            {
                $image = $request->file('rs_logo');
                $file_extension = strtolower($image->getClientOriginalExtension());
                $path = public_path(). '/reports-images/';
                $rs_logo = md5(time() . $image->getClientOriginalName()) . '.' . $image->getClientOriginalExtension();

                $allowed_extensions = array('jpg', 'png', 'jpeg');

                if(in_array($file_extension, $allowed_extensions))
                {

                    $image->move($path, $rs_logo);
                }
            }
            
            //sub topic title page bg image
            if($request->hasFile('rs_subtopic_bg_image')) 
            {
                $image = $request->file('rs_subtopic_bg_image');
                $file_extension = strtolower($image->getClientOriginalExtension());
                $path = public_path(). '/reports-images/';
                $rs_subtopic_bg_image = md5(time() . $image->getClientOriginalName()) . '.' . $image->getClientOriginalExtension();

                $allowed_extensions = array('jpg', 'png', 'jpeg');

                if(in_array($file_extension, $allowed_extensions))
                {

                    $image->move($path, $rs_subtopic_bg_image);
                }
            }
            
            $subtopic_ids = '';
            if(isset($request["sub_topics"]) && !empty($request["sub_topics"]) && count($request["sub_topics"])>0)
            {
                for($i=0; $i<count($request["sub_topics"]); $i++)
                {
                    $subtopic_ids .= $request["sub_topics"][$i].',';
                }
                
                $subtopic_ids = substr($subtopic_ids, 0, -1);
            }
            else
                $subtopic_ids = 'ALL';
            
            DB::insert("INSERT INTO reports_settings SET rs_uid = ".$loggedin_user_id.", rs_uid_loggedin = ".$login_user_id.", rs_tid = ".$rs_tid.", rs_bg_color = '".$rs_bg_color."', rs_font_color = '".$rs_font_color."', rs_logo = '".$rs_logo."', rs_bg_image_first_page = '".$rs_bg_image_first_page."', rs_bg_image_last_page = '".$rs_bg_image_last_page."', rs_topic_from_date = '".$rs_topic_from_date."', rs_topic_to_date = '".$rs_topic_to_date."', rs_subtopic_ids = '".$subtopic_ids."', rs_subtopic_bg_color = '".$rs_subtopic_bg_color."', rs_subtopic_font_color = '".$rs_subtopic_font_color."', rs_subtopic_bg_image = '".$rs_subtopic_bg_image."', rs_subtopic_from_date = '".$rs_subtopic_from_date."', rs_subtopic_to_date = '".$rs_subtopic_to_date."', rs_req_time = NOW(), rs_status = 'p'");
            
            echo 'Success';
        }
        else if(isset($request["mode"]) && $request["mode"] == 'delete_record_handler' && isset($request["section"]) && $request["section"] == 'report')
        {
            //delete report and data
            $loggedin_user_id = $this->cus_obj->get_parent_account_id();
            $path = public_path(). '/reports-images/';
            
            $report_data = DB::select("SELECT * FROM reports_settings WHERE rs_uid = ".$loggedin_user_id." AND rs_id = ".$request["record_id"]);
            
            if(file_exists($path.$report_data[0]->rs_logo) && is_file($path.$report_data[0]->rs_logo))
                unlink($path.$report_data[0]->rs_logo);
            
            if(file_exists($path.$report_data[0]->rs_bg_image_first_page) && is_file($path.$report_data[0]->rs_bg_image_first_page))
                unlink($path.$report_data[0]->rs_bg_image_first_page);
            
            if(file_exists($path.$report_data[0]->rs_bg_image_last_page) && is_file($path.$report_data[0]->rs_bg_image_last_page))
                unlink($path.$report_data[0]->rs_bg_image_last_page);
            
            if(file_exists($path.$report_data[0]->rs_subtopic_bg_image) && is_file($path.$report_data[0]->rs_subtopic_bg_image))
                unlink($path.$report_data[0]->rs_subtopic_bg_image);
            
            if(file_exists(public_path().'/dashboard-reports/'.$report_data[0]->rs_filename) && is_file(public_path().'/dashboard-reports/'.$report_data[0]->rs_filename))
                unlink(public_path().'/dashboard-reports/'.$report_data[0]->rs_filename);
            
            DB::delete("DELETE FROM reports_settings WHERE rs_uid = ".$loggedin_user_id." AND rs_id = ".$request["record_id"]);
            
            echo 'Success';
        }
    }
    
    public function get_data_for_report_from_es($mode, $topic_id, $subtopic_id, $date_from, $date_to, $extra_param)
    {
        $this->client = ClientBuilder::create()->setHosts([env('ELASTICSEARCH_HOST').":".env('ELASTICSEARCH_PORT')])->build();
        $this->search_index_name = env('ELASTICSEARCH_DEFAULTINDEX');
        $this->printmedia_search_index_name = env('PRINTMEDIA_ELASTIC_INDEX');
        //$greater_than_time = date("Y-m-d", strtotime($date_from));
        //$less_than_time = date("Y-m-d", strtotime($date_to));
        
        $less_than_time = date_create_from_format('Y-m-d', $date_to);
        $less_than_time = date_format($less_than_time, 'Y-m-d');
        
        $greater_than_time = date_create_from_format('Y-m-d', $date_from);
        $greater_than_time = date_format($greater_than_time, 'Y-m-d');
        
        $inc_dec_to_date = '';
        $inc_dec_from_date = '';
        $subtopic_query_string = '';
        
        $topic_query_string = $this->topic_obj->get_topic_elastic_query($topic_id);
        
        if(isset($subtopic_id) && !empty($subtopic_id))
            $subtopic_query_string = $this->subtopic_obj->get_subtopic_elastic_query($subtopic_id);
        
        $inc_dec_to_date = $greater_than_time;
        
        $days_diff = $this->gen_func_obj->date_difference($less_than_time, $greater_than_time);
        //$tmp_tm = date("Y-m-d", strtotime('-' . $days_diff . ' day', strtotime(date("Y-m-d", strtotime($inc_dec_to_date)))));
        //dd($inc_dec_to_date);
        //$tmp_tm = date("Y-m-d", strtotime('-' . $days_diff . ' day', $inc_dec_to_date)); 
        //$inc_dec_from_date = $tmp_tm;
        
        $date = date_create($greater_than_time);
        date_sub($date, date_interval_create_from_date_string($days_diff." days"));
        $inc_dec_from_date = date_format($date,"Y-m-d");
        
        if($mode == 'digital_mentions')
        {
            //Digital = From all sources other than printmedia & social media
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string.' AND source:("khaleej_times" OR "Omanobserver" OR "Time of oman" OR "Blogs" OR "FakeNews" OR "News")']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);
            $digital_mentions = $results["count"];
            //dd($params);
            return $digital_mentions;
        }
        else if($mode == 'conventional_mentions')
        {
            //Conventional = Only Printmedia
            $params = [
                'index' => $this->printmedia_search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => [ 'query' => str_replace('p_message_text', 'p_message', $topic_query_string) ] ],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);

            $conventional_mentions = $results["count"];
            
            return $conventional_mentions;
        }
        else if($mode == 'main_topic_mentions')
        {
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string ]],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);
            $mentions = $results["count"];
            //dd($params);
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string ]],
                                ['range' => ['p_created_time' => ['gte' => $inc_dec_from_date, 'lte' => $inc_dec_to_date ]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results1 = $this->client->count($params);
            $mentions1 = $results1["count"];
            
            if ($mentions > $mentions1) //increase
                $result_diff = $mentions - $mentions1;
            else
                $result_diff = $mentions1 - $mentions;

            

            if ($mentions == 0)
                $per_diff = 0;
            else
                $per_diff = ($result_diff / $mentions) * 100;

            if ($mentions > $mentions1) //increase
                $response = number_format($mentions).'|+'.number_format($per_diff, 2).'% increase';
            else
                $response = number_format($mentions).'|-'.number_format($per_diff, 2).'% decrease';
            
            return $response;
        }
        else if($mode == 'main_topic_comments')
        {
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string ]],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ],
                    'aggs' => [
                        'total_comments' => ['sum' => ['field' => 'p_comments']]
                    ]
                ]
            ];

            $results = $this->client->search($params);
            $comments1 = $results["aggregations"]["total_comments"]["value"];

            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string ]],
                                ['range' => ['p_created_time' => ['gte' => $inc_dec_from_date, 'lte' => $inc_dec_to_date]]]
                            ]
                        ]
                    ],
                    'aggs' => [
                        'total_comments' => ['sum' => ['field' => 'p_comments']]
                    ]
                ]
            ];

            $results1 = $this->client->search($params);
            $comments2 = $results1["aggregations"]["total_comments"]["value"];

            if ($comments1 > $comments2) //increase
                $result_diff = $comments1 - $comments2;
            else
                $result_diff = $comments2 - $comments1;

            

            if ($comments1 == 0)
                $per_diff = 0;
            else
                $per_diff = ($result_diff / $comments1) * 100;

            if ($comments1 > $comments2) //increase
                $response = number_format($comments1).'|+'.number_format($per_diff, 2).'% increase';
            else
                $response = number_format($comments1).'|-'.number_format($per_diff, 2).'% decrease';
            
            return $response;
        }
        else if($mode == 'main_topic_language')
        {
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string ]],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);
            $total_mentions = $results["count"];
            
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string.' AND lange_detect:("en")' ]],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);
            $eng_mentions = $results["count"];
            
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string.' AND lange_detect:("ar")' ]],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);
            $ar_mentions = $results["count"];
            
            if($total_mentions == 0)
                $total_mentions = 1;
            
            $mentions_english = number_format(($eng_mentions/$total_mentions)*100, 2);
            $mentions_arabic = number_format(($ar_mentions/$total_mentions)*100, 2);
            
            $other_mentions = 100 - ($mentions_english + $mentions_arabic);
            
            $response = $mentions_english.'|'.$mentions_arabic.'|'. number_format($other_mentions, 2);
            
            return $response;
        }
        else if($mode == 'main_topic_reach')
        {
            //estimated reach 50% normal user followers + 5% influencers + total engagement
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'size' => '0',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string]],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]],
                                ['range' => ['u_followers' => ['gt' => 0, 'lt' => 1000]]]
                            ]
                        ]
                    ],
                    'aggs' => [
                        'total_normal_users_followers' => ['sum' => ['field' => 'u_followers']]
                    ]
                ]
            ];
            $results = $this->client->search($params);

            $total_normal_users_followers = $results["aggregations"]["total_normal_users_followers"]["value"];
            $normal_user_followers = ceil($total_normal_users_followers * 0.50); //50%

            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'size' => '0',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string]],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]],
                                ['range' => ['u_followers' => ['gt' => 1000]]]
                            ]
                        ]
                    ],
                    'aggs' => [
                        'total_influencer_followers' => ['sum' => ['field' => 'u_followers']]
                    ]
                ]
            ];
            $results = $this->client->search($params);

            $total_influencer_followers = $results["aggregations"]["total_influencer_followers"]["value"];
            $influencer_user_followers = ceil($total_influencer_followers * 0.05); //5% of followers

            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'size' => '0',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string]],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ],
                    'aggs' => [
                        'total_shares' => ['sum' => ['field' => 'p_shares']],
                        'total_comments' => ['sum' => ['field' => 'p_comments']],
                        'total_likes' => ['sum' => ['field' => 'p_likes']]
                    ]
                ]
            ];

            $results = $this->client->search($params);
            $tot_eng = $results["aggregations"]["total_shares"]["value"] + $results["aggregations"]["total_comments"]["value"] + $results["aggregations"]["total_likes"]["value"];
            $estimated_reach = $normal_user_followers + $influencer_user_followers + $tot_eng;
            
            //for previous days diff
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'size' => '0',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string]],
                                ['range' => ['p_created_time' => ['gte' => $inc_dec_from_date, 'lte' => $inc_dec_to_date]]],
                                ['range' => ['u_followers' => ['gt' => 0, 'lt' => 1000]]]
                            ]
                        ]
                    ],
                    'aggs' => [
                        'total_normal_users_followers' => ['sum' => ['field' => 'u_followers']]
                    ]
                ]
            ];
            $results = $this->client->search($params);

            $total_normal_users_followers1 = $results["aggregations"]["total_normal_users_followers"]["value"];
            $normal_user_followers1 = ceil($total_normal_users_followers1 * 0.50); //50%

            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'size' => '0',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string]],
                                ['range' => ['p_created_time' => ['gte' => $inc_dec_from_date, 'lte' => $inc_dec_to_date]]],
                                ['range' => ['u_followers' => ['gt' => 1000]]]
                            ]
                        ]
                    ],
                    'aggs' => [
                        'total_influencer_followers' => ['sum' => ['field' => 'u_followers']]
                    ]
                ]
            ];
            $results = $this->client->search($params);

            $total_influencer_followers1 = $results["aggregations"]["total_influencer_followers"]["value"];
            $influencer_user_followers1 = ceil($total_influencer_followers1 * 0.05); //5% of followers

            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'size' => '0',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string]],
                                ['range' => ['p_created_time' => ['gte' => $inc_dec_from_date, 'lte' => $inc_dec_to_date]]]
                            ]
                        ]
                    ],
                    'aggs' => [
                        'total_shares' => ['sum' => ['field' => 'p_shares']],
                        'total_comments' => ['sum' => ['field' => 'p_comments']],
                        'total_likes' => ['sum' => ['field' => 'p_likes']]
                    ]
                ]
            ];

            $results = $this->client->search($params);
            $tot_eng1 = $results["aggregations"]["total_shares"]["value"] + $results["aggregations"]["total_comments"]["value"] + $results["aggregations"]["total_likes"]["value"];
            $estimated_reach1 = $normal_user_followers1 + $influencer_user_followers1 + $tot_eng1;
            
            if ($estimated_reach > $estimated_reach1) //increase
                $result_diff = $estimated_reach - $estimated_reach1;
            else
                $result_diff = $estimated_reach1 - $estimated_reach;

            

            if ($estimated_reach == 0)
                $per_diff = 0;
            else
                $per_diff = ($result_diff / $estimated_reach) * 100;
            
            if ($estimated_reach > $estimated_reach1)
                $response = number_format($estimated_reach).'|+'.number_format($per_diff, 2).'% increase';
            else
                $response = number_format($estimated_reach).'|+'.number_format($per_diff, 2).'% increase';
            
            return $response;
        }
        else if($mode == 'main_topic_chanels')
        {
            $response_output = '';
            
            if($extra_param == 'subtopic_channels')
            {
                $topic_query_string .= $subtopic_query_string;
            }
            
            //videos
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string . ' AND source:("Youtube" OR "Vimeo")']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);

            $response_output .= $results["count"].'|';
            
            //news sources
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string . ' AND source:("FakeNews" OR "News")']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);

            $response_output .= $results["count"].'|';

            //twitter
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string . ' AND source:("Twitter")']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);

            $response_output .= $results["count"].'|';

            //Instagram
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string . ' AND source:("Instagram")']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);

            $response_output .= $results["count"].'|';

            //Blogs
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string . ' AND source:("Blogs")']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);

            $response_output .= $results["count"].'|';

            //Reddit
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string . ' AND source:("Reddit")']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);

            $response_output .= $results["count"].'|';

            //Tumblr
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string . ' AND source:("Tumblr")']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);

            $response_output .= $results["count"].'|';

            //Facebook
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string . ' AND source:("Facebook")']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);

            $response_output .= $results["count"].'|';
            
            //Pinterest
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string . ' AND source:("Pinterest")']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);

            $response_output .= $results["count"].'|';
            
            //Printmedia
            $params = [
                'index' => $this->printmedia_search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => [ 'query' => str_replace('p_message_text', 'p_message', $topic_query_string) ] ],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);

            $response_output .= $results["count"].'|';
            
            //Linkedin
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string . ' AND source:("Linkedin")']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);

            $response_output .= $results["count"].'|';
            
            
            
            return substr($response_output, 0, -1);
        }
        else if($mode == 'main_topic_eng')
        {
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string ]],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ],
                    'aggs' => [
                        'total_shares' => ['sum' => ['field' => 'p_shares']],
                        'total_comments' => ['sum' => ['field' => 'p_comments']],
                        'total_likes' => ['sum' => ['field' => 'p_likes']]
                    ]
                ]
            ];

            $results = $this->client->search($params);
            $tot_eng = $results["aggregations"]["total_shares"]["value"] + $results["aggregations"]["total_comments"]["value"] + $results["aggregations"]["total_likes"]["value"];

            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string ]],
                                ['range' => ['p_created_time' => ['gte' => $inc_dec_from_date, 'lte' => $inc_dec_to_date]]]
                            ]
                        ]
                    ],
                    'aggs' => [
                        'total_shares' => ['sum' => ['field' => 'p_shares']],
                        'total_comments' => ['sum' => ['field' => 'p_comments']],
                        'total_likes' => ['sum' => ['field' => 'p_likes']]
                    ]
                ]
            ];

            $results1 = $this->client->search($params);
            $tot_eng1 = $results1["aggregations"]["total_shares"]["value"] + $results1["aggregations"]["total_comments"]["value"] + $results1["aggregations"]["total_likes"]["value"];

            if ($tot_eng > $tot_eng1) //increase
                $result_diff = $tot_eng - $tot_eng1;
            else
                $result_diff = $tot_eng1 - $tot_eng;

            

            if ($tot_eng == 0)
                $per_diff = 0;
            else
                $per_diff = ($result_diff / $tot_eng) * 100;

            if ($tot_eng > $tot_eng1) //increase
                $response = number_format($tot_eng).'|+'.number_format($per_diff, 2).'% increase';
            else
                $response = number_format($tot_eng).'|-'.number_format($per_diff, 2).'% decrease';
            
            return $response;
        }
        else if($mode == 'main_topic_shares')
        {
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string . ' AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ],
                    'aggs' => [
                        'total_shares' => ['sum' => ['field' => 'p_shares']]
                    ]
                ]
            ];

            $results = $this->client->search($params);

            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string . ' AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")']],
                                ['range' => ['p_created_time' => ['gte' => $inc_dec_from_date, 'lte' => $inc_dec_to_date]]]
                            ]
                        ]
                    ],
                    'aggs' => [
                        'total_shares' => ['sum' => ['field' => 'p_shares']]
                    ]
                ]
            ];

            $results1 = $this->client->search($params);

            if ($results["aggregations"]["total_shares"]["value"] > $results1["aggregations"]["total_shares"]["value"]) //increase
                $result_diff = $results["aggregations"]["total_shares"]["value"] - $results1["aggregations"]["total_shares"]["value"];
            else
                $result_diff = $results1["aggregations"]["total_shares"]["value"] - $results["aggregations"]["total_shares"]["value"];

            if ($results["aggregations"]["total_shares"]["value"] == 0)
                $per_diff = 0;
            else
                $per_diff = ($result_diff / $results["aggregations"]["total_shares"]["value"]) * 100;

            

            if ($results["aggregations"]["total_shares"]["value"] > $results1["aggregations"]["total_shares"]["value"]) //increase
                $response = number_format($results["aggregations"]["total_shares"]["value"]).'|+'.number_format($per_diff, 2).'% increase';
            else
                $response = number_format($results["aggregations"]["total_shares"]["value"]).'|-'.number_format($per_diff, 2).'% decrease';
            
            return $response;
        }
        else if($mode == 'main_topic_likes')
        {
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string ]],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ],
                    'aggs' => [
                        'total_likes' => ['sum' => ['field' => 'p_likes']]
                    ]
                ]
            ];

            $results = $this->client->search($params);

            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string ]],
                                ['range' => ['p_created_time' => ['gte' => $inc_dec_from_date, 'lte' => $inc_dec_to_date]]]
                            ]
                        ]
                    ],
                    'aggs' => [
                        'total_likes' => ['sum' => ['field' => 'p_likes']]
                    ]
                ]
            ];

            $results1 = $this->client->search($params);

            if ($results["aggregations"]["total_likes"]["value"] > $results1["aggregations"]["total_likes"]["value"]) //increase
                $result_diff = $results["aggregations"]["total_likes"]["value"] - $results1["aggregations"]["total_likes"]["value"];
            else
                $result_diff = $results1["aggregations"]["total_likes"]["value"] - $results["aggregations"]["total_likes"]["value"];

            if ($results["aggregations"]["total_likes"]["value"] == 0)
                $per_diff = 0;
            else
                $per_diff = ($result_diff / $results["aggregations"]["total_likes"]["value"]) * 100;

            

            if ($results["aggregations"]["total_likes"]["value"] > $results1["aggregations"]["total_likes"]["value"]) //increase
                $response = number_format($results["aggregations"]["total_likes"]["value"]).'|+'.number_format($per_diff, 2).'% increase';
            else
                $response = number_format($results["aggregations"]["total_likes"]["value"]).'|-'.number_format($per_diff, 2).'% decrease';
            
            return $response;
        }
        else if($mode == 'inf_data')
        {
            $followers_from = 0;
            $followers_to = 0;

            if ($extra_param == 'nano')
            {
                $followers_from = 1000;
                $followers_to = 10000;
            }
            else if ($extra_param == 'micro')
            {
                $followers_from = 10000;
                $followers_to = 50000;
            }
            else if ($extra_param == 'midtier')
            {
                $followers_from = 50000;
                $followers_to = 500000;
            }
            else if ($extra_param == 'macro')
            {
                $followers_from = 500000;
                $followers_to = 1000000;
            }
            else if ($extra_param == 'mega')
            {
                $followers_from = 1000000;
                $followers_to = 5000000;
            }
            else if ($extra_param == 'celebrity')
            {
                $followers_from = 5000000;
                $followers_to = 500000000;
            }

            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string ]],
                                ['exists' => ['field' => 'u_profile_photo']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]],
                                ['range' => ['u_followers' => ['gte' => $followers_from, 'lte' => $followers_to]]]
                            ],
                            'must_not' => [
                                ['term' => ['u_profile_photo.keyword' => '']]
                            ]
                        ]
                    ],
                    'aggs' => [
                        'group_by_user' => ['terms' => ['field' => 'u_source.keyword', 'size' => 3, 'order' => ['followers_count' => 'desc']],
                            'aggs' => [
                                'grouped_results' => [
                                    'top_hits' => ['size' => 1, '_source' => ['include' => ['u_fullname', 'u_profile_photo', 'u_country', 'u_followers']],
                                        'sort' => ['p_created_time' => ['order' => 'desc']]
                                    ]
                                ],
                                'followers_count' => ['max' => ['script' => 'doc.u_followers']]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->search($params);

            $n = 1;
            $j = 0;
            $data_array = array();

            for ($i = 0; $i < count($results["aggregations"]["group_by_user"]["buckets"]); $i ++)
            {
                if (!empty($results["aggregations"]["group_by_user"]["buckets"][$i]["key"]))
                {
                    $flag_image = '';
                    if(isset($results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_country"]))
                        $flag_image = '<img src="https://dashboard.datalyticx.ai/images/flags/4x3/'.$this->gen_func_obj->get_country_flag($results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_country"]) . '" width="30">';
                    else
                        $flag_image = '&nbsp;';
                    
                    $data_array[$i]['profile_image'] = $results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_profile_photo"];
                    $data_array[$i]['fullname'] = $results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_fullname"];
                    $data_array[$i]['country'] = $flag_image;
                    $data_array[$i]['followers'] = $results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_followers"];
                    $data_array[$i]['posts'] = $results["aggregations"]["group_by_user"]["buckets"][$i]["doc_count"];
                }
            }
            
            return $data_array;
        }
        else if($mode == 'source_channel_data')
        {
            if($extra_param == 'News')
                $topic_query_string .= ' AND source:("FakeNews" OR "News")';
            else if($extra_param == 'Youtube')
                $topic_query_string .= ' AND source:("Youtube" OR "Vimeo")';
            else
                $topic_query_string .= ' AND source:("'.$extra_param.'")';
                
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'from' => 0,
                'size' => 4,
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string ]],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ],
                    'sort' => [
                        ['p_likes' => ['order' => 'desc']]
                    ]
                ]
            ];

            $results = $this->client->search($params);
            
            $posts_html = '';
            for($ii=0; $ii<count($results["hits"]["hits"]); $ii++)
            {
                $posts_html .= '<div style="width: 300px; float:left; padding-right: 30px; padding-top: 10px; border-bottom: 1px solid #999; height: 320px;">'.$this->get_report_postview_html($results["hits"]["hits"][$ii]["_source"]).'</div>';
            }
            
            return $posts_html;
        }
        else if($mode == 'main_topic_sentiments')
        {
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string ]],
                                ['match' => ['predicted_sentiment_value' => $extra_param]],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);
            return $results["count"];
        }
        else if($mode == 'subtopic_mentions')
        {
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string . ' AND '.$subtopic_query_string ]],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->count($params);
            $tot_mentions = $results["count"];

            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string . ' AND '.$subtopic_query_string ]],
                                ['range' => ['p_created_time' => ['gte' => $inc_dec_from_date, 'lte' => $inc_dec_to_date]]]
                            ]
                        ]
                    ]
                ]
            ];

            $results1 = $this->client->count($params);
            $tot_mentions1 = $results1["count"];

            if ($tot_mentions > $tot_mentions1) //increase
                $result_diff = $tot_mentions - $tot_mentions1;
            else
                $result_diff = $tot_mentions1 - $tot_mentions;

            if ($tot_mentions == 0)
                $per_diff = 0;
            else
                $per_diff = ($result_diff / $tot_mentions) * 100;

            

            if ($tot_mentions > $tot_mentions1) //increase
                $response = $this->gen_func_obj->format_number_data($tot_mentions).'|<span style="color: green;">+' . number_format($per_diff, 2).'%</span>';
            else
                $response = $this->gen_func_obj->format_number_data($tot_mentions).'|<span style="color: red;">-'.number_format($per_diff, 2).'%</span>';
            
            return $response;
        }
        else if($mode == 'subtopic_engagements')
        {
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string . ' AND '.$subtopic_query_string ]],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ],
                    'aggs' => [
                        'total_shares' => ['sum' => ['field' => 'p_shares']],
                        'total_comments' => ['sum' => ['field' => 'p_comments']],
                        'total_likes' => ['sum' => ['field' => 'p_likes']]
                    ]
                ]
            ];

            $results = $this->client->search($params);
            $tot_eng = $results["aggregations"]["total_shares"]["value"] + $results["aggregations"]["total_comments"]["value"] + $results["aggregations"]["total_likes"]["value"];

            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string . ' AND '.$subtopic_query_string ]],
                                ['range' => ['p_created_time' => ['gte' => $inc_dec_from_date, 'lte' => $inc_dec_to_date]]]
                            ]
                        ]
                    ],
                    'aggs' => [
                        'total_shares' => ['sum' => ['field' => 'p_shares']],
                        'total_comments' => ['sum' => ['field' => 'p_comments']],
                        'total_likes' => ['sum' => ['field' => 'p_likes']]
                    ]
                ]
            ];

            $results1 = $this->client->search($params);
            $tot_eng1 = $results1["aggregations"]["total_shares"]["value"] + $results1["aggregations"]["total_comments"]["value"] + $results1["aggregations"]["total_likes"]["value"];

            if ($tot_eng > $tot_eng1) //increase
                $result_diff = $tot_eng - $tot_eng1;
            else
                $result_diff = $tot_eng1 - $tot_eng;

            if ($tot_eng == 0)
                $per_diff = 0;
            else
                $per_diff = ($result_diff / $tot_eng) * 100;

            

            if ($tot_eng > $tot_eng1) //increase
                $response = $this->gen_func_obj->format_number_data($tot_eng).'|<span style="color: green;">+' . number_format($per_diff, 2).'%</span>';
            else
                $response = $this->gen_func_obj->format_number_data($tot_eng).'|<span style="color: red;">-'.number_format($per_diff, 2).'%</span>';
            
            return $response;
        }
        else if($mode == 'subtopic_shares')
        {
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string . ' AND '.$subtopic_query_string . ' AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ],
                    'aggs' => [
                        'total_shares' => ['sum' => ['field' => 'p_shares']]
                    ]
                ]
            ];

            $results = $this->client->search($params);
            
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string . ' AND '.$subtopic_query_string . ' AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")']],
                                ['range' => ['p_created_time' => ['gte' => $inc_dec_from_date, 'lte' => $inc_dec_to_date]]]
                            ]
                        ]
                    ],
                    'aggs' => [
                        'total_shares' => ['sum' => ['field' => 'p_shares']]
                    ]
                ]
            ];

            $results1 = $this->client->search($params);

            if ($results["aggregations"]["total_shares"]["value"] > $results1["aggregations"]["total_shares"]["value"]) //increase
                $result_diff = $results["aggregations"]["total_shares"]["value"] - $results1["aggregations"]["total_shares"]["value"];
            else
                $result_diff = $results1["aggregations"]["total_shares"]["value"] - $results["aggregations"]["total_shares"]["value"];

            if ($results["aggregations"]["total_shares"]["value"] == 0)
                $per_diff = 0;
            else
                $per_diff = ($result_diff / $results["aggregations"]["total_shares"]["value"]) * 100;

            if ($results["aggregations"]["total_shares"]["value"] > $results1["aggregations"]["total_shares"]["value"]) //increase
                $response = $this->gen_func_obj->format_number_data($results["aggregations"]["total_shares"]["value"]).'|<span style="color: green;">+' . number_format($per_diff, 2).'%</span>';
            else
                $response = $this->gen_func_obj->format_number_data($results["aggregations"]["total_shares"]["value"]).'|<span style="color: red;">-'.number_format($per_diff, 2).'%</span>';
            
            return $response;
        }
        else if($mode == 'subtopic_likes')
        {
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string . ' AND '.$subtopic_query_string . ' AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ],
                    'aggs' => [
                        'total_likes' => ['sum' => ['field' => 'p_likes']]
                    ]
                ]
            ];

            $results = $this->client->search($params);
            
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string . ' AND '.$subtopic_query_string . ' AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Instagram" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Facebook")']],
                                ['range' => ['p_created_time' => ['gte' => $inc_dec_from_date, 'lte' => $inc_dec_to_date]]]
                            ]
                        ]
                    ],
                    'aggs' => [
                        'total_likes' => ['sum' => ['field' => 'p_likes']]
                    ]
                ]
            ];

            $results1 = $this->client->search($params);

            if ($results["aggregations"]["total_likes"]["value"] > $results1["aggregations"]["total_likes"]["value"]) //increase
                $result_diff = $results["aggregations"]["total_likes"]["value"] - $results1["aggregations"]["total_likes"]["value"];
            else
                $result_diff = $results1["aggregations"]["total_likes"]["value"] - $results["aggregations"]["total_likes"]["value"];

            if ($results["aggregations"]["total_likes"]["value"] == 0)
                $per_diff = 0;
            else
                $per_diff = ($result_diff / $results["aggregations"]["total_likes"]["value"]) * 100;

            if ($results["aggregations"]["total_likes"]["value"] > $results1["aggregations"]["total_likes"]["value"]) //increase
                $response = $this->gen_func_obj->format_number_data($results["aggregations"]["total_likes"]["value"]).'|<span style="color: green;">+' . number_format($per_diff, 2).'%</span>';
            else
                $response = $this->gen_func_obj->format_number_data($results["aggregations"]["total_likes"]["value"]).'|<span style="color: red;">-'.number_format($per_diff, 2).'%</span>';
            
            return $response;
        }
        else if($mode == 'revenue_loss')
        {
            $exp_metrics = $this->subtopic_obj->get_subtopic_metrics($subtopic_id);

            if(stristr($exp_metrics[0]->exp_metrics, 'potential_loss') === FALSE)
            {
                return 'NA';
            }
            else
            {
                $roi_data = DB::select("SELECT * FROM roi_settings WHERE roi_cx_id = ".$subtopic_id);
                                
                $topic_query_string .= ' AND '.$subtopic_query_string;

                //negative unique uers
                $params = [
                    'index' => $this->search_index_name,
                    'type' => 'mytype',
                    'from' => '0',
                    'size' => '0',
                    'body' => [
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['query_string' => ['query' => $topic_query_string . ' AND predicted_sentiment_value:("negative") AND NOT source:("FakeNews" OR "News")']],
                                    ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                                ]
                            ]
                        ],
                        'aggs' => ['group_by_user' => ['terms' => ['field' => 'u_source.keyword', 'size' => 1]], 'unique_users' => ['cardinality' => ['field' => 'u_source.keyword']]]
                    ]
                ];

                $results = $this->client->search($params);
                $unique_neg_users = $results["aggregations"]["unique_users"]["value"];

                if(count($roi_data) > 0)
                {
                    $avg_roi = preg_replace('/[^0-9]+/', '', $roi_data[0]->roi_avg_revenue);
                    $churn_rate = ($unique_neg_users * $roi_data[0]->roi_churn_rate) / 100;

                    $potential_loss = $churn_rate * $avg_roi;

                    return $roi_data[0]->roi_currency." ".number_format(ceil($potential_loss))."|".$roi_data[0]->roi_churn_rate;
                }                   
                
            }
        }
        else if($mode == 'most_active_users')
        {
            $params = [
                'index' => $this->search_index_name,
                'type' => 'mytype',
                'from' => '0',
                'size' => '0',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => ['query' => $topic_query_string . ' AND source:("Twitter" OR "Youtube" OR "Linkedin" OR "FakeNews" OR "News" OR "Pinterest" OR "Reddit" OR "Tumblr" OR "Vimeo" OR "Instagram" OR "Facebook")' ]],
                                ['exists' => ['field' => 'u_profile_photo']],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ],
                            'must_not' => [
                                ['term' => ['u_profile_photo.keyword' => '']]
                            ]
                        ]
                    ],
                    'aggs' => [
                        'group_by_user' => ['terms' => ['field' => 'u_source.keyword', 'size' => 26],
                            'aggs' => [
                                'grouped_results' => [
                                    'top_hits' => ['size' => 1, '_source' => ['include' => ['u_fullname', 'u_profile_photo', 'u_date_joined', 'u_country', 'u_followers']],
                                        'sort' => ['p_created_time' => ['order' => 'desc']]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            $results = $this->client->search($params);

            $n = 1;
            $j = 0;

            $u_html = '';
            
            for ($i = 0; $i < count($results["aggregations"]["group_by_user"]["buckets"]); $i ++)
            {
                if (!empty($results["aggregations"]["group_by_user"]["buckets"][$i]["key"]))
                {
                    $bg_color = ($j == 0 || $j == 1) ? '#f9f9f9' : '#ffffff';
                    
                    $follow = 0;
                    if(isset($results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_followers"]))
                        $follow = $results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_followers"];
                    
                    $flag_image = '';
                    if(isset($results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_country"]))
                        $flag_image = '<img src="https://dashboard.datalyticx.ai/images/flags/4x3/'.$this->gen_func_obj->get_country_flag($results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_country"]) . '" width="30">';
                    else
                        $flag_image = '&nbsp;';

                    $u_html .= '<div style="background: ' . $bg_color . '; width: 50%; float: left; margin-left:0px; padding: 7px 0px 7px 0px; font-size: 15px;">';
                    $u_html .= '<table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td width="3%">&nbsp;&nbsp;&nbsp;' . $n . '.</td>
                                <td width="10%" style="text-align: center;"><img alt="" src="' . $results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_profile_photo"] . '" style="width: 25px; height: 25px;"></td>
                                <td width="44%" style="">'.$results["aggregations"]["group_by_user"]["buckets"][$i]["grouped_results"]["hits"]["hits"][0]["_source"]["u_fullname"] . '</td>
                                <td width="12%">'.$flag_image.'</td>
                                <td width="15%" style="text-align: right;">' . $this->gen_func_obj->format_number_data($follow) . '<p style="color:#cccccc; font-size: 12px;">Followers</p></td>
                                <td width="16%" style="text-align: right; padding-right: 15px;">' . $this->gen_func_obj->format_number_data($results["aggregations"]["group_by_user"]["buckets"][$i]["doc_count"]) . '<p style="color:#cccccc; font-size: 12px;">Posts</p></td>
                            </tr>
                        </table>';

                    $u_html .= '</div>';

                    $n = $n + 1;

                    if ($j == 3)
                        $j = 0;
                    else
                        $j = $j + 1;
                }
            }

            return $u_html;
        }
        else if($mode == 'printmedia_data')
        {
            $params = [
                'index' => $this->printmedia_search_index_name,
                'type' => 'mytype',
                'from' => '0',
                'size' => '6',
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['query_string' => [ 'query' => str_replace('p_message_text', 'p_message', $topic_query_string) ] ],
                                ['range' => ['p_created_time' => ['gte' => $greater_than_time, 'lte' => $less_than_time]]]
                            ]
                        ]
                    ],
                    'sort' => [
                        [ 'p_created_time' => ['order' => 'desc'] ]
                    ]
                ]
            ];

            $results = $this->client->search($params);

            $manual_print_media_post_html = '';
            for($ii=0; $ii<count($results["hits"]["hits"]); $ii++)
            {
                $src_data = DB::select("SELECT * FROM news_sources WHERE source_id = ".$results["hits"]["hits"][$ii]["_source"]["source"]);

                $pic = 'https://dashboard.datalyticx.ai/printmedia-data-entry/assets/uploads/news_sources/'.$this->gen_func_obj->decrypt($src_data[0]->source_image, $this->gen_func_obj->get_encryption_key());

                $emotion_icon = '&nbsp;';

                $post_id = $results["hits"]["hits"][$ii]["_source"]["p_id"];

                if (false === file_get_contents("https://dashboard.datalyticx.ai/printmedia-data-entry/assets/uploads/".$results["hits"]["hits"][$ii]["_source"]["post_full_detail_doc"],0,null,0,1))
                    $detail_url = $results["hits"]["hits"][$ii]["_source"]["p_url"];
                else
                    $detail_url = 'https://dashboard.datalyticx.ai/observer/pmd?pmdid='.$this->gen_func_obj->encrypt($this->gen_func_obj->encrypt($post_id, $this->gen_func_obj->get_encryption_key()), $this->gen_func_obj->get_encryption_key());

                $manual_print_media_post_html .= '
                <div style="width: 320px; float: left; margin: 0px 0px 25px 20px;">
                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td>
                            <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td style="background: #ffffff !important; border:1px solid #f0f0f0; padding:15px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="width:20%;"><img src="'.$pic.'" width="50" height="50"></td>
                                                <td style="width: 80%;"><h5 style="padding-bottom:6px; margin:0px;">'.$results["hits"]["hits"][$ii]["_source"]["title"].'</h5><span style="padding-top:5px; color:#8b91a0;"></span></td>
                                            </tr>
                                            <tr><td colspan="2">'.date("j M, Y h:i a", strtotime('+4 hours', strtotime($results["hits"]["hits"][$ii]["_source"]["p_created_time"]))).'</td></tr>
                                            <tr><td>&nbsp;</td></tr>
                                            <tr><td colspan="2"><div style="height: 50px; overflow-x: hidden; overflow-y: auto;">'.substr(strip_tags($results["hits"]["hits"][$ii]["_source"]["p_message"]), 0, 30).'...</div></td></tr>
                                            <tr>
                                                <td colspan="2">
                                                    <table width="100%">
                                                        <tr>
                                                            <td style="padding-top: 25px;">Source: '.$this->gen_func_obj->decrypt($src_data[0]->source_name, $this->gen_func_obj->get_encryption_key()).'&nbsp;&nbsp;|&nbsp;&nbsp;Reach: '.number_format($this->gen_func_obj->decrypt($src_data[0]->source_reach, $this->gen_func_obj->get_encryption_key())).'<br><a href="'.$detail_url.'" target="_blank">Read more</a></td>
                                                            </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                </div> ';
            }
            
            return $manual_print_media_post_html;
        }
    }
    
    public function get_report_postview_html($es_data)
    {
        $html_str = '';
        $user_data_string = '';
        $post_data_string = '';
        $images_path = env('PUBLIC_IMAGES_PATH');
        
        if (empty($es_data["p_picture"]))
            $img_src = env('PUBLIC_IMAGES_PATH')."grey.png";
        else
            $img_src = $es_data["p_picture"];
        
        if (!empty(trim($es_data["u_profile_photo"])))
            $user_profile_pic = $es_data["u_profile_photo"];
        else
            $user_profile_pic = env('PUBLIC_IMAGES_PATH')."grey.png";
        
        /**** User data string ****/
        if(isset($es_data["u_followers"]) && $es_data["u_followers"] > 0)
            $user_data_string .= 'Followers '.$this->gen_func_obj->format_number_data($es_data["u_followers"]).'&nbsp;|&nbsp;';
        
        if(isset($es_data["u_following"]) && $es_data["u_following"] > 0)
            $user_data_string .= 'Following '.$this->gen_func_obj->format_number_data($es_data["u_following"]).'&nbsp;|&nbsp;';
        
        if(isset($es_data["u_posts"]) && $es_data["u_posts"] > 0)
            $user_data_string .= 'Posts '.$this->gen_func_obj->format_number_data($es_data["u_posts"]).'&nbsp;|&nbsp;';
        
        /******** Post data string ****/
        if(isset($es_data["p_likes"]) && $es_data["p_likes"] > 0)
        {
            $post_data_string .= 'Likes: '.$this->gen_func_obj->format_number_data($es_data["p_likes"]).' | ';
        }
        
        if(isset($es_data["p_comments"]) && $es_data["p_comments"] > 0)
        {
            $post_data_string .= 'Comments: '.$this->gen_func_obj->format_number_data($es_data["p_comments"]).' | ';
        }
        
        if(isset($es_data["p_shares"]) && $es_data["p_shares"] > 0)
        {
            $post_data_string .= 'Shares: '.$this->gen_func_obj->format_number_data($es_data["p_shares"]).' | ';
        }
        
        /*********** source icon **********/
        if($es_data["source"] == 'Twitter')
            $source_icon = $images_path.'/logo/icons8-twitter.png';
        else if($es_data["source"] == 'Instagram')
            $source_icon = $images_path.'/logo/icons8-instagram.png';
        else if($es_data["source"] == 'Facebook')
            $source_icon = $images_path.'/logo/icons8-facebook.png';
        else if($es_data["source"] == 'Reddit')
            $source_icon = $images_path.'/logo/icons8-reddit.png';
        else if($es_data["source"] == 'Youtube')
            $source_icon = $images_path.'/logo/icons8-youtube.png';
        else if($es_data["source"] == 'FakeNews' || $es_data["source"] == 'News')
            $source_icon = $images_path.'/logo/icons8-news.png';
        else if($es_data["source"] == 'khaleej_times' || $es_data["source"] == 'Omanobserver' || $es_data["source"] == 'Time of oman' || $es_data["source"] == 'Blogs')
            $source_icon = $images_path.'/logo/icons8-blogs.png';
        else if($es_data["source"] == 'Tumblr')
            $source_icon = $images_path.'/logo/icons8-tumblr.png';
        else if ($es_data["source"] == 'Pinterest')
            $source_icon = $images_path.'/logo/icons8-pinterest.png';
        else
            $source_icon = $images_path.'/logo/icons8-question-mark-60.png';
        
        $html_str = '<div class="card">';
        
        $html_str .= '<div style="width: 100%; height: 150px; max-height: 150px; background:url(\''.$img_src.'\'); background-size: cover; background-position: center; background-repeat: no-repeat;"></div>';
        
        $html_str .= '<table width="100%">'
            . '<tr><td width="25%"><div style="width:80px; height: 80px;"><img src="'.$user_profile_pic.'" width="60" height="60"></div></td><td style="padding-left: 5px;"><div style=""><h5>'.$es_data["u_fullname"].'</h5></div><div style="font-size: 11px;">'.substr($user_data_string, 0, -13).'</div><div style="padding-top: 5px; color: #999;"><small>'.date("j M, Y h:i a", strtotime($es_data["p_created_time"])).'</small></div></td></tr>'
            . '</table>';
              
        if(isset($es_data["p_message_text"]))
            $ptext = $es_data["p_message_text"];
        else
            $ptext = '';
            
        if(!empty($post_data_string))
        {
            $html_str .= '<div class="row">';
            $html_str .= '<div style="font-size: 12px;">'.substr(strip_tags($ptext), 0, 60).'...</div>';
            $html_str .= '<div style="padding: 0px 0px 0px 0px; font-size: 11px;">'.substr($post_data_string, 0, -3).'</div>';
            $html_str .= '<div style=""></div>';
            $html_str .= '</div>';
        }
        else
        {
            $html_str .= '<div style="font-size: 12px;">'.substr(strip_tags($ptext), 0, 60).'...</div>';
            $html_str .= '<div style="padding: 0px 0px 0px 0px; font-size: 11px;">&nbsp;</div>';
        }
        
        $html_str .= '<div class="row" style="font-size: 11px;"><img src="'.$source_icon.'" width="25"> <a href="'.str_replace("https: // ", "https://", trim($es_data["p_url"])).'">View original post</a></div>';
        
        $html_str .= '</div>';
        
        return $html_str;
    }
}
?>

