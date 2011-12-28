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
 * Item identifier constraint tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class ItemIdentifierConstraintTest extends PHPTMAPITestCase
{
  public function testTopicMap()
  {
    $this->assertTrue($this->_topicMap instanceof TopicMap);
    $this->_testConstraintSameTopicMap($this->_topicMap);
    $otherMap = $this->_createAnotherTopicMap();
    $this->_testConstraintDifferentTopicMap($otherMap);
    $otherMap->remove();
  }
  
  public function testAssociation()
  {
    $this->_testConstraintSameTopicMap($this->_createAssoc());
    $this->_testConstraintSameTopicMapAgainstTopic($this->_createAssoc());
    $otherMap = $this->_createAnotherTopicMap();
    $otherAssoc = $otherMap->createAssociation($otherMap->createTopic());
    $this->_testConstraintDifferentTopicMap($otherAssoc);
    $otherMap->remove();
  }
  
  public function testRole()
  {
    $this->_testConstraintSameTopicMap($this->_createRole());
    $this->_testConstraintSameTopicMapAgainstTopic($this->_createRole());
    $otherMap = $this->_createAnotherTopicMap();
    $otherAssoc = $otherMap->createAssociation($otherMap->createTopic());
    $otherRole = $otherAssoc->createRole($otherMap->createTopic(), $otherMap->createTopic());
    $this->_testConstraintDifferentTopicMap($otherRole);
    $otherMap->remove();
  }
  
  public function testOccurrence()
  {
    $this->_testConstraintSameTopicMap($this->_createOcc());
    $this->_testConstraintSameTopicMapAgainstTopic($this->_createOcc());
    $otherMap = $this->_createAnotherTopicMap();
    $otherTopic = $otherMap->createTopic();
    $otherOcc = $otherTopic->createOccurrence(
      $otherMap->createTopic(), 
      'foo',
      parent::$_dtString
    );
    $this->_testConstraintDifferentTopicMap($otherOcc);
    $otherMap->remove();
  }
  
  public function testName()
  {
    $this->_testConstraintSameTopicMap($this->_createName());
    $this->_testConstraintSameTopicMapAgainstTopic($this->_createName());
    $otherMap = $this->_createAnotherTopicMap();
    $otherTopic = $otherMap->createTopic();
    $otherName = $otherTopic->createName('foo');
    $this->_testConstraintDifferentTopicMap($otherName);
    $otherMap->remove();
  }
  
  public function testVariant()
  {
    $this->_testConstraintSameTopicMap($this->_createVariant());
    $this->_testConstraintSameTopicMapAgainstTopic($this->_createVariant());
    $otherMap = $this->_createAnotherTopicMap();
    $otherTopic = $otherMap->createTopic();
    $otherName = $otherTopic->createName('foo');
    $otherVariant = $otherName->createVariant(
    	'bar', 
      parent::$_dtString, 
      array($otherMap->createTopic())
    );
    $this->_testConstraintDifferentTopicMap($otherVariant);
    $otherMap->remove();
  }
  
  public function testTopic()
  {
    $this->_testConstraintSameTopicMap(
      $this->_topicMap->createTopicBySubjectIdentifier('http://localhost/t/' . uniqid())
    );
  }
  
  public function testTopicDifferentTopicMapNoMerge()
  {
    $thisTopic = $this->_topicMap->createTopicBySubjectIdentifier(
    	'http://localhost/t/' . uniqid()
    );
    $this->assertEquals(count($this->_topicMap->getTopics()), 1);
    $locator = 'http://localhost/t/0';
    $thisTopic->addItemIdentifier($locator);
    $this->assertTrue(
      in_array($locator, $thisTopic->getItemIdentifiers(), true), 
      'Expected item identifier!'
    );
    $otherMap = $this->_createAnotherTopicMap();
    $otherTopic = $otherMap->createTopicBySubjectIdentifier(
    	'http://localhost/t/' . uniqid()
    );
    $this->assertEquals(count($otherMap->getTopics()), 1);
    $otherTopic->addItemIdentifier($locator);
    $this->assertTrue(
      in_array($locator, $otherTopic->getItemIdentifiers(), true), 
      'Expected item identifier!'
    );
    $this->assertEquals(count($this->_topicMap->getTopics()), 1);
    $this->assertEquals(count($otherMap->getTopics()), 1);
    $otherMap->remove();
  }
  
  public function testTopicMergeUnescaped()
  {
    $this->_testTopicMerge('http://localhost/t/1');
  }
  
  public function testTopicMergeEscaped()
  {
    $this->_testTopicMerge('http://localhost/t/"scape');
  }
  
  public function testEscapedUri()
  {
    $tm = $this->_topicMap;
    $topic = $tm->createTopic();
    $locator = 'http://localhost/t/"scape';
    $topic->addItemIdentifier($locator);
    $this->assertTrue(
      in_array($locator, $topic->getItemIdentifiers(), true), 
      'Expected item identifier!'
    );
    $name = $this->_createName();
    try {
      $name->addItemIdentifier($locator);
    } catch (IdentityConstraintException $e) {
      $this->assertEquals($name->getId(), $e->getReporter()->getId());
      $this->assertEquals($topic->getId(), $e->getExisting()->getId());
      $this->assertEquals($locator, $e->getLocator());
      $msg = $e->getMessage();
      $this->assertTrue(!empty($msg));
    }
  }
  
  private function _testConstraintSameTopicMap(Construct $construct)
  {
    $this->assertEquals(0, count($construct->getItemIdentifiers()), 
      'Expected number of item identifiers to be 0 for newly created construct!');
    $locator1 = 'http://localhost/c/1';
    $locator2 = 'http://localhost/c/2';
    $assoc = $this->_createAssoc();
    $assoc->addItemIdentifier($locator1);
    $this->assertFalse(in_array($locator1, $construct->getItemIdentifiers(), true), 
      'Unexpected item identifier!');
    try {
      $construct->addItemIdentifier($locator1);
      $this->fail('Topic Maps constructs with the same item identifier are not allowed!');
    } catch (IdentityConstraintException $e) {
      $this->assertEquals($construct->getId(), $e->getReporter()->getId());
      $this->assertEquals($assoc->getId(), $e->getExisting()->getId());
      $this->assertEquals($locator1, $e->getLocator());
      $msg = $e->getMessage();
      $this->assertTrue(!empty($msg));
    }
    $construct->addItemIdentifier($locator2);
    $this->assertTrue(in_array($locator2, $construct->getItemIdentifiers(), true), 
      'Expected item identifier!');
    $construct->removeItemIdentifier($locator2);
    $assoc->removeItemIdentifier($locator1);
    $this->assertFalse(in_array($locator1, $assoc->getItemIdentifiers(), true), 
      'Unexpected item identifier!');
    $construct->addItemIdentifier($locator1);
    $this->assertTrue(in_array($locator1, $construct->getItemIdentifiers(), true), 
      'Expected item identifier!');
    if (!$construct instanceof TopicMap) {
      // removal should free the iid
      $construct->remove();
      $assoc->addItemIdentifier($locator1);
      $this->assertTrue(in_array($locator1, $assoc->getItemIdentifiers(), true), 
        'Expected item identifier!');
    }
  }
  
  private function _testConstraintSameTopicMapAgainstTopic(Construct $construct)
  {
    $this->assertEquals(0, count($construct->getItemIdentifiers()), 
      'Expected number of item identifiers to be 0 for newly created construct!');
    $locator1 = 'http://localhost/c/3';
    $locator2 = 'http://localhost/c/4';
    $topic = $this->_topicMap->createTopic();
    $topic->addItemIdentifier($locator1);
    $this->assertFalse(in_array($locator1, $construct->getItemIdentifiers(), true), 
      'Unexpected item identifier!');
    try {
      $construct->addItemIdentifier($locator1);
      $this->fail('Topic Maps constructs with the same item identifier are not allowed!');
    } catch (IdentityConstraintException $e) {
      $this->assertEquals($construct->getId(), $e->getReporter()->getId());
      $this->assertEquals($topic->getId(), $e->getExisting()->getId());
      $this->assertEquals($locator1, $e->getLocator());
      $msg = $e->getMessage();
      $this->assertTrue(!empty($msg));
    }
    $construct->addItemIdentifier($locator2);
    $this->assertTrue(in_array($locator2, $construct->getItemIdentifiers(), true), 
      'Expected item identifier!');
    $construct->removeItemIdentifier($locator2);
    $topic->removeItemIdentifier($locator1);
    $this->assertFalse(in_array($locator1, $topic->getItemIdentifiers(), true), 
      'Unexpected item identifier!');
    $construct->addItemIdentifier($locator1);
    $this->assertTrue(in_array($locator1, $construct->getItemIdentifiers(), true), 
      'Expected item identifier!');
    if (!$construct instanceof TopicMap) {
      // removal should free the iid
      $construct->remove();
      $topic->addItemIdentifier($locator1);
      $this->assertTrue(in_array($locator1, $topic->getItemIdentifiers(), true), 
        'Expected item identifier!');
    }
  }
  
  private function _testConstraintDifferentTopicMap(Construct $otherConstruct)
  {
    $this->assertEquals(
      0, 
      count($otherConstruct->getItemIdentifiers()), 
      'Expected number of item identifiers to be 0 for newly created construct!'
    );
    $locator = 'http://localhost/c/5';
    $thisAssoc = $this->_createAssoc();
    $thisAssoc->addItemIdentifier($locator);
    $this->assertFalse(
      in_array($locator, $otherConstruct->getItemIdentifiers(), true), 
      'Unexpected item identifier!'
    );
    $otherConstruct->addItemIdentifier($locator);
    $this->assertTrue(
      in_array($locator, $otherConstruct->getItemIdentifiers(), true), 
      'Expected item identifier!'
    );
  }
  
  private function _createAnotherTopicMap()
  {
    return $this->_sharedFixture->createTopicMap(
      'http://localhost/tm/' . uniqid()
    );
  }
  
  private function _testTopicMerge($locator)
  {
    $tm = $this->_topicMap;
    $topic1 = $tm->createTopic();
    $topic1->addItemIdentifier($locator);
    $this->assertTrue(in_array($locator, $topic1->getItemIdentifiers(), true), 
      'Expected item identifier!');
    $topic2 = $tm->createTopic();
    try {
      $topic2->addItemIdentifier($locator);
      if (!$this->_sharedFixture->getFeature('http://tmapi.org/features/automerge/')) {
        $this->fail('Topics with the same item identifier are not allowed!');
      }
    } catch (IdentityConstraintException $e) {
      $this->assertEquals($topic2->getId(), $e->getReporter()->getId());
      $this->assertEquals($topic1->getId(), $e->getExisting()->getId());
      $this->assertEquals($locator, $e->getLocator());
      $msg = $e->getMessage();
      $this->assertTrue(!empty($msg));
    }
    if ($this->_sharedFixture->getFeature('http://tmapi.org/features/automerge/')) {
      // $topic1 has been merged; it must be omitted from here
      $this->assertEquals(count($tm->getTopics()), 1, 'Topics have not been merged!');
      $this->assertTrue(in_array($locator, $topic2->getItemIdentifiers(), true), 
        'Expected item identifier!');
      $countIidsBefore = count($topic2->getItemIdentifiers());
      $topic2->addItemIdentifier($locator);
      $countIidsAfter = count($topic2->getItemIdentifiers());
      $this->assertEquals($countIidsBefore, $countIidsAfter, 
        'Unexpected count of item identifiers!');
      $this->assertTrue(in_array($locator, $topic2->getItemIdentifiers(), true), 
        'Expected item identifier!');
      $topic2->removeItemIdentifier($locator);
      $this->assertFalse(in_array($locator, $topic2->getItemIdentifiers(), true), 
        'Unexpected item identifier!');
    } else {
      $this->assertEquals(count($tm->getTopics()), 2, 'Expected 2 topics!');
      // TODO extend tests for automerge = false
    }
  }
}
?>
