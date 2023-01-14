<?php

include_once("base.php");

class HostManager extends ComputeManager {
    public function __construct() {
        parent::__construct("host", "hosts");
    }
}

?>