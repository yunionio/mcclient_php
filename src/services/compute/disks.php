<?php

include_once("base.php");

class DiskManager extends ComputeManager {
    public function __construct() {
        parent::__construct("disk", "disks");
    }
}

?>