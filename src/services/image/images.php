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

include_once("base.php");

class ImageManager extends BaseImageManager {
    public function __construct() {
        parent::__construct("image", "images");
    }

    function list_items($session, $params) {
        if (array_key_exists("details", $params) && $params["details"]) {
            $path = "/".$this->context_path(array())."/detail";
            unset($params["details"]);
            if (count($params) > 0) {
                $qs = http_build_query($params);
                $path = $path."?".$qs;
            }
            return $this->_list($session, $path, $this->get_keyword_plural());
        }
        return parent::list_items($session, $params);
    }

    /**
     * 创建镜像预留记录
     * 参考golang SDK的Create方法
     */
    function create($session, $params) {
        return $this->_create($session, $params, null, 0);
    }

    /**
     * 上传镜像数据
     * 支持字符串和文件句柄两种方式
     * 参考golang SDK的Upload方法
     */
    function upload($session, $params, $body, $size = 0) {
        return $this->_create($session, $params, $body, $size);
    }

    /**
     * 内部创建方法，支持预留和上传
     * 参考golang SDK的_create方法
     */
    private function _create($session, $params, $body, $size) {
        $image_id = isset($params["image_id"]) ? $params["image_id"] : "";
        $path = "/".$this->context_path(array());
        $method = "POST";
        
        if (strlen($image_id) > 0) {
            $path = "/".$this->context_path(array())."/".urlencode($image_id);
            $method = "PUT";
        } else {
            if (!isset($params["name"]) && !isset($params["generate_name"])) {
                die("Missing parameter: name or generate_name");
            }
        }

        $headers = $this->_set_image_meta($params);
        
        $copy_from_url = isset($params["copy_from"]) ? $params["copy_from"] : "";
        $compress_format = isset($params["compress_format"]) ? $params["compress_format"] : "";
        
        if (strlen($copy_from_url) > 0) {
            if ($size != 0) {
                die("Can't use copy_from and upload file at the same time");
            }
            $body = null;
            $size = 0;
            array_push($headers, "x-glance-api-copy-from: ".$copy_from_url);
            array_push($headers, "x-glance-compress-format: ".$compress_format);
        }
        
        if ($body !== null) {
            array_push($headers, "Content-Type: application/octet-stream");
            if ($size > 0) {
                array_push($headers, "Content-Length: ".$size);
            }
        }

        $result = $this->raw_request($session, $method, $path, $headers, $body);
        $response_body = $result[1];
        $response_json = json_decode($response_body, true);
        
        if ($response_json === null) {
            die("Invalid JSON response");
        }
        
        if (!isset($response_json["image"])) {
            die("Invalid response: missing 'image' field: " . $response_body);
        }
        
        return $response_json["image"];
    }



    /**
     * 设置镜像元数据到HTTP头
     * 参考golang SDK的setImageMeta方法
     */
    private function _set_image_meta($params) {
        $headers = array();
        
        foreach ($params as $k => $v) {
            if ($k == "copy_from" || $k == "properties" || $k == "compress_format") {
                continue;
            }
            if (is_string($v) || is_numeric($v) || is_bool($v)) {
                $header_value = "X-Image-Meta-".$this->_capitalize($k).": ".strval($v);
                array_push($headers, $header_value);
            }
        }
        
        if (isset($params["properties"]) && is_array($params["properties"])) {
            foreach ($params["properties"] as $k => $v) {
                if (is_string($v)) {
                    array_push($headers, "X-Image-Meta-Property-".$this->_capitalize($k).": ".$v);
                }
            }
        }
        
        return $headers;
    }

    /**
     * 首字母大写
     */
    private function _capitalize($str) {
        return ucfirst($str);
    }

    /**
     * 下载镜像
     * 参考golang SDK的Download方法
     */
    function download($session, $id, $format = "", $torrent = false) {
        $query = array();
        if (strlen($format) > 0) {
            $query["format"] = $format;
            if ($torrent) {
                $query["torrent"] = "true";
            }
        }
        
        $path = "/".$this->context_path(array())."/".urlencode($id);
        if (count($query) > 0) {
            $qs = http_build_query($query);
            $path = $path."?".$qs;
        }
        
        $result = $this->raw_request($session, "GET", $path, null, null);
        $headers = $result[0];
        $body = $result[1];
        
        // 从响应头中获取大小信息
        $size = -1;
        if (isset($headers["content-length"]) && count($headers["content-length"]) > 0) {
            $size = intval($headers["content-length"][0]);
        }
        
        return array($this->_fetch_image_meta($headers), $body, $size);
    }

    /**
     * 从HTTP头中提取镜像元数据
     * 参考golang SDK的FetchImageMeta方法
     */
    private function _fetch_image_meta($headers) {
        $meta = array();
        $meta["properties"] = array();
        
        foreach ($headers as $k => $v) {
            if (count($v) == 0) continue;
            
            $value = $v[0];
            
            if ($k == "x-image-meta-metadata") {
                $metadata = json_decode($value, true);
                if ($metadata !== null) {
                    $meta["metadata"] = $metadata;
                }
            } else if ($k == "x-image-meta-project_metadata") {
                $metadata = json_decode($value, true);
                if ($metadata !== null) {
                    $meta["project_metadata"] = $metadata;
                }
            } else if (strpos($k, "x-image-meta-property-") === 0) {
                $prop_key = strtolower(substr($k, strlen("x-image-meta-property-")));
                $meta["properties"][$prop_key] = $this->_decode_meta($value);
                // 同时支持下划线版本
                $prop_key_underscore = str_replace("-", "_", $prop_key);
                $meta["properties"][$prop_key_underscore] = $this->_decode_meta($value);
            } else if (strpos($k, "x-image-meta-") === 0) {
                $meta_key = strtolower(substr($k, strlen("x-image-meta-")));
                $meta[$meta_key] = $this->_decode_meta($value);
                // 同时支持下划线版本
                $meta_key_underscore = str_replace("-", "_", $meta_key);
                $meta[$meta_key_underscore] = $this->_decode_meta($value);
            }
        }
        
        return $meta;
    }

    /**
     * 解码元数据值
     * 参考golang SDK的decodeMeta方法
     */
    private function _decode_meta($str) {
        $decoded = urldecode($str);
        if ($decoded !== $str) {
            return $this->_decode_meta($decoded);
        }
        return $str;
    }
}

?>