@extends('layouts.contentLayoutMaster')
{{-- title --}}
@section('title','Surveys')
{{-- vendor scripts --}}
@section('vendor-styles')
<!-- <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/tables/datatable/dataTables.bootstrap4.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/tables/datatable/responsive.bootstrap4.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/tables/datatable/buttons.bootstrap4.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/forms/select/select2.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/tagsinput.css')}}"> -->
@endsection

@section('content')
<style>

.input-group-text {
    border: none!important;
}

label {
  text-transform: none!important;
}

label.btn.btn-primary.white.active {
    background: #3f51b5 !important;
}
label.btn.btn-primary.white {
    background: #a8c1f3 !important;
}
.chat_test {
  background: #39da8a;
  padding: 3px;
  margin: 2px 0px;
}
.add-question {

}

.add-question:hover {
  border-color: blue;
  border: 2px solid black;
  transition: border-color 0.2s ease-in-out;
}
.slide-toggle-small {
  position: relative;
  display: inline-block;
  width: 25px;
  height: 12px;
}

.slide-toggle-small input {
  display: none;
}

.slider-small {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  border-radius: 20px;
  -webkit-transition: .4s;
  transition: .4s;
}

.slider-small:before {
  position: absolute;
  content: "";
  height: 8px;
  width: 8px;
  left: 2px;
  bottom: 2px;
  background-color: white;
  border-radius: 50%;
  -webkit-transition: .4s;
  transition: .4s;
}

input:checked + .slider-small {
  background-color: #2196F3;
}

input:focus + .slider-small {
  box-shadow: 0 0 1px #2196F3;
}

input:checked + .slider-small:before {
  -webkit-transform: translateX(20px);
  -ms-transform: translateX(15px);
  transform: translateX(15px);
}

</style>
<!-- Bootstrap Select start -->
<section class="bootstrap-select">
  <div class="row">
    <div class="col-12 mt-1 mb-2">
      <hr>
      Here you can create different surveys according to your need.
    </div>
  </div>
</section>
<!-- Bootstrap Select end -->
<section id="horizontal-input">
  <!-- Button trigger for basic modal -->
  <div class="row">
   <div class="col-md-12">
      <div style="float: right;margin:15px 0px;">
        <button type="button" class="btn btn-primary">
          Add survey
        </button>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-md-12">
      @if (session()->has('success'))
      <div class="alert alert-success">
          @if(is_array(session('success')))
              <ul style="margin-bottom: 0px;">
                  @foreach (session('success') as $message)
                      <li>{{ $message }}</li>
                  @endforeach
              </ul>
          @else
              {{ session('success') }}
          @endif
      </div>
      @endif
    </div>
  </div>



  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-body">
          <form >
            
            <div class="form-row">
  
              <div class="col-sm-6 mb-1">
                <label for="text-input">Survey Title</label>
                <input type="text" class="form-control" id="text-input" placeholder="Enter text">
              </div>
              <div class="col-sm-6 mb-1">
                <label for="text-input">Text Input</label>
                <input type="file" class="form-control-file" id="file-input">
              </div>
            </div>

            <div class="form-row">
  
              <div class="col-sm-6 mb-1 ml-1">

                <label class="slide-toggle-small">
                  <input type="checkbox">
                  <span class="slider-small round"></span>
                </label>
                <label style="margin-left: 7px;">Check this box if you want to take surveyor details</label>
              </div>
             
            </div>


            <fieldset>
              <legend>Questions</legend>
              <div class="container ml-0 mt-1 rounded p-3" style="background-color:#F2F4F4;width:70%">
                <div class="form-row">
                  <div class="col-sm-12 mb-1">
                    <label for="box-text">Question 1</label>
                    <input type="text" class="form-control" id="box-text" placeholder="Enter text">
                  </div>
                </div>
                <div class="form-row">
                  <div class="col-sm-6 mb-1">
                    <label for="box-text">Question Type</label>
                    <div class="input-group">
                      <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Type</button>
                      <div class="dropdown-menu">
                        <a class="dropdown-item" href="#">Option 1</a>
                        <a class="dropdown-item" href="#">Option 2</a>
                        <a class="dropdown-item" href="#">Option 3</a>
                      </div>
                    </div>
                  </div>
                  <div class="col-sm-6 mb-1 float-right">
                    <div class="float-right">
                      <label for="box-text"></label>
                      <div class="input-group-append">
                      <span class="input-group-text"><i class='bx bx-lock'></i>Move up</span>
                      <span class="input-group-text"><i class='bx bx-lock'></i>Move down</span>
                      <span class="input-group-text"><i class='bx bx-lock'></i>Required</span>
                      <span class="input-group-text"><i class='bx bx-lock'></i>Delete</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="container ml-0  mt-1  rounded p-3" style="background-color:#F2F4F4;width:70%">
                <div class="form-row">
                  <div class="col-sm-12">
                    <label for="box-text" class="col-12 pl-0 pr-0">
                      Question 1
                        <div class="input-group-append float-right">
                        <span class="input-group-text" style="margin-left: 7px;"><i class='bx bx-sort-up' style='color:#1c1b77'  ></i><label style="margin-left: 7px; color: #475F7B;font-weight: 400;">Move Up</label></span>

                        <span class="input-group-text" style="margin-left: 7px;"><i class='bx bx-sort-down' style='color:#1c1b77'  ></i><label style="margin-left: 7px; color: #475F7B;font-weight: 400;">Move Down</label></span>
                        </div>
                    </label>
                    <input type="text" class="form-control" id="box-text" placeholder="Enter text">
                  </div>
                </div>
                <div class="form-row">
                  <div class="col-sm-6 mb-1">
                  <div class="btn-group">
  <button type="button" class="btn btn-outline-secondary dropdown-toggle rounded-0" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    Select
    <i class="fas fa-caret-down ml-1"></i>
  </button>
  <div class="dropdown-menu rounded-0">
    <a class="dropdown-item" href="#">Option 1</a>
    <a class="dropdown-item" href="#">Option 2</a>
    <a class="dropdown-item" href="#">Option 3</a>
  </div>
