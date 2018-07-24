$(document).ready(function() {
    var urlstr = location.href;
    var urlstatus = false;
    $('.l_menu dd a').each(function () {
        if ((urlstr + '/').indexOf($(this).attr('href')) > -1 && $(this).attr('href') != '') {
            $(this).parent('dd').addClass('on');
            urlstatus = true;
        } else {
            $(this).parent('dd').removeClass('on');
        }
    });


    $('input, textarea').placeholder();
    $('.select').selectOrDie();
    $('.lazybg').lazyload({d:300,r:250});
    $('.dot').dotdotdot({
        wrap: 'letter',
        watch: 'window'
    });

    $(".nav li").each(function() {
        if($(this).find("dl").length > 0){
            var width = $(this).find('dl').width() + 40;
            $(this).find('dl').css("margin-left",-width/2);
        }
    });
    var timeout=null;
    $(".header .nav li").mouseenter(function(event) {
        clearTimeout(timeout);
        $(this).siblings('li').find('dl,.snav_main').stop(true,true).slideUp(300)
        if($('.header').hasClass('second')){
            $(this).find('dl,.snav_main').stop(true,true).slideDown(300);
        }
    });
    $(".header .nav li").mouseleave(function(event) {
        var _this=$(this);
        timeout=setTimeout(function(argument) {
            _this.find('dl,.snav_main').stop(true,true).slideUp(300);
        }, 250)
    });

    $('.header .nav li.design').hover(function() {
        $(this).removeClass('out').addClass('in');
    }, function() {
        $(this).removeClass('in').addClass('out');
    });

    var f = 32;
    var s = 388;
    var t = $(".header");
    if($('.header').hasClass('fixed')){
        $('body').addClass('pt_header');
    }else{
        $('body').removeClass('pt_header');
        parseInt($(window).scrollTop()) > parseInt(f) ? t.addClass("first") : t.removeClass("first");
        parseInt($(window).scrollTop()) > parseInt(s) ? t.addClass("second") : t.removeClass("second");
    }

    $(window).scroll(function(){
        if($('.header').hasClass('fixed')){ return false; }
        $(window).scrollTop() > parseInt(f) ? t.addClass("first") : t.removeClass("first");
        $(window).scrollTop() > parseInt(s) ? t.addClass("second") : t.removeClass("second");
    });
    $('.rl_main,.forget_main').css({"min-height":$(window).height() - $('.header').outerHeight() - $('.footer').height()});
    checkHeight();
    $(window).resize(function() {
        $('.rl_main,.forget_main').css({"min-height":$(window).height() - $('.header').outerHeight() - $('.footer').height()});
        if($('.mem_left').length != 0){ checkHeight(); }
    });

    //置顶
    $('.float_right,.s_float_right').click(function() {
        $('html,body,.ipro_popup').stop().animate({ scrollTop: 0 }, 300);
    });

    //滚动条
    $('.sod_select .sod_list').mCustomScrollbar({
        axis: "y",
        scrollInertia: 100,
        scrollButtons: {
            enable: true,
            scrollSpeed: 20
        },
        theme: "3d"
    });

    checkfloat_r();
    $(window).scroll(function() {
        if($(window).scrollTop() > $(window).height() / 4) {
            $('.float_right').show();
        }else{
            $('.float_right').hide();
        }
    });
    $('body').on('scroll', '.ipro_popup', function() {
        if($('.ipro_popup').scrollTop() > $(window).height() / 4){
           $('.float_right').show();
        }else{
            $('.float_right').hide();
        }
    });

    //header购物袋
    /*var type = 0;
    $('.hcart_con dd .box5 .del').click(function() {
        $('.dc_pr').removeClass('dc_show');
        $(this).parents('.dc_pr').addClass('dc_show');
        type = 999;
    });

    $('.dc_firm .dc_yes').click(function(event) {
        if(type == 999){
            $(this).parents('dd').remove();
            type = 0;
        }
    });*/
    $('.hcart_con dd .box5 .del').click(function() {
        $('.dc_pr').removeClass('dc_show');
        $(this).parents('.dc_pr').addClass('dc_show');
    });

    $(document).on("click",function(e){
        if($(e.target).closest(".dc_pr").length == 0){
            $('.dc_pr').removeClass('dc_show');
            $('.ipro_popup').removeClass('visible');
        }
    });

    $(document).on('click', '.dc_firm .dc_no', function(event) {
        $(this).parents('.dc_pr').removeClass('dc_show');
        $('.ipro_popup').removeClass('visible');
    });

    //hover图片
    // $('.act_box4 .act_list li').hover(function() {
    //     var onsrc = $(this).find('img').attr("onsrc");
    //     $(this).find('img').attr("src",onsrc);
    // }, function() {
    //     var orsrc = $(this).find('img').attr("orsrc");
    //     $(this).find('img').attr("src",orsrc);
    // });

    //显示隐藏私信
    $(document).on('click','.ava_talk,.d_showtalk',function() {
        $('body > .p_y').stop(true,true).fadeIn();
        //拼接数据
        //alert($(this).attr('class'));
        if($(this).attr('class') == "ava_talk author nb_t1"){//实时排行榜
            var name =  $(this).parent('p').siblings('a').find('h4').html();
            var headimg = $(this).parent().parent().prev('.p_img').find('.lazybg').attr('src');
            var user_id = $(this).prev('a').attr('user_id');
        }else{
            var headimg = $(this).siblings('a').find('.lazybg').attr('data-lazyload-bg');
            var name = $(this).siblings('.ava_txt').find('h2').html();
            var user_id  = $(this).siblings('#pre_user_id').val();
        }
        $('#a_id').val(user_id);
        $('.p_ycon2b .p_img img').attr('src',headimg);
        $('.p_ycon2b .p_img p').html(name);
        $('.p_ycon2b').append(html);

        $('html').addClass('report');
    });
    $('.p_pclo1').click(function() {
        $('body > .p_y').stop(true,true).fadeOut();
        $('html').removeClass('report');
    });

    //我要出售滑动
    $('body').on('click', '.know_more', function() {
        var left_height = $('.mem_wrap').height();
        var comm_height = $('.mep_bot').height();
        $('.ipro_popup').stop().animate({ scrollTop: left_height - comm_height }, 300);
    });

    //删除OTEE弹窗
    $('.del_ot_wrap .del,.del_ot_wrap .del_ot_can').click(function() {
        $('.del_ot_bg').fadeOut();
    });

    //取消关注
    $('.nb_t2').hover(function() {
        if($(this).hasClass('followed')){
            $(this).text("取消关注");
        }
    },function(){
        if($(this).hasClass('followed')){
            if($(this).attr('value') == 1){
                $(this).text("已关注");
            }else{
                $(this).text("互相关注");
            }
        }
    });

    /**
     * 关注用户/取消关注用户
     */
    $('body').on('click','.guanzhu',function() {
        var be_user_id = $(this).attr("user_id");
        var user_id    = $('#user_id').val();
        var _this      = $(this);
        // var _fansi     = $(this).parent().parent().find(".fansi");
        var _fansi     = $('.fansi');
        var url = "/qdapi/?act=user/add_user_attention&debug=1&version=1&user_id="+user_id+"&be_user_id="+be_user_id;
        $.get(url, function(result){
            layer.msg(result.data.message);
            if(result.code == 200){
                if(result.data.is_add > 0){
                    _fansi.html(parseInt(_fansi.html())+1);
                    _this.attr('value',1);
                    _this.addClass('followed').html("取消关注");
                }else{
                    _fansi.html(_fansi.html()-1);
                    _this.attr('value',0);
                    _this.removeClass('followed').html("关注");
                }
            } else {
                layer.msg(result.message);
            }
        });
    });


    //详情左边展开分享按钮
    /*$('.ipro_share').hover(function() {
        if($(this).hasClass('disabled')){ return false; }
        if ($(this).hasClass('on')) {
            $(this).removeClass('on');
        } else {
            $(this).addClass('on');
        }
    });*/

    $(document).on('hover','.ipro_share',function() {
        if($(this).hasClass('disabled')){ return false; }
        if ($(this).hasClass('on')) {
            $(this).removeClass('on');
        } else {
            $(this).addClass('on');
        }
    });


    //错误点击清空input
    $('body').on('click', '.item i.state', function() {
        if($(this).parents('.item').hasClass('wrong')){
            $(this).parents('.item').find('input').val("").focus();
            $(this).parents('.item').removeClass('wrong');
        }
        if($(this).parents('.item').find('.w50').hasClass('wrong')){
            $(this).parents('.item').find('input').val("").focus();
            $(this).parents('.item').find('.w50').removeClass('wrong');
        }
    });
});

