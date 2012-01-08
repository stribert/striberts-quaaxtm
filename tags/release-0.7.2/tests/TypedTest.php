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
 * Typed construct tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class TypedTest extends PHPTMAPITestCase
{
  /**
   * The typed test.
   * 
   * @param Typed A typed construct.
   * @return void
   */
  private function _testTyped(Typed $typed)
  {
    $prevType = $typed->getType();
    $this->assertNotNull($prevType, 'Expected a type!');
    $type = $this->_topicMap->createTopic();
    $typed->setType($type);
    $this->assertEquals($type->getId(), $typed->getType()->getId(), 'Expected identity!');
    $typed->setType($prevType);
    $this->assertEquals($prevType->getId(), $typed->getType()->getId(), 
      'Expected identity!');
  }
  
  public function testTopicMap()
  {
    $this->assertTrue($this->_topicMap instanceof TopicMap);
  }
  
  public function testAssociation()
  {
    $this->_testTyped($this->_createAssoc());
  }
  
  public function testRole()
  {
    $this->_testTyped($this->_createRole());
  }
  
  public function testOccurrence()
  {
    $this->_testTyped($this->_createOcc());
  }
  
  public function testName()
  {
    $this->_testTyped($this->_createName());
  }
}
?>
