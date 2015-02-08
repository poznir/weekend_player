<?
require_once "startup.php";
$error_message = "";
if ($Users->is_auth()) {
  header('Location: index.php');
  die();
}

if (isset($_POST["todo"])) {
  if ($_SESSION["vitamin"] != $_POST["vitamin"]) {
    $error_message = "something went wrong. try again.";
  } else {
    $email = $_POST["email"];
    $password = $_POST["password"];
    $name = $_POST["name"];
    switch ($_POST["todo"]) {
      case 'login':
        if ($Users->user_auth($email, $password)) {
          $Users->set_auth($email);
          header('Location: index.php');
          die();
        } else {
          $error_message = "something went wrong. try again.";
        }
      break;
      case 'register':
        if ($Users->user_register($email, $password, $name)) {
          $Users->set_auth($email);
          header('Location: index.php');
          die();
        } else {
          $error_message = "something went wrong. try again.";
        }
      break;
    }
  }
}

$vitamin = md5($config_secret_key . microtime() . mt_rand(5,15));
$_SESSION["vitamin"] = $vitamin;
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Weekend Player v2</title>
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="signin.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
  </head>
  <body>
    <div class="container">
      <?if ($error_message != "") { ?><div><h3>Error: <?=$error_message?><h3></div><? } ?>
      <form class="form-signin" role="form" method="POST" name="weekend_youtube_player">
        <h2 class="form-signin-heading">Weekend Player</h2>
        <input type="hidden" name="todo" value="">
        <input type="hidden" name="vitamin" value="<?=$vitamin?>">
        <input name="email" type="email" class="form-control" placeholder="Email address" required autofocus>
        <input name="password" type="password" class="form-control" placeholder="Password" required>
        <button class="btn btn-lg btn-primary btn-block" type="submit" onclick="$('input[name=name]')[0].value='#';$('input[name=todo]')[0].value='login'">Sign in</button>
        <h2 class="form-signin-heading">Or</h2>
        <input name="name" type="name" class="form-control" placeholder="Display Name (5-25)" required>
        <button class="btn btn-lg btn-primary btn-block" type="submit" onclick="$('input[name=todo]')[0].value='register'">Register</button>
      </form>

    </div>

  </body>
</html>
