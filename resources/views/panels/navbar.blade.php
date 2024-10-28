{{-- navabar  --}}
<div class="header-navbar-shadow"></div>
<nav class="header-navbar main-header-navbar navbar-expand-lg navbar navbar-with-menu
     @if(isset($configData['navbarType'])){{$configData['navbarClass']}} @endif" data-bgcolor="@if(isset($configData['navbarBgColor'])){{$configData['navbarBgColor']}}@endif">
    <div class="navbar-wrapper">
        <div class="navbar-container content">
            <div class="navbar-collapse" id="navbar-mobile">
                <div class="mr-auto float-left bookmark-wrapper d-flex align-items-center">
                    <ul class="nav navbar-nav">
                        <li class="nav-item mobile-menu d-xl-none mr-auto"><a class="nav-link nav-menu-main menu-toggle hidden-xs" href="#"><i class="ficon bx bx-menu"></i></a></li>
                    </ul>

                    <?php
                    if(stristr($_SERVER["REQUEST_URI"], '/topic-dashboard') !== FALSE)
                        echo '<h3>' . \Session::get('current_loaded_topic_name') . '</h3>';
                    else if(stristr($_SERVER["REQUEST_URI"], '/subtopic-dashboard') !== FALSE)
                        echo '<h3>' . \Session::get('current_loaded_topic_name') . ' - ' . \Session::get('current_loaded_subtopic_name') . '<span id="st_type_heading"></span></h3>';
                    else if(stristr($_SERVER["REQUEST_URI"], '/topic-settings') !== FALSE)
                        echo '<h3>Dashboards management</h3>';
                    else if(stristr($_SERVER["REQUEST_URI"], '/roi-settings') !== FALSE)
                        echo '<h3>ROI Settings</h3>';
                    else if(stristr($_SERVER["REQUEST_URI"], '/reports') !== FALSE)
                        echo '<h3>Dashboard reports</h3>';
                    else if(stristr($_SERVER["REQUEST_URI"], '/search') !== FALSE)
                        echo '<h3>Search dashboard results</h3>';
                    else if(stristr($_SERVER["REQUEST_URI"], '/my-account') !== FALSE)
                        echo '<h3>My account</h3>';
                    else if(stristr($_SERVER["REQUEST_URI"], '/source-handles') !== FALSE)
                        echo '<h3>Data sources</h3>';
                    else if(stristr($_SERVER["REQUEST_URI"], '/ca-settings') !== FALSE)
                        echo '<h3>Competitor analysis</h3>';
                    else if(stristr($_SERVER["REQUEST_URI"], '/dashboard-competitor-analysis') !== FALSE)
                        echo '<h3>Competitor analysis | '.\Session::get('_loaded_ca_name').'</h3>';
                    else if(stristr($_SERVER["REQUEST_URI"], '/customer-surveys') !== FALSE)
                        echo '<h3>Surveys</h3>';
                    else if(stristr($_SERVER["REQUEST_URI"], '/load-activity-log') !== FALSE)
                        echo '<h3>Account activity log</h3>';
                    else if(stristr($_SERVER["REQUEST_URI"], '/surveys-filter') !== FALSE)
                        echo '<h3>Filter survey stats</h3>';
                    else
                        echo '&nbsp;';
                    
                    /*if ($_SERVER["REQUEST_URI"] == '/topic-dashboard')
                        echo '<h3>' . \Session::get('current_loaded_topic_name') . '</h3>';
                    else if ($_SERVER["REQUEST_URI"] == '/subtopic-dashboard')
                        echo '<h3>' . \Session::get('current_loaded_topic_name') . ' - ' . \Session::get('current_loaded_subtopic_name') . '</h3>';
                    else
                        echo '&nbsp;';*/
                    ?>
                </div>
                <ul class="nav navbar-nav float-right">
                    <!--<li class="dropdown dropdown-language nav-item">
                      <a class="dropdown-toggle nav-link" id="dropdown-flag" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="flag-icon flag-icon-us"></i><span class="selected-language">English</span>
                      </a>
                      <div class="dropdown-menu" aria-labelledby="dropdown-flag">
                        <a class="dropdown-item" href="{{url('lang/en')}}" data-language="en">
                          <i class="flag-icon flag-icon-us mr-50"></i> English
                        </a>
                        <a class="dropdown-item" href="{{url('lang/fr')}}" data-language="fr">
                          <i class="flag-icon flag-icon-fr mr-50"></i> French
                        </a>
                        <a class="dropdown-item" href="{{url('lang/de')}}" data-language="de">
                          <i class="flag-icon flag-icon-de mr-50"></i> German
                        </a>
                        <a class="dropdown-item" href="{{url('lang/pt')}}" data-language="pt">
                          <i class="flag-icon flag-icon-pt mr-50"></i> Portuguese
                        </a>
                      </div>
                    </li>
                    <li class="nav-item d-none d-lg-block"><a class="nav-link nav-link-expand"><i class="ficon bx bx-fullscreen"></i></a></li>
                    <li class="nav-item nav-search"><a class="nav-link nav-link-search"><i class="ficon bx bx-search"></i></a>
                      <div class="search-input">
                        <div class="search-input-icon"><i class="bx bx-search primary"></i></div>
                        <input class="input" type="text" placeholder="Explore Frest..." tabindex="-1" data-search="template-search">
                        <div class="search-input-close"><i class="bx bx-x"></i></div>
                        <ul class="search-list"></ul>
                      </div>
                    </li>
                    <li class="dropdown dropdown-notification nav-item"><a class="nav-link nav-link-label" href="#" data-toggle="dropdown"><i class="ficon bx bx-bell bx-tada bx-flip-horizontal"></i><span class="badge badge-pill badge-danger badge-up">5</span></a>
                      <ul class="dropdown-menu dropdown-menu-media dropdown-menu-right">
                        <li class="dropdown-menu-header">
                          <div class="dropdown-header px-1 py-75 d-flex justify-content-between"><span class="notification-title">7 new Notification</span><span class="text-bold-400 cursor-pointer">Mark all as read</span></div>
                        </li>
                        <li class="scrollable-container media-list"><a class="d-flex justify-content-between" href="javascript:void(0)">
                            <div class="media d-flex align-items-center">
                              <div class="media-left pr-0">
                                <div class="avatar mr-1 m-0"><img src="{{asset('images/portrait/small/avatar-s-11.jpg')}}" alt="avatar" height="39" width="39"></div>
                              </div>
                              <div class="media-body">
                                <h6 class="media-heading"><span class="text-bold-500">Congratulate Socrates Itumay</span> for work anniversaries</h6><small class="notification-text">Mar 15 12:32pm</small>
                              </div>
                            </div></a>
                          <div class="d-flex justify-content-between read-notification cursor-pointer">
                            <div class="media d-flex align-items-center">
                              <div class="media-left pr-0">
                                <div class="avatar mr-1 m-0"><img src="{{asset('images/portrait/small/avatar-s-16.jpg')}}" alt="avatar" height="39" width="39"></div>
                              </div>
                              <div class="media-body">
                                <h6 class="media-heading"><span class="text-bold-500">New Message</span> received</h6><small class="notification-text">You have 18 unread messages</small>
                              </div>
                            </div>
                          </div>
                          <div class="d-flex justify-content-between cursor-pointer">
                            <div class="media d-flex align-items-center py-0">
                              <div class="media-left pr-0"><img class="mr-1" src="{{asset('images/icon/sketch-mac-icon.png')}}" alt="avatar" height="39" width="39"></div>
                              <div class="media-body">
                                <h6 class="media-heading"><span class="text-bold-500">Updates Available</span></h6><small class="notification-text">Sketch 50.2 is currently newly added</small>
                              </div>
                              <div class="media-right pl-0">
                                <div class="row border-left text-center">
                                  <div class="col-12 px-50 py-75 border-bottom">
                                    <h6 class="media-heading text-bold-500 mb-0">Update</h6>
                                  </div>
                                  <div class="col-12 px-50 py-75">
                                    <h6 class="media-heading mb-0">Close</h6>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                          <div class="d-flex justify-content-between cursor-pointer">
                            <div class="media d-flex align-items-center">
                              <div class="media-left pr-0">
                                <div class="avatar bg-primary bg-lighten-5 mr-1 m-0 p-25"><span class="avatar-content text-primary font-medium-2">LD</span></div>
                              </div>
                              <div class="media-body">
                                <h6 class="media-heading"><span class="text-bold-500">New customer</span> is registered</h6><small class="notification-text">1 hrs ago</small>
                              </div>
                            </div>
                          </div>
                          <div class="cursor-pointer">
                            <div class="media d-flex align-items-center justify-content-between">
                              <div class="media-left pr-0">
                                <div class="media-body">
                                  <h6 class="media-heading">New Offers</h6>
                                </div>
                              </div>
                              <div class="media-right">
                                <div class="custom-control custom-switch">
                                  <input class="custom-control-input" type="checkbox" checked id="notificationSwtich">
                                  <label class="custom-control-label" for="notificationSwtich"></label>
                                </div>
                              </div>
                            </div>
                          </div>
                          <div class="d-flex justify-content-between cursor-pointer">
                            <div class="media d-flex align-items-center">
                              <div class="media-left pr-0">
                                <div class="avatar bg-danger bg-lighten-5 mr-1 m-0 p-25"><span class="avatar-content"><i class="bx bxs-heart text-danger"></i></span></div>
                              </div>
                              <div class="media-body">
                                <h6 class="media-heading"><span class="text-bold-500">Application</span> has been approved</h6><small class="notification-text">6 hrs ago</small>
                              </div>
                            </div>
                          </div>
                          <div class="d-flex justify-content-between read-notification cursor-pointer">
                            <div class="media d-flex align-items-center">
                              <div class="media-left pr-0">
                                <div class="avatar mr-1 m-0"><img src="{{asset('images/portrait/small/avatar-s-4.jpg')}}" alt="avatar" height="39" width="39"></div>
                              </div>
                              <div class="media-body">
                                <h6 class="media-heading"><span class="text-bold-500">New file</span> has been uploaded</h6><small class="notification-text">4 hrs ago</small>
                              </div>
                            </div>
                          </div>
                          <div class="d-flex justify-content-between cursor-pointer">
                            <div class="media d-flex align-items-center">
                              <div class="media-left pr-0">
                                <div class="avatar bg-rgba-danger m-0 mr-1 p-25">
                                  <div class="avatar-content"><i class="bx bx-detail text-danger"></i></div>
                                </div>
                              </div>
                              <div class="media-body">
                                <h6 class="media-heading"><span class="text-bold-500">Finance report</span> has been generated</h6><small class="notification-text">25 hrs ago</small>
                              </div>
                            </div>
                          </div>
                          <div class="d-flex justify-content-between cursor-pointer">
                            <div class="media d-flex align-items-center border-0">
                              <div class="media-left pr-0">
                                <div class="avatar mr-1 m-0"><img src="{{asset('images/portrait/small/avatar-s-16.jpg')}}" alt="avatar" height="39" width="39"></div>
                              </div>
                              <div class="media-body">
                                <h6 class="media-heading"><span class="text-bold-500">New customer</span> comment recieved</h6><small class="notification-text">2 days ago</small>
                              </div>
                            </div>
                          </div>
                        </li>
                        <li class="dropdown-menu-footer"><a class="dropdown-item p-50 text-primary justify-content-center" href="javascript:void(0)">Read all notifications</a></li>
                      </ul>
                    </li>-->
                    
                    <!--<li class="dropdown dropdown-user nav-item">
                        <a class="dropdown-toggle nav-link dropdown-user-link" href="#" data-toggle="dropdown" style="padding-right:0px;">
                            <div class="user-nav d-sm-flex d-none">
                                <span class="user-name">&nbsp;</span>
                                <span class="user-status text-muted">&nbsp;</span>
                            </div>
                            <span><i class="bx bxs-palette mr-50" style="font-size: 2.5rem; color: lightblue;"></i></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right pb-0">
                            <div style="text-align: center; text-transform: uppercase; font-weight: 500;">Select theme</div>
                            <div class="dropdown-divider mb-0"></div>
                            <div class="radio" style="padding: 10px 0px 0px 10px;">
                                <input type="radio" name="layoutOptions" value="light" id="radio-light" class="layout-name submit_theme" data-layout="light-layout" @if($configData['theme'] == "light") checked @endif>
                                <label for="radio-light" style="font-size:0.8rem; font-weight: normal;">Light</label>
                            </div>
                            
                            <div class="radio" style="padding: 10px 0px 0px 10px;">
                                <input type="radio" name="layoutOptions" value="dark" id="radio-dark" class="layout-name submit_theme" data-layout="dark-layout" @if($configData['theme'] == "dark") checked @endif>
                                <label for="radio-dark" style="font-size:0.8rem; font-weight: normal;">Dark</label>
                            </div>
                            
                            <div class="dropdown-divider mb-0"></div>
                        </div>
                    </li>-->
                    
                    <li class="dropdown dropdown-user nav-item">
                        <a class="dropdown-toggle nav-link dropdown-user-link" href="#" data-toggle="dropdown">
                            <div class="user-nav d-sm-flex d-none">
                                <span class="user-name">&nbsp;</span>
                                <span class="user-status text-muted">&nbsp;</span>
                            </div>
                            <span><i class="bx bx-user-circle mr-50" style="font-size: 2.5rem;"></i></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right pb-0">
                            <?php
                            //$parent_user_id = Helper::get_parent_account_id();

                            if(\Session::get('_loggedin_customer_id') != 292) {
                            ?>
                            <a class="dropdown-item" href="/my-account">
                              <i class="bx bx-user mr-50"></i> My Account
                            </a>
                            <div class="dropdown-divider mb-0"></div>
                            <?php } ?>
                            <a class="dropdown-item" href="{{asset('logout-user')}}"><i class="bx bx-power-off mr-50"></i> Logout</a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>
