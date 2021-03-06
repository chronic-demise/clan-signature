<?php

/**
 * A simple class for retrieving & caching information retrieved from the RuneScape hiscores API.
 * @author berserkguard
 */
class HiscoreParser {
    
    /*****************************************************************************/
    /* Constants
    /*****************************************************************************/
    
    /** Directory to store refetch timestamps in. */
    const REFETCH_DIR = "./refetch/";
    
    /** Directory to store fetched API data in. */
    const DATA_DIR =  "./data/";
    
    /** Base URL for accessing the RuneScape user API. */
    const BASE_USER_URL = "http://services.runescape.com/m=hiscore/index_lite.ws?player=";
    
    /** URL for fetching the user's clan & title. */
    const USER_WEB_DATA_URL = "http://services.runescape.com/m=website-data/playerDetails.ws?names=[%22{user}%22]&callback=jQuery000000000000000_0000000000";
    
    /** URL for a user's chat avatar. */
    const USER_AVATAR_URL = "http://services.runescape.com/m=avatar-rs/{user}/chat.png";
    
    /** Base URL for accessing the RuneScape clans API. */
    const BASE_CLAN_URL = "http://services.runescape.com/m=clan-hiscores/members_lite.ws?clanName=";
    
    /** URL for RuneScape's clan motif image. */
    const CLAN_MOTIF_URL = "http://services.runescape.com/m=avatar-rs/a=135/{clan}/clanmotif.png";
    
    /** Minimum number of seconds to wait before refetching latest data. */
    const MIN_REFRESH = 24 * 60 * 60; // 24 hours
    
    /** Enumeration of RuneScape skills, in row-order as returned by the RuneScape API. */
    const SKILLS = [
        "Overall",
        "Attack", "Defence", "Strength", "Constitution", "Ranged", "Prayer", "Magic", "Cooking", "Woodcutting",
        "Fletching", "Fishing", "Firemaking", "Crafting", "Smithing", "Mining", "Herblore", "Agility", "Thieving",
        "Slayer", "Farming", "Runecrafting", "Hunter", "Construction", "Summoning", "Dungeoneering", "Divination", "Invention", "Archaeology"
    ];
    
    /** Number of skills in RuneScape. Note that this number < count(self::SKILLS) */
    const NUM_SKILLS = 28;

    /** Max XP for any skill */
    const MAX_SKILL_XP = 200000000;
    
    /** Max XP across all skills */
    const MAX_XP = self::NUM_SKILLS * self::MAX_SKILL_XP;
    
    /** Total skill experience table, from levels 0 - 120. */
    const XP_TABLE = [
        0,
        0,        83,       174,      276,      388,      512,      650,      801,      969,      1154,         // Levels 1 - 10
        1358,     1584,     1833,     2107,     2411,     2746,     3115,     3523,     3973,     4470,         // Levels 11 - 20
        5018,     5624,     6291,     7028,     7842,     8740,     9730,     10824,    12031,    13363,        // Levels 21 - 30
        14833,    16456,    18247,    20224,    22406,    24815,    27473,    30408,    33648,    37224,        // Levels 31 - 40
        41171,    45529,    50339,    55649,    61512,    67983,    75127,    83014,    91721,    101333,       // Levels 41 - 50
        111945,   123660,   136594,   150872,   166636,   184040,   203254,   224466,   247886,   273742,       // Levels 51 - 60
        302288,   333804,   368599,   407015,   449428,   496254,   547953,   605032,   668051,   737627,       // Levels 61 - 70
        814445,   899257,   992895,   1096278,  1210421,  1336443,  1475581,  1629200,  1798808,  1986068,      // Levels 71 - 80
        2192818,  2421087,  2673114,  2951373,  3258594,  3597792,  3972294,  4385776,  4842295,  5346332,      // Levels 81 - 90
        5902831,  6517253,  7195629,  7944614,  8771558,  9684577,  10692629, 11805606, 13034431, 14391160,     // Levels 91 - 100
        15889109, 17542976, 19368992, 21385073, 23611006, 26068632, 28782069, 31777943, 35085654, 38737661,     // Levels 101 - 110
        42769801, 47221641, 52136869, 57563718, 63555443, 70170840, 77474828, 85539082, 94442737, 104273167     // Levels 111 - 120
    ];
    
