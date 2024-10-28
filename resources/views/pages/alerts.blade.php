@extends('layouts.contentLayoutMaster')
{{-- page Title --}}
@section('title','Alerts')
{{-- vendor css --}}
@section('vendor-styles')

@endsection
@section('page-styles')
<link rel="stylesheet" type="text/css" href="{{asset('css/wizard.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/pickadate/pickadate.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/daterange/daterangepicker.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/app-file-manager.css')}}">

@endsection
@section('content')
<section id="reports_settings">
    <div class="col-sm-12" style="padding:0px;">
      <div class="row" id="basic-table">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h4 class="card-title">List of filters created</h4>
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
                      <th>#</th>
                      <th>TOPIC NAME</th>
                      <th>HASHTAGS & KEYWORDS</th>
                      <th>FILTERS ADDED</th>
                      <th>FREQUENCY</th>
                      <th>ACTION</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($filters as $key => $filter)
                    <tr>
                      <td class="text-bold-500">{{ $key+1 }}</td>
                      <td> {{ $filter->topic_title }}</td>
                      <td class="text-bold-500">
                        @if($filter->topic_keywords)
                          @foreach (explode(',',$filter->topic_keywords) as $keyword)
                            {{-- <div class="badge badge-primary mr-1 mb-1">{{ $keyword }}</div> --}}
                            <span span class="badge badge-primary">{{ $keyword }}</span>
                          @endforeach
                        @endif
                        @if($filter->topic_hash_tags)
                          @foreach (explode(',',$filter->topic_keywords) as $keyword)
                            {{-- <div class="badge badge-primary mr-1 mb-1">{{ $keyword }}</div> --}}
                            <span span class="badge badge-primary">{{ $keyword }}</span>
                          @endforeach
                        @endif
                      </td>
                      <td>
                        {{-- {{ dd($filter->filter) }} --}}
                        @if($filter->filter)
                          @foreach (explode(',',$filter->filter->filter_keywords) as $keyword)
                            <span span class="badge badge-info">{{ $keyword }}</span>
                          @endforeach
                        @endif
                        @if($filter->filter)
                          @foreach (explode(',',$filter->filter->filter_emails) as $keyword)
                            <span span class="badge badge-info">{{ $keyword }}</span>
                          @endforeach
                        @endif
                      </td>
                      <td>
                        @if (@empty($filter->filter->filter_freq))
                          Not set
                        @else
                          {{ $filter->filter->filter_freq }}
                        @endif
                      </td>
                      <td>
                        <a href="{{ route('detail',$filter->topic_id) }}" class="btn mr-1 mb-1 btn-success btn-sm">Manage</a>
                        <button type="button" class="btn mr-1 mb-1 btn-danger btn-sm deleteAlert" @if($filter->filter) id="{{ $filter->filter->filter_id }}" @else   @endif>Delete</button>
                      </td>
                    </tr>
                    @endforeach
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
<script type="text/javascript">
$( document ).ready(function() {
    //alert( "ready!" );
    $('.deleteAlert').click(function(){
      //Some code

      if (confirm('Are you sure you want to reset this alert?')) {
        //alert(  );
        $.ajax({
          type:'POST',
          url:'/alerts/delete/'+$(this).attr('id'),
          data : {
            "_token": "{{ csrf_token() }}" ,
            "id":$(this).attr('id')  //pass the CSRF_TOKEN()
          },
          success:function(data){
            location.reload();
          }
        });
      }
    });
});
</script>
@endsection