<!-- Popup posts loader -->
<div id="popup_posts_container" class="card" style="display: none; width: 27%; position: fixed; z-index: 1052; right: 0px; top: 0px; bottom: 0px; height: 100vh; -webkit-transition: right .4s cubic-bezier(.05, .74, .2, .99); transition: right .4s cubic-bezier(.05, .74, .2, .99); backface-visibility: hidden; border-left: 1px solid rgba(0, 0, 0, .05); box-shadow: 0 15px 30px 0 rgb(0 0 0/ 11%), 0 5px 15px 0 rgb(0 0 0/ 8%); padding: 0px; overflow:hidden;">
    <div class="row-fluid">
        <div style="text-align: right;">
            <a onclick="javascript:load_posts('', '', '', 'close');"><i class="bx bx-x" style="font-size: 2rem; cursor: pointer;"></i></a>
        </div>
        <div id="posts_listing_heading_container" class="col-sm-12" style="padding-top: 10px;">
            <div>
                <h4 id="posts_source_heading"></h4>
            </div>
            
        </div>
        <div id="posts_listings" class="col-sm-12" style="padding:0px;">
            <div id="" class="hide-native-scrollbar" style="height: 90vh; overflow-x: hidden; overflow-y: auto; padding:0px 18px 60px 18px;">
                <div id="insights_container" style="display:none;"></div>
                <div id="channels_container" style="display:none;"></div>
                <div id="posts_container"></div>
            </div>
        	<div id="loadmore_link" class="col-sm-12" style="padding:0px; height: 100px;">
            	{{--Load more link will come here--}}&nbsp;
            </div>
        </div>
        
    </div>
