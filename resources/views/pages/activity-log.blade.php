@extends('layouts.contentLayoutMaster')
{{-- page Title --}}
@section('title','Activity log')
{{-- vendor css --}}
@section('vendor-styles')

@endsection
@section('page-styles')
<link rel="stylesheet" type="text/css" href="{{asset('css/dashboard-ecommerce.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/dashboard-analytics.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/widgets.min.css')}}">
<link rel="stylesheet" href="{{asset('vendors/css/tables/datatable/dataTables.bootstrap4.min.css')}}">
<link rel="stylesheet" href="{{asset('vendors/css/tables/datatable/responsive.bootstrap4.min.css')}}">
<link rel="stylesheet" href="{{asset('vendors/css/tables/datatable/buttons.bootstrap4.min.css')}}">


@endsection
@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <p>Below in list you can view the activity of your and invited sub accounts.</p>
                <div class="table-responsive">
                    <table class="table zero-configuration" id="activity_log_data_table">
                        <thead>
                            <tr>
                                <th>&nbsp;</th>
                                <th>Log detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $num = 1;
                            for($i=0; $i<count($activity_log); $i++)
                            {
                                $detail_str = ''; $st_name = array(); $tp_name = array();
                                
                                if(isset($activity_log[$i]->al_tid) && !is_null($activity_log[$i]->al_tid))
                                {
                                    $detail_str .= ' --- <b>'.$topic_obj->get_topic_name($activity_log[$i]->al_tid).'</b>';
                                }
                                else if(isset($activity_log[$i]->al_stid) && !is_null($activity_log[$i]->al_stid))
                                {
                                    $st_name = $stopic_obj->get_subtopic_data($activity_log[$i]->al_stid);
                                    
                                    if(count($st_name) > 0)
                                        $detail_str .= ' --- <b>'.$st_name[0]->exp_name.'</b>';
                                }
                                else if(isset($activity_log[$i]->al_tpid) && !is_null($activity_log[$i]->al_tpid))
                                {
                                    $tp_name = $tp_obj->get_touchpoint_data($activity_log[$i]->al_tpid);
                                    
                                    if(count($tp_name) > 0)
                                        $detail_str .= ' --- <b>'.$tp_name[0]->tp_name.'</b>';
                                }
                                else if(isset($activity_log[$i]->al_ca_id) && !is_null($activity_log[$i]->al_ca_id))
                                {
                                    $ca_name = $ca_obj->get_ca_name($activity_log[$i]->al_ca_id);
                                    
                                    if($ca_name)
                                        $detail_str .= ' --- <b>'.$ca_name.'</b>';
                                }
                            ?>
                            <tr>
                                <td style="width:10%; font-weight: bold;"><?php echo $num; ?>.</td>
                                <td style="width:90%;"><?php echo date("Y-m-d h:i:s", strtotime($activity_log[$i]->al_time)).' => <span style="color:blue;">'.$cus_obj->get_customer_name($activity_log[$i]->al_cid).'</span> --- '.$activity_log[$i]->al_message.$detail_str; ?></td>
                            </tr>
                            <?php
                                $num = $num+1;
                            }
                            ?>
                        </tbody>
                        <!--<tfoot>
                            <tr>
                                <th>Name</th>
                                <th>Position</th>
                                <th>Office</th>
                                <th>Age</th>
                                <th>Start date</th>
                                <th>Salary</th>
                            </tr>
                        </tfoot>-->
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
{{-- vendor scripts --}}
@section('vendor-scripts')
 
@endsection
  
@section('page-scripts')
<script src="{{asset('vendors/js/tables/datatable/jquery.dataTables.min.js')}}"></script>
<script src="{{asset('vendors/js/tables/datatable/dataTables.bootstrap4.min.js')}}"></script>
<script src="{{asset('vendors/js/tables/datatable/dataTables.buttons.min.js')}}"></script>
<script src="{{asset('vendors/js/tables/datatable/buttons.html5.min.js')}}"></script>
<script src="{{asset('vendors/js/tables/datatable/buttons.print.min.js')}}"></script>
<script src="{{asset('vendors/js/tables/datatable/buttons.bootstrap4.min.js')}}"></script>
<script src="{{asset('vendors/js/tables/datatable/pdfmake.min.js')}}"></script>
<script src="{{asset('vendors/js/tables/datatable/vfs_fonts.js')}}"></script>
<script src="{{asset('js/scripts/custom.js')}}"></script>
<script>
$("#activity_log_data_table").DataTable();
</script>
@endsection
  