    /** Total elite skill experience table, from levels 0 - 120. */
    const ELITE_XP_TABLE = [
        0,
        0,         830,       1861,      2902,      3980,      5126,      6390,      7787,      9400,      11275,     // Levels 1 - 10
        13605,     16372,     19656,     23546,     28138,     33520,     39809,     47109,     55535,     64802,     // Levels 11 - 20
        77190,     90811,     106221,    123573,    143025,    164742,    188893,    215651,    245196,    277713,    // Levels 21 - 30
        316311,    358547,    404634,    454796,    509259,    568254,    632019,    700797,    774834,    854383,    // Levels 31 - 40
        946227,    1044569,   1149696,   1261903,   1381488,   1508756,   1644015,   1787581,   1939773,   2100917,   // Levels 41 - 50
        2283490,   2476369,   2679907,   2894505,   3120508,   3358307,   3608290,   3870846,   4146374,   4435275,   // Levels 51 - 60
        4758122,   5096111,   5449685,   5819299,   6205407,   6608473,   7028964,   7467354,   7924122,   8399751,   // Levels 61 - 70
        8925664,   9472665,   10041285,  10632061,  11245538,  11882262,  12542789,  13227679,  13937496,  14672812,  // Levels 71 - 80
        15478994,  16313404,  17176661,  18069395,  18992239,  19945833,  20930821,  21947856,  22997593,  24080695,  // Levels 81 - 90
        25259906,  26475754,  27728955,  29020233,  30350318,  31719944,  33129852,  34580790,  36073511,  37608773,  // Levels 91 - 100
        39270442,  40978509,  42733789,  44537107,  46389292,  48291180,  50243611,  52247435,  54303504,  56412678,  // Levels 101 - 110
        58575823,  60793812,  63067521,  65397835,  67785643,  70231841,  72737330,  75303019,  77929820,  80618654,  // Levels 111 - 120
        83370445,  86186124,  89066630,  92012904,  95025896,  98106559,  101255855, 104474750, 107764216, 111125230, // Levels 121 - 130
        114558777, 118065845, 121647430, 125304532, 129038159, 132849323, 136739041, 140708338, 144758242, 148889790, // Levels 131 - 140
        153104021, 157401983, 161784728, 166253312, 170808801, 175452262, 180184770, 185007406, 189921255, 194927409  // Levels 132 - 150
    ];
    
    /*****************************************************************************/
    /* Public Functions
    /*****************************************************************************/
    
    /**
     * Returns API information about the given clan.
     */
    public function getClan($clanName) {
        $name = $this->getNameForURL($clanName);
        $url = self::BASE_CLAN_URL . $name;
        
        $curTime = time();
        // Make sure we have refetch and data directories.
        $this->ensureDirExists(self::REFETCH_DIR);
        $this->ensureDirExists(self::DATA_DIR);
        
        // Read the refetch file to see if our data is still valid.
        $refetchFile = "clan." . $name . ".txt";
        $needsRefresh = false;
        $lastUpdated = $curTime;
        $this->readRefetchFile($refetchFile, $needsRefresh, $lastUpdated, $curTime);
        
        // We make a new directory in our data directory for each clan, as we'll be storing multiple
        // versions of the fetched API data for future datamining.
        $clanDir = self::DATA_DIR . "clan/" . $name . "/";
        $this->ensureDirExists($clanDir);
        
        // The data file takes the form: {DATA_DIR}/clan/chronic+demise/1465269902.chronic+demise.txt
        $dataFile = $clanDir . strval($curTime) . "." . $name . ".txt";
        
        // For simplicity, we also save the latest copy with the timestamp omitted:
        // {DATA_DIR}/clan/chronic+demise/chronic+demise.txt
        $latestDataFile = $clanDir . $name . ".txt";
        
        // If we need to refresh the data, fetch latest copy from the RuneScape API.
        if ($needsRefresh) {
            $contents = $this->fetchData($url, $refetchFile);
            
            file_put_contents($dataFile, $contents);
            file_put_contents($latestDataFile, $contents);
            
            // Fetch the clan's latest motif image too.
            $motif = str_replace("{clan}", $name, self::CLAN_MOTIF_URL);
            $motifData = $this->fetch($motif);
            if ($motifData !== false) {
                file_put_contents($clanDir . "motif.png", $motifData);
            }
        }
        
        // Build out the return object.
        return $this->buildClanData($clanName, $lastUpdated, $latestDataFile);
    }
    
