<?
require_once("class/room.php");

class Rooms {
  private $db;
  function __construct($_db) {
    $this->db = $_db;
  }

  function get_list() {
    $result = $this->db->query("select id from weekendv2_rooms");
    if (!$result) {
      return false;
    }
    $list = array();
    while ($row = $this->db->fetch($result)) {
      $list[] = new Room($this->db, $row['id']);
    }

    return $list;
  }

  function is_legal_name($name) {
    return !preg_match('/[^-A-Za-z0-9_ א-תץךףם]/', $name);
  }

  function get_room($id) {
    return new Room($this->db, $id);
  }

  function room_exists_by_id($id) {
    $safe_id = $this->db->safe($id);
    $result = $this->db->query("select count(*) as cc from weekendv2_rooms where id='$safe_id' limit 1");
    if (!$result) {
      return false;
    }
    $row = $this->db->fetch($result);
    return ($row['cc'] == 1 ? true : false);
  }

  function room_exists($name) {
    $safe_name = $this->db->safe($name);
    $result = $this->db->query("select count(*) as cc from weekendv2_rooms where name='$safe_name' limit 1");
    if (!$result) {
      return false;
    }
    $row = $this->db->fetch($result);
    return ($row['cc'] == 1 ? true : false);
  }

  function create_room($name, $email) {
    if (!$this->is_legal_name($name)) {
      return false;
    }
    if ($this->room_exists($name)) {
      return false;
    }
    $safe_name = $this->db->safe($name);
    $safe_email = $this->db->safe($email);
    $result = $this->db->query("insert into weekendv2_rooms (owner_email, name) values ('$safe_email','$safe_name')");
    return $result;
  }

}
?>