function getScrollbarWidth() {
    var oP = document.createElement('p'),
        styles = {
            width: '100px',
            height: '100px'
        }, i, clientWidth1, clientWidth2, scrollbarWidth;
    for (i in styles) oP.style[i] = styles[i];
    document.body.appendChild(oP);
    clientWidth1 = oP.clientWidth;
    oP.style.overflowY = 'scroll';
    clientWidth2 = oP.clientWidth;
    scrollbarWidth = clientWidth1 - clientWidth2;
    oP.parentNode.removeChild(oP);
    return scrollbarWidth;
}
function checkHeight(){
    if($('.mem_left').length == 0){ return false; }
    $('.mem_left').attr("min-height",$('.mem_left').height());
    $('.mem_right').attr("min-height",$('.mem_right').height());

    if(parseFloat($('.mem_left').attr("height")) > parseFloat($('.mem_right').attr("height"))){
        $('.mem_right').height($('.mem_left').attr("height") );
    }
}
function formatNum(str) {
    var newStr = "";
    var count = 0;

    if (str.indexOf(".") == -1) {
        for (var i = str.length - 1; i >= 0; i--) {
            if (count % 3 == 0 && count != 0) {
                newStr = str.charAt(i) + "," + newStr;
            } else {
                newStr = str.charAt(i) + newStr;
            }
            count++;
        }
        str = newStr + ".00"; //自动补小数点后两位
    } else {
        for (var i = str.indexOf(".") - 1; i >= 0; i--) {
            if (count % 3 == 0 && count != 0) {
                newStr = str.charAt(i) + "," + newStr;
            } else {
                newStr = str.charAt(i) + newStr; //逐个字符相接起来
            }
            count++;
        }
        str = newStr + (str + "00").substr((str + "00").indexOf("."), 3);
        return str;
    }
}
function checkfloat_r(){
    if($('html').hasClass('enabled')){
        if($('.ipro_popup').scrollTop() > ($(window).height() / 4)){
           $('.float_right').show();
        }else{
            $('.float_right').hide();
        }
    }else{
        if($(window).scrollTop() > $(window).height() / 4) {
            $('.float_right').show();
        }else{
            $('.float_right').hide();
        }
    }
}

