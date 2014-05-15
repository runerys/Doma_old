<?php
  include_once(dirname(__FILE__) ."/include/main.php");
  
  class RSSController
  {   
    public function Execute()
    {
      $viewData = array();
      
      $dateformat = "r";
     
      if(isset($_GET["dateformat"]))
      {
        $dateformat = $_GET["dateformat"];
      }

      // check if user is not specified or hidden
      if(!getCurrentUser())
      {
        // all maps in archive
        $mode = "all";
        $count = $_GET["count"];
        if(!$count) $count = 30;
        $maps = DataAccess::GetMaps(0, 0, 0, 0, null, $count, "ID");
        $categories = DataAccess::GetCategoriesByUserID();
        $users = DataAccess::GetAllUsers(true);
        $viewData["Title"] = _SITE_TITLE;
        $viewData["LastCreatedTime"] = date($dateformat, DataAccess::GetLastCreatedTime());
        $viewData["Description"] = _SITE_DESCRIPTION;
        $viewData["WebsiteUrl"] = Helper::GlobalPath("index.php");
      }
      else
      {
        // specified archive
        $mode = "user";
        if(!getCurrentUser()->Visible) die();
        $users[getCurrentUser()->ID] = getCurrentUser();
        $maps = DataAccess::GetMaps(getCurrentUser()->ID);
        $categories = DataAccess::GetCategoriesByUserID(getCurrentUser()->ID);
        $viewData["Title"] = __("PAGE_TITLE");
        $viewData["LastCreatedTime"] = date($dateformat, DataAccess::GetLastCreatedTime(getCurrentUser()->ID));
        $viewData["Description"] = __("RSS_DESCRIPTION");
        $viewData["WebsiteUrl"] = Helper::GlobalPath("index.php?". Helper::CreateQuerystring(getCurrentUser()));
      }
      
      $viewData["Items"] = array();

      if(!$this->AllowRssGet())
        return $viewData;

      foreach($maps as $map)
      {             
        if($this->ShouldSkip($map))
          continue;
        
        $item = array();
        $user = $users[$map->UserID];
        $item["Title"] = hsc(Helper::DateToLongString(Helper::StringToTime($map->Date, true)) .": ". $map->Name);
        $item["URL"] = ($map->MapImage ? Helper::GlobalPath('show_map.php?user='. urlencode($user->Username) .'&amp;map='. $map->ID) : "");
        
        $atoms = array();
        if(__("SHOW_MAP_AREA_NAME") && $map->MapName != "") $atoms[] = $map->MapName;
        if(__("SHOW_ORGANISER") && $map->Organiser != "") $atoms[] = $map->Organiser;
        if(__("SHOW_COUNTRY") && $map->Country != "") $atoms[] = $map->Country;

        $atoms2 = array();
        if(__("SHOW_DISCIPLINE") && $map->Discipline != "") $atoms2[] = hsc($map->Discipline);
        if(__("SHOW_RELAY_LEG") && $map->RelayLeg != "") $atoms2[] = __("RELAY_LEG_LOWERCASE") .' '. hsc($map->RelayLeg);
        
        $item["Description"] = 
          ($mode == "all" ? hsc($user->FirstName ." ". $user->LastName .'<br />') : '') .
          __("CATEGORY") .": ". hsc($categories[$map->CategoryID]->Name) . 
          hsc('<br />'). 
          hsc(join(", ", $atoms)) .
          hsc('<br />'). 
          join(", ", $atoms2);
          
        if($_GET["nameonly"] === '1')
           $item["Description"] = hsc($user->FirstName ." ". $user->LastName);
        
        $item["PubDate"] = hsc(date($dateformat, Helper::StringToTime($map->CreatedTime, true)));      
        $viewData["Items"][] = $item;      
      }
      
      return $viewData;
    }
    
    private function AllowRssGet()
    {
        // Deny client IP if on the blacklist
        if(strlen(RSS_BLOCK_IP_ADDRESSES) > 0)
        {
            $blockIps = explode(';', RSS_BLOCK_IP_ADDRESSES);
            $clientIp = $this->getClientIP();
            
            foreach($blockIps as $blockIp)
            {
                if(strcmp($clientIp, $blockIp) == 0)    
                    return false;
            }
        }
            
        // No secret configured: Allow
        if(strlen(RSS_ALLOW_GET_WITH_SECRET) == 0)
            return true;
      
        // No secret presented in Url: Deny
        if(!isset($_GET["secret"]))
            return false;
        
        $fromUrl = $_GET["secret"];
        $secrets = explode(';', RSS_ALLOW_GET_WITH_SECRET);
        
        foreach($secrets as $secret)
        {
            // Compare Case-Insensitive. 0=equal
            if (strcasecmp($secret, $fromUrl) == 0)
            {        
                // Url matches a configured secret: Allow
                return true;
            }
        }
       
        // No match: Deny
        return false;               
    }

    private function ShouldSkip($map)
    {         
      // Allow all fetching unless dicipline filter is set
      if(strlen(RSS_SKIP_MAPS_WITH_DICIPLINE_CONTAINING) == 0)
        return false;            
      
      $discipline = $map->Discipline;        
      
      $stopWords = explode(';', RSS_SKIP_MAPS_WITH_DICIPLINE_CONTAINING);
        
      foreach($stopWords as $stopWord)
      {
        if (stripos($discipline, $stopWord) !== false)
        {        
          return true;
        }
      }
      
      return false;
    }  
    
    function getClientIP() 
    {
        if (isset($_SERVER))
        {
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
                return $_SERVER["HTTP_X_FORWARDED_FOR"];

            if (isset($_SERVER["HTTP_CLIENT_IP"]))
                return $_SERVER["HTTP_CLIENT_IP"];

            return $_SERVER["REMOTE_ADDR"];
        }

        if (getenv('HTTP_X_FORWARDED_FOR'))
            return getenv('HTTP_X_FORWARDED_FOR');

        if (getenv('HTTP_CLIENT_IP'))
            return getenv('HTTP_CLIENT_IP');

        return getenv('REMOTE_ADDR');
    }  
  }
?>
