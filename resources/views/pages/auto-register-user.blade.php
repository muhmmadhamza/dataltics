@extends('layouts.fullLayoutMaster')
{{-- page title --}}
@section('title','Welcome to Datalyticx')
{{-- page scripts --}}
@section('page-styles')
    <!--<link rel="stylesheet" type="text/css" href="{{asset('css/pages/authentication.css')}}">-->
@endsection

@section('content')
    <!-- login page start -->
    <section id="auth-login" class="row flexbox-container">
        <div class="col-xl-8 col-11">
            <div class="card bg-authentication mb-0">
                <div class="row m-0">
                    <!-- left section-login -->
                    <div class="col-md-6 col-12 px-0">
                        <div class="card disable-rounded-right mb-0 p-2 h-100 d-flex justify-content-center">
                            <div class="card-header pb-1">
                                <div class="card-title">
                                    <h4 class="text-center mb-2">Account Auto Register</h4>
                                </div>
                            </div>
                            <div class="card-body">
                                <form action="" name="login-form" id="ajaxautoregisterform" autocomplete="off">
                                    <input type="hidden" name="invitation_code" value="{{$invitation_code}}">
                                    <p style="color:red;" class="error_show" id="overlay_hide"></p>
                                    <p style="color:green;" class="success_show" id="overlay_hide_success"></p>
                                    <div id="login_error" style="display:none; color:#ff0000;"></div>
                                    <div class="form-group mb-50">
                                        <label class="text-bold-600" for="customer_password">Password*</label>
                                        <input type="password" class="form-control" id="customer_password" name="customer_password" placeholder="Password"></div>
                                    <div class="form-group">
                                        <label class="text-bold-600" for="customer_password_confirm">Confirm Password*</label>
                                        <input type="password" class="form-control" id="customer_password_confirm" name="customer_password_confirm" placeholder="Confirm Password">
                                    </div>
                                    <div class="form-group d-flex flex-md-row flex-column justify-content-between align-items-center">
                                        <div class="text-left">
                                            <div class="spinner-border" role="status" id="loading_icon" style="display:none;">
                                                <span class="sr-only">Loading...</span>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-primary glow w-100 position-relative" id="auto_register">Save
                                        <i id="icon-arrow" class="bx bx-right-arrow-alt"></i></button>
                                    <input type="hidden" name="_token" id="_token" value="{{ csrf_token() }}">
                                    <input type="hidden" name="mode" value="validate_login">
                                </form>
                                <hr>
                                <div class="text-center">
                                    <small class="mr-25">Don't have an account?</small>
                                    <a href="https://www.datalyticx.ai/contact"><small>Contact us</small></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- right section image -->
                    <div class="col-md-6 d-md-block d-none text-center align-self-center p-3">
                        <img class="img-fluid" src="{{asset('images/logo/logo.png')}}" alt="branding logo">
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- login page ends -->

    <script src="{{asset('vendors/js/vendors.min.js')}}"></script>
    <script src="{{asset('js/scripts/custom.js')}}"></script>
@endsection
