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
          $_mysqlMock;
          
  private static $_tmLocator = 'http://localhost/tm/s3cr31';
  
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
    
    try {
      $tmSystemFactory = TopicMapSystemFactory::newInstance();
      // QuaaxTM specific features
      $tmSystemFactory->setFeature(VocabularyUtils::QTM_FEATURE_AUTO_DUPL_REMOVAL, false);
      
      // create a mock object to allow detailed testing 
      $this->_mysqlMock = new MysqlMock($config, true);// "true" enables memcached
      $this->_mysqlMock->setResultCacheExpiration(5);
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
      $this->_tmSystem = null;
    }
  }
  
  public function testTopicMapSystem()
  {
    $this->assertTrue($this->_tmSystem instanceof TopicMapSystem);
    $mysqlProperty = $this->_tmSystem->getProperty(VocabularyUtils::QTM_PROPERTY_MYSQL);
    $this->assertTrue($mysqlProperty instanceof MysqlMock);
  }
  
  public function testAssociation()
  {
    $this->assertFalse($this->_mysqlMock->memcachedWasCalledSuccessfully);
    $this->assertFalse($this->_mysqlMock->memcachedWasIgnored);
    $this->assertFalse($this->_mysqlMock->memcachedWasSet);
    
    $topicMap = $this->_tmSystem->createTopicMap(self::$_tmLocator);
    $assoc = $topicMap->createAssociation($topicMap->createTopic());
    $assoc->createRole($topicMap->createTopic(), $topicMap->createTopic());
    $assocs = $topicMap->getAssociations();
    $this->assertEquals(count($assocs), 1);
    $assoc = $assocs[0];
    $this->_testAssociation($assoc, true);
    
    // test regular Mysql class
    try {
      $tmSystemFactory = TopicMapSystemFactory::newInstance();
      // need to unset MySQL property due to singleton
      $tmSystemFactory->setProperty(VocabularyUtils::QTM_PROPERTY_MYSQL, null);
      // QuaaxTM specific features
      $tmSystemFactory->setFeature(VocabularyUtils::QTM_FEATURE_AUTO_DUPL_REMOVAL, false);
      $tmSystemFactory->setFeature(VocabularyUtils::QTM_FEATURE_RESULT_CACHE, true);
      
      $tmSystem = $tmSystemFactory->newTopicMapSystem();
      
      $topicMap = $tmSystem->createTopicMap(self::$_tmLocator . uniqid());
      $assoc = $topicMap->createAssociation($topicMap->createTopic());
      $assoc->createRole($topicMap->createTopic(), $topicMap->createTopic());
      $assocs = $topicMap->getAssociations();
      $this->assertEquals(count($assocs), 1);
      $assoc = $assocs[0];
      $this->_testAssociation($assoc);
      
    } catch (Exception $e) {
      $this->fail(
        'Could not test regular Mysql class in ' . __METHOD__ . ': ' . $e->getMessage()
      );
    }
  }
  
  /**
   * TODO remove this again
   */
  public function testFoo()
  {
    $this->assertFalse($this->_mysqlMock->memcachedWasCalledSuccessfully);
    $this->assertFalse($this->_mysqlMock->memcachedWasIgnored);
    $this->assertFalse($this->_mysqlMock->memcachedWasSet);
  }
  
  private function _testAssociation(Association $assoc, $mock=false)
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
}
?>