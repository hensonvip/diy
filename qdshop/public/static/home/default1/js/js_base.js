var a_k;
$(function(){
	r_top_bot(a_k);

    $('.ipro_popup').scroll(function() {
        setTimeout(function(){
            r_top_bot(a_k);
        },40);
    });

    $(window).resize(function() {
        setTimeout(function(){
            r_top_bot(a_k);
        },40);
    });

	//详情左边图片放大
    var pic_index = 0;
    $(document).on('click', '.ipro_piclist img', function() {
        pic_index = $(this).attr("data-index");
        picCheck(pic_index);
        $('.js-lightbox-wrap').fadeIn();
        $('.js-contents .js-slide').eq(pic_index).show().siblings().hide();
        picLoad(pic_index);
    });
    $(document).on('click', '.js-slide-content', function() {
        if($('html').hasClass('lightbox-zoomed')){
            $('html').removeClass('lightbox-zoomed');
        }else{
            if($(this).parents('.js-slide').attr("zoom-in") == "true"){
                $('html').addClass('lightbox-zoomed');
            }
        }
    });
    $('.js-lightbox-wrap').hover(function() {
        $(this).removeClass('extras-hidden')
    }, function() {
        $(this).addClass('extras-hidden');
    });

    //切换
    $('#lightbox-inner-wrap .prev').click(function() {
        pic_index = parseInt(pic_index)-1;
        if(pic_index <= 0){
            pic_index = 0;
        }
        picCheck(pic_index);
        $('.js-contents .js-slide').eq(pic_index).fadeIn().siblings().hide();
        picLoad(pic_index);
    });

    $('#lightbox-inner-wrap .next').click(function() {
        pic_index = parseInt(pic_index)+1;
        if(pic_index >= $('.js-contents .js-slide').length){
            pic_index = $('.js-contents .js-slide').length - 1;
        }
        picCheck(pic_index);
        $('.js-contents .js-slide').eq(pic_index).fadeIn().siblings().hide();
        picLoad(pic_index);
    });

    //详情左边图片关闭
    $('.js-close').click(function() {
        $('.js-lightbox-wrap').fadeOut();
    });

    //点击右边按钮滑到评论区域
    $('.ipd_detail .des span.comment').click(function() {
        var left_height = $(this).parents('.ipro_wrap').find('.ipro_left').height();
        var comm_height = $(this).parents('.ipro_wrap').find('.ipro_com_bot').height();
        $('.ipro_popup').stop().animate({ scrollTop: left_height - comm_height }, 300);
    });

    //滚动条
    $('.ipro_right_bef .ipd_rtop .ipd8,.card_after .card_des,.i_bar_more,.ipro_right_after .ipd_rtop').mCustomScrollbar({
        axis: "y",
        scrollInertia: 100,
        scrollButtons: {
            enable: true,
            scrollSpeed: 20
        },
        theme: "3d"
    });
});
function loadpList(eq){
    var ihtml = '';
    $('.ipro_content .ipro_wrap').eq(eq).find('.ipro_piclist img').each(function(index, el) {
       $(this).attr("data-index",index);
       ihtml += '<div data-slide-id="'+ index +'" class="js-slide" zoom-in="false"><div class="js-slide-content lightbox-content"><img data-src="'+ $(this).attr("realsrc") +'"></div></div>';
    });
    $('.js-contents').append(ihtml);
}
function picCheck(pic_index){
    var pic_length = $('.js-contents .js-slide').length;
    if(pic_index == 0){
        $('.js-prev').addClass('hidden');
    }else{
        $('.js-prev').removeClass('hidden');
    }
    if(pic_index >= pic_length - 1){
        $('.js-next').addClass('hidden');
    }else{
        $('.js-next').removeClass('hidden');
    }
}
function picLoad(pic_index){
    if($('.js-contents .js-slide').eq(pic_index).find('img').attr("loaded") == true){
        return false;
    }
    $('.js-contents .js-slide').eq(pic_index).find('img').attr("src", $('.js-contents .js-slide').eq(pic_index).find('img').attr("data-src"));
    $('.js-contents .js-slide').eq(pic_index).find('img').bind('load',function(){
        var nWidth = $(this).naturalWidth();
        var nHeight = $(this).naturalHeight();
        var w_h = nHeight * $(window).width() / nWidth;
        if(nHeight > $(window).height() && w_h > $(window).height()){
            $(this).parents('.js-slide').attr("zoom-in","true");
            $(this).parents('.js-slide-content').addClass('zoomable');
        }
        $('.js-contents .js-slide').eq(pic_index).find('img').attr("loaded",true);
    });
}
function r_top_bot(k) { // k=1(otee),k=2(作品)
	a_k = k;
	$('.ipro_right_bef .ipd_rtop .ipd8,.ipro_right_after .ipd_rtop').mCustomScrollbar("update");

	if(k == 1){
		var nDivHight = $(".ipro_popup").height();
	    var nScrollHight = $('.ipro_popup')[0].scrollHeight;
	    var nScrollTop = $('.ipro_popup')[0].scrollTop;
	    var t_top_h1 = $('.ipro_right_bef .ipd_rtop').outerHeight();
	    var t_bot_h1 = $('.ipro_right_bef .ipd_rbot_wrap').outerHeight();
	    var t_top_h2 = $('.ipro_right_after .ipd_rtop').outerHeight();
	    var t_bot_h2 = $('.ipro_right_after .ipd_rbot_wrap').outerHeight();
	    var pad = 80;
	    var top_dis = 0;
	    var bot_dis = 0;
	    if (pad - nScrollTop > 0) {
	        top_dis = pad - nScrollTop;
	    } else {
	        top_dis = 0;
	    }
	    if($('html').hasClass('size')){ 
	        $('html').addClass('close_top');
	        return false;
	    }
	    if ($('.ipro_popup').scrollTop() < pad) { //top
	        if (t_top_h1 > $('.ipro_popup').height() - top_dis - t_bot_h1) {
	            var bot_top1 = t_top_h1;
	        } else {
	            var bot_top1 = $('.ipro_popup').height() - top_dis - t_bot_h1;
	        }
	        if (t_top_h2 > $('.ipro_popup').height() - top_dis - t_bot_h2) {
	            var bot_top2 = t_top_h2;
	        } else {
	            var bot_top2 = $('.ipro_popup').height() - top_dis - t_bot_h2;
	        }
	        $('.ipro_wrap').removeClass('top mid bot').addClass('top');
	        $('.ipro_right_bef .ipd_rbot').css({ "top": bot_top1 });
	        $('.ipro_right_after .ipd_rbot').css({ "top": bot_top2 });
	    } else if ($('.ipro_popup').scrollTop() > pad && nScrollTop + nDivHight < nScrollHight - pad) { //mid
	        $('.ipro_wrap').removeClass('top mid bot').addClass('mid');
	        $('.ipro_right_bef .ipd_rtop').css({ "height": "calc(100% - " + t_bot_h1 + "px)" });
	        $('.ipro_right_after .ipd_rtop').css({ "height": "calc(100% - " + t_bot_h2 + "px)" });
	    } else if (nScrollTop + nDivHight >= nScrollHight - pad) { //bot
	        bot_dis = nScrollHight - nScrollTop - nDivHight;
	        $('.ipro_wrap').removeClass('top mid bot').addClass('bot');
	        $('.ipro_right_bef .ipd_rtop').css({ "height": "calc(100% - " + (t_bot_h1 + pad - bot_dis) + "px)" });
	        $('.ipro_right_after .ipd_rtop').css({ "height": "calc(100% - " + (t_bot_h2 + pad - bot_dis) + "px)" });
	    }
	}
	if(k == 2){
		var nDivHight = $(".ipro_popup").height();
	    var nScrollHight = $('.ipro_popup')[0].scrollHeight;
	    var nScrollTop = $('.ipro_popup')[0].scrollTop;
	    var t_top_h1 = $('.ipro_right_bef .ipd_rtop').outerHeight();
	    var t_bot_h1 = $('.ipro_right_bef .share_box').outerHeight();
	    var pad = 80;
	    var top_dis = 0;
	    var bot_dis = 0;
	    if (pad - nScrollTop > 0) {
	        top_dis = pad - nScrollTop;
	    } else {
	        top_dis = 0;
	    }
	    if($('html').hasClass('size')){ 
	        $('html').addClass('close_top');
	        return false;
	    }
	    if ($('.ipro_popup').scrollTop() < pad) { //top
	        if (t_top_h1 > $('.ipro_popup').height() - top_dis - t_bot_h1) {
	            var bot_top1 = t_top_h1;
	        } else {
	            var bot_top1 = $('.ipro_popup').height() - top_dis - t_bot_h1;
	        }
	        $('.ipro_wrap').removeClass('top mid bot').addClass('top');
	        $('.ipro_right_bef .ipd_rbot').css({ "top": bot_top1 });
	    } else if ($('.ipro_popup').scrollTop() > pad && nScrollTop + nDivHight < nScrollHight - pad) { //mid
	        $('.ipro_wrap').removeClass('top mid bot').addClass('mid');
	        $('.ipro_right_bef .ipd_rtop').css({ "height": "calc(100% - " + t_bot_h1 + "px)" });
	    } else if (nScrollTop + nDivHight >= nScrollHight - pad) { //bot
	        bot_dis = nScrollHight - nScrollTop - nDivHight;
	        $('.ipro_wrap').removeClass('top mid bot').addClass('bot');
	        $('.ipro_right_bef .ipd_rtop').css({ "height": "calc(100% - " + (t_bot_h1 + pad - bot_dis) + "px)" });
	    }
	}
    if ($('.ipro_wrap').hasClass('top')) {
        $('html').addClass('close_top');
    } else {
        $('html').removeClass('close_top');
    }
    if ($('.ipro_wrap').hasClass('bot')) {
        $('html').addClass('bottom');
        $('.float_right').css({ "bottom": "calc(5% + " + (pad - bot_dis) + "px)" })
    } else {
        $('html').removeClass('bottom');
    }
    $('.ipro_right_bef .ipd_rtop .ipd8,.ipro_right_after .ipd_rtop').mCustomScrollbar("update");
}
(function($){
    var
    props = ['Width', 'Height'],
    prop;

    while (prop = props.pop()) {
    (function (natural, prop) {
      $.fn[natural] = (natural in new Image()) ? 
      function () {
      return this[0][natural];
      } : 
      function () {
      var 
      node = this[0],
      img,
      value;

      if (node.tagName.toLowerCase() === 'img') {
        img = new Image();
        img.src = node.src,
        value = img[prop];
      }
      return value;
      };
    }('natural' + prop, prop.toLowerCase()));
    }
  }(jQuery));