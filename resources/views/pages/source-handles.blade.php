@extends('layouts.contentLayoutMaster')
{{-- page Title --}}
@section('title','Source Handles')
{{-- vendor css --}}
@section('vendor-styles')

@endsection
@section('page-styles')
<link rel="stylesheet" type="text/css" href="{{asset('css/dashboard-ecommerce.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/dashboard-analytics.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('css/widgets.min.css')}}">


@endsection
@section('content')
<div class="col-sm-12" style="padding: 0px 0px 0px 0px;">
    <div class="card">
        <div class="card-header">Here you can manage different source handles under your account.<br>Our system will fetch data from respective source and add into our system for analysis. Data fetched from any source will only be visible under your account.<br><br>For private source channels, already authenticated / connected accounts can be disconnected anytime. After disconnection, our system will not be able to access data from that particular account.</div>
    </div>
</div>

<div class="col-sm-12" style="padding-left: 0px;"><h5>Public sources</h5></div>

<div class="row">
    <div class="col-sm-3" style="float: left;">
        <div class="card">
            <div class="card-header">
                <table style="width: 100%; padding: 0px; margin: 0px;">
                    <tr>
                        <td style="width: 25%; vertical-align: top;"><i class="bx bxs-group" style="font-size: 4rem; color:#6e82b6 !important"></i></td>
                        <td style="width: 75%;">
                            <div><h4>Social media</h4></div>
                            <div style="height: 70px;">Data from all popular social channels is default activated.</div>
                            <div style="padding-top: 35px;"><button type="button" class="btn mr-1 mb-1 btn-primary btn-sm" style="background-color: #6e82b6 !important;">Activated</button></div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-sm-3" style="float: left;">
        <div class="card">
            <div class="card-header">
                <table style="width: 100%; padding: 0px; margin: 0px;">
                    <tr>
                        <td style="width: 25%; vertical-align: top;"><i class="bx bx-globe" style="font-size: 4rem; color:#b6aa6e !important"></i></td>
                        <td style="width: 75%;">
                            <div><h4>Web</h4></div>
                            <div style="height: 70px;">Data from Web is by default activated.</div>
                            <div style="padding-top: 35px;"><button type="button" class="btn mr-1 mb-1 btn-primary btn-sm" style="background-color: #b6aa6e !important;">Activated</button></div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-sm-3" style="float: left;">
        <div class="card">
            <div class="card-header">
                <table style="width: 100%; padding: 0px; margin: 0px;">
                    <tr>
                        <td style="width: 25%; vertical-align: top;"><i class="bx bx-news" style="font-size: 4rem; color:#da9968 !important"></i></td>
                        <td style="width: 75%;">
                            <div><h4>Print media</h4></div>
                            <div style="height: 70px;">Data from Print media is default activated.</div>
                            <div style="padding-top: 35px;"><button type="button" class="btn mr-1 mb-1 btn-primary btn-sm" style="background-color: #da9968 !important;">Activated</button></div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
</div>

<div class="col-sm-12" style="padding: 20px 0px 0px 0px;"><h5>Private sources</h5></div>

<div class="row">
    <div class="col-sm-3">
        <div class="card">
            <div class="card-header">
                <table style="width: 100%; padding: 0px; margin: 0px;">
                    <tr>
                        <td style="width: 25%; vertical-align: top;"><i class="bx bxl-twitter" style="font-size: 4rem; color:#00ABEA !important"></i></td>
                        <td style="width: 75%;">
                            <div><h4>Twitter</h4></div>
                            <div>Connect your Twitter account to fetch data into our system for analysis.</div>
                            <?php
                            $twitter_dm_access = Helper::get_module_access('Twitter');
                            if($twitter_dm_access)
                            {
                                $twitter_dm_handle_name = Helper::get_source_handle_name('Twitter');
                            ?>
                            <div style="padding-top: 5px;">@<?php echo $twitter_dm_handle_name; ?></div>
                            <div style="padding-top: 10px;"><button type="button" class="btn mr-1 mb-1 btn-primary btn-sm" style="background-color: #00ABEA !important;">Disconnect</button></div>
                            <?php
                            }
                            else
                            {
                            ?>
                            <div style="padding-top: 35px;"><button type="button" class="btn mr-1 mb-1 btn-primary btn-sm" style="background-color: #00ABEA !important;">Authenticate account</button></div>
                            <?php
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-sm-3">
        <div class="card">
            <div class="card-header">
                <table style="width: 100%; padding: 0px; margin: 0px;">
                    <tr>
                        <td style="width: 25%; vertical-align: top;"><i class="bx bxl-linkedin" style="font-size: 4rem; color:#0077B5 !important"></i></td>
                        <td style="width: 75%;">
                            <div><h4>Linkedin</h4></div>
                            <div>Connect your Linkedin account to fetch data into our system for analysis.</div>
                            <div style="padding-top: 35px;"><button type="button" class="btn mr-1 mb-1 btn-primary btn-sm" style="background-color: #0077B5 !important;">Authenticate account</button></div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-sm-3">
        <div class="card">
            <div class="card-header">
                <table style="width: 100%; padding: 0px; margin: 0px;">
                    <tr>
                        <td style="width: 25%; vertical-align: top;"><i class="bx bxl-instagram-alt" style="font-size: 4rem; color:#C13584 !important"></i></td>
                        <td style="width: 75%;">
                            <div><h4>Instagram</h4></div>
                            <div>Connect your Instagram account to fetch data into our system for analysis.</div>
                            <div style="padding-top: 35px;"><button type="button" class="btn mr-1 mb-1 btn-primary btn-sm" style="background-color: #C13584 !important;">Authenticate account</button></div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-sm-3">
        <div class="card">
            <div class="card-header">
                <table style="width: 100%; padding: 0px; margin: 0px;">
                    <tr>
                        <td style="width: 25%; vertical-align: top; padding-top: 10px;"><img src="{{asset('images/logo/tripadvisor-logo.png')}}" style="width: 56px;"></td>
                        <td style="width: 75%;">
                            <div><h4>Tripadvisor</h4></div>
                            <div>Connect your Tripadvisor account to fetch data into our system for analysis.</div>
                            <div style="padding-top: 35px;"><button type="button" class="btn mr-1 mb-1 btn-primary btn-sm" style="background-color: #000000 !important;">Authenticate account</button></div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
{{-- vendor scripts --}}
@section('vendor-scripts')
 
@endsection
  
@section('page-scripts')
<script src="{{asset('js/scripts/custom.js')}}"></script>
@endsection
  