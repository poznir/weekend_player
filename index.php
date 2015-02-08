<?
require_once "startup.php";
$error_message = "";
if (!$Users->is_auth()) {
  header('Location: login.php');
  die();
}

require("header.php");
?>
<div class="container">
  <h1>Virtual rooms list</h1>
  <h4><a href="create_room.php">Create new room</a></h4>
  <?

  $rooms_list = $Rooms->get_list();
  echo "<h1>";
  foreach($rooms_list as $room) {
    ?><a href="room.php?id=<?=$room->get_id()?>"><span class="label label-primary"><?=$room->get_name()?> (Admin: <?=$room->get_owner_name()?>)</span></a><?
      echo "<br /><br />";
  }
  echo "</h1>";
  ?>
</div>
<?
require("footer.php");
?>
