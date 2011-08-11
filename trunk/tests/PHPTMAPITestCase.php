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
 * Provides common functions for tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class PHPTMAPITestCase extends PHPUnit_Framework_TestCase
{  
  protected $_sharedFixture,
            $_topicMap;
  
  protected static $_tmLocator = 'http://localhost/tm/1', 
  					        $_dtString = 'http://www.w3.org/2001/XMLSchema#string', 
                    $_dtUri = 'http://www.w3.org/2001/XMLSchema#anyURI';
  
  private $_preservedBaseLocators;
  
  /**
   * @see PHPUnit_Framework_TestCase::setUp()
   * @override
   */
  protected function setUp()
  {
    try {
      $tmSystemFactory = TopicMapSystemFactory::newInstance();
      // QuaaxTM specific features
      $tmSystemFactory->setFeature(VocabularyUtils::QTM_FEATURE_AUTO_DUPL_REMOVAL, false);
      $tmSystemFactory->setFeature(VocabularyUtils::QTM_FEATURE_RESULT_CACHE, false);
      $tmSystemFactory->setFeature(VocabularyUtils::QTM_FEATURE_TEST_MODE, true);
      
      $this->_sharedFixture = $tmSystemFactory->newTopicMapSystem();
      $this->_preservedBaseLocators = $this->_sharedFixture->getLocators();
      
      $this->_topicMap = $this->_sharedFixture->createTopicMap(self::$_tmLocator);
    
    } catch (Exception $e) {
      $this->markTestSkipped('Skip test: ' . $e->getMessage());
    }
  }
  
  /**
   * @see PHPUnit_Framework_TestCase::tearDown()
   * @override
   */
  protected function tearDown()
  {
    if ($this->_sharedFixture instanceof TopicMapSystem) {
      $locators = $this->_sharedFixture->getLocators();
      foreach ($locators as $locator) {
        if (!in_array($locator, $this->_preservedBaseLocators)) {
          $tm = $this->_sharedFixture->getTopicMap($locator);
          $tm->close();
          $tm->remove();
        }
      }
      $this->_sharedFixture->close();
      $this->_topicMap = 
      $this->_sharedFixture = null;
    }
  }
  
  protected function _getIdsOfConstructs(array $constructs)
  {
    $ids = array();
    foreach ($constructs as $construct) {
      $ids[] = $construct->getId();
    }
    return $ids;
  }
  
  protected function _createAssoc()
  {
    return $this->_topicMap->createAssociation($this->_topicMap->createTopic());
  }
  
  protected function _createRole()
  {
    $player = $this->_topicMap->createTopicBySubjectIdentifier(
    	'http://example.org'
    );
    return $this->_createAssoc()->createRole(
      $this->_topicMap->createTopic(), 
      $player
    );
  }
  
  protected function _createOcc()
  {
    $type = $this->_topicMap->createTopicBySubjectIdentifier(
    	'http://example.org'
    );
    return $this->_topicMap->createTopic()->createOccurrence(
      $type, 
      'http://phptmapi.sourceforge.net/', 
      self::$_dtUri
    );
  }
  
  protected function _createName()
  {
    return $this->_topicMap->createTopic()->createName('foo');
  }
  
  protected function _createVariant()
  {
    return $this->_createName()->createVariant(
    	'bar', 
      self::$_dtString, 
      array($this->_topicMap->createTopic())
    );
  }
}
?>