 $(function(){
    $('.ipd_tp').hover(function () {
        if (!$(this).hasClass('tp_ed')) {
            $(this).html("+ <em>1</em>");
        }
    }, function () {
        if (!$(this).hasClass('tp_ed')) {
            $(this).html("投 票");
        }
    });
    //投票
    $(document).on('click','.ipd_rbot_wrap .ipd_tp',function () {
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
                    layer.msg("投票成功");
                    return false;
                }else if(result['message'] == '3'){
                    layer.msg("本赛期投票已结束，无法再进行投票");
                    return false;
                }
                _tihs.addClass('tp_ed disabled').html("已投票");
                $('.ipd_user .price.ps').addClass('tp_ed');
                $('.ipd_user .price.ps .piaoshu').text(parseInt(number) + 1);
                layer.msg("投票成功");
            },
            error:function(){
                layer.msg("网络请求失败，请稍后重试");
            }
        });
    });

    //详情作品右侧+左侧icon点赞
    var click_zan = false;
    $(document).on('click','.ipro_right .des .zan,.ipro_left .ipro_zan',function(){
        var _tihs = $(".ipro_wrap");
        var zan_num = $(".ipro_right .des .zan").text();
        var record_id = _tihs.attr('data-rid');
        var diy_id = _tihs.attr('data-did');
        if(click_zan == true){
                layer.msg("点击过于频繁,请稍后重试！");
                return false;
        }
        if(_tihs.hasClass('on')||$('.ipro_left .ipro_zan').hasClass('on')){
            click_zan = true;
            $.ajax({
                type: 'GET',
                url: zan_reduce,
                data: {record:record_id,diy_id:diy_id},
                dataType: 'json',
                success: function (result) {
                    $(".ipro_right .des .zan").removeClass('on');
                    $('.ipro_left .ipro_zan').removeClass('on');
                    $(".ipro_right .des .zan").text(parseInt(zan_num) - 1);
                     click_zan = false;
                },
                error:function(){
                    layer.msg("网络请求失败，请稍后重试");
                }
            });
        }else{
            click_zan = true;
            $.ajax({
                type: 'GET',
                url: zan_increase,
                data: {record:record_id,diy_id:diy_id},
                dataType: 'json',
                success: function (result){
                    //click_zan = false;
                    $(".ipro_right .des .zan").addClass('on');
                    //$('.ipro_zan').addClass('on');
                    $('.ipro_left .ipro_zan').addClass('on');
                    $(".ipro_right .des .zan").text(parseInt(zan_num) + 1);
                        click_zan = false;
                },
                error:function(){
                    layer.msg("网络请求失败，请稍后重试");
                }
            });
        }
    });

  function sleep(n) { //n表示的毫秒数
        var start = new Date().getTime();
        while (true) if (new Date().getTime() - start > n) break;
    }  

//提交回复
    $(document).on('click','#reply .ipro_com_bot_sub',function(){
        var _tihs = $(".ipro_wrap");
        var user_id = _tihs.attr('data-aid');//接收回复的人
        var comment_id = $(".clearfix .ipro_com_icon").attr('data-cid');//评论id
        var record_id = _tihs.attr('data-rid');
        var content = $('#reply textarea').val();
        var aName = $('.ava_txt h2').html();
        //alert(author_id);
        if(content == ""){
            layer.msg("回复内容不能为空！");
            return false;
        }
        if(user_id==""||comment_id==""||record_id==""){
            layer.msg("网络请求失败，请重试");
            return false;
        }
        $.ajax({
            type: 'POST',
            url: reply,
            data: {user_id:user_id,content:content,comment_id:comment_id,record_id:record_id},
            dataType: 'json',
            success: function (result) {
                if(result['user_data']['code'] == 1){
                   var html = '<div class="ipro_com_detail"> <em class="author_name">'+aName+': </em>'+content+ '</div>';
                $('[data-cid='+comment_id+']').parent().append(html);
                    console.log($('[data-cid='+comment_id+']').parent());
                layer.msg("回复成功");
                }else{
                    layer.msg("回复失败，请重试");
                }
                return false;
            },
            error:function(){
                layer.msg("网络请求失败，请稍后重试");
            }
        });
    });

