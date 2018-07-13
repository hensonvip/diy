(function ($) {
    var defaluts={
        d:300,
        r:550
    };
    function setshow(obj,r) {
        var t=$(window).scrollTop();
        while (obj.is(':hidden')){
            obj=obj.parent();
            if(obj[0].tagName=='BODY'){
                return true;
            }
        }
        if(t<obj.offset().top+obj.outerHeight()+r&&t>obj.offset().top-$(window).height()-r){
            return true;
        }else{
            return false;
        }
    }
    function setbg(obj,r) {
        if(!obj.attr('data-lazyload-suc')&&setshow(obj,r)){
            $('body').attr('data-lazyload-num',parseInt($('body').attr('data-lazyload-num'))+1);
            obj.attr('data-lazyload-suc',true);
            if(obj.attr('data-lazyload-img')){
                obj.attr('src',obj.attr('data-lazyload-img'));
            }
            else if(obj.attr('data-lazyload-bg')){
                obj.css('background-image','url('+obj.attr('data-lazyload-bg')+')');
            }
        }
    }
    $.fn.lazyload=function (options) {
        var options=$.extend({}, defaluts, options || {});
        var win=$(window);
        var yesno=true;
        var _that=this;
        var length=_that.length;
        $('body').attr('data-lazyload-num',0);
        _that.each(function () {
            setbg($(this),options.r);
            return '';
        });
        win.scroll(function () {
            if(yesno){
                yesno=false;
                setTimeout(function () {
                    _that.each(function () {
                        setbg($(this),options.r);
                        return '';
                    });
                    if($('body').attr('data-lazyload-num')==length){
                        // alert(1)
                        yesno=false;
                    }else{
                        yesno=true;
                    }
                },options.d);
            }
        })
    };
})(jQuery);