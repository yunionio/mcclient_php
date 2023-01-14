<?php

include_once("base.php");

class UserManager extends BaseIdentityManager {
    public function __construct() {
        parent::__construct("user", "users");
    }
}

?>