    /**
     * Returns the clan motif for the given clan.
     */
    public function getClanMotif($clan) {
        $name = $this->getNameForURL($clan);
        return self::DATA_DIR . "clan/" . $name . "/motif.png";
    }
    
    /**
     * Returns the user avatar for the given user. 
     */
    public function getUserAvatar($username) {
        $name = $this->getNameForURL($username);
        return self::DATA_DIR . "user/" . $name . "/avatar.png";
    }
    
    /**
     * Returns API information about the given user.
     */
    public function getUser($username) {
        if (!self::isValidUsername($username)) {
            throw new Exception("Username not valid.");
        }
        
        $name = $this->getNameForURL($username);
        $url = self::BASE_USER_URL . $name;
        
        $curTime = time();
        
        // Make sure we have refetch and data directories.
        $this->ensureDirExists(self::REFETCH_DIR);
        $this->ensureDirExists(self::DATA_DIR);
        
        // Read the refetch file to see if our data is still valid.
        $refetchFile = "user." . $name . ".txt";
        $needsRefresh = false;
        $lastUpdated = $curTime;
        $this->readRefetchFile($refetchFile, $needsRefresh, $lastUpdated, $curTime);
        
        // We make a new directory in our data directory for each user, as we'll be storing multiple
        // versions of the fetched API data for future datamining.
        $userDir = self::DATA_DIR . "user/" . $name . "/";
        $this->ensureDirExists($userDir);
        
        // The data file takes the form: {DATA_DIR}/user/berserkguard/1465269902.berserkguard.txt
        $dataFile = $userDir . strval($curTime) . "." . $name . ".txt";
        
        // For simplicity, we also save the latest copy with the timestamp omitted:
        // {DATA_DIR}/user/berserkguard/berserkguard.txt
        $latestDataFile = $userDir . $name . ".txt";
        
        // If we need to refresh the data, fetch latest copy from the RuneScape API.
        if ($needsRefresh) {
            $contents = $this->fetchData($url, $refetchFile);
            
            // Also fetch the website data (for clan name & title) & append to payload
            $json = $this->getWebsiteDataForUser($name);
            $contents .= "\n" . $json;
            
            $avatarFile = $this->getUserAvatar($name);
            $avatarContents = $this->getAvatarForUser($name);
            file_put_contents($avatarFile, $avatarContents);
            file_put_contents($dataFile, $contents);
            file_put_contents($latestDataFile, $contents);
        }
        
        // Build out the return object.
        return $this->buildUserData($username, $lastUpdated, $latestDataFile);
    }
    
    /**
     * Returns the level based on the given xp.
     */
    public static function getLevelFromXp($xp, $isElite) {
        $level = 2;
        
        $maxLevel = ($isElite ? 150 : 120);
        while ($level <= $maxLevel && $xp > self::getXpToLevel($level, 0, $isElite)) {
            $level++;
        }
        return $level - 1;
    }
    
