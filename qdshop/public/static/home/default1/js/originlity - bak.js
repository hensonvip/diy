<!--比赛详情-->

$(function(){
    $('.cylt_bbox').css("background","rgba(255,255,255,0)");
});

$(function(){
    $('body').css({"padding-top":"0"});
    var l_bar_h = $('.cylt_bleft').outerHeight();
    $(window).scroll(function() {
        var w_h = $(window).height();
        var l_bat_top = (w_h - l_bar_h - $('.header').outerHeight()) / 2;
        var baseHeight = $('.cylt_top').height();
        var scrollTop = $(window).scrollTop();
        var doc_height = $('body').height();
        if(scrollTop >= doc_height - $(window).height() - 130 - $('.footer').outerHeight()){
            $('.cylt_top .cylt_fw').css({"height": doc_height - scrollTop - 330});
        }else{
            $('.cylt_top .cylt_fw').css({"height":"100%"});
        }

        if(scrollTop > 60){
            $('.cylt_top .cylt_fw,.cylt_ttxt').css({"top": 70})
            $('.cylt_share').addClass('fixed');
        }else{
            $('.cylt_top .cylt_fw,.cylt_ttxt').css({"top":  130 - scrollTop})
            $('.cylt_share').removeClass('fixed');
        }
        var bot_scroll = baseHeight - scrollTop;
        if(bot_scroll <= 0){ bot_scroll = 0; }
        if(bot_scroll >= baseHeight){ bot_scroll = baseHeight; }
        $('.cylt_bot').css('top',0 +"px");
        $('.cylt_ttxt').css({'opacity': 1 - scrollTop/800});

        if(scrollTop >= l_bat_top + l_bar_h - 60){
            $('.cylt_bleft').removeClass('bottom').addClass('fixed').css('margin-top',-l_bar_h/2 + 35);
        }else{
            $('.cylt_bleft').removeClass('bottom fixed').css('margin-top',0);
        }
        if(scrollTop + l_bar_h > $('.cylt_wrap').height() - 60 - 70){
            $('.cylt_bleft').removeClass('fixed').addClass('bottom');
        }
    });

    $('.cylt_share').click(function() {
        $('.cbg_bg').fadeIn();
        $('.cbg_share_wrap').show().siblings().hide();
    });

    $('.cbg_share_wrap .del,.cbg_join_wrap .del,.cbg_make_wrap .del,.cbg_choose_wrap .del').click(function() {
        $('.cbg_bg').fadeOut();
    });

    //参赛弹窗
    $('.show_join').click(function() {
        $('.cbg_bg').fadeIn();
        $('.cbg_join_wrap').show().siblings().hide();
    });

    //选择已有作品
    $('.show_choose').click(function() {
        $('.cbg_bg').fadeIn();
        $('.cbg_choose_wrap').show().siblings().hide();
    });

    // $('.go_detail').click(function() {
    //     $(".cylt_popup").animate({scrollTop: $('.cylt_top').height()}, 500);
    // });
    //切换通过审核未通过
    $('.cylt_bright .cylt_bar li').click(function() {
        var eq = $(this).index();
        $(this).addClass('on').siblings().removeClass('on');
        this_count = $(this).children().length;//切换时把总个数切换为当前显示 ，弹窗箭头判断
        $('.cylt_bright .cylt2_wrap .cylt2_box').hide().css({"opacity":".6"});
        $('.cylt_bright .cylt2_wrap .cylt2_box').eq(eq).show().animate({"opacity":"1"});
    });

    $(document).on('click','.cylt2_plist.tp li .tp_box',function(e) {
        e.stopPropagation();
        //alert(e.isPropagationStopped());
        var number = $(this).parents('li').find('.tps').text();
        if($(this).hasClass('active')){
            // $(this).removeClass('active');
            // $(this).parents('li').find('.tps').text(parseInt(number) - 1);
        }else{
            var record = $(this).parents('li').find('.record').text();
            //alert(record);
            $.ajax({
                type: 'GET',
                url: vote_ajax,
                data: {record:record},
                dataType: 'json',
                success: function (result) {
                    console.log(result);
                    if(result['message'] == 0){
                        layer.msg('您已投过票');
                    }else if(result['message'] == 1){
                        layer.msg('投票成功');
                    }else{
                        layer.msg('投票失败，请稍后重试');
                    }
                    return true;
                }
            })
            $(this).addClass('active');
            $(this).parents('li').find('.tps').text(parseInt(number) + 1);
        }
    });



/*    //投票
    $('body').on('click','.ipd_rbot_wrap .ipd_tp',function () {
        var number = $('.ipd_user .price.ps .piaoshu').text();
        var _tihs = $(this);
        var record_id = $(".ipro_wrap").attr('data-rid');
        if (_tihs.hasClass('tp_ed')){
            return false;
        }
        $.ajax({
            type: 'GET',
            url: vote,
            data: {record:record_id},
            dataType: 'json',
            success: function (result) {
                if(result['message'] == '0'){
                    _tihs.addClass('tp_ed disabled').html("已投票");
                    //layer.msg("您已经投过票了");
                    return false;
                }
                _tihs.addClass('tp_ed disabled').html("已投票");
                $('.ipd_user .price.ps').addClass('tp_ed');
                $('.ipd_user .price.ps .piaoshu').text(parseInt(number) + 1);
            },
            error:function(){
                layer.msg("网络请求失败，请稍后重试");
            }
        });
    });*/

    $('body').on('mouseover','.ipd_tp',function(){
        //console.log(1);
        if(!$(this).hasClass('tp_ed')){
            $(this).html("+ <em>1</em>");
        }
    });

    $('body').on('mouseout','.ipd_tp',function(){
        if(!$(this).hasClass('tp_ed')){
            $(this).html("投 票");
        }
    });



    checkBaseH();
    $(window).resize(function() {
        checkBaseH();
    });
});



