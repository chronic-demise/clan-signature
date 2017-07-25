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
    
    /** Named colors (RGB) to use for rendering. */
    const COLORS = [
        "white"    => 0xFFFFFF,
        "black"    => 0x000000,
        "red"      => 0x990000,
        "dark red" => 0xA91000,
        "green"    => 0x009900,
        "gold"     => 0x7E712A,
        "grey"     => 0x333333,
        "cyan"     => 0x0099BB,
    ];
    
    /** Dimensions of the output banner image. */
    const WIDTH = 728;
    const HEIGHT = 150;
    
    /** Themes that can be used to change the overall look & feel of the image. */
    const THEMES = [
        [
            "bg" => "bg_06.png",
            "avatar_bg" => "avatar_bg_02.png",
            "colors" => [ "on_bg" => 0xFFFFFF, "alt_on_bg" => 0xA91000, "stat_border" => 0x444444 ],
        ],
        [
            "bg" => "bg_02.png",
            "avatar_bg" => "avatar_bg_01.png",
            "colors" => [ "on_bg" => 0xFF7F00, "alt_on_bg" => 0x99ff00, "stat_border" => 0x000000 ],
        ],
        [
            "bg" => "bg_07.png",
            "avatar_bg" => "avatar_bg_03.png",
            "colors" => [ "on_bg" => 0x000000, "alt_on_bg" => 0x333333, "stat_border" => 0x000000 ],
        ],
        [
            "bg" => "bg_01.png",
            "avatar_bg" => "avatar_bg_02.png",
            "colors" => [ "on_bg" => 0x00FFFF, "alt_on_bg" => 0xFFFFFF, "stat_border" => 0x000000 ],
        ],
        [
            "bg" => "bg_03.png",
            "avatar_bg" => "avatar_bg_02.png",
            "colors" => [ "on_bg" => 0x99AADD, "alt_on_bg" => 0xFFFFFF, "stat_border" => 0x000000 ],
        ],
        [
            "bg" => "bg_04.png",
            "avatar_bg" => "avatar_bg_02.png",
            "colors" => [ "on_bg" => 0x002200, "alt_on_bg" => 0x00FF00, "stat_border" => 0x000000 ],
        ]
    ];
    
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
    
    /** The theme to use for rendering. */
    private $theme;
    
    /*****************************************************************************/
    /* Setup/Cleanup
    /*****************************************************************************/
    
    public function __construct($username, $theme) {
        $this->parser = new HiscoreParser();
        $this->user = $this->parser->getUser($username);
        if ($this->user["Clan"]) {
            $this->clan = $this->parser->getClan($this->user["Clan"]);
        }
        $this->theme = self::THEMES[min(max($theme, 0), count(self::THEMES) - 1)];
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
        $this->renderAvatar($img, 8, 4, 0.5);
        $this->renderName($img, 67, 27);
        $this->renderSkills($img, 8, 34, 3, 3);
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
        $theme = $this->theme;
        
        $banner = @imagecreatefrompng(self::RESOURCE_DIR . "backgrounds/" . $theme["bg"]);
        $img = @imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagecopy($img, $banner, 0, 0, 0, 0, self::WIDTH, self::HEIGHT);
        
        // Build our array of allocated colors
        $this->colors = array();
        foreach (self::COLORS as $color => $num) {
            $this->colors[$color] = $this->getColor($img, $num);
        }
        foreach ($theme["colors"] as $color => $num) {
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
        
        $this->drawText($img, self::WIDTH - $textWidth - 3, self::HEIGHT - 5, 12, $this->colors["on_bg"], $text, self::DEFAULT_FONT);
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
        
        $this->drawText($img, $x + ($width - $textWidth) / 2, $y, $fontSize, $this->colors["on_bg"], $this->clan["Name"], self::LEVEL_FONT);
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
    
    /**
     * Renders the user's avatar at the given location.
     */
    private function renderAvatar($img, $x, $y, $scale) {
        $bg = @imagecreatefrompng(self::RESOURCE_DIR . "backgrounds/" . $this->theme["avatar_bg"]);
        imagecopyresized($img, $bg, $x, $y, 0, 0, imagesx($bg) * $scale, imagesy($bg) * $scale, imagesx($bg), imagesy($bg));
        
        $avatar = @imagecreatefrompng($this->parser->getUserAvatar($this->user["Name"]));
        if ($avatar !== false) {
            imagecopyresized($img, $avatar, $x, $y, 0, 0, imagesx($avatar) * $scale, imagesy($avatar) * $scale, imagesx($avatar), imagesy($avatar));
            imagedestroy($avatar);
        }
        
        $frame = @imagecreatefrompng(self::RESOURCE_DIR . "avatar_frame.png");
        imagecopyresized($img, $frame, $x, $y, 0, 0, imagesx($frame) * $scale, imagesy($frame) * $scale, imagesx($frame), imagesy($frame));
        
        imagedestroy($bg);
        imagedestroy($frame);
    }
    
    /**
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
        if ($this->user["IsTitleSuffix"] || $this->user["Title"] === "") {
            // If suffix or no name
            $box = imagettfbbox(20, 0, $this->getFontPath(self::NAME_FONT), $this->user["Name"]);
            $textWidth = $box[4] - $box[6];
            
            $this->drawText($img, $x, $y, 20, $this->colors["on_bg"], $this->user["Name"], self::NAME_FONT);
            if (strlen($this->user["Title"]) > 0) {
                $this->drawText($img, $x + 8 + $textWidth, $y, 12, $this->colors["alt_on_bg"], $this->user["Title"], self::NAME_FONT);
            }
        } else {
            // If prefix
            $box = imagettfbbox(12, 0, $this->getFontPath(self::NAME_FONT), $this->user["Title"]);
            $textWidth = $box[4] - $box[6];
            
            $this->drawText($img, $x, $y, 12, $this->colors["alt_on_bg"], $this->user["Title"], self::NAME_FONT);
            $this->drawText($img, $x + 8 + $textWidth, $y, 20, $this->colors["on_bg"], $this->user["Name"], self::NAME_FONT);
        }
    }
    
    /**
     * Renders the 27 skills on the given image starting at the given (x, y) location, with the spacing specifed.
     */
    private function renderSkills($img, $x, $y, $xPadding, $yPadding) {
        $rows = [
            [60, "Overall", "XP"],
            ["Attack", "Defence", "Strength", "Constitution", "Ranged", "Prayer", "Magic"],
            ["Cooking", "Woodcutting", "Fletching", "Fishing", "Firemaking", "Crafting", "Smithing"],
            ["Mining", "Herblore", "Agility", "Thieving", "Slayer", "Farming", "Runecrafting"],
            ["Hunter", "Construction", "Summoning", "Dungeoneering", "Divination", "Invention"]
        ];
        $curY = $y;
        foreach ($rows as $i => $row) {
            $curX = $x;
            foreach ($row as $idx => $name) {
                if (is_numeric($name)) {
                    $curX += $name;
                    continue;
                }
                
                $width = 47;
                $height = 19;
                if ($name == "Overall") {
                    $width = 70;
                    $height = 24;
                } else if ($name == "XP") {
                    $width = 130;
                    $height = 24;
                }
                
                $this->renderSkill($img, $curX, $curY, $width, $height, $this->user["Skills"][$name]);
                
                $curX += $width + $xPadding;
            }
            $curY += $height + $yPadding;
        }
    }
    
    /**
     * Renders the skill on the given image at the given (x, y) location.
     */
    private function renderSkill($img, $x, $y, $w, $h, $skill) {
        $levelColor =  $this->colors[self::getOption($skill, "white",       "gold",   "cyan")];
        $borderColor = $this->colors[self::getOption($skill, "stat_border", "gold",   "cyan")];
        $col1 =        $this->colors[self::getOption($skill, "green",       "cyan",   "cyan")];
        $col2 =        $this->colors[self::getOption($skill, "red",         "red",    "cyan")];

        $fill = $this->getColor($img, 0x20000000);
        imagefilledrectangle($img, $x + $h - 1, $y, $x + $w - 1, $y + $h - 1, $fill);
        
        $this->drawSkillIcon($img, $x, $y, $h, $skill);
        $this->drawBorder($img, $x, $y, $w, $h, $borderColor);
        
        // Horizontally align level text
        $fontSize = 11;
        $text = strval(number_format($skill["Name"] == "XP" ? $skill["XP"] : $skill["Virtual"]));
        $box = imagettfbbox($fontSize, 0, self::RESOURCE_DIR . "fonts/" . self::LEVEL_FONT, $text);
        $textWidth = $box[4] - $box[6];
        
        $textX = $x + ($w - $textWidth) / 2.0 + $h / 2 - 1;
        $textY = $y + ($h - $fontSize) / 2.0 + $fontSize;
        $this->drawText($img, $textX, $textY, $fontSize, $levelColor, $text, self::LEVEL_FONT);
        
        $this->drawProgressBar($img, $x + 1, $y + $h - 2, $h - 3, 1, $skill["Progress"], $col1, $col2);
    }
    
    /**
     * Returns opt3 if skill is true maxed, opt2 if skill is maxed, opt1 otherwise.
     */
    private static function getOption($skill, $opt1, $opt2, $opt3) {
        // For Overall skill, used maxed status instead of level to determine colors.
        if ($skill["Name"] == "Overall")
            return $skill["Maxed"] ? $opt2 : $opt1;
        
        // Always use lowest option for total XP
        if ($skill["Name"] == "XP") {
            return $opt1;
        }
        
        // All other (normal) skills - use the level
        if ($skill["Virtual"] < 99)
            return $opt1;
        if ($skill["Virtual"] < 120)
            return $opt2;
        return $opt3;
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
        $icon = @imagecreatefrompng(self::RESOURCE_DIR . "/skill_icons/" . strtolower($skill["Name"]) . ".png");
        $icon = imagescale($icon, $size, $size, IMG_BICUBIC);
        
        $bg = @imagecreatefrompng(self::RESOURCE_DIR . self::getOption($skill, "normal", "maxed", "true") . "_bg.png");
        $bg = imagescale($bg, $size, $size, IMG_BICUBIC);
        imagecopy($bg, $icon, 0, 0, 0, 0, $size, $size);
        imagecopy($img, $bg, $x, $y, 1, 1, $size - 1, $size - 1);
        
        imagedestroy($icon);
        imagedestroy($bg);
    }
    
    /**
     * Renders a progress bar for the ratio & colors at the given (x, y) location.
     */
    private function drawProgressBar($img, $x, $y, $w, $h, $ratio, $col1, $col2) {
        $offset = -1;
        if ($w * $ratio < 1) {
            $offset = 0;
        }
        imagefilledrectangle($img, $x, $y, $x + $w - 1, $y + $h - 1, $col2);
        imagefilledrectangle($img, $x, $y, $x + ($w * $ratio) + $offset, $y + $h - 1, $col1);
    }
}

?>