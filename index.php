 <?php

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
      $from_station = $crs_array[array_search(strtolower($from_station), $crs_array)];
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
        $to_station = $crs_array[array_search(strtolower($to_station), $crs_array)];
      }
    }
  }
}

// Generate Address
$address = "https://huxley.apphb.com/all/";

if ($to) {
  $address = $address.$from_station."/to/".$to_station."/1?accessToken=".$nationalrail_token;
} else {
  $address = $address.$from_station."/1?accessToken=".$nationalrail_token;
}

$response = file_get_contents($address);

$json = json_decode($response, true);

if (is_null($json["trainServices"])) {

  if (!is_null($json["busServices"])) {
    echo 'The next train is actually a bus...';
  } elseif (!is_null($json["ferryServices"])) {
    echo 'The next train is acutally a ferry... ahoy!';
  } else {
    echo 'Sorry, unable to find any trains...';
  }

} else {

if ($to) {
  echo 'The next train from '.$json["locationName"].' to '.$json["filterLocationName"];
} else {
  echo 'The next train at '.$json["locationName"];
}

// if std = null, train is arrival
if (is_null($json["trainServices"][0]["std"])) {
  echo ' is the '.$json["trainServices"][0]["sta"];
  echo ' from '.$json["trainServices"][0]["origin"][0]["locationName"];
  if (strcmp($json["trainServices"][0]["eta"], "On time") !== 0) {
    echo ' due at '.$json["trainServices"][0]["eta"];
  }
} else {
  // if sta = null, train is departure
  echo ' is the '.$json["trainServices"][0]["std"];
  if (!is_null($json["trainServices"][0]["sta"])) {
    echo ' from '.$json["trainServices"][0]["origin"][0]["locationName"];
  }
  echo ' to '.$json["trainServices"][0]["destination"][0]["locationName"];
  if (strcmp($json["trainServices"][0]["etd"], "On time") !== 0) {
    echo ' due at '.$json["trainServices"][0]["etd"];
  }

}

}
