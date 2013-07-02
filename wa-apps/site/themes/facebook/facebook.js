$(document).ready(function () {
    $('input[type="button"]').addClass('facebook-button');
    $('input[type="submit"]').addClass('facebook-button');
});

$(window).load(function () {
    FB.Canvas.setAutoGrow(true);
});