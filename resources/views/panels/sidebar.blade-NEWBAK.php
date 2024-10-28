{{-- vertical-menu --}} @if($configData['mainLayoutType'] == 'vertical-menu')
<div class="main-menu menu-fixed @if($configData['theme'] === 'light') {{" menu-light"}} @else {{'menu-dark'}} @endif menu-accordion menu-shadow" data-scroll-to-active="true">
    <div class="navbar-header">
        <ul class="nav navbar-nav flex-row">
            <li class="nav-item mr-auto"><a class="navbar-brand" href="{{asset('/topic-settings')}}">
                    <div class="brand-logo">
                        <img src="{{asset('images/logo/logo.png')}}" class="logo" alt="">
                    </div>
                    <h2 class="brand-text mb-0">{{-- @if(!empty($configData['templateTitle']) && isset($configData['templateTitle'])) {{$configData['templateTitle']}} @else Frest @endif --}}</h2>
                </a></li>

        </ul>
    </div>
    <div class="shadow-bottom"></div>
    <?php 
    $loggedin_user_id = \Session::get('_loggedin_customer_id');
    if(isset($loggedin_user_id) && !empty($loggedin_user_id))
    {
    ?>
    <div class="main-menu-content">
        <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation" data-icon-style="lines">
            <li class="nav-item has-sub<?php if (stristr($_SERVER["REQUEST_URI"], '/topic-dashboard') !== FALSE) echo ' sidebar-group-active open'; ?>"><a href="#"> <i class="menu-livicon livicon-evo-holder" data-icon="desktop" style="visibility: visible; width: 60px;"></i> <span class="menu-title text-truncate">Dashboards</span>
                </a>
                <ul class="menu-content">
                    <?php
                    
                    $topic_session = \Session::get('current_loaded_project');
                    $subtopic_session_id = \Session::get('current_loaded_subtopic');

                    $t_data = DB::select("SELECT topic_id, topic_title FROM customer_topics WHERE customer_portal = 'D24' AND topic_is_deleted != 'Y' AND topic_user_id = " . $loggedin_user_id . " ORDER BY topic_id DESC");
                    for ($i = 0; $i < count($t_data); $i++) {
                        //Check for sub topics / customer experience
                        $li_classes = '';
                        $exp_data = DB::select("SELECT exp_id, exp_name FROM customer_experience WHERE exp_uid = " . $loggedin_user_id . " AND exp_topic_id = " . $t_data[$i]->topic_id);
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
            <li class="nav-item has-sub<?php if (stristr($_SERVER["REQUEST_URI"], '/topic-settings') !== FALSE) echo ' sidebar-group-active open'; ?>">
                <a href="# "> <i class="menu-livicon livicon-evo-holder" data-icon="settings" style="visibility: visible; width: 60px;"><div class="lievo-svg-wrapper"></div></i> <span class="menu-title text-truncate">Setting</span></a>
                <ul class="menu-content">
                    <li<?php if (stristr($_SERVER["REQUEST_URI"], '/topic-settings') !== FALSE) echo ' class="active"'; ?>><a href="/topic-settings " class="d-flex align-items-center"> <i class="bx bx-right-arrow-alt"></i> <span class="menu-item text-truncate">Dashboards</span>
                        </a></li>
                </ul>
            </li>
            <li class="nav-item has-sub<?php if (stristr($_SERVER["REQUEST_URI"], '/reports') !== FALSE) echo ' sidebar-group-active open'; ?>">
                <a href="# "> <i class="menu-livicon livicon-evo-holder" data-icon="notebook" style="visibility: visible; width: 60px;"><div class="lievo-svg-wrapper"></div></i> <span class="menu-title text-truncate">Reports</span></a>
                <ul class="menu-content">
                    <li<?php if (stristr($_SERVER["REQUEST_URI"], '/reports') !== FALSE) echo ' class="active"'; ?>><a href="/reports " class="d-flex align-items-center"> <i class="bx bx-right-arrow-alt"></i> <span class="menu-item text-truncate">Reports listing</span>
                        </a>
                    </li>
                    {{--<li><a href="javascript:void(0);" class="d-flex align-items-center" data-toggle="modal" data-target="#addReportModal"> <i class="bx bx-right-arrow-alt"></i> <span class="menu-item text-truncate">Create new</span>
                        </a>
                    </li>--}}
                </ul>
            </li>
        </ul>
    </div>
    <?php } ?>
</div>

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
