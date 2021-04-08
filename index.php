 <?php
    header("Access-Control-Allow-Origin: *");
    header('Content-Type: application/json');

    global $configs;
    $configs = include('config.php');
    $bathinglocations =  getBathingLocationsFromJsonFile();

    $frames=[];
    $poolTempreatures=[];
    $date="";
    $location="";

    $showDate=(isset($_REQUEST["showDate"])&&$_REQUEST["showDate"]=="true");
    $showBathingLocationName=(isset($_REQUEST["showBathingLocationName"])&&$_REQUEST["showBathingLocationName"]=="true");
    $showAllPools=(isset($_REQUEST["showAllPools"])&&$_REQUEST["showAllPools"]=="true");

    

    if (isset($_REQUEST["bathingLocation"])) {
        $location=$_REQUEST["bathingLocation"];
        $bathinglocationid=$bathinglocations[urldecode($_REQUEST["bathingLocation"])];
        $poolTempreatures=getBathingLocationTempreature($bathinglocationid);
        $i=0;
        foreach ($poolTempreatures as $poolTempreature) {            
            if($i==0 || $showAllPools){
                addFrames($frames,$poolTempreature->temp . "° ", getIcon($poolTempreature->temp));
                $date= toGermanDate($poolTempreature->date);
            }
            $i++;           
        }        
    }

    if($showBathingLocationName) addFrames($frames,$location, null);
    if($showDate) addFrames($frames,$date, null);    
    

    $lametricfeedback["frames"]=$frames;
    echo json_encode($lametricfeedback);




   

    function getIcon($value){
        global $configs;

        $iconKey="icon_temperature";
        switch (true) {
            case $value <= $configs["cold_temperature_upto"]:
                $iconKey.="_cold";
                break;
        
            case $value <= $configs["low_temperature_upto"]:
                $iconKey.="_low";
                break;
        
            case $value <= $configs["middle_temperature_upto"]:
                $iconKey.="_middle";
                break;
        
            default:
                $iconKey.="_hot";
                break;
        }
        
        return $configs[$iconKey];
    }

    function addFrames(&$frames,$text, $iconkey)
    {
        array_push($frames, array("text" => $text, "icon" => $iconkey));
    }


    function getBathingLocationTempreature($bathinglocationid)
    {
        global $configs;

        try {
            $result = callAPI("GET", $configs["api_baseurl"] . "temperature.json/" .$bathinglocationid . "/", array("api_key" => $configs["api_key"]));
            return json_decode($result);
        } catch (Exception $ex) {
        }
    }

    function getBathingLocationsFromJsonFile()
    {
        // Get the contents of the JSON file 
        $strJsonFileContents = file_get_contents("bathinglocations.json");
        // Convert to array 
        return json_decode($strJsonFileContents, true);
    }

    // Method: POST, PUT, GET etc
    // Data: array("param" => "value") ==> index.php?param=value
    function callAPI($method, $url, $data = false)
    {
        $curl = curl_init();

        switch ($method) {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);

                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case "PUT":
                curl_setopt($curl, CURLOPT_PUT, 1);
                break;
            default:
                if ($data)
                    $url = sprintf("%s?%s", $url, http_build_query($data));
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl);

        curl_close($curl);

        return $result;
    }

    function toGermanDate($date){
        return date('d.m.Y H:i' ,strtotime($date)) ;
    }

    
