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
    const NAME_FONT    = "Averia-Regular.ttf";
    const LEVEL_FONT   = "GFSArtemisia.otf";
    
    /** Named colors (aRGB) to use for rendering. */
    const COLORS = array(
        "white"    => 0xFFFFFF,
        "black"    => 0x000000,
        "red"      => 0x990000,
        "dark red" => 0xA91000,
        "green"    => 0x009900,
        "gold"     => 0x7E712A,
        "grey"     => 0x333333,
    );
    
    /** Dimensions of the output banner image. */
    const WIDTH = 728;
    const HEIGHT = 150;
    
    /*****************************************************************************/
    /* Variables
    /*****************************************************************************/
    
    /** The HiscoreParser object used by the banner. */
    private $parser;
    
    /** Associative array of allocated GD colors, with the same indices as COLORS. */
    private $colors;
    
    /** The user data returned by the parser. */
    private $user;
    
    /** The user's clan data returned by the parser. */
    private $clan;
    
    /*****************************************************************************/
    /* Setup/Cleanup
    /*****************************************************************************/
    
    public function __construct($username) {
        $this->parser = new HiscoreParser();
        $this->user = $this->parser->getUser($username);
        if ($this->user["Clan"]) {
            $this->clan = $this->parser->getClan($this->user["Clan"]);
        }
    }
    
    /*****************************************************************************/
    /* Public Functions
    /*****************************************************************************/
    
    /**
     * Render the banner as a PNG image.
     */
    public function render() {
        header("Content-Type: image/png");
        
        $img = $this->setupBanner();
        
        if ($this->clan) {
            $this->renderClan($img, 544, 24, 180);
        }
        $this->renderName($img, 5, 28);
        $this->renderSkills($img, 8, 37, 50, 24);
        $this->drawUpdated($img);
        
        imagepng($img);
        imagedestroy($img);
    }
    
    /*****************************************************************************/
    /* Private Functions
    /*****************************************************************************/
    
    /**
     * Sets up the base image from the background, and allocates our colors.
     */
    private function setupBanner() {
        $banner = @imagecreatefrompng(self::RESOURCE_DIR . "banner.png");
        $img = @imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagecopy($img, $banner, 0, 0, 0, 0, self::WIDTH, self::HEIGHT);
        
        // Build our array of allocated colors
        $this->colors = array();
        foreach (self::COLORS as $color => $num) {
            $this->colors[$color] = $this->getColor($img, $num);
        }
        return $img;
    }
    
    /**
     * Returns the path to the specified font.
     */
    private function getFontPath($font) {
        return self::RESOURCE_DIR . "fonts/" . $font;
    }
    
    /**
     * Draws the updated text onto the image.
     */
    private function drawUpdated($img) {
        $text = gmdate("m/j/Y H:i:s", $this->user["Metadata"]["LastUpdated"]);
        $box = imagettfbbox(12, 0, $this->getFontPath(self::DEFAULT_FONT), $text);
        $textWidth = $box[4] - $box[6];
        
        $this->drawText($img, 7, self::HEIGHT - 5, 12, $this->colors["white"], $text, self::DEFAULT_FONT);
    }
    
    /**
     * Renders the clan section at the given location and width.
     */
    private function renderClan($img, $x, $y, $width) {
        $fontSize = 19;
        $textWidth = $width + 1;
        while ($textWidth > $width) {
            $fontSize -= 1;
            $box = imagettfbbox($fontSize, 0, $this->getFontPath(self::LEVEL_FONT), $this->clan["Name"]);
            $textWidth = $box[4] - $box[6];
        }
        
        $this->drawText($img, $x + ($width - $textWidth) / 2, $y, $fontSize, $this->colors["white"], $this->clan["Name"], self::LEVEL_FONT);
        $this->renderMotif($img, $x, $y + 6, $width);
    }
    
    /**
     * Renders the clan motif at the given location and scale, returning the width.
     */
    private function renderMotif($img, $x, $y, $width) {
        if ($this->clan) {
            $barScale = 0.3;
            $bar = @imagecreatefrompng(self::RESOURCE_DIR . "motif_bar.png");
            $barW = imagesx($bar);
            $barH = imagesy($bar);
            
            $scale = $width / ($barW);
            
            $resizedBarW = $width;
            $resizedBarH = $barH * $scale;
            imagecopyresized($img, $bar, $x, $y, 0, 0, $resizedBarW - 1, $resizedBarH - 1, $barW - 1, $barH - 1);
            
            $motif = @imagecreatefrompng($this->parser->getClanMotif($this->user["Clan"]));
            $motifW = imagesx($motif);
            $motifH = imagesy($motif);
            
            //$scale = 0.85 * $scale;
            
            $resizedMotifW = 0.7 * $width;
            $resizedMotifH = 0.7 * $motifH * ($width / $motifW);
            $xOffset = ($resizedBarW - $resizedMotifW) / 2;
            $yOffset = $resizedBarH * 0.7;
            imagecopyresized($img, $motif, $x + $xOffset, $y + $yOffset, 0, 0, $resizedMotifW - 1, $resizedMotifH - 1, $motifW - 1, $motifH - 1);
            
            imagedestroy($bar);
            imagedestroy($motif);
            
            return $resizedBarW;
        }
        return false;
    }
    
    /*
     * Draws a border of the given color onto the image at the given location & size.
     */
    private function drawBorder($img, $x, $y, $w, $h, $color) {
        imagerectangle($img, $x, $y, $x + $w - 1, $y + $h - 1, $color);
    }
    
    /**
     * Returns the allocated GD color for the given image and integer color.
     */
    private function getColor($img, $color) {
        $a = ($color >> 24) & 0x7F;
        $r = ($color >> 16) & 0xFF;
        $g = ($color >>  8) & 0xFF;
        $b = ($color >>  0) & 0xFF;
        
        return imagecolorallocatealpha($img, $r, $g, $b, $a);
    }
    
    /**
     * Renders the player's name on the given image at the given coordinates.
     */
    private function renderName($img, $x, $y) {
        if ($this->user["IsTitleSuffix"] || !isset($this->user["Title"])) {
            // If suffix or no name
            $box = imagettfbbox(20, 0, $this->getFontPath(self::NAME_FONT), $this->user["Name"]);
            $textWidth = $box[4] - $box[6];
            
            $this->drawText($img, $x, $y, 20, $this->colors["white"], $this->user["Name"], self::NAME_FONT);
            if (isset($this->user["Title"])) {
                $this->drawText($img, $x + 8 + $textWidth, $y, 12, $this->colors["dark red"], $this->user["Title"], self::NAME_FONT);
            }
        } else {
            // If prefix
            $box = imagettfbbox(12, 0, $this->getFontPath(self::NAME_FONT), $this->user["Title"]);
            $textWidth = $box[4] - $box[6];
            
            $this->drawText($img, $x, $y, 12, $this->colors["dark red"], $this->user["Title"], self::NAME_FONT);
            $this->drawText($img, $x + 8 + $textWidth, $y, 20, $this->colors["white"], $this->user["Name"], self::NAME_FONT);
        }
    }
    
    /**
     * Renders the 27 skills on the given image starting at the given (x, y) location, with the spacing specifed.
     */
    private function renderSkills($img, $x, $y, $xSpacing, $ySpacing) {
        $rows = [
            ["Attack", "Defence", "Strength", "Constitution", "Ranged", "Prayer", "Magic"],
            ["Cooking", "Woodcutting", "Fletching", "Fishing", "Firemaking", "Crafting", "Smithing"],
            ["Mining", "Herblore", "Agility", "Thieving", "Slayer", "Farming", "Runecrafting"],
            ["Hunter", "Construction", "Summoning", "Dungeoneering", "Divination", "Invention"]
        ];
        foreach ($rows as $i => $row) {
            foreach($row as $idx => $skill) {
                $this->renderSkill($img, $x + $idx * $xSpacing, $y, $this->user["Skills"][$skill]);
            }
            $y += $ySpacing;
        }
    }
    
    /**
     * Renders the skill on the given image at the given (x, y) location.
     */
    private function renderSkill($img, $x, $y, $skill) {
        $levelColor = $this->colors[$skill["Maxed"] ? "gold" : "white"];
        
        $fill = $this->getColor($img, 0x20000000);
        imagefilledrectangle($img, $x + 19, $y, $x + 47 - 1, $y + 20 - 1, $fill);
        
        $this->drawSkillIcon($img, $x, $y, 20, $skill);
        $this->drawBorder($img, $x, $y, 47, 20, $this->colors[$skill["Maxed"] ? "gold" : "grey"]);
        
        // Horizontally align level text
        $text = strval($skill["Level"]);
        $box = imagettfbbox(11, 0, self::RESOURCE_DIR . "fonts/" . self::LEVEL_FONT, $text);
        $textWidth = $box[4] - $box[6];
        
        $this->drawText($img, ($x + 32) - ($textWidth / 2), $y + 15, 11, $levelColor, $text, self::LEVEL_FONT);
        $this->drawProgressBar($img, $x + 1, $y + 18, 17, 1, $skill);
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
        
        $icon = @imagecreatefrompng("../resources/skill_icons/" . strtolower($skill["Name"]) . ".png");
        $icon = imagescale($icon, $size, $size, IMG_BICUBIC);
        
        $bg = @imagecreatefrompng("../resources/" . ($skill["Maxed"] ? "maxed" : "normal") . "_bg.png");
        $bg = imagescale($bg, $size, $size, IMG_BICUBIC);
        imagecopy($bg, $icon, 0, 0, 0, 0, $size, $size);
        imagecopy($img, $bg, $x, $y, 1, 1, $size - 1, $size - 1);
        
        imagedestroy($icon);
        imagedestroy($bg);
    }
    
    /**
     * Renders a progress bar for the skill at the given (x, y) location.
     */
    private function drawProgressBar($img, $x, $y, $w, $h, $skill) {
        $ratio = $skill["Progress"];
        
        imagefilledrectangle($img, $x, $y, $x + $w - 1,            $y + $h - 1, $this->colors[$skill["Maxed"] ? "gold" : "red"]);
        imagefilledrectangle($img, $x, $y, $x + ($w * $ratio) - 1, $y + $h - 1, $this->colors[$skill["Maxed"] ? "gold" : "green"]);
    }
}

?>
