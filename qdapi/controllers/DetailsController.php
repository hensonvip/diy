<?php
/**
 * 创意擂台
 */
require_once(ROOT_PATH . 'includes/cls_diy.php');
require_once(ROOT_PATH . 'includes/cls_user.php');

class DetailsController extends ApiController
{
    public function __construct()
    {
        parent::__construct();
        $this->data = $this->input();
        $this->diy = cls_diy::getInstance();
        $this->user = cls_user::getInstance();
    }

    public function index(){

    }

}