//提交评论
    $(document).on('click','#comment .ipro_com_bot_sub',function(){
        var _tihs = $(".ipro_wrap");
        var author_id = _tihs.attr('data-aid');//接收评论的人
        var record_id = _tihs.attr('data-rid');
        var headimg =  $('.huser_box').find('.user').css("background-image").split("\"")[1];
        //var myDate = new Date();
        //var diy_id = _tihs.attr('data-did');
        var content = $('.ipro_com_bot_form textarea').val();
        if(content == ""){
            layer.msg("评论内容不能为空！");
            return false;
        }
        if(author_id==""||record_id==""){
            layer.msg("网络请求失败，请重试");
            return false;
        }
        //alert(author_id);
        $.ajax({
            type: 'POST',
            url: comment,
            data: {author_id:author_id,content:content,record_id:record_id},
            dataType: 'json',
            success: function (result) {
                if(result['user_data']['code'] == 1){
                    var comment_html = '<li class="clearfix"> <div class="ipro_com_icon" >' +
                        ' </div>' +
                        ' <div class="clearfix"> <a href="javascript:void(0)">' +
                        '<div class="ipro_com_avatar lazybg" data-lazyload-bg="'+headimg+'" data-lazyload-suc="true" style="background-image: url('+headimg+');"></div>' +
                        '</a> <div class="ipro_com_txt" > <h3><a href=""><b>您</b></a><span> 刚刚</span></h3> <p>'+content+'</p> </div> <div class="ipro_com_rbox"> ' +
                        '</div> </div> </li>';
                    $('.ipro_com_bot_ul').prepend(comment_html);
                    $('.ipro_com_bot_pf').text('评论('+ (parseInt($('.ipro_com_bot_pf').text().replace(/[^0-9]/ig,"")) +1)+')');
                    layer.msg("评论成功");
                }else{
                    layer.msg("评论失败，请稍后重试！");
                }
                return false;
            },
            error:function(e){
                layer.msg("网络请求失败，请稍后重试");
                console.log(e);
            }
        });
    });

    //评论点赞
    var comment_click_zan = false;
    function comment_onzan(obj, comment_id){
        var _this = $(obj);
        //alert(_this.text());
        if(comment_click_zan == true){
            layer.msg("点击过于频繁,请稍后重试！");
            return false;
        }
        comment_click_zan = true;
        $.ajax({
            type: 'GET',
            url: comment_zan,
            data: {comment_id:comment_id},
            dataType: 'json',
            success: function (result) {
                if(result['status'] == '200'){
                    comment_click_zan = false;
                    if(result['is_add'] == '2'){
                        _this.text(parseInt(_this.text()) + 1);
                        _this.attr('class','zan on');
                        layer.msg("点赞成功");
                    }else{
                        _this.text(parseInt(_this.text()) - 1);
                        _this.attr('class','zan');
                        layer.msg("取消点赞成功");
                    }

                }else{
                    layer.msg("网络请求失败，请稍后重试!");
                }
                return false;
            },
            error:function(e){
                layer.msg("网络请求失败，请稍后重试");
                console.log(e);
            }
        });
    }

    //私信提交
    $(document).on('click','.p_ycon2 .p_fs',function(){
        //console.log(1);
        var a_id = $('#a_id').val();
        var content = $('.p_txt textarea').val();
        if(content==""){
            layer.msg('内容不能为空！');return false;
        }
        if(a_id==""){
            layer.msg('网络请求失败，请稍后重试');return false;
        }
        $.ajax({
            type: 'GET',
            url: letter,
            data: {receive_user_id: a_id, msg_content: content},
            dataType: 'json',
            success: function (result) {
                console.log(result);
                if(result.code == '500'){
                    layer.msg(result.message);return false;
                }
                layer.msg('发送成功');
                $('body > .p_y').stop(true,true).fadeOut();
                $('html').removeClass('report');
            },error:function(){
                layer.msg('发送失败，请稍后重试');return false;
            }
        });

    })

    // 收藏
    $('body').on('mouseover','.nb_t5',function(){
        //console.log(1);
        if(!$(this).hasClass('active')){
            $(this).html("<i></i>添加收藏");
        }else{
            $(this).html("<i></i>取消收藏");
        }
    });

    $('body').on('mouseout','.nb_t5',function(){
        if(!$(this).hasClass('active')){
            $(this).html("<i></i>收 藏");
        }else{
            $(this).html("<i></i>已收藏");
        }
    });



 })

 //收藏参赛作品  与收藏商品公用一个接口
 function collect(diy_id, obj,str) {
     // if($(obj).hasClass('disabled')){ return false; }
     var index = layer.load(0, {
         shade: [0.1, '#fff']
     });
     $.ajax({
         type: "get",
         url: "/Goods/collectGoods",
         dataType: 'json',
         data: {goods_id: diy_id, str: str},
         success: function (data) {
             console.log(data);
             if (data.status == 200) {
                 layer.close(index);
                 if ($(obj).hasClass('active')) {
                     $(obj).removeClass('active').html("<i></i>收藏").css({
                         color: '#fff'
                     });
                 } else {
                     $(obj).addClass('active').html("<i></i>已收藏").css({
                         color: '#fff'
                     });
                 }
                 layer.msg(data.message);
                 // location.reload();
             }
         }
     })
 }