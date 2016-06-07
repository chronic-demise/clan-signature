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
    
    /** Base URL for accessing the RuneScape clans API. */
    const BASE_CLAN_URL = "http://hiscore.runescape.com/members_lite.ws?clanName=";
    
    /** Base URL for accessing the RuneScape user API. */
    const BASE_USER_URL = "http://hiscore.runescape.com/index_lite.ws?player=";
    
    /** Minimum number of seconds to wait before refetching latest data. */
    const MIN_REFRESH = 24 * 60 * 60;
    
    /** Enumeration of RuneScape skills, in row-order as returned by the RuneScape API. */
    const SKILLS = [
        "Overall",
        "Attack", "Defence", "Strength", "Constitution", "Ranged", "Prayer", "Magic", "Cooking", "Woodcutting",
        "Fletching", "Fishing", "Firemaking", "Crafting", "Smithing", "Mining", "Herblore", "Agility", "Thieving",
        "Slayer", "Farming", "Runecrafting", "Hunter", "Construction", "Summoning", "Dungeoneering", "Divination"
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
        
        /**
         * Clanmate, Clan Rank, Total XP, Kills
         * Enteater1,Owner,597282220,2
         * Aradonna,Deputy Owner,46289714,0
         * DevilChief,Coordinator,958624720,1
         */
        
        return "unimplemented";
    }
    
    /**
     * Returns API information about the given user.
     */
    public function getUser($username) {
        $name = $this->getNameForURL($username);
        $url = self::BASE_USER_URL . $name;
        
        $curTime = time();
        
        $refetchFile = "user." . $name . ".txt";
        
        // Make sure we have refetch and data directories.
        $this->ensureDirExists(self::REFETCH_DIR);
        $this->ensureDirExists(self::DATA_DIR);
        
        // Assume the last update time is the current time. We'll update this if using cached data.
        $lastUpdated = $curTime;
        
        $needsRefresh = false;
        if (!file_exists(self::REFETCH_DIR . $refetchFile)) {
            // No refetch file - need to refresh.
            $needsRefresh = true;
        } else {
            // File exists; read it in and check timestamp.
            $contents = file_get_contents(self::REFETCH_DIR . $refetchFile);
            $timestamp = intval($contents);
            
            if ($timestamp + self::MIN_REFRESH < $curTime) {
                // We're past the valid cache period - need to refresh.
                $needsRefresh = true;
            } else {
                // Otherwise, make sure we set our last updated time.
                $lastUpdated = $timestamp;
            }
        }
        
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
            $contents = $this->fetch($url);
            
            if ($contents !== false) {
                // Store locally for future datamining.
                file_put_contents($dataFile, $contents);
                file_put_contents($latestDataFile, $contents);
                
                // Update our last updated blob.
                file_put_contents(self::REFETCH_DIR . $refetchFile, strval($curTime));
            }
        }
        
        // Build out the return object.
        $user = [];
        $user["Metadata"]["LastUpdated"] = $lastUpdated;
        
        $lines = explode("\n", file_get_contents($latestDataFile));
        
        for ($i = 0; $i < count(self::SKILLS); $i++) {
            $skillData = explode(",", $lines[$i]);
            
            $user["Skills"][self::SKILLS[$i]]["Rank"] = $skillData[0];
            $user["Skills"][self::SKILLS[$i]]["Level"] = $skillData[1];
            $user["Skills"][self::SKILLS[$i]]["XP"] = $skillData[2];
        }
        
        return $user;
    }
    
    /*****************************************************************************/
    /* Private Functions
    /*****************************************************************************/
    
    /**
     * Fetches data from the specified URL using cURL.
     */
    private function fetch($url) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, utf8_encode($url));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        
        $result = curl_exec($ch);
        
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($responseCode == 200 && $result) {
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
        return urlencode(strtolower(trim($name)));
    }
}

?>