function checkBaseH(){
    if($(window).height() <= 700){
        $('.cylt_top').removeClass('middle small').addClass('small');
    }else if($(window).height() <= 800){
        $('.cylt_top').removeClass('middle small').addClass('middle');
    }else{
        $('.cylt_top').removeClass('middle small');
    }
}

$(function(){
    $('.cbg_check input').change(function() {
        $('.cbg_cover .cbg_check').removeClass('on');
        $('.cbg_cover .cbg_check input').prop("checked",true);
        if($(this).prop("checked") == true){
            $(this).parents('.cbg_check').addClass('on');
            $('.cbg_choose_wrap .cbg_sub').removeClass('disabled');
        }else{
            $(this).parents('.cbg_check').removeClass('on');
            $('.cbg_choose_wrap .cbg_sub').addClass('disabled');
        }
        checkCBG_all();
    });
    /*        $('.cbg_checkall').click(function() {
     if($(this).hasClass('on')){
     $('.cbg_cover .cbg_check').removeClass('on');
     $('.cbg_cover .cbg_check input').prop("checked",false);
     $(this).removeClass('on').text("全选");
     }else{
     $('.cbg_cover .cbg_check').addClass('on');
     $('.cbg_cover .cbg_check input').prop("checked",true);
     $(this).addClass('on').text("取消全选");
     }
     checkCBG_all();
     });*/

    $('.cbg_cover').mCustomScrollbar({
        axis:"y",
        scrollInertia: 100,
        scrollButtons:{
            enable: true,
            scrollSpeed: 20
        },
        theme:"3d"
    });
});
function checkCBG_all(){
    $('.cbg_cover .cbg_check').each(function(index, el) {
        if($(this).find('input').prop("checked") == false){
            $(".cbg_checkall input").prop("checked",false);
            $('.cbg_checkall').removeClass('on').text("全选");
            return false;
        }else{
            $(".cbg_checkall input").prop("checked",true);
            $('.cbg_checkall').addClass('on').text("取消全选");
        }
    });
}

$(function(){
    $('.cbg_sub').click(function() {
        $('.lt_bg').fadeIn();

    });

    $('.proto_bg2 .sure_btn').click(function() {
        $('.lt_bg .mep_tsub label').addClass('on');
        $('.lt_bg .mep_tsub label').find('input').prop("checked",true);
        $('.lt_bg .mep_sbtn').removeClass('disabled');
    });

    $('.lt_bg .del').click(function() {
        $('.lt_bg').fadeOut();

    });
});

$(function(){
    //协议弹窗
    $('.lt_bg .show_xy').click(function() {
        $('.proto_bg').fadeIn();
        $('.ipro_close').hide();
    });

    //阅读协议
    $('.mep_tsub label input').change(function() {
        if($(this).prop("checked") == true){
            $(this).parents('label').addClass('on');
            $('.lt_bg .mep_sbtn').removeClass('disabled');
            $('.lt_bg .mep_sbtn').css('pointer-events','auto');
        }else{
            $(this).parents('label').removeClass('on');
            $('.lt_bg .mep_sbtn').addClass('disabled');
            $('.lt_bg .mep_sbtn').css('pointer-events','none');
        }
    });

    $('.cbg_make_wrap .mep_tright,.proto_wrap .content .pb15').mCustomScrollbar({
        axis:"y",
        scrollInertia: 100,
        scrollButtons:{
            enable: true,
            scrollSpeed: 20
        },
        theme:"3d"
    });

    $('.proto_bg .btn').click(function() {
        $('.proto_bg').fadeOut();
        $('.ipro_close').show();
    });

    //标签字数
    $('.get_tag').keyup(function(event){
        var val_length = $('.get_tag').val().length;
        var max_length = 16;
        var left_length = max_length - val_length;
        if(max_length <= val_length){
            $('.get_tag').val($('.get_tag').val().slice(0,16));
            left_length == 0;
            $('.left_length').text("0");
        }else{
            $('.left_length').text(left_length);
        }
    });

    //回车或者空格添加标签
    $('.get_tag').keydown(function(event){
        var get_tag_val = $('.get_tag').val();

        var val = $.trim(get_tag_val);
        if(val){
            if(event.keyCode==13 || event.keyCode==32){
                if($('.n_tag_list dd').length >= 5){
                    layer.msg("最多只能添加五个标签！");
                    return false;
                }else{
                    $('.n_tag_list').append('<dd>'+ val +'<i class="type_del"></i></dd>');
                }
                $('.tag_txt').text($('.n_tag_list dd').length +"/5 标签");
                $('.mep_tright .tag_input input').val("");
                if($('.n_tag_list dd').length != 0){
                    $('.tag_box').addClass('active');
                }else{
                    $('.tag_box').removeClass('active');
                }
            }
        }
        $('.cbg_make_wrap .mep_tright').mCustomScrollbar('update');
    });

    //删除标签
    $(document).on('click', '.n_tag_list dd i', function(event) {
        var _this = $(this);
        layer.confirm('确定删除该标签？', {
            title: false,
            closeBtn: 0,
            btn: ['取 消','确 定'] //按钮
        }, function(){
            layer.closeAll();
        }, function(){
            _this.parents('dd').remove();
            $('.tag_txt').text($('.n_tag_list dd').length +"/5 标签");
            if($('.n_tag_list dd').length != 0){
                $('.tag_box').addClass('active');
            }else{
                $('.tag_box').removeClass('active');
            }
        });
    });

    //热门标签
    $('.mep_tright .tag_hot dd').click(function() {
        var text = $(this).text();
        if($('.n_tag_list dd').length >= 5){
            layer.msg("最多只能添加五个标签！");
            return false;
        }else{
            $('.n_tag_list').append('<dd>'+ text +'<i class="type_del"></i></dd>');
        }
        $('.tag_txt').text($('.n_tag_list dd').length +"/5 标签");
        if($('.n_tag_list dd').length != 0){
            $('.tag_box').addClass('active');
        }else{
            $('.tag_box').removeClass('active');
        }
        $('.cbg_make_wrap .mep_tright').mCustomScrollbar('update');
    });

    //上传图片
    $('.upload_img').click(function() {
        if($('.upload_box li.uploading').length != 0){
            layer.msg("还有图片在上传中，请稍等！")
            return false;
        }
    });
    $('.upload_img').change(function() {
        if($(this).val()){
            loadImageFile();//实时预览
            //异步上传图片,传完返回图片链接src
            //$('.upload_box li.uploading b').css({'background-image':'url('+ src +')'});
            //模拟2秒上传完
            setTimeout(function(){
                //alert($('.upload_box li.uploading').css("background-image").replace('url("','').replace('")'));
                $('.upload_box li.uploading b').css({'background-image':'url('+ $('.upload_box li.uploading').css("background-image") +')'});
                $('.upload_box li.uploading').removeClass('uploading').addClass('uploaded');
            }, 2000);
        }
    });

    //删除上传图片
    $(document).on('click', '.upload_box li.uploaded em', function(event) {
        event.preventDefault();
        var _this = $(this);
        layer.confirm('确定删除该图片？', {
            title: false,
            closeBtn: 0,
            btn: ['取 消','确 定'] //按钮
        }, function(){
            layer.closeAll();
        }, function(){
            _this.parents('li').remove();
            //异步删图片
        });
    });
});

