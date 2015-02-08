<?
class Users {
  private $db;
  function __construct($_db) {
    $this->db = $_db;
  }

  function user_exists($email) {
    $safe_email = $this->db->safe($email);
    $result = $this->db->query("select count(*) as cc from weekendv2_users where email='$safe_email' limit 1");
    if (!$result) {
      return false;
    }
    $row = $this->db->fetch($result);
    return ($row['cc'] == 1 ? true : false);
  }

  function user_name_exists($name) {
    $safe_name = $this->db->safe($name);
    $result = $this->db->query("select count(*) as cc from weekendv2_users where name='$safe_name' limit 1");
    if (!$result) {
      return false;
    }
    $row = $this->db->fetch($result);
    return ($row['cc'] == 1 ? true : false);
  }

  function user_auth($email, $pass) {
    $safe_email = $this->db->safe($email);
    $safe_pass =  $this->make_user_password($this->db->safe($pass));
    $result = $this->db->query("select count(*) as cc from weekendv2_users where email='$safe_email' AND password='$safe_pass' limit 1");
    if (!$result) {
      return false;
    }
    $row = $this->db->fetch($result);
    return ($row['cc'] == 1 ? true : false);
  }

  function is_legal_name($name) {
    return !preg_match('/[^-A-Za-z0-9_ ]/', $name);
  }

  function user_register($email, $pass, $name) {
    if ($this->user_exists($email)) {
      return false;
    }
    $name = trim($name);
    if (strlen($name) < 5 or strlen($name) > 25 or !$this->is_legal_name($name) or $this->user_name_exists($name)) {
      return false;
    }
    $safe_email = $this->db->safe($email);
    $safe_pass =  $this->make_user_password($this->db->safe($pass));
    $safe_name = $this->db->safe($name);
    $result = $this->db->query("insert into weekendv2_users (email,password, name) values ('$safe_email','$safe_pass', '$safe_name')");
    return $result;
  }

  function make_user_password($pass) {
    global $config_secret_key;
    return md5($config_secret_key . $pass);
  }

  function is_auth() {
    global $_SESSION;
    return (isset($_SESSION["auth"]) && $_SESSION["auth"] == true ? true : false);
  }

  function set_auth($email) {
    global $_SESSION;
    $_SESSION["auth"] = true;
    $_SESSION["auth_email"] = $email;
  }

  function unset_auth() {
    global $_SESSION;
    $_SESSION["auth"] = false;
    $_SESSION["auth_email"] = "";
  }

  function get_auth_email() {
    global $_SESSION;
    return $_SESSION["auth_email"];
  }
}
?>