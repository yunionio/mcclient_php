<?php

include_once(__DIR__."/../../resources.php");

class ComputeManager extends ResourceManager {
    public function __construct($keyword, $keyword_plural) {
        parent::__construct("compute", "", $keyword, $keyword_plural);        
    }
}

?>