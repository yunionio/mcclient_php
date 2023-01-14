<?php

include_once("base.php");
include_once(__DIR__."../../utils/crypt.php");

class ServerManager extends ComputeManager {
    public function __construct() {
        parent::__construct("server", "servers");
    }

    function get_login_info($s, $id, $private_key=null) {
        $ret = array();
        $data = $this->get($s, $id, array());
        $login_account = $data["metadata"]["login_account"];
        $login_key_timestamp = $data["metadata"]["login_key_timestamp"];
        $ret["username"] = $login_account;
        $ret["updated"] = $login_key_timestamp;
        $login_key = $data["metadata"]["login_key"];
        if (strlen($login_key) > 0) {
            $ret["login_key"] = $login_key;
            $keypair_id = $data["keypair_id"];
            if (strlen($keypair_id) > 0 && strcmp($keypair_id, "none") !== 0) {
                // 秘钥
                $ret["keypair"] = $data["keypair"];
                if (is_null($private_key)) {
                    die("require private key to decrypt password");
                }
            } else {
                // 密码
                $passwd = descrypt_aes_base64($data["id"], $login_key);
                $ret["password"] = $passwd;
            }
        }
        return $ret;
    }
}

?>