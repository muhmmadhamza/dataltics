<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\GeneralFunctionsController;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EmailListingController;

class MyAccountController extends Controller
{
    public function load_myaccount_page(Request $request) 
    {
        $this->gen_func_obj = new GeneralFunctionsController();
        $this->cus_obj = new CustomerController();
        
        $pageConfigs = ['pageHeader' => true];
        
        if(!$this->gen_func_obj->validate_access())
        {
            \Session::flush(); //remove all sessions
            return redirect('/');
        }
        else
        {
            $loggedin_cus_id = \Session::get('_loggedin_customer_id');
            
            $noti_data = DB::table('customers')->select('customer_notification_freq')->WhereRaw('customer_id = ?', $loggedin_cus_id)->get();
            $notifcation_data = $noti_data->first()->customer_notification_freq;
            
            $customer_email = $this->cus_obj->get_customer_email($this->cus_obj->get_parent_account_id());
            
            $sub_account_email = $this->cus_obj->get_customer_email($loggedin_cus_id);
            
            //encrypt the session user email
            $encrypted_session_user_email = $this->gen_func_obj->encrypt($customer_email, $this->gen_func_obj->get_encryption_key());

            $invitation_used_data = DB::table('customer_invitations')->selectRAW('COUNT(*) as invitation_used')->WhereRaw('invitation_sent_by = ?', $encrypted_session_user_email)->get();
            $invitation_used = $invitation_used_data->first()->invitation_used;

            $invitation_list_data = DB::table('customer_invitations')->selectRAW('invitation_email,invitation_name,invitation_sent_date')->WhereRaw('invitation_sent_by = ?', "$encrypted_session_user_email")->OrderBy('invitation_sent_date','DESC')->get();
            $invitations_list = array();
            foreach ($invitation_list_data as $invite)
            {
                $decrypt_invite_email = $this->gen_func_obj->decrypt($invite->invitation_email, $this->gen_func_obj->get_encryption_key());

                $invite_registered_check = DB::table('customers')->select('customer_email')->WhereRaw('customer_email = ?', "$decrypt_invite_email" )->get();
                $invite_registered = $invite_registered_check->count();
                if($invite_registered > 0){
                    $invite_value = "Is also registered";
                }else{
                    $invite_value = "Not registered";
                }

                $invitations_list [] = array(
                  'invitation_email' => $decrypt_invite_email,
                  'invitation_name' => $this->gen_func_obj->decrypt($invite->invitation_name, $this->gen_func_obj->get_encryption_key()),
                  'invitation_sent_date' => date("F j, Y", strtotime($invite->invitation_sent_date)),
                  'invite_accept' =>$invite_value
                );
            }

            $invitation_allowed_data = DB::table('customers')->select('customer_allowed_invitations as invitation_allowed','customer_reg_scope')->WhereRaw('customer_email = ?', $sub_account_email)->get();
            $invitation_allowed = $this->gen_func_obj->decrypt($invitation_allowed_data->first()->invitation_allowed, $this->gen_func_obj->get_encryption_key());
            $customer_registered_scope = $invitation_allowed_data->first()->customer_reg_scope;

            return view('pages.my-account', ['notification_value' => $notifcation_data,'invitation_used' => $invitation_used ,'invitation_allowed' => $invitation_allowed,
        'invitations_list'=>$invitations_list, 'customer_registered_scope'=>$customer_registered_scope, 'pageConfigs'=>$pageConfigs]);
        }
    }
    
