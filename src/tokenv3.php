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

class TokenV3 {
    private $id;
    private $token;

    function __construct($id, $token) {
        $this->id = $id;
        $this->token = $token;
    }

    function get_token_string() {
        return $this->id;
    }

    function get_domain_id() {
        return $this->token["user"]["domain"]["id"];
    }

    function get_domain_name() {
        return $this->token["user"]["domain"]["name"];
    }

    function get_project_id() {
        return $this->token["project"]["id"];
    }

    function get_project_name() {
        return $this->token["project"]["name"];
    }

    function get_project_domain_id() {
        return $this->token["project"]["domain"]["id"];
    }

    function get_project_domain_name() {
        return $this->token["project"]["domain"]["name"];
    }

    function get_user_id() {
        return $this->token["user"]["id"];
    }

    function get_user_name() {
        return $this->token["user"]["name"];
    }

    function get_roles() {
        $roles = array();
        for ($i = 0; $i < count($this->token["roles"]); $i++) {
            array_push($roles, $this->token["roles"][$i]["name"]);
        }
        return $roles;
    }

    function get_role_ids() {
        $roles = array();
        for ($i = 0; $i < count($this->token["roles"]); $i++) {
            array_push($roles, $this->token["roles"][$i]["id"]);
        }
        return $roles;
    }

    function get_expires() {
        return $this->token["expires_at"];
    }

    function get_catalog() {
        return new Catalog($this->token["catalog"]);
    }
}

function get_region_id($region, $zone) {
    if (strlen($region) > 0 && strlen($zone) > 0) {
        return $region."-".$zone;
    } else {
        return $region;
    }
}

class Catalog {
    private $catalog;

    function __construct($catalog) {
        $this->catalog = $catalog;
    }

    function is_empty() {
        return count($this->catalog) === 0;
    }

    function get_service_url($service, $region="", $zone="", $endpointType="") {
        $urls = $this->get_service_urls($service, $region, $zone, $endpointType);
        return $urls[rand(0, count($urls)-1)];
    }

    function get_service_urls($service, $region="", $zone="", $endpointType="") {
        if ($endpointType == "") {
            $endpointType = "internalURL";
        }
        for ($i = 0; $i < count($this->catalog); $i++) {
            if ($service === $this->catalog[$i]["type"]) {
                if (count($this->catalog[$i]["endpoints"]) === 0) {
                    continue;
                }
                $selected = array();
                $regeps = array();
                $regionzone = "";
                if (strlen($zone) > 0) {
                    $regionzone = get_region_id($region, $zone);
                }
                for ($j = 0; $j < count($this->catalog[$i]["endpoints"]); $j++) {
                    $ep = $this->catalog[$i]["endpoints"][$j];
                    if (strpos($endpointType, $ep["interface"]) === 0 && (
                        strlen($region) === 0 || 
                        strcmp($region, $ep["region_id"]) === 0 || 
                        strcmp($regionzone, $ep["region_id"]) === 0
                        )) {
                        if (!array_key_exists($ep["region_id"], $regeps)) {
                            $regeps[$ep["region_id"]] = array();
                        }
                        array_push($regeps[$ep["region_id"]], $ep["url"]);
                    }
                }
                if (strlen($region) === 0) {
                    if (count($regeps) > 0) {
                        foreach($regeps as $eps) {
                            return $eps;
                        }
                    } else {
                        throw new Exception("No default region for region(".$region.") zone(".$zone.")");
                    }
                } else {
                    if (array_key_exists($regionzone, $regeps)) {
                        return $regeps[$regionzone];
                    } else if (array_key_exists($region, $regeps)) {
                        return $regeps[$region];
                    } else {
                        throw new Exception("No valid ".$endpointType." endpoints for ".$service." in region ".get_region_id($region, $zone));
                    }
                }
            }
        }
    }
}

?>