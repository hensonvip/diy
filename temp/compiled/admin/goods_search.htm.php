<!-- $Id: goods_search.htm 16790 2009-11-10 08:56:15Z wangleisvn $ -->
<link href="styles/zTree/zTreeStyle.css" rel="stylesheet" type="text/css" />
<link href="styles/chosen/chosen.css" rel="stylesheet" type="text/css">
<?php echo $this->smarty_insert_scripts(array('files'=>'jquery-1.6.2.min.js,chosen.jquery.min.js,jquery.ztree.all-3.5.min.js,category_selecter.js')); ?>
<div class="form-div">
    <form action="javascript:searchGoods()" name="searchForm">
        <img src="images/icon_search.gif" width="26" height="22" border="0" alt="SEARCH" />
        <?php if ($_GET['act'] != "trash"): ?>
        <!-- 分类 -->
        <input type="text" id="cat_name" name="cat_name" nowvalue="<?php echo $this->_var['goods_cat_name']; ?>" value="<?php echo $this->_var['goods_cat_name']; ?>" >
        <input type="hidden" id="cat_id" name="cat_id" value="<?php echo $this->_var['goods_cat_id']; ?>">
        <!-- 品牌 -->
        <select  class="chzn-select" name="brand_id"><option value="0"><?php echo $this->_var['lang']['goods_brand']; ?></option><?php echo $this->html_options(array('options'=>$this->_var['brand_list'])); ?></select>
        <!-- 推荐 -->
        <?php if ($this->_var['suppliers_exists'] == 0): ?>
        <?php if ($this->_var['intro_list'] != ''): ?>
        <select class="chzn-select" name="intro_type"><option value="0"><?php echo $this->_var['lang']['intro_type']; ?></option>
            <?php echo $this->html_options(array('options'=>$this->_var['intro_list'],'selected'=>$_GET['intro_type'])); ?>
        </select>
        <?php endif; ?>
        <?php endif; ?>
        <?php if ($this->_var['suppliers_exists'] == 1): ?>
        <!-- 供货商 -->
        <select class="chzn-select" name="suppliers_id" style="height:100;"><option value="0"><?php echo $this->_var['lang']['intro_type']; ?></option><?php echo $this->html_options(array('options'=>$this->_var['suppliers_list_name'],'selected'=>$_GET['suppliers_id'])); ?></select>

        <select class="chzn-select" name="supplier_status"><option value="">审核状态</option>
            <?php $_from = $this->_var['supplier_status_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('supplier_key', 'supplier_status');if (count($_from)):
    foreach ($_from AS $this->_var['supplier_key'] => $this->_var['supplier_status']):
?>
            <option value="<?php echo $this->_var['supplier_key']; ?>" <?php if ($this->_var['supplier_key'] != '' && $this->_var['supplier_key'] == $_GET['supplier_status']): ?>selected<?php endif; ?>><?php echo $this->_var['supplier_status']; ?></option>
            <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
        </select>
        <?php endif; ?>
        <!-- 上架 -->
        <?php if ($this->_var['lang']['intro_type'] != ''): ?>
        <select class="chzn-select" name="is_on_sale"><option value=''><?php echo $this->_var['lang']['intro_type']; ?></option><option value="1"><?php echo $this->_var['lang']['on_sale']; ?></option><option value="0"><?php echo $this->_var['lang']['not_on_sale']; ?></option></select>
        <?php endif; ?>
        <?php endif; ?>
        <!-- 关键字 -->
        关键字 <input type="text" name="keyword" size="15" />
        <input type="submit" value="<?php echo $this->_var['lang']['button_search']; ?>" class="layui-btn" />
         <?php if ($this->_var['is_pre_sale'] != '1'): ?>
        <input type="button" value="批量导出" class="layui-btn" onclick="batch_export()" />
         <?php endif; ?>
        
    </form>
</div>


<script language="JavaScript">

    $().ready(function(){
       $(".chzn-select").chosen();
       $(".chzn-container").click(function(){
     	$("#menuContent_cat_id").hide();
    	})
		$('.chzn-container-single:last').css('width','60px');
    });

    function searchGoods()
    {
        
        <?php if ($_GET['act'] != "trash"): ?>
        listTable.filter['cat_id'] = document.forms['searchForm'].elements['cat_id'].value;
        listTable.filter['brand_id'] = document.forms['searchForm'].elements['brand_id'].value;
        <?php if ($this->_var['suppliers_exists'] == 0): ?>
            listTable.filter['intro_type'] = document.forms['searchForm'].elements['intro_type'].value;
            <?php endif; ?>
                <?php if ($this->_var['suppliers_exists'] == 1): ?>
                listTable.filter['suppliers_id'] = document.forms['searchForm'].elements['suppliers_id'].value;
                listTable.filter['supplier_status'] = document.forms['searchForm'].elements['supplier_status'].value;
                <?php endif; ?>
                    listTable.filter['is_on_sale'] = document.forms['searchForm'].elements['is_on_sale'].value;
                    <?php endif; ?>
                        
                        listTable.filter['keyword'] = Utils.trim(document.forms['searchForm'].elements['keyword'].value);

                        listTable.filter['page'] = 1;

                        listTable.loadList();
                    }
</script>

<script type="text/javascript">
    $().ready(function(){
        // $("#cat_name")为获取分类名称的jQuery对象，可根据实际情况修改
        // $("#cat_id")为获取分类ID的jQuery对象，可根据实际情况修改
        // "<?php echo $this->_var['goods_cat_id']; ?>"为被选中的商品分类编号，无则设置为null或者不写此参数或者为空字符串
        $.ajaxCategorySelecter($("#cat_name"), $("#cat_id"), "<?php echo $this->_var['goods_cat_id']; ?>");
    });

    function searchSuppliers()
    {
        var filter = new Object;
        filter.keyword = document.forms['searchForm'].elements['search_suppliers'].value;
        Ajax.call('goods.php?is_ajax=1&act=search_suppliers', filter, searchSuppliersResponse, 'GET', 'JSON')
    }

    function searchSuppliersResponse(result)
    {
    	
        var frm = document.forms['searchForm'];
        var sel = frm.elements['suppliers_id'];

        if (result.error == 0)
        {
            /* 清除 options */
            sel.length = 0;

            /* 创建 options */
            var suppliers = result.content;
            if (suppliers)
            {
                for (i = 0; i < suppliers.length; i++)
                {
                    var opt = document.createElement("OPTION");
                    opt.value = suppliers[i].supplier_id;
                    opt.text  = suppliers[i].supplier_name;
                    sel.options.add(opt);
                }
            }
            else
            {
                var opt = document.createElement("OPTION");
                opt.value = 0;
                opt.text  = search_is_null;
                sel.options.add(opt);
            }
        }

        if (result.message.length > 0)
        {
            alert(result.message);
        }
    }

    function batch_export()
    {
        // 分类
        var cat_id = document.forms['searchForm'].elements['cat_id'].value;
        // 品牌
        var brand_id = document.forms['searchForm'].elements['brand_id'].value;
        // 入驻商
        if (typeof(document.forms['searchForm'].elements['suppliers_id']) == "undefined")
        {
            var suppliers_exists = 0;
            var suppliers_id = 0;
        }
        else
        {
            var suppliers_exists = 1;
            var suppliers_id = document.forms['searchForm'].elements['suppliers_id'].value;
        }
        // 审核状态
        if (typeof(document.forms['searchForm'].elements['supplier_status']) == "undefined")
        {
            var supplier_status = '';
        }
        else
        {
            var supplier_status = document.forms['searchForm'].elements['supplier_status'].value;
        }
        // 推荐
        if (typeof(document.forms['searchForm'].elements['intro_type']) == "undefined")
        {
            var intro_type = '';
        }
        else
        {
            var intro_type = document.forms['searchForm'].elements['intro_type'].value;
        }
        // 上架
        var is_on_sale = document.forms['searchForm'].elements['is_on_sale'].value;
        // 搜索关键字
        var keyword = Utils.trim(document.forms['searchForm'].elements['keyword'].value);
        return location.href='goods.php?act=export&cat_id='+cat_id+'&brand_id='
                +brand_id+'&suppliers_id='+suppliers_id+'&supplier_status='+supplier_status+
                '&intro_type='+intro_type+'&is_on_sale='+is_on_sale+'&keyword='+keyword+'&suppliers_exists='+suppliers_exists;
    }
</script>