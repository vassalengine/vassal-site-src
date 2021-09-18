{% extends "base.html" %}
{% block title %}Vassal Status{% endblock %}
{% block description %}See what's happening on the Vassal game server right now!{% endblock %}
{% block head %}
  {{ super() }}

  <script src="/js/mktree.js"></script>
  <script src="/js/status.js"></script>

  <link rel="stylesheet" type="text/css" href="/css/mktree.css"/>
  <link rel="stylesheet" type="text/css" href="/css/status.css"/>
{% endblock %}
{% block content %}

  <div class="hero border-bottom mb-5">
    <div class="container-md px-5 py-5 mt-5">
      <div class="row mb-4">
        <div class="col text-center">
          <h1 class="display-4 fw-bold">Server Status</h1>
        </div>
      </div>
      <div class="row justify-content-center text-md-center">
        <div class="col-md-8">
          <p class="fs-5">Here you can see which modules are being used with the game server at present, and which modules have been used in the past day, week, and month.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="container mb-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <ul class="tab_menu">
<?php

$when = isset($_GET['when']) ? $_GET['when'] : 'current';

switch ($when) {
case 'current':
  echo <<<END
      <li class="showing"><a href="?when=current">Current</a></li>
      <li><a href="?when=day">Day</a></li>
      <li><a href="?when=week">Week</a></li>
      <li><a href="?when=month">Month</a></li>
END;
  break;
case 'day':
  echo <<<END
      <li><a href="?when=current">Current</a></li>
      <li class="showing"><a href="?when=day">Day</a></li>
      <li><a href="?when=week">Week</a></li>
      <li><a href="?when=month">Month</a></li>
END;
  break;
case 'week':
  echo <<<END
      <li><a href="?when=current">Current</a></li>
      <li><a href="?when=day">Day</a></li>
      <li class="showing"><a href="?when=week">Week</a></li>
      <li><a href="?when=month">Month</a></li>
END;
  break;
case 'month':
  echo <<<END
      <li><a href="?when=current">Current</a></li>
      <li><a href="?when=day">Day</a></li>
      <li><a href="?when=week">Week</a></li>
      <li class="showing"><a href="?when=month">Month</a></li>
END;
  break;
default:
  throw new ErrorException('Unrecognized when: ' . $when);
}

?>
      <li><img id="toggle" src="/images/green-plus.png" alt="Expand"/></li>
    </ul>

    <div class="tab_contents">
      <ul class="mktree" id="stree">
<?php

try {
  # connect to the SQL server
  require_once('util/vserver-config.php');

  $dbh = mysqli_connect(SQL_HOST, SQL_USERNAME, SQL_PASSWORD, SQL_DB);
  if (mysqli_connect_errno()) {
    throw new RuntimeException('Connection failed: ' . mysqli_connect_error());
  }

  $query = 'SELECT DISTINCT module_name, game_room, player_name FROM connections ';

  switch ($when) {
  case 'current':
    # get the set of rows having the most recent timestamp
    $query .= 'WHERE time = (SELECT MAX(time) FROM connections) ';
    break;
  case 'day':
    $query .= 'WHERE DATEDIFF(NOW(), time) <= 1 ';
    break;
  case 'week':
    $query .= 'WHERE DATEDIFF(NOW(), time) <= 7 ';
    break;
  case 'month':
    $query .= 'WHERE DATEDIFF(NOW(), time) <= 30 ';
    break;
  default:
    throw new RuntimeException('Unrecognized when: ' . $when);
  }

  $query .= 'ORDER BY module_name, game_room, player_name';

  $r = mysqli_query($dbh, $query);
  if (!$r) {
    throw new RuntimeException('SELECT failed: ' . mysqli_error($dbh));
  }

  $tree = array();

  while (($row = mysqli_fetch_row($r))) {
    $tree[$row[0]][$row[1]][] = $row[2];
  }

  # NB: root is liClosed to prevent the tree from being
  # rendered as expanded before all the parts are loaded
  echo sprintf(
    "<li id=\"root\" class=\"liClosed\">Vassal (%d)\n",
    mysqli_num_rows($r)
  );

  mysqli_free_result($r);

  echo ' <ul>', "\n";

  $first = true;
  foreach ($tree as $module => $rooms) {
    echo sprintf(
      " <li%s>%s (%d)\n",
      $first ? ' id="first_module"' : '',
      htmlspecialchars($module, ENT_QUOTES),
      count($rooms, COUNT_RECURSIVE) - count($rooms)
    );
    echo '  <ul>', "\n";

    $first = false;

    foreach ($rooms as $room => $players) {
      echo sprintf(
        "   <li>%s (%d)\n",
        htmlspecialchars($room, ENT_QUOTES),
        count($players)
      );
      echo '    <ul>', "\n";

      foreach ($players as $player) {
        echo '     <li>', htmlspecialchars($player, ENT_QUOTES), "</li>\n";
      }

      echo '    </ul>', "\n";
      echo '   </li>', "\n";
    }

    echo '  </ul>', "\n";
    echo ' </li>', "\n";
  }
}
catch (Exception $e) {
  echo sprintf("            <li>Exception: %s</li>\n", $e->getMessage());
}
?>

              </ul>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

{% endblock %}
