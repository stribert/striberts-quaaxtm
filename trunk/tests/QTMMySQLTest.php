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

$utilPath = dirname(__FILE__) . 
  DIRECTORY_SEPARATOR . 
  '..' .  
  DIRECTORY_SEPARATOR . 
  'src' . 
  DIRECTORY_SEPARATOR . 
  'utils' . 
  DIRECTORY_SEPARATOR;
  
require_once($utilPath . 'Mysql.class.php');
require_once($utilPath . 'MysqlResult.class.php');

/**
 * QuaaxTM MySQL utils tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class QTMMySQLTest extends PHPUnit_Framework_TestCase
{
  private $_config,
          $_mysql;
  
  /**
   * @override
   */
  protected function setUp()
  {
    $config = array();
    require(
      dirname(__FILE__) . 
      DIRECTORY_SEPARATOR . 
      '..' . 
      DIRECTORY_SEPARATOR . 
      'src' . 
      DIRECTORY_SEPARATOR . 
      'phptmapi' . 
      DIRECTORY_SEPARATOR . 
      'config.php'
    );
    $this->_mysql = new Mysql($config);
    $this->_config = $config;
  }
  
  /**
   * @override
   */
  protected function tearDown()
  {
    unset($this->_mysql);
    unset($this->_config);
  }
  
  public function testTrnxFail()
  {
    $query = 'SELECT COUNT(*) FROM ' . $this->_config['table']['topicmap'];
    $mysqlResult = $this->_mysql->execute($query);
    $result = $mysqlResult->fetchArray();
    $preTmCount = $result[0];
    
    $query = 'SELECT COUNT(*) FROM ' . $this->_config['table']['topic'];
    $mysqlResult = $this->_mysql->execute($query);
    $result = $mysqlResult->fetchArray();
    $preTopicCount = $result[0];
    
    $this->_mysql->startTransaction();
    
    $query = 'INSERT INTO ' . $this->_config['table']['topicmap'] . 
      '(id, locator) VALUES (NULL, "http://localhost/tm1")';
    $mysqlResult = $this->_mysql->execute($query);
    $lastTmId = $mysqlResult->getLastId();
    
    // the column "topicmap_id_fail" does not exist
    $query = 'INSERT INTO ' . $this->_config['table']['topic'] . 
      '(id, topicmap_id_fail) VALUES (NULL, ' . $lastTmId . ')';
    $this->_mysql->execute($query);
    
    $this->_mysql->finishTransaction();
    
    $this->assertTrue($this->_mysql->hasError(), 'Expected a MySQL error!');
    $errorMsg = $this->_mysql->getError();
    $this->assertTrue(!empty($errorMsg), 'Expected an error message!');
    
    $query = 'SELECT COUNT(*) FROM ' . $this->_config['table']['topicmap'];
    $mysqlResult = $this->_mysql->execute($query);
    $result = $mysqlResult->fetchArray();
    $postTmCount = $result[0];
    
    $query = 'SELECT COUNT(*) FROM ' . $this->_config['table']['topic'];
    $mysqlResult = $this->_mysql->execute($query);
    $result = $mysqlResult->fetchArray();
    $postTopicCount = $result[0];
    
    $this->assertEquals($preTmCount, $postTmCount, 'Unexpected topic map!');
    $this->assertEquals($preTopicCount, $postTopicCount, 'Unexpected topic!');
  }
  
  public function testMysqlResult()
  {
    $query = 'SELECT id FROM ' . $this->_config['table']['topicmap'] . 
    	' WHERE locator = "fdgfd"';
    $mysqlResult = $this->_mysql->execute($query);
    $this->assertNull($mysqlResult->fetchArray());
    $this->assertNull($mysqlResult->fetch());
  }
  
  public function testMisc()
  {
    $this->assertFalse($this->_mysql->getError());
    $this->assertFalse($this->_mysql->hasError());
    $this->_mysql->close();
    $mysqlResult = $this->_mysql->execute('SELECT VERSION()');
    $this->assertFalse($mysqlResult);
  }
  
  public function testFetch()
  {
    $this->assertFalse($this->_mysql->getError());
    $this->assertFalse($this->_mysql->hasError());
    $query = 'SELECT';
    $results = $this->_mysql->fetch($query);
    $this->assertFalse($results);
    $error = $this->_mysql->getError();
    $this->assertFalse(empty($error));
    $this->assertTrue($this->_mysql->hasError());
  }
  
  public function testSetResultCacheExpiration()
  {
    $seconds = 12;
    $this->_mysql->setResultCacheExpiration($seconds);
    $this->assertEquals(
      $this->_mysql->getResultCacheExpiration(), 
      $seconds
    );
  }
}
?>