    /**
     * Returns the total XP needed to get to the given level, less the given current XP.
     */
    public static function getXpToLevel($level, $curXp, $isElite) {
        if (!$isElite && $level > 120) {
            $level = 120;
        } else if ($isElite && $level > 150) {
            $level = 150;
        }
        if ($level < 0) {
            $level = 0;
        }
        
        if ($isElite) {
            return max(0, self::ELITE_XP_TABLE[$level] - $curXp);
        }
        return max(0, self::XP_TABLE[$level] - $curXp);
    }
    
    /**
     * Returns true if the specified skill name is an elite skill, else false.
     */
    public static function isElite($skill) {
        return ($skill == "Invention");
    }
    
    /**
     * Returns true if the specified username is a valid RuneScape username, else false.
     */
    public static function isValidUsername($username) {
        if (!is_string($username)) {
            return false;
        }
        $len = strlen($username);
        return ($len >= 1 && $len <= 12) && preg_match("/[a-zA-Z0-9\-]+/", $username);
    }
    
    /*****************************************************************************/
    /* Private Functions
    /*****************************************************************************/
    
    /**
     * Fetches & returns the latest avatar image from the RuneScape website.
     */
    private function getAvatarForUser($username) {
        $name = $this->getNameForURL($username);
        $url = str_replace("{user}", $name, self::USER_AVATAR_URL);
        return $this->fetch($url);
    }
    
    /**
     * Returns the RS website data for the given user. This includes clan name & title.
     */
    private function getWebsiteDataForUser($username) {
        $name = $this->getNameForURL($username);
        $url = str_replace("{user}", $name, self::USER_WEB_DATA_URL);
        
        preg_match("/\{.+\}/", $this->fetch($url), $matches);
        return $matches[0];
    }
    
    /**
     * Builds out clan data for the given user data file.
     */
    private function buildClanData($clanName, $lastUpdated, $dataFile) {
        $clan = [];
        $clan["Name"] = $clanName;
        $clan["Metadata"]["LastUpdated"] = $lastUpdated;
        $lines = explode("\n", file_get_contents($dataFile));
        
        $clan["Members"] = [];
        $clan["Metadata"]["Whitelist"] = [];
        for ($i = 1; $i < count($lines); $i++) {
            if ($lines[$i] === "") {
                continue;
            }
            $member = explode(",", $lines[$i]);
            $memberData = [
                "Name"  => $member[0],
                "Rank"  => $member[1],
                "XP"    => $member[2],
                "Kills" => $member[3],
            ];
            $clan["Members"][] = $memberData;
            $clan["Metadata"]["Whitelist"][] = strtolower(str_replace("\xA0", " ", $memberData["Name"]));
        }
        
        return $clan;
    }
    
