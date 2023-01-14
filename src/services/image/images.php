<?php

include_once("base.php");

class ImageManager extends BaseImageManager {
    public function __construct() {
        parent::__construct("image", "images");
    }

    function list_items($session, $params) {
        if (array_key_exists("details", $params) && $params["details"]) {
            $path = "/".$this->context_path(array())."/detail";
            unset($params["details"]);
            if (count($params) > 0) {
                $qs = http_build_query($params);
                $path = $path."?".$qs;
            }
            return $this->_list($session, $path, $this->get_keyword_plural());
        }
        return parent::list_items($session, $params);
    }
}

?>