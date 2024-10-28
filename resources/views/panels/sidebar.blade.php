{{-- vertical-menu --}} @if($configData['mainLayoutType'] == 'vertical-menu')
<?php
$loggedin_user_id = \Session::get('_loggedin_customer_id');
$parent_user_id = Helper::get_parent_account_id();

if(isset($loggedin_user_id) && !empty($loggedin_user_id))
{
?>
<div class="main-menu menu-fixed @if($configData['theme'] === 'light') {{" menu-light"}} @else {{'menu-dark'}} @endif menu-accordion menu-shadow" data-scroll-to-active="true">
    <div class="navbar-header">
        <ul class="nav navbar-nav flex-row">
            <li class="nav-item mr-auto">
                <?php if($loggedin_user_id == 292) { ?>
		    <a class="navbar-brand" href="{{asset('/customer-surveys')}}">
                <?php } else { ?>
                    <a class="navbar-brand" href="{{asset('/topic-settings')}}">
                <?php } ?>
                
                    <div class="brand-logo">
                        <?php
                        if ($loggedin_user_id == 308) {
                        ?>
                            <img src="{{asset('images/logo/rk-logo.png')}}" style="width: 110px; height: auto;" class="logo" alt="">
                        <?php } else if ($loggedin_user_id == 309 || $loggedin_user_id == 310) { ?>
                            <img src="{{asset('images/logo/datalyticx-eand-logo.png')}}" style="width: 215px; height: auto;" class="logo" alt="">
                        <?php } else if ($loggedin_user_id == 309 || $loggedin_user_id == 411) { ?>
                            <img src="{{asset('images/logo/adafsa-eand-logo.png')}}" style="width: 215px; height: auto;" class="logo" alt="">
                        <?php } else if ($loggedin_user_id == 412) { ?>
                        <img src="{{asset('images/logo/ncema-eand-logo.png')}}" style="width: 215px; height: auto;" class="logo" alt="">
                        <?php } else if ($loggedin_user_id == 415) { ?>
                        <img src="{{asset('images/logo/icp-eand-logo.png')}}" style="width: 215px; height: auto;" class="logo" alt="">
                        <?php } else { ?>
                            <img src="{{asset('images/logo/logo.png')}}" style="width: 205px; height: auto;" class="logo" alt="">
                        <?php } ?>
                    </div>
                    <h2 class="brand-text mb-0">{{-- @if(!empty($configData['templateTitle']) && isset($configData['templateTitle'])) {{$configData['templateTitle']}} @else Frest @endif --}}</h2>
                </a></li>

        </ul>
    </div>
    <div class="shadow-bottom"></div>
    <?php
    
    //dd('hi: '.$loggedin_user_id);
    if (isset($loggedin_user_id) && !empty($loggedin_user_id) && $loggedin_user_id != '001') {
        ?>
        <div class="main-menu-content">
            <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation" data-icon-style="lines">
                <input type="hidden" class="dashboards_value" value="<?php if (stristr($_SERVER["REQUEST_URI"], '/topic-dashboard') !== FALSE || stristr($_SERVER["REQUEST_URI"], '/subtopic-dashboard') !== FALSE) echo '1';
    else echo '0'; ?>">
                <li id="dash_li" class="nav-item dashboards has-sub<?php if (stristr($_SERVER["REQUEST_URI"], '/topic-dashboard') !== FALSE) echo ' sidebar-group-active open'; ?>"<?php  if($parent_user_id == 292) echo ' style="display:none;"'; ?>><a href="#"> <i class="menu-livicon livicon-evo-holder" data-icon="desktop" style="visibility: visible; width: 60px;"></i> <span class="menu-title text-truncate">Dashboards</span>
                    </a>
                    <ul class="menu-content">
                        <?php
                        $topic_session = \Session::get('current_loaded_project');
                        $subtopic_session_id = \Session::get('current_loaded_subtopic');

                        $t_data = DB::select("SELECT topic_id, topic_title FROM customer_topics WHERE customer_portal = 'D24' AND topic_is_deleted != 'Y' AND topic_user_id = " . $parent_user_id . " ORDER BY topic_order ASC");
                        for ($i = 0; $i < count($t_data); $i++) {
                            //Check for sub topics / customer experience
                            $li_classes = '';
                            //$exp_data = DB::select("SELECT exp_id, exp_name FROM customer_experience WHERE exp_uid = " . $loggedin_user_id . " AND exp_topic_id = " . $t_data[$i]->topic_id);
                            $chkq = DB::select("SELECT customer_subtopics_access FROM customers WHERE customer_id = " . \Session::get('_loggedin_customer_id'));

                            if (isset($chkq[0]->customer_subtopics_access) && !is_null($chkq[0]->customer_subtopics_access) && !empty($chkq[0]->customer_subtopics_access))
                                $exp_data = DB::select("SELECT exp_id, exp_name FROM customer_experience WHERE exp_id IN (" . $chkq[0]->customer_subtopics_access . ") AND exp_topic_id = " . $t_data[$i]->topic_id);
                            else
                                $exp_data = DB::select("SELECT exp_id, exp_name FROM customer_experience WHERE exp_topic_id = " . $t_data[$i]->topic_id);

                            if (count($exp_data) == 0) {
                                if ($t_data[$i]->topic_id == $topic_session && stristr($_SERVER["REQUEST_URI"], '/topic-dashboard') !== FALSE)
                                    $li_classes .= ' active';
                                ?>
                                <li class="<?php echo $li_classes; ?>">
                                    <a href="javascript:void(0);" onclick="javascript:load_topic('<?php echo $t_data[$i]->topic_id; ?>', '{{ csrf_token() }}', 'maintopic');" class="d-flex align-items-center" style="font-weight: bold;"> <i class="bx bx-right-arrow-alt"></i> <span class="menu-item text-truncate"><?php echo $t_data[$i]->topic_title; ?></span>
                                    </a>
            <?php
        } else {
            if ($t_data[$i]->topic_id == $topic_session && stristr($_SERVER["REQUEST_URI"], '/topic-dashboard') !== FALSE)
                $li_classes = ' open active';
            ?>
                                <li class="<?php echo $li_classes; ?>">
                                    <a href="javascript:void(0);" onclick="" class="d-flex align-items-center" style="font-weight: bold;"> <i class="bx bx-right-arrow-alt"></i> <span class="menu-item text-truncate"><?php echo $t_data[$i]->topic_title; ?></span>
                                    </a>
            <?php
        }
        ?>
        <!-- <li class="<?php //echo $li_classes;   ?>">
        <a href="javascript:void(0);" onclick="<?php //echo $on_click;  ?>" class="d-flex align-items-center" style="font-weight: bold;"> <i class="bx bx-right-arrow-alt"></i> <span class="menu-item text-truncate"><?php //echo $t_data[$i]->topic_title;  ?></span>
        </a> -->
        <?php
        if (count($exp_data) > 0) {
            ?>
                                    <ul class="menu-content">
                                    <?php
                                    for ($j = 0; $j < count($exp_data); $j++) {
                                        if ($j == 0 && $exp_data > 0) {
                                            ?>
                                                <li class="<?php echo $li_classes; ?>"><a href="javascript:void(0);" onclick="javascript:load_topic('<?php echo $t_data[$i]->topic_id; ?>', '{{ csrf_token() }}', 'maintopic');" class="d-flex align-items-center" style="font-weight: bold;"> <i class="bx bx-right-arrow-alt"></i> <span class="menu-item text-truncate"><?php echo $t_data[$i]->topic_title; ?></span></a></li>
                                                <?php
                                            }
                                            if ($exp_data[$j]->exp_id == $subtopic_session_id && stristr($_SERVER["REQUEST_URI"], '/subtopic-dashboard') !== FALSE)
                                                $li_classes = ' active';
                                            else
                                                $li_classes = '';
                                            ?>
                                            <li class="<?php echo $li_classes; ?>"><a href="javascript:void(0);" onclick="javascript:load_topic('<?php echo $exp_data[$j]->exp_id; ?>', '{{ csrf_token() }}', 'subtopic');" class="d-flex align-items-center"> <i class="bx bx-right-arrow-alt"></i> <span class="menu-item text-truncate"><?php echo $exp_data[$j]->exp_name; ?></span></a></li>
                                            <?php
                                        }
                                        ?>
                                    </ul>
                                        <?php
                                    }
                                    ?>
                            </li>
                                <?php
                            }
                            ?>
                    </ul>
                </li>
                <?php // || stristr($_SERVER["REQUEST_URI"], '/ca-settings') !== FALSE || stristr($_SERVER["REQUEST_URI"], '/dashboard-competitor-analysis') !== FALSE ?>
                <li class="nav-item settings has-sub<?php if (stristr($_SERVER["REQUEST_URI"], '/topic-settings') !== FALSE) echo ' sidebar-group-active open'; ?>"<?php  if($parent_user_id == 292) echo ' style="display:none;"'; ?>>
                    <a href="# "> <i class="menu-livicon livicon-evo-holder" data-icon="settings" style="visibility: visible; width: 60px;"><div class="lievo-svg-wrapper"></div></i> <span class="menu-title text-truncate">Settings</span></a>
                    <ul class="menu-content">
                        <li<?php if (stristr($_SERVER["REQUEST_URI"], '/topic-settings') !== FALSE) echo ' class="active"'; ?>><a href="/topic-settings" class="d-flex align-items-center"> <i class="bx bx-right-arrow-alt"></i> <span class="menu-item text-truncate">Dashboards</span>
                            </a></li>
                        <li<?php if (stristr($_SERVER["REQUEST_URI"], '/source-handles') !== FALSE) echo ' class="active"'; ?>><a href="/source-handles" class="d-flex align-items-center"> <i class="bx bx-right-arrow-alt"></i> <span class="menu-item text-truncate">Data sources</span>
                            </a></li>
                        <!--<li<?php //if (stristr($_SERVER["REQUEST_URI"], '/ca-settings') !== FALSE || stristr($_SERVER["REQUEST_URI"], '/dashboard-competitor-analysis') !== FALSE) echo ' class="active"'; ?>><a href="/ca-settings" class="d-flex align-items-center"> <i class="bx bx-right-arrow-alt"></i> <span class="menu-item text-truncate">Competitor analysis</span>
                            </a></li>-->
    <?php if ($loggedin_user_id == $parent_user_id || $loggedin_user_id == 301) { ?>    
                            <li<?php if (stristr($_SERVER["REQUEST_URI"], '/load-activity-log') !== FALSE) echo ' class="active"'; ?>><a href="/load-activity-log" class="d-flex align-items-center"> <i class="bx bx-right-arrow-alt"></i> <span class="menu-item text-truncate">Activity log</span>
                                </a></li>
    <?php } ?>
                    </ul>
                </li>
                <?php if($parent_user_id != 292) { ?>
                <li class="nav-itemi<?php if (stristr($_SERVER["REQUEST_URI"], '/dashboard-competitor-analysis') !== FALSE || stristr($_SERVER["REQUEST_URI"], '/ca-settings') !== FALSE) echo ' active'; ?>">
                    <a href="{{ route('ca-settings') }}">
                        <i class="menu-livicon grid" data-icon="grid"></i>
                        <span class="menu-title text-truncate">Competitor analysis</span>
                    </a>
                   </li>
		<?php } ?>
<?php if($parent_user_id != 292) { ?>
                <li class="nav-itemi<?php if (stristr($_SERVER["REQUEST_URI"], '/dashboard-mentions-summary') !== FALSE || stristr($_SERVER["REQUEST_URI"], '/ms-settings') !== FALSE) echo ' active'; ?>">
                    <a href="{{ route('ms-settings') }}">
                        <i class="menu-livicon grid" data-icon="grid"></i>
                        <span class="menu-title text-truncate">Mentions Summary</span>
                    </a>
                </li>
                <?php } ?>
                <li class="nav-item reports has-sub<?php if (stristr($_SERVER["REQUEST_URI"], '/reports') !== FALSE) echo ' sidebar-group-active open'; ?>"<?php  if($parent_user_id == 292) echo ' style="display:none;"'; ?>>
                    <a href="# "> <i class="menu-livicon livicon-evo-holder" data-icon="notebook" style="visibility: visible; width: 60px;"><div class="lievo-svg-wrapper"></div></i> <span class="menu-title text-truncate">Reports</span></a>
                    <ul class="menu-content">
                        <li<?php if (stristr($_SERVER["REQUEST_URI"], '/reports') !== FALSE) echo ' class="active"'; ?>><a href="/reports " class="d-flex align-items-center"> <i class="bx bx-right-arrow-alt"></i> <span class="menu-item text-truncate">Reports listing</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item alerts has-sub<?php if (stristr($_SERVER["REQUEST_URI"], '/alerts') !== FALSE) echo ' sidebar-group-active'; ?>"<?php  if($parent_user_id == 292) echo ' style="display:none;"'; ?>>
                    <a href="# "> <i class="menu-livicon alarm" data-icon="bell" style="visibility: visible; width: 60px;"><div class="lievo-svg-wrapper"></div></i> <span class="menu-title text-truncate">Alerts</span></a>
                    <ul class="menu-content">
                        <li @if(Route::currentRouteName() == 'alert.sindex' || Route::currentRouteName() == 'detail') class="active" @endif><a href="{{ route('alert.sindex') }}" class="d-flex align-items-center"> <i class="bx bx-right-arrow-alt"></i> <span class="menu-item text-truncate">Custom Alerts</span>
                            </a>
                        </li>
                        <li @if(Route::currentRouteName() == 'spike.index' || Route::currentRouteName() == 'spike.detail') class="active" @endif><a href="{{ route('spike.index') }}" class="d-flex align-items-center"> <i class="bx bx-right-arrow-alt"></i> <span class="menu-item text-truncate">Spike Alerts</span>
                            </a>
                        </li>

                    </ul>
                </li>
                <?php if ($loggedin_user_id == 421 || $loggedin_user_id == 264 || $loggedin_user_id == 292 || $loggedin_user_id == 424 || $loggedin_user_id == 301 || $loggedin_user_id == 305 || $loggedin_user_id == 313 || $loggedin_user_id == 314 || $loggedin_user_id == 415 || $loggedin_user_id == 416 || $loggedin_user_id == 411 || $loggedin_user_id == 418 || $loggedin_user_id == 309 || $loggedin_user_id == 419 || $loggedin_user_id == 420) { //afzaal,demo, sohar  ?>
                    <?php if($loggedin_user_id == 292) { ?>
                    <li class="nav-item alerts has-sub<?php if (stristr($_SERVER["REQUEST_URI"], '/customer-surveys') !== FALSE) echo ' sidebar-group-active'; ?>">
                        <a href="# "> <i class="menu-livicon livicon-evo-holder" data-icon="grid" style="visibility: visible; width: 60px;"><div class="lievo-svg-wrapper"></div></i> <span class="menu-title text-truncate">Surveys</span></a>
                        <ul class="menu-content">
                            <li<?php if (stristr($_SERVER["REQUEST_URI"], '/customer-surveys') !== FALSE && (isset($_GET["stype"]) && base64_decode($_GET["stype"]) == 1)) echo ' class="active"'; ?>><a href="/customer-surveys?stype=<?php echo base64_encode(1); ?>" class="d-flex align-items-center"> <i class="bx bx-right-arrow-alt"></i> <span class="menu-item text-truncate">Conventional</span>
                                </a>
                            </li>
                            <li<?php if (stristr($_SERVER["REQUEST_URI"], '/customer-surveys') !== FALSE && (isset($_GET["stype"]) && base64_decode($_GET["stype"]) == 2)) echo ' class="active"'; ?>><a href="/customer-surveys?stype=<?php echo base64_encode(2); ?>" class="d-flex align-items-center"> <i class="bx bx-right-arrow-alt"></i> <span class="menu-item text-truncate">Islamic</span>
                                </a>
                            </li>
                            <li<?php if (stristr($_SERVER["REQUEST_URI"], '/customer-surveys') !== FALSE && (isset($_GET["lt"]) && $_GET["lt"] == 'y')) echo ' class="active"'; ?>><a href="/customer-surveys?lt=y" class="d-flex align-items-center"> <i class="bx bx-right-arrow-alt"></i> <span class="menu-item text-truncate">Survey templates</span>
                                </a>
                            </li>
                            <li<?php if (stristr($_SERVER["REQUEST_URI"], '/customer-surveys') !== FALSE && (isset($_GET["ld"]) && $_GET["ld"] == 'y')) echo ' class="active"'; ?>><a href="/customer-surveys?ld=y" class="d-flex align-items-center"> <i class="bx bx-right-arrow-alt"></i> <span class="menu-item text-truncate">Survey dashboard</span>
                                </a>
                            </li>
                            <li<?php if (stristr($_SERVER["REQUEST_URI"], '/customer-surveys') !== FALSE && (isset($_GET["lrd"]) && $_GET["lrd"] == 'y')) echo ' class="active"'; ?>><a href="/customer-surveys?lrd=y" class="d-flex align-items-center"> <i class="bx bx-right-arrow-alt"></i> <span class="menu-item text-truncate">Regional Stats</span>
                                </a>
                            </li>
                        </ul>
		    </li>
                    <?php } else if ($loggedin_user_id == 421) { ?>
                    <li class="nav-item alerts has-sub<?php if (stristr($_SERVER["REQUEST_URI"], '/customer-surveys') !== FALSE) echo ' sidebar-group-active'; ?>">
                        <a href="# "> <i class="menu-livicon livicon-evo-holder" data-icon="grid" style="visibility: visible; width: 60px;"><div class="lievo-svg-wrapper"></div></i> <span class="menu-title text-truncate">Surveys</span></a>
                        <ul class="menu-content">
                            <li<?php if (stristr($_SERVER["REQUEST_URI"], '/customer-surveys') !== FALSE && (isset($_GET["lrd"]) && $_GET["lrd"] == 'y')) echo ' class="active"'; ?>><a href="/customer-surveys?lrd=y" class="d-flex align-items-center"> <i class="bx bx-right-arrow-alt"></i> <span class="menu-item text-truncate">Regional Stats</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php } else { ?>
                    <li class="nav-item alerts has-sub<?php if (stristr($_SERVER["REQUEST_URI"], '/customer-surveys') !== FALSE) echo ' sidebar-group-active'; ?>">
                        <a href="# "> <i class="menu-livicon livicon-evo-holder" data-icon="grid" style="visibility: visible; width: 60px;"><div class="lievo-svg-wrapper"></div></i> <span class="menu-title text-truncate">Surveys</span></a>
                        <ul class="menu-content">
                            <li<?php if (stristr($_SERVER["REQUEST_URI"], '/customer-surveys') !== FALSE && !isset($_GET["lt"]) && !isset($_GET["ld"]) && !isset($_GET["lrd"])) echo ' class="active"'; ?>><a href="/customer-surveys " class="d-flex align-items-center"> <i class="bx bx-right-arrow-alt"></i> <span class="menu-item text-truncate">My Surveys</span>
                                </a>
                            </li>
                            <li<?php if (stristr($_SERVER["REQUEST_URI"], '/customer-surveys') !== FALSE && (isset($_GET["lt"]) && $_GET["lt"] == 'y')) echo ' class="active"'; ?>><a href="/customer-surveys?lt=y" class="d-flex align-items-center"> <i class="bx bx-right-arrow-alt"></i> <span class="menu-item text-truncate">Survey templates</span>
                                </a>
                            </li>
                            
                        </ul>
                    </li>
                    <?php } ?>    
                    

                    <?php if ($loggedin_user_id == 424 || $loggedin_user_id == 264 || $loggedin_user_id == 416 || $loggedin_user_id == 411 || $loggedin_user_id == 418 || $loggedin_user_id == 309 || $loggedin_user_id == 420) { ?>
                        <li class="nav-itemi">
                            <a href="https://engage.datalyticx.ai/platforms/dashboard" target="_blank">
                                <i class="menu-livicon search" data-icon="adjust"></i>
                                <span class="menu-title text-truncate">Engage</span>
                            </a>
                        </li>

                        <li class="nav-itemi">
                            <a href="http://voice.datalyticx.ai/" target="_blank">
                                <i class="menu-livicon search" data-icon="circle"></i>
                                <span class="menu-title text-truncate">Voice Analytics</span>
                            </a>
                        </li>
                    <?php } ?>
                <?php } ?>
                
                <?php if($loggedin_user_id == 418 || $loggedin_user_id == 309 || $loggedin_user_id == 420 || $loggedin_user_id == 424) { ?>
                <li class="nav-itemi<?php if (stristr($_SERVER["REQUEST_URI"], '/emails-analysis') !== FALSE) echo ' active'; ?>">
                    <a href="{{ route('emails-analysis-list') }}">
                        <i class="menu-livicon search" data-icon="envelope-pull"></i>
                        <span class="menu-title text-truncate">Emails Analysis</span>
                    </a>
                </li>
                <li class="nav-itemi<?php if (stristr($_SERVER["REQUEST_URI"], '/chatbot') !== FALSE) echo ' active'; ?>">
                    <a href="{{ route('chatbot') }}">
                        <i class="menu-livicon comment" data-icon="comment"></i>
                        <span class="menu-title text-truncate">Chatbot</span>
                    </a>
                </li>
                <?php } ?>

                <?php if($loggedin_user_id == 418) { ?>
                <li class="nav-itemi<?php if (stristr($_SERVER["REQUEST_URI"], '/emails-analysis') !== FALSE) echo ' active'; ?>">
                    <a href="{{ route('emails-analysis-list') }}">
                        <i class="menu-livicon search" data-icon="envelope-pull"></i>
                        <span class="menu-title text-truncate">Emails Analysis</span>
                    </a>
                </li>
                <li class="nav-itemi">
                    <a href="http://voice.datalyticx.ai/" target="_blank">
                        <i class="menu-livicon search" data-icon="circle"></i>
                        <span class="menu-title text-truncate">Voice Analytics</span>
                    </a>
                </li>
                <?php } ?>

                <li class="nav-itemi<?php if (stristr($_SERVER["REQUEST_URI"], '/search') !== FALSE) echo ' active'; ?>"<?php  if($parent_user_id == 292) echo ' style="display:none;"'; ?>>
                    <a href="{{ route('search') }}">
                        <i class="menu-livicon search" data-icon="search"></i>
                        <span class="menu-title text-truncate">Search</span>
                    </a>
                </li>

            </ul>
        </div>
<?php } ?>
</div>
<?php
}
?>


