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
 * Construct tests.
 *
 * @package test
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class ConstructTest extends PHPTMAPITestCase {
  
  /**
   * Tests adding / removing item identifiers, retrieval by item identifier 
   * and retrieval by the system specific id.
   *
   * @param Construct The Topic Maps construct to test.
   * @return void
   */
  private function _testConstruct(Construct $construct) {
    $tm = $this->topicMap;
    $this->assertEquals(0, count($construct->getItemIdentifiers()), 
      'Expected number of iids to be 0 for newly created construct!');
    $locator1 = 'http://tmapi.org/test#test1';
    $locator2 = 'http://tmapi.org/test#test2';
    $construct->addItemIdentifier($locator1);
    $this->assertEquals(1, count($construct->getItemIdentifiers()), 
      'Expected 1 iid!');
    $construct->addItemIdentifier($locator2);
    $this->assertEquals(2, count($construct->getItemIdentifiers()), 
      'Expected 2 iids!');
    $this->assertTrue(in_array($locator1, $construct->getItemIdentifiers(), true), 
      'Expected iid!');
    $this->assertTrue(in_array($locator2, $construct->getItemIdentifiers(), true), 
      'Expected iid!');
    $this->assertEquals($construct->getId(), 
      $tm->getConstructByItemIdentifier($locator1)->getId(), 'Unexpected construct!');
    $this->assertEquals($construct->getId(), 
      $tm->getConstructByItemIdentifier($locator2)->getId(), 'Unexpected construct!');
    $this->assertEquals($construct->hashCode(), 
      $tm->getConstructByItemIdentifier($locator1)->hashCode(), 'Unexpected construct!');
    $this->assertEquals($construct->hashCode(), 
      $tm->getConstructByItemIdentifier($locator2)->hashCode(), 'Unexpected construct!');
    $construct->removeItemIdentifier($locator1);
    $this->assertEquals(1, count($construct->getItemIdentifiers()), 
      'Iid was not removed!');
    $construct->removeItemIdentifier($locator2);
    $this->assertEquals(0, count($construct->getItemIdentifiers()), 
      'Iid was not removed!');
    $this->assertFalse(in_array($locator1, $construct->getItemIdentifiers(), true), 
      'Expected iid not to be returned!');
    $this->assertFalse(in_array($locator2, $construct->getItemIdentifiers(), true), 
      'Expected iid not to be returned!');
    $this->assertTrue(is_null($tm->getConstructByItemIdentifier($locator1)), 
      'Got a construct even if the iid is unassigned!');
    try {
      $construct->addItemIdentifier(null);
      $this->fail('addItemIdentifier(null) is illegal!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
    if ($construct instanceof TopicMap) {
      $this->assertNull($construct->getParent(), 'Topic map has no parent!');
    } else {
      $this->assertNotNull($construct->getParent(), 'Topic Maps constructs have a parent!');
    }
    $this->assertEquals($this->topicMap->getId(), $construct->getTopicMap()->getId(), 
      'Construct belongs to wrong topic map!');
    $id = $construct->getId();
    $this->assertEquals($construct->getId(), $tm->getConstructById($id)->getId(), 
      'Unexpected construct!');
  }
  
  public function testTopicMap() {
    $this->assertTrue($this->topicMap instanceof TopicMap);
    $this->_testConstruct($this->topicMap);
  }
  
  public function testTopic() {
    // Avoid that the topic has an item identifier
    $this->_testConstruct($this->topicMap
      ->createTopicBySubjectIdentifier('http://tmapi.org/test#topic1'));
  }
  
  public function testAssociation() {
    $this->_testConstruct($this->createAssoc());
  }
  
  public function testRole() {
    $this->_testConstruct($this->createRole());
  }
  
  public function testOccurrence() {
    $this->_testConstruct($this->createOcc());
  }
  
  public function testName() {
    $this->_testConstruct($this->createName());
  }
  
  public function testVariant() {
    $this->_testConstruct($this->createVariant());
  }
}
?>
