<?php

header("Content-Type: image/png");

require_once("HiscoreParser.php");

$parser = new HiscoreParser();
$userData = $parser->getUser("berserkguard");
$skills = $userData["Skills"];

$img = @imagecreate(728, 150) or die("Cannot Initialize new GD image stream");
$bgColor = imagecolorallocate($img, 0xFF, 0xCC, 0xDD);
$textColor = imagecolorallocate($img, 133, 14, 91);

imagestring($img, 5, 250, 65, "Testing Chronic Demise custom sig - coming soon!", $textColor);
imagestring($img, 5, 10, 10,  "Username: Berserkguard", $textColor);
imagestring($img, 5, 10, 30,  "Overall Level: " . strval($skills["Overall"]["Level"]), $textColor);
imagestring($img, 5, 10, 50,  "Overall XP: " . strval($skills["Overall"]["XP"]), $textColor);
imagestring($img, 5, 10, 90,  "Slayer Level: " . strval($skills["Slayer"]["Level"]), $textColor);
imagestring($img, 5, 10, 110, "Slayer XP: " . strval($skills["Slayer"]["XP"]), $textColor);
imagestring($img, 5, 250, 85, "Last updated: " . gmdate("Y/m/j H:i:s", $userData["Metadata"]["LastUpdated"]), $textColor);

imagepng($img);

imagedestroy($img);

?>
