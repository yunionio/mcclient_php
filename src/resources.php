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

interface IBaseManager {
    public function get_version();
    public function get_keyword();
    public function get_keyword_plural();
    public function get_service_type();
    
    // 列表
    public function list_items($session, $params);
}

interface IManager extends IBaseManager {
    // 列表
    public function list_in_context($session, $params, $ctx, $ctxid);
    public function list_in_contexts($session, $params, $ctxs);

    // 获取单个资源的详情
    public function get($session, $id, $params);
    public function get_in_context($session, $id, $params, $ctx, $ctxid);
    public function get_in_contexts($session, $id, $params, $ctxs);

    // 通过head方法获取单个资源的详情，目前只有 images 支持
    public function head($session, $id, $params);
    public function head_in_context($session, $id, $params, $ctx, $ctxid);
    public function head_in_contexts($session, $id, $params, $ctxs);

    // 获取具体某个资源的spec的属性，例如虚拟机的vnc信息等
    public function get_specific($session, $id, $spec, $params);
    public function get_specific_in_context($session, $id, $spec, $params, $ctx, $ctxid);
    public function get_specific_in_contexts($session, $id, $spec, $params, $ctxs);

    // 创建资源
    public function create($session, $params);
    public function create_in_context($session, $params, $ctx, $ctxid);
    public function create_in_contexts($session, $params, $ctxs);

    // 批量创建资源
    public function batch_create($session, $params, $count);
    public function batch_create_in_context($session, $params, $count, $ctx, $ctxid);
    public function batch_create_in_contexts($session, $params, $count, $ctxs);

    // 更新资源属性
    public function put($session, $id, $params);
    public function put_in_context($session, $id, $params, $ctx, $ctxid);
    public function put_in_contexts($session, $id, $params, $ctxs);

    // 更新资源属性，只有少数资源支持
    public function patch($session, $id, $params);
    public function patch_in_context($session, $id, $params, $ctx, $ctxid);
    public function patch_in_contexts($session, $id, $params, $ctxs);

    // 执行资源整体的操作，例如 validate_create_data
    public function perform_class_action($session, $action, $params);
    public function perform_class_action_in_context($session, $action, $params, $ctx, $ctxid);
    public function perform_class_action_in_contexts($session, $action, $params, $ctxs);

    // 执行某个资源的操作的方法，例如开机，perform_action($s, $srv_id, "start", $params)
    public function perform_action($session, $id, $action, $params);
    public function perform_action_in_context($session, $id, $action, $params, $ctx, $ctxid);
    public function perform_action_in_contexts($session, $id, $action, $params, $ctxs);

    // 删除某个具体资源
    public function delete_item($session, $id, $query, $body);
    public function delete_in_context($session, $id, $query, $body, $ctx, $ctxid);
    public function delete_in_contexts($session, $id, $query, $body, $ctxs);
}

class ManagerContext {
    public $Manager;
    public $Id;

    public function __construct($m, $i) {
        $this->Manager = $m;
        $this->Id = $i;
    }
}

function NewContext($m, $id) {
    return new ManagerContext($m, $id);
}

class BaseResourceManager extends BaseManager {
    private $keyword;
    private $keyword_plural;

    function __construct($service, $version, $keyword, $keyword_plural) {
        parent::__construct($service, $version);
        $this->keyword = $keyword;
        $this->keyword_plural = $keyword_plural;
    }

    function get_keyword() {
        return $this->keyword;
    }

    function get_keyword_plural() {
        return $this->keyword_plural;
    }
}

class ResourceManager extends BaseResourceManager implements IManager {

    function __construct($service, $version, $keyword, $keyword_plural) {
        parent::__construct($service, $version, $keyword, $keyword_plural);
    }

    function url_path() {
        return urlencode($this->get_keyword_plural());
    }

    function context_path($ctxs) {
        $segs = array();
        for ($i = 0; $i < count($ctxs); $i++) {
            $ctx = $ctxs[$i];
            array_push($segs, urlencode($ctx->Manager->get_keyword_plural()));
            if (strlen($ctx->Id) > 0) {
                array_push($segs, urlencode($ctx->id));
            }
        }
        array_push($segs, $this->url_path());
        return implode("/", $segs);
    }

    function list_items($session, $params) {
        return $this->list_in_contexts($session, $params, array());
    }

    function list_in_context($session, $params, $ctx, $ctxid) {
        return $this->list_in_contexts($session, $params, array(NewContext($ctx, $ctxid)));
    }

    function list_in_contexts($session, $params, $ctxs) {
        $path = "/".$this->context_path($ctxs);
        if (is_null($params)) {
            $params = array();
        }
        if (!array_key_exists("limit", $params)) {
            $params["limit"] = 20;
        }
        if (count($params) > 0) {
            $qs = http_build_query($params);
            $path = $path."?".$qs;
        }
        return $this->_list($session, $path, $this->get_keyword_plural());
    }

    function get($session, $id, $params) {
        return $this->get_in_contexts($session, $id, $params, array());
    }

    function get_in_context($session, $id, $params, $ctx, $ctxid) {
        return $this->get_in_contexts($session, $id, $params, array(NewContext($ctx, $ctxid)));
    }

    function get_in_contexts($session, $id, $params, $ctxs) {
        $path = "/".$this->context_path($ctxs)."/".urlencode($id);
        if (count($params) > 0) {
            $qs = http_build_query($params);
            $path = $path."?".$qs;
        }
        return $this->_get($session, $path, $this->get_keyword());
    }

    function head($session, $id, $params) {
        return $this->head_in_contexts($session, $id, $params, array());
    }
    