oFReader = new FileReader(),
    rFilter = /^(?:image\/bmp|image\/cis\-cod|image\/gif|image\/ief|image\/jpeg|image\/jpeg|image\/jpeg|image\/pipeg|image\/png|image\/svg\+xml|image\/tiff|image\/x\-cmu\-raster|image\/x\-cmx|image\/x\-icon|image\/x\-portable\-anymap|image\/x\-portable\-bitmap|image\/x\-portable\-graymap|image\/x\-portable\-pixmap|image\/x\-rgb|image\/x\-xbitmap|image\/x\-xpixmap|image\/x\-xwindowdump)$/i;

oFReader.onload = function (oFREvent) {
    // console.log(oFREvent.target.result);
    $('.upload_box ul').prepend('<li class="uploading" style="background-image: url('+ oFREvent.target.result +')"><i></i><em></em><b></b></li>')
};

function loadImageFile() {
    if (document.getElementById("upload_img").files && document.getElementById("upload_img").files.length) {
        var oFile = document.getElementById("upload_img").files[0];
        if (!rFilter.test(oFile.type)) { alert("You must select a valid image file!"); return; }
        oFReader.readAsDataURL(oFile);
    }else{
        $('.upload_box ul').prepend('<li class="uploading"><i></i><em></em><b></b></li>')
        return;
    }
}

