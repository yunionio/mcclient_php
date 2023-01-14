<?php

include_once(__DIR__."/../../resources.php");

class BaseImageManager extends ResourceManager {
    public function __construct($keyword, $keyword_plural) {
        parent::__construct("image", "v1", $keyword, $keyword_plural);
    }
}

?>