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

include_once(__DIR__."/../../resources.php");
include_once(__DIR__."/../../joint.php");

class ComputeManager extends ResourceManager {
    public function __construct($keyword, $keyword_plural) {
        parent::__construct("compute", "", $keyword, $keyword_plural);
    }
}

class ComputeJointManager extends JointResourceManager {
    public function __construct($keyword, $keyword_plural, $master, $slave) {
        parent::__construct("compute", "", $keyword, $keyword_plural, $master, $slave);
    }
}

?>