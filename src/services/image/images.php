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
}

?>