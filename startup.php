<?
require_once("config.php");
require_once("class/db.php");
require_once("class/users.php");
require_once("class/rooms.php");
require_once("class/playlist.php");
$db = new mydb($conf_db_host, $conf_db_user, $conf_db_pass, $conf_db_name) or die("cant connect to db.");
$Users = new Users($db);
$Rooms = new Rooms($db);
$Playlist = new Playlist($db);

session_start();

?>