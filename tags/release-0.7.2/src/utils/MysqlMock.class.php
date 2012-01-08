<?php
/*
 * QuaaxTM is an implementation of PHPTMAPI which uses MySQL with InnoDB as 
 * storage engine.
 * 
 * Copyright (C) 2011 Johannes Schmidt <joschmidt@users.sourceforge.net>
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
 * Mocks parent class Mysql and allows analysis of the different result cache states 
 * via public class members.
 *
 * @package utils
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class MysqlMock extends Mysql
{ 
  /**
   * The indicator if a query result was successfully returned from memcached.
   * 
   * @var boolean
   */
  public $memcachedWasCalledSuccessfully;
  
  /**
   * The indicator if memcached was ignored when fetching a query result.
   * 
   * @var boolean
   */
  public $memcachedWasIgnored;
  
  /**
   * The indicator if a query result was added to memcached.
   * 
   * @var boolean
   */
  public $memcachedWasSet;
  
  /**
   * The memcached keys set in runtime.
   * 
   * @var array
   */
  protected $_memcachedKeys;
  
  /**
   * The salt string to protect existing memcached keys.
   * 
   * @var string
   * @static
   */
  protected static $_salt = '694de4723d3ac90edad3378630f8deae8ebbffb3';
  
  /**
   * The constructor.
   * 
   * @see Mysql::__construct()
   */
  public function __construct(array $config, $enableResultCache=false)
  {
    parent::__construct($config, $enableResultCache);
    $this->memcachedWasCalledSuccessfully = 
    $this->memcachedWasSet = false;
    $this->memcachedWasIgnored = true;
    $this->_memcachedKeys = array();
  }
  
  /**
   * Resets the runtime filled result cache: Deletes all data for the 
   * runtime registered keys.
   * 
   * @return void
   */
  public function resetRuntimeUsedResultCache()
  {
    foreach ($this->_memcachedKeys as $index=>$key) {
      $this->_memcached->delete($key);
    }
  }
  
  /**
   * Overrides Mysql::_get() and further provides registration of the different 
   * memcached states to allow detailed testing.
   * 
   * @see Mysql::_get()
   * @override
   */
  protected function _get($query, $resultCacheAllowed, $fetchOne)
  {
    if ($this->_resultCacheEnabled && $resultCacheAllowed) {
      $this->memcachedWasIgnored = false;
      $key = md5(self::$_salt . $query);// add $_salt to protect possibly existing keys
      $this->_memcachedKeys[$key] = $key;
      $results = $this->_memcached->get($key);
      if ($results !== false) {
        $this->memcachedWasCalledSuccessfully = true;
        $this->memcachedWasSet = false;
        return $results;
      }
      $this->memcachedWasCalledSuccessfully = false;
      $results = $this->_fetchAssociated($query, $fetchOne);
      if ($results !== false) {
        if (!empty($results)) {
          $this->_memcached->set($key, $results, $this->_resultCacheExpiration);
          $this->memcachedWasSet = true;
        }
        return $results;
      }
      return false;
    }
    $this->memcachedWasIgnored = true;
    return $this->_fetchAssociated($query, $fetchOne);
  }
}
?>