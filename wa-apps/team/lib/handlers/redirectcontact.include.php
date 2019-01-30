<?php
/*
 * See backend_dispatch_miss handler.
 */
?><!DOCTYPE html>
<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><title></title></head><body>
<script>(function(){"use strict";
    var t = window.location.hash.split('#/contact/');
    if (t[0] || !t[1]) {
        document.write('404');
    } else {
        window.location = '../team/id/'+t[1];
    }
})();</script></body></html>