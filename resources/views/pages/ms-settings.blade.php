@php
function formatSummary($text) {
    // Split the text into lines
    $lines = explode("\n", trim($text));
    $formatted = '';

    if (preg_match('/^\d+\./', trim($lines[0]))) {
        // Numbered list
        $formatted .= '<ol>';
        foreach ($lines as $line) {
            // Remove existing numbers
            $cleanedLine = preg_replace('/^\d+\.\s*/', '', trim($line));
            $formatted .= '<li>' . e($cleanedLine) . '</li>';
        }
        $formatted .= '</ol>';
    } elseif (preg_match('/^\*\s*/', trim($lines[0]))) {
        // Bullet points
        $formatted .= '<ul>';
        foreach ($lines as $line) {
            // Remove existing asterisks
            $cleanedLine = preg_replace('/^\*\s*/', '', trim($line));
            $formatted .= '<li>' . e($cleanedLine) . '</li>';
        }
        $formatted .= '</ul>';
    } else {
        // Plain text
        $formatted .= '<p>' . nl2br(e($text)) . '</p>';
    }

    // Debugging output
    error_log("Formatted summary: $formatted");

    return $formatted;
}
@endphp

	@extends('layouts.contentLayoutMaster') {{-- page Title --}} @section('title','Topic Dasbhoard') {{-- vendor css --}} @section('vendor-styles')
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/charts/apexcharts.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/swiper.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/dragula.min.css')}}">
@endsection @section('page-styles')
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/pickadate/pickadate.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/daterange/daterangepicker.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/forms/select/select2.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/dashboard-ecommerce.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/dashboard-analytics.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/widgets.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/jquery.rateyo.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/swiper.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/swiper.css')}}">
<!--<link rel="stylesheet" type="text/css" href="{{asset('css/plugins/extensions/ext-component-ratings.css')}}">-->
<link rel="stylesheet" type="text/css" href="{{asset('css/drag-and-drop.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/daterange/daterangepicker.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/leaflet/leaflet.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/leaflet/leaflet-gesture-handling.css')}}">

{{--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">--}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

@endsection

@section('content')
<!-- Dashboard Ecommerce Starts -->
<section>
    
    <!-- Add topic button -->
     <div class="row">
        <div class="col-12 mb-2 -mt-4">
                    <h3>Mentions Summary</h3>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row">
     <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
    <form action="/ms-settings" method="post">
    @csrf
    <fieldset class="form-group">
		<select class="form-control" id="basicSelect" name="topic_id">
                <option disabled value='' selected >Select Topic..</option>
@foreach($topics_data as $topic)  
                  <option value="{{$topic->topic_id}}">{{$topic->topic_title}}</option>
                  @endforeach
                </select>
	      </fieldset>
<button type="submit" class="btn mr-1 btn-light btn-sm"  name="fetch-summary">Get Summary</button>
	      </form>

       </div>
</div>
</div>
       @if(!empty($topics_summary))

         @foreach($topics_summary as $summary)
              <div class="col-md-12">
		<div class="card">
<div class="card-header bg-light" style="margin-bottom: 15px;"><!--background: #8ac4d5; -->
		    <h4 class="greeting-text" style="color:#ffffff;">{{$summary->topic_title}} Topic Summary</h4>
</div>
		    <div class="card-body">
                        <p class="card-text">{!!$summary->topic_summary !=null? formatSummary($summary->topic_summary) :'No Summary Found'!!}</p>
                    </div>
                </div>
            </div>
            @if(!empty($summary->topic_summary_twitter))
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div style="display:flex;align-items: center;gap:10px">
                        <h4 class="card-title">Topic Summary Twitter</h4>
                        <div class="avatar m-0 mb-1" style="background-color: #c3c3c370 !important">
                                <div class="avatar-content">
                                    <i class="fa-brands fa-x-twitter" style="color: #000000 !important"></i>
                                </div>
                            </div>
                            </div> 
                        <p class="card-text"> {!! formatSummary($summary->topic_summary_twitter) !!}</p>
                    </div>
                </div>
            </div>
            @endif
            @if(!empty($summary->topic_summary_fb))
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div style="display:flex;align-items: center;gap:10px">
                        <h4 class="card-title">Topic Summary Facebook</h4>
                        <div class="avatar m-0 mb-1" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bxl-facebook-square text-primary" style="color: #3B5998 !important"></i>
                            </div>
                        </div>
                            </div> 
                        <p class="card-text"> {!! formatSummary($summary->topic_summary_fb) !!}</p>
                    </div>
                </div>
            </div>
            @endif
            @if(!empty($summary->topic_summary_insta))
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div style="display:flex;align-items: center;gap:10px">
                        <h4 class="card-title">Topic Summary Instagram</h4>
                        <div class="avatar m-0 mb-1" style="background-color: #c3c3c370 !important">
                            <div class="avatar-content">
                                <i class="bx bxl-instagram-alt text-success" style="color: #E4405F !important"></i>
                            </div>
                        </div>
                        </div>
                        <p class="card-text"> {!! formatSummary($summary->topic_summary_insta) !!}</p>
                    </div>
                </div>
            </div>
            @endif
        @endforeach
       @endif

        
        
    </div>
  </section>
  <!-- END: Topics list -->
  
  </section>
  <!-- Sortable lists section end -->
  <!-- Dashboard Ecommerce ends -->
  @endsection
  {{-- vendor scripts --}}
  @section('vendor-scripts')
  
  
  
  @endsection
  
  @section('page-scripts')
  <!--<script src="{{asset('js/scripts/pages/dashboard-ecommerce.js')}}"></script>
  <script src="{{asset('js/scripts/pages/dashboard-analytics.js')}}"></script>
  <script src="{{asset('js/scripts/cards/widgets.js')}}"></script>-->
  <script src="{{asset('js/scripts/custom.js')}}"></script>
  <script src="{{asset('js/scripts/ca-script.js')}}"></script>
  <script src="{{asset('js/scripts/tagsinput.js')}}"></script>
  <script src="{{asset('js/scripts/popover/popover.js')}}"></script>
  <script src="{{asset('vendors/js/forms/select/select2.full.min.js')}}"></script>
  <script src="{{asset('js/scripts/forms/select/form-select2.js')}}"></script>
  
  @endsection
