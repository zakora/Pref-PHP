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
 * - Decide whether to call die() when encountering an error
 */

class PrefSql extends mysqli {

  // @var show_errors  Global setting for displaying errors
  private $show_errors;
  // Next are benchmarks vars
  // @var total_time  Total time of the queries during the session (in millisec)
  private $total_time = 0.0;
  // @var trace_req  Array with query info: req, time (millisec), error (if one)
  private $trace_req = array();
  // @var nb_req  Number of requests
  private $nb_req = 0;

  
  /*
   * +-----------------------+
   * | Constructor functions |
   * +-----------------------+
   */

  /**
   * Construct a new PrefSql instance and init a new connection
   *
   * @param host           Host to connect to
   * @param login          Login used to connect to the server
   * @param password       Password used to conncect to the server
   * @param database       Database to select on the server
   * @param show_errors    Global setting for displaying errors
   * @param force_showerr  Display errors for this function only,
   *                       even if show_errors is set to False
   * @throws Exception     If an error occured while connecting to the server
   */
  public function __construct($host, $login, $password, $database,
                              $show_errors=False, $force_showerr=False) {
    // If we don't want errors to be displayed
    if(!($show_errors OR $force_showerr)) {
      parent::__construct($host, $login, $password, $database);
      $this->show_errors = $show_errors;
    }
    // Else $show_errors is set to True, errors will be displayed
    else {
      // Trying to connect
      try {
        $this->construct_exception($host, $login, $password, $database,
                                   $show_errors);
      } // And catching an exception if there is one
      catch (Exception $e) {
        print($this->display_msg_error('Error while connecting to MySQL',
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
  private function construct_exception($host, $login, $password, $database,
                                       $show_errors) {
    $try_connect = parent::__construct($host, $login, $password, $database);
    $this->show_errors = $show_errors;
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
   * @param req            SQL request
   * @param force_showerr  Display errors for this function only,
   *                       even if show_errors is set to False
   * @throws Exception     SQL error message
   * @return               mysqli query object
   */
  public function query($req, $force_showerr=False) {
    // Executing and benchmarking the query
    $query = $this->bench_query($req);
    
    // If no error or errors are disabled
    if($query != False OR !($this->show_errors OR $force_showerr)) {
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
    if($this->error != NULL) {
      throw new Exception('(#'.$this->errno.') '.$this->error.'<br />
                          <em>Involved query</em>: '.$req);
    }
    // If not, it might be an upstream error
    else {
      throw new Exception(
        'Error <strong>might not be (only)</strong> in the query,
         probably it is upstream.');
    }
  }


  /*
   * +---------------------+
   * | Benchmark functions |
   * +---------------------+
   */

  /**
   * Benchmark for the query function
   *
   * @param req  SQL request
   * @return     The query (already executed)
   */
  private function bench_query($req) {
    // Starting the query clock
    $time_start = microtime(True);
    // Executing and storing the query
    $query = parent::query($req);
    // Stopping the query clock
    $query_time = microtime(True) - $time_start;
    // Converting query_time to millisec
    $query_time = $query_time * 1000;
    // Adding this query time to the total time
    $this->total_time += $query_time;
    // Storing this query info to trace_req
    $this->trace_req[$this->nb_req]['req'] = $req;
    $this->trace_req[$this->nb_req]['time'] = $query_time;
    $this->trace_req[$this->nb_req]['error'] = $this->error;
    // Incrementing nb_req
    $this->nb_req++;
    // Returning the executed query so the function query() can go on
    return $query;
  }

  /*** Benchmarks Getters ***/
  /** @param $precision  precision of round() */
  public function get_total_time($precision=3) {
    return round($this->total_time, $precision);
  }
  public function get_trace_req() {
    return $this->trace_req;
  }
  public function get_nb_req() {
    return $this->nb_req;
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
              <p><em>The error is</em>: '.$msg.'</p>';
    return $resul;
  }
}
?>
