 <?php
require("OpenLDBWS.php");

include "tokens.php";

include "crs.php";

$from_station = null;
$to_station = null;
$to = False;

if (isset($_POST["token"])) {
  if ($_POST["token"] != $slack_command_token) { #replace this with the token from your slash command configuration page
    $msg = "The token for the slash command doesn't match. Check your script.";
    die($msg);
    echo $msg;
  }
}

if (isset($_GET["from"])) {
  $from_station = htmlspecialchars($_GET["from"]);
} elseif (isset($_POST['text'])) {
  $from_station = htmlspecialchars($_POST["text"]);
}

if (is_null($from_station)) {
    $msg = "Sorry, you didn't tell me which station. Try again...";
    die($msg);
    echo $msg;
} else {

  $from_station = str_replace(" ", "%20", $from_station);
  $command = explode("%20", $from_station);
  // remove head of array as station
  $from_station = array_shift($command);
  // for each in command
  while (count($command) > 0) {
    if (strcmp(strtolower($command[0]), 'to') != 0) {
      $from_station = $from_station." ".array_shift($command);
    } else {
      break;
    }
  }
  if (strcmp(strtolower($command[0]), 'to') == 0) {
    array_shift($command);
    $to = True;
  }
  while (count($command) > 0) {
    if (is_null($to_station)) {
      $to_station = array_shift($command);
    } else {
      $to_station = $to_station." ".array_shift($command);
    }
  }

  if (!array_key_exists(strtolower($from_station), $crs_array)) {
    if (!in_array(strtolower($from_station), array_values($crs_array))) {
      $msg = "Sorry, I can't find that station. Try again...";
      die($msg);
      echo $msg;
    } else {
      $from_station = strtoupper(array_search(strtolower($from_station), $crs_array));
    }
  }
}

if ($to) {
  if (is_null($to_station)) {
      $msg = "Sorry, you didn't give me a destination. Try again...";
      die($msg);
      echo $msg;
  } else {
    if (!array_key_exists(strtolower($to_station), $crs_array)) {
      if (!in_array(strtolower($to_station), array_values($crs_array))) {
        $msg = "Sorry, I can't find your destination. Try again...";
        die($msg);
        echo $msg;
      } else {
        $to_station = strtoupper(array_search(strtolower($to_station), $crs_array));
      }
    }
  }
}

// Access API

$OpenLDBWS = new OpenLDBWS($nationalrail_token);

if ($to) {
  $phpresponse = $OpenLDBWS->GetDepartureBoard(1, $from_station,$to_station);
} else {
  $phpresponse = $OpenLDBWS->GetArrivalDepartureBoard(1,$from_station);
}

if (!isset($phpresponse->GetStationBoardResult->trainServices)) {

  if (isset($phpresponse->GetStationBoardResult->busServices)) {
    echo 'The next train is actually a bus...';
  } elseif (isset($phpresponse->GetStationBoardResult->ferryServices)) {
    echo 'The next train is acutally a ferry... ahoy!';
  } else {
    echo 'Sorry, unable to find any trains...';
  }

} else {

if ($to) {
  echo 'The next train from '.$phpresponse->GetStationBoardResult->locationName.' to '.$phpresponse->GetStationBoardResult->filterLocationName;
} else {
  echo 'The next train at '.$phpresponse->GetStationBoardResult->locationName;
}

// if std = null, train is arrival
if (!isset($phpresponse->GetStationBoardResult->trainServices->service->std)) {
  echo ' is the '.$phpresponse->GetStationBoardResult->trainServices->service->sta;
  echo ' from '.$phpresponse->GetStationBoardResult->trainServices->service->origin->location->locationName;
  if (strcmp($phpresponse->GetStationBoardResult->trainServices->service->eta, "On time") !== 0) {
    echo ' due at '.$phpresponse->GetStationBoardResult->trainServices->service->eta;
  }
} else {
  // if sta = null, train is departure
  echo ' is the '.$phpresponse->GetStationBoardResult->trainServices->service->std;
  if (!isset($phpresponse->GetStationBoardResult->trainServices->service->sta)) {
    echo ' from '.$phpresponse->GetStationBoardResult->trainServices->service->origin->location->locationName;
  }
  echo ' to '.$phpresponse->GetStationBoardResult->trainServices->service->destination->location->locationName;
  if (strcmp($phpresponse->GetStationBoardResult->trainServices->service->etd, "On time") !== 0) {
    echo ' due at '.$phpresponse->GetStationBoardResult->trainServices->service->etd;
  }

}

}
