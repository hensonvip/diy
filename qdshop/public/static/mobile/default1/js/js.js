$(document).ready(function() {
	function tab(a,b){
		$(a).click(
			function(){
				var i = $(this).index();
	            if($(this).hasClass('on')){

	            }else{
	                $(this).addClass('on').siblings().removeClass('on');
	                $(b).hide();
	                $(b).eq(i).fadeIn();
	            }
				
			}
		)
	}

});