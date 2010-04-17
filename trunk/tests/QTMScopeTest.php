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

require_once('PHPTMAPITestCase.php');

/**
 * QuaaxTM scope object tests. A scope object represents a construct's scope.
 * Scope tests from a PHPTMAPI perspective are collected in ScopedTest.php.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class QTMScopeTest extends PHPTMAPITestCase {
  
  private $config,
          $mysql;
  
  public function setUp() {
    parent::setUp();
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
    $this->mysql = new Mysql($config);
    $this->config = $config;
  }
  
  public function tearDown() {
    parent::tearDown();
    $this->mysql = null;
    $this->config = array();
  }
  
  public function testTopicMap() {
    $this->assertTrue($this->topicMap instanceof TopicMap);
  }
  
  public function testMysql() {
    $this->assertTrue($this->mysql instanceof Mysql);
  }
  
  public function testAssociation() {
    $this->_testRemoveThemeFromUnusedScope(
      $this->createAssoc(),
      $this->config['table']['association'],
      $this->config['table']['association_scope'], 
      'association_id'
    );
    $this->_testRemoveThemeFromUsedScope(
      $this->createAssoc(),
      $this->config['table']['association'],
      $this->config['table']['association_scope'], 
      'association_id'
    );
  }
  
  public function testName() {
    $this->_testRemoveThemeFromUnusedScope(
      $this->createName(), 
      $this->config['table']['topicname'],
      $this->config['table']['topicname_scope'], 
      'topicname_id'
    );
    $this->_testRemoveThemeFromUsedScope(
      $this->createName(), 
      $this->config['table']['topicname'],
      $this->config['table']['topicname_scope'], 
      'topicname_id'
    );
  }
  
  public function testOccurrence() {
    $this->_testRemoveThemeFromUnusedScope(
      $this->createOcc(),
      $this->config['table']['occurrence'],
      $this->config['table']['occurrence_scope'], 
      'occurrence_id'
    );
    $this->_testRemoveThemeFromUsedScope(
      $this->createOcc(),
      $this->config['table']['occurrence'],
      $this->config['table']['occurrence_scope'], 
      'occurrence_id'
    );
  }
  
  public function testVariant() {
    $this->_testRemoveThemeFromUnusedScope(
      $this->createVariant(),
      $this->config['table']['variant'],
      $this->config['table']['variant_scope'], 
      'variant_id'
    );
    $this->_testRemoveThemeFromUsedScope(
      $this->createVariant(),
      $this->config['table']['variant'],
      $this->config['table']['variant_scope'], 
      'variant_id'
    );
  }
  
  private function _testRemoveThemeFromUnusedScope(
    Scoped $scoped, 
    $scopedTable, 
    $scopeTable, 
    $fk
    ) 
  {
    $theme1 = $this->topicMap->createTopic();
    $theme2 = $this->topicMap->createTopic();
    $scoped->addTheme($theme1);
    $scoped->addTheme($theme2);
    
    try {
      $theme1->remove();
      $this->fail('Must not remove topic!');
    } catch (TopicInUseException $e) {
      $this->assertEquals($theme1->getId(), $e->getReporter()->getId(), 
        'Expected identity!');
    }
    try {
      $theme2->remove();
      $this->fail('Must not remove topic!');
    } catch (TopicInUseException $e) {
      $this->assertEquals($theme2->getId(), $e->getReporter()->getId(), 
        'Expected identity!');
    }
    
    $scopedDbId = $scoped->getDbId();
    $query = 'SELECT scope_id FROM ' . $scopeTable . ' WHERE ' . $fk . ' = ' . $scopedDbId;
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetch();
    $scopeDbId = $result['scope_id'];
    $this->assertTrue(!is_null($scopeDbId), 'Expected an ID!');
    
    $scoped->remove();
    
    $query = 'SELECT COUNT(*) FROM ' . $scopedTable . ' WHERE id = ' . $scopedDbId;
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetchArray();
    $this->assertEquals((int) $result[0], 0, 'Expected equality!');
    
    $query = 'SELECT COUNT(*) FROM ' . $scopeTable . ' WHERE scope_id = ' . $scopeDbId;
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetchArray();
    $this->assertEquals((int) $result[0], 0, 'Expected equality!');
    
    try {
      $theme1->remove();
    } catch (TopicInUseException $e) {
      $this->fail('Removal must be allowed!');
    }
    try {
      $theme2->remove();
    } catch (TopicInUseException $e) {
      $this->fail('Removal must be allowed!');
    }
  }
  
  private function _testRemoveThemeFromUsedScope(
    Scoped $scoped, 
    $scopedTable, 
    $scopeTable, 
    $fk
    ) 
  {
    $theme1 = $this->topicMap->createTopic();
    $theme2 = $this->topicMap->createTopic();
    $scoped->addTheme($theme1);
    $scoped->addTheme($theme2);
    
    $topic = $this->topicMap->createTopic();
    // create another scoped construct to preserve scope
    $topic->createName('Test', null, array($theme1, $theme2));
    
    try {
      $theme1->remove();
      $this->fail('Must not remove topic!');
    } catch (TopicInUseException $e) {
      $this->assertEquals($theme1->getId(), $e->getReporter()->getId(), 
        'Expected identity!');
    }
    try {
      $theme2->remove();
      $this->fail('Must not remove topic!');
    } catch (TopicInUseException $e) {
      $this->assertEquals($theme2->getId(), $e->getReporter()->getId(), 
        'Expected identity!');
    }
    
    $scopedDbId = $scoped->getDbId();
    $query = 'SELECT scope_id FROM ' . $scopeTable . ' WHERE ' . $fk . ' = ' . $scopedDbId;
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetch();
    $scopeDbId = $result['scope_id'];
    $this->assertTrue(!is_null($scopeDbId), 'Expected an ID!');
    
    $scoped->remove();
    
    $query = 'SELECT COUNT(*) FROM ' . $scopedTable . ' WHERE id = ' . $scopedDbId;
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetchArray();
    $this->assertEquals((int) $result[0], 0, 'Expected equality!');
    
    $query = 'SELECT COUNT(*) FROM ' . $scopeTable . ' WHERE scope_id = ' . $scopeDbId;
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetchArray();
    // the other scoped construct sharing this scope is a topic name
    if (!$scoped instanceof Name) {
      $this->assertEquals((int) $result[0], 0, 'Expected equality!');
    } else {
      $this->assertEquals((int) $result[0], 1, 'Expected equality!');
    }
    
    try {
      $theme1->remove();
      $this->fail('Must not remove topic!');
    } catch (TopicInUseException $e) {
      $this->assertEquals($theme1->getId(), $e->getReporter()->getId(), 
        'Expected identity!');
    }
    try {
      $theme2->remove();
      $this->fail('Must not remove topic!');
    } catch (TopicInUseException $e) {
      $this->assertEquals($theme2->getId(), $e->getReporter()->getId(), 
        'Expected identity!');
    }
  }
}
?>