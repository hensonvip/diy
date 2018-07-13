<?php
include_once(ROOT_PATH . 'includes/cls_supplier.php');

/**
 * 店铺接口
 * 
 * @version v1.0
 * @create 2016-10-26
 * @author cyq
 */

class SupplierController extends ApiController
{
	public function __construct()
	{
		parent::__construct();
		$this->data = $this->input();
		$this->user_id = isset($this->data['user_id'])? intval($this->data['user_id']) : 0;
		$this->supplier = cls_supplier::getInstance();

	}

	/**
	 * 店铺列表
	 * @param integer $supplier_type	店铺类型
	 * @param integer $size 			分页数量
	 * @param integer $page 			当前分页
	 * @param string  $sort      		排序方式
	 * @param string  $order     		按字段排序
	 * @param string  $keywords     	关键字
	 */
	public function getSupplierList(){
		$supplier_type 	= $this->input('supplier_type', 0);
		$size   	= $this->input('page_size', 4);
		$page   	= $this->input('page', 1);
		$order   	= $this->input('order', 'ASC');
		$sort    	= $this->input('sort', 'sort_order');
		$keywords 	= htmlspecialchars(trim($this->input('keywords', '')));

		$result = $this->supplier->getSupplierlist($supplier_type,$size,$page,$order,$sort,$keywords,$this->user_id);
		$count = $this->supplier->getSupplierlist_count($supplier_type,$size,$page,$order,$sort,$keywords,$this->user_id);

		if (empty($result))
		{
			$this->error( '找不到数据');
		}
		//var_dump($obj);
		//sort($result); //..........Yip 改~

		//分页
        $pager = array();
        $pager['page']         = $page;
        $pager['page_size']    = $size;
        $pager['record_count'] = $count;
        $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $size)) : 1;

        $goods_data['list'] = $result;
        $goods_data['pager'] = $pager;

		$this->success($goods_data);
		//$this->success($result);
	}

	/**
	 * 店铺详情
	 */
	public function getSupplierDetails(){	
		$supplier_id 	= $this->input('supplier_id', 0);
		$result = $this->supplier->get_SupplierDetails($supplier_id,$this->user_id);
		if (empty($result))
		{
			$this->error( '找不到数据');
		}
		$this->success($result);
	}

	/**
	 * 店铺商品列表
	 */
	public function getSupplierGoods(){
		$supplier_id 	= $this->input('supplier_id', 0);
		$page_size   	= $this->input('page_size', 4);
		$page   	= $this->input('page', 1);
		$page_start = $page_size*($page-1);
		$order   	= $this->input('order', 'desc');
		$sort    	= $this->input('sort', 'sort_order');
		$filter  	= $this->input('filter', '');
		$page_size2 = $page_start.",".$page_size;
		$result = $this->supplier->get_SupplierGoods($supplier_id,$page_size2,$page_start,$order = 'desc', $sort = 'goods_id' ,$filter='');
		$count = $this->supplier->get_SupplierGoods_count($supplier_id);
		
		if (empty($result))
		{
			//$this->success(array('list'=>array(),'pager'=>new StdClass()), $code = 200, $msg = '找不到数据');
			$this->error( '找不到数据');
		}
		//var_dump($obj);
		//sort($result); //..........Yip 改~

		//分页
        $pager = array();
        $pager['page']         = $page;
        $pager['page_size']    = $page_size;
        $pager['record_count'] = $count;
        $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $page_size)) : 1;

        $goods_data['list'] = $result;
        $goods_data['pager'] = $pager;

		$this->success($goods_data);
		//$this->success($result);
	}

	/**
	 * @description 用户添加收藏商品
	 * @param integer user_id 用户ID
	 * @param string supplier_id 店铺ID
	 */
	public function getGuanzhu(){
		$supplier_id = !empty($this->data['supplier_id'])?intval($this->data['supplier_id']):0;
		if(!$this->user_id){
			$this->error('请先登录！');
		}
		if(empty($supplier_id)){
			$this->error('操作失败，缺少商家参数！');
		}
		$result = $this->supplier->get_Guanzhu($supplier_id,$this->user_id);$this->success($result);
		if($result['status'] == 200){
			unset($result['status']);
			$this->success($result);
		}else{
			unset($result['status']);
			$this->error($result);
		}
	}

	/**
	 * @description 获取商家分类列表
	 */
	public function getSupplierStreet(){
		$list = $this->supplier->get_supplierStreet();
		$result = array();
		$result['list'] = $list;
		if($result){
			$this->success($result);
		}else{
			$this->error($result);
		}
	}

	/**
	 * 获取申请入驻信息
	 */
	public function apply_enter_info(){
		$user_id = $this->input('user_id');
		$result = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('supplier') . " WHERE user_id = '$user_id'");
		if(!empty($result)){
			$result['handheld_idcard'] = !empty($result['handheld_idcard']) ? 'data/supplier/'.$result['handheld_idcard'] : '';
			$result['idcard_front'] = !empty($result['idcard_front']) ? 'data/supplier/'.$result['idcard_front'] : '';
			$result['idcard_reverse'] = !empty($result['idcard_reverse']) ? 'data/supplier/'.$result['idcard_reverse'] : '';

			$result['zhizhao'] = !empty($result['zhizhao']) ? 'data/supplier/'.$result['zhizhao'] : '';
			$result['organization_code_electronic'] = !empty($result['organization_code_electronic']) ? 'data/supplier/'.$result['organization_code_electronic'] : '';
			$result['general_taxpayer'] = !empty($result['general_taxpayer']) ? 'data/supplier/'.$result['general_taxpayer'] : '';
			$result['bank_licence_electronic'] = !empty($result['bank_licence_electronic']) ? 'data/supplier/'.$result['bank_licence_electronic'] : '';
			$result['tax_registration_certificate_electronic'] = !empty($result['tax_registration_certificate_electronic']) ? 'data/supplier/'.$result['tax_registration_certificate_electronic'] : '';

			$result['province_name'] = $GLOBALS['db']->getOne("SELECT region_name FROM " . $GLOBALS['ecs']->table('region') . " WHERE region_id = '$result[province]'");
			$result['city_name'] = $GLOBALS['db']->getOne("SELECT region_name FROM " . $GLOBALS['ecs']->table('region') . " WHERE region_id = '$result[city]'");
			$result['district_name'] = $GLOBALS['db']->getOne("SELECT region_name FROM " . $GLOBALS['ecs']->table('region') . " WHERE region_id = '$result[district]'");

		}else{
			$result = array();
			$result['company_name'] = '';//店铺名称 || 公司名称@company
			$result['country'] = '';//国家  || @company
			$result['province'] = '';//省 || @company
			$result['province_name'] = '';//省名称 || @company
			$result['city'] = '';//市 || @company
			$result['city_name'] = '';//市名称 || @company
			$result['district'] = '';//区 || @company
			$result['district_name'] = '';//区名称 || @company
			$result['address'] = '';//详细地址 || @company
			$result['tel'] = '';//公司电话@company
			$result['guimo'] = '';//公司规模@company
			$result['company_type'] = '';//公司类型@company
			$result['contacts_name'] = '';//姓名
			$result['contacts_phone'] = '';//联系人电话
			$result['email'] = '';//邮箱
			$result['business_licence_number'] = '';//营业执照号@company
		    $result['business_sphere'] = '';//法定经营范围@company
		    $result['organization_code'] = '';//组织机构代@company
		    $result['zhizhao'] = '';//营业执照@company
		    $result['organization_code_electronic'] = '';//组织机构代码证电子版@company
		    $result['general_taxpayer'] = '';//一般纳税人证明@company  

		    $result['settlement_bank_account_name'] = '';//结算账号信息
		    $result['settlement_bank_account_number'] = '';//公司银行账号
		    $result['settlement_bank_name'] = '';//开户支行名称
		    $result['settlement_bank_code'] = '';//支行联行号
		    $result['tax_registration_certificate'] = '';//税务登记证号
		    $result['taxpayer_id'] = '';//纳税人识别号    
		    $result['bank_licence_electronic'] = '';//开户银行许可证电子版  
		    $result['tax_registration_certificate_electronic'] = '';//税务登记证号电子版

			$result['id_card_no'] = '';//身份证号码
			$result['handheld_idcard'] = '';//手持身份证照片
			$result['idcard_front'] = '';//身份证正面
			$result['idcard_reverse'] = '';//身份证反面
			$result['bank_account_name'] = '';//银行开户名
		    $result['bank_account_number'] = '';//个人银行账号
		    $result['bank_name'] = '';//开户银行支行名称
		    $result['bank_code'] = '';//支行联行号
		    $result['supplier_name'] = ''; //店铺名称
			$result['rank_id'] = '';//店铺等级 
			$result['type_id'] = '';//店铺分类 
			$result['status'] = 0;
			$result['applynum'] = 0;

		}

		$result['article_id'] = 78;//入驻协议文章ID
		$this->success($result);exit();
	}

	/**
	 * 申请入驻
	 */
	public function apply_enter(){
		require_once(ROOT_PATH . 'languages/zh_cn/common.php');
		global $_LANG;
		global $_CFG;
		include_once(ROOT_PATH . 'includes/lib_main.php');
		$upload_size_limit = $_CFG['upload_size_limit'] == '-1' ? ini_get('upload_max_filesize') : $_CFG['upload_size_limit'];

		$save = array();
		$user_id = $this->user_id;

		//个人信息
		//$save['company_name'] = isset($this->data['company_name']) ? trim(addslashes(htmlspecialchars($this->data['company_name']))) : '';//店铺名称 
		$save['country'] = isset($this->data['country']) ? intval($this->data['country']) : 1;//国家 
		$save['province'] = isset($this->data['province']) ? intval($this->data['province']) : 1;//省
		$save['city'] = isset($this->data['city']) ? intval($this->data['city']) : 1;//市
		$save['district'] = isset($this->data['district']) ? intval($this->data['district']) : 1;//区
		$save['address'] = isset($this->data['address']) ? trim(addslashes(htmlspecialchars($this->data['address']))) : '';//详细地址
		
		$save['contacts_name'] = isset($this->data['contacts_name']) ? trim(addslashes(htmlspecialchars($this->data['contacts_name']))) : '';//姓名
		$save['contacts_phone'] = isset($this->data['contacts_phone']) ? trim(addslashes(htmlspecialchars($this->data['contacts_phone']))) : '';//联系人电话
		$save['email'] = isset($this->data['email']) ? trim($this->data['email']) : '';//邮箱

		//身份证信息
		$save['id_card_no'] = isset($this->data['id_card_no']) ? trim(addslashes(htmlspecialchars($this->data['id_card_no']))) : '';//身份证号码
		//手持身份证照片
		if (@$_FILES['handheld_idcard']['size'] > 0)
		{
			if($_FILES['handheld_idcard']['size'] / 1024 > $upload_size_limit)
			{
				$this->error(sprintf($_LANG['upload_file_limit']),200,new StdClass());exit();
			}
			$handheld_idcard_img = upload_file($_FILES['handheld_idcard'], 'supplier',$user_id);
			if ($handheld_idcard_img === false)
			{
				$this->error('手持身份证照片上传失败！',200,new StdClass());exit();
			}
			else
			{
				$save['handheld_idcard'] = $handheld_idcard_img;
			}
		}
		//身份证正面
		if (@$_FILES['idcard_front']['size'] > 0)
		{
			if($_FILES['idcard_front']['size'] / 1024 > $upload_size_limit)
			{
				$this->error(sprintf($_LANG['upload_file_limit']),200,new StdClass());exit();
			}
			$idcard_front_img = upload_file($_FILES['idcard_front'], 'supplier',$user_id);
			if ($idcard_front_img === false)
			{
				$this->error('身份证正面照片上传失败！',200,new StdClass());exit();
			}
			else
			{
				$save['idcard_front'] = $idcard_front_img;
			}
		}
		//身份证反面
		if (@$_FILES['idcard_reverse']['size'] > 0)
		{
			if($_FILES['idcard_reverse']['size'] / 1024 > $upload_size_limit)
			{
				$this->error(sprintf($_LANG['upload_file_limit']),200,new StdClass());exit();
			}
			$idcard_reverse_img = upload_file($_FILES['idcard_reverse'], 'supplier',$user_id);
			if ($idcard_reverse_img === false)
			{
				$this->error('身份证反面照片上传失败！',200,new StdClass());exit();
			}
			else
			{
				$save['idcard_reverse'] = $idcard_reverse_img;
			}
		}

		//开户银行信息
		$save['bank_account_name'] = isset($this->data['bank_account_name']) ? trim(addslashes(htmlspecialchars($this->data['bank_account_name']))) : '';//银行开户名
	    $save['bank_account_number'] = isset($this->data['bank_account_number']) ? trim(addslashes(htmlspecialchars($this->data['bank_account_number']))) : '';//个人银行账号
	    $save['bank_name'] = isset($this->data['bank_name']) ? trim(addslashes(htmlspecialchars($this->data['bank_name']))) : '';//开户银行支行名称
	    $save['bank_code'] = isset($this->data['bank_code']) ? trim(addslashes(htmlspecialchars($this->data['bank_code']))) : '';//支行联行号

	    //店铺经营信息
	    $save['supplier_name'] = isset($this->data['supplier_name']) ? trim(addslashes(htmlspecialchars($this->data['supplier_name']))) : ''; //店铺名称
		$save['rank_id'] = isset($this->data['rank_id']) ? intval($this->data['rank_id']) : 0;//店铺等级 
		$save['type_id'] = isset($this->data['type_id']) ? intval($this->data['type_id']) : 0;//店铺分类 

		$save['company_name'] = $save['supplier_name'];//店铺名称 
		
		$save['applynum'] = 3;//申请入驻提交完成状态
		$save['status'] = 0;//审核状态  默认为0   -1（审核不通过）、0（待审核）、1（审核通过）

		$result = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('supplier') . " WHERE user_id = '$user_id'");
		if(!empty($result)){
			$old_handheld_idcard = $result['handheld_idcard'];
			$old_idcard_front = $result['idcard_front'];
			$old_idcard_reverse = $result['idcard_reverse'];
			if ($GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('supplier'), $save, 'UPDATE', 'user_id='.$user_id) !== false){
				//删除临时文件
		        $files = glob(ROOT_PATH.'runtime/temp/*');
		        foreach($files as $file){
		            if(is_file($file)){
		                @unlink($file);
		            }
		        }
		        //删除旧图片
		        if(!empty($save['handheld_idcard'])){
		        	@unlink(ROOT_PATH.'data/supplier/'.$old_handheld_idcard);
		        }
		        if(!empty($save['idcard_front'])){
		        	@unlink(ROOT_PATH.'data/supplier/'.$old_idcard_front);
		        }
		        if(!empty($save['idcard_reverse'])){
		        	@unlink(ROOT_PATH.'data/supplier/'.$old_idcard_reverse);
		        }
				$this->success('更新申请入驻成功，等待管理员审核！请耐心等候通知。');
			}
		}else{
			$save['user_id'] = $user_id;
			if ($GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('supplier'), $save, 'INSERT', 'user_id='.$user_id) !== false){
				//删除临时文件
		        $files = glob(ROOT_PATH.'runtime/temp/*');
		        foreach($files as $file){
		            if(is_file($file)){
		                @unlink($file);
		            }
		        }
				$this->success('提交申请入驻成功，等待管理员审核！请耐心等候通知。');
			}
		}
		$this->error('操作失败！');

	}

	/**
	 * 申请入驻 - 公司
	 */
	public function apply_enter_company(){
		require_once(ROOT_PATH . 'languages/zh_cn/common.php');
		global $_LANG;
		global $_CFG;
		include_once(ROOT_PATH . 'includes/lib_main.php');
		$upload_size_limit = $_CFG['upload_size_limit'] == '-1' ? ini_get('upload_max_filesize') : $_CFG['upload_size_limit'];

		$save = array();
		$user_id = $this->user_id;

		//公司及联系人信息
		$save['company_name'] = isset($this->data['company_name']) ? trim(addslashes(htmlspecialchars($this->data['company_name']))) : '';//公司名称
		$save['country'] = isset($this->data['country']) ? intval($this->data['country']) : 1;//国家 
		$save['province'] = isset($this->data['province']) ? intval($this->data['province']) : 1;//省
		$save['city'] = isset($this->data['city']) ? intval($this->data['city']) : 1;//市
		$save['district'] = isset($this->data['district']) ? intval($this->data['district']) : 1;//区
		$save['address'] = isset($this->data['address']) ? trim(addslashes(htmlspecialchars($this->data['address']))) : '';//详细地址
		
		$save['tel'] = isset($this->data['tel']) ? trim(addslashes(htmlspecialchars($this->data['tel']))) : '';//公司电话
		$save['guimo'] = isset($this->data['guimo']) ? trim(addslashes(htmlspecialchars($this->data['guimo']))) : '';//公司规模
		$save['company_type'] = isset($this->data['company_type']) ? trim($this->data['company_type']) : '';//公司类型

		$save['contacts_name'] = isset($this->data['contacts_name']) ? trim(addslashes(htmlspecialchars($this->data['contacts_name']))) : '';//姓名
		$save['contacts_phone'] = isset($this->data['contacts_phone']) ? trim(addslashes(htmlspecialchars($this->data['contacts_phone']))) : '';//联系人电话
		$save['email'] = isset($this->data['email']) ? trim($this->data['email']) : '';//邮箱

		$save['business_licence_number'] = isset($this->data['business_licence_number']) ? trim(addslashes(htmlspecialchars($this->data['business_licence_number']))) : '';//营业执照号
	    $save['business_sphere'] = isset($this->data['business_sphere']) ? trim(addslashes(htmlspecialchars($this->data['business_sphere']))) : '';//法定经营范围
	    $save['organization_code'] = isset($this->data['organization_code']) ? trim(addslashes(htmlspecialchars($this->data['organization_code']))) : '';//组织机构代码

		//营业执照
		if (@$_FILES['zhizhao']['size'] > 0)
		{
			if($_FILES['zhizhao']['size'] / 1024 > $upload_size_limit)
			{
				$this->error(sprintf($_LANG['upload_file_limit']),200,new StdClass());exit();
			}
			$zhizhao_img = upload_file($_FILES['zhizhao'], 'supplier',$user_id);
			if ($zhizhao_img === false)
			{
				$this->error('营业执照号电子版图片上传失败！',200,new StdClass());exit();
			}
			else
			{
				$save['zhizhao'] = $zhizhao_img;
			}
		}
		//组织机构代码证电子版
		if (@$_FILES['organization_code_electronic']['size'] > 0)
		{
			if($_FILES['organization_code_electronic']['size'] / 1024 > $upload_size_limit)
			{
				$this->error(sprintf($_LANG['upload_file_limit']),200,new StdClass());exit();
			}
			$organization_code_electronic_img = upload_file($_FILES['organization_code_electronic'], 'supplier',$user_id);
			if ($organization_code_electronic_img === false)
			{
				$this->error('组织机构代码证电子版图片上传失败！',200,new StdClass());exit();
			}
			else
			{
				$save['organization_code_electronic'] = $organization_code_electronic_img;
			}
		}
		//一般纳税人证明
		if (@$_FILES['general_taxpayer']['size'] > 0)
		{
			if($_FILES['general_taxpayer']['size'] / 1024 > $upload_size_limit)
			{
				$this->error(sprintf($_LANG['upload_file_limit']),200,new StdClass());exit();
			}
			$general_taxpayer_img = upload_file($_FILES['general_taxpayer'], 'supplier',$user_id);
			if ($general_taxpayer_img === false)
			{
				$this->error('一般纳税人证明图片上传失败！',200,new StdClass());exit();
			}
			else
			{
				$save['general_taxpayer'] = $general_taxpayer_img;
			}
		}

		//开户银行信息
		$save['bank_account_name'] = isset($this->data['bank_account_name']) ? trim(addslashes(htmlspecialchars($this->data['bank_account_name']))) : '';//银行开户名
	    $save['bank_account_number'] = isset($this->data['bank_account_number']) ? trim(addslashes(htmlspecialchars($this->data['bank_account_number']))) : '';//个人银行账号
	    $save['bank_name'] = isset($this->data['bank_name']) ? trim(addslashes(htmlspecialchars($this->data['bank_name']))) : '';//开户银行支行名称
	    $save['bank_code'] = isset($this->data['bank_code']) ? trim(addslashes(htmlspecialchars($this->data['bank_code']))) : '';//支行联行号
	    
	    $save['settlement_bank_account_name'] = isset($this->data['settlement_bank_account_name']) ? trim(addslashes(htmlspecialchars($this->data['settlement_bank_account_name']))) : '';//结算账号信息
	    $save['settlement_bank_account_number'] = isset($this->data['settlement_bank_account_number']) ? trim(addslashes(htmlspecialchars($this->data['settlement_bank_account_number']))) : '';//公司银行账号
	    $save['settlement_bank_name'] = isset($this->data['settlement_bank_name']) ? trim(addslashes(htmlspecialchars($this->data['settlement_bank_name']))) : '';//开户支行名称
	    $save['settlement_bank_code'] = isset($this->data['settlement_bank_code']) ? trim(addslashes(htmlspecialchars($this->data['settlement_bank_code']))) : '';//支行联行号
	    $save['tax_registration_certificate'] = isset($this->data['tax_registration_certificate']) ? trim(addslashes(htmlspecialchars($this->data['tax_registration_certificate']))) : '';//税务登记证号
	    $save['taxpayer_id'] = isset($this->data['taxpayer_id']) ? trim(addslashes(htmlspecialchars($this->data['taxpayer_id']))) : '';//纳税人识别号

	    //开户银行许可证电子版
		if (@$_FILES['bank_licence_electronic']['size'] > 0)
		{
			if($_FILES['bank_licence_electronic']['size'] / 1024 > $upload_size_limit)
			{
				$this->error(sprintf($_LANG['upload_file_limit']),200,new StdClass());exit();
			}
			$bank_licence_electronic_img = upload_file($_FILES['bank_licence_electronic'], 'supplier',$user_id);
			if ($bank_licence_electronic_img === false)
			{
				$this->error('开户银行许可证电子版图片上传失败！',200,new StdClass());exit();
			}
			else
			{
				$save['bank_licence_electronic'] = $bank_licence_electronic_img;
			}
		}

		//税务登记证号电子版
		if (@$_FILES['tax_registration_certificate_electronic']['size'] > 0)
		{
			if($_FILES['tax_registration_certificate_electronic']['size'] / 1024 > $upload_size_limit)
			{
				$this->error(sprintf($_LANG['upload_file_limit']),200,new StdClass());exit();
			}
			$tax_registration_certificate_electronic_img = upload_file($_FILES['tax_registration_certificate_electronic'], 'supplier',$user_id);
			if ($tax_registration_certificate_electronic_img === false)
			{
				$this->error('税务登记证号电子版图片上传失败！',200,new StdClass());exit();
			}
			else
			{
				$save['tax_registration_certificate_electronic'] = $tax_registration_certificate_electronic_img;
			}
		}

	    //店铺经营信息
	    $save['supplier_name'] = isset($this->data['supplier_name']) ? trim(addslashes(htmlspecialchars($this->data['supplier_name']))) : ''; //店铺名称
		$save['rank_id'] = isset($this->data['rank_id']) ? intval($this->data['rank_id']) : 0;//店铺等级 
		$save['type_id'] = isset($this->data['type_id']) ? intval($this->data['type_id']) : 0;//店铺分类 
		
		$save['applynum'] = 3;//申请入驻提交完成状态
		$save['status'] = 0;//审核状态  默认为0   -1（审核不通过）、0（待审核）、1（审核通过）

		$result = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('supplier') . " WHERE user_id = '$user_id'");
		if(!empty($result)){
			$old_zhizhao = $result['zhizhao'];
			$old_organization_code_electronic = $result['organization_code_electronic'];
			$old_general_taxpayer = $result['general_taxpayer'];
			$old_bank_licence_electronic = $result['bank_licence_electronic'];
			$old_tax_registration_certificate_electronic = $result['tax_registration_certificate_electronic'];
			if ($GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('supplier'), $save, 'UPDATE', 'user_id='.$user_id) !== false){
				//删除临时文件
		        $files = glob(ROOT_PATH.'runtime/temp/*');
		        foreach($files as $file){
		            if(is_file($file)){
		                @unlink($file);
		            }
		        }
		        //删除旧图片
		        if(!empty($save['zhizhao'])){
		        	@unlink(ROOT_PATH.'data/supplier/'.$old_zhizhao);
		        }
		        if(!empty($save['organization_code_electronic'])){
		        	@unlink(ROOT_PATH.'data/supplier/'.$old_organization_code_electronic);
		        }
		        if(!empty($save['general_taxpayer'])){
		        	@unlink(ROOT_PATH.'data/supplier/'.$old_general_taxpayer);
		        }
		        if(!empty($save['bank_licence_electronic'])){
		        	@unlink(ROOT_PATH.'data/supplier/'.$old_bank_licence_electronic);
		        }
		        if(!empty($save['tax_registration_certificate_electronic'])){
		        	@unlink(ROOT_PATH.'data/supplier/'.$old_tax_registration_certificate_electronic);
		        }
				$this->success('更新申请入驻成功，等待管理员审核！请耐心等候通知。');
			}
		}else{
			$save['user_id'] = $user_id;
			if ($GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('supplier'), $save, 'INSERT', 'user_id='.$user_id) !== false){
				//删除临时文件
		        $files = glob(ROOT_PATH.'runtime/temp/*');
		        foreach($files as $file){
		            if(is_file($file)){
		                @unlink($file);
		            }
		        }
				$this->success('提交申请入驻成功，等待管理员审核！请耐心等候通知。');
			}
		}
		$this->error('操作失败！');

	}
	
	
}