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
 * QuaaxTM PropertyHolder object tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class QTMPropertyHolderTest extends PHPTMAPITestCase
{
  public function testTopicMap()
  {
    $this->assertTrue($this->_topicMap instanceof TopicMap);
  }
  
  public function testName()
  {
    $parent = $this->_topicMap->createTopic();
    $type1 = $this->_topicMap->createTopic();
    $type2 = $this->_topicMap->createTopic();
    // second iteration returns an existing name
    for ($i=0;$i<2;$i++) {
      $name = $parent->createName('Testname', $type1);
      $this->assertTrue($name instanceof Name, 'Expected a name!');
      $this->assertEquals(count($parent->getNames()), 1, 'Expected 1 name!');
      $this->assertEquals($name->getValue(), 'Testname', 'Expected identity!');
      $this->assertEquals(
        $name->getType()->getId(), 
        $type1->getId(), 
        'Expected identity!'
      );
      $name->setValue('Name');
      $this->assertEquals($name->getValue(), 'Name', 'Expected identity!');
      $name->setType($type2);
      $this->assertEquals(
        $name->getType()->getId(), 
        $type2->getId(), 
        'Expected identity!'
      );
      $name->setValue('Testname');
      $this->assertEquals($name->getValue(), 'Testname', 'Expected identity!');
      $name->setType($type1);
      $this->assertEquals(
        $name->getType()->getId(), 
        $type1->getId(), 
        'Expected identity!'
      );
    }
    unset($name);
    $names = $parent->getNames();
    $this->assertEquals(count($names), 1, 'Expected 1 name!');
    $restoredName = $names[0];
    $this->assertTrue($restoredName instanceof Name, 'Expected a name!');
    $this->assertEquals($restoredName->getValue(), 'Testname', 'Expected identity!');
    $this->assertEquals($restoredName->getType()->getId(), $type1->getId(), 
      'Expected identity!');
  }
  
  public function testOccurrence()
  {
    $parent = $this->_topicMap->createTopic();
    $type1 = $this->_topicMap->createTopic();
    $type2 = $this->_topicMap->createTopic();
    // second iteration returns an existing occurrence
    for ($i=0;$i<2;$i++) {
      $occ = $parent->createOccurrence($type1, 'New occ', parent::$_dtString);
      $this->assertTrue($occ instanceof Occurrence, 'Expected an occurrence!');
      $this->assertEquals(count($parent->getOccurrences()), 1, 'Expected 1 occurrence!');
      $this->assertEquals($occ->getValue(), 'New occ', 'Expected identity!');
      $this->assertEquals(
        $occ->getType()->getId(), 
        $type1->getId(), 
        'Expected identity!'
      );
      $this->assertEquals($occ->getDatatype(), parent::$_dtString, 'Expected identity!');
      $occ->setValue('http://example.org/', parent::$_dtUri);
      $this->assertEquals($occ->getValue(), 'http://example.org/', 'Expected identity!');
      $this->assertEquals($occ->getDatatype(), parent::$_dtUri, 'Expected identity!');
      $occ->setType($type2);
      $this->assertEquals(
        $occ->getType()->getId(), 
        $type2->getId(), 
        'Expected identity!'
      );
      $occ->setValue('New occ', parent::$_dtString);
      $this->assertEquals($occ->getValue(), 'New occ', 'Expected identity!');
      $this->assertEquals($occ->getDatatype(), parent::$_dtString, 'Expected identity!');
      $occ->setType($type1);
      $this->assertEquals(
        $occ->getType()->getId(), 
        $type1->getId(), 
        'Expected identity!'
      );
    }
    unset($occ);
    $occs = $parent->getOccurrences();
    $this->assertEquals(count($occs), 1, 'Expected 1 occurrence!');
    $restoredOcc = $occs[0];
    $this->assertTrue($restoredOcc instanceof Occurrence, 'Expected an occurrence!');
    $this->assertEquals($restoredOcc->getValue(), 'New occ', 'Expected identity!');
    $this->assertEquals($restoredOcc->getDatatype(), parent::$_dtString, 'Expected identity!');
    $this->assertEquals(
      $restoredOcc->getType()->getId(), 
      $type1->getId(), 
      'Expected identity!'
    );
  }
  
  public function testRole()
  {
    $parent = $this->_createAssoc();
    $roleType = $this->_topicMap->createTopic();
    $newRoleType = $this->_topicMap->createTopic();
    $player = $this->_topicMap->createTopic();
    $newPlayer = $this->_topicMap->createTopic();
    // second iteration returns an existing role
    for ($i=0;$i<2;$i++) {
      $role = $parent->createRole($roleType, $player);
      $this->assertTrue($role instanceof Role, 'Expected a role!');
      $roles = $parent->getRoles();
      $this->assertEquals(count($roles), 1, 'Expected 1 role!');
      $this->assertEquals(
        $role->getType()->getId(), 
        $roleType->getId(), 
        'Expected identity!'
      );
      $this->assertEquals(
        $role->getPlayer()->getId(), 
        $player->getId(), 
        'Expected identity!'
      );
      $role->setType($newRoleType);
      $role->setPlayer($newPlayer);
      $this->assertEquals(
        $role->getType()->getId(), 
        $newRoleType->getId(), 
        'Expected identity!'
      );
      $this->assertEquals(
        $role->getPlayer()->getId(), 
        $newPlayer->getId(), 
        'Expected identity!'
      );
      $role->setType($roleType);
      $role->setPlayer($player);
      $this->assertEquals(
        $role->getType()->getId(), 
        $roleType->getId(), 
        'Expected identity!'
      );
      $this->assertEquals(
        $role->getPlayer()->getId(), 
        $player->getId(), 
        'Expected identity!'
      );
    }
    unset($role);
    $roles = $parent->getRoles();
    $this->assertEquals(count($roles), 1, 'Expected 1 role!');
    $restoredRole = $roles[0];
    $this->assertTrue($restoredRole instanceof Role, 'Expected a role!');
    $this->assertEquals(
      $restoredRole->getType()->getId(), 
      $roleType->getId(), 
      'Expected identity!'
    );
    $this->assertEquals(
      $restoredRole->getPlayer()->getId(), 
      $player->getId(), 
      'Expected identity!'
    );
  }
  
  public function testVariant()
  {
    $parent = $this->_createName();
    $scope = array($this->_topicMap->createTopic());
    // second iteration returns an existing variant
    for ($i=0;$i<2;$i++) {
      $variant = $parent->createVariant('New variant', parent::$_dtString, $scope);
      $this->assertTrue($variant instanceof IVariant, 'Expected a variant!');
      $this->assertEquals(count($parent->getVariants()), 1, 'Expected 1 variant!');
      $this->assertEquals($variant->getValue(), 'New variant', 'Expected identity!');
      $this->assertEquals($variant->getDatatype(), parent::$_dtString, 'Expected identity!');
      $variant->setValue('http://example.org/', parent::$_dtUri);
      $this->assertEquals($variant->getValue(), 'http://example.org/', 'Expected identity!');
      $this->assertEquals($variant->getDatatype(), parent::$_dtUri, 'Expected identity!');
      $variant->setValue('New variant', parent::$_dtString);
      $this->assertEquals($variant->getValue(), 'New variant', 'Expected identity!');
      $this->assertEquals($variant->getDatatype(), parent::$_dtString, 'Expected identity!');
    }
    unset($variant);
    $variants = $parent->getVariants();
    $this->assertEquals(count($variants), 1, 'Expected 1 variant!');
    $restoredVariant = $variants[0];
    $this->assertTrue($restoredVariant instanceof IVariant, 'Expected a variant!');
    $this->assertEquals($restoredVariant->getValue(), 'New variant', 'Expected identity!');
    $this->assertEquals($restoredVariant->getDatatype(), parent::$_dtString, 'Expected identity!');
  }
  
  public function testAssociation()
  {
    $type = $this->_topicMap->createTopic();
    $newType = $this->_topicMap->createTopic();
    // second iteration returns an existing association
    for ($i=0;$i<2;$i++) {
      $assoc = $this->_topicMap->createAssociation($type);
      $this->assertTrue($assoc instanceof Association, 'Expected an association!');
      $assocs = $this->_topicMap->getAssociations();
      $this->assertEquals(count($assocs), 1, 'Expected 1 association!');
      $this->assertEquals(
        $assoc->getType()->getId(), 
        $type->getId(), 
        'Expected identity!'
      );
      $assoc->setType($newType);
      $this->assertEquals(
        $assoc->getType()->getId(), 
        $newType->getId(), 
        'Expected identity!'
      );
      $assoc->setType($type);
      $this->assertEquals(
        $assoc->getType()->getId(), 
        $type->getId(), 
        'Expected identity!'
      );
    }
    unset($assoc);
    $assocs = $this->_topicMap->getAssociations();
    $this->assertEquals(count($assocs), 1, 'Expected 1 association!');
    $restoredAssoc = $assocs[0];
    $this->assertTrue($restoredAssoc instanceof Association, 'Expected an association!');
    $this->assertEquals(
      $restoredAssoc->getType()->getId(), 
      $type->getId(), 
      'Expected identity!'
    );
  }
}
?>
