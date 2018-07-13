<?php echo $this->fetch('pageheader.htm'); ?>
<?php echo $this->smarty_insert_scripts(array('files'=>'validator.js')); ?>

<form action="user_account.php" method="post" name="theForm" onsubmit="return validate()" class="layui-form layui-form-pane">
<div class="layui-tab-content">
    <div class="layui-tab-item layui-show">
        <!--会员名称-->
        <div class="layui-form-item">
            <label class="layui-form-label"><?php echo $this->_var['lang']['require_field']; ?> <?php echo $this->_var['lang']['user_id']; ?></label>
            <div class="layui-input-block">
                <input type="text" name="user_id" value="<?php echo $this->_var['user_name']; ?>" size="20" <?php if ($this->_var['user_surplus']['process_type'] == 2 || $this->_var['user_surplus']['process_type'] == 3 || $this->_var['action'] == "edit"): ?> readonly="true" <?php endif; ?> class="layui-input"/>
            </div>
        </div>
        <!--金额-->
        <div class="layui-form-item">
            <label class="layui-form-label"><?php echo $this->_var['lang']['require_field']; ?> <?php echo $this->_var['lang']['surplus_amount']; ?></label>
            <div class="layui-input-block">
                <input type="text" name="amount" value="<?php echo $this->_var['user_surplus']['amount']; ?>" size="20" <?php if ($this->_var['user_surplus']['process_type'] == 2 || $this->_var['user_surplus']['process_type'] == 3 || $this->_var['action'] == "edit"): ?> readonly="true" <?php endif; ?> class="layui-input"/>
            </div>
        </div>
        <!--手续费-->
        <div class="layui-form-item">
            <label class="layui-form-label">手续费</label>
            <div class="layui-input-block">
                <input type="text" name="poundage" value="<?php echo $this->_var['user_surplus']['poundage']; ?>" size="20" <?php if ($this->_var['user_surplus']['process_type'] == 2 || $this->_var['user_surplus']['process_type'] == 3): ?> readonly="true" <?php endif; ?> class="layui-input"/>
            </div>
        </div>
        <!--支付方式-->
        <div class="layui-form-item" style="display:none;">
            <label class="layui-form-label"><?php echo $this->_var['lang']['pay_mothed']; ?></label>
            <div class="layui-input-block">
                <select name="payment" <?php if ($this->_var['user_surplus']['process_type'] == 2 || $this->_var['user_surplus']['process_type'] == 3): ?>disabled="true" <?php endif; ?>>
                    <option value=""><?php echo $this->_var['lang']['please_select']; ?></option>
                    <?php echo $this->html_options(array('options'=>$this->_var['payment_list'],'selected'=>$this->_var['user_surplus']['payment'])); ?>
                </select>
            </div>
        </div>
        <!--类型-->
        <div class="layui-form-item" style="display:none;">
            <label class="layui-form-label"><?php echo $this->_var['lang']['process_type']; ?></label>
            <div class="layui-input-block">
                <input type="radio" name="process_type" value="0" <?php if ($this->_var['user_surplus']['process_type'] == 0): ?> checked="true" <?php endif; ?> <?php if ($this->_var['user_surplus']['process_type'] == 2 || $this->_var['user_surplus']['process_type'] == 3 || $this->_var['action'] == "edit"): ?>disabled="true" <?php endif; ?> title="<?php echo $this->_var['lang']['surplus_type_0']; ?>" />
                <input type="radio" name="process_type" value="1" <?php if ($this->_var['user_surplus']['process_type'] == 1): ?> checked="true" <?php endif; ?> <?php if ($this->_var['user_surplus']['process_type'] == 2 || $this->_var['user_surplus']['process_type'] == 3 || $this->_var['action'] == "edit"): ?>disabled="true" <?php endif; ?> title="<?php echo $this->_var['lang']['surplus_type_1']; ?>" />
                <?php if ($this->_var['action'] == "edit" && ( $this->_var['user_surplus']['process_type'] == 2 || $this->_var['user_surplus']['process_type'] == 3 )): ?>
                    <input type="radio" name="process_type" value="2" <?php if ($this->_var['user_surplus']['process_type'] == 2 || $this->_var['action'] == "edit"): ?> checked="true"<?php endif; ?><?php if ($this->_var['user_surplus']['process_type'] == 2 || $this->_var['user_surplus']['process_type'] == 3): ?> disabled="true"<?php endif; ?> title="<?php echo $this->_var['lang']['surplus_type_2']; ?>" />
                    <input type="radio" name="process_type" value="3" <?php if ($this->_var['user_surplus']['process_type'] == 3 || $this->_var['action'] == "edit"): ?> checked="true"<?php endif; ?><?php if ($this->_var['user_surplus']['process_type'] == 2 || $this->_var['user_surplus']['process_type'] == 3): ?> disabled="true"<?php endif; ?> title="<?php echo $this->_var['lang']['surplus_type_3']; ?>" />
                <?php endif; ?>
            </div>
        </div>
        <!--管理员备注-->
        <div class="layui-form-item">
            <label class="layui-form-label"><?php echo $this->_var['lang']['surplus_notic']; ?></label>
            <div class="layui-input-block">
                <textarea name="admin_note" cols="55" rows="3"<?php if ($this->_var['user_surplus']['process_type'] == 2 || $this->_var['user_surplus']['process_type'] == 3): ?> readonly="true" <?php endif; ?>><?php echo $this->_var['user_surplus']['admin_note']; ?></textarea>
            </div>
        </div>
        <!--会员描述-->
        <div class="layui-form-item">
            <label class="layui-form-label"><?php echo $this->_var['lang']['surplus_desc']; ?></label>
            <div class="layui-input-block">
                <textarea name="user_note" cols="55" rows="3"<?php if ($this->_var['user_surplus']['process_type'] == 2 || $this->_var['user_surplus']['process_type'] == 3): ?> readonly="true" <?php endif; ?>><?php echo $this->_var['user_surplus']['user_note']; ?></textarea>
            </div>
        </div>
        <!--到款状态-->
        <div class="layui-form-item">
            <label class="layui-form-label"><?php echo $this->_var['lang']['status']; ?></label>
            <div class="layui-input-block">
                <input type="radio" name="is_paid" value="0" <?php if ($this->_var['user_surplus']['is_paid'] == 0): ?> checked="true"<?php endif; ?> <?php if ($this->_var['user_surplus']['process_type'] == 2 || $this->_var['user_surplus']['process_type'] == 3 || $this->_var['action'] == "edit"): ?> disabled="true"<?php endif; ?> title="<?php echo $this->_var['lang']['unconfirm']; ?>" />
                <input type="radio" name="is_paid" value="1" <?php if ($this->_var['user_surplus']['is_paid'] == 1): ?> checked="true" <?php endif; ?> <?php if ($this->_var['user_surplus']['process_type'] == 2 || $this->_var['user_surplus']['process_type'] == 3 || $this->_var['action'] == "edit"): ?> disabled="true"<?php endif; ?> title="<?php echo $this->_var['lang']['confirm']; ?>" />
                <input type="radio" name="is_paid" value="2" <?php if ($this->_var['user_surplus']['is_paid'] == 2): ?> checked="true" <?php endif; ?> <?php if ($this->_var['user_surplus']['process_type'] == 2 || $this->_var['user_surplus']['process_type'] == 3 || $this->_var['action'] == "edit"): ?> disabled="true"<?php endif; ?> title="<?php echo $this->_var['lang']['cancel']; ?>" />
            </div>
        </div>
        <p class="btn_padding_left">
            <input type="hidden" name="id" value="<?php echo $this->_var['user_surplus']['id']; ?>" />
            <input type="hidden" name="act" value="<?php echo $this->_var['form_act']; ?>" />
            <?php if ($this->_var['user_surplus']['process_type'] == 0 || $this->_var['user_surplus']['process_type'] == 1): ?>
            <input type="submit" class="layui-btn" value="<?php echo $this->_var['lang']['button_submit']; ?>" />
            <input type="reset" class="layui-btn layui-btn-primary" value="<?php echo $this->_var['lang']['button_reset']; ?>" />
            <?php endif; ?>
        </p>

    </div>
</div>
</form>

<script language="JavaScript">
<!--

onload = function()
{
    // 开始检查订单
    startCheckOrder();
}

/**
 * 检查表单输入的数据
 */
function validate()
{
    validator = new Validator("theForm");

    validator.required("user_id",   user_id_empty);
    validator.required("amount",    deposit_amount_empty);
    validator.isNumber("amount",    deposit_amount_error, true);

    var deposit_amount = document['theForm'].elements['amount'].value;
    if (deposit_amount.length > 0)
    {
        if (deposit_amount == 0 || deposit_amount < 0)
        {
            alert(deposit_amount_error);
            return false;
        }
    }

    return validator.passed();
}

//-->

</script>

<script type="text/javascript">
    layui.use('form', function(){
        var form = layui.form();
        form.render(); //更新全部
    });
</script>
<?php echo $this->fetch('pagefooter.htm'); ?>