</div>
                  </div>
                  <div class="col-sm-6 mb-1 float-right">
                    <div class="float-right">
                      <label for="box-text"></label>
                      <div class="input-group-append">
                        <span class="input-group-text">
                          <label class="slide-toggle-small">
                            <input type="checkbox">
                            <span class="slider-small round"></span>
                          </label>
                          <label style="margin-left: 7px; color: #475F7B;font-weight: 400;">Required</label>
                        </span>
                        <span class="input-group-text" style="margin-left: 7px;"><i class='bx bx-trash' style="color:#ff9814;"></i> <label style="margin-left: 7px; color: #475F7B;font-weight: 400;">Delete</label></span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="container add-question ml-0 rounded p-1 mt-1" style="background-color:#F2F4F4;width:70%">
              <div class="text-center">
                Add Question
              </div>
              </div>

            </fieldset>  
            <!-- <button type="submit" class="btn btn-primary">Submit</button> -->
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- Select2 Advance Options start -->

<!-- Select2 Advance Options end -->
@endsection

{{-- vendor scripts --}}
@section('vendor-scripts')
<!-- <script src="{{asset('vendors/js/tables/datatable/jquery.dataTables.min.js')}}"></script>
<script src="{{asset('vendors/js/tables/datatable/dataTables.bootstrap4.min.js')}}"></script>
<script src="{{asset('vendors/js/tables/datatable/dataTables.buttons.min.js')}}"></script>
<script src="{{asset('vendors/js/tables/datatable/buttons.bootstrap4.min.js')}}"></script>
<script src="{{asset('vendors/js/forms/select/select2.full.min.js')}}"></script> -->
@endsection
{{-- page scrips --}}
@section('page-scripts')
<!-- <script src="{{asset('js/scripts/forms/select/form-select2.js')}}"></script>
<script src="{{asset('js/scripts/tagsinput.js')}}"></script>
<script src="{{asset('js/scripts/datatables/datatable.js')}}"></script> -->
<script>
$(document).ready(function() {
  $('.dropdown-item').click(function() {
    var value = $(this).attr('data-value');
    $(this).closest('.dropdown').find('.dropdown-toggle').text(value);
  });
});
</script>
@endsection
