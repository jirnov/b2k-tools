jQuery(document).ready(function($) {
	// --- Google Analytics ---
	window.dataLayer = window.dataLayer || [];
	window.gtag = function() {
		window.dataLayer.push(arguments);
	}

	$.getScript(
		'https://www.googletagmanager.com/gtag/js?id=G-41BWRDZLG5',
		function() {
			gtag('js', new Date());
			gtag('config', 'G-41BWRDZLG5');
		}
	);

	// --- Yandex Metrika ---
	window.ym = function() { (window.ym.a||[]).push(arguments); }
	window.ym.l = 1*new Date();

	$.getScript(
		'/metrika_tag.js',
		function() {
			ym(91531653, "init", {
				webvisor:true,
				trackLinks:true,
				clickmap:true,
				accurateTrackBounce:false
			}
			);
		}
	);

	// -- Mail.ru counter ---
	var _tmr = window._tmr || (window._tmr = []);
	_tmr.push({
		id: "2601331", 
		type: "pageView", 
		start: (new Date()).getTime()
	});

	$.getScript('//top-fwz1.mail.ru/js/code.js');

	// --- Yadro counter ---
	new Image().src = "https://counter.yadro.ru/hit?r"+escape(document.referrer)+((typeof(screen)=="undefined")?"":";s"+screen.width+"*"+screen.height+"*"+(screen.colorDepth?screen.colorDepth:screen.pixelDepth))+";u"+escape(document.URL)+";h"+escape(document.title.substring(0,150))+";"+Math.random();
});

window.notifyShare = function(service) {
	if (typeof gtag !== 'undefined') {
		gtag('event', 'share', { 'service' : service });
	}
	if (typeof ym !== 'undefined') {
		ym(91531653, 'reachGoal', 'share', { 'service': service });
	}
}

window.notifyComments = function(area) {
	if (typeof gtag !== 'undefined') {
		gtag('event', 'comment', { area: area });
	}

	if (typeof ym !== 'undefined') {
		ym(91531653, 'reachGoal', 'comment-focus', { area: area });
	}
};

jQuery(window).on('load', function() {
	// Отключаем pointer-events для иконок соцсетей
	jQuery('.social-likes__icon').css('pointer-events', 'none');

	// Отслеживание кликов по соцсетям
	jQuery('.social-likes__widget').click(function(e) {
		var service = jQuery(e.target).attr('data-service');
		notifyShare(service);
	});

	// Отслеживание фокуса на полях комментариев
	jQuery('#comment').focus(function() {
		notifyComments('main');
	});
  jQuery('#author').focus(function() {
    notifyComments('author');
  });
  jQuery('#email').focus(function() {
    notifyComments('email');
  });
});    