    public function update_account_password(Request $request) 
    {
        $flag = 0;
        if ($request['old_password'] == '' || $request['new_password'] == '') {
            //flag 3 will work for if we are not receivng anything from ajax
            $flag = 3;
        } else {
            $this->gen_func_obj = new GeneralFunctionsController();
            $loggedin_cus_id = \Session::get('_loggedin_customer_id');
            
            $cust_data = DB::table('customers')
                ->select('customer_pass')
                ->WhereRaw('customer_pass = ?', $this->gen_func_obj->encrypt($request["old_password"], $this->gen_func_obj->get_encryption_key()))
                ->WhereRaw('customer_id = ?', $loggedin_cus_id)
                ->get();
            $cust_data_count = $cust_data->count();
            if ($cust_data_count > 0) {
                $update_query = DB::table('customers')
                    ->whereRaw('customer_id = ? ', $loggedin_cus_id)
                    ->update(array('customer_pass' => $this->gen_func_obj->encrypt($request["new_password"], $this->gen_func_obj->get_encryption_key())));
                if ($update_query) {
                    $flag = 1;
                } else {
                    $flag = 0;
                }
            } else {
                $flag = 0;
            }
        }
        return $flag;
    }
    
    public function ajax_request_update_notification(Request $request)
    {
        $loggedin_cus_id = \Session::get('_loggedin_customer_id');
        $notification = 0;
        if($request['notify_freq'] == ''){
            //notification 3 will work for if we are not receivng anything from ajax
            $notification = 3;
        }else{
                $notify_value = $request["notify_freq"];
//            DB::enableQueryLog();
            $update_query = DB::table('customers')
                    ->whereRaw('customer_id = ? ', $loggedin_cus_id)
                    ->update(array('customer_notification_freq' => $notify_value));
//            dd(DB::getQueryLog());
                if($update_query){
                    $notification = 1;
                }else{
                    $notification = 0;
                }
        }
        return $notification;
    }
    
