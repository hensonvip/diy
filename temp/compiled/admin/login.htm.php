<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=Edge，chrome=1">
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0" />
    <title><?php echo $this->_var['lang']['cp_home']; ?><?php if ($this->_var['ur_here']): ?> - <?php echo $this->_var['ur_here']; ?><?php endif; ?></title>
    <link rel="stylesheet" type="text/css" href="styles/reset.css"/>
    <link rel="stylesheet" type="text/css" href="styles/style.css?ver=527"/>
    <script src="js/jquery.js" type="text/javascript" charset="utf-8"></script>
    <script src="js/index.js" type="text/javascript" charset="utf-8"></script>
    <?php echo $this->smarty_insert_scripts(array('files'=>'../js/utils.js,validator.js')); ?>
    <script language="JavaScript">
        <!--
        // 这里把JS用到的所有语言都赋值到这里
        <?php $_from = $this->_var['lang']['js_languages']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('key', 'item');if (count($_from)):
    foreach ($_from AS $this->_var['key'] => $this->_var['item']):
?>
        var <?php echo $this->_var['key']; ?> = "<?php echo $this->_var['item']; ?>";
        <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
        
        if (window.parent != window)
        {
            window.top.location.href = location.href;
        }
        
        //-->
    </script>
</head>
<body>
<div class="wrap">
    <div class="bg"></div>
    <div class="inner clearfix">
        <div class="left">
            <span class="span0"></span>
            <h3>Administrator</h3>
            <span class="span1">管理员</span>
            <span class="span1">B2B2C购物管理系统</span>

            <form method="post" action="privilege.php" name='theForm' onsubmit="return validate()">
                <div class="clearfix">
                    <p class="gg2">
                        <b>账户</b><input name="username" type="text"  placeholder="请输入您的账户名"/>
                    </p>
                    <p class="gg">
                        <em class="ml t">*</em><i class="fz">最小字节需大于4个字符</i>
                    </p>
                </div>
                <div class="clearfix">
                    <p class="gg2">
                        <b>密码</b><input type="password" name="password" placeholder="最长填写16位"/>
                    </p>
                </div>
                <div class="clearfix">
                    <p class="gg2">
                        <input type="text" name="captcha" placeholder="请输入验证码" class="last"/><img src="index.php?act=captcha&<?php echo $this->_var['random']; ?>" class="em1" alt="CAPTCHA" border="0" onclick= this.src="index.php?act=captcha&"+Math.random() style="cursor: pointer; " title="<?php echo $this->_var['lang']['click_for_another']; ?>" />
                    </p>
                </div>
                <input type="checkbox" value="1" name="remember" id="remember" style="display:none;" />

            <div class="dl clearfix"><input type="submit" value="立即登录" style="text-align:left;cursor: pointer;" class="a1" /></div>
                <input type="hidden" name="act" value="signin" />
            </form>
        </div>

        <div class="right clearfix">
            <div class="d1">
                <img src="images/a.jpg" alt="" />
            </div>
            <div class="d2">
                <a href="http://www.hunuo.com/contact/" class="btn1" target="_blank">立即联系我们</a>
                <div></div>
                <a href="http://www.hunuo.com/contact/" class="btn2" target="_blank">常见问题帮助？</a>
            </div>
        </div>

    </div>
</div>


<!--加载页面-->
<div class="spinner">
    <div class="bounce1"></div>
    <div class="bounce2"></div>
    <div class="bounce3"></div>
</div>
<!--加载页面结束-->

<script language="JavaScript">
    <!--
    document.forms['theForm'].elements['username'].focus();
    
    /**
     * 检查表单输入的内容
     */
    function validate()
    {
        var validator = new Validator('theForm');
        validator.required('username', user_name_empty);
        //validator.required('password', password_empty);
        if (document.forms['theForm'].elements['captcha'])
        {
            validator.required('captcha', captcha_empty);
        }
        return validator.passed();
    }
    
    //-->
</script>

</body>
</html>
