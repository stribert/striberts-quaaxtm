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
 * Name tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class NameTest extends TestCase {
  
  public function testTopicMapSystem() {
    $this->assertTrue($this->sharedFixture instanceof TopicMapSystem);
  }
  
  public function testName() {
    $file = $this->xtmIncPath . 'name.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 2);
    
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
  }
  
  public function testNameType() {
    $file = $this->xtmIncPath . 'name-type.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 2);
    
    $nameTopic = $tm->getConstructByItemIdentifier($this->tmLocator . '#topic');
    $this->assertTrue($nameTopic instanceof Topic);
    $names = $nameTopic->getNames();
    $this->assertEquals(count($names), 1);
    
    $name = $names[0];
    $this->assertEquals($name->getValue(), 'Topics');
    $type = $name->getType();
    $this->assertTrue($type instanceof Topic);
    
    $sids = $type->getSubjectIdentifiers();
    $this->assertEquals(count($sids), 0);
    
    $iids = $type->getItemIdentifiers();
    $this->assertEquals(count($iids), 1);
    $iid = $iids[0];
    $this->assertEquals($iid, 'http://psi.ontopia.net/xtm/basename/#plural');
  }
  
  public function testNameTypeScope() {
    $file = $this->xtmIncPath . 'name-type-scope.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 3);
    
    $nameTopic = $tm->getConstructByItemIdentifier($this->tmLocator . '#topic');
    $this->assertTrue($nameTopic instanceof Topic);
    $names = $nameTopic->getNames();
    $this->assertEquals(count($names), 1);
    
    $name = $names[0];
    $this->assertEquals($name->getValue(), 'Emner');
    $type = $name->getType();
    $this->assertTrue($type instanceof Topic);
    
    $sids = $type->getSubjectIdentifiers();
    $this->assertEquals(count($sids), 0);
    
    $iids = $type->getItemIdentifiers();
    $this->assertEquals(count($iids), 1);
    $iid = $iids[0];
    $this->assertEquals($iid, 'http://psi.ontopia.net/xtm/basename/#plural');
    
    $scope = $name->getScope();
    $this->assertEquals(count($scope), 1);
    $theme = $scope[0];
    $this->assertTrue($theme instanceof Topic);
    $iids = $theme->getItemIdentifiers();
    $this->assertEquals(count($iids), 1);
    $iid = $iids[0];
    $fragment = $this->getIidFragment($iid);
    $this->assertEquals($fragment, '#norwegian');
  }
  
  public function testNameTypeBefore() {
    $file = $this->xtmIncPath . 'name-type-before.xtm';
    $this->readAndParse($file);
    $this->_testNameTypeBeforeAfter();
  }
  
  public function testNameTypeAfter() {
    $file = $this->xtmIncPath . 'name-type-after.xtm';
    $this->readAndParse($file);
    $this->_testNameTypeBeforeAfter();
  }
  
  public function testNameEscaping() {
    $file = $this->xtmIncPath . 'name-escaping.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 2);
    
    $nameType = $tm->getTopicBySubjectIdentifier(
    	'http://psi.topicmaps.org/iso13250/model/topic-name'
    );
    $this->assertTrue($nameType instanceof Topic);
    
    $topic = $tm->getConstructByItemIdentifier($this->tmLocator . '#att');
    $this->assertTrue($topic instanceof Topic);
    
    $names = $topic->getNames();
    $this->assertEquals(count($names), 1);
    
    $name = $names[0];
    $value = $name->getValue();
    $this->assertEquals($value, '<AT&T>');
    
    $type = $name->getType();
    $this->assertEquals($type->getId(), $nameType->getId());
  }
  
  public function testNameReifier() {
    $file = $this->xtmIncPath . 'name-reifier.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 3);
    
    $nameType = $tm->getTopicBySubjectIdentifier(
    	'http://psi.topicmaps.org/iso13250/model/topic-name'
    );
    $this->assertTrue($nameType instanceof Topic);
    
    $topic = $tm->getConstructByItemIdentifier($this->tmLocator . '#topic');
    $this->assertTrue($topic instanceof Topic);
    
    $names = $topic->getNames();
    $this->assertEquals(count($names), 1);
    
    $name = $names[0];
    $value = $name->getValue();
    $this->assertEquals($value, 'Topic');
    
    $reifier = $name->getReifier();
    $this->assertTrue($reifier instanceof Topic);
    
    $_reifier = $tm->getConstructByItemIdentifier($this->tmLocator . '#reifier');
    $this->assertEquals($reifier->getId(), $_reifier->getId());
    
    $type = $name->getType();
    $this->assertEquals($type->getId(), $nameType->getId());
  }
  
  public function testNameScopeDuplicateMerged() {
    $file = $this->xtmIncPath . 'name-scope-duplicate-merged.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 3);
    
    $nameType = $tm->getTopicBySubjectIdentifier(
    	'http://psi.topicmaps.org/iso13250/model/topic-name'
    );
    $this->assertTrue($nameType instanceof Topic);
    
    $topic = $tm->getConstructByItemIdentifier($this->tmLocator . '#topic');
    $this->assertTrue($topic instanceof Topic);
    
    $noTopic = $tm->getConstructByItemIdentifier($this->tmLocator . '#no');
    $this->assertTrue($topic instanceof Topic);
    
    $norwegianTopic = $tm->getConstructByItemIdentifier($this->tmLocator . '#norwegian');
    $this->assertTrue($topic instanceof Topic);
    
    $this->assertEquals($noTopic->getId(), $norwegianTopic->getId());
    
    $names = $topic->getNames();
    $this->assertEquals(count($names), 1);
    
    $name = $names[0];
    $this->assertEquals($name->getValue(), 'Emne');
    $scope = $name->getScope();
    $this->assertEquals(count($scope), 1);
    
    $theme = $scope[0];
    $this->assertEquals($theme->getId(), $noTopic->getId());
  }
  
  public function testNameScopeDuplicate() {
    $file = $this->xtmIncPath . 'name-scope-duplicate.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 3);
    
    $nameType = $tm->getTopicBySubjectIdentifier(
    	'http://psi.topicmaps.org/iso13250/model/topic-name'
    );
    $this->assertTrue($nameType instanceof Topic);
    
    $topic = $tm->getConstructByItemIdentifier($this->tmLocator . '#topic');
    $this->assertTrue($topic instanceof Topic);
    $names = $topic->getNames();
    $this->assertEquals(count($names), 1);
    $name = $names[0];
    
    $value = $name->getValue();
    $this->assertEquals($value, 'Emne');
    
    $scope = $name->getScope();
    $this->assertEquals(count($scope), 1);
    $theme = $scope[0];
    $iids = $theme->getItemIdentifiers();
    $this->assertEquals(count($iids), 1);
    $iid = $iids[0];
    $fragment = $this->getIidFragment($iid);
    $this->assertEquals($fragment, '#norwegian');
    
    $type = $name->getType();
    $this->assertEquals($nameType->getId(), $type->getId());
  }
  
  public function testNameDuplicate() {
    $file = $this->xtmIncPath . 'name-duplicate.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 2);
    
    $topic = $tm->getConstructByItemIdentifier($this->tmLocator . '#topic');
    $this->assertTrue($topic instanceof Topic);
    $names = $topic->getNames();
    $this->assertEquals(count($names), 1);
    $name = $names[0];
    
    $value = $name->getValue();
    $this->assertEquals($value, 'Topic');
  }
  
  public function testNameDuplicateMerge() {
    $file = $this->xtmIncPath . 'name-duplicate-merge.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 2);
    
    $topic = $tm->getConstructByItemIdentifier($this->tmLocator . '#topic');
    $this->assertTrue($topic instanceof Topic);
    $names = $topic->getNames();
    $this->assertEquals(count($names), 1);
    $name = $names[0];
    
    $value = $name->getValue();
    $this->assertEquals($value, 'Topics');
    
    $type = $name->getType();
    $this->assertTrue($type instanceof Topic);
    $iids = $type->getItemIdentifiers();
    $this->assertEquals(count($iids), 2);
    $this->assertFalse($iids[0] === $iids[1]);
    
    $_iids = array(
      $this->tmLocator . '#flertall',
      $this->tmLocator . '#plural'
    );
    foreach ($iids as $iid) {
      $this->assertTrue(in_array($iid, $_iids));
    }
  }
  
  private function _testNameTypeBeforeAfter() {
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 2);
    
    $nameType = $tm->getTopicBySubjectIdentifier(
    	'http://psi.topicmaps.org/iso13250/model/topic-name'
    );
    $this->assertTrue($nameType instanceof Topic);
    
    $topic = $tm->getConstructByItemIdentifier($this->tmLocator . '#topic');
    $this->assertTrue($topic instanceof Topic);
    
    $names = $topic->getNames();
    $this->assertEquals(count($names), 1);
    $name = $names[0];
    
    $type = $name->getType();
    $this->assertEquals($type->getId(), $nameType->getId());
  }
  
}
?>