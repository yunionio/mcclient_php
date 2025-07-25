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

class WebconsoleManager extends BaseWebconsoleManager {
    public function __construct() {
        parent::__construct("webconsole", "webconsole");
    }

    function do_connect($s, $connType, $id, $action, $params) {
        if (strlen($connType) === 0) {
            throw new Exception("Empty connection resource type");
        }
        $url = "/webconsole/$connType";
        if (strlen($id) > 0) {
            $url = "$url/$id";
        }
        if (strlen($action) > 0) {
            $url = "$url/$action";
        }
        return $this->_post($s, $url, $params, "webconsole");
    }

    function do_server_connect($s, $id) {
        return $this->do_connect($s, "server", $id, "", array());
    }
}

?>