// 删除购物车一个商品
function delCart(rec_id, obj){
    $('.dc_pr').removeClass('dc_show');
    var index = layer.load(0, {
        shade: [0.1,'#fff']
    });
    $.ajax({
        type: "get",
        url: "/Cart/deleteCart",
        dataType: 'json',
        data:{rec_id:rec_id},
        success: function(data){
            layer.close(index);
            if(data.code == 200){
                layer.msg('删除成功');
                $(obj).parents('dd').remove();
                location.reload();
            } else {
                layer.msg(data.message);
            }
        }
    });
}

//收藏一个商品
function collectOne(goods_id){
    $.ajax({
        type: "get",
        url: "/Goods/collectGoods",
        dataType: 'json',
        data:{goods_id:goods_id},
        success: function(data){
            if(data.status ==200){
                layer.msg(data.message);
            }
        }
    });
}

/**
 * AJAX请求封装函数
 * @param  {String}      url    请求地址
 * @param  {String|Json} data   请求参数
 * @param  {Function}    refun  返回方法
 * @param  {String}      method 请求传输类型
 * @param  {String}      format 请求数据类型
 * @param  {Function}    errfun 错误返回方法
 */
function ajaxCall(url, data, refun, method, format, errfun){
    if(!method){
        method = 'post';
    }
    if(!format){
        format = 'json';
    }
    if(!errfun){
        errfun = function(){};
    }

    $.ajax({
        type: method,
        url: url,
        data: data,
        dataType: format,
        success: refun,
        error: errfun
     });
}