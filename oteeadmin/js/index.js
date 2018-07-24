$(window).load(function(){
//	var imgArr = ["a.jpg","a2.jpg","bg.jpg","logo.jpg","btn.png"];
//	function xzImg(){
//		for(var i = 0; i<imgArr.length; i++){
//			$("<img />").attr("src","images/"+imgArr[i]);
//			console.log(i);
//		}
//		console.log(1);
//		fn();
//	}
//	xzImg();
	//对齐线
	if($(".inner").length>0){
		var lineHeight = $(".inner").get(0).getBoundingClientRect().top;
		var Height = lineHeight + 350;
		$(".bg").css("height",Height+"px");
		$(window).resize(function(){
			lineHeight = $(".inner").get(0).getBoundingClientRect().top;
			Height = lineHeight + 350;
			$(".bg").css("height",Height+"px");
		})
	}
	//对齐线
	
	$(".spinner").hide();
	if($(window).width() > 450){
		$(".wrap").addClass("on");
	}
	//头部
	$(".b3").click(function(){
		if($(".b3").hasClass("g")){
			$(".dp").slideDown();
			$(".b3").removeClass("g");
		}
		else{
			$(".dp").slideUp();
			$(".b3").addClass("g");
		}
	});
	//菜单栏
	$(".h4").click(function(){
		if($(window).width() < 1200){
			if($(".h4").hasClass("g")){
				$(".nav-info").hide();
				$(".h4").removeClass("g")
			}
			else{
				alert(1);
				$(".nav-info").show();
				$(".h4").addClass("g")
			}
		}
	})
})
