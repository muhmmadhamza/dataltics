@extends('layouts.contentLayoutMaster')
{{-- title --}}
@section('title','Dashboard Analytics')
{{-- venodr style --}}
@section('vendor-styles')
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/charts/apexcharts.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/dragula.min.css')}}">
@endsection
{{-- page style --}}
@section('page-styles')
<link rel="stylesheet" type="text/css" href="{{asset('css/widgets.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/dashboard-analytics.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/drag-and-drop.min.css')}}">
@endsection
@section('content')
<!-- Dashboard Analytics Start -->
<section class="list-group draggable" id="basic-list-group">
  <div class="row">
    <!-- Website Analytics Starts-->
    <div class="col-md-5 col-sm-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h4 class="card-title">Summary</h4>
          <i class="bx bx-dots-vertical-rounded font-medium-3 cursor-pointer"></i>
        </div>
        <div class="card-body pb-1">
          <div class="d-flex justify-content-around align-items-center flex-wrap">
            <div class="user-analytics mr-2">
              <i class="bx bx-user mr-25 align-middle"></i>
              <span class="align-middle text-muted">Users</span>
              <div class="d-flex">
                <div id="radial-success-chart"></div>
                <h3 class="mt-1 ml-50">61K</h3>
              </div>
            </div>
            <div class="sessions-analytics mr-2">
              <i class="bx bx-trending-up align-middle mr-25"></i>
              <span class="align-middle text-muted">Sessions</span>
              <div class="d-flex">
                <div id="radial-warning-chart"></div>
                <h3 class="mt-1 ml-50">92K</h3>
              </div>
            </div>
            <div class="bounce-rate-analytics">
              <i class="bx bx-pie-chart-alt align-middle mr-25"></i>
              <span class="align-middle text-muted">Bounce Rate</span>
              <div class="d-flex">
                <div id="radial-danger-chart"></div>
                <h3 class="mt-1 ml-50">72.6%</h3>
              </div>
            </div>
          </div>
          <div id="analytics-bar-chart" class="my-75"></div>
        </div>
      </div>

    </div>
    <div class="col-xl-4 col-md-6 col-sm-12 dashboard-referral-impression">
      <div class="row">
        <!-- Referral Chart Starts-->
        <div class="col-xl-12 col-12">
          <div class="card">
            <div class="card-body text-center pb-0">
              <h2>$32,690</h2>
              <span class="text-muted">Referral 40%</span>
              <div id="success-line-chart"></div>
            </div>
          </div>
        </div>
        <!-- Impression Radial Chart Starts-->
        <div class="col-xl-12 col-12">
          <div class="card">
            <div class="card-body donut-chart-wrapper">
              <div id="donut-chart" class="d-flex justify-content-center"></div>
              <ul class="list-inline d-flex justify-content-around mb-0">
                <li> <span class="bullet bullet-xs bullet-primary mr-50"></span>Social</li>
                <li> <span class="bullet bullet-xs bullet-info mr-50"></span>Email</li>
                <li> <span class="bullet bullet-xs bullet-warning mr-50"></span>Search</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-md-12 col-sm-12">
      <div class="row">
        <!-- Conversion Chart Starts-->
        <div class="col-xl-12 col-md-6 col-12">
          <div class="card">
            <div class="card-header">
              <h4 class="card-title">Main Social Channel</h4>
            </div>
            <div class="card-body pt-1">
              <div class="d-flex activity-content">
                <div class="avatar bg-rgba-primary m-0 mr-75">
                  <div class="avatar-content">
                    <i class="bx bxl-facebook-square text-primary"></i>
                  </div>
                </div>
                <div class="activity-progress flex-grow-1">
                  <small class="text-muted d-inline-block mb-50">Total Sales</small>
                  <small class="float-right">$8,125</small>
                  <div class="progress progress-bar-primary progress-sm">
                    <div class="progress-bar" role="progressbar" aria-valuenow="50" style="width:50%"></div>
                  </div>
                </div>
              </div>
              <div class="d-flex activity-content pt-3">
                <div class="avatar bg-rgba-success m-0 mr-75">
                  <div class="avatar-content">
                    <i class="bx bxl-linkedin text-success"></i>
                  </div>
                </div>
                <div class="activity-progress flex-grow-1">
                  <small class="text-muted d-inline-block mb-50">Income Amount</small>
                  <small class="float-right">$18,963</small>
                  <div class="progress progress-bar-success progress-sm">
                    <div class="progress-bar" role="progressbar" aria-valuenow="80" style="width:80%"></div>
                  </div>
                </div>
              </div>
              <div class="d-flex activity-content pt-3">
                <div class="avatar bg-rgba-warning m-0 mr-75">
                  <div class="avatar-content">
                    <i class="bx bxl-twitter text-warning"></i>
                  </div>
                </div>
                <div class="activity-progress flex-grow-1">
                  <small class="text-muted d-inline-block mb-50">Total Budget</small>
                  <small class="float-right">$14,150</small>
                  <div class="progress progress-bar-warning progress-sm">
                    <div class="progress-bar" role="progressbar" aria-valuenow="60" style="width:60%"></div>
                  </div>
                </div>
              </div>
              <div class="d-flex mb-75 pt-3">
                <div class="avatar bg-rgba-danger m-0 mr-75">
                  <div class="avatar-content">
                    <i class="bx bxl-youtube text-danger"></i>
                  </div>
                </div>
                <div class="activity-progress flex-grow-1">
                  <small class="text-muted d-inline-block mb-50">Completed Tasks</small>
                  <small class="float-right">106</small>
                  <div class="progress progress-bar-danger progress-sm">
                    <div class="progress-bar" role="progressbar" aria-valuenow="30" style="width:30%"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="row">
    <!-- Activity Card Starts-->
    <div class="col-xl-9 col-md-6 col-12 activity-card">
      <div class="card">
        <div class="card-header">
          <h4 class="card-title">Bar Chart</h4>
        </div>
        <div class="card-body">
          <div id="bar-chart"></div>
        </div>
      </div>
    </div>
    <!-- Profit Report Card Starts-->
    <div class="col-xl-3 col-md-6 col-12 profit-report-card">
      <div class="row">
        <div class="col-md-12 col-sm-6">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h4 class="card-title">CSAT</h4>
              <i class="bx bx-dots-vertical-rounded font-medium-3 cursor-pointer"></i>
            </div>
            <div class="card-body d-flex justify-content-around">
              <div class="d-inline-flex mr-xl-2">
                <div id="profit-primary-chart"></div>
                <div class="profit-content ml-50 mt-50">
                  <h5 class="mb-0">$12k</h5>
                  <small class="text-muted">2020</small>
                </div>
              </div>
              <div class="d-inline-flex">
                <div id="profit-info-chart"></div>
                <div class="profit-content ml-50 mt-50">
                  <h5 class="mb-0">$64k</h5>
                  <small class="text-muted">2019</small>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-12 col-sm-6">
          <div class="card">
            <div class="card text-center">
              <div class="card-body">
                <div class="badge-circle badge-circle-lg badge-circle-light-primary mx-auto my-1">
                  <i class="bx bx-money font-medium-5"></i>
                </div>
                <p class="text-muted mb-0 line-ellipsis">Potential Revenue Loss</p>
                <h2 class="mb-0">OMR 26,250</h2>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Mixed Chart -->
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h4 class="card-title">Mixed Chart</h4>
        </div>
        <div class="card-body">
          <div id="mixed-chart"></div>
        </div>
      </div>
    </div>
  </div>
  <div class="row">
    <!-- Statistics progress Widget -->
    <div class="col-xl-6 col-md-6 progress-card">
      <div class="card">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center pr-2">
          <h5 class="card-title">Statistics</h5>
          <i class="bx bx-dots-vertical-rounded font-medium-3 align-middle cursor-pointer"></i>
        </div>
        <div class="table-responsive">
          <table class="table table-borderless">
            <tbody>
              <tr>
                <td class="w-25">Graphic</td>
                <td>
                  <div class="progress progress-bar-info progress-sm mb-0">
                    <div class="progress-bar" role="progressbar" aria-valuenow="24" aria-valuemin="80"
                      aria-valuemax="100" style="width:24%;"></div>
                  </div>
                </td>
                <td class="w-25 text-right">24%</td>
              </tr>
              <tr>
                <td class="w-25">Prototyping</td>
                <td>
                  <div class="progress progress-bar-success progress-sm mb-0">
                    <div class="progress-bar" role="progressbar" aria-valuenow="61" aria-valuemin="80"
                      aria-valuemax="100" style="width:61%;"></div>
                  </div>
                </td>
                <td class="w-25 text-right">61%</td>
              </tr>
              <tr>
                <td class="w-25">Sketching</td>
                <td>
                  <div class="progress progress-bar-primary progress-sm mb-0">
                    <div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="80"
                      aria-valuemax="100" style="width:60%;"></div>
                  </div>
                </td>
                <td class="w-25 text-right">24%</td>
              </tr>
              <tr>
                <td class="w-25">Modeling</td>
                <td>
                  <div class="progress progress-bar-info progress-sm mb-0">
                    <div class="progress-bar" role="progressbar" aria-valuenow="35" aria-valuemin="80"
                      aria-valuemax="100" style="width:35%;"></div>
                  </div>
                </td>
                <td class="w-25 text-right">35%</td>
              </tr>
              <tr>
                <td class="w-25">Images</td>
                <td>
                  <div class="progress progress-bar-primary progress-sm mb-0">
                    <div class="progress-bar" role="progressbar" aria-valuenow="65" aria-valuemin="80"
                      aria-valuemax="100" style="width:65%;"></div>
                  </div>
                </td>
                <td class="w-25 text-right">65%</td>
              </tr>
              <tr>
                <td class="w-25">HTML</td>
                <td>
                  <div class="progress progress-bar-success progress-sm mb-0">
                    <div class="progress-bar" role="progressbar" aria-valuenow="32" aria-valuemin="80"
                      aria-valuemax="100" style="width:32%;"></div>
                  </div>
                </td>
                <td class="w-25 text-right">32%</td>
              </tr>
              <tr>
                <td class="w-25">Laravel</td>
                <td>
                  <div class="progress progress-bar-danger progress-sm mb-0">
                    <div class="progress-bar" role="progressbar" aria-valuenow="40" aria-valuemin="80"
                      aria-valuemax="100" style="width:40%;"></div>
                  </div>
                </td>
                <td class="w-25 text-right">40%</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <!-- Statistics progress Widget Ends -->
     <!-- Statistics Multi Radial Chart Starts -->
     <div class="col-lg-6 col-md-6">
      <div class="card">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
          <h4 class="card-title">Statistics</h4>
          <i class="bx bx-dots-vertical-rounded font-medium-3 cursor-pointer"></i>
        </div>
        <div class="card-body">
          <div id="radial-chart-multi"></div>
          <ul class="list-inline text-center mt-2">
            <li class="mr-2"> <span class="bullet bullet-xs bullet-primary mr-50"></span>Comments</li>
            <li class="mr-2"> <span class="bullet bullet-xs bullet-warning mr-50"></span>Sharing</li>
            <li> <i class="bullet bullet-xs bullet-danger mr-50"></i>Replies</li>
          </ul>
        </div>
      </div>
    </div>
    <!-- Statistics Multi Radial Chart Ends -->
    <!-- Earnings Widget Swiper Ends -->
  </div>
</section>
<!-- Dashboard Analytics end -->
@endsection
{{-- vendor scripts --}}
@section('vendor-scripts')
<script src="{{asset('vendors/js/charts/apexcharts.min.js')}}"></script>
<script src="{{asset('vendors/js/extensions/dragula.min.js')}}"></script>
@endsection

@section('page-scripts')
<script src="{{asset('js/scripts/pages/dashboard-analytics.js')}}"></script>
<script src="{{asset('js/scripts/charts/chart-apex.js')}}"></script>
<script src="{{asset('js/scripts/cards/widgets.js')}}"></script>
<script src="{{asset('js/scripts/extensions/drag-drop.js')}}"></script>
@endsection
