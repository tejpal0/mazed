<?php

  require 'rb.php'; // ORM redbean
  $THRESHOLD_FOR_DISTANCE = 500; // threshold distance for considering nearby (in kms)

  /* shuffling the grid and calculating the min steps required */
  function shuffle_grid($array){
    $len = count($array);
    $steps = 0;
    for ($idx = mt_rand(10,20); $idx >= 0; $idx--){
      $j = mt_rand(0,10000) % (9);
      $i = mt_rand(0,10000) % (9);
      $temp = $array[$i];
      $array[$i] = $array[$j];
      $array[$j] = $temp;
      $steps++;
    }
    return array($steps, $array);
  }

  /* Returns the approx. distance in kms */
  function calc_distance($user1, $user2){
    $lat1 = $user1->latitude;
    $lng1 = $user1->longitude;
    $lat2 = $user2->latitude;
    $lng2 = $user2->longitude;
    $radius = 6371.0;
    $phi1 = deg2rad($lat1);
    $phi2 = deg2rad($lat2);
    $del_phi = deg2rad($lat2-$lat1);
    $del_gamma = deg2rad($lng2-$lng1);
    $val = pow(sin($del_phi/2.0),2) + cos($phi1)*cos($phi2)*pow(sin($del_gamma/2.0),2);
    $radians = 2.0 * atan2(sqrt($val), sqrt(1-$val));
    $distance = $radius * $radians;
    return $distance;
  }

  function print_grid($array){
    for($outer = 0; $outer<3; $outer++){
      $x = 1; while($x <= 32) { echo "&nbsp"; $x++; }
      echo "[";
      for($inner = 0; $inner<3; $inner++){
        if($inner == 2){
          echo "<b>" . $array[$outer*3+$inner] . "</b>";
        }
        else{
          echo "<b>" . $array[$outer*3+$inner] . "</b> &nbsp; &nbsp; &nbsp;";
        }

      }
      echo "]<br/>";
    }
  }

  // get the HTTP method, path and body of the request
  $method = $_SERVER['REQUEST_METHOD'];
  $url = $_SERVER['REQUEST_URI'];
  $uri_parts = explode('?', $url, 2);
  $uri_path_parts = explode('/', $uri_parts[0]);
  $user_id = $uri_path_parts[2];
  $game_request = $uri_path_parts[3];

  // connect to the mysql database
  R::setup('mysql:host=127.0.0.1;dbname=mazegame', 'root', '' );

  if($game_request == "new"){

    // fetching user details
    $user_data = R::findLast( 'users', ' user_id = ?', [$user_id]);
    if($user_data == NULL){
      echo "<h2>Error 400: Bad request</h2>Unregistered user. Please use a valid userid.";
    }
    else{
      echo "id: <b>" . $user_id . "</b><br/>";
      echo "Name: <b>" . $user_data->name . "</b><br/>";
      echo "Age: <b>" . $user_data->age . "</b><br/>";
      echo "gender: <b>" . $user_data->gender . "</b><br/>";
      echo "Latitude: <b>" . $user_data->latitude . "</b><br/>";
      echo "longitude: <b>" . $user_data->longitude . "</b><br/>";

      /*
      checking if its a running game from which new game is requested in middle,
      by checking if moves used is ZERO (as updation happens at the end of game, although it
      can be counted as a loss for the player, quitting the game in the middle). Moreover,
      previous played games by this user will be preserved as they will have some non-zero
      positive value of moves used.
      */
      $game_data = R::findLast( 'gamedata', ' user_id = ? and moves_used = ?', [$user_id, 0]);

      /*
        games which are currently being played will have zero moves as updation happens
        at the end of game.
      */
      $current_game_instances = R::find('gamedata', 'moves_used = ?', [0]);
      $nearby_users_grid = array(); // to store instances of game being currently played by nearby users
      foreach ($current_game_instances as &$id){
        $other_user = R::findLast('users', 'user_id = ?', [$id->user_id]);
        if(calc_distance($user_data, $other_user) < $THRESHOLD_FOR_DISTANCE){
          array_push($nearby_users_grid, json_decode($id->grid_data));
        }
      }
      $matched = true;
      $shuffled_data = NULL;
      while($matched){
        $shuffled_data = shuffle_grid([1,2,3,4,5,6,7,8,9]);
        $min_moves_req = $shuffled_data[0];
        $new_grid = $shuffled_data[1];
        $game_grid = array(array($new_grid[0], $new_grid[1], $new_grid[2]), array($new_grid[3], $new_grid[4], $new_grid[5]), array($new_grid[6], $new_grid[7], $new_grid[8]));
        for($i = 0; $i<count($nearby_users_grid); $i++){
          if($game_grid == $nearby_users_grid[$i]){
            continue;
          }
        }
        $matched = false;
      }
      if($game_data == NULL){
        $game_data = R::dispense('gamedata');
        $game_data->user_id = $user_id;
      }
      $game_data->grid_data = json_encode($shuffled_data[1]);
      $game_data->min_moves_req = $min_moves_req;
      $id = R::store($game_data);
      echo "New Arrangement <br/>";
      print_grid($shuffled_data[1]);
    }
  }
  else if($game_request == "submit"){
    $game_data = R::findLast( 'gamedata', ' user_id = ? and moves_used = ?', [$user_id, 0]);
    if($game_data == NULL){
      echo "<h2>Error 400: Bad request</h2>No game in progress by this user.";
    }
    else{
      $user_data = R::findLast( 'users', ' user_id = ?', [$user_id]);
      $moves_by_user = htmlspecialchars($_GET["moves"]);

      echo "user_id: <b>" . $user_id . "</b><br/>";
      echo "Name: <b>" . $user_data->name . "</b><br/>";
      echo "Age: <b>" . $user_data->age . "</b><br/>";
      echo "gender: <b>" . $user_data->gender . "</b><br/>";
      echo "Latitude: <b>" . $user_data->latitude . "</b><br/>";
      echo "longitude: <b>" . $user_data->longitude . "</b><br/>";
      echo "Initial Arrangement<br/>";
      print_grid(json_decode($game_data->grid_data));
      echo "<br/>";
      echo "Optimal Moves: <b>" . $game_data -> min_moves_req . "</b><br/>";
      echo "Moves Used: <b>" . $moves_by_user . "</b><br/>";

      // score calculation for current player
      if($moves_by_user < $game_data -> min_moves_req){
        $game_data -> score = 0.00; // a flag to denote invalid game play
        $game_data -> moves_used = -1;
        echo '<h2>Error 400: Bad Request</h2>Score: Invalid no. of moves by player in the request.';
      }
      else{
        $game_data -> moves_used = $moves_by_user;
        $game_data -> score = (floatval($game_data -> min_moves_req)/$moves_by_user) * 10.0;
        echo "Score: <b>" . $game_data -> score . "</b><br/>";
      }

      R::store($game_data); // saving the result of current game play
    }
  }
  else{
    echo "<h2>Error 404: Page Not Found</h2>";
    echo "Please refer the ReadMe (documentation) in the repository for usage of APIs.";
  }

  R::close();
?>
