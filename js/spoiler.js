jQuery('.spoiler-body').toggleClass("folded");
jQuery('.spoiler-head').click(function(){
	var body = jQuery(this).toggleClass("folded").toggleClass("unfolded").next();
	body.toggleClass("folded").toggleClass("unfolded");
});

