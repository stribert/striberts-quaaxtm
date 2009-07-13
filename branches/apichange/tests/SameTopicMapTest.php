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

require_once('PHPTMAPITestCase.php');

/**
 * "Same topic map" constraint tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class SameTopicMapTest extends PHPTMAPITestCase {
  
  protected static $tmLocator2 = 'http://localhost/tm/2';
  
  private $tm1,
          $tm2;
  
  /**
   * @override
   */
  public function setUp() {
    parent::setUp();
    $this->tm1 = $this->topicMap;
    $this->tm2 = $this->sharedFixture->createTopicMap(self::$tmLocator2);
  }
  
  /**
   * @override
   */
  public function tearDown() {
    parent::tearDown();
    $this->tm2 = null;
  }

  public function testTopicMap() {
    $this->assertTrue($this->tm1 instanceof TopicMap);
    $this->assertTrue($this->tm2 instanceof TopicMap);
  }
  
  public function testAssociationCreationIllegalType() {
    try {
      $this->tm1->createAssociation($this->tm2->createTopic());
      $this->fail('Expected a model contraint exception!');
    } catch (ModelConstraintException $e) {
      $this->assertEquals($e->getReporter()->getId(), $this->tm1->getId(), 
        'Expected identity!');
    }
  }
  
  public function testAssociationCreationIllegalScope() {
    try {
      $this->tm1->createAssociation($this->tm1->createTopic(), 
        array($this->tm1->createTopic(), $this->tm2->createTopic()));
      $this->fail('Expected a model contraint exception!');
    } catch (ModelConstraintException $e) {
      $this->assertEquals($e->getReporter()->getId(), $this->tm1->getId(), 
        'Expected identity!');
    }
  }
  
  public function testNameCreationIllegalType() {
    try {
      $parent = $this->tm1->createTopic();
      $parent->createName('Name', $this->tm2->createTopic());
      $this->fail('Expected a model contraint exception!');
    } catch (ModelConstraintException $e) {
      $this->assertEquals($e->getReporter()->getId(), $parent->getId(), 'Expected identity!');
    }
  }
  
  public function testNameCreationIllegalScope() {
    try {
      $parent = $this->tm1->createTopic();
      $parent->createName('Name', $this->tm1->createTopic(), 
        array($this->tm1->createTopic(), $this->tm2->createTopic()));
      $this->fail('Expected a model contraint exception!');
    } catch (ModelConstraintException $e) {
      $this->assertEquals($e->getReporter()->getId(), $parent->getId(), 'Expected identity!');
    }
  }
  
  public function testOccurrenceCreationIllegalType() {
    try {
      $parent = $this->tm1->createTopic();
      $parent->createOccurrence($this->tm2->createTopic(), 'Occurrence', parent::$dtString);
      $this->fail('Expected a model contraint exception!');
    } catch (ModelConstraintException $e) {
      $this->assertEquals($e->getReporter()->getId(), $parent->getId(), 'Expected identity!');
    }
  }
  
  public function testOccurrenceCreationIllegalScope() {
    try {
      $parent = $this->tm1->createTopic();
      $parent->createOccurrence($this->tm1->createTopic(), 'Occurrence', parent::$dtString, 
        array($this->tm1->createTopic(), $this->tm2->createTopic()));
      $this->fail('Expected a model contraint exception!');
    } catch (ModelConstraintException $e) {
      $this->assertEquals($e->getReporter()->getId(), $parent->getId(), 'Expected identity!');
    }
  }
  
  public function testRoleCreationIllegalType() {
    try {
      $parent = $this->tm1->createAssociation($this->tm1->createTopic());
      $parent->createRole($this->tm2->createTopic(), $this->tm1->createTopic());
      $this->fail('Expected a model contraint exception!');
    } catch (ModelConstraintException $e) {
      $this->assertEquals($e->getReporter()->getId(), $parent->getId(), 'Expected identity!');
    }
  }
  
  public function testRoleCreationIllegalPlayer() {
    try {
      $parent = $this->tm1->createAssociation($this->tm1->createTopic());
      $parent->createRole($this->tm1->createTopic(), $this->tm2->createTopic());
      $this->fail('Expected a model contraint exception!');
    } catch (ModelConstraintException $e) {
      $this->assertEquals($e->getReporter()->getId(), $parent->getId(), 'Expected identity!');
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
  
  /**
   * Tests illegal add theme.
   * 
   * @param ScopedImpl
   * @return void
   */
  private function _testIllegalTheme(Scoped $scoped) {
    try {
      $scoped->addTheme($this->tm2->createTopic());
      $this->fail('Expected a model contraint exception!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
  }
  
}
?>
