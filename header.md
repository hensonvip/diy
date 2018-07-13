## <span id="api-example-for-a-submenu-entry">全局信息</span>

#### 密匙（key）和密钥(screct)

    $config = array(
         '27e56b4e4df8cf87654fed31248801bd' => array(
              'secret' => 'badbf2a847ac732ea8af0739167d689b'
          )
    );

#### 域名

    http://gzxueyou.sz2.hostadm.net/

#### 错误示例

    {
        "code": 1025,
        "message": "缺少必填参数",
        "data": []
    }
    {
        "code": 1003,
        "message": "Signature Verification Failed",
        "data": []
    }
    {
        "code": 1088,
        "message": "API版本号不存在",
        "data": []
    }
    {
        "code": 1002,
        "message": "Class Not Found",
        "data": []
    }
    {
        "code": 1001,
        "message": "Method Not Found",
        "data": []
    }

#### 所有请求必须参数：
| 参数        | 必填      |  字段名称  |  类型  |  示例  |  描述  |
| --------      | -----        | :----:            | :----:    | :----:    | :----:    |
| device         | 是      |   请求机型    |   string    |   android    |   取值如下：android, ios, xcx, pc, wap    |
| version        | 是      |   接口版本号    |   string    |   v1    |   接口版本号，默认为v1，现在只支持v1    |
| timestamp   | 是      |   请求发起时间戳    |   string    |   1508774400      |   数据请求发起时间戳    |

#### 返回字段名称：
| 字段        | 字段名称  |
| --------      | -----       |
| code       | 500请求接口失败的状态码，200请求接口成功的状态码  |
| data        | 接口访问成功时返回的数据      |
| message   | 接口访问时返回的提示文字   |

#### 分页返回字段：
| 参数        |  字段名称  |  类型  |  为空时  |  备注  |
| --------      | :----:            | :----:    | :----:    | :----:    |
| page       |  当前页数  | integral |   1    |    |
| page_size    |   每页显示的数量    |   integral    |   10    |   |
| record_count   |   数据总数    |   integral    |   0  |     |
| page_count   |   总页数    |   integral    |   0  |     |

#### 分页接口传递参数：
| 参数        |  字段名称  |  类型  |  为空时  |  备注  |
| --------      | :----:            | :----:    | :----:    | :----:    |
| page       |  当前页数  | integral |   1   |   可选   |
| page_size    |   每页显示的数量   |   integral   |   10    |   可选   |

