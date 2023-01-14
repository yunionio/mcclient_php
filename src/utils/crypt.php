<?php

function to_aes_key($key) {
    if (strlen($key) > 32) {
        return substr($key, 0, 32);
    } else {
        while (strlen($key) < 32) {
            $key = $key."$";
        }
        return $key;
    }
}

function decrypt_aes_cfb8($key, $secret) {
    $iv = substr($secret, 0, 16);
    $ciphertext = substr($secret, 16);
    return openssl_decrypt($ciphertext, 'aes-256-cfb8', $key, OPENSSL_RAW_DATA, $iv);
}

function descrypt_aes_base64($key, $secret) {
    $s = base64_decode($secret);
    $k = to_aes_key($key);
    return decrypt_aes_cfb8($k, $s);
}

?>