@endif {{-- horizontal-menu --}} @if($configData['mainLayoutType'] == 'horizontal-menu')
<div class="header-navbar navbar-expand-sm navbar navbar-horizontal navbar-light navbar-without-dd-arrow
     @if($configData['navbarType'] === 'navbar-static') {{'navbar-sticky'}} @endif" role="navigation" data-menu="menu-wrapper">
    <div class="navbar-header d-xl-none d-block">
        <ul class="nav navbar-nav flex-row">
            <li class="nav-item mr-auto"><a class="navbar-brand" href="{{asset('/')}}">
                    <div class="brand-logo">
                        <img src="{{asset('images/logo/logo.png')}}" class="logo" alt="">
                    </div>
                    <h2 class="brand-text mb-0">@if(!empty($configData['templateTitle']) && isset($configData['templateTitle'])) {{$configData['templateTitle']}} @else Frest @endif</h2>
                </a></li>
            <li class="nav-item nav-toggle"><a class="nav-link modern-nav-toggle pr-0" data-toggle="collapse"> <i class="bx bx-x d-block d-xl-none font-medium-4 primary toggle-icon"></i>
                </a></li>
        </ul>
    </div>
    <div class="shadow-bottom"></div>
    <!-- Horizontal menu content-->
    <div class="navbar-container main-menu-content" data-menu="menu-container">
        <ul class="nav navbar-nav" id="main-menu-navigation" data-menu="menu-navigation" data-icon-style="filled">
            @if(!empty($menuData[1]) && isset($menuData[1])) @foreach ($menuData[1]->menu as $menu)
            <li class="@if(isset($menu->submenu)){{'dropdown'}} @endif nav-item" data-menu="dropdown"><a class="@if(isset($menu->submenu)){{'dropdown-toggle'}} @endif nav-link" href="{{asset($menu->url)}}" @if(isset($menu->submenu)){{'data-toggle=dropdown'}} @endif @if(isset($menu->newTab)){{"target=_blank"}}@endif> <i class="menu-livicon" data-icon="{{$menu->icon}}"></i> <span>{{ __('locale.'.$menu->name)}}</span>
                </a> @if(isset($menu->submenu)) @include('panels.sidebar-submenu',['menu'=>$menu->submenu]) @endif</li> @endforeach @endif
        </ul>
    </div>
    <!-- /horizontal menu content-->
