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

function recusive_ksort($a) {
     if (is_array($a)) {
         if (isset($a[0])) {
             sort($a);
             for ($i = 0; $i < count($a); $i++) {
                 $a[$i] = recusive_ksort($a[$i]);
             }
         } else {
             ksort($a);
             foreach ($a as $k=>$v) {
                 $a[$k] = recusive_ksort($v);
             }
         }
    }
    return $a;
}

function jsonize($body) {
    return str_replace("\\/", "/", json_encode($body, JSON_UNESCAPED_UNICODE));
}

function monitor_signature($body) {
    if (array_key_exists("signature", $body)) {
        unset($body["signature"]);
    }
    $body = recusive_ksort($body);
    $jbody = jsonize($body);
    $sig = hash('sha256', $jbody);
    $body["signature"] = $sig;
    return $body;
}

/*
{
  "metric_query": [
    {
      "model": {
        "measurement": "vm_diskio",
        "select": [
          [
            {
              "type": "field",
              "params": [
                "read_bps"
              ]
            },
            {
              "type": "mean",
              "params": []
            },
            {
              "type": "alias",
              "params": [
                "磁盘读速率"
              ]
            }
          ]
        ],
        "tags": [
          {
            "key": "vm_id",
            "value": "ab9502de-c6b6-4150-880b-d0e3e6ba8ec8",
            "operator": "="
          }
        ],
        "group_by": [
          {
            "type": "tag",
            "params": [
              "vm_id"
            ]
          }
        ]
      }
    }
  ],
  "scope": "system",
  "from": "24h",
  "interval": "5m",
  "unit": false,
  "signature": "5ad57b46104d4a2daad323df4d87bcb22394d9b977acb4094089eb9d941c65c4"
}
*/
class MetricQuery {
    private $id_key;
    private $id_value;
    private $measurement;
    private $field;
    private $alias;
    private $aggr_func;

    public function __construct($measurement, $field, $alias, $idkey, $idvalue, $aggr_func='mean') {
        $this->id_key = $idkey;
        $this->id_value = $idvalue;
        $this->measurement = $measurement;
        $this->field = $field;
        $this->alias = $alias;
        $this->aggr_func = $aggr_func;
    }

    function query() {
        if (is_array($this->id_value)) {
            $op = "=~";
            $val = "/".implode("|", $this->id_value)."/";
        } else {
            $op = "=";
            $val = $this->id_value;
        }
        return array(
            "measurement"=>$this->measurement,
            "select"=>array(
                array(
                    array(
                        "type"=>"field",
                        "params"=>array($this->field),
                    ),
                    array(
                        "type"=>$this->aggr_func,
                        "params"=>array(),
                    ),
                    array(
                        "type"=>"alias",
                        "params"=>array($this->alias),
                    ),
                ),
            ),
            "tags"=>array(
                array(
                    "key"=>$this->id_key,
                    "value"=>$val,
                    "operator"=>$op,
                ),
            ),
            "group_by"=>array(
                array(
                    "type"=>"tag",
                    "params"=>array($this->id_key),
                ),
            ),
        );
    }
}

class Query {
    private $from;
    private $interval;
    private $scope;
    private $unit;
    private $metrics;

    public function __construct($from, $interval) {
        $this->from = $from;
        $this->interval = $interval;
        $this->scope = "system";
        $this->unit = false; 
        $this->metrics = array();      
    }

    function add_metric($measurement, $field, $alias, $idkey, $idvalue) {
        array_push($this->metrics, new MetricQuery($measurement, $field, $alias, $idkey, $idvalue));
        return $this;
    }

    function query() {
        $metrics = array();
        for($i=0;$i<count($this->metrics);$i++) {
            array_push($metrics, array("model"=>$this->metrics[$i]->query()));
        }
        $q = array(
            "metric_query"=>$metrics,
            "scope"=>$this->scope,
            "from"=>$this->from,
            "interval"=>$this->interval,
            "unit"=>$this->unit,
        );
        return monitor_signature($q);
    }
}

if (!debug_backtrace()) {
    $q = new Query("24h", "5m");
    $q->add_metric("vm_diskio", "read_bps", "磁盘读速率", "vm_id", "ab9502de-c6b6-4150-880b-d0e3e6ba8ec8");
    print(jsonize($q->query()));
    $q->add_metric("vm_diskio", "read_bps", "磁盘读速率", "vm_id", array("ab9502de-c6b6-4150-880b-d0e3e6ba8ec8", "ab9502de-c6b6-4150-880b-d0e3e6ba8ec9"));
    print(jsonize($q->query()));


}

?>
