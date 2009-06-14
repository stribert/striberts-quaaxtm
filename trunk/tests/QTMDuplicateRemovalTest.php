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
 * QuaaxTM duplicate removal tests.
 *
 * @package test
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class QTMDuplicateRemovalTest extends PHPTMAPITestCase {
  
  public function testTopicMap() {
    $this->assertTrue($this->topicMap instanceof TopicMap);
  }
  
  public function testName() {
    $tm = $this->topicMap;
    $parent = $tm->createTopic();
    $theme1 = $tm->createTopic();
    $theme2 = $tm->createTopic();
    $this->assertEquals(count($parent->getNames()), 0, 
      'Expected new topic to be created without names!');
    $name1 = $parent->createName('Name1', array($theme1, $theme2));
    $name2 = $parent->createName('Name2');
    $this->assertEquals($name1->getParent()->getId(), $parent->getId(), 
      'Unexpected name parent after creation!');
    $this->assertEquals($name2->getParent()->getId(), $parent->getId(), 
      'Unexpected name parent after creation!');
    $this->assertEquals(count($parent->getNames()), 2, 'Expected 2 names!');
    $ids = $this->getIdsOfConstructs($parent->getNames());
    $this->assertTrue(in_array($name1->getId(), $ids, true), 
      'Name is not part of getNames()!');
    $this->assertTrue(in_array($name2->getId(), $ids, true), 
      'Name is not part of getNames()!');
    // make dupl. (both have default name type)
    $name2->setValue('Name1');
    $name2->addTheme($theme1);
    $name2->addTheme($theme2);
    $parent->finished($name2);
    
    $this->assertEquals(count($parent->getNames()), 1, 'Expected 1 name after duplicate removal!');
    $names = $parent->getNames();
    $name = $names[0];
    $this->assertEquals($name->getParent()->getId(), $parent->getId(), 'Unexpected name parent!');
    $this->assertEquals($name->getValue(), 'Name1', 'Unexpected value!');
    $scope = $name->getScope();
    $this->assertEquals(count($scope), 2, 'Expected 2 themes!');
    $ids = $this->getIdsOfConstructs($scope);
    $this->assertTrue(in_array($theme1->getId(), $ids, true), 
      'Topic is not part of getScope()!');
    $this->assertTrue(in_array($theme2->getId(), $ids, true), 
      'Topic is not part of getScope()!');
    $type = $name->getType();
    $sids = $type->getSubjectIdentifiers();
    $this->assertTrue(count($sids)>0, 'Expected 1 subject identifier minimum!');
    $this->assertTrue(in_array(VocabularyUtils::TMDM_PSI_DEFAULT_NAME_TYPE, $sids, true), 
      'Expected subject identifier for the default name type!');
  }
  
  public function testVariant() {
    $tm = $this->topicMap;
    $topic = $tm->createTopic();
    $parent = $topic->createName('Name');
    $theme1 = $tm->createTopic();
    $theme2 = $tm->createTopic();
    $this->assertEquals(count($parent->getVariants()), 0, 
      'Expected new name to be created without variants!');
    $variant1 = $parent->createVariant('Variant1', parent::$dtString, array($theme1, $theme2));
    $variant2 = $parent->createVariant('Variant2', parent::$dtString, array($theme1));
    $this->assertEquals($variant1->getParent()->getId(), $parent->getId(), 
      'Unexpected variant parent after creation!');
    $this->assertEquals($variant2->getParent()->getId(), $parent->getId(), 
      'Unexpected variant parent after creation!');
    $this->assertEquals(count($parent->getVariants()), 2, 'Expected 2 variants!');
    $ids = $this->getIdsOfConstructs($parent->getVariants());
    $this->assertTrue(in_array($variant1->getId(), $ids, true), 
      'Variant is not part of getVariants()!');
    $this->assertTrue(in_array($variant2->getId(), $ids, true), 
      'Variant is not part of getVariants()!');
    // make dupl.
    $variant2->setValue('Variant1', parent::$dtString);
    $variant2->addTheme($theme2);
    $parent->finished($variant2);
    
    $this->assertEquals(count($parent->getVariants()), 1, 
      'Expected 1 variant after duplicate removal!');
    $variants = $parent->getVariants();
    $variant = $variants[0];
    $this->assertEquals($variant->getParent()->getId(), $parent->getId(), 
      'Unexpected variant parent!');
    $this->assertEquals($variant->getValue(), 'Variant1', 'Unexpected value!');
    $this->assertEquals($variant->getDatatype(), parent::$dtString, 'Unexpected datatype!');
    $scope = $variant->getScope();
    $this->assertEquals(count($scope), 2, 'Expected 2 themes!');
    $ids = $this->getIdsOfConstructs($scope);
    $this->assertTrue(in_array($theme1->getId(), $ids, true), 
      'Topic is not part of getScope()!');
    $this->assertTrue(in_array($theme2->getId(), $ids, true), 
      'Topic is not part of getScope()!');
  }
  
  public function testOccurrence() {
    $tm = $this->topicMap;
    $parent = $tm->createTopic();
    $type = $tm->createTopic();
    $theme1 = $tm->createTopic();
    $theme2 = $tm->createTopic();
    $this->assertEquals(count($parent->getOccurrences()), 0, 
      'Expected new topic to be created without occurrences!');
    $occ1 = $parent->createOccurrence($type, 'occ1', parent::$dtString, array($theme1, $theme2));
    $occ2 = $parent->createOccurrence($tm->createTopic(), 'http://quaaxtm.sf.net/', parent::$dtUri);
    $this->assertEquals($occ1->getParent()->getId(), $parent->getId(), 
      'Unexpected occurrence parent after creation!');
    $this->assertEquals($occ2->getParent()->getId(), $parent->getId(), 
      'Unexpected occurrence parent after creation!');
    $this->assertEquals(count($parent->getOccurrences()), 2, 'Expected 2 occurrences!');
    $ids = $this->getIdsOfConstructs($parent->getOccurrences());
    $this->assertTrue(in_array($occ1->getId(), $ids, true), 
      'Occurrence is not part of getOccurrences()!');
    $this->assertTrue(in_array($occ2->getId(), $ids, true), 
      'Occurrence is not part of getOccurrences()!');
    // make dupl.
    $occ2->setValue('occ1', parent::$dtString);
    $occ2->setType($type);
    $occ2->addTheme($theme1);
    $occ2->addTheme($theme2);
    $parent->finished($occ2);
    
    $this->assertEquals(count($parent->getOccurrences()), 1, 
      'Expected 1 occurrence after duplicate removal!');
    $occs = $parent->getOccurrences();
    $occ = $occs[0];
    $this->assertEquals($occ->getParent()->getId(), $parent->getId(), 
      'Unexpected occurrence parent!');
    $this->assertEquals($occ->getType()->getId(), $type->getId(), 'Unexpected type!');
    $this->assertEquals($occ->getValue(), 'occ1', 'Unexpected value!');
    $this->assertEquals($occ->getDatatype(), parent::$dtString, 'Unexpected datatype!');
    $scope = $occ->getScope();
    $this->assertEquals(count($scope), 2, 'Expected 2 themes!');
    $ids = $this->getIdsOfConstructs($scope);
    $this->assertTrue(in_array($theme1->getId(), $ids, true), 
      'Topic is not part of getScope()!');
    $this->assertTrue(in_array($theme2->getId(), $ids, true), 
      'Topic is not part of getScope()!');
  }
  
  public function testAssociation() {
    $tm = $parent = $this->topicMap;
    $assocType = $tm->createTopic();
    $roleType = $tm->createTopic();
    $player = $tm->createTopic();
    $theme1 = $tm->createTopic();
    $theme2 = $tm->createTopic();
    $this->assertEquals(count($parent->getAssociations()), 0, 
      'Expected new topic to be created without associations!');
    $assoc1 = $parent->createAssociation($assocType, array($theme1, $theme2));
    $assoc2 = $parent->createAssociation($tm->createTopic());
    $this->assertEquals($assoc1->getParent()->getId(), $parent->getId(), 
      'Unexpected association parent after creation!');
    $this->assertEquals($assoc2->getParent()->getId(), $parent->getId(), 
      'Unexpected association parent after creation!');
    $this->assertEquals(count($parent->getAssociations()), 2, 'Expected 2 associations!');
    $ids = $this->getIdsOfConstructs($parent->getAssociations());
    $this->assertTrue(in_array($assoc1->getId(), $ids, true), 
      'Association is not part of getAssociations()!');
    $this->assertTrue(in_array($assoc2->getId(), $ids, true), 
      'Association is not part of getAssociations()!');
    $role = $assoc1->createRole($roleType, $player);
    $this->assertEquals(count($assoc1->getRoles()), 1, 'Expected 1 role!');
    // make dupl.
    $assoc2->setType($assocType);
    $assoc2->addTheme($theme1);
    $assoc2->addTheme($theme2);
    $assoc2->createRole($roleType, $player);
    $parent->finished($assoc2);
    
    $this->assertEquals(count($parent->getAssociations()), 1, 
      'Expected 1 association after duplicate removal!');
    $assocs = $parent->getAssociations();
    $assoc = $assocs[0];
    $this->assertEquals($assoc->getParent()->getId(), $parent->getId(), 
      'Unexpected association parent!');
    $this->assertEquals($assoc->getType()->getId(), $assocType->getId(), 'Unexpected type!');
    $scope = $assoc->getScope();
    $this->assertEquals(count($scope), 2, 'Expected 2 themes!');
    $ids = $this->getIdsOfConstructs($scope);
    $this->assertTrue(in_array($theme1->getId(), $ids, true), 
      'Topic is not part of getScope()!');
    $this->assertTrue(in_array($theme2->getId(), $ids, true), 
      'Topic is not part of getScope()!');
    $this->assertEquals(count($assoc->getRoles()), 1, 'Expected 1 role!');
    $roles = $assoc->getRoles();
    $role = $roles[0];
    $this->assertEquals($role->getType()->getId(), $roleType->getId(), 'Unexpected type!');
    $this->assertEquals($role->getPlayer()->getId(), $player->getId(), 'Unexpected player!');
  }
}
?>
