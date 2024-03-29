<?php
/*
 * QuaaxTM is an implementation of PHPTMAPI which uses MySQL with InnoDB as 
 * storage engine.
 * 
 * Copyright (C) 2009 Johannes Schmidt <joschmidt@users.sourceforge.net>
 * 
 * This library is free software; you can redistribute it and/or modify it under the 
 * terms of the GNU Lesser General Public License as published by the Free Software 
 * Foundation; either version 3 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 * See the GNU Lesser General Public License for more details.
 *  
 * You should have received a copy of the GNU Lesser General Public License along with this 
 * library; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, 
 * Boston, MA 02111-1307 USA
 */

/**
 * Wraps access to MySQL via mysqli.
 *
 * @package utils
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class Mysql {
	
  private $sql,
          $result,
          $errno,
          $error,
          $connection,
          $trnx,
          $delayTrnx,
          $commit,
          $connOpen;
	
  /**
   * Constructor.
   * 
   * @param array Configuration data.
   * @return void
   */
  public function __construct(array $config) {
    $this->sql = 
    $this->error = '';
    $this->errno = 0;
    $this->connection = null;
    $this->result = 
    $this->commit = 
    $this->trnx = 
    $this->delayTrnx = false;
    
    $this->connect($config);
  }

  /**
   * Connects to MySQL.
   * 
   * @param array Configuration data.
   * @return void
   * @throws RuntimeException If the connect fails.
   */
  private function connect(array $config) {
    $this->connection = mysqli_connect(
      $config['db']['host'], 
      $config['db']['user'], 
      $config['db']['pass'], 
      $config['db']['name'], 
      $config['db']['port']
    );
    $error = mysqli_connect_error();
    if (!empty($error)) {
      throw new RuntimeException(__METHOD__ . ': ' . $error);
    }
    $this->connOpen = true;
  }
  
  /**
   * Gets the current MySQL connection.
   * 
   * @return mysqli
   */
  public function getConnection() {
    return $this->connection;
  }
	
  /**
   * Closes a connection to MySQL.
   * 
   * @return void
   */
  public function close() {
    if (mysqli_close($this->connection)) {
      $this->connOpen = false;
    }
  }
  
  /**
   * Checks if a connection to MySQL is established.
   * 
   * @return boolean
   */
  public function isConnected() {
    return $this->connOpen;
  }

  /**
   * Executes a query.
   * 
   * @param string The SQL query.
   * @return MysqlResult|false
   */
  public function execute($query) {
    if (!$this->connOpen) {
      return false;
    }
    $this->sql = $query;
    $this->result = mysqli_query($this->connection, $this->sql);
    if ($this->result) {
      return new MysqlResult($this->result, $this->connection);
    } else {
      $this->errno = mysqli_errno($this->connection);
      $this->error = mysqli_error($this->connection);
      if ($this->trnx) {
        $this->commit = false;
      }
      return false;
    }
  }

  /**
   * Tells if an error occurred.
   * 
   * @return boolean
   */
  public function hasError() {
    return empty($this->error) ? false : true;
  }
 
  /**
   * Gets the error message.
   * 
   * @return string
   */			
  public function getError() {
    if ($this->hasError()) {
      $msg  = 'Query: ' . $this->sql . "\n";
      $msg .= 'Response: ' . $this->error . "\n";
      $msg .= 'Error code: ' . $this->errno;
      return $msg;
    } else {
      return 'No error.';
    }
  }
  
  /**
   * Starts a transaction.
   * 
   * @param boolean True if transaction is delayed, false if not. Default false.
   * @return void
   * @throws RuntimeException If the transaction can not be started.
   */
  public function startTransaction($delay=false) {
    if (!$this->trnx) {
      $this->delayTrnx = $delay;
      $result = $this->execute('START TRANSACTION');
      if (!$result) {
        throw new RuntimeException($this->getError());
      }
      $this->trnx = 
      $this->commit = true;
    }
  }
  
  /**
   * Finishes a transaction.
   * 
   * @param boolean True if finish is forced, false if not. Default false.
   * @return void
   * @throws RuntimeException If the transaction can not be finished.
   */
  public function finishTransaction($forced=false) {
    if ($forced) {
      $this->delayTrnx = false;
    }
    if (!$this->delayTrnx) {
      if ($this->commit) {
        $result = $this->execute('COMMIT');
        if (!$result) {
          throw new RuntimeException($this->getError());
        }
        $this->trnx = false;
      } else {
        $result = $this->execute('ROLLBACK');
        if (!$result) {
          throw new RuntimeException($this->getError());
        }
      }
    }
  }
}
?>