<?php
/**
 * PrefSQL Class
 * This class intends to add some useful stuff to mysqli
 * @package Pref-PHP
 * @subpackage PrefSQL
 * @author Vincent
 * @link http://github.com/zakora/Pref-PHP
 */

/*
 * NOTES/BEHAVIOURS:
 * - `display_errors' must be set to Off in php.ini, otherwise errors will be
 * displayed anyway
 * - If an error or an exception is encounter then the die() function is invoked
 */

/*
 * TODOs :
 * - var `show_errors' = True in constructor means _every_ fun should display
 * errors
 * - var `force_errors' = True : bypass $show_errors (if False)
 */

class PrefSql extends mysqli {

     /*
      * +-----------------------+
      * | Constructor functions |
      * +-----------------------+
      */
     
     /**
      * Construct a new PrefSql instance and init a new connection
      *
      * @param host         Host to connect to
      * @param login        Login used to connect to the server
      * @param password     Password used to conncect to the server
      * @param database     Database to select on the server
      * @param show_errors  Choice for displaying error
      * @throws Exception   If an error occured while connecting to the server
      */
     public function __construct(
	  $host, $login, $password, $database, $show_errors=False) {
	  // If $show_errors is set to False, no errors will be displayed
	  if(!$show_errors) { 
	       parent::__construct($host, $login, $password,
					      $database);
	  }
	  // Else $show_errors is set to True, errors will be displayed
	  else {
	       // Trying to connect
	       try {
		    $this->construct_exception(
			 $host, $login, $password, $database);
	       } // And catching an exception if there is one
	       catch (Exception $e) {
		    print($this->display_msg_error(
			       'Error while connecting to MySQL',
			       $e->getMessage()));
		    // If there is an error then stop all next processes
		    die();
	       }
	  }
     }

     /**
      * Try to connect to a database and throw an exception if an error occured
      *
      * @param host        Host to connect to
      * @param login       Login used to connect to the server
      * @param password    Password used to conncect to the server
      * @param database    Database to select on the server
      * @throws Exception  mysqli_connect_error() message
      * @return            mysqli object
      */
     private function construct_exception($host, $login, $password, $database) {
	  $try_connect = parent::__construct($host, $login, $password, $database);
	  // If there is an error while trying to connect
	  if (mysqli_connect_errno()) {
	       throw new Exception(mysqli_connect_error());
	  }
	  // Else, no error, returning a mysqli object
	  else {
	       return $try_connect;
	  }
     }


     /*
      * +-----------------+
      * | Query functions |
      * +-----------------+
      */

     /**
      * Query and SQL request to the database
      *
      * @param req          SQL request
      * @param show_errors  Choice for displaying errors
      * @throws Exception   SQL error message
      * @return             mysqli query object
      */
     public function query($req, $show_errors=False) {
	  // Storing the query
	  $query = parent::query($req);
	  // If no error or errors are disabled
	  if($query != False OR $show_errors == False) {
	       return $query;
	  }
	  // Else, there is an error
	  else {
	       try { // Trying the query
		    $this->query_throw_exception($req);
	       } // And catching the exception
	       catch (Exception $e) {
		    print($this->display_msg_error('Error while querying',
						 $e->getMessage()));
		    // Stop all next processes
		    die();
	       }
	  }
     }

     /**
      * Throws an exception wether the request is bad or the error is upstream
      *
      * @param req         SQL request
      * @throws Exception  If it is a query error
      * @throws Exception  If it is not a query error
      */
     private function query_throw_exception($req) {
	  // If the error is in the query
	  // TODO: don't know why `isset($this->error)` doesn't work
	  if($this->error != NULL) {
	       throw new Exception('<strong>#'.$this->errno.'</strong> '.
				   $this->error);
	  }
	  // If not, it might be an upstream error
	  // TODO: it is possible to have both an query error and a serv error?
	  // cause the 'else' stands for... else.
	  else {
	       throw new Exception(
		    'Error <strong>might not be (only)</strong> in
                     the query, probably it is upstream.');
	  }
     }

     
     /*
      * +--------------------+
      * | Non-core functions |
      * +--------------------+
      */

     /**
      * Template for displaying error message
      *
      * @param title  Message title
      * @param msg    Message content
      * @return       (X)HTML message
      */
     private function display_msg_error($title, $msg) {
	  $resul = '<h3>PrefSql<h3>
                    <h4>'.$title.'</h4>
                    <p>The error is: <em>'.$msg.'</em></p>';
	  return $resul;
     }
}
?>
