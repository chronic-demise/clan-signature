<?php

require_once("Banner.php");

// Our whitelist, hardcoded for now
$whitelist = array("berserkguard", "enteater1", "timesplitta8", "devilchief");

// If our username is valid
if (isset($_GET["user"]) && in_array(strtolower($_GET["user"]), $whitelist)) {
    try {
        $banner = new Banner($_GET["user"]);
        $banner->render();
    } catch (Exception $e) {
        // Ehh, just do nothing
    }
}

?>
