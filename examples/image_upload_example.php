<?php
// Copyright 2019 Yunion
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//     http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.

/**
 * 镜像预留和上传示例
 * 演示如何使用PHP SDK的镜像预留和上传功能
 */

include_once("src/client.php");
include_once("src/tokenv3.php");
include_once("src/session.php");
include_once("src/services/image/images.php");

// 配置信息
$auth_url = "https://your-cloudpods-endpoint/v3";
$domain_name = "Default";
$username = "your-username";
$password = "your-password";
$project_name = "your-project";
$project_id = "";
$project_domain = "Default";
$region = "your-region";
$endpoint_type = "public";

// 创建客户端
$client = new Client($auth_url);

// 认证
$token = $client->auth($domain_name, $username, $password, $project_id, $project_name, $project_domain);

// 获取session
$session = $client->get_session($token, $endpoint_type, $region);

// 创建镜像管理器
$image_manager = new ImageManager();

echo "=== 镜像预留和上传示例 ===\n\n";

// 示例1: 镜像预留（创建镜像记录但不上传数据）
echo "1. 镜像预留示例:\n";
$reserve_params = array(
    "name" => "test-image-reserve",
    "disk_format" => "iso",
    "min_disk" => 30*1024,
    "min_ram" => 512,
    "is_public" => false,
    "properties" => array(
        "os_type" => "Linux",
        "os_distribution" => "CULinux",
        "os_version" => "3.0"
    )
);

try {
    $reserved_image = $image_manager->create($session, $reserve_params);
    echo "镜像预留成功，镜像ID: " . $reserved_image["id"] . "\n";
    echo "镜像名称: " . $reserved_image["name"] . "\n";
    echo "状态: " . $reserved_image["status"] . "\n\n";
    
    $image_id = $reserved_image["id"];
} catch (Exception $e) {
    echo "镜像预留失败: " . $e->getMessage() . "\n\n";
    exit(1);
}

// 示例2: 上传镜像数据到预留的镜像记录
echo "2. 镜像上传示例:\n";
$image_file_path = "/home/yunion/CULinux-3.0-kr108-17220.x86_64.iso"; // 替换为实际的镜像文件路径

if (!file_exists($image_file_path)) {
    echo "镜像文件不存在: $image_file_path\n";
    echo "跳过上传步骤\n\n";
} else {
    try {
        $file_size = filesize($image_file_path);
        echo "文件大小: " . $file_size . " bytes\n";
        
        // 使用文件流而不是加载整个文件到内存
        $file_handle = fopen($image_file_path, 'rb');
        if (!$file_handle) {
            throw new Exception("无法打开文件: $image_file_path");
        }
        
        $upload_params = array(
            "image_id" => $image_id,
            "disk_format" => "iso",
            "container_format" => "bare"
        );
        
        // 使用合并后的上传方法（自动检测文件句柄）
        $uploaded_image = $image_manager->upload($session, $upload_params, $file_handle, $file_size);
        fclose($file_handle);
        
        echo "镜像上传成功\n";
        echo "镜像ID: " . $uploaded_image["id"] . "\n";
        echo "状态: " . $uploaded_image["status"] . "\n";
        echo "大小: " . $uploaded_image["size"] . " bytes\n\n";
    } catch (Exception $e) {
        echo "镜像上传失败: " . $e->getMessage() . "\n\n";
        if (isset($file_handle) && $file_handle) {
            fclose($file_handle);
        }
    }
}

// 示例3: 从URL复制镜像
echo "3. 从URL复制镜像示例:\n";
$copy_params = array(
    "name" => "test-image-copy",
    "disk_format" => "qcow2",
    "container_format" => "bare",
    "copy_from" => "https://cloud.debian.org/images/cloud/buster/latest/debian-10-genericcloud-amd64.qcow2",
    "min_disk" => 10,
    "min_ram" => 512
);

try {
    $copied_image = $image_manager->create($session, $copy_params);
    echo "镜像复制成功，镜像ID: " . $copied_image["id"] . "\n";
    echo "镜像名称: " . $copied_image["name"] . "\n";
    echo "状态: " . $copied_image["status"] . "\n\n";

    $image_id = $reserved_image["id"];
} catch (Exception $e) {
    echo "镜像复制失败: " . $e->getMessage() . "\n\n";
}

// 示例4: 下载镜像
echo "4. 镜像下载示例:\n";
try {
    $download_result = $image_manager->download($session, $image_id, "qcow2", false);
    $meta = $download_result[0];
    $image_data = $download_result[1];
    $size = $download_result[2];
    
    echo "镜像下载成功\n";
    echo "镜像大小: " . $size . " bytes\n";
    echo "元数据: " . json_encode($meta, JSON_PRETTY_PRINT) . "\n";
    
    // 可以选择保存到文件
    // file_put_contents("downloaded_image.qcow2", $image_data);
    echo "镜像数据已获取，可以保存到文件\n\n";
} catch (Exception $e) {
    echo "镜像下载失败: " . $e->getMessage() . "\n\n";
}

// 示例5: 列出镜像
echo "5. 列出镜像示例:\n";
try {
    $list_params = array(
        "limit" => 10,
        "details" => true
    );
    
    $images = $image_manager->list_items($session, $list_params);
    echo "找到 " . count($images->Data) . " 个镜像:\n";
    
    foreach ($images->Data as $img) {
        echo "- " . $img["name"] . " (ID: " . $img["id"] . ", 状态: " . $img["status"] . ")\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "列出镜像失败: " . $e->getMessage() . "\n\n";
}

echo "=== 示例完成 ===\n";
?> 
