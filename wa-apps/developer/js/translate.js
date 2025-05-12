$(function() {
  var $block = $('#translate-tabs');
  $('.tabs li a', $block).on('click', function (event) {
    event.preventDefault();
    $('.tabs li', $block).removeClass('selected');
    $('.tab-content', $block).hide();
    var $link = $(this);
    $link.parents('li').addClass('selected');
    $($link.attr('href')).show();
  });
  $('.tabs li:eq(0) a', $block).trigger('click');
});