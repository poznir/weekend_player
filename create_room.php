<?
require_once "startup.php";
$error_message = "";
if (!$Users->is_auth()) {
  header('Location: login.php');
  die();
}

if (isset($_POST["todo"])) {
  if ($_SESSION["vitamin_create_room"] != $_POST["vitamin_create_room"]) {
    $error_message = "something went wrong. try again.";
  } else {
    $name = $_POST["name"];
    if ($Rooms->create_room($name, $Users->get_auth_email())) {
       header('Location: index.php');
       die();
    } else {
      $error_message = "invalid room name or already in use.";
    }
  }
}

$vitamin_create_room = md5($config_secret_key . microtime() . mt_rand(5,15));
$_SESSION["vitamin_create_room"] = $vitamin_create_room;

require("header.php");
?>
<div class="container">
  <h1>Create new room</h1>

  <?if ($error_message != "") { ?><div><h3>Error: <?=$error_message?><h3></div><? } ?>
  <form class="form-signin" role="form" method="POST" name="weekend_youtube_player">
    <h2 class="form-signin-heading">Room name</h2>
    <input type="hidden" name="todo" value="create">
    <input type="hidden" name="vitamin_create_room" value="<?=$vitamin_create_room?>">
    <input name="name" type="text" class="form-control" required>
    <button class="btn btn-lg btn-primary btn-block" type="submit">Create</button>
  </form>
</div>
<?
require("footer.php");
?>
