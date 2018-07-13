$(document).ready(function(){
$(".wh").hover(function() {
		$(this).children(".zypop").show();
		$(this).children(".zypop").stop().animate({
			left: "-93px",
			opacity: "1"
		}, 400)
	}, function() {
		$(this).children(".zypop").hide();
		$(this).children(".zypop").stop().animate({
			left: "-124px",
			opacity: "0"
		}, 1)
});




})