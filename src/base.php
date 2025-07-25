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

include_once("list.php");

class BaseManager {
    private $service;
    private $version;

    function __construct($service, $version) {
        $this->service = $service;
        $this->version = $version;
    }

    function get_version() {
        return $this->version;
    }

    function get_service_type() {
        return $this->service;
    }

    function versioned_url($path) {
        $path = ltrim($path, "/");
        if (strlen($this->version) > 0) {
            return "/".$this->version."/".$path;
        } else {
            return "/".$path;
        }
    }

    function json_request($session, $method, $path, $header, $body) {
        return $session->json_request($this->service, $method, $this->versioned_url($path), $header, $body);
    }

    function raw_request($session, $method, $path, $header, $body) {
        return $session->raw_request($this->service, $method, $this->versioned_url($path), $header, $body);
    }

    function _list($session, $path, $response_key) {
        $result = $this->json_request($session, "GET", $path, null, null);
        $body = $result[1];
        if (!array_key_exists($response_key, $body)) {
            throw new Exception("Response key '$response_key' not found in response");
        }
        $data = $body[$response_key];
        $total = count($data);
        if (array_key_exists("total", $body)) {
            $total = $body["total"];
        }
        $limit = 0;
        if (array_key_exists("limit", $body)) {
            $limit = $body["limit"];
        }
        $offset = 0;
        if (array_key_exists("offset", $body)) {
            $offset = $body["offset"];
        }
        $totals = array();
        if (array_key_exists("totals", $body)) {
            $totals = $body["totals"];
        }
        $ret = new ListResult();
        $ret->Data = $data;
        $ret->Total = $total;
        $ret->Limit = $limit;
        $ret->Offset = $offset;
        $ret->Totals = $totals;
        return $ret;
    }

    function _submit($session, $method, $path, $body, $resp_key) {
        $result = $this->json_request($session, $method, $path, array(), $body);
        if (strcmp($method, "HEAD") === 0) {
            $ret = array();
            $hdrPrefix = "x-".$resp_key."-";
            foreach($result[0] as $k=>$v) {
                if (strpos($k, $hdrPrefix) === 0 && count($v) > 0) {
                    if (count($v) === 1) {
                        $ret[$k] = $v[0];
                    } else {
                        $ret[$k] = $v;
                    }
                }
            }
            return $ret;
        }
        $body = $result[1];
        if (strlen($resp_key) === 0) {
            return $body;
        }
        return $body[$resp_key];
    }

    function _get($session, $path, $resp_key) {
        return $this->_submit($session, "GET", $path, null, $resp_key);
    }

    function _head($session, $path, $resp_key) {
        return $this->_submit($session, "HEAD", $path, null, $resp_key);
    }
    
    function _post($session, $path, $body, $resp_key) {
        return $this->_submit($session, "POST", $path, $body, $resp_key);
    }
    
    function _put($session, $path, $body, $resp_key) {
        return $this->_submit($session, "PUT", $path, $body, $resp_key);
    }
    
    function _patch($session, $path, $body, $resp_key) {
        return $this->_submit($session, "PATCH", $path, $body, $resp_key);
    }
    
    function _delete($session, $path, $body, $resp_key) {
        return $this->_submit($session, "DELETE", $path, $body, $resp_key);
    }
}

?>