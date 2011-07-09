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
 * "Same topic map constraint" tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class SameTopicMapTest extends PHPTMAPITestCase {
  
  public function testTopicMap() {
    $this->assertTrue($this->topicMap instanceof TopicMap);
  }
  
  public function testAssociationCreationIllegalType() {
    try {
      $otherTopicMap = $this->sharedFixture->createTopicMap('http://localhost/tm/' . uniqid());
      $this->topicMap->createAssociation($otherTopicMap->createTopic());
      $this->fail('Expected a model contraint exception!');
      $otherTopicMap->remove();
    } catch (ModelConstraintException $e) {
      $this->assertEquals(
        $e->getReporter()->getId(), 
        $this->topicMap->getId(), 
        'Expected identity!'
      );
      $msg = $e->getMessage();
      $this->assertTrue(!empty($msg));
      $otherTopicMap->remove();
    }
  }
  
  public function testAssociationCreationIllegalScope() {
    try {
      $otherTopicMap = $this->sharedFixture->createTopicMap('http://localhost/tm/' . uniqid());
      $this->topicMap->createAssociation(
        $this->topicMap->createTopic(), 
        array($this->topicMap->createTopic(), $otherTopicMap->createTopic(), 'foo')
      );
      $this->fail('Expected a model contraint exception!');
      $otherTopicMap->remove();
    } catch (ModelConstraintException $e) {
      $this->assertEquals(
        $e->getReporter()->getId(), 
        $this->topicMap->getId(), 
        'Expected identity!'
      );
      $msg = $e->getMessage();
      $this->assertTrue(!empty($msg));
      $otherTopicMap->remove();
    }
  }
  
  public function testNameCreationIllegalType() {
    try {
      $otherTopicMap = $this->sharedFixture->createTopicMap('http://localhost/tm/' . uniqid());
      $parent = $this->topicMap->createTopic();
      $parent->createName('Name', $otherTopicMap->createTopic());
      $this->fail('Expected a model contraint exception!');
      $otherTopicMap->remove();
    } catch (ModelConstraintException $e) {
      $this->assertEquals(
        $e->getReporter()->getId(), 
        $parent->getId(), 
        'Expected identity!'
      );
      $msg = $e->getMessage();
      $this->assertTrue(!empty($msg));
      $otherTopicMap->remove();
    }
  }
  
  public function testNameCreationIllegalScope() {
    try {
      $otherTopicMap = $this->sharedFixture->createTopicMap('http://localhost/tm/' . uniqid());
      $parent = $this->topicMap->createTopic();
      $parent->createName(
      	'Name', 
        $this->topicMap->createTopic(), 
        array($this->topicMap->createTopic(), $otherTopicMap->createTopic(), 'foo')
      );
      $this->fail('Expected a model contraint exception!');
      $otherTopicMap->remove();
    } catch (ModelConstraintException $e) {
      $this->assertEquals(
        $e->getReporter()->getId(), 
        $parent->getId(), 
        'Expected identity!'
      );
      $msg = $e->getMessage();
      $this->assertTrue(!empty($msg));
      $otherTopicMap->remove();
    }
  }
  
  public function testOccurrenceCreationIllegalType() {
    try {
      $otherTopicMap = $this->sharedFixture->createTopicMap('http://localhost/tm/' . uniqid());
      $parent = $this->topicMap->createTopic();
      $parent->createOccurrence($otherTopicMap->createTopic(), 'Occurrence', parent::$dtString);
      $this->fail('Expected a model contraint exception!');
      $otherTopicMap->remove();
    } catch (ModelConstraintException $e) {
      $this->assertEquals(
        $e->getReporter()->getId(), 
        $parent->getId(), 
        'Expected identity!'
      );
      $msg = $e->getMessage();
      $this->assertTrue(!empty($msg));
      $otherTopicMap->remove();
    }
  }
  
  public function testOccurrenceCreationIllegalScope() {
    try {
      $otherTopicMap = $this->sharedFixture->createTopicMap('http://localhost/tm/' . uniqid());
      $parent = $this->topicMap->createTopic();
      $parent->createOccurrence(
        $this->topicMap->createTopic(), 
        'Occurrence', 
        parent::$dtString, 
        array($this->topicMap->createTopic(), $otherTopicMap->createTopic(), 'foo')
      );
      $this->fail('Expected a model contraint exception!');
      $otherTopicMap->remove();
    } catch (ModelConstraintException $e) {
      $this->assertEquals(
        $e->getReporter()->getId(), 
        $parent->getId(), 
        'Expected identity!'
      );
      $msg = $e->getMessage();
      $this->assertTrue(!empty($msg));
      $otherTopicMap->remove();
    }
  }
  
  public function testVariantCreationIllegalScope() {
    try {
      $otherTopicMap = $this->sharedFixture->createTopicMap('http://localhost/tm/' . uniqid());
      $topic = $this->topicMap->createTopic();
      $parent = $topic->createName('foo');
      $parent->createVariant(
        'Foo', 
        parent::$dtString, 
        array($this->topicMap->createTopic(), $otherTopicMap->createTopic(), 'bar')
      );
      $this->fail('Expected a model contraint exception!');
      $otherTopicMap->remove();
    } catch (ModelConstraintException $e) {
      $this->assertEquals(
        $e->getReporter()->getId(), 
        $parent->getId(), 
        'Expected identity!'
      );
      $msg = $e->getMessage();
      $this->assertTrue(!empty($msg));
      $otherTopicMap->remove();
    }
  }
  
  public function testRoleCreationIllegalType() {
    try {
      $otherTopicMap = $this->sharedFixture->createTopicMap('http://localhost/tm/' . uniqid());
      $parent = $this->topicMap->createAssociation($this->topicMap->createTopic());
      $parent->createRole($otherTopicMap->createTopic(), $this->topicMap->createTopic());
      $this->fail('Expected a model contraint exception!');
      $otherTopicMap->remove();
    } catch (ModelConstraintException $e) {
      $this->assertEquals(
        $e->getReporter()->getId(), 
        $parent->getId(), 
        'Expected identity!'
      );
      $msg = $e->getMessage();
      $this->assertTrue(!empty($msg));
      $otherTopicMap->remove();
    }
  }
  
  public function testRoleCreationIllegalPlayer() {
    try {
      $otherTopicMap = $this->sharedFixture->createTopicMap('http://localhost/tm/' . uniqid());
      $parent = $this->topicMap->createAssociation($this->topicMap->createTopic());
      $parent->createRole($this->topicMap->createTopic(), $otherTopicMap->createTopic());
      $this->fail('Expected a model contraint exception!');
      $otherTopicMap->remove();
    } catch (ModelConstraintException $e) {
      $this->assertEquals(
        $e->getReporter()->getId(), 
        $parent->getId(), 
        'Expected identity!'
      );
      $msg = $e->getMessage();
      $this->assertTrue(!empty($msg));
      $otherTopicMap->remove();
    }
  }
  
  public function testAssociationIllegalTheme() {
    $this->_testIllegalTheme($this->createAssoc());
  }
  
  public function testOccurrenceIllegalTheme() {
    $this->_testIllegalTheme($this->createOcc());
  }
  
  public function testNameIllegalTheme() {
    $this->_testIllegalTheme($this->createName());
  }
  
  public function testVariantIllegalTheme() {
    $this->_testIllegalTheme($this->createVariant());
  }
  
  public function testAssociationIllegalType() {
    $this->_testIllegalType($this->createAssoc());
  }
  
  public function testRoleIllegalType() {
    $this->_testIllegalType($this->createRole());
  }
  
  public function testOccurrenceIllegalType() {
    $this->_testIllegalType($this->createOcc());
  }
  
  public function testNameIllegalType() {
    $this->_testIllegalType($this->createName());
  }
  
  public function testRoleIllegalPlayer() {
    try {
      $otherTopicMap = $this->sharedFixture->createTopicMap('http://localhost/tm/' . uniqid());
      $role = $this->createRole();
      $role->setPlayer($otherTopicMap->createTopic());
      $this->fail('Expected a model contraint exception!');
      $otherTopicMap->remove();
    } catch (ModelConstraintException $e) {
      $this->assertEquals(
        $e->getReporter()->getId(), 
        $role->getId(), 
        'Expected identity!'
      );
      $msg = $e->getMessage();
      $this->assertTrue(!empty($msg));
      $otherTopicMap->remove();
    }
  }
  
  public function testIllegalTopicType() {
    try {
      $otherTopicMap = $this->sharedFixture->createTopicMap('http://localhost/tm/' . uniqid());
      $topic = $this->topicMap->createTopic();
      $topic->addType($otherTopicMap->createTopic());
      $this->fail('Expected a model contraint exception!');
      $otherTopicMap->remove();
    } catch (ModelConstraintException $e) {
      $this->assertEquals(
        $e->getReporter()->getId(), 
        $topic->getId(), 
        'Expected identity!'
      );
      $msg = $e->getMessage();
      $this->assertTrue(!empty($msg));
      $otherTopicMap->remove();
    }
  }
  
  public function testTopicMapIllegalReifier() {
    $this->_testIllegalReifier($this->topicMap);
  }
  
  public function testAssociationIllegalReifier() {
    $this->_testIllegalReifier($this->createAssoc());
  }
  
  public function testRoleIllegalReifier() {
    $this->_testIllegalReifier($this->createRole());
  }
  
  public function testOccurrenceIllegalReifier() {
    $this->_testIllegalReifier($this->createOcc());
  }
  
  public function testNameIllegalReifier() {
    $this->_testIllegalReifier($this->createName());
  }
  
  public function testVariantIllegalReifier() {
    $this->_testIllegalReifier($this->createVariant());
  }
  
  /**
   * Tests illegal theme adding.
   * 
   * @param Scoped
   * @return void
   */
  private function _testIllegalTheme(Scoped $scoped) {
    try {
      $otherTopicMap = $this->sharedFixture->createTopicMap('http://localhost/tm/' . uniqid());
      $scoped->addTheme($otherTopicMap->createTopic());
      $this->fail('Expected a model contraint exception!');
      $otherTopicMap->remove();
    } catch (ModelConstraintException $e) {
      $this->assertEquals(
        $e->getReporter()->getId(), 
        $scoped->getId(), 
        'Expected identity!'
      );
      $msg = $e->getMessage();
      $this->assertTrue(!empty($msg));
      $otherTopicMap->remove();
    }
  }
  
  /**
   * Tests illegal type setting.
   * 
   * @param Typed
   * @return void
   */
  private function _testIllegalType(Typed $typed) {
    try {
      $otherTopicMap = $this->sharedFixture->createTopicMap('http://localhost/tm/' . uniqid());
      $typed->setType($otherTopicMap->createTopic());
      $this->fail('Expected a model contraint exception!');
      $otherTopicMap->remove();
    } catch (ModelConstraintException $e) {
      $this->assertEquals(
        $e->getReporter()->getId(), 
        $typed->getId(), 
        'Expected identity!'
      );
      $msg = $e->getMessage();
      $this->assertTrue(!empty($msg));
      $otherTopicMap->remove();
    }
  }
  
  /**
   * Tests illegal reifying.
   * 
   * @param Reifiable
   * @return void
   */
  private function _testIllegalReifier(Reifiable $reifiable) {
    try {
      $otherTopicMap = $this->sharedFixture->createTopicMap('http://localhost/tm/' . uniqid());
      $reifiable->setReifier($otherTopicMap->createTopic());
      $this->fail('Expected a model contraint exception!');
      $otherTopicMap->remove();
    } catch (ModelConstraintException $e) {
      $this->assertEquals(
        $e->getReporter()->getId(), 
        $reifiable->getId(), 
        'Expected identity!'
      );
      $msg = $e->getMessage();
      $this->assertTrue(!empty($msg));
      $otherTopicMap->remove();
    }
  }  
}
?>
