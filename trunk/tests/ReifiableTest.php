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
 * Reifiable tests.
 *
 * @package test
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class ReifiableTest extends PHPTMAPITestCase {
  
  /**
   * Tests setting / getting the reifier for the <var>reifiable</var>.
   * 
   * @param Reifiable The reifiable to run the tests against.
   * @return void
   */
  private function _testReification(Reifiable $reifiable) {
    $this->assertNull($reifiable->getReifier(), 'Unexpected reifier property!');
    $reifier = $this->topicMap->createTopic();
    $this->assertNull($reifier->getReified(), 'Unexpected reified property!');
    $reifiable->setReifier($reifier);
    $this->assertEquals($reifier->getId(), $reifiable->getReifier()->getId(), 
      'Unexpected reifier property!');
    $this->assertEquals($reifiable->getId(), $reifier->getReified()->getId(), 
      'Unexpected reified property!');
    $reifiable->setReifier(null);
    $this->assertNull($reifiable->getReifier(), 'Reifier should be null!');
    $this->assertNull($reifier->getReified(), 'Reifiable should be null!');
    $reifiable->setReifier($reifier);
    $this->assertEquals($reifier->getId(), $reifiable->getReifier()->getId(), 
      'Unexpected reifier property!');
    $this->assertEquals($reifiable->getId(), $reifier->getReified()->getId(), 
      'Unexpected reified property!');
    try {
      // Re-assigning the reifier is allowed; the TM processor MUST NOT raise an exception
      $reifiable->setReifier($reifier);
    } catch (ModelConstraintException $e) {
      $this->fail('Unexpected exception while setting the reifier to the same value!');
    }
    $this->assertEquals($reifier->getId(), $reifiable->getReifier()->getId(), 
      'Unexpected reifier property!');
    $this->assertEquals($reifiable->getId(), $reifier->getReified()->getId(), 
      'Unexpected reified property!');
  }
  
  /**
   * Tests if a reifier collision (the reifier is already assigned to another 
   * construct) is detected.
   * 
   * @param Reifiable The reifiable to run the tests against.
   * @return void
   */
  private function _testReificationCollision(Reifiable $reifiable) {
    $this->assertNull($reifiable->getReifier(), 'Unexpected reifier property!');
    $reifier = $this->topicMap->createTopic();
    $this->assertNull($reifier->getReified(), 'Unexpected reified property!');
    $otherReifiable = $this->createAssoc();
    $otherReifiable->setReifier($reifier);
    $this->assertEquals($reifier->getId(), $otherReifiable->getReifier()->getId(), 
      'Unexpected reifier property!');
    $this->assertEquals($otherReifiable->getId(), $reifier->getReified()->getId(), 
      'Unexpected reified property!');
    try {
      $reifiable->setReifier($reifier);
      $this->fail('The reifier already reifies another construct!');
    } catch (ModelConstraintException $e) {
      $this->assertEquals($reifiable->getId(), $e->getReporter()->getId());
    }
    $otherReifiable->setReifier(null);
    $this->assertNull($otherReifiable->getReifier(), 'Reifier should be null!');
    $this->assertNull($reifier->getReified(), 'Reifiable should be null!');
    $reifiable->setReifier($reifier);
    $this->assertEquals($reifier->getId(), $reifiable->getReifier()->getId(), 
      'Reifier property should have been changed!');
    $this->assertEquals($reifiable->getId(), $reifier->getReified()->getId(), 
      'Reified property should have been changed!');
  }
  
  public function testTopicMap() {
    $this->_testReification($this->topicMap);
  }
  
  public function testTopicMapReifierCollision() {
    $this->_testReificationCollision($this->topicMap);
  }
  
  public function testAssociation() {
    $this->_testReification($this->createAssoc());
  }
  
  public function testAssociationReifierCollision() {
    $this->_testReificationCollision($this->createAssoc());
  }
  
  public function testRole() {
    $this->_testReification($this->createRole());
  }
  
  public function testRoleReifierCollision() {
    $this->_testReificationCollision($this->createRole());
  }
  
  public function testOccurrence() {
    $this->_testReification($this->createOcc());
  }
  
  public function testOccurrenceReifierCollision() {
    $this->_testReificationCollision($this->createOcc());
  }
  
  public function testName() {
    $this->_testReification($this->createName());
  }
  
  public function testNameReifierCollision() {
    $this->_testReificationCollision($this->createName());
  }
  
  public function testVariant() {
    $this->_testReification($this->createVariant());
  }
  
  public function testVariantReifierCollision() {
    $this->_testReificationCollision($this->createVariant());
  }
}
?>
