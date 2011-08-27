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

require_once(
  dirname(__FILE__) . 
  DIRECTORY_SEPARATOR . 
  '..' . 
  DIRECTORY_SEPARATOR . 
  'lib' . 
  DIRECTORY_SEPARATOR . 
  'phptmapi2.0' . 
  DIRECTORY_SEPARATOR . 
  'core' . 
  DIRECTORY_SEPARATOR . 
  'TopicMapSystemFactory.class.php'
);

/**
 * QuaaxTM MySQL result cache tests. The result cache uses memcached.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class QTMResultCacheTest extends PHPUnit_Framework_TestCase
{
  private $_tmSystem, 
          $_preservedBaseLocators, 
          $_mysqlMock,
          $_config;
          
  private static $_tmLocator = 'http://localhost/tm/s3cr31';
  
  private static $_resultCacheExpiration = 2;
  
  /**
   * @see PHPUnit_Framework_TestCase::setUp()
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
    $this->_config = $config;
    
    try {
      $tmSystemFactory = TopicMapSystemFactory::newInstance();
      // QuaaxTM specific features
      $tmSystemFactory->setFeature(VocabularyUtils::QTM_FEATURE_AUTO_DUPL_REMOVAL, false);
      
      // create a mock object to allow detailed testing 
      $this->_mysqlMock = new MysqlMock($this->_config, true);// "true" enables memcached
      $this->_mysqlMock->setResultCacheExpiration(self::$_resultCacheExpiration);
      $tmSystemFactory->setProperty(VocabularyUtils::QTM_PROPERTY_MYSQL, $this->_mysqlMock);
    
      $this->_tmSystem = $tmSystemFactory->newTopicMapSystem();
      $this->_preservedBaseLocators = $this->_tmSystem->getLocators();
      
    } catch (Exception $e) {
      $this->markTestSkipped($e->getMessage() . ': Skip test.');
    }
  }
  
  /**
   * @see PHPUnit_Framework_TestCase::setUp()
   * @override
   */
  protected function tearDown()
  {
    if ($this->_tmSystem instanceof TopicMapSystem) {
      $this->_mysqlMock->resetRuntimeUsedResultCache();
      $locators = $this->_tmSystem->getLocators();
      foreach ($locators as $locator) {
        if (!in_array($locator, $this->_preservedBaseLocators)) {
          $tm = $this->_tmSystem->getTopicMap($locator);
          $tm->close();
          $tm->remove();
        }
      }
      $this->_tmSystem->close();
      $this->_tmSystem = 
      $this->_mysqlMock = 
      $this->_config = null;
      $this->_preservedBaseLocators = array();
    }
  }
  
  public function testTopicMapSystem()
  {
    $this->assertTrue($this->_tmSystem instanceof TopicMapSystem);
    $mysqlProperty = $this->_tmSystem->getProperty(VocabularyUtils::QTM_PROPERTY_MYSQL);
    $this->assertTrue($mysqlProperty instanceof MysqlMock);
  }
  
  public function testAssociationGetRoles()
  {
    $this->assertFalse($this->_mysqlMock->memcachedWasCalledSuccessfully);
    $this->assertFalse($this->_mysqlMock->memcachedWasIgnored);
    $this->assertFalse($this->_mysqlMock->memcachedWasSet);
    
    $topicMap = $this->_tmSystem->createTopicMap(self::$_tmLocator);
    $assoc = $topicMap->createAssociation($topicMap->createTopic());
    $assoc->createRole($topicMap->createTopic(), $topicMap->createTopic());
    $this->_testAssociationGetRoles($assoc, true);
    
    // test regular Mysql class
    try {
      $tmSystemFactory = $this->_getTmSystemFactory();
      $tmSystem = $tmSystemFactory->newTopicMapSystem();
    } catch (Exception $e) {
      $this->markTestSkipped($e->getMessage() . ': Skip test.');
    }
      
    $topicMap = $tmSystem->createTopicMap(self::$_tmLocator . uniqid());
    $assoc = $topicMap->createAssociation($topicMap->createTopic());
    $assoc->createRole($topicMap->createTopic(), $topicMap->createTopic());
    $this->_testAssociationGetRoles($assoc);
  }
  
  public function testAssociationGetRoleTypes()
  {
    $this->assertFalse($this->_mysqlMock->memcachedWasCalledSuccessfully);
    $this->assertFalse($this->_mysqlMock->memcachedWasIgnored);
    $this->assertFalse($this->_mysqlMock->memcachedWasSet);
    
    $topicMap = $this->_tmSystem->createTopicMap(self::$_tmLocator);
    $assoc = $topicMap->createAssociation($topicMap->createTopic());
    $roleType = $topicMap->createTopic();
    $assoc->createRole($roleType, $topicMap->createTopic());
    $this->_testAssociationGetRoleTypes($assoc, $roleType, true);
    
    // test regular Mysql class
    try {
      $tmSystemFactory = $this->_getTmSystemFactory();
      $tmSystem = $tmSystemFactory->newTopicMapSystem();
    } catch (Exception $e) {
      $this->markTestSkipped($e->getMessage() . ': Skip test.');
    }
      
    $topicMap = $tmSystem->createTopicMap(self::$_tmLocator. uniqid());
    $assoc = $topicMap->createAssociation($topicMap->createTopic());
    $roleType = $topicMap->createTopic();
    $assoc->createRole($roleType, $topicMap->createTopic());
    $this->_testAssociationGetRoleTypes($assoc, $roleType);
  }
  
  public function testTopicMapGetAssociations()
  {
    $this->assertFalse($this->_mysqlMock->memcachedWasCalledSuccessfully);
    $this->assertFalse($this->_mysqlMock->memcachedWasIgnored);
    $this->assertFalse($this->_mysqlMock->memcachedWasSet);
    
    $topicMap = $this->_tmSystem->createTopicMap(self::$_tmLocator);
    $topicMap->createAssociation($topicMap->createTopic());
    $topicMap->createAssociation($topicMap->createTopic());
    $this->_testTopicMapGetAssociations($topicMap, true);
    
    // test regular Mysql class
    try {
      $tmSystemFactory = $this->_getTmSystemFactory();
      $tmSystem = $tmSystemFactory->newTopicMapSystem();
    } catch (Exception $e) {
      $this->markTestSkipped($e->getMessage() . ': Skip test.');
    }
      
    $topicMap = $tmSystem->createTopicMap(self::$_tmLocator. uniqid());
    $topicMap->createAssociation($topicMap->createTopic());
    $topicMap->createAssociation($topicMap->createTopic());
    
    $this->_testTopicMapGetAssociations($topicMap);
  }
  
  public function testSetDefaultResultCacheExpiration()
  {
    try {
      $tmSystemFactory = TopicMapSystemFactory::newInstance();
      // need to unset MySQL property due to singleton
      $tmSystemFactory->setProperty(VocabularyUtils::QTM_PROPERTY_MYSQL, null);
      // QuaaxTM specific features
      $tmSystemFactory->setFeature(VocabularyUtils::QTM_FEATURE_AUTO_DUPL_REMOVAL, false);
      $tmSystemFactory->setFeature(VocabularyUtils::QTM_FEATURE_RESULT_CACHE, true);
      $tmSystem = $tmSystemFactory->newTopicMapSystem();
      $this->assertTrue($tmSystem instanceof TopicMapSystem);
    } catch (Exception $e) {
      $this->markTestSkipped($e->getMessage() . ': Skip test.');
    }
  }
  
  private function _getTmSystemFactory()
  {
    $tmSystemFactory = TopicMapSystemFactory::newInstance();
    // need to unset MySQL property due to singleton
    $tmSystemFactory->setProperty(VocabularyUtils::QTM_PROPERTY_MYSQL, null);
    // QuaaxTM specific features
    $tmSystemFactory->setFeature(VocabularyUtils::QTM_FEATURE_AUTO_DUPL_REMOVAL, false);
    $tmSystemFactory->setFeature(VocabularyUtils::QTM_FEATURE_RESULT_CACHE, true);
    
    $mysql = new Mysql($this->_config, true);// "true" enables memcached
    $mysql->setResultCacheExpiration(self::$_resultCacheExpiration);
    $tmSystemFactory->setProperty(VocabularyUtils::QTM_PROPERTY_MYSQL, $mysql);
    
    return $tmSystemFactory;
  }
  
  private function _testAssociationGetRoles(Association $assoc, $mock=false)
  {
    $roles = $assoc->getRoles();
    $this->assertEquals(count($roles), 1);
    if ($mock) {
      $this->assertFalse($this->_mysqlMock->memcachedWasIgnored);
      $this->assertTrue($this->_mysqlMock->memcachedWasSet);
    }
    // get roles from result cache
    $roles = $assoc->getRoles();
    $this->assertEquals(count($roles), 1);
    if ($mock) {
      $this->assertFalse($this->_mysqlMock->memcachedWasIgnored);
      $this->assertTrue($this->_mysqlMock->memcachedWasCalledSuccessfully);
      $this->assertFalse($this->_mysqlMock->memcachedWasSet);
    }
  }
  
  private function _testAssociationGetRoleTypes(
    Association $assoc, 
    Topic $roleType, 
    $mock=false
    )
  {
    $roleTypes = $assoc->getRoleTypes();
    $this->assertEquals(count($roleTypes), 1);
    $this->assertEquals($roleTypes[0]->getId(), $roleType->getId());
    if ($mock) {
      $this->assertFalse($this->_mysqlMock->memcachedWasIgnored);
      $this->assertTrue($this->_mysqlMock->memcachedWasSet);
    }
    // get roles from result cache
    $roleTypes = $assoc->getRoleTypes();
    $this->assertEquals(count($roleTypes), 1);
    $this->assertEquals($roleTypes[0]->getId(), $roleType->getId());
    if ($mock) {
      $this->assertFalse($this->_mysqlMock->memcachedWasIgnored);
      $this->assertTrue($this->_mysqlMock->memcachedWasCalledSuccessfully);
      $this->assertFalse($this->_mysqlMock->memcachedWasSet);
    }
  }
  
  private function _testTopicMapGetAssociations(TopicMap $topicMap, $mock=false)
  {
    $assocs = $topicMap->getAssociations();
    $this->assertEquals(count($assocs), 2);
    if ($mock) {
      $this->assertFalse($this->_mysqlMock->memcachedWasIgnored);
      $this->assertTrue($this->_mysqlMock->memcachedWasSet);
    }
    // the associations are stored in class member $_assocsCache, nothing changes
    $assocs = $topicMap->getAssociations();
    $this->assertEquals(count($assocs), 2);
    if ($mock) {
      $this->assertFalse($this->_mysqlMock->memcachedWasCalledSuccessfully);
      $this->assertFalse($this->_mysqlMock->memcachedWasIgnored);
      $this->assertTrue($this->_mysqlMock->memcachedWasSet);
    }
    
    $topicMap->clearAssociationsCache();
    
    // get associations from result cache
    $assocs = $topicMap->getAssociations();
    $this->assertEquals(count($assocs), 2);
    if ($mock) {
      $this->assertTrue($this->_mysqlMock->memcachedWasCalledSuccessfully);
      $this->assertFalse($this->_mysqlMock->memcachedWasIgnored);
      $this->assertFalse($this->_mysqlMock->memcachedWasSet);
    }
  }
}
?>