    public function ajax_request_send_invitation(Request $request)
    {
        $this->gen_func_obj = new GeneralFunctionsController();
        $this->cus_obj = new CustomerController();
        $this->mail_func_obj = new EmailListingController();
        
        $invitation = []; //initialize
        if($request['fullname'] == '' || $request['email'] == '' ){
            //invitation 1 will work for if we are not receivng anything from ajax
            $invitation = json_encode(['status'=>1,'response'=>'unable to process request']);
        }else{
            $invitation_data = DB::table('customers')->select('customer_id')->WhereRaw('customer_email = ?', $request["email"])->get();
            $invitation_data_count = $invitation_data->count();
            if($invitation_data_count > 0){       //check result count
                //throw system duplicate email error
                $invitation = json_encode(['status'=>2,'response'=>'A customer with provided email already exist in our system']);

            }else{
                //here we will check the the count in our customers_invitation table if our user already consumed the number of allowed invitations
                $encrypted_email_to_send_invite = $this->gen_func_obj->encrypt($request['email'], $this->gen_func_obj->get_encryption_key());
                
                $customer_email = $this->cus_obj->get_customer_email($this->cus_obj->get_parent_account_id());
                
                //encrypt the session user email
                $encrypted_session_user_email = $this->gen_func_obj->encrypt($customer_email, $this->gen_func_obj->get_encryption_key());

                $invitation_used_data = DB::table('customer_invitations')->selectRAW('COUNT(*) as invitation_used')->WhereRaw('invitation_sent_by = ?', $encrypted_session_user_email)->get();
                $invitation_used = $invitation_used_data->first()->invitation_used;

                //here we will get the total number of invitations allowed for user

                $invitation_allowed_data = DB::table('customers')->select('customer_allowed_invitations as invitation_allowed')->WhereRaw('customer_email = ?', $customer_email)->get();
                $invitation_allowed = $this->gen_func_obj->decrypt($invitation_allowed_data->first()->invitation_allowed, $this->gen_func_obj->get_encryption_key());

                //encrypt all data that will save in the database
                $invitation_code = sha1(time());
                $invitation_name = $this->gen_func_obj->encrypt($request["fullname"], $this->gen_func_obj->get_encryption_key());
                //inviation email already encrypted above we will use it
                $invitation_date_time = date('Y-m-d H:i:s');
                
                if($invitation_allowed > $invitation_used)
                {
                    //findout here if any invitation is send out already throw back with date
                    $invitation_already_sent = DB::table('customer_invitations')->select('invitation_sent_date')->WhereRaw('invitation_sent_by  = ?', $encrypted_session_user_email)->WhereRaw('invitation_email  = ?', $encrypted_email_to_send_invite)->get();
                    $invitation_already_sent_count = $invitation_already_sent->count();
                    if($invitation_already_sent_count > 0){
                        //throw back with the date of invitation already send
                        $invitation = json_encode(['status'=>3,'response'=>' Invitation already sent to this user on '.date("F j, Y", strtotime($invitation_already_sent->first()->invitation_sent_date)).'']);
                    } else{
                     $invitation_sent = DB::insert('INSERT INTO customer_invitations (invitation_name, invitation_email, invitation_sent_by, invitation_code, invitation_sent_date ) values (?, ?, ?, ?, ?)', [$invitation_name, $encrypted_email_to_send_invite,$encrypted_session_user_email,$invitation_code,$invitation_date_time]);
                        //need to count invite used again after send it
                        $invitation_used_data = DB::table('customer_invitations')->selectRAW('COUNT(*) as invitation_used')->WhereRaw('invitation_sent_by = ?', $encrypted_session_user_email)->get();
                        $invitation_used_after_insert = $invitation_used_data->first()->invitation_used;

                        //this is duplicate code use a gerneal function for it and return any array only
                        //for here use when return array use json_encode
                        $invitation_list_data = DB::table('customer_invitations')->selectRAW('invitation_email,invitation_name,invitation_sent_date')->WhereRaw('invitation_sent_by = ?', "$encrypted_session_user_email")->OrderBy('invitation_sent_date','DESC')->get();
                        $invitations_list = array();
                        foreach ($invitation_list_data as $invite){
                            $decrypt_invite_email = $this->gen_func_obj->decrypt($invite->invitation_email, $this->gen_func_obj->get_encryption_key());
                            $invite_registered_check = DB::table('customers')->select('customer_email')->WhereRaw('customer_email = ?', "$decrypt_invite_email" )->get();
                            $invite_registered = $invite_registered_check->count();
                            if($invite_registered > 0){
                                $invite_value = "Is also registered";
                            }else{
                                $invite_value = "Not registered";
                            }

                            $invitations_list [] = array(
                                'invitation_email' => $decrypt_invite_email,
                                'invitation_name' => $this->gen_func_obj->decrypt($invite->invitation_name, $this->gen_func_obj->get_encryption_key()),
                                'invitation_sent_date' => date("F j, Y", strtotime($invite->invitation_sent_date)),
                                'invite_accept' =>$invite_value
                            );
                        }

                        if($invitation_sent){
                            $uid = '';
                            $uemail = '';
                            
                            $uid = $this->cus_obj->get_parent_account_id();
                            $uemail = $this->cus_obj->get_customer_email($uid);
                            //send mail
                            //first param will be the email to send
                            //seocnd param will be the info email no-reply type
                            //mode of email
                            //invitation code | Change $request["email"] for hardcoded sending email
                            if($this->mail_func_obj->sendingMail($request["email"],$uemail,'invitation_signup',$invitation_code) == 1) //$request["email"]:afzaalgohar@gmail.com
                            {
                                //succesfully inserted
                                $invitation = json_encode(['status'=>4,'response'=>'Invitation has been sent successfully',
                                     'invite_used' => $invitation_used_after_insert, 'invitation_list'=> $invitations_list]);
                            }
                            else
                            {
                                $invitation = json_encode(['status'=>5,'response'=>'unable to process request']);
                            }
                     }else{
                         //unable to process request
                         $invitation = json_encode(['status'=>5,'response'=>'unable to process request']);
                     }
                 }

             }else{
                    //your limit has been completed for send email
                    $invitation = json_encode(['status'=>6,'response'=>'Your limit for sending more invitations is finished']);

                }
            }
         }
        return $invitation;
    }

