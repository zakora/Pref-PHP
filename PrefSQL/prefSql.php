<?php
/*
 * This class intends to add some useful stuff to mysqli
 */

/*
 * NOTES/BEHAVIOURS:
 * - `display_errors' must be set to Off in php.ini, otherwise errors will be
 * displayed anyway
 * - If an error or an exception is encounter then the die() function is invoked
 */

/*
 * TODOs :
 * - Better documention (style & content)
 * - var `show_errors' = True in constructor means _every_ fun should display
 * errors
 * - var `force_errors' = True : bypass $show_errors (if False)
 */

class PrefSql extends mysqli {
     // Common vars
     
     // Constructor
     public function __construct(
	  $host, $login, $password, $database, $show_errors=False) {
	  if(!$show_errors) { 
	       parent::__construct($host, $login, $password,
					      $database);
	  }
	  else {
	       try {
		    $this->construct_exception(
			 $host, $login, $password, $database);
	       }
	       catch (Exception $e) {
		    print($this->display_msg_error(
			       'Error while connecting to MySQL',
			       $e->getMessage()));
		    die();
	       }
	  }
     }

     private function construct_exception($host, $login, $password, $database) {
	  $try_connect = parent::__construct($host, $login, $password, $database);
	  if (mysqli_connect_errno()) {
	       throw new Exception(mysqli_connect_error());
	  }
	  else {
	       return $try_connect;
	  }
     }

     // Querying
     public function query($req, $show_errors=False) {
	  $baz = parent::query($req);
	  if($baz != False OR $show_errors == False) {
	       return $baz;
	  }
	  else {
	       try {
		    $this->query_throw_exception($req);
	       }
	       catch (Exception $e) {
		    print($this->display_msg_error('Error while querying',
						 $e->getMessage()));
		    die();
	       }
	  }
     }
     
     private function query_throw_exception($req) {
	  // If the error is in the query
	  // TODO: don't know why `isset($this->error)` doesn't work
	  if($this->error != NULL) {
	       throw new Exception('<strong>#'.$this->errno.'</strong> '.
				   $this->error);
	  }
	  // If not, maybe an upstream error
	  else {
	       throw new Exception(
		    'Error <strong>might not be (only)</strong> in
                     the query, probably it is upstream.');
	  }
     }

     /*
      * Non-core functions
      */
     private function display_msg_error($title, $msg) {
	  $resul = '<h3>PrefSql<h3>
                    <h4>'.$title.'</h4>
                    <p>The error is: <em>'.$msg.'</em></p>';
	  return $resul;
     }
}
?>
