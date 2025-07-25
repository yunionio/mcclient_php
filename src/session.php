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

const DEFAULT_API_VERSION = "v1";

function join_path2($p1, $p2) {
    return rtrim($p1, "/")."/".ltrim($p2, "/");
}

function join_path3($p1, $p2, $p3) {
    return join_path2(join_path2($p1, $p2), $p3);
}

class Session {
    private $client;
    private $region;
    private $zone;
    private $endpoint_type;
    private $token;
    private $catalog;

    function __construct($client, $region, $zone, $endpoint_type, $token) {
        $this->client = $client;
        $this->region = $region;
        $this->zone = $zone;
        $this->endpoint_type = $endpoint_type;
        $this->token = $token;
        $this->catalog = $token->get_catalog();
    }

    function get_api_version_by_service_type($serviceType) {
        if (strcmp($serviceType, "compute") === 0) {
            return "v2";
        }
        return '';
    }
    
    function get_service_name($service) {
        $api_version = $this->get_api_version_by_service_type($service);
        if (strlen($api_version) > 0 && strcmp($api_version, DEFAULT_API_VERSION) !== 0) {
            $service = $service."_".$api_version;
        }
        return $service;
    }

    function get_service_url($service) {
        $urls = $this->get_service_urls($service);
        return $urls[rand(0, count($urls)-1)];
    }

    function get_service_urls($service) {
        $service = $this->get_service_name($service);
        print("get_service_urls for service $service endpoint $this->endpoint_type");
        if (strcmp($this->endpoint_type, "apigateway") === 0) {
            return $this->get_apigateway_service_urls($service, $this->region, $this->zone, $this->endpoint_type);
        } else {
            return $this->get_service_version_urls($service, $this->region, $this->zone, $this->endpoint_type);
        }
    }

    function get_service_version_urls($service, $region, $zone, $endpoint_type) {
        $auth_url = $this->client->get_auth_url();
        if (is_null($this->catalog)) {
            return array($auth_url);
        }
        $urls = $this->catalog->get_service_urls($service, $region, $zone, $endpoint_type);
        // HACK! in case of fail to get kestone url or schema of keystone changed, always trust authUrl
        if (strcmp($service, "identity") === 0 && (count($urls) == 0 || (strlen($auth_url) > 5 && strcmp(substr($auth_url, 0, 5), substr($urls[0], 0, 5)) !== 0))) {
            return array($auth_url);
        }
        return $urls;
    }

    function get_apigateway_service_urls($service, $region, $zone, $endpoint_type) {
        $urls = $this->get_service_version_urls($service, $region, $zone, "");
        // replace URLs with authUrl prefix
        // find the common prefix
        $prefix = $this->client->get_auth_url();
        $lastSlashPos = strrpos($prefix, "/api/s/identity");
        if ($lastSlashPos <= 0) {
            throw new Exception("Invalid auth_url: " . $prefix);
        }
        $prefix = join_path3(substr($prefix, 0, $lastSlashPos), "api/s", $service);
        if (strlen($region) > 0) {
            $prefix = join_path3($prefix, "r", $region);
            if (strlen($zone) > 0) {
                $prefix = join_path3($prefix, "z", $zone);
            }
        }
        $rets = array();
        for ($i = 0; $i < count($urls); $i++) {
            $url = $urls[$i];
            if (strlen($url) < 9) {
                continue;
            }
            $slashPos = strpos(substr($url, 9), "/");
            if ($slashPos > 0) {
                $url = substr($url, 9+$slashPos);
                $rets[$i] = join_path2($prefix, $url);
            } else {
                $rets[$i] = $prefix;
            }
        }
        return $rets;
    }

    function json_request($service, $method, $url, $headers, $body) {
        $base_url = $this->get_service_url($service);
        return $this->client->json_request($base_url, $this->token->get_token_string(), $method, $url, $headers, $body);
    }

    function raw_request($service, $method, $url, $headers, $body) {
        $base_url = $this->get_service_url($service);
        return $this->client->raw_request($base_url, $this->token->get_token_string(), $method, $url, $headers, $body);
    }
}


?>