</div>

<!-- Delete record popup -->
<div class="modal fade text-left" id="delete_modal" tabindex="-1" aria-labelledby="delete-modal-popup" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title white" id="myModalLabel120">Confirmation required!</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="bx bx-x"></i>
                </button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this record? 
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light-secondary" data-dismiss="modal">
                    <i class="bx bx-x d-block d-sm-none"></i>
                    <span class="d-none d-sm-block">Close</span>
                </button>
                <button type="button" class="btn btn-danger ml-1" data-dismiss="modal" onclick="javascript:proceed_delete();">
                    <i class="bx bx-check d-block d-sm-none"></i>
                    <span class="d-none d-sm-block">Confirm</span>
                </button>
                <input type="hidden" name="_delete_record_id" id="_delete_record_id">
                <input type="hidden" name="_delete_section" id="_delete_section">
                <input type="hidden" name="_delete_token" id="_delete_token">
            </div>
        </div>
    </div>
</div>

<!--Basic Modal Post comments -->
<div class="modal fade text-left" id="comments_modal" tabindex="-1" role="dialog" aria-labelledby="comments_modal_label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="myModalLabel1">Post comments</h3>
                <button type="button" class="close rounded-pill" data-dismiss="modal" aria-label="Close">
                    <i class="bx bx-x"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="post_comments_senti_chart" style="">
                  <div id="post_comments_chart" class="d-flex justify-content-center"></div>
                </div>
                <div id="post_comments_data">...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light-secondary" data-dismiss="modal">
                    <i class="bx bx-x d-block d-sm-none"></i>
                    <span class="d-none d-sm-block">Close</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!--Basic Modal Email analysis -->
<div class="modal fade text-left" id="email_analysis_modal" tabindex="-1" role="dialog" aria-labelledby="email_analysis_modal_label" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="ea_label">Email analysis</h3>
                <button type="button" class="close rounded-pill" data-dismiss="modal" aria-label="Close">
                    <i class="bx bx-x"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="email_analysis_data">Loading ...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light-secondary" data-dismiss="modal">
                    <i class="bx bx-x d-block d-sm-none"></i>
                    <span class="d-none d-sm-block">Close</span>
                </button>
            </div>
        </div>
    </div>
</div>