<?php
/*
 * QuaaxTM is an implementation of PHPTMAPI which uses MySQL with InnoDB as 
 * storage engine.
 * 
 * Copyright (C) 2008 Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * 
 * This library is free software; you can redistribute it and/or modify it under the 
 * terms of the GNU Lesser General Public License as published by the Free Software 
 * Foundation; either version 2.1 of the License, or (at your option) any later version.
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
 * Topic map tests.
 *
 * @package test
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class TopicMapTest extends PHPTMAPITestCase {
  
  public function testTopicMap() {
    $this->assertTrue($this->topicMap instanceof TopicMap);
  }

  public function testParent() {
    $this->assertNull($this->topicMap->getParent(), 'A topic map has no parent!');
  }
  
  public function testTopicCreationSubjectIdentifier() {
    $tm = $this->topicMap;
    $sid = 'http://www.example.org/';
    $this->assertEquals(count($tm->getTopics()), 0, 
      'Expected new topic map created without topics!');
    $topic = $tm->createTopicBySubjectIdentifier($sid);
    $this->assertEquals(count($tm->getTopics()), 1, 'Expected 1 topic!');
    $ids = $this->getIdsOfConstructs($tm->getTopics());
    $this->assertTrue(in_array($topic->getId(), $ids, true), 
      'Topic is not part of getTopics()!');
    $this->assertEquals(count($topic->getSubjectIdentifiers()), 1, 
      'Expected 1 subject identifier');
    $this->assertTrue(in_array($sid, $topic->getSubjectIdentifiers(), true), 
      'Subject identifier is not part of getSubjectIdentifiers()!');
    $this->assertEquals(count($topic->getSubjectLocators()), 0, 
      'Unexpected subject locator');
    $this->assertEquals(count($topic->getItemIdentifiers()), 0, 
      'Unexpected item identifier');
    $sids = $topic->getSubjectIdentifiers();
    foreach ($sids as $_sid) {
      $this->assertEquals($sid, $_sid, 'Unexpected subject identifier');
    }
  }
  
  public function testTopicCreationSubjectIdentifierIllegal() {
    try {
      $this->topicMap->createTopicBySubjectIdentifier(null);
      $this->fail('null is not allowed as subject identifier!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
  }
  
  public function testTopicCreationSubjectLocator() {
    $tm = $this->topicMap;
    $slo = 'http://www.example.org/';
    $this->assertEquals(count($tm->getTopics()), 0, 
      'Expected new topic map created without topics!');
    $topic = $tm->createTopicBySubjectLocator($slo);
    $this->assertEquals(count($tm->getTopics()), 1, 'Expected 1 topic!');
    $ids = $this->getIdsOfConstructs($tm->getTopics());
    $this->assertTrue(in_array($topic->getId(), $ids, true), 
      'Topic is not part of getTopics()!');
    $this->assertEquals(count($topic->getSubjectLocators()), 1, 
      'Expected 1 subject locator');
    $this->assertTrue(in_array($slo, $topic->getSubjectLocators(), true), 
      'Subject locator is not part of getSubjectLocators()!');
    $this->assertEquals(count($topic->getSubjectIdentifiers()), 0, 
      'Unexpected subject identifier');
    $this->assertEquals(count($topic->getItemIdentifiers()), 0, 
      'Unexpected item identifier');
    $slos = $topic->getSubjectLocators();
    foreach ($slos as $_slo) {
      $this->assertEquals($slo, $_slo, 'Unexpected subject locator');
    }
  }
  
  public function testTopicCreationSubjectLocatorIllegal() {
    try {
      $this->topicMap->createTopicBySubjectLocator(null);
      $this->fail('null is not allowed as subject locator!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
  }
  
  public function testTopicCreationItemIdentifier() {
    $tm = $this->topicMap;
    $iid = 'http://www.example.org/';
    $this->assertEquals(count($tm->getTopics()), 0, 
      'Expected new topic map created without topics!');
    $topic = $tm->createTopicByItemIdentifier($iid);
    $this->assertEquals(count($tm->getTopics()), 1, 'Expected 1 topic!');
    $ids = $this->getIdsOfConstructs($tm->getTopics());
    $this->assertTrue(in_array($topic->getId(), $ids, true), 
      'Topic is not part of getTopics()!');
    $this->assertEquals(count($topic->getItemIdentifiers()), 1, 
      'Expected 1 item identifier');
    $this->assertTrue(in_array($iid, $topic->getItemIdentifiers(), true), 
      'Item identifier is not part of getItemIdentifiers()!');
    $this->assertEquals(count($topic->getSubjectIdentifiers()), 0, 
      'Unexpected subject identifier');
    $this->assertEquals(count($topic->getSubjectLocators()), 0, 
      'Unexpected subject locator');
    $iids = $topic->getItemIdentifiers();
    foreach ($iids as $_iid) {
      $this->assertEquals($iid, $_iid, 'Unexpected item identifier');
    }
  }
  
  public function testTopicCreationItemIdentifierIllegal() {
    try {
      $this->topicMap->createTopicByItemIdentifier(null);
      $this->fail('null is not allowed as item identifier!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
  }
  
  public function testTopicCreationAutomagicItemIdentifier() {
    $tm = $this->topicMap;
    $this->assertEquals(count($tm->getTopics()), 0, 
      'Expected new topic map created without topics!');
    $topic = $tm->createTopic();
    $this->assertEquals(count($tm->getTopics()), 1, 'Expected 1 topic!');
    $ids = $this->getIdsOfConstructs($tm->getTopics());
    $this->assertTrue(in_array($topic->getId(), $ids, true), 
      'Topic is not part of getTopics()!');
    $this->assertEquals(count($topic->getItemIdentifiers()), 1, 
      'Expected 1 item identifier');
    $this->assertEquals(count($topic->getSubjectIdentifiers()), 0, 
      'Unexpected subject identifier');
    $this->assertEquals(count($topic->getSubjectLocators()), 0, 
      'Unexpected subject locator');
  }
  
  public function testTopicBySubjectIdentifier() {
    $tm = $this->topicMap;
    $sid = 'http://www.example.org/';
    $topic = $tm->getTopicBySubjectIdentifier($sid);
    $this->assertNull($topic, 'Unexpected topic!');
    $_topic = $tm->createTopicBySubjectIdentifier($sid);
    $this->assertTrue($_topic instanceof Topic, 'Expected topic!');
    $topic = $tm->getTopicBySubjectIdentifier($sid);
    $this->assertEquals($topic->getId(), $_topic->getId(), 'Unexpected topic!');
    $_topic->remove();
    $topic = $tm->getTopicBySubjectIdentifier($sid);
    $this->assertNull($topic, 'Unexpected topic!');
  }
  
  public function testTopicBySubjectLocator() {
    $tm = $this->topicMap;
    $slo = 'http://www.example.org/';
    $topic = $tm->getTopicBySubjectLocator($slo);
    $this->assertNull($topic, 'Unexpected topic!');
    $_topic = $tm->createTopicBySubjectLocator($slo);
    $this->assertTrue($_topic instanceof Topic, 'Expected topic!');
    $topic = $tm->getTopicBySubjectLocator($slo);
    $this->assertEquals($topic->getId(), $_topic->getId(), 'Unexpected topic!');
    $_topic->remove();
    $topic = $tm->getTopicBySubjectLocator($slo);
    $this->assertNull($topic, 'Unexpected topic!');
  }
  
  public function testAssociationCreation() {
    $tm = $this->topicMap;
    $type = $tm->createTopic();
    $this->assertEquals(count($tm->getAssociations()), 0, 
      'Expected new topic map to be created without associations!');
    $assoc = $tm->createAssociation($type);
    $this->assertEquals(count($tm->getAssociations()), 1, 'Expected 1 association!');
    $ids = $this->getIdsOfConstructs($tm->getAssociations());
    $this->assertTrue(in_array($assoc->getId(), $ids, true), 
      'Association is not part of getAssociations()!');
    $this->assertEquals(count($assoc->getRoles()), 0, 'Unexpected number of roles!');
    $this->assertEquals($assoc->getType()->getId(), $type->getId(), 'Unexpected type!');
    $this->assertEquals(count($assoc->getScope()), 0, 'Unexpected scope!');
  }
  
  public function testAssociationCreationScope() {
    $tm = $this->topicMap;
    $type = $tm->createTopic();
    $theme1 = $tm->createTopic();
    $theme2 = $tm->createTopic();
    $this->assertEquals(count($tm->getAssociations()), 0, 
      'Expected new topic map to be created without associations!');
    $assoc = $tm->createAssociation($type, array($theme1, $theme2));
    $this->assertEquals(count($tm->getAssociations()), 1, 'Expected 1 association!');
    $ids = $this->getIdsOfConstructs($tm->getAssociations());
    $this->assertTrue(in_array($assoc->getId(), $ids, true), 
      'Association is not part of getAssociations()!');
    $this->assertEquals(count($assoc->getRoles()), 0, 'Unexpected number of roles!');
    $this->assertEquals($assoc->getType()->getId(), $type->getId(), 'Unexpected type!');
    $this->assertEquals(count($assoc->getScope()), 2, 'Unexpected scope!');
    $ids = $this->getIdsOfConstructs($assoc->getScope());
    $this->assertTrue(in_array($theme1->getId(), $ids, true), 
      'Topic is not part of getScope()!');
    $this->assertTrue(in_array($theme2->getId(), $ids, true), 
      'Topic is not part of getScope()!');
  }
  
  public function testGetIndex() {
    try {
      $index = $this->topicMap->getIndex(md5(uniqid()));
      $this->fail('Exception expected for an unknown index!');
    } catch (Exception $e) {
      // no op.
    }
  }
}
?>
