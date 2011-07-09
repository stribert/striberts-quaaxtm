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
 * QuaaxTM MySQL result cache tests. The result cache uses memcached.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class QTMResultCacheTest extends PHPTMAPITestCase {
  
  protected function setUp() {
    $tmSystemFactory = TopicMapSystemFactory::newInstance();
    // QuaaxTM specific features
    $tmSystemFactory->setFeature(VocabularyUtils::QTM_FEATURE_AUTO_DUPL_REMOVAL, false);
    $tmSystemFactory->setFeature(VocabularyUtils::QTM_FEATURE_RESULT_CACHE, true);
    try {
      $this->sharedFixture = $tmSystemFactory->newTopicMapSystem();
      $this->preservedBaseLocators = $this->sharedFixture->getLocators();
      $this->topicMap = $this->sharedFixture->createTopicMap(self::$tmLocator);
    } catch (PHPTMAPIRuntimeException $e) {
      $this->markTestSkipped($e->getMessage() . ': Skip test.');
    }
  }
  
  protected function tearDown() {
    if ($this->sharedFixture instanceof TopicMapSystem) {
      $locators = $this->sharedFixture->getLocators();
      foreach ($locators as $locator) {
        if (!in_array($locator, $this->preservedBaseLocators)) {
          $tm = $this->sharedFixture->getTopicMap($locator);
          $tm->close();
          $tm->remove();
        }
      }
      $this->sharedFixture->close();
      $this->topicMap = 
      $this->sharedFixture = null;
    }
  }
  
  public function testTopicMapSystem() {
    $this->assertTrue($this->sharedFixture instanceof TopicMapSystem);
    $this->assertTrue($this->topicMap instanceof TopicMap);
  }
  
  public function testAssociation() {
    $role = $this->createRole();
    $assocs = $this->topicMap->getAssociations();
    $this->assertEquals(count($assocs), 1);
    $assoc = $assocs[0];
    $roles = $assoc->getRoles();
    $this->assertEquals(count($roles), 1);
    // get roles from result cache
    for ($i=0; $i<10; $i++) {
      $roles = $assoc->getRoles();
      $this->assertEquals(count($roles), 1);
    }
  }
  
}
?>