    /**
     * Builds out user data for the given user data file path.
     */
    private function buildUserData($username, $lastUpdated, $dataFile) {
        $user = [];
        $user["Name"] = $username;
        $user["Metadata"]["LastUpdated"] = $lastUpdated;
        
        $lines = explode("\n", file_get_contents($dataFile));
        
        $maxedLevel = 0;
        $maxedXp = 0;
        $sumOf99s = 0;
        $sumOfXp99s = 0;
        $totalLevel = 0;
        for ($i = 0; $i < count(self::SKILLS); $i++) {
            $skillData = explode(",", $lines[$i]);
            
            $isElite = self::isElite(self::SKILLS[$i]);
            
            $skill = &$user["Skills"][self::SKILLS[$i]];
            
            $skill["Name"] = self::SKILLS[$i];
            $skill["Rank"] = $skillData[0];
            $skill["Level"] = $skillData[1];
            $skill["XP"] = $skillData[2];
            
            if ($skill["Name"] != "Overall") {
                $skill["Virtual"] = self::getLevelFromXp($skill["XP"], $isElite);
                $skill["Maxed"] = ($skill["Level"] >= 99);
                
                $totalLevel += $skill["Virtual"];
                
                $maxedXp += self::getXpToLevel(99, 0, $isElite);
                $sumOf99s += $skill["Level"] > 99 ? 99 : $skill["Level"];
                $sumOfXp99s += $skill["Level"] > 99 ? self::getXpToLevel(99, 0, $isElite) : $skill["XP"];
                if ($skill["Virtual"] < 120) {
                    $xpNeeded = self::getXpToLevel($skill["Virtual"] + 1, $skill["XP"], $isElite);
                    $xpCurLevel = self::getXpToLevel($skill["Virtual"], 0, $isElite);
                    $xpNextLevel = self::getXpToLevel($skill["Virtual"] + 1, 0, $isElite);
                    
                    $ratio = ($skill["XP"] - $xpCurLevel) / ($xpNextLevel - $xpCurLevel);
                    $skill["Progress"] = $ratio;
                } else {
                    $skill["Progress"] = 1.0;
                }
            }
        }
        
        // Overall Level
        $skill = &$user["Skills"]["Overall"];
        $maxedLevel = (count(self::SKILLS) - 1) * 99;
        $skill["Maxed"] = ($sumOf99s >= $maxedLevel);
        $skill["Progress"] = min(1.0, $sumOfXp99s / $maxedXp);
        $skill["Virtual"] = $totalLevel;
        
        // Overall XP
        $xp = $user["Skills"]["Overall"]["XP"];
        $user["Skills"]["XP"] = [
            "Name" => "XP",
            "Maxed" => ($xp == self::MAX_XP),
            "Progress" => ($xp / self::MAX_XP),
            "XP" => $xp,
            "Virtual" => 0
        ];
        
        $lastLine = $lines[count($lines) - 1];
        if (strpos($lastLine, "{") == 0) {
            // We have JSON data, parse it
            $obj = json_decode($lastLine, true);
            
            $user["Clan"] = $obj["clan"];
            $user["Title"] = $obj["title"];
            $user["IsTitleSuffix"] = $obj["isSuffix"];
        }
        
        return $user;
    }
    
    /**
     * Reads the contents of the specified refecth file, and updates needsRefresh and lastUpdated accordingly.
     */
    private function readRefetchFile($file, &$needsRefresh, &$lastUpdated, $curTime) {
        $needsRefresh = false;
        $lastUpdated = $curTime;
        if (!file_exists(self::REFETCH_DIR . $file)) {
            // No refetch file - need to refresh.
            $needsRefresh = true;
        } else {
            // File exists; read it in and check timestamp.
            $contents = file_get_contents(self::REFETCH_DIR . $file);
            $timestamp = intval($contents);
            
            if ($timestamp + self::MIN_REFRESH < time()) {
                // We're past the valid cache period - need to refresh.
                $needsRefresh = true;
            } else {
                // Otherwise, make sure we set our last updated time.
                $lastUpdated = $timestamp;
            }
        }
    }
    
    /**
     * Fetches data from the URL
     */
    private function fetchData($url, $refetchFile) {
        $contents = $this->fetch($url);
        if (isset($refetchFile) && $contents !== false) {
            // Update our last updated blob.
            file_put_contents(self::REFETCH_DIR . $refetchFile, strval(time()));
        }
        return $contents;
    }
    
    /**
     * Fetches data from the specified URL using cURL.
     */
    private function fetch($url) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, utf8_encode($url));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        
        $result = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($result !== false && $responseCode == 200) {
            return $result;
        }
        return false;
    }
    
    /**
     * Creates the specified directory if it doesn't exist.
     */
    private function ensureDirExists($path) {
        if (!is_dir($path)) {
            mkdir($path, 0740, true);
        }
    }
    
    /**
     * Returns the URL-friendly name for the given user/clan.
     */
    private function getNameForURL($name) {
        if (!preg_match("/^[a-zA-Z0-9\+]+$/", $name)) {
            return urlencode(strtolower(trim($name)));
        }
        return strtolower($name);
    }
}

?>