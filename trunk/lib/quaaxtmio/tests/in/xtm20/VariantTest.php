<?php
/*
 * QuaaxTMIO is the Topic Maps syntaxes serializer/deserializer library for QuaaxTM.
 * 
 * Copyright (C) 2010 Johannes Schmidt <joschmidt@users.sourceforge.net>
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

require_once('TestCase.php');

/**
 * Variant tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class VariantTest extends TestCase {
  
  public function testTopicMapSystem() {
    $this->assertTrue($this->sharedFixture instanceof TopicMapSystem);
  }
  
  public function testVariant() {
    $file = $this->xtmIncPath . 'variant.xtm';
    $this->readAndParse($file);
    
    $this->_testVariant();
  }
  
  public function testVariantDuplicate() {
    $file = $this->xtmIncPath . 'variant-duplicate.xtm';
    $this->readAndParse($file);
    
    $this->_testVariant();
  }
  
  public function testVariantDatatypeUnknown() {
    $file = $this->xtmIncPath . 'variant-datatype-unknown.xtm';
    $this->readAndParse($file);
    
    $this->_testVariant('BOGUS', 'http://example.com/bogus', '#scope');
  }
  
  public function testVariantScopeDuplicate() {
    $file = $this->xtmIncPath . 'variant-scope-duplicate.xtm';
    $this->readAndParse($file);
    
    $this->_testVariant();
  }
  
  public function testVariantScopeMultiple() {
    $file = $this->xtmIncPath . 'variant-scope-multiple.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 4);
    
    $nameTopic = $tm->getConstructByItemIdentifier($this->tmLocator . '#topic');
    $this->assertTrue($nameTopic instanceof Topic);
    $names = $nameTopic->getNames();
    $this->assertEquals(count($names), 1);
    
    $name = $names[0];
    $this->assertEquals($name->getValue(), 'Topic');
    $type = $name->getType();
    $this->assertTrue($type instanceof Topic);
    
    $sids = $type->getSubjectIdentifiers();
    $this->assertEquals(count($sids), 1);
    
    $sid = $sids[0];
    $this->assertEquals($sid, 'http://psi.topicmaps.org/iso13250/model/topic-name');
    
    $variants = $name->getVariants();
    $this->assertEquals(count($variants), 1);
    
    $variant = $variants[0];
    $this->assertEquals($variant->getValue(), 'TOPIC!');
    $this->assertEquals(
      $variant->getDatatype(), 
      'http://www.w3.org/2001/XMLSchema#string'
    );
    
    $scope = $variant->getScope();
    $this->assertEquals(count($scope), 2);
    
    $themeIds = array(
      '#command' => '#command',
      '#silly' => '#silly'
    );
    
    foreach ($scope as $theme) {
      $iids = $theme->getItemIdentifiers();
      $this->assertEquals(count($iids), 1);
      $iid = $iids[0];
      $fragment = $this->getIidFragment($iid);
      $this->assertTrue(in_array($fragment, $themeIds));
      unset($themeIds[$fragment]);
    }
  }
  
  public function testVariantInherit() {
    $file = $this->xtmIncPath . 'variant-inherit.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 4);
    
    $nameTopic = $tm->getConstructByItemIdentifier($this->tmLocator . '#topic');
    $this->assertTrue($nameTopic instanceof Topic);
    $names = $nameTopic->getNames();
    $this->assertEquals(count($names), 1);
    
    $name = $names[0];
    $this->assertEquals($name->getValue(), 'Topic');
    $type = $name->getType();
    $this->assertTrue($type instanceof Topic);
    
    $sids = $type->getSubjectIdentifiers();
    $this->assertEquals(count($sids), 1);
    
    $sid = $sids[0];
    $this->assertEquals($sid, 'http://psi.topicmaps.org/iso13250/model/topic-name');
    
    $scope = $name->getScope();
    $this->assertEquals(count($scope), 1);
    
    $theme = $scope[0];
    $iids = $theme->getItemIdentifiers();
    $this->assertEquals(count($iids), 1);
    $iid = $iids[0];
    $fragment = $this->getIidFragment($iid);
    $this->assertEquals($fragment, '#english');
    
    $variants = $name->getVariants();
    $this->assertEquals(count($variants), 1);
    
    $variant = $variants[0];
    $this->assertEquals($variant->getValue(), 'TOPIC!');
    $this->assertEquals(
      $variant->getDatatype(), 
      parent::$dtString
    );
    
    $scope = $variant->getScope();
    $this->assertEquals(count($scope), 2);
    
    $themeIds = array(
      '#command' => '#command',
      '#english' => '#english'
    );
    
    foreach ($scope as $theme) {
      $iids = $theme->getItemIdentifiers();
      $this->assertEquals(count($iids), 1);
      $iid = $iids[0];
      $fragment = $this->getIidFragment($iid);
      $this->assertTrue(in_array($fragment, $themeIds));
      unset($themeIds[$fragment]);
    }
  }
  
  public function testVariantResourceRef() {
    $file = $this->xtmIncPath . 'variant-resourceref.xtm';
    $this->readAndParse($file);
    
    $this->_testVariantResourceUri('http://example.org/command.wav');
  }
  
  public function testVariantResourceRefRelative() {
    $file = $this->xtmIncPath . 'variant-resourceref-relative.xtm';
    $this->readAndParse($file);
    
    $this->_testVariantResourceUri('topic-command.wav');
  }
  
  public function testVariantResourceDataUri() {
    $file = $this->xtmIncPath . 'variant-resourcedata-uri.xtm';
    $this->readAndParse($file);
    
    $this->_testVariantResourceUri('http://example.org/command.wav');
  }
  
  public function testVariantReifier() {
    $file = $this->xtmIncPath . 'variant-reifier.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 4);
    
    $topic = $tm->getConstructByItemIdentifier($this->tmLocator . '#command');
    $this->assertTrue($topic instanceof Topic);
    
    $nameTopic = $tm->getConstructByItemIdentifier($this->tmLocator . '#topic');
    $this->assertTrue($nameTopic instanceof Topic);
    $names = $nameTopic->getNames();
    $this->assertEquals(count($names), 1);
    
    $name = $names[0];
    $this->assertEquals($name->getValue(), 'Topic');
    $type = $name->getType();
    $this->assertTrue($type instanceof Topic);
    
    $sids = $type->getSubjectIdentifiers();
    $this->assertEquals(count($sids), 1);
    
    $sid = $sids[0];
    $this->assertEquals($sid, 'http://psi.topicmaps.org/iso13250/model/topic-name');
    
    $variants = $name->getVariants();
    $this->assertEquals(count($variants), 1);
    
    $variant = $variants[0];
    $this->assertEquals($variant->getValue(), 'TOPIC!');
    $this->assertEquals(
      $variant->getDatatype(), 
      parent::$dtString
    );
    
    $scope = $variant->getScope();
    $this->assertEquals(count($scope), 1);
    
    $theme = $scope[0];
    $this->assertTrue($theme instanceof Topic);
    $iids = $theme->getItemIdentifiers();
    $this->assertEquals(count($iids), 1);
    
    $iid = $iids[0];
    $fragment = $this->getIidFragment($iid);
    $this->assertEquals($fragment, '#command');
    
    $reifier = $variant->getReifier();
    $this->assertTrue($reifier instanceof Topic);
  }
  
  private function _testVariantResourceUri($ref) {
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 3);
    
    $topic = $tm->getConstructByItemIdentifier($this->tmLocator . '#command');
    $this->assertTrue($topic instanceof Topic);
    
    $nameTopic = $tm->getConstructByItemIdentifier($this->tmLocator . '#topic');
    $this->assertTrue($nameTopic instanceof Topic);
    $names = $nameTopic->getNames();
    $this->assertEquals(count($names), 1);
    
    $name = $names[0];
    $this->assertEquals($name->getValue(), 'Topic');
    $type = $name->getType();
    $this->assertTrue($type instanceof Topic);
    
    $sids = $type->getSubjectIdentifiers();
    $this->assertEquals(count($sids), 1);
    
    $sid = $sids[0];
    $this->assertEquals($sid, 'http://psi.topicmaps.org/iso13250/model/topic-name');
    
    $variants = $name->getVariants();
    $this->assertEquals(count($variants), 1);
    
    $variant = $variants[0];
    $this->assertEquals($variant->getValue(), $ref);
    $this->assertEquals(
      $variant->getDatatype(), 
      parent::$dtUri
    );
    
    $scope = $variant->getScope();
    $this->assertEquals(count($scope), 1);
    
    $theme = $scope[0];
    $this->assertTrue($theme instanceof Topic);
    $iids = $theme->getItemIdentifiers();
    $this->assertEquals(count($iids), 1);
    
    $iid = $iids[0];
    $fragment = $this->getIidFragment($iid);
    $this->assertEquals($fragment, '#command');
  }

  private function _testVariant(
    $value='TOPIC!', 
    $datatype='http://www.w3.org/2001/XMLSchema#string', 
    $iidFragment='#command'
  ) {
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 3);
    
    $topic = $tm->getConstructByItemIdentifier($this->tmLocator . $iidFragment);
    $this->assertTrue($topic instanceof Topic);
    
    $nameTopic = $tm->getConstructByItemIdentifier($this->tmLocator . '#topic');
    $this->assertTrue($nameTopic instanceof Topic);
    $names = $nameTopic->getNames();
    $this->assertEquals(count($names), 1);
    
    $name = $names[0];
    $this->assertEquals($name->getValue(), 'Topic');
    $type = $name->getType();
    $this->assertTrue($type instanceof Topic);
    
    $sids = $type->getSubjectIdentifiers();
    $this->assertEquals(count($sids), 1);
    
    $sid = $sids[0];
    $this->assertEquals($sid, 'http://psi.topicmaps.org/iso13250/model/topic-name');
    
    $variants = $name->getVariants();
    $this->assertEquals(count($variants), 1);
    
    $variant = $variants[0];
    $this->assertEquals($variant->getValue(), $value);
    $this->assertEquals($variant->getDatatype(), $datatype);
    
    $scope = $variant->getScope();
    $this->assertEquals(count($scope), 1);
    
    $theme = $scope[0];
    $this->assertTrue($theme instanceof Topic);
    $iids = $theme->getItemIdentifiers();
    $this->assertEquals(count($iids), 1);
    
    $iid = $iids[0];
    $fragment = $this->getIidFragment($iid);
    $this->assertEquals($fragment, $iidFragment);
  }
}
?>