//弹窗代码
$(function() {
    //展开详情
    /*        var open = false;
     $(document).on('click', '.cylt_price .cylt2_plist li', function() {
     type = 0;
     $('#fancybox-loading').fadeIn();
     $('.ipro_bg').show();
     $('html').addClass('enabled');
     $('.ipro_tx').show().siblings().hide();
     checkfloat_r();
     setTimeout(function() {
     $('.banner-wrap').slick("slickPause");
     $('.ipro_popup').fadeIn();
     $('.ipro_slick.slick-slider').slick("unslick");
     $('.ipro_slick').slick({
     autoplay: false,
     draggable: false,
     fade: true
     });
     $('#fancybox-loading').hide();
     loadpList(0);
     r_top_bot(1);
     open = true;
     }, 2000);
     });*/
    //点击右边按钮滑到评论区域
    $(document).on('click','.ipd_detail .des span.comment',function() {
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

    //关闭详情
    $('.ipro_close').click(function() {
        if(open == false){ return false;}
        $('.js-contents').html('');
        $('.banner-wrap').slick("slickPlay");
        $('.ipro_popup').animate({ scrollTop: 0 }, 0);
        //获取cookie存储的url
        var originalUrl = cookie.get("originalUrl");
        window.history.pushState({},0,originalUrl);
        $('.ipro_popup,#fancybox-loading').hide();
        $('.ipro_bg').hide();
        $('.ipro_prev').hide();
        $('.ipro_next').hide();
        $('html').removeClass('enabled bottom');
        setTimeout(function() {
            checkfloat_r();
        },200);
    });

    //详情左边点赞
    /*    $('.ipro_left .ipro_zan .icon').click(function() {if($(this).parents('.ipro_zan').hasClass('disabled')){ return false; }
     if ($(this).parents('.ipro_zan').hasClass('on')) {
     $(this).parents('.ipro_zan').removeClass('on');
     } else {
     $(this).parents('.ipro_zan').addClass('on');
     }
     });*/

   var fav_this;
    //详情左边底下评论点赞举报标记
    $(document).on('click', '.ipro_com_icon i', function() {
        if($(this).hasClass('favor')){
            right_favor = false;
            if($(this).hasClass('on')){ return false; }
            fav_this = $(this);
            var comment_id = fav_this.parent(".ipro_com_icon").attr('data-cid');
            var com_html = '<input type="hidden"  class="com_id" value="'+comment_id+'">';
            $('.w100 .com_id  ').replaceWith(com_html);
            var name = $('.ipro_com_txt h3 b').html();
            $('.rep_form .item .rep_name').html(name);
            $('.rep_bg').fadeIn();
            $('html').addClass('report');
            return false;
        }
        if ($(this).hasClass('on')) {
            $(this).removeClass('on');
            /*if($(this).hasClass('zan')){
                $(this).text(parseInt($(this).text()) - 1);
            }*/
            if($(this).hasClass('comment')){
                $(this).parents('li').find('.ipro_com_rbox').fadeOut();
                r_top_bot(1);
            }
        } else {
            $(this).addClass('on');
            /*if($(this).hasClass('zan')){
                $(this).text(parseInt($(this).text()) + 1);
            }*/
            if($(this).hasClass('comment')){
                $(this).parents('li').find('.ipro_com_rbox').fadeIn();
                r_top_bot(1);
            }
        }
    });

    //举报弹窗（确认）
    $(document).on('click','.rep_form .rep_sub',function() {
        var _this = $(this);
        var comment_id = _this.next('.com_id').val();
        var reason = $('#reason').val();
        //alert(reason);
        layer.confirm('确定要举报该评论？', {
            title: false,
            closeBtn: 0,
            btn: ['取 消','确 定'] //按钮
        }, function(){
            layer.closeAll();
        }, function() {
            $.ajax({
                type: 'GET',
                url: report_ajax,
                data: {comment_id:comment_id,reason:reason,type:0},
                dataType: 'json',
                success: function (result) {
                    $('.rep_bg').fadeOut();
                    $('html').removeClass('report');
                    if (right_favor) {
                        fav_right.addClass('on');
                    } else {
                        fav_this.addClass('on');
                    }
                    layer.msg("举报成功");
                },error:function(){
                    layer.msg("网络请求失败，请重试");
                }
            });
        });
    });

    //举报弹窗（取消）
    $('.rep_wrap .del').click(function() {
        $('.rep_bg').fadeOut();
        $('html').removeClass('report');
    });

    //右边举报
    var right_favor = false;
    var fav_right;
    $('.ipd_detail .des span.favor').click(function() {
        if($(this).hasClass('on')){ return false; }
        $('.rep_bg').fadeIn();
        $('html').addClass('report');
        right_favor = true;
        fav_right = $(this);
    });

    //详情右边删除购物袋对应商品
    $('.ipro_right_after .ipd_cadel').click(function() {
        $('.dc_pr').removeClass('dc_show');
        $(this).parents('.dc_pr').addClass('dc_show');
    });

    $('.dc_firm .dc_yes').click(function(event) {
        $(this).parents('li').remove();
    });

    /*    //详情右侧点赞
     $(document).on('click', '.ipd_detail .des span.zan', function(event) {
     var zan_num = $(this).text();
     if($(this).hasClass('on')){
     $(this).removeClass('on');
     $(this).text(parseInt(zan_num) - 1);
     }else{
     $(this).addClass('on');
     $(this).text(parseInt(zan_num) + 1);
     }
     });*/
});
//详情页呈现数据
/*function present(result){
 var author = result['data']['data']['author'];
 $(".ipro_right .text h2").text(author['user_name']);//名字
 //$(".ipro_right .avatar span").attr('data-lazyload-bg',author['headimg']);
 $(".ipro_right .ipd_user .avatar a span").css('background-image','url(/'+author['headimg']+')');//头像

 //class="guanzhu p_no nb_t2 {if $rk.is_user_attention neq 0}followed{/if}">{if $rk.is_user_attention eq 1}已关注{elseif $rk.is_user_attention eq 2 }互相关注{else}关注{/if}</a>
 var attention = result['data']['data']['attention'];
 if(attention != 0){
 $(".ipro_right .ipd_user .text .follow").attr('class','follow guanzhu nb_t2 followed ');//显示已关注的状态
 }

 if(attention == 0){
 $(".ipro_right .ipd_user .text .follow").text('关注');//显示关注
 }else if(attention == 1){
 $(".ipro_right .ipd_user .text .follow").text('已关注');//显示已关注
 $(".ipro_right .ipd_user .text .follow").attr('value',attention);
 }else if(attention == 2){
 $(".ipro_right .ipd_user .text .follow").text('互相关注');//显示互相关注
 $(".ipro_right .ipd_user .text .follow").attr('value',attention);
 }

 //作品信息
 var info = result['data']['data']['info'];
 $(".ipro_wrap").attr('data-rid',info['record_id']);
 $(".ipro_wrap").attr('data-did',info['diy_id']);
 $(".ipro_wrap").attr('data-aid',info['user_id']);
 $(".ipro_com_bot_ul").attr('comment-rid',info['record_id']);//区分评论页用

 $(".ipro_right .ipd_user .text .follow").attr('user_id',info['user_id']);//关注id
 //是否已经点赞
 if(info['if_zan'] == 1){
 $(".ipro_right .des .zan").attr('class','zan on');
 $('.ipro_left .ipro_zan').attr('class','ipro_zan on');
 }

 //是否已投票
 if(result['data']['data']['vote_code']==1){
 //ipd_favar ipd_tp nb_t2 tp_ed disabled
 $(".ipd_rbot_wrap .nb_t2 ").attr('class','ipd_favar ipd_tp nb_t2 tp_ed disabled').html("已投票");

 }

 /!*    if(info['state'] == 0 ){
 var sh = "<p>待审核</p>";
 $(".ipd_user .price").attr('class','price ing').html(sh);
 $(".ipd_rbot_wrap .nb_t5 ").addClass('disabled');
 $(".guanzhu ").addClass('disabled');
 $(".ipro_inner").addClass('disabled');
 $('.ipro_popup').find(".disabled");
 $('.ipd_rbot_wrap .nb_t2').css("display","none");
 }else if(info['state'] == 1){
 var wtg = "<p>未通过</p>";
 $(".ipd_user .price").attr('class','price ing').html(wtg);
 $(".ipd_rbot_wrap .nb_t5 ").addClass('disabled');
 $(".guanzhu ").addClass('disabled');
 $(".ipro_inner").addClass('disabled');
 $('.ipd_rbot_wrap .nb_t2').css("display","none");
 }else{
 var ytg = "<p>已通过</p>";
 $(".ipd_user .price").attr('class','price ing').html(ytg);
 $(".ipd_rbot_wrap .nb_t5 ").removeClass('disabled');
 $(".guanzhu ").removeClass('disabled');
 $(".ipro_inner").removeClass('disabled');
 $('.ipd_rbot_wrap .nb_t2').css("display","");
 }*!/
 $(".ipro_com_bot_ul").html('');



 $(".ipro_right .des .zan").text(info['zan_num'])//点赞数
 $(".ipro_right .piaoshu").text(info['vote_num'])//票数
 $(".ipro_right .des .comment").text(info['comment_num'])//评论数
 $(".ipro_com_bot_pf").text("评论("+info['comment_num']+")")//评论区的评论数
 $(".ipro_right .des .view").text(info['click_count'])//浏览量
 $(".ipro_right .ipd_detail .tag_list h2").text(info['title']);//标题
 $(".ipro_right .ipd_detail .tag_list a").text(info['types']);//类型
 $(".ipro_right .ipd_detail .time").text('发布时间：'+formatDate(parseInt(info['add_time']+'000')));//时间
 $(".ipro_right .ipd_info .box p").text(info['describe']);//描述
 $(".ipro_right .ipd_tag div a").text(info['tags_str']);//标签

 //名片
 $(".ipro_right .avatar_box .ava_top a .ava_pic").css('background-image','url(/'+author['headimg']+')');//标签
 $(".ipro_right .avatar_box .ava_top .ava_txt h2").text(author['user_name']);//名字;
 $(".ipro_right .avatar_box .ava_bot li:eq(0) a font").text(author['shitr_count']);//T恤数 ;
 $(".ipro_right .avatar_box .ava_bot li:eq(1) a font").text(author['attention_count']);//粉丝数 ;
 $(".ipro_com_detail .author_name").text(author['user_name']+':');//回复区作者名字

 //详情图片1
 var img_box1 = $(".ipro_main .ipro_slick");
 var html_banner="";
 var imgs_banner = result['data']['data']['img_two'];
 /!* var html_banner = '<li class="ipro_zoom"><img class="o_img" src="/../../qdshop/public/static/home/default1/images/pic11.jpg"></li>' +
 '<li class="ipro_zoom"><img class="o_img" src="/../../qdshop/public/static/home/default1/images/pic10.jpg"></li>';*!/
 for(var i=0;i<imgs_banner.length;i++){
 html_banner +="<li class='ipro_zoom'><img class='o_img' src='/"+imgs_banner[i]+"'></li>";
 }

 img_box1.html(html_banner);

 //详情图片2
 var img_box2 = $(".ipro_piclist");
 var html="";
 var imgs_arr = result['data']['data']['img_two'];
 for(var i=0;i<imgs_arr.length;i++){
 html +="<div class='box'><img realsrc='/"+imgs_arr[i]+"' class='lazybg' data-lazyload-img='/"+imgs_arr[i]+"' data-lazyload-suc='true' src='/"+imgs_arr[i]+"'></div>";
 }
 img_box2.html(html);

 //推荐作品
 var product__box = $(".ipro_com .cylt2_box .cylt2_plist");
 var html="";
 var product_arr = result['data']['data']['product_three'];
 for(var i=0;i<product_arr.length;i++){
 html +="<li><a href='"+product_arr[i]['record_id']+"'><div class='pic'><img src='/"+product_arr[i]['img']+"'></div> <div class='text clearfix'><span class='tover'>"+product_arr[i]['title']+"</span> <font>"+product_arr[i]['vote_count']+"票</font></div></a></li>";
 }
 product__box.html(html);
 //var pages = 1;
 alert(pages);
 //pages = 1;

 return true;
 }*/
//弹窗需要加载的数据
/*function popup_load(){
 $('.ipro_bg').show();
 $('html').addClass('enabled');
 $('.ipro_tx').show().siblings().hide();
 checkfloat_r();

 $('.banner-wrap').slick("slickPause");
 $('.ipro_popup-' + record).fadeIn();
 $('.lazybg').lazyload({d:300,r:250});
 $('.ipro_slick.slick-slider').slick("unslick");
 $('.ipro_slick').slick({
 autoplay: false,
 draggable: false,
 fade: true
 });
 $('#fancybox-loading').hide();
 loadpList(0);
 r_top_bot(1);
 open = true;

 }*/


//详情弹窗
var ipro_prev;//上一页
var ipro_next;//下一页
var popup_url = "details/index/record/";
function productClick($key,$record_id, index){
    $('.js-contents').html('');
    $('#fancybox-loading').fadeIn();
    var url = popup_url+$record_id;
    //alert(index+','+this_count);
    cookie.set("originalUrl",window.location.href,1);
    window.history.pushState({},0,'http://'+window.location.host+'/'+url);
    if($('.ipro_popup-'+$record_id).html() == ''){
        var open = false;
        $.ajax({
            type: 'GET',
            url: popup_ajax,
            data: {key: $key,record:$record_id},
            dataType: 'json',
            success: function (result) {
                $(".ipro_com_bot_ul ").attr("comment-rid",$record_id);
                // layer.close(index);
                $('.ipro_popup-'+$record_id).append(result);
                //$('#fancybox-loading').hide();
                // present(result);//呈现数据函数

                socialShare(".social-share, .share-component");
                ipro_prev=index-1;
                ipro_next=index+1;
                $('.ipro_prev span').text(ipro_prev);
                $('.ipro_next span').text(ipro_next);

                //alert(this_count);
                if(ipro_next < this_count) {
                    if ($('.ipro_next').is(":hidden")) {
                        $('.ipro_next').show();
                    }
                }else{
                    if($('.ipro_next').is(":hidden")){
                        $('.ipro_next').hide();
                    }
                }

                if(ipro_prev > -1){
                    if($('.ipro_prev').is(":hidden")){
                        $('.ipro_prev').show();
                    }
                }else{
                    if($('.ipro_prev').is(':visible')){
                        $('.ipro_prev').hide();
                    }
                }

                $('.ipro_bg').show();
                $('html').addClass('enabled');
                $('.ipro_tx').show().siblings().hide();
                checkfloat_r();

                $('.banner-wrap').slick("slickPause");
                $('.ipro_popup-'+$record_id).fadeIn();
                $('.lazybg').lazyload({d:300,r:250});
                $('.ipro_popup-'+$record_id+ ' .ipro_zoom').zoombie({ on: 'click' });
                $('.ipro_popup-'+$record_id+ ' .ipro_main li').click(function() {
                    if ($('.ipro_popup-'+$record_id+' .ipro_main').hasClass('hover')) {
                        $('.ipro_popup-'+$record_id+' .ipro_main').removeClass('hover');
                    } else {
                        $('.ipro_popup-'+$record_id+' .ipro_main').addClass('hover');
                    }
                });
                $('.ipro_slick.slick-slider').slick("unslick");
                $('.ipro_slick').slick({
                    autoplay: false,
                    draggable: false,
                    fade: true
                });
                $('#fancybox-loading').hide();
                loadpList(0);
                r_top_bot(1);
                open = true;
                return false;

            },
            error:function(){
                $('#fancybox-loading').hide();
                layer.msg("网络请求失败，请重试");
                return false;
            }
        });
    }
    setTimeout(function(){
        $(".ipro_com_bot_ul ").attr("comment-rid",$record_id);
        ipro_prev=index-1;
        ipro_next=index+1;
        $('.ipro_prev span').text(ipro_prev);
        $('.ipro_next span').text(ipro_next);

        if(ipro_next < this_count) {
            if ($('.ipro_next').is(":hidden")) {
                $('.ipro_next').show();
            }
        }else{
            if($('.ipro_next').is(":hidden")){
                $('.ipro_next').hide();
            }
        }

        if(ipro_prev > -1){
            if($('.ipro_prev').is(":hidden")){
                $('.ipro_prev').show();
            }
        }else{
            if($('.ipro_prev').is(':visible')){
                $('.ipro_prev').hide();
            }
        }

        // index_comment($record_id);

        $('.ipro_bg').show();
        $('html').addClass('enabled');
        $('.ipro_tx').show().siblings().hide();
        checkfloat_r();

        $('.banner-wrap').slick("slickPause");
        $('.ipro_popup-'+$record_id).fadeIn();
        $('.lazybg').lazyload({d:300,r:250});
        //$("[class='ipro_inner disabled']").hide();
        $('.dot').dotdotdot({
            wrap: 'letter',
            watch: 'window'
        });
        $('.ipro_slick.slick-slider').slick("unslick");
        $('.ipro_slick').slick({
            autoplay: false,
            draggable: false,
            fade: true
        });
        $('#fancybox-loading').hide();
        loadpList(0);
        r_top_bot(1);
        open = true;
    },1000);





}


//详情弹窗 上一个
function switch_prev(){
    $('.js-contents').html('');
    $('.ipro_popup').hide();
    var open = false;
    var key = $('.ipro_prev span').text();
    // var keyClass = 'diy'+key;
    // var record = $("."+keyClass).text();
    var record = $('.porduct_li').eq(key).find('.diy').html();
    //alert(key);
    var url = popup_url+record;
    // cookie.set("originalUrl",window.location.href,1);
    $('#fancybox-loading').fadeIn();
    window.history.pushState({},0,'http://'+window.location.host+'/'+url);
    if($('.ipro_popup-'+record).html() == ''){
        $('html').removeClass('enabled bottom');
        $.ajax({
            type: 'GET',
            url: popup_ajax,
            data: {key: key,record: record},
            dataType: 'json',
            success: function (result) {
                $(".ipro_com_bot_ul ").attr("comment-rid",record);
                $('.ipro_popup-'+record).append(result);
                // present(result);//呈现数据函数

                socialShare(".social-share, .share-component");

                ipro_prev=parseInt(key)-1;
                ipro_next=parseInt(key)+1;
                $('.ipro_prev span').text(ipro_prev);
                $('.ipro_next span').text(ipro_next);

                if(ipro_prev <= -1){
                    $('.ipro_prev').hide();
                }
                if(ipro_next < this_count){
                    $('.ipro_next').show();
                }

                //index_comment(record);

                $('.ipro_bg').show();
                $('html').addClass('enabled');
                $('.ipro_tx').show().siblings().hide();
                checkfloat_r();

                $('.banner-wrap').slick("slickPause");
                $('.ipro_popup-'+record).fadeIn();
                $('.lazybg').lazyload({d:300,r:250});
                $('.ipro_popup-' + record+ ' .ipro_zoom').zoombie({ on: 'click' });
                $('.ipro_popup-' + record+ ' .ipro_main li').click(function() {
                    if ($('.ipro_popup-' + record+ ' .ipro_main').hasClass('hover')) {
                        $('.ipro_popup-' + record+ ' .ipro_main').removeClass('hover');
                    } else {
                        $('.ipro_popup-' + record+ ' .ipro_main').addClass('hover');
                    }
                });
                $('.ipro_slick.slick-slider').slick("unslick");
                $('.ipro_slick').slick({
                    autoplay: false,
                    draggable: false,
                    fade: true
                });
                $('#fancybox-loading').hide();
                loadpList(0);
                r_top_bot(1);
                open = true;
            }
        });
    }
    setTimeout(function(){
        $(".ipro_com_bot_ul ").attr("comment-rid",record);
        ipro_prev=parseInt(key)-1;
        ipro_next=parseInt(key)+1;
        $('.ipro_prev span').text(ipro_prev);
        $('.ipro_next span').text(ipro_next);

        if(ipro_prev <= -1){
            $('.ipro_prev').hide();
        }
        if(ipro_next < this_count){
            $('.ipro_next').show();
        }

        //index_comment(record);

        $('.ipro_bg').show();
        $('html').addClass('enabled');
        $('.ipro_tx').show().siblings().hide();
        checkfloat_r();

        $('.banner-wrap').slick("slickPause");
        $('.ipro_popup-'+record).fadeIn();
        $('.lazybg').lazyload({d:300,r:250});
        $('.ipro_slick.slick-slider').slick("unslick");
        $('.ipro_slick').slick({
            autoplay: false,
            draggable: false,
            fade: true
        });
        $('#fancybox-loading').hide();
        loadpList(0);
        r_top_bot(1);
        open = true;
    },1000)





}
//详情弹窗 下一个
function switch_next(){
    $('.js-contents').html('');
    $('.ipro_popup').hide();
    $('#fancybox-loading').fadeIn();
    var open = false;
    var key = $('.ipro_next span').text();
    // var keyClass = 'diy'+key;
    // var record = $("."+keyClass).text();
    var record = $('.porduct_li').eq(key).find('.diy').html();
    var url = popup_url+record;
    // cookie.set("originalUrl",window.location.href,1);
    window.history.pushState({},0,'http://'+window.location.host+'/'+url);
    //$('.ipro_popup,#fancybox-loading').hide();
    if($('.ipro_popup-'+record).html() == ''){
        $('html').removeClass('enabled bottom');
        //alert(key);
        $.ajax({
            type: 'GET',
            url: popup_ajax,
            data: {key: key, record: record},
            dataType: 'json',
            success: function (result) {
                //present(result);//呈现数据函数
                $(".ipro_com_bot_ul ").attr("comment-rid",record);
                $('.ipro_popup-' + record).append(result);
                // present(result);//呈现数据函数

                socialShare(".social-share, .share-component");

                ipro_prev = parseInt(key) - 1;
                ipro_next = parseInt(key) + 1;
                $('.ipro_prev span').text(ipro_prev);
                $('.ipro_next span').text(ipro_next);

                if (ipro_prev >= 0) {
                    $('.ipro_prev').show();
                }
                if (ipro_next >= this_count) {
                    $('.ipro_next').hide();
                }

                //index_comment(record);

                $('.ipro_bg').show();
                $('html').addClass('enabled');
                $('.ipro_tx').show().siblings().hide();
                checkfloat_r();

                $('.banner-wrap').slick("slickPause");
                $('.ipro_popup-' + record).fadeIn();
                $('.lazybg').lazyload({d:300,r:250});
                $('.ipro_popup-' + record+ ' .ipro_zoom').zoombie({ on: 'click' });
                $('.ipro_popup-' + record+ ' .ipro_main li').click(function() {
                    if ($('.ipro_popup-' + record+ ' .ipro_main').hasClass('hover')) {
                        $('.ipro_popup-' + record+ ' .ipro_main').removeClass('hover');
                    } else {
                        $('.ipro_popup-' + record+ ' .ipro_main').addClass('hover');
                    }
                });
                $('.ipro_slick.slick-slider').slick("unslick");
                $('.ipro_slick').slick({
                    autoplay: false,
                    draggable: false,
                    fade: true
                });
                $('#fancybox-loading').hide();
                loadpList(0);
                r_top_bot(1);
                open = true;
            }
        });
    }
    setTimeout(function(){
        $(".ipro_com_bot_ul ").attr("comment-rid",record);
        ipro_prev=parseInt(key)-1;
        ipro_next=parseInt(key)+1;
        $('.ipro_prev span').text(ipro_prev);
        $('.ipro_next span').text(ipro_next);

        if(ipro_prev >= 0){
            $('.ipro_prev').show();
        }
        if(ipro_next >= this_count){
            $('.ipro_next').hide();
        }

        $('.ipro_bg').show();
        $('html').addClass('enabled');
        $('.ipro_tx').show().siblings().hide();
        checkfloat_r();

        $('.banner-wrap').slick("slickPause");
        $('.ipro_popup-' + record).fadeIn();
        $('.lazybg').lazyload({d:300,r:250});
        $('.ipro_slick.slick-slider').slick("unslick");
        $('.ipro_slick').slick({
            autoplay: false,
            draggable: false,
            fade: true
        });
        $('#fancybox-loading').hide();
        loadpList(0);
        r_top_bot(1);
        open = true;

    },1000)

}
//详情评论下拉加载
//var pages = 1;//翻页
var c_load = false;
$('.lazybg').lazyload({d:300,r:250});
$('.ipro_popup').scroll(function () {
    var iHeight = $('.ipro_popup .ipro_inner').height();
    var iscrollTop = $('.ipro_popup').scrollTop();
    //var count = parseInt(page_count);
    // 如果滚动条到达底部就获取新数据追加到容器
    if (iscrollTop >= iHeight - $(window).height() * 1.2 && c_load == false) {
        $('.ipro_com_bot .base-load').addClass('show');
        var records = $(".ipro_com_bot_ul ").attr("comment-rid");
        console.log(records);
        var pages =$("#record_"+records).val();
        var count = $(".ipro_com_bot_pf").text();
        c_load = true;
        if ($('.ipro_com_bot_ul li').length > count) {
            setTimeout(function () {
                c_load = false;
                $('.ipro_com_bot .base-load').removeClass('show');
                //layer.msg("没有更多数据了");
            }, 2000);
            return false;
        }
        $.ajax({
            type: 'GET',
            url: comment_list,
            data: {is_comment_ajax: 1, pages: pages,record:records},
            dataType: 'html',
            success: function (result) {
                //alert(result);
                pages++;
                $("#record_"+records).val(pages);
                $("ul[comment-rid='"+records+"']").append(result);
                $('.lazybg').lazyload({d:300,r:250});
                $('.ipro_com_bot .base-load').removeClass('show');
                c_load = false;

            }
        });

    }
});


var cookie = {
    set:function(key,val,time){//设置cookie方法
        var date=new Date(); //获取当前时间
        var expiresDays=time;  //将date设置为n天以后的时间
        date.setTime(date.getTime()+expiresDays*24*3600*1000); //格式化为cookie识别的时间
        document.cookie=key + "=" + val +";expires="+date.toGMTString();  //设置cookie
    },
    get:function(key){//获取cookie方法
        /*获取cookie参数*/
        var getCookie = document.cookie.replace(/[ ]/g,"");  //获取cookie，并且将获得的cookie格式化，去掉空格字符
        var arrCookie = getCookie.split(";")  //将获得的cookie以"分号"为标识 将cookie保存到arrCookie的数组中
        var tips;  //声明变量tips
        for(var i=0;i<arrCookie.length;i++){   //使用for循环查找cookie中的tips变量
            var arr=arrCookie[i].split("=");   //将单条cookie用"等号"为标识，将单条cookie保存为arr数组
            if(key==arr[0]){  //匹配变量名称，其中arr[0]是指的cookie名称，如果该条变量为tips则执行判断语句中的赋值操作
                tips=arr[1];   //将cookie的值赋给变量tips
                break;   //终止for循环遍历
            }
        }
        return tips;
    },
    delete:function(key){ //删除cookie方法
        var date = new Date(); //获取当前时间
        date.setTime(date.getTime()-10000); //将date设置为过去的时间
        document.cookie = key + "=v; expires =" +date.toGMTString();//设置cookie
    }
}

//浏览器回退关闭详情
$(window).on('popstate', function() {
    //window.location.href = document.referrer;//回退刷新
    if(open == false){ return false;}
    $('.banner-wrap').slick("slickPlay");
    $('.ipro_popup').animate({ scrollTop: 0 }, 0);
    //获取cookie存储的url
    var originalUrl = cookie.get("originalUrl");
    window.history.pushState({},0,originalUrl);
    $('.ipro_popup,#fancybox-loading').hide();
    $('.ipro_bg').hide();
    $('.ipro_prev').hide();
    $('.ipro_next').hide();
    $('html').removeClass('enabled bottom');
    setTimeout(function() {
        checkfloat_r();
    },200);
})

//时间转换
function   formatDate(now)   {
    var   now= new Date(now);
    var   year=now.getFullYear();
    var   month=now.getMonth()+1;
    var   date=now.getDate();
    return   year+"-"+fixZero(month,2)+"-"+fixZero(date,2);
}
//时间如果为单位数补0
function fixZero(num,length){
    var str=""+num;
    var len=str.length;     var s="";
    for(var i=length;i-->len;){
        s+="0";
    }
    return s+str;
}




/*   $(function(){
 $('.lt_bg .mep_sbtn').click(function(){
 if($('.upload_box li.uploading').length != 0){
 layer.msg("还有图片在上传中，请稍等！")
 return false;
 }
 var title = $('#title').val();
 var type  = $('#type').find("option:selected").val();
 var describe  = $('#describe').val();

 var tags  = $('#tags dd');
 var img = $('.clearfix .uploaded');

 var tagsArray = new Array();
 var imgArray = new Array();

 for(var i = 0;tags.length > i;i++ ){
 tagsArray[i]=tags.eq(i).text();
 }
 for(var i = 0;img.length > i;i++ ){
 imgArray[i]=img.eq(i).css("background-image").replace('url("','').replace('")');
 }
 //alert(imgArray);
 //console.log(imgArray);

 $.ajax({
 url:"{:url('Originality/from_ajax')}",
 type:"POST",
 data:{diy:diy_id,title:title,type:type,describe:describe,tags:tagsArray,imgs:imgArray},
 success: function(e){
 console.log(e);
 layer.msg("提交成功");
 $('.cbg_bg').fadeOut();
 },
 error: function(){
 layer.msg('网络请求失败，请稍后重试');
 }
 })

 })
 })*/

