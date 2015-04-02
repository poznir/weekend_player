<?php
class Chat {
  function __construct($db) {
    $this->db = $db;
  }

  function add($text, $room_id, $user_id) {
    $result = $this->db->query("INSERT INTO  weekendv2_chat SET room_id = ?, user_id = ?, text = ?", [$room_id, $user_id, $text]);
  }
}
?>
