<?php

include_once("base.php");

class NetworkManager extends ComputeManager {
    public function __construct() {
        parent::__construct("network", "networks");
    }
}

?>