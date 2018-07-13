<!-- $Id: shop_config.htm 16865 2009-12-10 06:05:32Z sxc_shop $ -->
<?php echo $this->fetch('pageheader_bd.htm'); ?>
<?php echo $this->smarty_insert_scripts(array('files'=>'../js/utils.js,selectzone_bd.js,validator.js')); ?>
<link href="styles/jquery.bigcolorpicker.css" rel="stylesheet" type="text/css" />
<?php echo $this->smarty_insert_scripts(array('files'=>'../js/utils.js,../js/region.js')); ?>
<?php echo $this->smarty_insert_scripts(array('files'=>'jquery-1.6.1.js,jquery.bigcolorpicker.js')); ?>

<script type="text/javascript"></script>
<script type="text/javascript">
		$(function(){
			$("#demo1Text").bigColorpicker();
			
		})
</script>

<form enctype="multipart/form-data" name="theForm" action="?act=post" method="post" class="layui-form layui-form-pane">
<div class="layui-tab-content">
    <div class="layui-tab-item layui-show">
        <!--定义颜色-->
        <div class="layui-form-item">
            <label class="layui-form-label"><?php echo $this->_var['lang']['require_field']; ?> 定义颜色</label>
            <div class="layui-input-block">
                <input name="shop_header_color" type="text" value="<?php echo $this->_var['color']; ?>" id="demo1Text" size="40" class="layui-input" />
            </div>
        </div>
        <!--定义头部-->
        <div class="layui-form-item">
            <label class="layui-form-label"><?php echo $this->_var['lang']['require_field']; ?> 定义头部</label>
            <div class="layui-input-block">
                <input type="file" name="goods_img" value="选择" style="margin-top:5px;margin-left:10px;" />
                <span class="notice-span">（建议图片宽度：1210px，高度不限）</span>
            </div>
        </div>
        <?php if ($this->_var['picture'] != "请上传logo和banner" || ""): ?>
        <div  style="margin-left:160px;">  <img src="../<?php echo $this->_var['picture']; ?>" width="900px" height="100px;"/> </div>
        <?php endif; ?>
        <p class="btn_padding_left">
            <input type="submit" class="layui-btn" value="<?php echo $this->_var['lang']['button_submit']; ?>" />
            <input type="reset" class="layui-btn layui-btn-primary" value="<?php echo $this->_var['lang']['button_reset']; ?>" />
        </p>
    </div>
</div>
</form>

<?php echo $this->smarty_insert_scripts(array('files'=>'tab.js,validator.js')); ?>

<script language="JavaScript">


region.isAdmin = true;
onload = function()
{
    // 开始检查订单
    startCheckOrder();
}
var ReWriteSelected = null;
var ReWriteRadiobox = document.getElementsByName("value[209]");

for (var i=0; i<ReWriteRadiobox.length; i++)
{
  if (ReWriteRadiobox[i].checked)
  {
    ReWriteSelected = ReWriteRadiobox[i];
  }
}

function ReWriterConfirm(sender)
{
  if (sender == ReWriteSelected) return true;
  var res = true;
  if (sender != ReWriteRadiobox[0]) {
    var res = confirm('<?php echo $this->_var['rewrite_confirm']; ?>');
  }

  if (res==false)
  {
      ReWriteSelected.checked = true;
  }
  else
  {
    ReWriteSelected = sender;
  }
  return res;
}
</script>

<?php echo $this->fetch('pagefooter.htm'); ?>