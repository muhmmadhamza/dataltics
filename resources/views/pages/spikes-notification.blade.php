@extends('layouts.contentLayoutMaster')
{{-- page Title --}}
@section('title','Spikes
')
{{-- vendor styles --}}
@section('vendor-styles')
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/charts/apexcharts.css')}}">
@endsection

@section('content')<!-- apex charts section start -->
<section id="apexchart">
  <div class="row">
    <!-- Line Chart -->
    <div class="col-lg-12 col-md-12">
      <div class="card">
        <div class="card-header">
          <h4 class="card-title">Line Chart</h4>
        </div>
        <div class="card-body">
          <div id="line-chart"></div>
        </div>
      </div>
    </div>
    <div class="col-lg-12 col-md-12">
      <div class="card">
        <div class="card-header">
          <h4 class="card-title">Line Chart</h4>
        </div>
        <div class="card-body">
          <div id="line-chart2"></div>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- Apex charts section end -->
@endsection
{{-- vendor scripts --}}
@section('vendor-scripts')
<script src="{{asset('vendors/js/charts/apexcharts.min.js')}}"></script>
@endsection
{{-- page scripts --}}
@section('page-scripts')
{{-- <script src="{{asset('js/scripts/charts/chart-apex.js')}}"></script> --}}
<script>
  $(document).ready(function () {

var $primary = '#5A8DEE',
  $success = '#39DA8A',
  $danger = '#FF5B5C',
  $warning = '#FDAC41',
  $info = '#00CFDD',
  $label_color_light = '#E6EAEE';

var themeColors = [$primary, $warning, $danger, $success, $info];

// Line Chart
// ----------------------------------
var lineChartOptions = {
  chart: {
    height: 350,
    type: 'line',
    zoom: {
      enabled: false
    }
  },
  colors: themeColors,
  dataLabels: {
    enabled: false
  },
  stroke: {
    curve: 'straight'
  },
  series: [{
    name: "Desktops",
    data: [10, 41, 35, 51, 49, 62, 69],
  }],
  // title: {
  //   text: 'Product Trends by Month',
  //   align: 'left'
  // },
  grid: {
    row: {
      colors: ['#f3f3f3', 'transparent'], // takes an array which will be repeated on columns
      opacity: 0.5
    },
  },
  xaxis: {
    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
  },
  yaxis: {
    tickAmount: 5,
  }
}
var lineChart = new ApexCharts(
  document.querySelector("#line-chart"),
  lineChartOptions
);
lineChart.render();


var lineChart = new ApexCharts(
  document.querySelector("#line-chart2"),
  lineChartOptions
);
lineChart.render();


  });
</script>
@endsection
