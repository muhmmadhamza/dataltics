
@extends('layouts.contentLayoutMaster')
{{-- page title --}}
@section('title','My Account')
<style>
    .scroll {
    overflow-y: scroll;
    height: 145px;
    width: 320px;
    margin: 10px 5px; padding: 5px;
    text-align: justify;
}
.scroll::-webkit-scrollbar {
    width:10px;
}
.scroll::-webkit-scrollbar-track {
    -webkit-box-shadow:inset 0 0 6px rgba(0,0,0,0.3);
    border-radius:5px;
}
.scroll::-webkit-scrollbar-thumb {
    border-radius:5px;
    -webkit-box-shadow: inset 0 0 6px #5A8DEE;;
}
</style>
@isset($pageConfigs)
    {!! Helper::updatePageConfig($pageConfigs) !!}
@endisset
@php
    // confiData variable layoutClasses array in Helper.php file.
      $configData = Helper::applClasses();
@endphp
@section('content')
<?php //var_dump($configData); ?>
    <!-- collapse with icon -->
    <section id="collapsible-with-icon">
        <div class="card card-body mb-2">
        <h4 class="mt-2">Manage your account & settings</h4>
        <p>
            Be careful in updating your personal details & settings.
        </p>
        </div>


        <section id="accordion-icon-wrapper">
            <div class="accordion collapse-icon accordion-icon-rotate" id="accordionWrapa2">
                <div class="card collapse-header">
                    <div id="heading5" class="card-header" data-toggle="collapse" data-target="#accordion5" aria-expanded="false"
                         aria-controls="accordion5" role="tablist">
        <span class="collapse-title">
          <i class="bx bx-edit align-middle"></i>
          <span class="align-middle">Change Password</span>
        </span>
                    </div>
                    <div id="accordion5" role="tabpanel" data-parent="#accordionWrapa2" aria-labelledby="heading5" class="collapse">
                        <div class="card-body">
                            <div class="tab-pane fade active show" id="account-vertical-password" role="tabpanel" aria-labelledby="account-pill-password" aria-expanded="false">
                                <form class="validate-form" novalidate="novalidate" enctype="multipart/form-data" id="ajaxformpassword">
                                    <p style="color:red;" class="error_show" id="overlay_hide"></p>
                                    <p style="color:green;" class="success_show" id="overlay_hide_success"></p>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <div class="controls">
                                                    <label>Old Password*</label>
                                                    <input type="password" class="form-control old_password" placeholder="Old Password" name="old_password">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <div class="controls">
                                                    <label>New Password*</label>
                                                    <input type="password" class="form-control new_password" placeholder="New Password" id="account_new_password" name="new_password">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group">
                                                <div class="controls">
                                                    <label>Retype new Password*</label>
                                                    <input type="password" class="form-control re_type_new_passord" data-validation-match-match="password" placeholder="New Password" name="re_type_new_password" >
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6 d-flex flex-sm-row flex-column">
                                            <button type="button" class="btn btn-primary glow mr-sm-1 mb-1" id="password_submit">Save
                                                changes</button>
                                            <button type="reset" class="btn btn-light mb-1">Cancel</button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
                @if($customer_registered_scope != 'IS')
                <div class="card collapse-header">
                    <div id="heading6" class="card-header" data-toggle="collapse" role="button" data-target="#accordion6"
                         aria-expanded="false" aria-controls="accordion6">
        <span class="collapse-title">
          <i class="bx bxs-bell-ring align-middle"></i>
          <span class="align-middle">Notification Settings</span>
        </span>
                    </div>
                    <div id="accordion6" role="tabpanel" data-parent="#accordionWrapa2" aria-labelledby="heading6" class="collapse"
                         aria-expanded="false">
                        <div class="card-body">
                            <p>Every minute, new mentions are added to the topics you have created. Select below the frequency of email notifications you want to receive for newly added data against your topics.</p>
                            <form class="validate-form" novalidate="novalidate" enctype="multipart/form-data" id="ajaxformnotification">
                                <p style="color:red;" class="error_show_notification" id="overlay_hide_notifiction"></p>
                                <p style="color:green;" class="success_show_notification" id="overlay_notification_success"></p>
                                <ul class="list-unstyled mb-1">
                                    <li class="d-inline-block mr-2 mb-1">
                                        <fieldset>
                                            <div class="radio radio-primary">
                                                <input type="radio" name="notify_freq" value="0h" id="colorRadio0"
                                                       @if($notification_value == "0h")
                                                       checked="checked"
                                                        @endif>
                                                <label for="colorRadio0">Mute notifications</label>
                                            </div>
                                        </fieldset>
                                    </li>
                                    <li class="d-inline-block mr-2 mb-1">
                                        <fieldset>
                                            <div class="radio radio-primary">
                                                <input type="radio" name="notify_freq" value="1h" id="colorRadio1"
                                                       @if($notification_value == "1h")
                                                       checked="checked"
                                                        @endif>
                                                <label for="colorRadio1">Every hour</label>
                                            </div>
                                        </fieldset>
                                    </li>
                                    <li class="d-inline-block mr-2 mb-1">
                                        <fieldset>
                                            <div class="radio radio-primary">
                                                <input type="radio" name="notify_freq" value="3h" id="colorRadio2"
                                                       @if($notification_value == "3h")
                                                       checked="checked"
                                                        @endif>
                                                <label for="colorRadio2">Every 3 hours</label>
                                            </div>
                                        </fieldset>
                                    </li>
                                    <li class="d-inline-block mr-2 mb-1">
                                        <fieldset>
                                            <div class="radio radio-primary">
                                                <input type="radio" name="notify_freq" value="6h" id="colorRadio3"
                                                       @if($notification_value == "6h")
                                                       checked="checked"
                                                        @endif>
                                                <label for="colorRadio3">Every 6 hours</label>
                                            </div>
                                        </fieldset>
                                    </li>
                                    <li class="d-inline-block mr-2 mb-1">
                                        <fieldset>
                                            <div class="radio radio-primary">
                                                <input type="radio" name="notify_freq" value="12h" id="colorRadio4"
                                                       @if($notification_value == "12h")
                                                       checked="checked"
                                                        @endif>
                                                <label for="colorRadio4">Every 12 hours</label>
                                            </div>
                                        </fieldset>
                                    </li>
                                    <li class="d-inline-block mr-2 mb-1">
                                        <fieldset>
                                            <div class="radio radio-primary">
                                                <input type="radio" name="notify_freq" value="24h" id="colorRadio5"
                                                       @if($notification_value == "24h")
                                                       checked="checked"
                                                        @endif>
                                                <label for="colorRadio5">Every 24 hours</label>
                                            </div>
                                        </fieldset>
                                    </li>
                                </ul>
                                <div class="row">
                                    <div class="col-6 d-flex flex-sm-row flex-column">
                                        <button type="button" class="btn btn-primary glow mr-sm-1 mb-1 notification_submit" id="notification_submit">Save
                                            changes</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                                
                <div class="card collapse-header">
                    <div id="heading7" class="card-header" data-toggle="collapse" role="button" data-target="#accordion7"
                         aria-expanded="false" aria-controls="accordion7">
        <span class="collapse-title">
          <i class="bx bx-mail-send align-middle"></i>
          <span class="align-middle">Send Invitations</span>
        </span>
                    </div>
                    <div id="accordion7" role="tabpanel" data-parent="#accordionWrapa2" aria-labelledby="heading7" class="collapse"
                         aria-expanded="false">
                        <div class="card-body">
                            <p class="mb-2">Here you can invite your employees / colleagues to join Datalytics24 under your account..</p>
                            <form class="needs-validation" novalidate="" enctype="multipart/form-data"  id="ajaxformsentinvitation">
                                <p style="color:red;" class="error_show_invitation" id="overlay_invitation"></p>
                                <p style="color:green;" class="success_show_invitation" id="overlay_invitation_success"></p>

                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="fullname">Full Name*</label>
                                        <input type="text" class="form-control fullname" id="validationTooltip01" placeholder="Full Name" name="fullname">
                                        <label for="email"class="mt-2">Email*</label>
                                        <input type="email" class="form-control email" id="validationTooltip02" placeholder="Email" name="email">

                                    </div>
                                    <div class="col-md-8 mb-3">
                                        <h5 class="text-center">Sent Invitations</h5><p class="text-right">
                                        <span class="invite_sent_count">{{ $invitation_used ?  $invitation_used : 0}}</span>
                                            out of
                                            {{ $invitation_allowed ?  $invitation_allowed : 0}}
                                            Invite Sent
                                    </p>

                                        <div class="row">
                                            <div class="col-md-6">
                                                To
                                            </div>
                                            <div class="col-md-3">
                                                Date
                                            </div>
                                            <div class="col-md-3">
                                                Status
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12 scroll get_invitation_list">
                                                    @if($invitations_list)
                                                    @foreach ($invitations_list as $invite)
                                                    <div class="row" style="border-bottom: 1px solid #f0f0f0; line-height: 35px;">
                                                    <div class="col-md-6"><span>{{$invite['invitation_name']}} ({{$invite['invitation_email']}})</span></div>
                                                    <div class="col-md-3 pl-0">{{$invite['invitation_sent_date']}}</div>
                                                    <div class="col-md-3">{{$invite['invite_accept']}}</div>
                                                    </div>
                                                     @endforeach
                                                     @else
                                                        <div class="col-md-12"><span>No record found</span></div>
                                                    @endif

                                            </div>

                                        </div>
                                </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                    <button class="btn btn-primary submit_invitation" type="button">Send Invitation</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endif
                <div class="card collapse-header">
                    <div id="heading8" class="card-header" data-toggle="collapse" role="button" data-target="#accordion8"
                         aria-expanded="false" aria-controls="accordion8">
        <span class="collapse-title">
          <i class="bx bxs-cog align-middle"></i>
          <span class="align-middle">Theme Settings</span>
        </span>
                    </div>
                    <div id="accordion8" role="tabpanel" data-parent="#accordionWrapa2" aria-labelledby="heading8" class="collapse"
            aria-expanded="false">
           <form class="needs-validation" novalidate="" enctype="multipart/form-data"  id="ajaxformsavetheme">
             <div class="card-body">
                 <p style="color:green;" class="success_show_theme" id="overlay_theme_success"></p>
                 <p style="color:red;" class="error_show_theme" id="overlay_hide_theme"></p>


                 <div class="customizer-content mb-4">
                   <!-- Theme options starts -->
                   <h5 class="mt-1">Theme Layout</h5>
                   <div class="theme-layouts">
                       <div class="d-flex justify-content-start">
                           <div class="mx-50">
                               <fieldset>
                                   <div class="radio">
                                       <input type="radio" name="layoutOptions" value="light" id="radio-light"
                                              class="layout-name"  data-layout=""
                                              @if($configData['theme'] == "light")
                                              checked
                                               @endif>

                                       <label for="radio-light">Light</label>
                                   </div>
                               </fieldset>
                           </div>
                           <div class="mx-50">
                               <fieldset>
                                   <div class="radio">
                                       <input type="radio" name="layoutOptions" value="dark" id="radio-dark" class="layout-name"
                                              data-layout="dark-layout"
                                              @if($configData['theme'] == "dark")
                                              checked
                                               @endif>

                                       <label for="radio-dark">Dark</label>
                                   </div>
                               </fieldset>
                           </div>
                           <div class="mx-50">
                               <fieldset>
                                   <div class="radio">
                                       <input type="radio" name="layoutOptions" value="semi-dark" id="radio-semi-dark" class="layout-name"
                                              data-layout="semi-dark-layout"
                                              @if($configData['theme'] == "semi-dark")
                                              checked
                                               @endif>

                                       <label for="radio-semi-dark">Semi Dark</label>
                                   </div>
                               </fieldset>
                           </div>
                       </div>
                   </div>
               </div>
               <div class="row">
                   <div class="col-md-4">
                       <button class="btn btn-primary submit_theme" type="button">Update</button>
                   </div>
               </div>
               </div>
           </form>
       </div>
                </div>
            </div>
        </section>
    </section>
    <!-- collapse with icon end -->
@endsection
@section('vendor-scripts')
@endsection
  
  @section('page-scripts')
  <!--<script src="{{asset('js/scripts/pages/dashboard-ecommerce.js')}}"></script>
  <script src="{{asset('js/scripts/pages/dashboard-analytics.js')}}"></script>
  <script src="{{asset('js/scripts/cards/widgets.js')}}"></script>-->
  <script src="{{asset('js/scripts/custom.js')}}"></script>
  
  @endsection