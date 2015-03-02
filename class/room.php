<?
class Room {
  private $id;
  private $owner_email;
  private $name;
  private $currently_playing_id;
  private $update_version;

  function __construct($db, $id) {
    $this->db = $db;
    $result = $this->db->query("select * from weekendv2_rooms where id='$id'");
    if (!$result) {
      return false;
    }
    if ($row = $this->db->fetch($result)) {
      $this->id = $row["id"];
      $this->owner_email = $row["owner_email"];
      $this->name = $row["name"];
      $this->currently_playing_id = $row["currently_playing_id"];
      $this->update_version = $row["update_version"];
    }
  }

  public function get_id() {
    return $this->id;
  }

  public function get_owner_email() {
    return $this->owner_email;
  }

  public function get_owner_name() {
    $result = $this->db->query("select name from weekendv2_users where email='{$this->owner_email}'");
    if (!$result) {
      return "";
    }
    if ($row = $this->db->fetch($result)) {
      return $row["name"];
    }
    return "";
  }

  public function get_name() {
    return $this->name;
  }

  public function get_currently_playing_id() {
    return $this->currently_playing_id;
  }

  public function get_update_version() {
    return $this->update_version;
  }

  public function get_admin_volume() {
    $result = $this->db->query("select admin_volume from weekendv2_rooms where id='{$this->get_id()}' limit 1");
    if (!$result) {
      return 0;
    }
    $row = $this->db->fetch($result);
    return $row["admin_volume"];
  }

  public function set_admin_volume($volume) {
    $this->db->query("update weekendv2_rooms set admin_volume='{$volume}' where id='{$this->get_id()}' limit 1");
  }

  public function get_admin_random_radio() {
    $result = $this->db->query("select admin_random_radio from weekendv2_rooms where id='{$this->get_id()}' limit 1");
    if (!$result) {
      return 0;
    }
    $row = $this->db->fetch($result);
    return $row["admin_random_radio"];
  }

  public function set_admin_random_radio($bool) {
    $bool = strval($bool);
    if ($bool == "1" || $bool == "0") {
      $this->db->query("update weekendv2_rooms set admin_random_radio='{$bool}' where id='{$this->get_id()}' limit 1");
    }
  }

  public function get_playlist() {
    $result = $this->db->query("select weekendv2_playlist.*,weekendv2_users.name as user_name from weekendv2_playlist left join weekendv2_users on (weekendv2_users.email=weekendv2_playlist.added_by_email) where weekendv2_playlist.room_id='{$this->get_id()}' AND weekendv2_playlist.id>='{$this->get_currently_playing_id()}' order by weekendv2_playlist.id");
    if (!$result) {
      return array();
    }
    $list = array();
    while ($row = $this->db->fetch($result)) {
      $list[] = $row;
    }

    return $list;
  }

  public function get_history() {
    $result = $this->db->query("select weekendv2_playlist.*,weekendv2_users.name as user_name from weekendv2_playlist left join weekendv2_users on (weekendv2_users.email=weekendv2_playlist.added_by_email) where weekendv2_playlist.room_id='{$this->get_id()}' AND weekendv2_playlist.id<'{$this->get_currently_playing_id()}' order by weekendv2_playlist.id desc limit 10");
    if (!$result) {
      return array();
    }
    $list = array();
    while ($row = $this->db->fetch($result)) {
      $list[] = $row;
    }
    $list = array_reverse($list);
    return $list;
  }

  public function get_members_count($max_executing_time) {
    $time_margin = 10;
    $total_margin = 0 - $max_executing_time - $time_margin;
    $result = $this->db->query("select count(*) as cc from weekendv2_room_members where weekendv2_room_members.room_id='{$this->get_id()}' and weekendv2_room_members.last_update >= TIMESTAMPADD(SECOND,{$total_margin},NOW())");
    if (!$result) {
      return 0;
    }
    $row = $this->db->fetch($result);
    return $row["cc"];
  }

  public function get_members($max_executing_time) {
    $time_margin = 10;
    $total_margin = 0 - $max_executing_time - $time_margin;
    // get current members:
    $result = $this->db->query("select weekendv2_room_members.member_email as member_email, TIMESTAMPDIFF(SECOND, weekendv2_room_members.last_update, NOW()) as last_update, weekendv2_users.name as member_name from weekendv2_room_members left join weekendv2_users on (weekendv2_users.email=weekendv2_room_members.member_email) where weekendv2_room_members.room_id='{$this->get_id()}' and weekendv2_room_members.last_update >= TIMESTAMPADD(SECOND,{$total_margin},NOW())");
    if (!$result) {
      return array();
    }
    $list = array();
    while ($row = $this->db->fetch($result)) {
      $list[] = $row;
    }
    return $list;
  }

  public function flag_active_member($user_email) {
    $this->db->query("INSERT INTO `weekendv2_room_members` (room_id, member_email) VALUES ('{$this->get_id()}','{$user_email}') ON DUPLICATE KEY UPDATE last_update=NOW()");
  }

  public function get_playlist_next_song() {
    $result = $this->db->query("select id from weekendv2_playlist where room_id='{$this->get_id()}' AND id > {$this->get_currently_playing_id()} LIMIT 1");
    if (!$result) {
      return false;
    }
    if ($row = $this->db->fetch($result)) {
      return $row["id"];
    }
    return false;
  }

  public function check_if_should_skip() {
    if ($this->get_currently_playing_id() == '0') {
	return true;
    }
    $sql = "SELECT `currently_playing_id` FROM  `weekendv2_rooms` INNER JOIN weekendv2_playlist ON weekendv2_playlist.id = weekendv2_rooms.currently_playing_id WHERE `weekendv2_rooms`.id='{$this->get_id()}' AND weekendv2_playlist.skip_reason IS NOT NULL";
    $result = $this->db->query($sql);
    if (!$result) {
      return false;
    }
    if (!$row = $this->db->fetch($result)) {
      return false;
    }
    return true;
  }

  public function get_random_song() {
    // get random song and it must be different then the last played one.
    $sql = "select id from weekendv2_playlist where room_id='{$this->get_id()}' AND copy='0' AND skip_reason='played' AND id < {$this->get_currently_playing_id()} ORDER BY RAND() LIMIT 1";
    $result = $this->db->query($sql);
    if (!$result) {
      return false;
    }
    if (!$row = $this->db->fetch($result)) {
      return false;
    }
    return $row["id"];
  }

  public function set_next_song($Playlist) {
    $next = $this->get_playlist_next_song();
    if ($next !== false) {
      $this->set_currently_playing_id($next);
      $this->generate_update_version();
    } else {
      if ($this->get_admin_random_radio() == "1") {
        // radio is on and list is empty
        $copy_id = $this->get_random_song();
        if ($copy_id) {
          $this->set_currently_playing_id($Playlist->add_copy($copy_id));
          $this->generate_update_version();
        }
      }
    }
  }

  public function set_currently_playing_id($value) {
    $this->db->query("update weekendv2_rooms SET currently_playing_id='{$value}' where id='{$this->get_id()}' LIMIT 1");
  }

  public function generate_update_version() {
    $update_version = md5(microtime() . mt_rand(101,9999999) );
    $this->db->query("update weekendv2_rooms SET update_version='{$update_version}' where id='{$this->get_id()}' LIMIT 1");
  }
}
?>
