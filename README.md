# Cloudpods PHP SDK
======================

本仓库提供了访问Cloudpods API的PHP SDK。

Manager方法说明
-----------------

每类资源对应一个Manager，例如虚拟机对应的是 ServerManager。每个资源的Manager都继承于ResourceManager。

ResourceManager实现了一系列方法，对应后端的REST API，具体请参考 src/resources.php。

SDK调用方法
----------------

示例代码：

```php
include_once("src/client.php");
include_once("src/tokenv3.php");
include_once("src/session.php");
include_once("src/services/image/images.php");
include_once("src/services/compute/servers.php");

$client = new Client("https://192.168.222.122:30500/v3");

$domain_name = "Default";
$uname = "sysadmin";
$passwd = "passw0rd";
$project_name = "system";
$project_id = "";
$project_domain = "Default";
$token = "";
$region = "YunionHQ";
$endpointType = "public";

# 获得token
$token = $client->auth($domain_name, $uname, $passwd, $project_id, $project_name, $project_domain, $token);

# 获得session
$s = $client->get_session($token, $endpointType, $region, "");

$imgman = new ImageManager()

# 镜像预留示例
$reserve_params = array(
    "name" => "my-ubuntu-image",
    "disk_format" => "qcow2",
    "container_format" => "bare",
    "min_disk" => 20,
    "min_ram" => 1024,
    "properties" => array(
        "os_type" => "Linux",
        "os_distribution" => "Ubuntu"
    )
);
$reserved_image = $imgman->create($s, $reserve_params);
$img_id = $reserved_image["id"];

// 字符串上传（小数据）
$test_data = "This is a test string";
$uploaded_image = $image_manager->upload($session, $upload_params, $test_data, strlen($test_data));

// 文件流上传（大文件，避免内存溢出）
$file_handle = fopen($image_file_path, 'rb');
$uploaded_image = $image_manager->upload($session, $upload_params, $file_handle, $file_size);
fclose($file_handle);

# List all public images
$img_results = $imgman->list_items($s, ["is_public"=>false, "status"=>"active"]);
if (count($img_results->Data) === 0) {
    die("no image found");
}

$params = array();
$params['generate_name'] = 'test' # or params['name'] = 'test'
$params['vcpu_count'] = 1
$params['vmem_size'] = 64 # memory size 64MB
$params['disable_delete'] = false
$params['disks'] = array(
    array(
        "index"=>0,
        "image_id"=>img_id;
    ),
    array(
        "index"=>1,
        "size"=>1024,
    ),
);

$srvman = new ServerManager();

$guest = $srvman->create($s, $params);
print_r($guest);

// 等待虚拟机创建成功
wait_server_status($s, $guest["id"], "ready")

// 启动虚拟机
$result = $srvman->perform_action($s, $guest["id"], "start", array());

wait_server_status($s, $guest["id"], "running")

// 删除虚拟机，override_pending_delete=true 跳过回收站，直接删除
$srvman->delete($s, $guest["id"], ["override_pending_delete"=>true]);

function wait_server_status($srvman, $s, $id, $target_status) {
    $ret = $srvman->get($s, $id, array());
    $status = $ret["status"]
    while (strcmp($status,  $target_status) !== 0) {
        sleep(1);
    }
}

```
