jQuery('.post-body .toggle-post-body').click(function(event){
  const nextBlock = jQuery(this)
    .toggleClass("expanded")
    .toggleClass("collapsed")
    .siblings();

  nextBlock
    .toggleClass("expanded")
    .toggleClass("collapsed");
});

