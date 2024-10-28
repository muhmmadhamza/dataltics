<?php
    if(\Session::get('_loggedin_customer_id') == 308) {
?>
    <footer class="footer footer-light @if(isset($configData['footerType'])){{$configData['footerClass']}}@endif">
        <p class="clearfix mb-0">
          <span class="float-left d-inline-block"><script>document.write(new Date().getFullYear())</script> &copy; RocketKitchens</span>
          <span class="float-right d-sm-inline-block d-none">Al rights reserved by RocketKitchens</span>
        </p>
        @if($configData['isScrollTop'] === true)
        <button class="btn btn-primary btn-icon scroll-top" type="button" style="display: inline-block;">
        <i class="bx bx-up-arrow-alt"></i>
        </button>
        @endif
    </footer>
    <?php } else if(\Session::get('_loggedin_customer_id') == 309 || \Session::get('_loggedin_customer_id') == 310) { ?>
    <footer class="footer footer-light @if(isset($configData['footerType'])){{$configData['footerClass']}}@endif">
        <p class="clearfix mb-0">
          <span class="float-left d-inline-block"><script>document.write(new Date().getFullYear())</script> © Datalyticx / E& Enterprise IoT and AI</span>
          <span class="float-right d-sm-inline-block d-none">&nbsp;</span>
        </p>
        @if($configData['isScrollTop'] === true)
        <button class="btn btn-primary btn-icon scroll-top" type="button" style="display: inline-block;">
        <i class="bx bx-up-arrow-alt"></i>
        </button>
        @endif
    </footer>

    <?php } else { ?>
    <footer class="footer footer-light @if(isset($configData['footerType'])){{$configData['footerClass']}}@endif">
        <p class="clearfix mb-0">
          <span class="float-left d-inline-block"><script>document.write(new Date().getFullYear())</script> &copy; Datalyticx</span>
          <span class="float-right d-sm-inline-block d-none">Al rights reserved by Datalyticx</span>
        </p>
        @if($configData['isScrollTop'] === true)
        <button class="btn btn-primary btn-icon scroll-top" type="button" style="display: inline-block;">
        <i class="bx bx-up-arrow-alt"></i>
        </button>
        @endif
    </footer>

    <?php } ?>
