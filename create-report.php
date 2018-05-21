<?php
//Some initialization settings
ini_set('max_execution_time', 600);
date_default_timezone_set('Asia/Calcutta');

/******************************************
 * Change these values
 ******************************************/
//Setup hostname with correct port
define("HOSTNAME", "https://whm.hostname.com:2087");
//Username of the WHM account that owns the sender's cPanel account
define("USER", "<WHM_USER>");
//Generate a token from WHM > Development > Manage API Tokens
define("TOKEN", "<TOKEN_VALUE>");

/* mktime order of content (hour, minute, seconds, month, day, year) */
//Enter starting time
$startTimestamp = mktime(10, 00, 00, 5, 19, 2018);
//Enter ending time
$endTimestamp = mktime(18, 00, 00, 5, 21, 2018);
//Enter sender's email address
$sender = 'sender@domain.com';


//Main call
$records = fetchRecordsByDate($sender, $startTimestamp, $endTimestamp);

//Output all contents to a CSV file
header("Content-type: text/csv");
header("Content-Disposition: attachment; filename=file.csv");
header("Pragma: no-cache");
header("Expires: 0");

//Saving 4 main fields. Can add other fields as needed
foreach ($records as $recordItem) {
  echo '"'.date('r', $recordItem->sendunixtime).'",'.
        $recordItem->recipient.','.
        $recordItem->msgid.','.
        '"'.$recordItem->message.'"'.PHP_EOL;
}


/******************************************
 * Function to fetch records in batches
 * WHM seems to limit each API call to 250 records
 ******************************************/
function fetchRecordsByDate($sender, $startTimestamp, $endTimestamp) {
  //in seconds i.e. 60 secs x 30 mins = 30 minutes
  $duration = 60*30;

  //Set startTime to startTimestamp
  $startTime = $startTimestamp;
  $endTime = $startTimestamp;

  $results = array();

  //Check that endTime is less than endTimestamp
  while ($endTime < $endTimestamp) {
    //Set startTime to previous endTime
    $startTime = $endTime;
    //Increase endTime by $duration
    $endTime += $duration;
    //If endTime is later than endTimestamp, limit it
    if ($endTime > $endTimestamp) {
      $endTime = $endTimestamp;
    }
    //Call fetchRecordsByHour()
    $tempResult = fetchRecordsByHour($sender, $startTime, $endTime);
    //Loop through results and save the individual records
    foreach ($tempResult as $tempResultItem) {
      $results[] = $tempResultItem;
    }
  }

  return $results;
}


/******************************************
 * Function to make the API call to WHM server
 ******************************************/
function fetchRecordsByHour($sender, $startTime, $endTime) {
  //Set up variables
  $user = USER;
  $token = TOKEN;
  $hostname = HOSTNAME;

  //Set up query - https://documentation.cpanel.net/display/DD/WHM+API+1+Functions+-+emailtrack_search
  $query = $hostname.'/json-api/emailtrack_search?api.version=1'.
            '&api.filter.enable=1'. //Enable filter (https://documentation.cpanel.net/display/DD/WHM+API+1+-+Filter+Output)
            '&api.filter.a.field=sender&api.filter.a.arg0='.$sender.'&api.filter.a.type=eq'. //Filter records with $sender
            '&api.filter.b.field=sendunixtime&api.filter.b.arg0='.$startTime.'&api.filter.b.type=gt'. //Filter records greater than $startTime
            '&api.filter.c.field=sendunixtime&api.filter.c.arg0='.$endTime.'&api.filter.c.type=lt'. //Filter records less than $endTime
            '&api.sort.enable=1'. //Enable sorting (https://documentation.cpanel.net/display/DD/WHM+API+1+-+Sort+Output)
            '&api.sort.a.field=sendunixtime&api.sort.a.method=numeric&api.sort.a.reverse=0'. //Sort by sent time, not in reverse (By default, the API sorts in reverse order)
//          '&api.chunk.enable=0&api.chunk.size=999'. //WHM's API returns a maximum of 250 records, so I tried increasing the paginated chunk size, but this didn't work for me. It may work in the future, so leaving this commented here (https://documentation.cpanel.net/display/DD/WHM+API+1+-+Paginate+Output)
            '&success=1'. //Fetch success emails
            '&defer=1'. //Fetch defered emails
            '&failure=1'. //Fetch failed emails
            '&inprogress=1'. //Fetch in progress emails
            '&deliverytype=all'. //Fetch remote and local emails
            '&max_results_by_type=999'; // Fetch a large number of records per batch (Since WHM limits the total at 250 records, anything above 250 is fine)

  //Initialize CURL
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($curl, CURLOPT_URL, $query);

  //Set authentication parameters
  $header[0] = "Authorization: whm $user:$token";
  curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

  $result = curl_exec($curl);

  //Check for CURL error
  $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
  if ($http_status != 200) {
    echo "Error: ".$http_status." returned";
  }

  curl_close($curl);

  //Return only the records, stripping away all the metadata
  return json_decode($result)->data->records;
}

exit();
?>
