<?php

require_once("Banner.php");

$parser = new HiscoreParser();
$clan = $parser->getClan("Chronic Demise");

$theme = 0;
if (isset($_GET["theme"]) && is_numeric($_GET["theme"])) {
    $theme = intval($_GET["theme"]);
}

// If our username is valid and belongs to a clan member
if (isset($_GET["user"]) && in_array(strtolower($_GET["user"]), $clan["Metadata"]["Whitelist"])) {
    try {
        $banner = new Banner($_GET["user"], $theme);
        $banner->render();
    } catch (Exception $e) {
        // Ehh, just do nothing
    }
}

?>