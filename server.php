<?php
require_once "startup.php";
if (!$Users->is_auth()) {
  header('Location: login.php');
  die();
}

// because we hang the connection we need to write and close the session file in order to allow other requests to be made mean while..
session_write_close();

$room_id = $Rooms->clean_variable($_POST["id"]);
$update_version = $Rooms->clean_variable((isset($_POST["update_version"]) ? $_POST["update_version"] : ""));
$task = $Rooms->clean_variable($_POST["task"]);
$kind = $Rooms->clean_variable((isset($_POST["kind"]) ? $_POST["kind"] : ""));

if (!$Rooms->room_exists_by_id($room_id)) {
   header('Location: index.php');
   die();
}

if ($task == "report") {
  // for room admin only
  $room = $Rooms->get_room($room_id);
  if ($room->get_owner_email() != $Users->get_auth_email()) {
    die("access denied");
  }
  switch ($kind) {
    case 'player_error':
      $Playlist->set_item_report($room->get_currently_playing_id(), $Rooms->clean_variable($_POST["reason"]));
      $room->set_next_song($Playlist);
      break;
    case 'player_end':
      $Playlist->set_item_report($room->get_currently_playing_id(), "played");
      $room->set_next_song($Playlist);
      break;
  }
  send_data((object)["room_id" => $room_id]);
}

if ($task == "chat") {
  $room = $Rooms->get_room($room_id);
  switch ($kind) {
    case 'add':
      $text = $_POST["text"];
      $Chat->add($text);
      break;
    case 'delete':
      break;
  }
}

if ($task == "client") {
  $result = false;
  if ($kind == "vote") {
    $room = $Rooms->get_room($room_id);
    $video_id = $Rooms->clean_variable($_POST["video_id"]);
    $vote = $Rooms->clean_variable($_POST["vote"]);
    $Playlist->vote_item($video_id, $vote, $Users->get_auth_email());
    $result = true;
    $room->generate_update_version();
  }

  if ($kind == "remove") {
    $room = $Rooms->get_room($room_id);
    $video_id = $Rooms->clean_variable($_POST["video_id"]);
    $Playlist->remove_item($video_id);
    $result = true;
    $room->generate_update_version();
  }

  if ($kind == "add") {
    $room = $Rooms->get_room($room_id);
    $video_id = $Rooms->clean_variable($_POST["video_id"]);
    if (!$Playlist->is_already_last_in_playlist($room_id, $video_id)) {
      if ($Playlist->fetch_youtube_video_and_add($room_id, $video_id, $Users->get_auth_email())) {
        // added
        if ($room->check_if_should_skip()) {
          $room->set_next_song($Playlist);
        } else {
          $room->generate_update_version();
        } // if
        $result = true;
      } // if
    } // if
  } // if

  if ($kind == "update_volume") {
    $room = $Rooms->get_room($room_id);
    $volume = $Rooms->clean_variable($_POST["volume"]);
    if (is_numeric($volume) && $volume >= 0 && $volume <= 100) {
      $room->set_admin_volume($volume);
      $room->generate_update_version();
      $result = true;
    }
  }

  if ($kind == "update_radio") {
    $room = $Rooms->get_room($room_id);
    $radio = $Rooms->clean_variable($_POST["radio"]);
    if (is_numeric($radio) && ($radio == 0 || $radio == 1)) {
      $original_radio_state = $room->get_admin_random_radio();
      $room->set_admin_random_radio($radio);
      if ($room->get_playlist_next_song() === false && $original_radio_state == "0") {
          // TODO:
          // this can cause sometimes the last playing song to be cut and move to rnadom song,
          // but it's ok for now.. will be fixed later on
          $room->set_next_song($Playlist);
      } else {
        $room->generate_update_version();
      }
      $result = true;
    }
  }

  send_data((object)[
    "room_id" => $room_id,
    "result" => $result
  ]);
}

$start_time = time();

function new_playlist_data($room_id, $update_version) {
  global $db;
  $safe_update_version = $db->safe($update_version);
  $result = $db->query("select true from weekendv2_rooms where id='{$room_id}' AND update_version != '$safe_update_version' limit 1");
  if (!$result || !$db->fetch($result)) {
    return false;
  }
  return true;
}

function fetch_data($room) {
  global $db;
  $data = array(
    "update_version" => $room->get_update_version(),
    "currently_playing_id" => $room->get_currently_playing_id(),
    "playlist" => $room->get_playlist(),
    "history" => $room->get_history(),
    "chat" => $room->get_chat(),
    "members" => get_members_list($room),
    "admin_volume" => get_admin_volume($room),
    "admin_radio" => get_admin_radio($room),
  );
  return $data;
}

function is_timeout($start_time, $max_margin) {
  if ((time() - $start_time) > $max_margin) {
    return true;
  }
  return false;
}

function send_data($data) {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data);
  die();
}

function update_member_flag($room) {
  global $Users;
  $room->flag_active_member($Users->get_auth_email());
}

function get_members_list($room) {
  global $config_server_poll_max_executing_time;
  return $room->get_members($config_server_poll_max_executing_time);
}

function get_admin_volume($room) {
  return $room->get_admin_volume();
}

function get_admin_radio($room) {
  return $room->get_admin_random_radio();
}

$room = $Rooms->get_room($room_id);
update_member_flag($room); // add user to the room members list

while (!is_timeout($start_time, $config_server_poll_max_executing_time)) {
  if (new_playlist_data($room_id, $update_version)) {
    $data = fetch_data($room);
    send_data((object)[
      "timeout" => false,
      "room_id" => $room_id,
      "update_version" => $data["update_version"],
      "currently_playing_id" => $data["currently_playing_id"],
      "playlist" => $data["playlist"],
      "history" => $data["history"],
      "chat" => $data["chat"],
      "members" => $data["members"],
      "admin_volume" => $data["admin_volume"],
      "admin_radio" => $data["admin_radio"]
    ]);
  }
  usleep(1000);
}

//timeout data:
send_data((object)[
  "timeout" => true,
  "room_id" => $room_id,
  "members" => get_members_list($room)
]);
?>
