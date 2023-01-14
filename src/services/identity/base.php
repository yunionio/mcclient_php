<?php

include_once(__DIR__."/../../resources.php");

class BaseIdentityManager extends ResourceManager {
    public function __construct($keyword, $keyword_plural) {
        parent::__construct("identity", "v3", $keyword, $keyword_plural);
    }
}

?>