</div>
@endif {{-- vertical-box-menu --}} @if($configData['mainLayoutType'] == 'vertical-menu-boxicons')
<div class="main-menu menu-fixed @if($configData['theme'] === 'light') {{" menu-light"}} @else {{'menu-dark'}} @endif menu-accordion menu-shadow" data-scroll-to-active="true">
    <div class="navbar-header">
        <ul class="nav navbar-nav flex-row">
            <li class="nav-item mr-auto"><a class="navbar-brand" href="{{asset('/')}}">
                    <div class="brand-logo">
                        <img src="{{asset('images/logo/logo.png')}}" class="logo" alt="">
                    </div>
                    <h2 class="brand-text mb-0">@if(!empty($configData['templateTitle']) && isset($configData['templateTitle'])) {{$configData['templateTitle']}} @else Frest @endif</h2>
                </a></li>
            <li class="nav-item nav-toggle"><a class="nav-link modern-nav-toggle pr-0" data-toggle="collapse"><i class="bx bx-x d-block d-xl-none font-medium-4 primary toggle-icon"></i><i class="toggle-icon bx bx-disc font-medium-4 d-none d-xl-block collapse-toggle-icon primary" data-ticon="bx-disc"></i></a></li>
        </ul>
    </div>
    <div class="shadow-bottom"></div>
    <div class="main-menu-content">
        <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation" data-icon-style="">
            @if(!empty($menuData[2]) && isset($menuData[2])) @foreach ($menuData[2]->menu as $menu) @if(isset($menu->navheader))
            <li class="navigation-header text-truncate"><span>{{$menu->navheader}}</span></li> @else
            <li class="nav-item {{ Route::currentRouteName() === $menu->slug ? 'active' : '' }}"><a href="@if(isset($menu->url)){{asset($menu->url)}} @endif" @if(isset($menu->newTab)){{"target=_blank"}}@endif> @if(isset($menu->icon)) <i class="{{$menu->icon}}"></i> @endif @if(isset($menu->name)) <span class="menu-title text-truncate">{{ __('locale.'.$menu->name)}}</span> @endif @if(isset($menu->tag)) <span class="{{$menu->tagcustom}} ml-auto">{{$menu->tag}}</span> @endif
                </a> @if(isset($menu->submenu)) @include('panels.sidebar-submenu',['menu' => $menu->submenu]) @endif</li> @endif @endforeach @endif
        </ul>
    </div>
</div>
@endif
<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
<script type="text/javascript">
                                $(document).ready(function(){

                                $(".reports").click(function(){

                                if ($('.dashboards_value').val() == 1){
                                setTimeout(function(){
                                if ($(".dashboards").find("open") != ''){
                                $(".dashboards").addClass("open");
                                }
                                }, 1);
                                }
                                });
                                $(".settings").click(function(){

                                if ($('.dashboards_value').val() == 1){
                                setTimeout(function(){
                                if ($(".dashboards").find("open") != '') {
                                $(".dashboards").addClass("open");
                                }
                                }, 1);
                                }
                                });
                                $(".alerts").click(function(){
                                if ($('.dashboards_value').val() == 1){
                                setTimeout(function(){
                                if ($(".dashboards").find("open") != '') {
                                $(".dashboards").addClass("open");
                                }
                                }, 1);
                                }


                                });
                                $(".dashboards").click(function(){
                                if ($('.dashboards_value').val() == 0)
                                        $('.dashboards_value').val(1);
                                else
                                        $('.dashboards_value').val(0);
                                });
                                });
</script>
