<?php
/*
 * QuaaxTM is an implementation of PHPTMAPI which uses MySQL with InnoDB as 
 * storage engine.
 * 
 * Copyright (C) 2008 Johannes Schmidt <joschmidt@users.sourceforge.net>
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
class PHPTMAPITestCase extends PHPUnit_Framework_TestCase {
  
  protected static $tmLocator = 'http://localhost/tm/1', 
  					$dtString = 'http://www.w3.org/2001/XMLSchema#string', 
  					$dtUri = 'http://www.w3.org/2001/XMLSchema#anyURI';
  
  protected $sharedFixture,
            $topicMap;
  
  private $preservedBaseLocators;
  
  protected function setUp() {
    // allow all extending tests being stand alone
    if (!$this->sharedFixture instanceof TopicMapSystem) {
      $tmSystemFactory = TopicMapSystemFactory::newInstance();
      // QuaaxTM specific feature
      $tmSystemFactory->setFeature(VocabularyUtils::QTM_FEATURE_AUTO_DUPL_REMOVAL, true);
      $tmSystem = $tmSystemFactory->newTopicMapSystem();
      $this->sharedFixture = $tmSystem;
    }
    $this->preservedBaseLocators = $this->sharedFixture->getLocators();
    $this->topicMap = $this->sharedFixture->createTopicMap(self::$tmLocator);
  }
  
  protected function tearDown() {
    $locators = $this->sharedFixture->getLocators();
    foreach ($locators as $locator) {
      if (!in_array($locator, $this->preservedBaseLocators)) {
        $tm = $this->sharedFixture->getTopicMap($locator);
        $tm->close();
        $tm->remove();
      }
    }
    $this->topicMap = null;
  }
  
  protected function getIdsOfConstructs(array $constructs) {
    $ids = array();
    foreach ($constructs as $construct) {
      $ids[] = $construct->getId();
    }
    return $ids;
  }
  
  protected function createAssoc() {
    return $this->topicMap->createAssociation($this->topicMap->createTopic());
  }
  
  protected function createRole() {
    return $this->createAssoc()->createRole($this->topicMap->createTopic(), 
      $this->topicMap->createTopic());
  }
  
  protected function createOcc() {
    return $this->topicMap->createTopic()->createOccurrence($this->topicMap->createTopic(), 
      'http://phptmapi.sourceforge.net/', self::$dtUri);
  }
  
  protected function createName() {
    return $this->topicMap->createTopic()->createName('Testname');
  }
  
  protected function createVariant() {
    return $this->createName()->createVariant('Testvariant', 
      self::$dtString, array($this->topicMap->createTopic()));
  }
}
?>
