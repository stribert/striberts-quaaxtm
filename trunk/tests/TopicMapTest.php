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
 * Topic map tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class TopicMapTest extends PHPTMAPITestCase
{
  public function testTopicMap()
  {
    $this->assertTrue($this->_topicMap instanceof TopicMap);
  }

  public function testParent()
  {
    $this->assertNull($this->_topicMap->getParent(), 'A topic map has no parent!');
  }
  
  public function testTopicCreationSubjectIdentifier()
  {
    $tm = $this->_topicMap;
    $sid = 'http://www.example.org/';
    $this->assertEquals(count($tm->getTopics()), 0, 
      'Expected new topic map created without topics!');
    $topic = $tm->createTopicBySubjectIdentifier($sid);
    $this->assertEquals(count($tm->getTopics()), 1, 'Expected 1 topic!');
    $ids = $this->_getIdsOfConstructs($tm->getTopics());
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
    $topic1 = $tm->createTopicByItemIdentifier('http://www.example.org/foo');
    $topic2 = $tm->createTopicBySubjectIdentifier('http://www.example.org/foo');
    $this->assertEquals($topic1->getId(), $topic2->getId(), 'Expected identity!');
    $sids = $topic1->getSubjectIdentifiers();
    $this->assertTrue(
      in_array('http://www.example.org/foo', $sids, 'Expected subject identifier!')
    );
  }
  
  public function testTopicCreationSubjectIdentifierIllegal()
  {
    try {
      $this->_topicMap->createTopicBySubjectIdentifier(null);
      $this->fail('null is not allowed as subject identifier!');
    } catch (ModelConstraintException $e) {
      $msg = $e->getMessage();
      $this->assertTrue(!empty($msg));
    }
  }
  
  public function testTopicCreationSubjectLocator()
  {
    $tm = $this->_topicMap;
    $slo = 'http://www.example.org/';
    $this->assertEquals(count($tm->getTopics()), 0, 
      'Expected new topic map created without topics!');
    $topic = $tm->createTopicBySubjectLocator($slo);
    $this->assertEquals(count($tm->getTopics()), 1, 'Expected 1 topic!');
    $ids = $this->_getIdsOfConstructs($tm->getTopics());
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
    $topic1 = $tm->createTopicBySubjectLocator('http://www.example.org/foo');
    $topic2 = $tm->createTopicBySubjectLocator('http://www.example.org/foo');
    $this->assertEquals($topic1->getId(), $topic2->getId(), 'Expected identity!');
  }
  
  public function testTopicCreationSubjectLocatorIllegal()
  {
    try {
      $this->_topicMap->createTopicBySubjectLocator(null);
      $this->fail('null is not allowed as subject locator!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
  }
  
  public function testTopicCreationItemIdentifier()
  {
    $tm = $this->_topicMap;
    $iid = 'http://www.example.org/';
    $this->assertEquals(count($tm->getTopics()), 0, 
      'Expected new topic map created without topics!');
    $topic = $tm->createTopicByItemIdentifier($iid);
    $this->assertEquals(count($tm->getTopics()), 1, 'Expected 1 topic!');
    $ids = $this->_getIdsOfConstructs($tm->getTopics());
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
    $name = $this->_createName();
    $iid = 'http://www.example.org#foo';
    $name->addItemIdentifier($iid);
    try {
      $tm->createTopicByItemIdentifier($iid);
      $this->fail('Expected IdentityConstraintException!');
    } catch (IdentityConstraintException $e) {
      $this->assertEquals(
        $e->getExisting()->getId(), $name->getId(), 'Expected identitity!'
      );
      $this->assertEquals(
        $e->getReporter()->getId(), $tm->getId(), 'Expected identitity!'
      );
      $this->assertEquals($e->getLocator(), $iid, 'Expected identitity!');
    }
    $topic1 = $tm->createTopicBySubjectIdentifier('http://www.example.org/foo');
    $topic2 = $tm->createTopicByItemIdentifier('http://www.example.org/foo');
    $this->assertEquals($topic1->getId(), $topic2->getId(), 'Expected identity!');
    $iids = $topic1->getItemIdentifiers();
    $this->assertTrue(
      in_array('http://www.example.org/foo', $iids, 'Expected item identifier!')
    );
  }
  
  public function testTopicCreationItemIdentifierIllegal()
  {
    try {
      $this->_topicMap->createTopicByItemIdentifier(null);
      $this->fail('null is not allowed as item identifier!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
  }
  
  public function testTopicCreationAutomagicItemIdentifier()
  {
    $tm = $this->_topicMap;
    $this->assertEquals(count($tm->getTopics()), 0, 
      'Expected new topic map created without topics!');
    $topic = $tm->createTopic();
    $this->assertEquals(count($tm->getTopics()), 1, 'Expected 1 topic!');
    $ids = $this->_getIdsOfConstructs($tm->getTopics());
    $this->assertTrue(in_array($topic->getId(), $ids, true), 
      'Topic is not part of getTopics()!');
    $this->assertEquals(count($topic->getItemIdentifiers()), 1, 
      'Expected 1 item identifier');
    $this->assertEquals(count($topic->getSubjectIdentifiers()), 0, 
      'Unexpected subject identifier');
    $this->assertEquals(count($topic->getSubjectLocators()), 0, 
      'Unexpected subject locator');
  }
  
  private function _testGetTopicBySubjectIdentifier($sid)
  {
    $tm = $this->_topicMap;
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
  
  public function testGetTopicBySubjectIdentifier()
  {
    $this->_testGetTopicBySubjectIdentifier('http://www.example.org/');
  }
  
  public function testGetTopicBySubjectIdentifierEscaped()
  {
    $this->_testGetTopicBySubjectIdentifier("http://www.example.org/2/'scaped");
  }
  
  private function _testGetTopicBySubjectLocator($slo)
  {
    $tm = $this->_topicMap;
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
  
  public function testGetTopicBySubjectLocator()
  {
    $this->_testGetTopicBySubjectLocator('http://www.example.org/');
  }
  
  public function testGetTopicBySubjectLocatorEscaped()
  {
    $this->_testGetTopicBySubjectLocator("http://www.example.org/2/'scaped");
  }
  
  public function testCreateAssociation()
  {
    $tm = $this->_topicMap;
    $type = $tm->createTopic();
    $this->assertEquals(count($tm->getAssociations()), 0, 
      'Expected new topic map to be created without associations!');
    $assoc = $tm->createAssociation($type);
    $this->assertEquals(count($tm->getAssociations()), 1, 'Expected 1 association!');
    $ids = $this->_getIdsOfConstructs($tm->getAssociations());
    $this->assertTrue(in_array($assoc->getId(), $ids, true), 
      'Association is not part of getAssociations()!');
    $this->assertEquals(count($assoc->getRoles()), 0, 'Unexpected number of roles!');
    $this->assertEquals($assoc->getType()->getId(), $type->getId(), 'Unexpected type!');
    $this->assertEquals(count($assoc->getScope()), 0, 'Unexpected scope!');
  }
  
  public function testCreateAssociationScope()
  {
    $tm = $this->_topicMap;
    $type = $tm->createTopic();
    $theme1 = $tm->createTopic();
    $theme2 = $tm->createTopic();
    $this->assertEquals(count($tm->getAssociations()), 0, 
      'Expected new topic map to be created without associations!');
    $assoc = $tm->createAssociation($type, array($theme1, $theme2));
    $this->assertEquals(count($tm->getAssociations()), 1, 'Expected 1 association!');
    $ids = $this->_getIdsOfConstructs($tm->getAssociations());
    $this->assertTrue(in_array($assoc->getId(), $ids, true), 
      'Association is not part of getAssociations()!');
    $this->assertEquals(count($assoc->getRoles()), 0, 'Unexpected number of roles!');
    $this->assertEquals($assoc->getType()->getId(), $type->getId(), 'Unexpected type!');
    $this->assertEquals(count($assoc->getScope()), 2, 'Unexpected scope!');
    $ids = $this->_getIdsOfConstructs($assoc->getScope());
    $this->assertTrue(in_array($theme1->getId(), $ids, true), 
      'Topic is not part of getScope()!');
    $this->assertTrue(in_array($theme2->getId(), $ids, true), 
      'Topic is not part of getScope()!');
  }
  
  public function testGetIndex()
  {
    try {
      $this->_topicMap->getIndex(md5(uniqid()));
      $this->fail('Exception expected for an unknown index!');
    } catch (Exception $e) {
      // no op.
    }
  }
  
  public function testRemove()
  {
    $tm = $this->_topicMap;
    $typeTheme = $tm->createTopic();
    $topic = $tm->createTopic();
    $variant = $this->_createVariant();
    $variant->addTheme($typeTheme);
    $topic->addType($typeTheme);
    try {
      $tm->remove();
      $this->assertTrue(is_null($tm->getId()), 'Topic map must be removed!');
    } catch (PHPTMAPIRuntimeException $e) {
      $this->fail('Removal of topic map failed!');
    }
  }
}
?>