    function head_in_context($session, $id, $params, $ctx, $ctxid) {
        return $this->head_in_contexts($session, $id, $params, array());
    }

    function head_in_contexts($session, $id, $params, $ctxs) {
        $path = "/".$this->context_path($ctxs)."/".urlencode($id);
        if (count($params) > 0) {
            $qs = http_build_query($params);
            $path = $path."?".$qs;
        }
        return $this->_head($session, $path, $this->get_keyword());
    }

    function get_specific($session, $id, $spec, $params) {
        return $this->get_specific_in_contexts($session, $id, $spec, $params, array());
    }

    function get_specific_in_context($session, $id, $spec, $params, $ctx, $ctxid) {
        return $this->get_specific_in_contexts($session, $id, $spec, $params, array());
    }

    function get_specific_in_contexts($session, $id, $spec, $params, $ctxs) {
        $path = "/".$this->context_path($ctxs)."/".urlencode($id)."/".urlencode($spec);
        if (count($params) > 0) {
            $qs = http_build_query($params);
            $path = $path."?".$qs;
        }
        return $this->_get($session, $path, $this->get_keyword());
    }

    function params2body($params, $key) {
        $body = array();
        if (!is_null($params)) {
            $body[$key] = $params;
        }
        return $body;
    }

    function create($session, $params) {
        return $this->create_in_contexts($session, $params, array());
    }

    function create_in_context($session, $params, $ctx, $ctxid) {
        return $this->create_in_contexts($session, $params, array(NewContext($ctx, $ctxid)));
    }

    function create_in_contexts($session, $params, $ctxs) {
        $path = "/".$this->context_path($ctxs);
        return $this->_post($session, $path, $this->params2body($params, $this->get_keyword()), $this->get_keyword());
    }

    function batch_create($session, $params, $count) {
        return $this->batch_create_in_contexts($session, $params, $count, array());
    }

    function batch_create_in_context($session, $params, $count, $ctx, $ctxid) {
        return $this->batch_create_in_contexts($session, $params, $count, array(NewContext($ctx, $ctxid)));
    }

    function batch_create_in_contexts($session, $params, $count, $ctxs) {
        $path = "/".$this->context_path($ctxs);
        $body = $this->params2body($params, $this->get_keyword());
        $body["count"] = $count;
        return $this->_post($session, $path, $body, $this->get_keyword_plural());
    }

    function put($session, $id, $params) {
        return $this->put_in_contexts($session, $id, $params, array());
    }

    function put_in_context($session, $id, $params, $ctx, $ctxid) {
        return $this->put_in_contexts($session, $id, $params, array(NewContext($ctx, $ctxid)));
    }

    function put_in_contexts($session, $id, $params, $ctxs) {
        $path = "/".$this->context_path($ctxs)."/".urlencode($id);
        $body = $this->params2body($params, $this->get_keyword());
        return $this->_put($session, $path, $body, $this->get_keyword());
    }

    function patch($session, $id, $params) {
        return $this->patch_in_contexts($session, $id, $params, array());
    }

    function patch_in_context($session, $id, $params, $ctx, $ctxid) {
        return $this->patch_in_contexts($session, $id, $params, array(NewContext($ctx, $ctxid)));
    }

    function patch_in_contexts($session, $id, $params, $ctxs) {
        $path = "/".$this->context_path($ctxs)."/".urlencode($id);
        $body = $this->params2body($params, $this->get_keyword());
        return $this->_patch($session, $path, $body, $this->get_keyword());
    }

    public function perform_class_action($session, $action, $params) {
        return $this->perform_class_action_in_contexts($session, $action, $params, array());
    }

    public function perform_class_action_in_context($session, $action, $params, $ctx, $ctxid) {
        return $this->perform_class_action_in_contexts($session, $action, $params, array(NewContext($ctx, $ctxid)));
    }

    public function perform_class_action_in_contexts($session, $action, $params, $ctxs) {
        $path = "/".$this->context_path($ctxs)."/".urlencode($action);
        $body = $this->params2body($params, $this->get_keyword_plural());
        return $this->_post($session, $path, $body, $this->get_keyword_plural());
    }

    function perform_action($session, $id, $action, $params) {
        return $this->perform_action_in_contexts($session, $id, $action, $params, array());
    }

    function perform_action_in_context($session, $id, $action, $params, $ctx, $ctxid) {
        return $this->perform_action_in_contexts($session, $id, $action, $params, array(NewContext($ctx, $ctxid)));
    }

    function perform_action_in_contexts($session, $id, $action, $params, $ctxs) {
        $path = "/".$this->context_path($ctxs)."/".urlencode($id)."/".urlencode($action);
        $body = $this->params2body($params, $this->get_keyword());
        return $this->_post($session, $path, $body, $this->get_keyword());
    }

    function delete_item($session, $id, $query, $params) {
        return $this->delete_in_contexts($session, $id, $query, $params, array());
    }

    function delete_in_context($session, $id, $query, $params, $ctx, $ctxid) {
        return $this->delete_in_contexts($session, $id, $query, $params, array(NewContext($ctx, $ctxid)));
    }

    function delete_in_contexts($session, $id, $query, $params, $ctxs) {
        $path = "/".$this->context_path($ctxs)."/".urlencode($id);
        if (count($query) > 0) {
            $qs = http_build_query($query);
            $path = $path."?".$qs;
        }
        $body = $this->params2body($params, $this->get_keyword());
        return $this->_post($session, $path, $body, $this->get_keyword());
    }
}

?>
