@extends('layouts.contentLayoutMaster')
{{-- page Title --}}
@section('title','Dashboard E-commerce')
{{-- vendor css --}}
@section('vendor-styles')
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/charts/apexcharts.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/swiper.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/dragula.min.css')}}">
@endsection
@section('page-styles')
<link rel="stylesheet" type="text/css" href="{{asset('css/dashboard-ecommerce.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/dashboard-analytics.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/widgets.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/drag-and-drop.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/daterange/daterangepicker.css')}}">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.4/jspdf.min.js"></script>

@endsection
@section('content')
<!-- Dashboard Ecommerce Starts -->
<section class="list-group" id="card-drag-area">
  <div class="row">
    <!-- Order Activity Chart Starts -->
    <div class="col-12">
      <div class="card widget-order-activity">
        <div class="card-header d-md-flex justify-content-between align-items-center">
          <h4 class="card-title">Mentions</h4>
          <div class="heading-elements mt-md-0 mt-50 d-flex align-items-center">
            {{-- <fieldset class="d-inline-block form-group position-relative has-icon-left mb-0 mr-1">
            <button class="btn btn-primary" id="spinner" style="display: none" type="button" disabled="">
              <span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>
              Loading...
            </button>
            </fieldset> --}}
            <fieldset class="d-inline-block form-group position-relative has-icon-left mb-0 mr-1">
              <select class="custom-select" id="mentionselect">
                <option selected="default" value="default">Select Filter</option>
                <option value="mentions_today">Today</option>
                <option value="mentions_this_week">This Week</option>
                <option value="mentions_this_month">This Month</option>
              </select>
            </fieldset>
            <fieldset class="d-inline-block form-group position-relative has-icon-left mb-0 mr-1">
              OR
            </fieldset>

            <fieldset class="d-inline-block form-group position-relative has-icon-left mb-0 mr-1">
              <input type="text" class="form-control daterange">
              <div class="form-control-position">
                <i class='bx bx-calendar'></i>
              </div>
            </fieldset>
            {{-- <div class="btn-group" role="group" aria-label="Basic example">
              <button type="button" class="btn btn-primary">Monthly</button>
              <button type="button" class="btn btn-outline-primary">Annually</button>
            </div> --}}
          </div>
        </div>
        <div class="card-body">
          <div id="mention-activity-line-chart"></div>
        </div>
      </div>
    </div>
    <!-- Order Activity Chart Ends -->
  </div>
  <div class="row">
    <!-- Followers Danger Line Chart Starts -->
    <div class="col-xl-4 col-md-6 draggable">
      <div class="card widget-followers">
        <div class="card-header d-flex align-items-center justify-content-between">
          <div>
            <h4 class="card-title">Engagements</h4>
            {{-- <a class="nav-link nav-link-expand"><i class="ficon bx bx-fullscreen"></i></a> --}}
            {{-- <small class="text-muted">Spending on a day</small> --}}
          </div>
          <div class="d-flex align-items-center widget-followers-heading-right">
            <h6 class="mr-2 font-weight-normal mb-0" id="eng_count"></h6>
            <div class="d-flex flex-column align-items-center">
              <i class='bx bx-caret-down text-danger font-medium-1' id="engstatus"></i>
              <small class="text-muted" id="eng_per"></small>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div id="engagement_chart"></div>
        </div>
      </div>
    </div>
    <!-- Followers Danger Line Chart Ends -->
    <!-- Followers Primary Line Chart Starts -->
    <div class="col-xl-4 col-md-6 draggable">
      <div class="card widget-followers">
        <div class="card-header d-flex align-items-center justify-content-between">
          <div>
            <h4 class="card-title">Shares</h4>
            {{-- <small class="text-muted">HTML users in a day</small> --}}
          </div>
          <div class="d-flex align-items-center widget-followers-heading-right">
            <h6 class="mr-2 font-weight-normal mb-0" id="share_count"></h6>
            <div class="d-flex flex-column align-items-center">
              <i class='bx bx-caret-down text-success font-medium-1' id="shrstatus"></i>
              <small class="text-muted" id="share_per"></small>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div id="share_chart"></div>
        </div>
      </div>
    </div>
    <!-- Followers Primary Line Chart Ends -->
    <!-- Followers Success Line Chart Starts -->
    <div class="col-xl-4 col-md-6 draggable">
      <div class="card widget-followers">
        <div class="card-header d-flex align-items-center justify-content-between">
          <div>
            <h4 class="card-title">Likes</h4>
            {{-- <small class="text-muted">New entry in a day</small> --}}
          </div>
          <div class="d-flex align-items-center widget-followers-heading-right">
            <h6 class="mr-2 font-weight-normal mb-0" id="like_count"></h6>
            <div class="d-flex flex-column align-items-center">
              <i class='bx bx-caret-down text-success font-medium-1' id="likestatus"></i>
              <small class="text-muted" id="like_per"></small>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div id="likes_chart"></div>
        </div>
      </div>
    </div>
    <!-- Followers Success Line Chart Ends -->
  </div>
  <div class="row">
    <!-- Activity Card Starts-->
    <div class="col-xl-4 col-md-4 col-12 activity-card">
      <div class="card">
        <div class="card-header">
          <h4 class="card-title">Main Social Channel</h4>
        </div>
        <div class="card-body pt-1" id="main_social_channel">
          <div class="d-flex activity-content" id="facebook_data" style="display: none">
            <div class="avatar m-0 mr-75" style="background-color:#c3c3c370 !important">
              <div class="avatar-content">
                <i class="bx bxl-facebook-square text-primary" style="color:#3B5998 !important"></i>
              </div>
            </div>
            <div class="activity-progress flex-grow-1">
              <small class="text-muted d-inline-block mb-50" id="facebook"></small>
              <small class="float-right"></small>
              <div class="progress progress-bar-primary progress-sm">
                <div class="progress-bar" role="progressbar" aria-valuenow="5" style="width:5%;background: #3B5998;box-shadow: none;"></div>
              </div>
            </div>
          </div>
          <div class="d-flex activity-content" id="insta_data" style="display: none">
            <div class="avatar m-0 mr-75" style="background-color:#c3c3c370 !important">
              <div class="avatar-content">
                <i class="bx bxl-instagram-alt text-success" style="color : #E4405F !important"></i>
              </div>
            </div>
            <div class="activity-progress flex-grow-1">
              <small class="text-muted d-inline-block mb-50" id="insta"></small>
              <small class="float-right"></small>
              <div class="progress progress-bar-success progress-sm">
                <div class="progress-bar" role="progressbar" aria-valuenow="15" style="width:15%;background:#E4405F !important;box-shadow: none;"></div>
              </div>
            </div>
          </div>
          <div class="d-flex activity-content">
            <div class="avatar m-0 mr-75" style="background-color:#c3c3c370 !important">
              <div class="avatar-content">
                <i class="bx bxl-twitter text-warning" style="color: #00ABEA !important"></i>
              </div>
            </div>
            <div class="activity-progress flex-grow-1">
              <small class="text-muted d-inline-block mb-50" id="twitter"></small>
              <small class="float-right" ></small>
              <div class="progress progress-bar-warning progress-sm">
                <div class="progress-bar" role="progressbar" aria-valuenow="80" style="width:80%;background:#00ABEA !important;box-shadow: none;"></div>
              </div>
            </div>
          </div>
          <div class="d-flex activity-content">
            <div class="avatar m-0 mr-75" style="background-color:#c3c3c370 !important">
              <div class="avatar-content">
                <i class="bx bxl-youtube text-danger"></i>
              </div>
            </div>
            <div class="activity-progress flex-grow-1">
              <small class="text-muted d-inline-block mb-50" id="video"></small>
              <small class="float-right" ></small>
              <div class="progress progress-bar-danger progress-sm">
                <div class="progress-bar" role="progressbar" aria-valuenow="40" style="width:40%;box-shadow: none"></div>
              </div>
            </div>
          </div>
          <div class="d-flex activity-content">
            <div class="avatar m-0 mr-75" style="background-color:#c3c3c370 !important">
              <div class="avatar-content">
                <i class="bx bxl-reddit text-warning" style="color: #FF4301 !important"></i>
              </div>
            </div>
            <div class="activity-progress flex-grow-1">
              <small class="text-muted d-inline-block mb-50" id="reddit"></small>
              <small class="float-right" ></small>
              <div class="progress progress-bar-warning progress-sm">
                <div class="progress-bar" role="progressbar" aria-valuenow="15" style="width:15%;background: #FF4301 !important;box-shadow: none"></div>
              </div>
            </div>
          </div>
          <div class="d-flex activity-content">
            <div class="avatar m-0 mr-75" style="background-color:#c3c3c370 !important">
              <div class="avatar-content">
                <i class="bx bxl-blogger text-success" style="color: #F57D00 !important"></i>
              </div>
            </div>
            <div class="activity-progress flex-grow-1">
              <small class="text-muted d-inline-block mb-50" id="blog"></small>
              <small class="float-right"></small>
              <div class="progress progress-bar-success progress-sm">
                <div class="progress-bar" role="progressbar" aria-valuenow="15" style="width:15%;background: #F57D00 !important;box-shadow: none"></div>
              </div>
            </div>
          </div>
          <div class="d-flex activity-content">
            <div class="avatar m-0 mr-75" style="background-color:#c3c3c370 !important">
              <div class="avatar-content">
                <i class="bx bxl-tumblr text-danger" style="color:#34526F !important"></i>
              </div>
            </div>
            <div class="activity-progress flex-grow-1">
              <small class="text-muted d-inline-block mb-50" id="tumblr"></small>
              <small class="float-right" ></small>
              <div class="progress progress-bar-danger progress-sm">
                <div class="progress-bar" role="progressbar" aria-valuenow="5" style="width:5%;background: #34526F !important;box-shadow: none"></div>
              </div>
            </div>
          </div>
          <div class="d-flex activity-content">
            <div class="avatar m-0 mr-75" style="background-color:#c3c3c370 !important">
              <div class="avatar-content">
                <i class="bx bx-news text-primary" style="color: #77BD9D !important"></i>
              </div>
            </div>
            <div class="activity-progress flex-grow-1">
              <small class="text-muted d-inline-block mb-50" id="news"></small>
              <small class="float-right"></small>
              <div class="progress progress-bar-primary progress-sm">
                <div class="progress-bar" role="progressbar" aria-valuenow="30" style="width:30%;background: #77BD9D !important;box-shadow: none"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Profit Report Card Starts-->
    <div class="col-xl-8 col-md-8 col-12 profit-report-card">
      <div class="row">
        {{-- <div class="col-md-12 col-sm-6">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center pb-0">
              <h4 class="card-title">Revenue Growth</h4>
              <div class="d-flex align-items-end justify-content-end">
                <span class="mr-25">$25,980</span>
                <i class="bx bx-dots-vertical-rounded font-medium-3 cursor-pointer"></i>
              </div>
            </div>
            <div class="card-body pb-0">
              <div id="revenue-growth-chart"></div>
            </div>
          </div>
        </div> --}}
        <div class="col-md-12 col-sm-6">
          <div class="card">
            <div class="card-header">
              <h4 class="card-title">Sentiment Analysis</h4>
            </div>
            <div class="card-body">
              <div id="donut-chart" class="d-flex justify-content-center"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-xl-3 col-md-6 col-12 growth-card" style="display: none !important">
      <div class="card text-center mb-3">
        <div class="card-body">
          <ul class="nav nav-tabs" id="nav-tabs" role="tablist">
            <li class="nav-item">
              <a class="nav-link active" id="home-tab" data-toggle="tab" href="#home" role="tab" aria-controls="home"
              aria-selected="true">Most Recent</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" id="profile-tab" data-toggle="tab" href="#profile" role="tab" aria-controls="profile"
              aria-selected="false">Most Popular</a>
            </li>
          </ul>
          <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
              <div class="card">
                <img src="{{asset('images/slider/10.png')}}" class="card-img-top img-fluid" alt="singleminded">
                <div class="card-header">
                  <h4 class="card-title">Be Single Minded</h4>
                </div>
                <div class="card-body">
                  <p class="card-text">
                    Chocolate sesame snaps apple pie danish cupcake sweet roll 
                  </div>
                  
                </div>
              </div>
              <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                <div class="card">
                  <img src="{{asset('images/slider/10.png')}}" class="card-img-top img-fluid" alt="singleminded">
                  <div class="card-header">
                    <h4 class="card-title">Be Single Minded</h4>
                  </div>
                  <div class="card-body">
                    <p class="card-text">
                      Chocolate sesame snaps apple pie danish cupcake sweet roll 
                    </p>
                  </div>
                  
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-xl-12 col-12 dashboard-marketing-campaign">
        <div class="card marketing-campaigns">
          <div class="card-header d-flex justify-content-between align-items-center pb-1">
            <h4 class="card-title">Influencers</h4>
            <i class="bx bx-dots-vertical-rounded font-medium-3 cursor-pointer"></i>
          </div>
          {{-- <div class="card-body pb-0">
            <div class="row mb-1"> --}}
              {{-- <div class="col-md-9 col-12">
                <div class="d-inline-block"> --}}
                  <!-- chart-1   -->
                  {{-- <div class="d-flex market-statistics-1"> --}}
                    <!-- chart-statistics-1 -->
                    {{-- <div id="donut-success-chart" class="mx-1"></div> --}}
                    <!-- data -->
                    {{-- <div class="statistics-data my-auto">
                      <div class="statistics">
                        <span class="font-medium-2 mr-50 text-bold-600">25,756</span><span
                        class="text-success">(+16.2%)</span>
                      </div>
                      <div class="statistics-date">
                        <i class="bx bx-radio-circle font-small-1 text-success mr-25"></i>
                        <small class="text-muted">May 12, 2020</small>
                      </div>
                    </div> --}}
                  {{-- </div>
                </div>
                <div class="d-inline-block"> --}}
                  <!-- chart-2 -->
                  {{-- <div class="d-flex mb-75 market-statistics-2"> --}}
                    <!-- chart statistics-2 -->
                    {{-- <div id="donut-danger-chart" class="mx-1"></div> --}}
                    <!-- data-2 -->
                    {{-- <div class="statistics-data my-auto">
                      <div class="statistics">
                        <span class="font-medium-2 mr-50 text-bold-600">5,352</span><span
                        class="text-danger">(-4.9%)</span>
                      </div>
                      <div class="statistics-date">
                        <i class="bx bx-radio-circle font-small-1 text-success mr-25"></i>
                        <small class="text-muted">Jul 26, 2020</small>
                      </div>
                    </div> --}}
                  {{-- </div> --}}
                {{-- </div>
              </div> --}}
              {{-- <div class="col-md-3 col-12 text-md-right">
                <button class="btn btn-sm btn-primary glow mt-md-2 mb-1">View Report</button>
              </div> --}}
            {{-- </div>
          </div> --}}
           <!-- Nav tabs -->
           <ul class="nav nav-tabs nav-fill card-body pb-0" id="myTab" role="tablist">
            <li class="nav-item">
              <a class="nav-link active" id="home-tab-fill" data-toggle="tab" href="#home-fill" role="tab"
                aria-controls="home-fill" aria-selected="true">
                Nano
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" id="profile-tab-fill" data-toggle="tab" href="#profile-fill" role="tab"
                aria-controls="profile-fill" aria-selected="false">
                Micro
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" id="messages-tab-fill" data-toggle="tab" href="#messages-fill" role="tab"
                aria-controls="messages-fill" aria-selected="false">
                Midtier
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" id="settings-tab-fill" data-toggle="tab" href="#settings-fill" role="tab"
                aria-controls="settings-fill" aria-selected="false">
                Macro
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" id="mega-tab-fill" data-toggle="tab" href="#mega-fill" role="tab"
                aria-controls="mega-fill" aria-selected="false">
                Mega
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" id="cele-tab-fill" data-toggle="tab" href="#cele-fill" role="tab"
                aria-controls="cele-fill" aria-selected="false">
                Celebrity
              </a>
            </li>
          </ul>

          <!-- Tab panes -->
          <div class="tab-content pt-1">
            <div class="tab-pane active" id="home-fill" role="tabpanel" aria-labelledby="home-tab-fill">
              <div class="table-responsive">
                <!-- table start -->
                <table id="table-marketing-campaigns" class="table table-borderless table-marketing-campaigns mb-0">
                  <thead>
                    <tr>
                      <th style="padding-left: 80px">Name</th>
                      <th class="centeraligh">Country</th>
                      <th class="centeraligh">Followers</th>
                      <th class="centeraligh">Posts</th>
                    </tr>
                  </thead>
                  <tbody id="nano">
                    
                  </tbody>
                </table>
                <!-- table ends -->
              </div>
            </div>
            <style>
              .centeraligh {
                text-align: center;
              }
            </style>
            <div class="tab-pane" id="profile-fill" role="tabpanel" aria-labelledby="profile-tab-fill">
              <div class="table-responsive">
                <!-- table start -->
                <table id="table-marketing-campaigns" class="table table-borderless table-marketing-campaigns mb-0">
                  <thead>
                    <tr>
                      <th style="padding-left: 80px">Name</th>
                      <th class="centeraligh">Country</th>
                      <th class="centeraligh">Followers</th>
                      <th class="centeraligh">Posts</th>
                    </tr>
                  </thead>
                  <tbody id="micro">
                    
                  </tbody>
                </table>
                <!-- table ends -->
              </div>
            </div>
            <div class="tab-pane" id="messages-fill" role="tabpanel" aria-labelledby="messages-tab-fill">
              <div class="table-responsive">
                <!-- table start -->
                <table id="table-marketing-campaigns" class="table table-borderless table-marketing-campaigns mb-0">
                  <thead>
                    <tr>
                      <th style="padding-left: 80px">Name</th>
                      <th class="centeraligh">Country</th>
                      <th class="centeraligh">Followers</th>
                      <th class="centeraligh">Posts</th>
                    </tr>
                  </thead>
                  <tbody id="midtier">
                    
                  </tbody>
                </table>
                <!-- table ends -->
              </div>
            </div>
            <div class="tab-pane" id="settings-fill" role="tabpanel" aria-labelledby="settings-tab-fill">
              <div class="table-responsive">
                <!-- table start -->
                <table id="table-marketing-campaigns" class="table table-borderless table-marketing-campaigns mb-0">
                  <thead>
                    <tr>
                      <th style="padding-left: 80px">Name</th>
                      <th class="centeraligh">Country</th>
                      <th class="centeraligh">Followers</th>
                      <th class="centeraligh">Posts</th>
                    </tr>
                  </thead>
                  <tbody id="macro">
                    
                  </tbody>
                </table>
                <!-- table ends -->
              </div>
            </div>
            <div class="tab-pane" id="mega-fill" role="tabpanel" aria-labelledby="mega-tab-fill">
              <div class="table-responsive">
                <!-- table start -->
                <table id="table-marketing-campaigns" class="table table-borderless table-marketing-campaigns mb-0">
                  <thead>
                    <tr>
                      <th style="padding-left: 80px">Name</th>
                      <th class="centeraligh">Country</th>
                      <th class="centeraligh">Followers</th>
                      <th class="centeraligh">Posts</th>
                    </tr>
                  </thead>
                  <tbody id="mega">
                 
                  </tbody>
                </table>
                <!-- table ends -->
              </div>
            </div>
            <div class="tab-pane" id="cele-fill" role="tabpanel" aria-labelledby="cele-tab-fill">
              <div class="table-responsive">
                <!-- table start -->
                <table id="table-marketing-campaigns" class="table table-borderless table-marketing-campaigns mb-0">
                  <thead>
                    <tr>
                      <th style="padding-left: 80px">Name</th>
                      <th class="centeraligh">Country</th>
                      <th class="centeraligh">Followers</th>
                      <th class="centeraligh">Posts</th>
                    </tr>
                  </thead>
                  <tbody id="celebrity">
                    
                  </tbody>
                </table>
                <!-- table ends -->
              </div>
            </div>
          </div>
          
        </div>
      </div>
    </div>
    <div class="row" >
      <div class="col-lg-12">
        <div class="card">
          <div class="card-header">
            <h4 class="card-title">
              SubTopic Mentions Heat Map
            </h4>
            <div class="heading-elements">
              <i class="bx bx-dots-vertical-rounded font-medium-3 align-middle cursor-pointer"></i>
            </div>
          </div>
          <div class="card-body">
            <div id="subtopic_heatmap"></div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- Sortable lists section end -->
  <!-- Dashboard Ecommerce ends -->
  @endsection
  {{-- vendor scripts --}}
  @section('vendor-scripts')
  <script src="{{asset('vendors/js/charts/apexcharts.js')}}"></script>
  {{-- <script src="{{asset('vendors/js/extensions/swiper.min.js')}}"></script> --}}
  <script src="{{asset('vendors/js/extensions/moment.min.js')}}"></script>
  <script src="{{asset('vendors/js/pickers/daterange/daterangepicker.js')}}"></script>
  <script src="{{asset('vendors/js/extensions/swiper.min.js')}}"></script>
  {{-- <script src="{{asset('vendors/js/charts/apexcharts.min.js')}}"></script> --}}
  <script src="{{asset('vendors/js/extensions/dragula.min.js')}}"></script>
  @endsection
  
  @section('page-scripts')
  <script src="{{asset('js/scripts/pages/dashboard-ecommerce.js')}}"></script>
  <script src="{{asset('js/scripts/pages/dashboard-analytics.js')}}"></script>
  <script src="{{asset('js/scripts/cards/widgets.js')}}"></script>
  <script src="{{asset('js/scripts/charts/chart-apex.js')}}"></script>
  <script src="{{asset('js/scripts/extensions/drag-drop.js')}}"></script>
  <script src="{{asset('vendors/js/pickers/daterange/daterangepicker.js')}}"></script>
  <script src="{{asset('js/scripts/custom.js')}}"></script>
  <script type="text/javascript">
  
  $(function(){
    load_data('dashboard');
  });
  $(document).ready(function() {
      $('#mentionselect').trigger("change");
  });
  </script>
  
  @endsection
  