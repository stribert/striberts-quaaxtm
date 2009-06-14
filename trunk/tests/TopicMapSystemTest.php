<?php
/*
 * QuaaxTM is an implementation of PHPTMAPI which uses MySQL with InnoDB as 
 * storage engine.
 * 
 * Copyright (C) 2008 Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
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
 * Topic map system tests.
 *
 * @package test
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class TopicMapSystemTest extends PHPTMAPITestCase {
  
  private $sys;
  
  /**
   * @override
   */
  public function setUp() {
    parent::setUp();
    $this->sys = $this->sharedFixture;
  }
  
  /**
   * @override
   */
  public function tearDown() {
    parent::tearDown();
    $this->sys = null;
  }
  
  public function testTopicMapSystem() {
    $this->assertTrue($this->sys instanceof TopicMapSystem);
  }

  public function testTopicMap() {
    $this->assertTrue($this->topicMap instanceof TopicMap);
  }
  
  public function testLoad() {
    $tm = $this->sys->getTopicMap(self::$tmLocator);
    $this->assertNotNull($tm, 'Expected topic map!');
    $id = $tm->getId();
    $this->assertTrue(!empty($id), 'Expected internal identifier for topic map!');
    $this->assertEquals($id, $this->topicMap->getId(), 'Expected identity!');
  }
  
  public function testSameLocator() {
    try {
      $tm = $this->sys->createTopicMap(self::$tmLocator);
      $this->fail('A topic map under the same storage address already exists!');
    } catch (TopicMapExistsException $e) {
      // no op.
    }
  }
  
  public function testCreateTopicMaps() {
    $base = 'http://localhost/topicmaps/';
    $tm1 = $this->sys->createTopicMap($base . 'map1');
    $tm2 = $this->sys->createTopicMap($base . 'map2');
    $tm3 = $this->sys->createTopicMap($base . 'map3');
    $this->assertNotNull($tm1, 'Expected a topic map!');
    $this->assertNotNull($tm2, 'Expected a topic map!');
    $this->assertNotNull($tm3, 'Expected a topic map!');
    $id1 = $tm1->getId();
    $id2 = $tm2->getId();
    $id3 = $tm3->getId();
    $this->assertTrue(!empty($id1), 'Expected internal identifier for topic map!');
    $this->assertTrue(!empty($id2), 'Expected internal identifier for topic map!');
    $this->assertTrue(!empty($id3), 'Expected internal identifier for topic map!');
    $this->assertNotEquals($id1, $id2, 'Unexpected identity!');
    $this->assertNotEquals($id1, $id3, 'Unexpected identity!');
    $this->assertNotEquals($id2, $id3, 'Unexpected identity!');
  }
  
  public function testRemoveTopicMaps() {
    $base = 'http://localhost/topicmaps/';
    $tm1 = $this->sys->createTopicMap($base . 'map1');
    $tm2 = $this->sys->createTopicMap($base . 'map2');
    $tm3 = $this->sys->createTopicMap($base . 'map3');
    $this->assertNotNull($tm1, 'Expected a topic map!');
    $this->assertNotNull($tm2, 'Expected a topic map!');
    $this->assertNotNull($tm3, 'Expected a topic map!');
    $countBefore = count($this->sys->getLocators());
    $tm3->remove();
    $countAfter = count($this->sys->getLocators());
    $this->assertEquals($countBefore-1, $countAfter, 'Expected ' . $countBefore-1 . 
      ' topic maps!');
  }
  
  public function testTopicMapMembership() {
    $base = 'http://localhost/topicmaps/';
    $tm1 = $this->sys->createTopicMap($base . 'map1');
    $tm2 = $this->sys->createTopicMap($base . 'map2');
    $this->assertNotNull($tm1, 'Expected a topic map!');
    $this->assertNotNull($tm2, 'Expected a topic map!');
    $topic1 = $tm1->createTopic();
    $topic2 = $tm2->createTopic();
    $assoc1 = $tm1->createAssociation($tm1->createTopic());
    $assoc2 = $tm2->createAssociation($tm2->createTopic());
    $this->assertEquals($topic1->getParent()->getId(), $tm1->getId(), 
      'Unexpected parent topic map!');
    $this->assertNotEquals($topic2->getParent()->getId(), $tm1->getId(), 
      'Unexpected parent topic map!');
    $this->assertEquals($topic2->getParent()->getId(), $tm2->getId(), 
      'Unexpected parent topic map!');
    $this->assertNotEquals($topic1->getParent()->getId(), $tm2->getId(), 
      'Unexpected parent topic map!');
    $this->assertEquals($assoc1->getParent()->getId(), $tm1->getId(), 
      'Unexpected parent topic map!');
    $this->assertNotEquals($assoc2->getParent()->getId(), $tm1->getId(), 
      'Unexpected parent topic map!');
    $this->assertEquals($assoc2->getParent()->getId(), $tm2->getId(), 
      'Unexpected parent topic map!');
    $this->assertNotEquals($assoc1->getParent()->getId(), $tm2->getId(), 
      'Unexpected parent topic map!');
  }
  
  public function testGetModelFeature() {
    $featureIri1 = 'http://tmapi.org/features/model/xtm1.0/';
    $featureIri2 = 'http://tmapi.org/features/model/xtm1.1/';
    $value1 = $this->sys->getFeature($featureIri1);
    $value2 = $this->sys->getFeature($featureIri2);
    $this->assertFalse($value1, 'Unexpected data model support!');
    $this->assertTrue($value2, 'Unexpected data model support!');
  }
  
  public function testGetUnknownFeature() {
    try {
      $this->sys->getFeature(md5(uniqid()));
      $this->fail('Exception expected for an unknown feature!');
    } catch (FeatureNotRecognizedException $e) {
      // no op.
    }
  }
  
  public function testGetUnknownProperty() {
    $property = $this->sys->getProperty(md5(uniqid()));
    $this->assertNull($property, 'Unexpected property!');
  }
  
  public function testGetProperty() {
    $myTmSystemFactory = TopicMapSystemFactory::newInstance();
    $myTmSystemFactory->setProperty('myProperty', new myProperty());
    $myTmSystem = $myTmSystemFactory->newTopicMapSystem();
    $property = $myTmSystem->getProperty('myProperty');
    $this->assertTrue(is_object($property), 'Property is no object!');
    $this->assertEquals('PHPTMAPI', $property->myFunction(), 'Expected identity!');
  }
  
  public function testUnsetProperty() {
    $myTmSystemFactory = TopicMapSystemFactory::newInstance();
    $myTmSystemFactory->setProperty('myProperty', new MyProperty());
    $myTmSystemFactory->setProperty('myProperty', null);
    $myTmSystem = $myTmSystemFactory->newTopicMapSystem();
    $property = $myTmSystem->getProperty('myProperty');
    $this->assertNull($property, 'Unexpected property!');
  }
}

/**
 * Dummy property.
 *
 * @package test
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 */
class MyProperty {
  
  public function myFunction() {
    return 'PHPTMAPI';
  }
}
?>
