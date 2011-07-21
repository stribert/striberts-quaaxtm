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
 * Wraps access to MySQL via mysqli and provides a result cache layer using memcached.
 *
 * @package utils
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class Mysql
{	
  private $_sql,
          $_result,
          $_errno,
          $_error,
          $_connection,
          $_trnx,
          $_delayTrnx,
          $_commit,
          $_connOpen,
          $_memcached;
	
  /**
   * Constructor.
   * 
   * @param array Configuration data.
   * @param boolean Enable or disable the result cache. Default <var>false</var>.
   * @return void
   */
  public function __construct(array $config, $enableResultCache=false)
  {
    $this->_sql = 
    $this->_error = '';
    $this->_errno = 0;
    $this->_connection = 
    $this->_memcached = null;
    $this->_result = 
    $this->_commit = 
    $this->_trnx = 
    $this->_delayTrnx = false;
    
    $this->_connect($config, $enableResultCache);
  }

  /**
   * Connects to MySQL.
   * 
   * @param array Configuration data.
   * @param boolean Enable or disable the result cache.
   * @return void
   * @throws RuntimeException If the connection to MySQL or memcached fails, or if the 
   * 				configured memcached server cannot be added to the memcached server pool.
   * @throws Exception If PHP memcached support using <var>libmemcached</var> is not available.
   */
  private function _connect($config, $enableResultCache)
  {
    $this->_connection = mysqli_connect(
      $config['db']['host'], 
      $config['db']['user'], 
      $config['db']['pass'], 
      $config['db']['name'], 
      $config['db']['port']
    );
    $error = mysqli_connect_error();
    if (!empty($error)) {
      throw new RuntimeException('Error in ' . __METHOD__ . ': ' . $error);
    }
    $this->connOpen = true;
    
    if ($enableResultCache) {
      if (!class_exists('Memcached')) {
        throw new Exception(
          'Error in ' . __METHOD__ . ': PHP memcached support using libmemcached is not available.'
        );
      }
      $memcached = new Memcached();
      if (!$memcached->addServer($config['memcached']['host'], $config['memcached']['port'])) {
        throw new RuntimeException(
          'Error in ' . __METHOD__ . ': Cannot add server ' . 
            $config['memcached']['host'] . ':' . $config['memcached']['port'] . 
            	' to memcached server pool.'
        );
      }
      $memcachedStats = $memcached->getStats();
      $key = $config['memcached']['host'] . ':' . $config['memcached']['port'];
      if (!isset($memcachedStats[$key]) || empty($memcachedStats[$key])) {
        throw new RuntimeException(
          'Error in ' . __METHOD__ . ': Memcached at ' . 
            $config['memcached']['host'] . ':' . $config['memcached']['port'] . 
            	' is not available.'
        );
      }
      $this->_memcached = $memcached;
    }
  }
  
  /**
   * Gets the current MySQL connection.
   * 
   * @return mysqli
   */
  public function getConnection()
  {
    return $this->_connection;
  }
	
  /**
   * Closes a connection to MySQL.
   * 
   * @return void
   */
  public function close()
  {
    if (mysqli_close($this->_connection)) {
      $this->connOpen = false;
    }
  }
  
  /**
   * Gets the MySQL connection status.
   * 
   * @return boolean
   */
  public function isConnected()
  {
    return $this->connOpen;
  }

  /**
   * Executes a query.
   * 
   * @param string The SQL query.
   * @return MysqlResult|false
   */
  public function execute($query)
  {
    if (!$this->connOpen) {
      return false;
    }
    $this->_sql = $query;
    $this->_result = mysqli_query($this->_connection, $this->_sql);
    if ($this->_result) {
      return new MysqlResult($this->_result, $this->_connection);
    } else {
      $this->_errno = mysqli_errno($this->_connection);
      $this->_error = mysqli_error($this->_connection);
      if ($this->_trnx) {
        $this->_commit = false;
      }
      return false;
    }
  }

  /**
   * Tells if an error occurred.
   * 
   * @return boolean
   */
  public function hasError()
  {
    return !empty($this->_error);
  }
 
  /**
   * Gets the error message or <var>false</var> if no error occurred.
   * 
   * @return string|false The error message or <var>false</var> if no error occurred.
   */			
  public function getError()
  {
    if (!$this->hasError()) {
      return false;
    } else {
      $msg  = 'Query: ' . $this->_sql . "\n";
      $msg .= 'Response: ' . $this->_error . "\n";
      $msg .= 'Error code: ' . $this->_errno;
      return $msg;
    }
  }
  
  /**
   * Starts a transaction.
   * 
   * @param boolean True if transaction is delayed, false if not. Default false.
   * @return void
   * @throws RuntimeException If the transaction can not be started.
   */
  public function startTransaction($delay=false)
  {
    if (!$this->_trnx) {
      $this->_delayTrnx = $delay;
      $result = $this->execute('START TRANSACTION');
      if (!$result) {
        throw new RuntimeException($this->getError());
      }
      $this->_trnx = 
      $this->_commit = true;
    }
  }
  
  /**
   * Finishes a transaction.
   * 
   * @param boolean True if finish is forced, false if not. Default false.
   * @return void
   * @throws RuntimeException If the transaction can not be finished.
   */
  public function finishTransaction($forced=false)
  {
    if ($forced) {
      $this->_delayTrnx = false;
    }
    if (!$this->_delayTrnx) {
      if ($this->_commit) {
        $result = $this->execute('COMMIT');
        if (!$result) {
          throw new RuntimeException($this->getError());
        }
        $this->_trnx = false;
      } else {
        $result = $this->execute('ROLLBACK');
        if (!$result) {
          throw new RuntimeException($this->getError());
        }
      }
    }
  }
  
  /**
   * Fetches a query result either from MySQL or the memcached based result cache.
   * The result cache is taken into account if memcached is enabled and available - and 
   * permission is given.
   * 
   * @param string The SQL query.
   * @param boolean Permission to use the result cache or not. Default <var>false</var>.
   * @param int Result cache expiration in seconds. Default <var>60</var>.
   * @return array|false The query result as <var>associative array</var> or <var>false</var> 
   * 				on error.
   */
  public function fetch($query, $resultCachePermission=false, $resultCacheExp=60)
  {
    if ($this->_memcached instanceof Memcached && $resultCachePermission) {
      $key = md5($query);
      $result = $this->_memcached->get($key);
      if ($result !== false) {
        return $result;
      } else {
        $result = $this->_fetchAssociated($query);
        if ($result !== false) {
          if (!empty($result)) {
            $this->_memcached->set($key, $result, $resultCacheExp);
          }
          return $result;
        }
        return false;
      }
    } else {
      $result = $this->_fetchAssociated($query);
      return $result !== false ? $result : false;
    }
  }
  
  /**
   * Fetches a query result from MySQL.
   * 
   * @param string The SQL query.
   * @return array|false The query result as <var>associative array</var> or <var>false</var> 
   * 				on error.
   */
  private function _fetchAssociated($query)
  {
    $mysqlResult = mysqli_query($this->_connection, $query);
    if (!$mysqlResult) {
      $this->_errno = mysqli_errno($this->_connection);
      $this->_error = mysqli_error($this->_connection);
      return false;
    }
    $data = array();
    while ($result = mysqli_fetch_assoc($mysqlResult)) {
      $data[] = $result;
    }
    mysqli_free_result($mysqlResult);
    return $data;
  }
}
?>