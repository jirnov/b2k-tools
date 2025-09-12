"use strict;"

jQuery(document).ready(function($) {

  window.dataLayer = [];
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

  window.ym = function() { (window.ym.a||[]).push(arguments); }
  window.ym.l = 1*new Date();

  new Image().src = "https://counter.yadro.ru/hit?r"+escape(document.referrer)+((typeof(screen)=="undefined")?"":";s"+screen.width+"*"+screen.height+"*"+(screen.colorDepth?screen.colorDepth:screen.pixelDepth))+";u"+escape(document.URL)+";h"+escape(document.title.substring(0,150))+";"+Math.random();

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

  var _tmr = window._tmr || (window._tmr = []);
  _tmr.push({
    id: "2601331", 
    type: "pageView", 
    start: (new Date()).getTime()
  });

  $.getScript('//top-fwz1.mail.ru/js/code.js');
});

function notifyShare(service) {
  gtag('event', 'share', { 
    'service' : service 
  });
  ym(91531653, 'reachGoal', 'share', {
    'service': service
  });
}

function notifyComments(action) {
  gtag('event', 'comment', {
    'action' : action
  });
  ym(91531653, 'reachGoal', 'comment-focus', {
    'action' : action
  });
}

jQuery(window).on('load', function() {
  jQuery('.social-likes__icon').css('pointer-events', 'none');
  jQuery('.social-likes__widget').click(function(e) {
    var service = jQuery(e.target).attr('data-service');
    notifyShare(service);
  });
  jQuery('#comment').focus(function() {
    notifyComments('comment-area');
  });
  jQuery('#author').focus(function() {
    notifyComments('comment-author');
  });
  jQuery('#email').focus(function() {
    notifyComments('comment-email');
  });
});    

