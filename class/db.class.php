<?php



		class dbclass {
			private static $instance;
			private $connection;
			private $trans;

			private function __construct($conf_db_host, $conf_db_user, $conf_db_pass, $conf_db_name) {
				$dsn = "mysql:dbname=$conf_db_name;host=$conf_db_host";
				$this->connection = new PDO($dsn, $conf_db_user, $conf_db_pass);
			  	array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
				$this->connection->exec("SET CHARACTER SET utf8");
				$this->connection->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

			}

			public static function getInstance($conf_db_host, $conf_db_user, $conf_db_pass, $conf_db_name){
				if(empty(self::$instance)) {
					try {
							self::$instance = new dbclass($conf_db_host, $conf_db_user, $conf_db_pass, $conf_db_name);
						} catch (PDOException $e) {
							echo 'Connection Failed: ' . $e->getMessage();
						}
				}
				return self::$instance;
			}

			public function query($query,$args=NULL) {
				$tokens = explode(" ", $query);
				try {
					$sth = $this->connection->prepare($query);
					if (empty($args)) {
						$sth->execute();
					} else {
						$sth->execute($args);
					}

					if ($tokens[0] == 'SELECT') {
						$sth ->setFetchMode(PDO::FETCH_ASSOC);
						$results = $sth->fetchAll();
						return $results;
					}//end of select mode

				} catch(PDOException $e) {
					echo "Query Failed: " . $e->getMessage();
					echo "<br/> Query: " . $query;
				}//end of try
			}

			public function lastInsertId() {
				return $this->connection->lastInsertId();
			}

			function printTable($array,$datatable=1 ,$includes=array(),$excludes = array('href') ) {
				$class = $datatable ? 'class="datatable"' : NULL;
				if (!sizeof($array)) return 0;
				$output_table = NULL;
				$output_table .=  "<table $class>";

				//header
				$output_table .=  "<thead><tr>";
				foreach($array[0] as $k => $v) {
					if ($k=='href') continue;
					//if in excludes continue
						if (in_array($k,$excludes)) continue;

					//if not in includes continue
						if (!in_array($k,$includes) && sizeof($includes)) continue;

					$output_table .=  "<th>$k</th>";
				}
				$output_table .=  "</tr></thead>";

				//contents
				$output_table .=  "<tbody>";
				foreach ($array as $r) {
					if ($r['href']) $rowhead = "<tr href='{$r['href']}'>";
					else $rowhead = "<tr>" ;
					$output_table .=  $rowhead;
					foreach ($r as $k => $v) {
					//if in excludes continue
						if (in_array($k,$excludes)) continue;

					//if not in includes continue
						if (!in_array($k,$includes) && sizeof($includes)) continue;

						if ($k=='href') continue;
						$output_table .=  "<td>$v</td>";
					}
					$output_table .=  "</tr>";
				}
				$output_table .=  "</tbody>";

				$output_table .=  "</table>";
				return $output_table;
			}//end of printTable


		}//end of class

?>
