jQuery(window).load(function() {
  jQuery('.widget-body a.current-page').click(function(event) {
    event.preventDefault();

    var anchor = jQuery(this).attr('href');

    history.pushState(null, null, anchor);

    var deltaY = jQuery(window).width() >= 1280 ? 80 : 60;

    jQuery('html, body').animate({
      scrollTop: jQuery(anchor).offset().top-deltaY
    }, 'fast');
  });
  jQuery('.post a img').parent('a').addClass('image_link');
  jQuery('.sd-title').hide();
});

