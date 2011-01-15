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
 * Scoped index tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class ScopedIndexTest extends PHPTMAPITestCase {
  
  public function testGetAssociations() {
    $tm = $this->topicMap;
    $this->assertTrue($tm instanceof TopicMap);
    $index = $tm->getIndex('ScopedIndexImpl');
    $this->assertTrue($index instanceof ScopedIndexImpl);
    
    try {
      $index->getAssociations(array('foo', $tm->createTopic()), true);
      $this->fail('Scope must be an array containing themes (topics).');
    } catch (InvalidArgumentException $e) {
      // no op.
    }
    try {
      $index->getAssociations(array('foo'), true);
      $this->fail('Scope must be an array containing themes (topics).');
    } catch (InvalidArgumentException $e) {
      // no op.
    }

    $theme1 = $tm->createTopic();
    $theme2 = $tm->createTopic();
    $theme3 = $tm->createTopic();
    $assocType = $tm->createTopic();

    $assoc1 = $tm->createAssociation($assocType);
    $assoc2 = $tm->createAssociation($assocType, array($theme1));
    $assocs = $tm->getAssociations();
    $this->assertEquals(count($assocs), 2);
    $assocs = $index->getAssociations(array(), true);
    $this->assertEquals(count($assocs), 1);
    $this->assertEquals($assocs[0]->getType()->getId(), $assocType->getId());
    $this->assertEquals($assoc1->getId(), $assocs[0]->getId());
    $assocs = $index->getAssociations(array(), false);
    $this->assertEquals(count($assocs), 1);
    $this->assertEquals($assocs[0]->getType()->getId(), $assocType->getId());
    $this->assertEquals($assoc1->getId(), $assocs[0]->getId());
    
    $assocs = $index->getAssociations(array($theme1), true);
    $this->assertEquals(count($assocs), 1);
    $this->assertEquals($assoc2->getId(), $assocs[0]->getId());
    $this->assertEquals($assocs[0]->getType()->getId(), $assocType->getId());
    
    $assoc1->remove();
    $assoc2->remove();
    
    $assoc1 = $tm->createAssociation($assocType, array($theme1));
    $assoc2 = $tm->createAssociation($assocType);
    $assocs = $tm->getAssociations();
    $this->assertEquals(count($assocs), 2);
    
    $assocs = $index->getAssociations(array($theme1), true);
    $this->assertEquals(count($assocs), 1);
    $this->assertEquals($assoc1->getId(), $assocs[0]->getId());
    $this->assertEquals($assocs[0]->getType()->getId(), $assocType->getId());
    
    $assocs = $index->getAssociations(array($theme1), false);
    $this->assertEquals(count($assocs), 1);
    $this->assertEquals($assoc1->getId(), $assocs[0]->getId());
    $this->assertEquals($assocs[0]->getType()->getId(), $assocType->getId());
    
    $assocs = $index->getAssociations(array(), true);
    $this->assertEquals(count($assocs), 1);
    $this->assertEquals($assoc2->getId(), $assocs[0]->getId());
    $this->assertEquals($assocs[0]->getType()->getId(), $assocType->getId());
    
    $assoc1->remove();
    $assoc2->remove();
    
    $assoc1 = $tm->createAssociation($assocType);
    $assoc2 = $tm->createAssociation($assocType, array($theme1, $theme2));
    $assoc3 = $tm->createAssociation($assocType, array($theme1, $theme2, $theme3));
    
    $twoAssocs1 = $twoAssocs2 = $twoAssocs3 = array(
      $assoc2->getId() => $assoc2, 
      $assoc3->getId() => $assoc3,  
    );
    
    $assocs = $tm->getAssociations();
    $this->assertEquals(count($assocs), 3);
    $assocs = $index->getAssociations(array($theme1, $theme2), false);
    $this->assertEquals(count($assocs), 2);
    foreach ($assocs as $assoc) {
      $this->assertTrue($assoc instanceof Association);
      $this->assertTrue(array_key_exists($assoc->getId(), $twoAssocs1));
      $this->assertEquals($assoc->getType()->getId(), $assocType->getId());
      unset($twoAssocs1[$assoc->getId()]);
    }
    $assocs = $index->getAssociations(array($theme1, $theme2), true);
    $this->assertEquals(count($assocs), 2);
    foreach ($assocs as $assoc) {
      $this->assertTrue($assoc instanceof Association);
      $this->assertTrue(array_key_exists($assoc->getId(), $twoAssocs2));
      $this->assertEquals($assoc->getType()->getId(), $assocType->getId());
      unset($twoAssocs2[$assoc->getId()]);
    }
    $assocs = $index->getAssociations(array($theme1, $theme2, $theme3), false);
    $this->assertEquals(count($assocs), 2);
    foreach ($assocs as $assoc) {
      $this->assertTrue($assoc instanceof Association);
      $this->assertTrue(array_key_exists($assoc->getId(), $twoAssocs3));
      $this->assertEquals($assoc->getType()->getId(), $assocType->getId());
      unset($twoAssocs3[$assoc->getId()]);
    }
    $assocs = $index->getAssociations(array($theme1, $theme2, $theme3), true);
    $this->assertEquals(count($assocs), 1);
    $this->assertEquals($assoc3->getId(), $assocs[0]->getId());
    $this->assertEquals($assocs[0]->getType()->getId(), $assocType->getId());
  }
  
  public function testGetAssociationThemes() {
    $tm = $this->topicMap;
    $this->assertTrue($tm instanceof TopicMap);
    $index = $tm->getIndex('ScopedIndexImpl');
    $this->assertTrue($index instanceof ScopedIndexImpl);

    $theme1 = $tm->createTopic();
    $theme2 = $tm->createTopic();
    $theme3 = $tm->createTopic();
    $theme4 = $tm->createTopic();
    
    $threeThemes = array(
      $theme1->getId() => $theme1, 
      $theme2->getId() => $theme2, 
      $theme3->getId() => $theme3 
    );

    $tm->createAssociation($tm->createTopic(), array($theme1, $theme2, $theme3));
    $tm->createAssociation($tm->createTopic(), array($theme1, $theme2));
    $assocs = $tm->getAssociations();
    $this->assertEquals(count($assocs), 2);
    $topic = $tm->createTopic();
    $topic->createName('Name', null, array($theme4));
    $names = $topic->getNames();
    $this->assertEquals(count($names), 1);

    $themes = $index->getAssociationThemes();
    $this->assertEquals(count($themes), 3);
    foreach ($themes as $theme) {
      $this->assertTrue($theme instanceof Topic);
      $this->assertTrue(array_key_exists($theme->getId(), $threeThemes));
      unset($threeThemes[$theme->getId()]);
    }
  }
  
  public function testGetNames() {
    $tm = $this->topicMap;
    $this->assertTrue($tm instanceof TopicMap);
    $index = $tm->getIndex('ScopedIndexImpl');
    $this->assertTrue($index instanceof ScopedIndexImpl);
    
    try {
      $index->getNames(array('foo', $tm->createTopic()), true);
      $this->fail('Scope must be an array containing themes (topics).');
    } catch (InvalidArgumentException $e) {
      // no op.
    }
    try {
      $index->getNames(array('foo'), true);
      $this->fail('Scope must be an array containing themes (topics).');
    } catch (InvalidArgumentException $e) {
      // no op.
    }

    $theme1 = $tm->createTopic();
    $theme2 = $tm->createTopic();
    $theme3 = $tm->createTopic();
    $nameType = $tm->createTopic();
    $topic = $tm->createTopic();

    $name1 = $topic->createName('foo', $nameType);
    $name2 = $topic->createName('bar', $nameType, array($theme1));
    $names = $topic->getNames();
    $this->assertEquals(count($names), 2);
    
    $names = $index->getNames(array(), true);
    $this->assertEquals(count($names), 1);
    $this->assertEquals($name1->getId(), $names[0]->getId());
    $this->assertEquals($names[0]->getType()->getId(), $nameType->getId());
    $this->assertEquals($name1->getValue(), $names[0]->getValue());
    
    $names = $index->getNames(array(), false);
    $this->assertEquals(count($names), 1);
    $this->assertEquals($name1->getId(), $names[0]->getId());
    $this->assertEquals($names[0]->getType()->getId(), $nameType->getId());
    $this->assertEquals($name1->getValue(), $names[0]->getValue());
    
    $names = $index->getNames(array($theme1), true);
    $this->assertEquals(count($names), 1);
    $this->assertEquals($name2->getId(), $names[0]->getId());
    $this->assertEquals($names[0]->getType()->getId(), $nameType->getId());
    $this->assertEquals($name2->getValue(), $names[0]->getValue());
    
    $name1->remove();
    $name2->remove();
    
    $name1 = $topic->createName('foo', $nameType);
    $name2 = $topic->createName('bar', $nameType, array($theme1, $theme2));
    $name3 = $topic->createName('baz', $nameType, array($theme1, $theme2, $theme3));
    
    $twoNames1 = $twoNames2 = $twoNames3 = array(
      $name2->getId() => $name2, 
      $name3->getId() => $name3
    );
    
    $names = $topic->getNames();
    $this->assertEquals(count($names), 3);
    
    $names = $index->getNames(array($theme1, $theme2), false);
    $this->assertEquals(count($names), 2);
    foreach ($names as $name) {
      $this->assertTrue($name instanceof Name);
      $this->assertTrue(array_key_exists($name->getId(), $twoNames1));
      $this->assertEquals($name->getType()->getId(), $nameType->getId());
      $this->assertEquals($twoNames1[$name->getId()]->getValue(), $name->getValue());
      unset($twoNames1[$name->getId()]);
    }
    
    $names = $index->getNames(array($theme1, $theme2), true);
    $this->assertEquals(count($names), 2);
    foreach ($names as $name) {
      $this->assertTrue($name instanceof Name);
      $this->assertTrue(array_key_exists($name->getId(), $twoNames2));
      $this->assertEquals($name->getType()->getId(), $nameType->getId());
      $this->assertEquals($twoNames2[$name->getId()]->getValue(), $name->getValue());
      unset($twoNames2[$name->getId()]);
    }
    
    $names = $index->getNames(array($theme1, $theme2, $theme3), false);
    $this->assertEquals(count($names), 2);
    foreach ($names as $name) {
      $this->assertTrue($name instanceof Name);
      $this->assertTrue(array_key_exists($name->getId(), $twoNames3));
      $this->assertEquals($name->getType()->getId(), $nameType->getId());
      $this->assertEquals($twoNames3[$name->getId()]->getValue(), $name->getValue());
      unset($twoNames3[$name->getId()]);
    }
    
    $names = $index->getNames(array($theme1, $theme2, $theme3), true);
    $this->assertEquals(count($names), 1);
    $this->assertTrue($names[0] instanceof Name);
    $this->assertEquals($names[0]->getType()->getId(), $nameType->getId());
    $this->assertEquals($names[0]->getValue(), $name3->getValue());
  }
  
  public function testGetNameThemes() {
    $tm = $this->topicMap;
    $this->assertTrue($tm instanceof TopicMap);
    $index = $tm->getIndex('ScopedIndexImpl');
    $this->assertTrue($index instanceof ScopedIndexImpl);

    $theme1 = $tm->createTopic();
    $theme2 = $tm->createTopic();
    $theme3 = $tm->createTopic();
    $theme4 = $tm->createTopic();
    
    $threeThemes = array(
      $theme1->getId() => $theme1, 
      $theme2->getId() => $theme2, 
      $theme3->getId() => $theme3 
    );
    
    $topic = $tm->createTopic();

    $topic->createName('foo', $tm->createTopic(), array($theme1, $theme2, $theme3));
    $topic->createName('bar', $tm->createTopic(), array($theme1, $theme2));
    $names = $topic->getNames();
    $this->assertEquals(count($names), 2);
    $topic->createOccurrence($tm->createTopic(), 'foo', parent::$dtString, array($theme4));
    $occs = $topic->getOccurrences();
    $this->assertEquals(count($occs), 1);

    $themes = $index->getNameThemes();
    $this->assertEquals(count($themes), 3);
    foreach ($themes as $theme) {
      $this->assertTrue($theme instanceof Topic);
      $this->assertTrue(array_key_exists($theme->getId(), $threeThemes));
      unset($threeThemes[$theme->getId()]);
    }
  }
  
  public function testGetOccurrences() {
    $tm = $this->topicMap;
    $this->assertTrue($tm instanceof TopicMap);
    $index = $tm->getIndex('ScopedIndexImpl');
    $this->assertTrue($index instanceof ScopedIndexImpl);
    
    try {
      $index->getOccurrences(array('foo', $tm->createTopic()), true);
      $this->fail('Scope must be an array containing themes (topics).');
    } catch (InvalidArgumentException $e) {
      // no op.
    }
    try {
      $index->getOccurrences(array('foo'), true);
      $this->fail('Scope must be an array containing themes (topics).');
    } catch (InvalidArgumentException $e) {
      // no op.
    }
    
    $theme1 = $tm->createTopic();
    $theme2 = $tm->createTopic();
    $theme3 = $tm->createTopic();
    $occType = $tm->createTopic();
    $topic = $tm->createTopic();
    
    $occ1 = $topic->createOccurrence($occType, 'foo', parent::$dtString);
    $occ2 = $topic->createOccurrence($occType, 'http://example.org', parent::$dtUri, array($theme1));
    $occs = $topic->getOccurrences();
    $this->assertEquals(count($occs), 2);
    $occs = $topic->getOccurrences($occType);
    $this->assertEquals(count($occs), 2);
    
    $occs = $index->getOccurrences(array(), true);
    $this->assertEquals(count($occs), 1);
    $this->assertTrue($occs[0] instanceof Occurrence);
    $this->assertEquals($occs[0]->getType()->getId(), $occType->getId());
    $this->assertEquals($occs[0]->getValue(), 'foo');
    $this->assertEquals($occs[0]->getDatatype(), parent::$dtString);
    
    $occs = $index->getOccurrences(array($theme1), true);
    $this->assertEquals(count($occs), 1);
    $this->assertTrue($occs[0] instanceof Occurrence);
    $this->assertEquals($occs[0]->getType()->getId(), $occType->getId());
    $this->assertEquals($occs[0]->getValue(), 'http://example.org');
    $this->assertEquals($occs[0]->getDatatype(), parent::$dtUri);
    
    $occs = $index->getOccurrences(array($theme1), false);
    $this->assertEquals(count($occs), 1);
    $this->assertTrue($occs[0] instanceof Occurrence);
    $this->assertEquals($occs[0]->getType()->getId(), $occType->getId());
    $this->assertEquals($occs[0]->getValue(), 'http://example.org');
    $this->assertEquals($occs[0]->getDatatype(), parent::$dtUri);
    
    $occ1->remove();
    $occ2->remove();
    
    $occ1 = $topic->createOccurrence($occType, 'foo', parent::$dtString);
    $occ2 = $topic->createOccurrence(
      $occType, 'http://example.org', parent::$dtUri, array($theme1, $theme2)
    );
    $occ3 = $topic->createOccurrence(
      $occType, 'bar', parent::$dtString, array($theme1, $theme2, $theme3)
    );
    
    $twoOccs1 = $twoOccs2 = $twoOccs3 = array(
      $occ2->getId() => $occ2, 
      $occ3->getId() => $occ3,
    );
    
    $occs = $topic->getOccurrences();
    $this->assertEquals(count($occs), 3);
    $occs = $topic->getOccurrences($occType);
    $this->assertEquals(count($occs), 3);
    
    $occs = $index->getOccurrences(array($theme1, $theme2), false);
    $this->assertEquals(count($occs), 2);
    foreach ($occs as $occ) {
      $this->assertTrue($occ instanceof Occurrence);
      $this->assertTrue(array_key_exists($occ->getId(), $twoOccs1));
      $this->assertEquals($occ->getType()->getId(), $occType->getId());
      $this->assertEquals($twoOccs1[$occ->getId()]->getValue(), $occ->getValue());
      $this->assertEquals($twoOccs1[$occ->getId()]->getDatatype(), $occ->getDatatype());
      unset($twoOccs1[$occ->getId()]);
    }
    
    $occs = $index->getOccurrences(array($theme1, $theme2), true);
    $this->assertEquals(count($occs), 2);
    foreach ($occs as $occ) {
      $this->assertTrue($occ instanceof Occurrence);
      $this->assertTrue(array_key_exists($occ->getId(), $twoOccs2));
      $this->assertEquals($occ->getType()->getId(), $occType->getId());
      $this->assertEquals($twoOccs2[$occ->getId()]->getValue(), $occ->getValue());
      $this->assertEquals($twoOccs2[$occ->getId()]->getDatatype(), $occ->getDatatype());
      unset($twoOccs2[$occ->getId()]);
    }
    
    $occs = $index->getOccurrences(array($theme1, $theme2, $theme3), false);
    $this->assertEquals(count($occs), 2);
    foreach ($occs as $occ) {
      $this->assertTrue($occ instanceof Occurrence);
      $this->assertTrue(array_key_exists($occ->getId(), $twoOccs3));
      $this->assertEquals($occ->getType()->getId(), $occType->getId());
      $this->assertEquals($twoOccs3[$occ->getId()]->getValue(), $occ->getValue());
      $this->assertEquals($twoOccs3[$occ->getId()]->getDatatype(), $occ->getDatatype());
      unset($twoOccs3[$occ->getId()]);
    }
    
    $occs = $index->getOccurrences(array($theme1, $theme2, $theme3), true);
    $this->assertEquals(count($occs), 1);
    $this->assertTrue($occs[0] instanceof Occurrence);
    $this->assertEquals($occs[0]->getId(), $occ3->getId());
    $this->assertEquals($occs[0]->getType()->getId(), $occType->getId());
    $this->assertEquals($occs[0]->getValue(), $occ3->getValue());
    $this->assertEquals($occs[0]->getDatatype(), $occ3->getDatatype());
  }
  
  public function testGetOccurrenceThemes() {
    $tm = $this->topicMap;
    $this->assertTrue($tm instanceof TopicMap);
    $index = $tm->getIndex('ScopedIndexImpl');
    $this->assertTrue($index instanceof ScopedIndexImpl);

    $theme1 = $tm->createTopic();
    $theme2 = $tm->createTopic();
    $theme3 = $tm->createTopic();
    $theme4 = $tm->createTopic();
    
    $threeThemes = array(
      $theme1->getId() => $theme1, 
      $theme2->getId() => $theme2, 
      $theme3->getId() => $theme3 
    );
    
    $topic = $tm->createTopic();

    $topic->createOccurrence(
      $tm->createTopic(), 'foo', parent::$dtString, array($theme1, $theme2)
    );
    $topic->createOccurrence(
      $tm->createTopic(), 'bar', parent::$dtString, array($theme1, $theme2, $theme3)
    );
    $occs = $topic->getOccurrences();
    $this->assertEquals(count($occs), 2);
    $topic->createName('bar', $tm->createTopic(), array($theme4));
    $names = $topic->getNames();
    $this->assertEquals(count($names), 1);

    $themes = $index->getOccurrenceThemes();
    $this->assertEquals(count($themes), 3);
    foreach ($themes as $theme) {
      $this->assertTrue($theme instanceof Topic);
      $this->assertTrue(array_key_exists($theme->getId(), $threeThemes));
      unset($threeThemes[$theme->getId()]);
    }
  }
  
  public function testGetVariants() {
    $tm = $this->topicMap;
    $this->assertTrue($tm instanceof TopicMap);
    $index = $tm->getIndex('ScopedIndexImpl');
    $this->assertTrue($index instanceof ScopedIndexImpl);
    
    try {
      $index->getVariants(array(), true);
      $this->fail('Scope must not be an empty array.');
    } catch (InvalidArgumentException $e) {
      // no op.
    }
    try {
      $index->getVariants(array('foo'), true);
      $this->fail('Scope must be an array containing themes (topics).');
    } catch (InvalidArgumentException $e) {
      // no op.
    }
    try {
      $index->getVariants(array('foo', $tm->createTopic()), true);
      $this->fail('Scope must be an array containing themes (topics).');
    } catch (InvalidArgumentException $e) {
      // no op.
    }
    
    $theme1 = $tm->createTopic();
    $theme2 = $tm->createTopic();
    $theme3 = $tm->createTopic();
    $topic = $tm->createTopic();
    $name = $topic->createName('foo');
    
    $variant1 = $name->createVariant('foo', parent::$dtString, array($theme1));
    $variant2 = $name->createVariant('http://example.org', parent::$dtUri, array($theme2));
    $variants = $name->getVariants();
    $this->assertEquals(count($variants), 2);
    
    $variants = $index->getVariants(array($theme1), true);
    $this->assertEquals(count($variants), 1);
    $this->assertTrue($variants[0] instanceof IVariant);
    $this->assertEquals($variants[0]->getValue(), 'foo');
    $this->assertEquals($variants[0]->getDatatype(), parent::$dtString);
    
    $variants = $index->getVariants(array($theme1), false);
    $this->assertEquals(count($variants), 1);
    $this->assertTrue($variants[0] instanceof IVariant);
    $this->assertEquals($variants[0]->getValue(), 'foo');
    $this->assertEquals($variants[0]->getDatatype(), parent::$dtString);
    
    $variants = $index->getVariants(array($theme2), true);
    $this->assertEquals(count($variants), 1);
    $this->assertTrue($variants[0] instanceof IVariant);
    $this->assertEquals($variants[0]->getValue(), 'http://example.org');
    $this->assertEquals($variants[0]->getDatatype(), parent::$dtUri);
    
    $variants = $index->getVariants(array($theme2), false);
    $this->assertEquals(count($variants), 1);
    $this->assertTrue($variants[0] instanceof IVariant);
    $this->assertEquals($variants[0]->getValue(), 'http://example.org');
    $this->assertEquals($variants[0]->getDatatype(), parent::$dtUri);
    
    $variant1->remove();
    $variant2->remove();
    
    $variant1 = $name->createVariant('foo', parent::$dtString, array($tm->createTopic()));
    $variant2 = $name->createVariant(
    	'http://example.org', parent::$dtUri, array($theme1, $theme2)
    );
    $variant3 = $name->createVariant(
    	'bar', parent::$dtString, array($theme1, $theme2, $theme3)
    );
    $variants = $name->getVariants();
    $this->assertEquals(count($variants), 3);
    
    $twoVariants1 = $twoVariants2 = $twoVariants3 = array(
      $variant2->getId() => $variant2,
      $variant3->getId() => $variant3 
    );
    
    $variants = $name->getVariants();
    $this->assertEquals(count($variants), 3);
    
    $variants = $index->getVariants(array($theme1, $theme2), false);
    $this->assertEquals(count($variants), 2);
    foreach ($variants as $variant) {
      $this->assertTrue($variant instanceof IVariant);
      $this->assertTrue(array_key_exists($variant->getId(), $twoVariants1));
      $this->assertEquals($twoVariants1[$variant->getId()]->getValue(), $variant->getValue());
      $this->assertEquals($twoVariants1[$variant->getId()]->getDatatype(), $variant->getDatatype());
      unset($twoVariants1[$variant->getId()]);
    }
    
    $variants = $index->getVariants(array($theme1, $theme2), true);
    $this->assertEquals(count($variants), 2);
    foreach ($variants as $variant) {
      $this->assertTrue($variant instanceof IVariant);
      $this->assertTrue(array_key_exists($variant->getId(), $twoVariants2));
      $this->assertEquals($twoVariants2[$variant->getId()]->getValue(), $variant->getValue());
      $this->assertEquals($twoVariants2[$variant->getId()]->getDatatype(), $variant->getDatatype());
      unset($twoVariants2[$variant->getId()]);
    }
    
    $variants = $index->getVariants(array($theme1, $theme2, $theme3), false);
    $this->assertEquals(count($variants), 2);
    foreach ($variants as $variant) {
      $this->assertTrue($variant instanceof IVariant);
      $this->assertTrue(array_key_exists($variant->getId(), $twoVariants3));
      $this->assertEquals($twoVariants3[$variant->getId()]->getValue(), $variant->getValue());
      $this->assertEquals($twoVariants3[$variant->getId()]->getDatatype(), $variant->getDatatype());
      unset($twoVariants3[$variant->getId()]);
    }
    
    $variants = $index->getVariants(array($theme1, $theme2, $theme3), true);
    $this->assertEquals(count($variants), 1);
    $this->assertTrue($variants[0] instanceof IVariant);
    $this->assertEquals($variants[0]->getValue(), $variant3->getValue());
    $this->assertEquals($variants[0]->getDatatype(), $variant3->getDatatype());
  }
  
  public function testGetVariantThemes() {
    $tm = $this->topicMap;
    $this->assertTrue($tm instanceof TopicMap);
    $index = $tm->getIndex('ScopedIndexImpl');
    $this->assertTrue($index instanceof ScopedIndexImpl);

    $theme1 = $tm->createTopic();
    $theme2 = $tm->createTopic();
    $theme3 = $tm->createTopic();
    $theme4 = $tm->createTopic();
    
    $threeThemes = array(
      $theme1->getId() => $theme1, 
      $theme2->getId() => $theme2, 
      $theme3->getId() => $theme3 
    );
    
    $topic = $tm->createTopic();
    $name = $topic->createName('foo', null, array($theme4));
    
    $names = $topic->getNames();
    $this->assertEquals(count($names), 1);

    $name->createVariant('foo', parent::$dtString, array($theme1, $theme2));
    $name->createVariant('bar', parent::$dtString, array($theme1, $theme2, $theme3));
    $variants = $name->getVariants();
    $this->assertEquals(count($variants), 2);

    $themes = $index->getVariantThemes();
    $this->assertEquals(count($themes), 3);
    foreach ($themes as $theme) {
      $this->assertTrue($theme instanceof Topic);
      $this->assertTrue(array_key_exists($theme->getId(), $threeThemes));
      unset($threeThemes[$theme->getId()]);
    }
  }
}
?>