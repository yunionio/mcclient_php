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

function split_versioned_url($url) {
    $url = rtrim($url, "/");
    $parts = explode("/", $url);
    if (preg_match('/v\d+/', $parts[count($parts)-1])) {
        $url = implode("/", array_slice($parts, 0, count($parts)-1));
        $ver = $parts[count($parts)-1];
        return [$url, $ver];
    }else {
        return [$url, ""];
    }
}

class Client {
	private $auth_url;
	private $timeout;
	private $debug;
	private $insecure;
	private $catalog;

	function __construct($authUrl, $timeout=300, $debug=false, $insecure=true) {
		$this->auth_url = rtrim($authUrl, "/");
 		$this->timeout = $timeout;
		$this->debug = $debug;
		$this->insecure = $insecure;
	}

	function get_auth_url() {
		return $this->auth_url;
	}

	function auth_version() {
		$pos = strrpos($this->auth_url, '/');
		if ($pos > 0) {
			return substr($this->auth_url, $pos+1);
		} else {
			return "";
		}
	}

	function join_url($baseUrl, $path) {
		$url = split_versioned_url($baseUrl);
		if (strlen($url[1]) > 0 && strpos($path, "/".$url[1]."/") === 0) {
			$baseUrl = $url[0];
		}
		return $baseUrl.$path;
	}

	function get_default_header($header, $token) {
		if (is_null($header)) {
			$header = array();
		}
		if (!empty($token)) {
			array_push($header, "X-Auth-Token: ".$token);
		}
		return $header;
	}

	function raw_request($endpoint, $token, $method, $url, $header, $body) {
		$ch = curl_init();

		$requrl = $this->join_url($endpoint, $url);
		if ($this->debug) {
			printf("%s %s\n", $method, $requrl);			
		}
		if ($this->debug) {
			curl_setopt($ch, CURLOPT_VERBOSE, 1);
		}
		if ($this->timeout > 0) {
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout); //timeout in seconds
		}
		if ($this->insecure) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		}
		$headers = array();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $requrl);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->get_default_header($header, $token));
		curl_setopt($ch, CURLOPT_HEADERFUNCTION,
    		function ($curl, $header) use (&$headers) {
        		$len = strlen($header);
        		$header = explode(':', $header, 2);
        		if (count($header) < 2) { // ignore invalid headers
        	    	return $len;
				}
		        $headers[strtolower(trim($header[0]))][] = trim($header[1]);
        		return $len;
    		}
		);

		$read_only = false;
		if (strcmp($method, "GET") === 0 || strcmp($method, "HEAD") === 0) {
			// do nothing
			$read_only = true;
		} else if (strcmp($method, "POST") === 0) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);  
			curl_setopt($ch, CURLOPT_POST, true);
		} else if (strcmp($method, "PUT") === 0 || strcmp($method, "PATCH") === 0 || strcmp($method, "DELETE") === 0) {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		}
		
		if (!$read_only) {
			if ($body !== null) {
				// 检测 $body 是否为文件句柄
				if (is_resource($body) && get_resource_type($body) === 'stream') {
					// 使用文件流上传，避免内存溢出
					curl_setopt($ch, CURLOPT_INFILE, $body);
					curl_setopt($ch, CURLOPT_INFILESIZE, filesize(stream_get_meta_data($body)['uri']));
					curl_setopt($ch, CURLOPT_UPLOAD, true);
				} else {
					// 使用字符串上传
					curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
				}
			} else {
				array_push($header, "Content-Length: 0");
			}
		}

		$result = curl_exec($ch);
		$err = curl_error($ch);
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		if ($err) {
			throw new Exception("Error: " + $err);
		} else {
			if ($statusCode >= 400) {
				throw new Exception("HTTP $statusCode: $result", $statusCode);
			}
			return [$headers, $result];
		}
	}
	
	function json_request($endpoint, $token, $method, $url, $header, $body_json) {
		if (is_null($header)) {
			$header = array();
		}
		array_push($header, "Content-Type: application/json;charset=utf-8");
		$result = $this->raw_request($endpoint, $token, $method, $url, $header, json_encode($body_json));
		return [$result[0], json_decode($result[1], true)];
	}

	function auth($domain_name, $uname, $passwd, $project_id, $project_name, $project_domain, $token="") {
		$auth = array();
		if (strlen($uname) > 0 && strlen($passwd) > 0) { // Password authentication
			$user = array(
				'name' => $uname,
				'password' => $passwd,
			);
			if (strlen($domain_name) > 0) {
				$user["domain"] = array(
					"name" => $domain_name,
				);
			} else {
				$user["domain"] = array(
					"id" => "default",
				);
			}
			$auth["identity"] = array(
				"methods"=>array("password"),
				"password"=>array(
					"user"=>$user,
				),
			);
		} else if (strlen($token) > 0) {
			$auth["identity"] = array(
				"methods"=>array("token"),
				"token"=>array(
					"id"=>$token,
				),
			);
		}
		$project = array();
		if (strlen($project_id) > 0) {
			$project["id"] = $project_id;
		}
		if (strlen($project_name) > 0) {
			$project["name"] = $project_name;
			if (strlen($project_domain) > 0) {
				$project["domain"] = array(
					"name"=>$project_domain,
				);
			} else {
				$project["domain"] = array(
					"id"=>"default",
				);
			}
		}
		if (count($project) > 0) {
			$auth["scope"] = array(
				"project" => $project,
			);
		}
		$input = array("auth"=>$auth);
		return $this->auth_v3_input($input);
	}
	
	function auth_v3_input($input) {
		$result = $this->json_request($this->auth_url, "", "POST", "/auth/tokens", null, $input);
		$token = $result[0]["x-subject-token"][0];
		return new TokenV3($token, $result[1]["token"]);
	}

	function get_session($token, $endpointType, $region="", $zone="") {
		$cata = $token->get_catalog();
		if (is_null($this->catalog) && !$cata->is_empty()) {
			$this->catalog = $cata;
		}
		return new Session($this, $region, $zone, $endpointType, $token);
	}
}

?>
