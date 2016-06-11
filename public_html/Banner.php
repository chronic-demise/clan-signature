<?php

require_once("HiscoreParser.php");

/**
 * A customizable banner rendering class.
 * @author berserkguard
 */
class Banner {
    
    /*****************************************************************************/
    /* Constants
    /*****************************************************************************/
    
    /** Directory to store refetch timestamps in. */
    const RESOURCE_DIR = "../resources/";
    
    /** Fonts to use for text rendering. */
    const DEFAULT_FONT = "GFSArtemisia.otf";
    const NAME_FONT    = "MedievalSharp.ttf";
    const LEVEL_FONT   = "GFSArtemisia.otf";
    
    /** Named colors to use for rendering. */
    const COLORS = array(
        "white" => 0xFFFFFF,
        "red"   => 0x990000,
        "green" => 0x009900,
        "gold"  => 0x7E712A,
    );
    
    /** Dimensions of the output banner image. */
    const WIDTH = 728;
    const HEIGHT = 150;
    
    /*****************************************************************************/
    /* Variables
    /*****************************************************************************/
    
    /** Associative array of allocated GD colors, with the same indices as COLORS. */
    private $colors;
    
    /** The user data returned by the parser. */
    private $user;
    
    /*****************************************************************************/
    /* Setup/Cleanup
    /*****************************************************************************/
    
    public function __construct($username) {
        $parser = new HiscoreParser();
        $this->user = $parser->getUser($username);
    }
    
    /*****************************************************************************/
    /* Public Functions
    /*****************************************************************************/
    
    /**
     * Render the banner as a PNG image.
     */
    public function render() {
        header("Content-Type: image/png");
        
        // Create the image from our banner
        $banner = @imagecreatefrompng(self::RESOURCE_DIR . "banner.png");
        $img = @imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagecopy($img, $banner, 0, 0, 0, 0, self::WIDTH, self::HEIGHT);
        
        // Build our array of allocated colors
        $this->colors = array();
        foreach (self::COLORS as $color => $num) {
            $this->colors[$color] = $this->getColor($img, $num);
        }
        
        // Render the username
        $this->drawText($img, 5, 35, 28, $this->colors["white"], $this->user["Name"], self::NAME_FONT);
        
        // Render the skills & levels
        $rows = [
            ["Attack", "Defence", "Strength", "Constitution", "Ranged", "Prayer", "Magic", "Cooking", "Woodcutting"],
            ["Fletching", "Fishing", "Firemaking", "Crafting", "Smithing", "Mining", "Herblore", "Agility", "Thieving"],
            ["Slayer", "Farming", "Runecrafting", "Hunter", "Construction", "Summoning", "Dungeoneering", "Divination", "Invention"]
        ];
        $startX = 8;
        $startY = 47;
        foreach ($rows as $i => $row) {
            foreach($row as $idx => $skill) {
                $this->renderSkill($img, $startX + $idx * 54, $startY, $this->user["Skills"][$skill]);
            }
            $startY += 32;
        }
        
        // Render when the data was last updated
        imagestring($img, 5, 250, 5, "Testing Chronic Demise custom sig - coming soon!", $this->colors["white"]);
        imagestring($img, 5, 250, 25, "Last updated: " . gmdate("Y/m/j H:i:s", $this->user["Metadata"]["LastUpdated"]), $this->colors["white"]);
        
        imagepng($img);
        
        imagedestroy($banner);
        imagedestroy($img);
    }
    
    /*****************************************************************************/
    /* Private Functions
    /*****************************************************************************/
    
    /**
     * Returns the allocated GD color for the given image and integer color.
     */
    private function getColor($img, $color) {
        $r = ($color >> 16) & 0xFF;
        $g = ($color >>  8) & 0xFF;
        $b = ($color >>  0) & 0xFF;
        
        return imagecolorallocate($img, $r, $g, $b);
    }
    
    /**
     * Renders the skill on the given image at the given (x, y) location.
     */
    private function renderSkill($img, $x, $y, $skill) {
        $levelColor = $this->colors[$skill["Maxed"] ? "gold" : "white"];
        
        $this->drawSkillIcon($img, $x, $y, 26, $skill);
        $this->drawText($img, $x + 26, $y + 19, 14, $levelColor, strval($skill["Level"]), self::LEVEL_FONT);
        $this->drawProgressBar($img, $x, $y + 23, 23, 1, $skill);
    }
    
    /**
     * Renders text at the given (x, y) with the given size, color, and font.
     */
    private function drawText($img, $x, $y, $size, $color, $text, $font = self::DEFAULT_FONT) {
        imagettftext($img, $size, 0, $x, $y, $color, self::RESOURCE_DIR . "fonts/" . $font, $text);
    }
    
    /**
     * Renders the skill icon at the given (x, y) and size.
     */
    private function drawSkillIcon($img, $x, $y, $size, $skill) {
        $bg = @imagecreatefrompng("../resources/maxed_bg.png");
        
        $icon = @imagecreatefrompng("../resources/skill_icons/" . strtolower($skill["Name"]) . ".png");
        $icon = imagescale($icon, $size, $size, IMG_BICUBIC);
        
        if ($skill["Maxed"]) {
            $bg = imagescale($bg, $size, $size, IMG_BICUBIC);
            imagecopy($bg, $icon, 0, 0, 0, 0, $size, $size);
            imagecopy($img, $bg, $x, $y, 1, 1, $size - 1, $size - 1);
        } else {
            imagecopy($img, $icon, $x, $y, 1, 1, $size - 1, $size - 1);
        }
        
        imagedestroy($icon);
        imagedestroy($bg);
    }
    
    /**
     * Renders a progress bar for the skill at the given (x, y) location.
     */
    private function drawProgressBar($img, $x, $y, $w, $h, $skill) {
        $ratio = $skill["Progress"];
        
        imagefilledrectangle($img, $x, $y, $x + $w,            $y + $h, $this->colors[$skill["Maxed"] ? "gold" : "red"]);
        imagefilledrectangle($img, $x, $y, $x + ($w * $ratio), $y + $h, $this->colors[$skill["Maxed"] ? "gold" : "green"]);
    }
}

?>
