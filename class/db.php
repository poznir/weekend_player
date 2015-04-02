<?
class mydb {
  private $con;
  function __construct($conf_db_host, $conf_db_user, $conf_db_pass, $conf_db_name) {
      $this->con=mysqli_connect($conf_db_host, $conf_db_user, $conf_db_pass, $conf_db_name);
      // Check connection
      if (mysqli_connect_errno()) {
        return false;
      }
      mysqli_query($this->con,"SET NAMES utf8");

      return true;
  }

  public function query($sql) {
    return mysqli_query($this->con, $sql);
  }

  public function fetch($result) {
    return mysqli_fetch_array($result,MYSQLI_ASSOC);
  }

  public function last_id() {
    return mysqli_insert_id($this->con);
  }

  public function safe($str) {
    return mysqli_real_escape_string($this->con, $str);
  }

  public function close() {
    mysqli_close($this->con);
  }

  public function get_user_id_by_email($email) {
    $safe_reason = $this->con->safe($email);
    $result = $this->con->query("SELECT id FROM weekendv2_users WHERE email='$email' LIMIT 1");
    $row = $this->con->fetch($result);
    return $row['id'];
  }
}

?>
