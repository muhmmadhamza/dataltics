<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\GeneralFunctionsController;
use App\Http\Controllers\CustomerController;
use Crypt;

class SourceHandleController extends Controller
{
    public function __construct()
    {
        $this->gen_func_obj = new GeneralFunctionsController();
        $this->cus_obj = new CustomerController();
    }

    public function source_handles_page(Request $request)
    {
        //check for user login
        if(!$this->gen_func_obj->validate_access())
        {
            return redirect('/');
        }
        
        $loggedin_user_id = $this->cus_obj->get_parent_account_id();
                

        //return view('pages.topic-settings', compact('topics_data'));
        return view('pages.source-handles');
    }
    
    
}
?>

