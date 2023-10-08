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

interface IJointManager extends IBaseManager {

    public function master_manager();
    public function slave_manager();

    public function get($session, $mid, $sid, $params);
    
    public function list_descendent($session, $mid, $params);
    public function list_descendent2($session, $sid, $params);
    public function list_ascendent($session, $mid, $params);
    
    public function attach($session, $mid, $sid, $params);
    public function detach($session, $mid, $sid, $params);
    public function update($session, $mid, $sid, $query, $params);
    public function patch($session, $mid, $sid, $query, $params);
}

class JointResourceManager extends BaseResourceManager implements IJointManager {
    private $master_manager;
    private $slave_manager;

    function __construct($service, $version, $keyword, $keyword_plural, $master_manager, $slave_manager) {
        parent::__construct($service, $version, $keyword, $keyword_plural);
        $this->master_manager = $master_manager;
        $this->slave_manager = $slave_manager;
    }

    function master_manager() {
        return $this->master_manager;
    }

    function slave_manager() {
        return $this->slave_manager;
    }

    function list_items($session, $params) {
        $path = "/".$this->get_keyword_plural();
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

    function get($session, $mid, $sid, $params) {
        $path = "/".$this->master_manager->get_keyword_plural()."/".urlencode($mid)."/".$this->slave_manager->get_keyword_plural()."/".urlencode($sid);
        if (count($params) > 0) {
            $qs = http_build_query($params);
            $path = $path."?".$qs;
        }
        return $this->_get($session, $path, $this->get_keyword());
    }

    function list_descendent($session, $mid, $params) {
        $path = "/".$this->master_manager->get_keyword_plural()."/".urlencode($mid)."/".$this->slave_manager->get_keyword_plural();
        if (count($params) > 0) {
            $qs = http_build_query($params);
            $path = $path."?".$qs;
        }
        return $this->_list($session, $path, $this->get_keyword_plural());
    }

    function list_descendent2($session, $sid, $params) {
        $path = "/".$this->slave_manager->get_keyword_plural()."/".urlencode($sid)."/".$this->master_manager->get_keyword_plural();
        if (count($params) > 0) {
            $qs = http_build_query($params);
            $path = $path."?".$qs;
        }
        return $this->_list($session, $path, $this->get_keyword_plural());
    }

    function list_ascendent($session, $sid, $params) {
        return $this->list_descendent2($session, $sid, $params);
    }

    function params2body($params, $key) {
        $body = array();
        if (!is_null($params)) {
            $body[$key] = $params;
        }
        return $body;
    }

    function attach($session, $mid, $sid, $params) {
        $path = "/".$this->master_manager->get_keyword_plural()."/".urlencode($mid)."/".$this->slave_manager->get_keyword_plural()."/".urlencode($sid);
        return $this->_post($session, $path, $this->params2body($params, $this->get_keyword()), $this->get_keyword());
    }
    
    function detach($session, $mid, $sid, $params) {
        $path = "/".$this->master_manager->get_keyword_plural()."/".urlencode($mid)."/".$this->slave_manager->get_keyword_plural()."/".urlencode($sid);
        return $this->_delete($session, $path, $this->params2body($params, $this->get_keyword()), $this->get_keyword());
    }

    function update($session, $mid, $sid, $query, $params) {
        $path = "/".$this->master_manager->get_keyword_plural()."/".urlencode($mid)."/".$this->slave_manager->get_keyword_plural()."/".urlencode($sid);
        if (count($query) > 0) {
            $qs = http_build_query($query);
            $path = $path."?".$qs;
        }
        return $this->_put($session, $path, $this->params2body($params, $this->get_keyword()), $this->get_keyword());
    }

    function patch($session, $mid, $sid, $query, $params) {
        $path = "/".$this->master_manager->get_keyword_plural()."/".urlencode($mid)."/".$this->slave_manager->get_keyword_plural()."/".urlencode($sid);
        if (count($query) > 0) {
            $qs = http_build_query($query);
            $path = $path."?".$qs;
        }
        return $this->_patch($session, $path, $this->params2body($params, $this->get_keyword()), $this->get_keyword());
    }

}


?>