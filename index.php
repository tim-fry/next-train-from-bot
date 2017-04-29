 <?php

include "tokens.php";

include "crs.php";

$station = null;

if (isset($_POST["token"])) {
  if ($_POST["token"] != $slack_command_token){ #replace this with the token from your slash command configuration page
    $msg = "The token for the slash command doesn't match. Check your script.";
    die($msg);
    echo $msg;
  }
}

if (isset($_GET["from"])) {
  $station = htmlspecialchars($_GET["from"]);
} elseif (isset($_POST['text'])) {
  $station = htmlspecialchars($_POST["text"]);
}

if (is_null($station)) {
    $msg = "Sorry, you didn't tell me which station. Try again...";
    die($msg);
    echo $msg;
} else {

  if (!array_key_exists(strtolower($station), $crs_array)) {
    if (!in_array(strtolower($station), array_values($crs_array))) {
      $msg = "Sorry, I can't find that station. Try again...";
      die($msg);
      echo $msg;
    } else {
      $station = $crs_array[array_search(strtolower($station), $crs_array)];
    }

  }

$response = file_get_contents("https://huxley.apphb.com/all/".$station."/1?accessToken=".$nationalrail_token);

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

// if std = null, train is arrival
if (is_null($json["trainServices"][0]["std"])) {
  echo 'The next train is the '.$json["trainServices"][0]["sta"];
  echo ' from '.$json["trainServices"][0]["origin"][0]["locationName"];
  if (strcmp($json["trainServices"][0]["eta"], "On time") !== 0) {
    echo ' due at '.$json["trainServices"][0]["eta"];
  }
} else {
  // if sta = null, train is departure
  echo 'The next train is the '.$json["trainServices"][0]["std"];
  if (!is_null($json["trainServices"][0]["sta"])) {
    echo ' from '.$json["trainServices"][0]["origin"][0]["locationName"];
  }
  echo ' to '.$json["trainServices"][0]["destination"][0]["locationName"];
  if (strcmp($json["trainServices"][0]["etd"], "On time") !== 0) {
    echo ' due at '.$json["trainServices"][0]["etd"];
  }

}

}

}

?>
