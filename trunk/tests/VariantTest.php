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
 * Variant tests.
 *
 * @package test
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class VariantTest extends PHPTMAPITestCase {

  public function testTopicMap() {
    $this->assertTrue($this->topicMap instanceof TopicMap);
  }
  
  public function testParent() {
    $parent = $this->createName();
    $this->assertEquals(count($parent->getVariants()), 0, 
      'Expected new name to be created without variants!');
    $scope = array($this->topicMap->createTopic());
    $variant = $parent->createVariant('Variant', parent::$dtString, $scope);
    $this->assertEquals($parent->getId(), $variant->getParent()->getId(), 
      'Unexpected variant parent!');
    $this->assertEquals(count($parent->getVariants()), 1, 'Expected 1 variant!');
    $ids = $this->getIdsOfConstructs($parent->getVariants());
    $this->assertTrue(in_array($variant->getId(), $ids, true), 
      'Variant is not part of getVariants()!');
    $variant->remove();
    $this->assertEquals(count($parent->getVariants()), 0, 'Expected 0 variants after removal!');
  }
  
  public function testIllegalValueDatatype() {
    try {
      $name = $this->createName();
      $scope = array($this->topicMap->createTopic());
      $variant = $name->createVariant(null, parent::$dtString, $scope);
      $this->fail('Variant value must not be null!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
    try {
      $name = $this->createName();
      $scope = array($this->topicMap->createTopic());
      $variant = $name->createVariant('Variant', null, $scope);
      $this->fail('Variant datatype must not be null!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
    try {
      $name = $this->createName();
      $scope = array($this->topicMap->createTopic());
      $variant = $name->createVariant(null, null, $scope);
      $this->fail('Variant value and datatype must not be null!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
  }
  
  /**
   * Tests if the variant's scope contains the name's scope after Name::addTheme() 
   * and if the theme is removed when Name::removeTheme() is called.
   */
  public function testScopeNameAddRemoveTheme() {
    $name = $this->createName();
    $this->assertEquals(count($name->getScope()), 0, 'Expected UCS!');
    $varTheme = $this->topicMap->createTopic();
    $scope = array($varTheme);
    $variant = $name->createVariant('Variant', parent::$dtString, $scope);
    $this->assertNotNull($variant, 'Expected a variant!');
    $this->assertEquals(count($variant->getScope()), 1, 'Expected 1 theme in scope!');
    $ids = $this->getIdsOfConstructs($variant->getScope());
    $this->assertTrue(in_array($varTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
    $nameTheme = $this->topicMap->createTopic();
    $name->addTheme(($nameTheme));// also added to variants
    $this->assertEquals(count($name->getScope()), 1, 'Expected 1 theme in scope!');
    $ids = $this->getIdsOfConstructs($name->getScope());
    $this->assertTrue(in_array($nameTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
    $this->assertEquals(count($variant->getScope()), 2, 'Expected 2 themes in scope!');
    $ids = $this->getIdsOfConstructs($variant->getScope());
    $this->assertTrue(in_array($varTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
    $this->assertTrue(in_array($nameTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
    $name->removeTheme($nameTheme);// also removed from variants
    $this->assertEquals(count($variant->getScope()), 1, 'Expected 1 theme in scope!');
    $ids = $this->getIdsOfConstructs($variant->getScope());
    $this->assertTrue(in_array($varTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
  }
  
  /**
   * Tests if 
   * a) a variant's theme equals to a name's theme keeps preserved  
   *    if the name's theme is removed,
   * b) a variant's theme equals to a name's theme keeps preserved 
   *    if the variant's theme is removed (superset constraint).
   */
  public function testScopePreserveTheme() {
    $tm = $this->topicMap;
    $topic = $tm->createTopic();
    $nameTheme = $tm->createTopic();
    $varTheme = $tm->createTopic();
    $name = $topic->createName('Name', array($nameTheme));
    $this->assertEquals(count($name->getScope()), 1, 'Expected 1 theme!');
    $ids = $this->getIdsOfConstructs($name->getScope());
    $this->assertTrue(in_array($nameTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
    $variant = $name->createVariant('Variant', parent::$dtString, 
      array($nameTheme, $varTheme));
    $this->assertNotNull($variant, 'Expected a variant!');
    $this->assertEquals(count($variant->getScope()), 2, 'Expected 2 themes!');
    $ids = $this->getIdsOfConstructs($variant->getScope());
    $this->assertTrue(in_array($nameTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
    $this->assertTrue(in_array($varTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
    $name->removeTheme($nameTheme);
    $this->assertEquals(count($name->getScope()), 0, 'Unexpected theme!');
    $this->assertEquals(count($variant->getScope()), 2, 'Expected 2 themes!');
    $ids = $this->getIdsOfConstructs($variant->getScope());
    $this->assertTrue(in_array($nameTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
    $this->assertTrue(in_array($varTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');   
    $variant->remove();
    $this->assertEquals(count($name->getVariants()), 0, 'Unexpected variant!');
    $name->remove();
    $this->assertEquals(count($topic->getNames()), 0, 'Unexpected name!');
    
    $name = $topic->createName('Name', array($nameTheme));
    $this->assertEquals(count($name->getScope()), 1, 'Expected 1 theme!');
    $ids = $this->getIdsOfConstructs($name->getScope());
    $this->assertTrue(in_array($nameTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
    $variant = $name->createVariant('Variant', parent::$dtString, 
      array($nameTheme, $varTheme));
    $this->assertNotNull($variant, 'Expected a variant!');
    $this->assertEquals(count($variant->getScope()), 2, 'Expected 2 themes!');
    $ids = $this->getIdsOfConstructs($variant->getScope());
    $this->assertTrue(in_array($nameTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
    $this->assertTrue(in_array($varTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
    $variant->removeTheme($nameTheme);
    $this->assertEquals(count($variant->getScope()), 2, 
      'Expected 2 themes due to superset constraint!');
    $ids = $this->getIdsOfConstructs($variant->getScope());
    $this->assertTrue(in_array($nameTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
    $this->assertTrue(in_array($varTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
    $name->removeTheme($nameTheme);
    $this->assertEquals(count($name->getScope()), 0, 'Unexpected theme!');
    $this->assertEquals(count($variant->getScope()), 1, 'Expected 1 theme!');
    $ids = $this->getIdsOfConstructs($variant->getScope());
    $this->assertTrue(in_array($varTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
    $this->assertFalse(in_array($nameTheme->getId(), $ids, true), 
      'Theme (topic) is part of getScope()!');
  }
  
  public function testCreateScopeSupersetConstraint() {
    $tm = $this->topicMap;
    $topic = $tm->createTopic();
    $nameTheme = $tm->createTopic();
    $varTheme = $tm->createTopic();
    $name = $topic->createName('Name', array($nameTheme));
    $this->assertEquals(count($name->getScope()), 1, 'Expected 1 theme!');
    try {
      $name->createVariant('Variant', parent::$dtString, array());
      $this->fail('Variant scope is not a superset of the name scope!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
    try {
      $name->createVariant('Variant', parent::$dtString, array($nameTheme));
      $this->fail('Variant scope is not a superset of the name scope!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
    $variant = $name->createVariant('Variant', parent::$dtString, 
      array($nameTheme, $varTheme));
    $this->assertEquals(count($variant->getScope()), 2, 'Expected 2 themes!');
    $ids = $this->getIdsOfConstructs($variant->getScope());
    $this->assertTrue(in_array($varTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
    $this->assertTrue(in_array($nameTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
    $variant->removeTheme($varTheme);
    $this->assertEquals(count($variant->getScope()), 2, 
      'Expected 2 themes due to superset constraint!');
    $name->removeTheme($nameTheme);
    $this->assertEquals(count($variant->getScope()), 2, 
      'Expected 2 themes due to superset constraint!');
    $variant->removeTheme($varTheme);
    $this->assertEquals(count($variant->getScope()), 1, 'Expected 1 theme!');
  }
  
  public function testCreateScopeSupersetConstraintExtended() {
    $tm = $this->topicMap;
    $topic = $tm->createTopic();
    $nameTheme = $tm->createTopic();
    $varTheme = $tm->createTopic();
    $anotherVarTheme = $tm->createTopic();
    $name = $topic->createName('Name', array($nameTheme));
    $this->assertEquals(count($name->getScope()), 1, 'Expected 1 theme!');
    try {
      $name->createVariant('Variant', parent::$dtString, array());
      $this->fail('Variant scope is not a superset of the name scope!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
    try {
      $name->createVariant('Variant', parent::$dtString, array($nameTheme));
      $this->fail('Variant scope is not a superset of the name scope!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
    try {
      $name->createVariant('Variant', parent::$dtString, 
        array($nameTheme, $nameTheme));
      $this->fail('Variant scope is not a superset of the name scope!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
    try {
      $name->createVariant('Variant', parent::$dtString, 
        array($nameTheme, $nameTheme, $nameTheme));
      $this->fail('Variant scope is not a superset of the name scope!');
    } catch (ModelConstraintException $e) {
      // no op.
    }
    
    $variant = $name->createVariant('Variant', parent::$dtString, 
      array($nameTheme, $varTheme));
    $this->assertEquals(count($variant->getScope()), 2, 'Expected 2 themes!');
    $ids = $this->getIdsOfConstructs($variant->getScope());
    $this->assertTrue(in_array($varTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
    $this->assertTrue(in_array($nameTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
      
    $variant2 = $name->createVariant('Variant', parent::$dtString, 
      array($varTheme, $anotherVarTheme));
    $this->assertEquals(count($variant2->getScope()), 3, 'Expected 3 themes!');
    $ids = $this->getIdsOfConstructs($variant2->getScope());
    $this->assertTrue(in_array($varTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
    $this->assertTrue(in_array($anotherVarTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
    $this->assertTrue(in_array($nameTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
      
    $variant2->removeTheme($anotherVarTheme);
    $this->assertEquals(count($variant2->getScope()), 2, 'Expected 2 themes!');
    $ids = $this->getIdsOfConstructs($variant2->getScope());
    $this->assertTrue(in_array($varTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
    $this->assertTrue(in_array($nameTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
      
    $variant2->removeTheme($varTheme);
    $this->assertEquals(count($variant2->getScope()), 2, 
      'Expected 2 themes due to superset constraint!');
    $ids = $this->getIdsOfConstructs($variant2->getScope());
    $this->assertTrue(in_array($varTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
    $this->assertTrue(in_array($nameTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
    
    $variant2->remove();
    $this->assertEquals(count($name->getVariants()), 1, 'Expected 1 variant!');
    $variant->remove();
    $this->assertEquals(count($name->getVariants()), 0, 'Unexpected variant!');
    $this->assertEquals(count($name->getScope()), 1, 'Expected 1 theme!');
    $ids = $this->getIdsOfConstructs($name->getScope());
    $this->assertTrue(in_array($nameTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
  }
  
  public function testDuplicates() {
    $tm = $this->topicMap;
    $topic = $tm->createTopic();
    $nameTheme = $tm->createTopic();
    $varTheme = $tm->createTopic();
    $name = $topic->createName('Name', array($nameTheme));
    $variant = $name->createVariant('Variant', parent::$dtString, 
      array($varTheme));
    $variants = $name->getVariants();
    $this->assertEquals(count($variants), 1, 'Expected 1 variant!');
    $variant = $variants[0];
    $this->assertEquals(count($variant->getScope()), 2, 'Expected 2 themes!');
    $ids = $this->getIdsOfConstructs($variant->getScope());
    $this->assertTrue(in_array($varTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
    $this->assertTrue(in_array($nameTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
    $this->assertEquals($variant->getValue(), 'Variant', 'Expected identity!');
    $this->assertEquals($variant->getDataType(), parent::$dtString, 'Expected identity!');
    $duplVariant = $name->createVariant('Variant', parent::$dtString, 
      array($varTheme));
    $variants = $name->getVariants();
    $this->assertEquals(count($variants), 1, 'Expected 1 variant!');
    $variant = $variants[0];
    $this->assertEquals(count($variant->getScope()), 2, 'Expected 2 themes!');
    $ids = $this->getIdsOfConstructs($variant->getScope());
    $this->assertTrue(in_array($varTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
    $this->assertTrue(in_array($nameTheme->getId(), $ids, true), 
      'Theme (topic) is not part of getScope()!');
    $this->assertEquals($variant->getValue(), 'Variant', 'Expected identity!');
    $this->assertEquals($variant->getDataType(), parent::$dtString, 'Expected identity!');
  }
}
?>
