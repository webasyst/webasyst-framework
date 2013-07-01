$(document).ready(function () {

    VK.init(function() { 
        // API initialization succeeded 
        VK.callMethod("setTitle", document.title);
        VK.callMethod("resizeWindow", $("body").outerWidth(), $("body").outerHeight()+80);
    });
    
    $('input[type="button"]').addClass('vk-button');
    $('input[type="submit"]').addClass('vk-button');

});
