<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmailListing;
use SendGrid;

class EmailListingController extends Controller
{
    public function sendingMail($receiver_email, $from_user_email, $mode, $invitation_code, $param1 = null, $param2 = null)
    {
        if($mode == 'invitation_signup')
        {
            $email = new \SendGrid\Mail\Mail();
            $email->setFrom('info@datalytics24.com', "Datalyticx");
            $email->setSubject("Signup to Datalyticx");
            $email->addTo($receiver_email, $receiver_email);
           
            $body = '<div style="width:500px;">
                        <div style="text-align:center;"><h2>DATALYTICS24</h2></div>
                        <div style="height:50px; background: #f0f0f0; text-align: center; font-size: 18px; line-height: 50px;"><b>Your perfect choice!</b></div>
                        <div style="text-align:center; margin-top:30px; font-size: 16px;">'.$from_user_email.' has sent you an invitation to join freely on Datalytics24 community.<br><br>Click on the link below to accept the invitation and follow the instructions on the page.</div>
                        <div style="margin-top:30px; text-align:center;"><a href="https://'.$_SERVER['HTTP_HOST'].'/ac-inv/'.$invitation_code.'" style="text-decoration: none; font-size:16px;"><b>Accept the invitation</b></a></div>
                        <div style="text-align:center; margin-top: 50px;">Weteringschans 165 C, 1017 XD Amsterdam, Netherlands<br>E: <a href="mailto:info@datalytics24.com">info@datalytics24.com</a></div>
                    </div>';
            
            $email->addContent("text/html", $body);

            $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
            
            try 
            {
                $response = $sendgrid->send($email);
                return 1;
            } 
            catch (Exception $e) 
            {
                return response()->json( 'Caught exception: '. $e->getMessage() ."\n");
            }
        }
        else if($mode == 'monthly_report')
        {
            $email = new \SendGrid\Mail\Mail();
            $email->setFrom('info@datalytics24.com', "Datalyticx");
            $email->setSubject($param1." - Last month dashboard report");
            $email->addTo($receiver_email, $from_user_email);
           
            $body = '<div style="width:500px;">
                        <div>Dear '.$from_user_email.',<br><br>Please find attached monthly dashboard report.<br><br>Thanks,<br>Datalyticx Team.</div>
                    </div>';
            
            $email->addContent("text/html", $body);
            
            $file_encoded = base64_encode(file_get_contents($invitation_code));
            
            $email->addAttachment($file_encoded, "application/pdf", $param2, "attachment");

            $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
            
            try 
            {
                $response = $sendgrid->send($email);
                return 1;
            } 
            catch (Exception $e) 
            {
                return response()->json( 'Caught exception: '. $e->getMessage() ."\n");
            }
        }
    }
}
