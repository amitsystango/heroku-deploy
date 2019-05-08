<?php
//$db = mysqli_connect("localhost", "root", "");
//$db = mysqli_connect("localhost", "itechmid_amit", "@m1t#1");
//if(!$db)
  //die("Unable to connect");
//mysqli_select_db($db, "property");
//mysqli_select_db($db, "itechmid_site");


$json = file_get_contents('php://input');
/*$json = '{
  "responseId": "a8c46d5d-1753-439b-9129-b49a1b1227a3",
  "queryResult": {
    "queryText": "looking for condos in ca",
    "parameters": {
      "geo-state": "California",
      "property-type": "condo",
      "number": "",
      "geo-city": ""
    },
    "allRequiredParamsPresent": true,
    "fulfillmentText": "We found 21 results, here are top 3. Would you like to see more or search again?",
    "fulfillmentMessages": [
      {
        "text": {
          "text": [
            "We found 21 results, here are top 3. Would you like to see more or search again?"
          ]
        }
      }
    ],
    "outputContexts": [
      {
        "name": "projects/propert-616eb/agent/sessions/7d8da6c6-f680-d332-b5e8-1c00bbdaec81/contexts/welcome-followup",
        "lifespanCount": 2,
        "parameters": {
          "number": "",
          "property-type.original": "condos",
          "geo-state": "California",
          "geo-city": "",
          "geo-city.original": "",
          "geo-state.original": "ca",
          "number.original": "",
          "property-type": "condo"
        }
      }
    ],
    "intent": {
      "name": "projects/propert-616eb/agent/intents/ed2d4cb5-3197-4241-996e-52c83bbb341c",
      "displayName": "Welcome"
    },
    "intentDetectionConfidence": 1,
    "languageCode": "en"
  },
  "originalDetectIntentRequest": {
    "payload": {}
  },
  "session": "projects/propert-616eb/agent/sessions/7d8da6c6-f680-d332-b5e8-1c00bbdaec81"
}';*/

$data = json_decode($json,true);
$city = $data['queryResult']['parameters']['geo-city'];
$state = $data['queryResult']['parameters']['geo-state'];
$property_type = $data['queryResult']['parameters']['property-type'];
$sq_ft = $data['queryResult']['parameters']['number'];
$action = $data['queryResult']['action'];

if($action == 'Welcome.Welcome-more') {
  $city = $data['queryResult']['outputContexts']['geo-city'];
  $state = $data['queryResult']['outputContexts']['geo-state'];
  $property_type = $data['queryResult']['outputContexts']['property-type'];
  $sq_ft = $data['queryResult']['outputContexts']['number'];
}

$city = strtoupper(trim($city));
$state = trim($state);

if(strtolower($state) == 'california')
  $state = 'CA';

switch($property_type) {
  case 'condo':
  case 'condos':
    $property_type = 'Condo';
    break;
  case 'living home':
  case 'living homes':
  case 'home':
  case 'homes':
  case 'house':
  case 'houses':
    $property_type = 'Residential';
    break;
  default:
    $property_type = '';
    break;
}
$state = strtoupper(trim($state));


$properties = json_decode(file_get_contents("property.json"),true);

$fullfilmentTextLocation = '';
if($city != '' || $state != '') {
  $fullfilmentTextLocation = ' in';
} else {
  $fullfilmentTextLocation = ' near you';
}

if($city != '') {
  $properties = array_filter($properties, function($property) use (&$city) {
    return $property['city'] == $city;
  });
  $fullfilmentTextLocation .= " ".$city;
}
if($state != '') {
  $properties = array_filter($properties, function($property) use (&$state) {
    return $property['state'] == $state;
  });
  $fullfilmentTextLocation .= " ".$state;
}
if($property_type != '') {
  $properties = array_filter($properties, function($property) use (&$property_type) {
    return $property['type'] == $property_type;
  });
}
if($sq_ft != '') {
  $properties = array_filter($properties, function($property) use (&$sq_ft) {
    return $property['sq__ft'] == $sq_ft;
  });
}


  $total_rows = count($properties);
  $final_return_rows = $total_rows;
  $start_index = 0;
  $fulfilmentTextAddon = "";
  if($total_rows >= 3) {
    $fulfilmentText = "We found a total of ".$total_rows." properties".$fullfilmentTextLocation.". Here are details of top 3:<br>";
    $final_return_rows = 3;
    $fulfilmentTextAddon = "Would you like to search again? Or see more results?";
  } else if($total_rows > 0 && $total_rows < 3) {
    $fulfilmentText = "We found a total of ".$total_rows." properties".$fullfilmentTextLocation.". Here are their details:<br>";
    $fulfilmentTextAddon = "Would you like to search again?";
  } else {
    $fulfilmentText = 'We did not find anything matching your query, please try again.';
    $fulfilmentTextAddon = "Would you like to search again?";
  }

  $i = -1;
  if($action == 'Welcome.Welcome-more') {
    $fulfilmentText = "Here are 3 more results: <br>";
    $fulfilmentTextAddon = "Would you like to search again?";
    $final_return_rows = 6;
    $start_index = 3;
  }
  foreach($properties as $row){
    $i++;
    if($i >= $final_return_rows)
      break;
    if($i < $start_index)
      continue;
    $fulfilmentText .= "Address: ".$row['street']." ".$row['city']." ".$row['state'].", ".$row['zip']."<br>";
  }
  $fulfilmentText .= $fulfilmentTextAddon;
$ar = array(
  "fulfillmentText" => $fulfilmentText,
  "source" => "main"
);
echo json_encode($ar);
?>