    public function ajax_request_process_invitation(Request $request)
    {
        $this->gen_func_obj = new GeneralFunctionsController();
        //ic is the encrypted invitation_code
        //URL::to('/');


        if(isset($request->ic) &&  $request->ic != ''){
            $invitation_code_verify = DB::table('customer_invitations')->selectRAW('invitation_code,invitation_email')->WhereRaw('invitation_code = ?', "$request->ic")->WhereRaw('invitation_used = ?', "N")->get();
            $invitation_code_verified = $invitation_code_verify->count();

            if($invitation_code_verified > 0){
                $invitation_email = $this->gen_func_obj->decrypt($invitation_code_verify->first()->invitation_email, $this->gen_func_obj->get_encryption_key());
                $invite_registered_check = DB::table('customers')->select('customer_email')->WhereRaw('customer_email = ?', "$invitation_email" )->get();
                $invite_registered = $invite_registered_check->count();
                if($invite_registered > 0){
                    //redirect to base url
                  return  redirect()->to(env('SERVER_HTTP_PATH'));
                }else{
                  //send invitation code to balde
                    return view('pages.auto-register-user',['invitation_code'=> $request->ic]);
                }

            }else{
                echo 'Invalid page access or expired link used ..........';die();
            }
        }else{
            //redirect to home
          return  redirect()->to(env('SERVER_HTTP_PATH'));
        }


    }

    public function ajax_request_auto_register_user(Request $request)
    {
        $this->gen_func_obj = new GeneralFunctionsController();

        $auto_login = 0;
        if($request->invitation_code == '' || $request->customer_password == ''){
            $auto_login = 0;
        }else{
            //get data from customer invitations
            $invitation_customer_data = DB::table('customer_invitations')->selectRAW('invitation_sent_by,invitation_email,invitation_name')->WhereRaw('invitation_code = ?', "$request->invitation_code")->get();

            $invitation_customer = $invitation_customer_data->count();
            //decryption
            $decrypt_email = $this->gen_func_obj->decrypt($invitation_customer_data->first()->invitation_email, $this->gen_func_obj->get_encryption_key());
            $decrypt_name =  $this->gen_func_obj->decrypt($invitation_customer_data->first()->invitation_name, $this->gen_func_obj->get_encryption_key());
            $decrypt_sent_by = $this->gen_func_obj->decrypt($invitation_customer_data->first()->invitation_sent_by, $this->gen_func_obj->get_encryption_key());
            $encrypt_password = $this->gen_func_obj->encrypt($request->customer_password, $this->gen_func_obj->get_encryption_key());

            if($invitation_customer > 0){
                $invitation_user_register = DB::insert('INSERT INTO customers (customer_name, customer_email, customer_pass, customer_reg_time,customer_reg_scope,customer_notification_freq, customer_account_type, customer_account_parent) values (?, ?, ?, ?, ?, ?, ?, ?)', [$decrypt_name, $decrypt_email, $encrypt_password, NOW(), 'IS', '24h', 1, $decrypt_sent_by]);
                if($invitation_user_register){
                    $update_invitations = DB::table('customer_invitations')->whereRaw('invitation_code = ? ', "$request->invitation_code")->update(array('invitation_used' => 'Y'));
                    if($update_invitations){
                        $auto_login = 1;
                    }else{
                        $auto_login = 0;
                    }
                }else{
                    $auto_login = 0;
                }
            }
        }
        return $auto_login;
    }
    
    public function ajax_request_update_theme_settings(Request $request){
        $this->cus_obj = new CustomerController();
        $Options = 0;
        if($request['layoutOptions'] == ''){
            //notification 3 will work for if we are not receivng anything from ajax
            $Options = 3;
        }else{
            $layoutOptions = $request["layoutOptions"];
//            DB::enableQueryLog();
            $update_query = DB::table('customers')
                    ->whereRaw('customer_id = ? ', $this->cus_obj->get_parent_account_id())
                    ->update(array('customer_layout_settings' => "$layoutOptions"));
//            dd(DB::getQueryLog());
                if($update_query){
                    $Options = 1;
                }else{
                    $Options = 0;
                }
        }
        return $Options;
    }
}
