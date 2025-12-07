jQuery('.post-body .toggle-post-body').click(function(event){
  if (!jQuery(event.target).is('.toggle-post-body, .label')) {
    return;
  }

  const nextBlock = jQuery(this)
    .toggleClass("expanded")
    .toggleClass("collapsed")
    .siblings();

  nextBlock
    .toggleClass("expanded")
    .toggleClass("collapsed");
});

