$(function(){
    //详情左边图片切换+放大镜
    $('.ipro_slick').slick({
        autoplay: false,
        draggable: false,
        fade: true
    });
    $('.ipro_slick').on('beforeChange', function(event, slick, currentSlide, nextSlide) {
        $('.ipro_main').removeClass('hover');
    });
    $('.ipro_zoom').zoombie({ on: 'click' });
    $('.ipro_main li').click(function() {
        if ($('.ipro_main').hasClass('hover')) {
            $('.ipro_main').removeClass('hover');
        } else {
            $('.ipro_main').addClass('hover');
        }
    });
    $(document).on('click','.ipro_main_btn',function() {
        var goto_num = $('.ipro_slick li').length - 1;
        $('.ipro_slick').slick("slickGoTo", goto_num);
    });

    //显示右边尺码
    $(document).on('click','.ipd_type .size_box .show_size',function() {
        $('.ipro_wrap .ipro_left,.ipro_wrap .ipro_right').hide();
        $('.ipro_wrap').addClass('size_in');
        $('.ipro_popup').stop().animate({ scrollTop: 0 }, 0);
        $('html,.ipro_popup').stop().animate({ scrollTop: 0 }, 0);
        $('html').addClass('size');
    });

    //隐藏右边尺码
    $(document).on('click','.ipro_back',function() {
        $('.ipro_wrap .ipro_left,.ipro_wrap .ipro_right').show();
        $('.ipro_wrap').removeClass('size_in');
        $('html').removeClass('size');
        $('.ipro_right_bef .ipd_rtop').css("height","auto")
        r_top_bot(1);
    });

    //详情右边显示购物袋
    $(document).on('click','.ipd_cart',function() {
        if($(this).hasClass('disabled')){ return false; }
        var index = layer.load(0, {
            shade: [0.1,'#fff']
        });
        var goods_id = $("input[name='goods_id']").val();
        var quick = 0;
        var quick = (typeof (quick) == "undefined") ? 0 : parseInt(quick);
        var is_design = 0;  //diy设计商品
        var rec_id = new Array();
        var number = parseInt($("input[name='number']").val());//购买数量
        //获取属性值
        var chk_value = new Array();
        $('.ipd_type').find('.r_box input:checked').each(function(){
            chk_value.push($(this).val());
        });
        $.ajax({
            type: "post",
            url: "/index.php/cart/addToCart.html",
            dataType: 'json',
            data:{goods_id:goods_id,quick:quick,attr_id:chk_value,number:number,is_design:is_design},
            success: function(result){
                if(result.code == 401){
                    layer.close(index);
                    layer.open({
                        content: result.message,
                        btn: ['确定', '取消'],
                        shadeClose: false,
                        yes: function(index){
                            window.location.href = '/index.php/user/login.html';
                        }
                    });
                }else{
                    if (result.code == 200) {
                        $.ajax({
                            url: '/index.php/cart/get_cart_goods.html',
                            type: 'GET',
                            dataType: 'html',
                            // data: {param1: 'value1'},
                            success: function(html) {
                                layer.close(index);
                                $('.ipro_right_after').remove();
                                $('.ipro_right_bef').after(html);
                                $('.header .state .cart').text(parseInt($('.header .state .cart').text()) + number);
                                $('.ipro_right_bef').hide();
                                $('.ipro_right').addClass('show_bag');
                                $('html').addClass('bag');
                                r_top_bot(1);
                                $('.lazybg').lazyload({d:300,r:250});
                                count_num();
                            }
                        });
                    } else {
                        layer.close(index);
                        layer.msg(result.message);
                    }
                }
            }
        });
    });

    //详情右边隐藏购物袋
    $(document).on('click','.ipro_right_after .ipd_rtop_back',function(){
        $('.ipro_right').removeClass('show_bag');
        $('html').removeClass('bag');
        $('.ipro_right_bef').show();
        r_top_bot(1);
    });

    //详情右边改变款式
    function getId() {
        var goods_attr_id = $('.ipro_right .sex_box input:checked').val();
        var goods_attr_id2 = $('.ipro_right .color_box input:checked').val();
        if (goods_attr_id !== undefined && goods_attr_id2 !== undefined) {
            var attr_id = goods_attr_id + goods_attr_id2;
            var i=$('.slick-initialized li[data-id='+attr_id+']').index();
            console.log(attr_id);
            console.log(i);
            $('.ipro_slick').slick('slickGoTo',i);
        }
    }
    $(document).on('change','.ipro_right .sex_box input',function(){
        // $(this).parents('label').addClass('on').siblings().removeClass('on');
        $(this).parents('label').css('background-image','url('+$(this).parents("label").attr("data-srcon")+')').addClass('on').siblings().removeClass('on').each(function(argument) {
           $(this).css('background-image','url('+$(this).attr("data-src")+')');
        });
        getId();
    });
    //详情右边改变颜色
    $(document).on('change','.ipro_right .color_box input',function(){
        $(this).parents('label').addClass('on').siblings().removeClass('on');
        getId();
    });
    //详情右边改变尺码
    $(document).on('change','.ipro_right .size_box input',function(){
        $(this).parents('label').addClass('on').siblings().removeClass('on');
    });
    //详情右边改变数量
    $(document).on('click','.ipro_right .btn_a',function(){
        var num = parseInt($(this).parents('.num_box').find('.btn_input').val());
        var max = parseInt($(this).parents('.num_box').attr("data-max"));
        if (num >= max) { return false; }
        num++;
        $(this).parents('.num_box').find('.btn_input').val(num);
    });
    $(document).on('click','.ipro_right .btn_m',function(){
        var num = parseInt($(this).parents('.num_box').find('.btn_input').val());
        if (parseInt(num) <= 1 || num == "") {
            num = 1;
        } else {
            num--;
        }
        $(this).parents('.num_box').find('.btn_input').val(num);
    });
    $(document).on('click','.ipro_right .btn_input',function(){
        var number = parseInt($(this).val());
        var max = parseInt($(this).parents('.num_box').attr("data-max"));
        var re_num = /^[0-9]*$/;
        if (!number) {
            $(this).val("1");
        } else if (!re_num.test(number)) {
            $(this).val("1");
        } else if (number <= 1) {
            $(this).val("1");
        } else if (number >= max) {
            $(this).val(max);
        }
    });
});