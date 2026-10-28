!function(a){"function"==typeof define&&define.amd?define(["jquery"],a):"object"==typeof exports?a(require("jquery")):a(jQuery)}(function(a){function b(a){return h.raw?a:encodeURIComponent(a)}function c(a){return h.raw?a:decodeURIComponent(a)}function d(a){return b(h.json?JSON.stringify(a):String(a))}function e(a){0===a.indexOf('"')&&(a=a.slice(1,-1).replace(/\\"/g,'"').replace(/\\\\/g,"\\"));try{return a=decodeURIComponent(a.replace(g," ")),h.json?JSON.parse(a):a}catch(b){}}function f(b,c){var d=h.raw?b:e(b);return a.isFunction(c)?c(d):d}var g=/\+/g,h=a.cookie=function(e,g,i){if(void 0!==g&&!a.isFunction(g)){if(i=a.extend({},h.defaults,i),"number"==typeof i.expires){var j=i.expires,k=i.expires=new Date;k.setTime(+k+864e5*j)}return document.cookie=[b(e),"=",d(g),i.expires?"; expires="+i.expires.toUTCString():"",i.path?"; path="+i.path:"",i.domain?"; domain="+i.domain:"",i.secure?"; secure":""].join("")}for(var l=e?void 0:{},m=document.cookie?document.cookie.split("; "):[],n=0,o=m.length;o>n;n++){var p=m[n].split("="),q=c(p.shift()),r=p.join("=");if(e&&e===q){l=f(r,g);break}e||void 0===(r=f(r))||(l[q]=r)}return l};h.defaults={},a.removeCookie=function(b,c){return void 0===a.cookie(b)?!1:(a.cookie(b,"",a.extend({},c,{expires:-1})),!a.cookie(b))}});
(function($) { $(document).ready(function(){
	
jQuery(".menu_right ul li").hover(function(){
var n=jQuery(this).find(" > ul");n.length>0&&jQuery(window).width()>900&&n.stop().slideDown(180)},
function(){var n=jQuery(this).find(" > ul");n.length>0&&jQuery(window).width()>900&&n.stop().slideUp(200)
});

/* v3.7.0 — اسلایدر ویژه «تک‌اسلاید» شد: یک تصویر بزرگ به‌جای دو کارت ریز. */
$('.vip_top_slider').owlCarousel({
nav:false,rtl:true,autoplay:true,loop:true,autoplayTimeout:5200,autoplayHoverPause:true,margin:0,dots:true,dotsEach:false,
smartSpeed:600,
responsive:{
0:{items:1}
}
});

$('.artists_slider').owlCarousel({
nav:true,rtl:true,autoplay:true,autoplayTimeout:4000,autoplayHoverPause:true,margin:20,dots:false,
responsive:{
200:{items:2},
500:{items:3},
800:{items:4},
1000:{items:6},
1300:{items:7}
}
});

$(document).scroll(function(){
var y = $(this).scrollTop();
if (y > 600){ $('.top_page').fadeIn(); } else{ $('.top_page').fadeOut(); }});
$('.top_page').click( function(){ $('body,html').animate({ scrollTop: 0 },600); return false; });

$('body').click(function(event) {
  if (!$('.searches_mobile').is(event.target) && $('.searches_mobile').has(event.target).length === 0) {
    $('.searches_mobile').slideUp();
    $('.search_mobile_box').removeClass('active');
  }
});
$('.search_mobile_box').click(function(event) {
  event.stopPropagation()
  $(this).toggleClass('active');
  $(".searches_mobile").slideToggle();
});

$('#cssmenu li.has-sub>a').on('click', function(){
    $(this).removeAttr('href');
    var element = $(this).parent('li');
    if (element.hasClass('open')) {
      element.removeClass('open');
      element.find('li').removeClass('open');
      element.find('ul').slideUp();
    }
    else {
      element.addClass('open');
      element.children('ul').slideDown();
      element.siblings('li').children('ul').slideUp();
      element.siblings('li').removeClass('open');
      element.siblings('li').find('li').removeClass('open');
      element.siblings('li').find('ul').slideUp();
    }
  });
$('#cssmenu>ul>li.has-sub>a').append('<span class="holder"></span>');
$(".navicon").click(function(){ $(".mobiles_menu").animate({marginRight:"0px"},200,"linear"); runmenu=1; });
$(".bodydeactive").click(function(){ $(".mobiles_menu").animate({marginRight:"-262px"},200,"linear"); });
$(".navicon").click(function(){ 
$(".bodydeactive").fadeIn(400), $(".mobile_box").animate({marginRight:"0px"},200,"linear") });
$(".bodydeactive").click(function(){
$(".mobile_box").animate({marginRight:"0px"},200,"linear"), $(".bodydeactive").fadeOut(400)
});


$(window).scroll(function() {    
    var scroll = $(window).scrollTop();

    if (scroll >= 50) {
        $("#header").addClass("darkHeader");
    } else {
        $("#header").removeClass("darkHeader");
    }
});















$(".toggle_modes").click(function(){
$(".toggle_modes").toggleClass("active");
$("body").toggleClass("night");
$.cookie("toggle_modes", $(".toggle_modes").hasClass('active'),{ path: '/' });
});
if ($.cookie("toggle_modes") == "true"){
$(".toggle_modes").addClass("active");
$("body").addClass("night");
}




});
}(jQuery));


/* -------------------------------------------------------------------------
 * ۳.۷.۰ — پلیر قدیمی (audioPlayer) حذف شد.
 * جای آن، پلیر «رادیو» در js/tara-player.js است که بدون jQuery کار می‌کند و
 * در هر سه صفحه (اصلی، آهنگ، هنرمند) همان یک کامپوننت است. کد قدیمی به
 * `#audio` و `.progress-box` تکیه می‌کرد و در صفحه‌هایی که این عناصر نبودند
 * خطا می‌داد؛ حالا هیچ‌کدام لازم نیست.
 * ---------------------------------------------------------------------- */
