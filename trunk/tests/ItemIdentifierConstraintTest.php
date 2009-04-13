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
 * Item identifier constraint tests.
 *
 * @package test
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class ItemIdentifierConstraintTest extends PHPTMAPITestCase {
  
  /**
   * Item identifier constraint test.
   *
   * @param Construct The Topic Maps construct to test.
   * @return void
   */
  protected function _testConstraint(Construct $construct) {
    $tm = $this->topicMap;
    $this->assertEquals(0, count($construct->getItemIdentifiers()), 
      'Expected number of item identifiers to be 0 for newly created construct!');
    $locator1 = 'http://localhost/c/1';
    $locator2 = 'http://localhost/c/2';
    $assoc = $this->createAssoc();
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
  
  public function testTopicMap() {
    $this->assertTrue($this->topicMap instanceof TopicMap);
    $this->_testConstraint($this->topicMap);
  }
  
  public function testAssociation() {
    $this->_testConstraint($this->createAssoc());
  }
  
  public function testRole() {
    $this->_testConstraint($this->createRole());
  }
  
  public function testOccurrence() {
    $this->_testConstraint($this->createOcc());
  }
  
  public function testName() {
    $this->_testConstraint($this->createName());
  }
  
  public function testVariant() {
    $this->_testConstraint($this->createVariant());
  }
  
  public function testTopic() {
    $tm = $this->topicMap;
    $topic1 = $tm->createTopic();
    $locator1 = 'http://localhost/t/1';
    $topic1->addItemIdentifier($locator1);
    $this->assertTrue(in_array($locator1, $topic1->getItemIdentifiers(), true), 
      'Expected item identifier!');
    $topic2 = $tm->createTopic();
    try {
      $topic2->addItemIdentifier($locator1);
      if (!$this->sharedFixture->getFeature('http://tmapi.org/features/automerge/')) {
        $this->fail('Topics with the same item identifier are not allowed!');
      }
    } catch (IdentityConstraintException $e) {
      $this->assertEquals($topic2->getId(), $e->getReporter()->getId());
      $this->assertEquals($topic1->getId(), $e->getExisting()->getId());
      $this->assertEquals($locator1, $e->getLocator());
    }
    if ($this->sharedFixture->getFeature('http://tmapi.org/features/automerge/')) {
      $this->assertEquals(count($tm->getTopics()), 1, 'Topics have not been merged!');
    } else {
      // TODO
    }
  }
}
?>
