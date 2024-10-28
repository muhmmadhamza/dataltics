@extends('layouts.contentLayoutMaster')
{{-- page Title --}}
@section('title','Emails analysis')
{{-- vendor css --}}
@section('vendor-styles')

@endsection
@section('page-styles')
<link rel="stylesheet" type="text/css" href="{{asset('css/wizard.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/pickadate/pickadate.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/daterange/daterangepicker.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/app-file-manager.css')}}">
<style type="text/css">
  .link_button {
    -webkit-border-radius: 4px;
    -moz-border-radius: 4px;
    border-radius: 4px;
    border: solid 1px #20538D;
    text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.4);
    -webkit-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4), 0 1px 1px rgba(0, 0, 0, 0.2);
    -moz-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4), 0 1px 1px rgba(0, 0, 0, 0.2);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4), 0 1px 1px rgba(0, 0, 0, 0.2);
    background: #4479BA;
    color: #FFF;
    padding: 5px 12px;
    text-decoration: none;
}

.link_button:hover
{
  color: #ffffff !important;
}
</style>

@endsection
@section('content')
<section id="reports_settings">
    <div class="col-sm-12" style="padding:0px;">
      <div class="row" id="basic-table">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h4 class="card-title">Emails listing</h4>
            </div>
            @if(\Session::has('message'))
              <p class="alert {{ Session::get('alert-class', 'alert-info') }}">{{ \Session::get('message') }}</p>
            @endif
            <div class="card-body">
              <!-- Table with outer spacing -->
              <div class="table-responsive">
                <table class="table">
                  <thead>
                    <tr>
                      <th style="width: 5%;">#</th>
                      <!--<th>From</th>
                      <th>Subject</th>
                      <th>Date</th>-->
                      <th style="width: 80%;">Message</th>
                      <th style="width: 15%;">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $counter = 1;
                    if(count($emails_data) > 0)
                    {
                      for($i=0; $i<count($emails_data); $i++)
                      {
                        $text_str = '<b>Date:</b> '.date("Y/m/d", strtotime($emails_data[$i]->date)).'<br><b>From:</b> '.$emails_data[$i]->from.'<br><b>Subject:</b> '.$emails_data[$i]->subject.'<br>======================================<br><br>';
                    ?>
                      <tr>
                        <td><?php echo $counter; ?></td>
                        <!--<td><?php //echo $emails_data[$i]->from; ?></td>
                        <td><?php //echo $emails_data[$i]->subject; ?></td>
                        <td><?php //echo date("Y/m/d", strtotime($emails_data[$i]->date)); ?></td>-->
                        <td><div style="height: 180px; overflow-y: auto; overflow-x: hidden;"><?php echo $text_str . nl2br($emails_data[$i]->content); ?></div></td>
                        <td><a href="javascript:void(0);" class="link_button" onclick="javascript:load_email_analysis('{{ csrf_token() }}', '<?php echo encrypt($emails_data[$i]->id); ?>');">Load analysis</a></td>
                      </tr>
                    <?php
                        $counter = $counter + 1;
                      }
                    }
                    else
                    {
                      echo '<tr><td>No emails found.</td></tr>';
                    }
                    ?>
                    
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
</section>

@endsection
{{-- vendor scripts --}}
@section('vendor-scripts')
<script src="{{asset('vendors/js/extensions/jquery.steps.min.js')}}"></script>
<script src="{{asset('vendors/js/forms/validation/jquery.validate.min.js')}}"></script>

<script src="{{asset('vendors/js/pickers/pickadate/picker.js')}}"></script>
<script src="{{asset('vendors/js/pickers/pickadate/picker.date.js')}}"></script>
<script src="{{asset('vendors/js/pickers/pickadate/picker.time.js')}}"></script>
<script src="{{asset('vendors/js/pickers/pickadate/legacy.js')}}"></script>
<script src="{{asset('vendors/js/extensions/moment.min.js')}}"></script>
<script src="{{asset('vendors/js/pickers/daterange/daterangepicker.js')}}"></script>


@endsection

@section('page-scripts')
<script src="{{asset('js/scripts/custom.js')}}"></script>
<script src="{{asset('js/scripts/pages/app-file-manager.js')}}"></script>

@endsection
