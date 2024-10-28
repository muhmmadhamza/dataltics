<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Elasticsearch\ClientBuilder;
use App\Http\Controllers\GeneralFunctionsController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SubTopicController;
use App\Http\Controllers\TouchpointController;
use Crypt;

class ResourceHandleController extends Controller
{
    public function __construct()
    {
        $this->gen_func_obj = new GeneralFunctionsController();
        $this->cus_obj = new CustomerController();
    }

    public function resource_handles_page(Request $request)
    {
        //check for user login
        if(!$this->gen_func_obj->validate_access())
        {
            return redirect('/');
        }
        
        $loggedin_user_id = $this->cus_obj->get_parent_account_id();
                

        //return view('pages.topic-settings', compact('topics_data'));
        return view('pages.resource-handles');
    }
    
    
}
?>

