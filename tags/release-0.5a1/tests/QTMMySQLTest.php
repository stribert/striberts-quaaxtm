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

$qtmPath = dirname(__FILE__) . 
  DIRECTORY_SEPARATOR . 
  '..';
$utilPath = $qtmPath . 
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
class QTMMySQLTest extends PHPUnit_Framework_TestCase {
  
  private $config,
          $mysql;
  
  public function setUp() {
    $config = array();
    require(
          dirname(__FILE__) . 
          DIRECTORY_SEPARATOR . 
          '..' . 
          DIRECTORY_SEPARATOR . 
          'lib' . 
          DIRECTORY_SEPARATOR . 
          'phptmapi2.0' . 
          DIRECTORY_SEPARATOR . 
          'config.php'
    );
    $this->mysql = new Mysql($config);
    $this->config = $config;
  }
  
  public function tearDown() {
    $this->mysql = null;
    $this->config = array();
  }
  
  public function testTrnxFail() {
    $query = 'SELECT COUNT(*) FROM ' . $this->config['table']['topicmap'];
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetchArray();
    $preTmCount = $result[0];
    
    $query = 'SELECT COUNT(*) FROM ' . $this->config['table']['topic'];
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetchArray();
    $preTopicCount = $result[0];
    
    $this->mysql->startTransaction();
    
    $query = 'INSERT INTO ' . $this->config['table']['topicmap'] . 
      '(id, locator) VALUES (NULL, "http://localhost/tm1")';
    $mysqlResult = $this->mysql->execute($query);
    $lastTmId = $mysqlResult->getLastId();
    
    // the column "topicmap_id_fail" does not exist
    $query = 'INSERT INTO ' . $this->config['table']['topic'] . 
      '(id, topicmap_id_fail) VALUES (NULL, ' . $lastTmId . ')';
    $this->mysql->execute($query);
    
    $this->mysql->finishTransaction();
    
    $this->assertTrue($this->mysql->hasError(), 'Expected a MySQL error!');
    $errorMsg = $this->mysql->getError();
    $this->assertTrue(!empty($errorMsg), 'Expected an error message!');
    
    $query = 'SELECT COUNT(*) FROM ' . $this->config['table']['topicmap'];
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetchArray();
    $postTmCount = $result[0];
    
    $query = 'SELECT COUNT(*) FROM ' . $this->config['table']['topic'];
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetchArray();
    $postTopicCount = $result[0];
    
    $this->assertEquals($preTmCount, $postTmCount, 'Unexpected topic map!');
    $this->assertEquals($preTopicCount, $postTopicCount, 'Unexpected topic!');
  }
  
}
?>