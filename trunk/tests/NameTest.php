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
 * Name tests.
 *
 * @package test
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class NameTest extends PHPTMAPITestCase {
  
  public function testTopicMap() {
    $this->assertTrue($this->topicMap instanceof TopicMap);
  }
  
  public function testParent() {
    $parent = $this->topicMap->createTopic();
    $this->assertEquals(count($parent->getNames()), 0, 
      'Expected new topic to be created without names!');
    $name = $parent->createName('Name');
    $this->assertEquals($name->getParent()->getId(), $parent->getId(), 
      'Unexpected name parent after creation!');
    $this->assertEquals(count($parent->getNames()), 1, 'Expected 1 name!');
    $ids = $this->getIdsOfConstructs($parent->getNames());
    $this->assertTrue(in_array($name->getId(), $ids, true), 
      'Name is not part of getNames()!');
    $name->remove();
    $this->assertEquals(count($parent->getNames()), 0, 'Expected 0 names after removal!');
  }
  
  public function testDefaultNameType() {
    $name = $this->createName();
    $defaultType = $name->getType();
    $sids = $defaultType->getSubjectIdentifiers();
    $this->assertTrue(in_array('http://psi.topicmaps.org/iso13250/model/topic-name', 
      $sids));
  }
  
  public function testValue() {
    $value1 = 'PHPTMAPI name';
    $value2 = 'Süßer Name';
    $name = $this->createName();
    $this->assertTrue($name instanceof Name);
    $name->setValue($value1);
    $this->assertEquals($name->getValue(), $value1);
    $name->setValue($value2);
    $this->assertEquals($name->getValue(), $value2);
    try {
      $name->setValue(null);
      $this->fail('setValue(null) is not allowed!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
    $this->assertEquals($name->getValue(), $value2);
  }
  
  public function testVariantValueDatatype() {
    $name = $this->createName();
    $this->assertTrue($name instanceof Name);
    $theme = $this->topicMap->createTopic();
    $dtString = 'http://www.w3.org/2001/XMLSchema#string';
    try {
      $name->createVariant(null, $dtString, array($theme));
      $this->fail('null is not allowed as value!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
    try {
      $name->createVariant('Variant', null, array($theme));
      $this->fail('null is not allowed as datatype!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
    try {
      $name->createVariant(null, null, array($theme));
      $this->fail('Variants have a value and a datatype != null!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
    $variant = $name->createVariant('Variant', $dtString, array($theme));
    $this->assertEquals($variant->getValue(), 'Variant', 'Values are different!');
    $this->assertEquals($variant->getDatatype(), $dtString, 'Datatypes are different!');
    $variant = $name->createVariant('Variant Variant', $dtString, array($theme));
    $this->assertEquals($variant->getValue(), 'Variant Variant', 'Values are different!');
    $this->assertEquals(count($variant->getScope()), 1, 'Expected 1 theme in scope!');
    $ids = $this->getIdsOfConstructs($variant->getScope());
    $this->assertTrue(in_array($theme->getId(), $ids, true), 
      'Theme is not part of getScope()!');
  }
  
  public function testVariantScope() {
    $name = $this->createName();
    $this->assertTrue($name instanceof Name);
    $dtString = 'http://www.w3.org/2001/XMLSchema#string';
    $theme1 = $this->topicMap->createTopic();
    $theme2 = $this->topicMap->createTopic();
    $theme3 = $this->topicMap->createTopic();
    $name->addTheme($theme1);
    try {
      $variant = $name->createVariant('Variant', $dtString, array($theme1));
      $this->fail('Scope is not a true superset!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
    try {
      $variant = $name->createVariant('Variant', $dtString, array($theme2, $theme3));
      $this->fail('Scope is not a true superset!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
    try {
      $variant = $name->createVariant('Variant', $dtString, array($theme3));
      $this->fail('Scope is not a true superset!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
    $variant = $name->createVariant('Variant', $dtString, array($theme1, $theme2));
    $ids = $this->getIdsOfConstructs($variant->getScope());
    $this->assertTrue(in_array($theme1->getId(), $ids, true), 
      'Theme is not part of getScope()!');
    $this->assertTrue(in_array($theme2->getId(), $ids, true), 
      'Theme is not part of getScope()!');
  }
}
?>
