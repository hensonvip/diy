<?php echo $this->fetch('pageheader.htm'); ?>

<form action="street.php" method="post" name="theForm" enctype="multipart/form-data" class="layui-form layui-form-pane">
<div class="layui-tab-content">
    <div class="layui-tab-item layui-show">
        <!--店铺类型-->
        <div class="layui-form-item">
            <label class="layui-form-label"><?php echo $this->_var['lang']['require_field']; ?> 店铺类型</label>
            <div class="layui-input-block">
                <select name="supplier_type">
                    <option value="0">请选择</option>
                    <?php $_from = $this->_var['stype']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('value', 'name');if (count($_from)):
    foreach ($_from AS $this->_var['value'] => $this->_var['name']):
?>
                    <option value="<?php echo $this->_var['value']; ?>" <?php if ($this->_var['sinfo']['supplier_type'] == $this->_var['value']): ?> selected <?php endif; ?>><?php echo $this->_var['name']; ?></option>
                    <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
                </select>
            </div>
        </div>
        <!--店铺名称-->
        <div class="layui-form-item">
            <label class="layui-form-label"><?php echo $this->_var['lang']['require_field']; ?> 店铺名称</label>
            <div class="layui-input-block">
                <input type="text" name="supplier_name" value="<?php echo $this->_var['sinfo']['supplier_name']; ?>" class="layui-input">
            </div>
        </div>
        <!--店铺标题-->
        <div class="layui-form-item">
            <label class="layui-form-label"><?php echo $this->_var['lang']['require_field']; ?> 店铺标题</label>
            <div class="layui-input-block">
                <input type="text" name="supplier_title" value="<?php echo $this->_var['sinfo']['supplier_title']; ?>" class="layui-input">
                <span class="notice-span">为保证美观度,店铺标题控制在13个文字以内</span>
            </div>
        </div>
        <!-- 店铺描述 -->
        <style type="text/css">
            #supplier_desc_box label{float: left;}
            #supplier_desc_box .layui-input-initial{width: 60%;float: left;}
        </style>
        <div class="layui-form-item layui-form-text" id="supplier_desc_box">
            <label class="layui-form-label" style="float:left;">店铺描述</label>
            <div class="layui-input-initial">
              <textarea class="layui-textarea" name="supplier_desc" ><?php echo htmlspecialchars($this->_var['sinfo']['supplier_desc']); ?></textarea>
            </div>
        </div>
        <!--店铺海报-->
        <div class="layui-form-item">
            <label class="layui-form-label"><?php echo $this->_var['lang']['require_field']; ?> 店铺海报</label>
            <div class="layui-input-block">
                <input name="logo" type="file" size="40" style="margin-top:5px;margin-left:10px;" />
                <span class="notice-span">为达到前台图标显示最佳状态，建议上传800X474px图片</span>
                <?php if ($this->_var['sinfo']['logo']): ?>
                    <a href="?act=del&code=logo"><img src="images/no.gif" alt="Delete" border="0" /></a> <img src="images/yes.gif" border="0" onmouseover="showImg('logo_layer', 'show')" onmouseout="showImg('logo_layer', 'hide')" />
                    <div id="logo_layer" style="position:absolute; width:100px; height:100px; z-index:1; visibility:hidden" border="1">
                      <img src="<?php echo $this->_var['sinfo']['logo']; ?>" border="0" style="max-height:195px;" />
                    </div>
                <?php else: ?>
                    <?php if ($this->_var['sinfo']['logo'] != ""): ?>
                    <img src="images/yes.gif" alt="yes" />
                    <?php else: ?>
                    <img src="images/no.gif" alt="no" />
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <!--是否推荐-->
        <div class="layui-form-item">
            <label class="layui-form-label">是否推荐</label>
            <div class="layui-input-block">
                <input type="radio" name="is_groom" disabled value="1" <?php if ($this->_var['sinfo']['is_groom'] != 0): ?> checked="true"<?php endif; ?> title="<?php echo $this->_var['lang']['yes']; ?>" />
                <input type="radio" name="is_groom" disabled value="0" <?php if ($this->_var['sinfo']['is_groom'] == 0): ?> checked="true"<?php endif; ?> title="<?php echo $this->_var['lang']['no']; ?>" />
                <span class="notice-span">如果您希望成为推荐店铺，请联系管理方</span>
            </div>
        </div>
        <!--排序-->
        <div class="layui-form-item">
            <label class="layui-form-label">排序</label>
            <div class="layui-input-block">
                <input type="text" disabled name='sort_order' <?php if ($this->_var['sinfo']['sort_order']): ?>value='<?php echo $this->_var['sinfo']['sort_order']; ?>'<?php else: ?> value="50"<?php endif; ?> class="layui-input" />
                <span class="notice-span">如果您希望您的店铺排序比其他店铺靠前，请联系管理方</span>
            </div>
        </div>
        <!--通知-->
        <?php if ($this->_var['sinfo']['supplier_notice']): ?>
        <div class="layui-form-item">
            <label class="layui-form-label">通知</label>
            <div class="layui-input-block">
                <input type="text" value="<?php echo $this->_var['sinfo']['supplier_notice']; ?>" disabled  class="layui-input" style="width:50%;" />
                <span class="notice-span">如果您希望您的店铺排序比其他店铺靠前，请联系管理方</span>
            </div>
        </div>
        <?php else: ?>
        <div class="layui-form-item">
            <label class="layui-form-label">声明</label>
            <div class="layui-input-block">
                <input type="checkbox" name="sm" id="sm" checked value='1' title="声明" /> <a href="/article.php?id=78" target="_blank">查看声明</a>
            </div>
        </div>
        <?php endif; ?>
        <!--店铺信息-->
        <div class="layui-form-item">
            <label class="layui-form-label">店铺信息</label>
            <div class="layui-input-block">
                <?php echo $this->_var['FCKeditor']; ?>
            </div>
        </div>
        
        <p class="btn_padding_left">
            <input type="submit" class="layui-btn" value="<?php echo $this->_var['lang']['button_submit']; ?>" />
            <input type="reset" class="layui-btn layui-btn-primary" value="<?php echo $this->_var['lang']['button_reset']; ?>" />
            <input type="hidden" name="act" value="saveinfo" />
            <input type="hidden" name="supplier_id" value="<?php echo $_SESSION['supplier_id']; ?>" />
        </p>

    </div>
</div>
</form>

<script type="text/javascript">
    layui.use('form', function(){
        var form = layui.form();
        form.render(); //更新全部  
    });
</script>
<?php echo $this->fetch('pagefooter.htm'); ?>