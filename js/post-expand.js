jQuery('.toggle-post-body').click(function(event){
  var nextButton = jQuery(this)
    .toggleClass('expanded')
    .toggleClass('collapsed')
    .siblings('.toggle-post-body');

  nextButton
    .toggleClass("expanded")
    .toggleClass("collapsed");

  var contentBlock = nextButton
    .closest(".toggle-buttons")
    .next('.post-body-content');

  contentBlock
    .toggleClass("expanded")
    .toggleClass("collapsed");
});

