<?php
/**
 * 全景控制器
 */
require_once(ROOT_PATH . 'includes/cls_common.php');

class GlobalController extends ApiController
{
    public function __construct()
    {
        parent::__construct();
        $this->data = $this->input();
        $this->common = cls_common::getInstance();
    }

    /* 获取蒙版 */
    public function getSysCfg(){
        $sys_cfg = $this->common->get_Sys_Cfg();
        $this->success($sys_cfg);
    }
}
