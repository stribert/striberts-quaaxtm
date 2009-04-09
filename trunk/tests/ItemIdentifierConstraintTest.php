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
 * Iid constraint tests.
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
  private function _testConstraint(Construct $construct) {
    $tm = $this->topicMap;
    $this->assertEquals(0, count($construct->getItemIdentifiers()), 
      'Expected number of iids to be 0 for newly created construct!');
    $locator1 = 'http://tmapi.org/test#test1';
    $locator2 = 'http://tmapi.org/test#test2';
    $assoc = $this->createAssoc();
    $assoc->addItemIdentifier($locator1);
    $this->assertFalse(in_array($locator1, $construct->getItemIdentifiers(), true), 
      'Unexpected iid!');
    try {
      $construct->addItemIdentifier($locator1);
      $this->fail('Topic Maps constructs with the same iid are not allowed!');
    } catch (IdentityConstraintException $e) {
      $this->assertEquals($construct->getId(), $e->getReporter()->getId());
      $this->assertEquals($assoc->getId(), $e->getExisting()->getId());
      $this->assertEquals($locator1, $e->getLocator());
    }
    $construct->addItemIdentifier($locator2);
    $this->assertTrue(in_array($locator2, $construct->getItemIdentifiers(), true), 
      'Unexpected iid!');
    $construct->removeItemIdentifier($locator2);
    $assoc->removeItemIdentifier($locator1);
    $this->assertFalse(in_array($locator1, $assoc->getItemIdentifiers(), true), 
      'Unexpected iid!');
    $construct->addItemIdentifier($locator1);
    $this->assertTrue(in_array($locator1, $construct->getItemIdentifiers(), true), 
      'Unexpected iid!');
    if (!$construct instanceof TopicMap) {
      // removal should free the iid
      $construct->remove();
      $assoc->addItemIdentifier($locator1);
      $this->assertTrue(in_array($locator1, $assoc->getItemIdentifiers(), true), 
        'Unexpected iid!');
    }
  }
  
  public function testTopicMap() {
    $this->assertTrue($this->topicMap instanceof TopicMap);
    $this->_testConstraint($this->topicMap);
  }
  
  public function testAssociation() {
    $this->_testConstraint($this->createAssoc());
  }
}
?>
