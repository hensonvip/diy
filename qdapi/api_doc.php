<?php
// apidoc -i qdapi/ -o docs

/**
* @api {post} /qdapi/?act=diy/uploadFile 导入图片
* @apiVersion 1.0.0
* @apiName uploadFile
* @apiGroup DIY
* @apiSampleRequest /qdapi/?act=diy/uploadFile
* @apiParam (参数值) {Int} debug 1为调试模式
* @apiParam (参数值) {Int} user_id 用户ID
* @apiParam (参数值) {Binary} file 文件
* @apiSuccessExample {json} 成功示例
{
    "code": 200,
    "message": "SUCCESS",
    "data": {
        "file_id": 11,
        "file_url": "data/diy/201804/1523505643646616934.png"
    }
}
*/