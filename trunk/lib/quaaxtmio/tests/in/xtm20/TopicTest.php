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
 * Topic tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class TopicTest extends TestCase {
  
  public function testTopicMapSystem() {
    $this->assertTrue($this->sharedFixture instanceof TopicMapSystem);
  }
  
  public function testTopic() {
    $file = $this->xtmIncPath . 'topic.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 1);
    
    $topic = $topics[0];
    $this->assertTrue($topic instanceof Topic);
    
    $iids = $topic->getItemIdentifiers();
    $this->assertEquals(count($iids), 1);
    
    $iid = $iids[0];
    
    $fragment = $this->getIidFragment($iid);
    $this->assertEquals($fragment, '#topic');
  }
  
  public function testTopicType() {
    $file = $this->xtmIncPath . 'topic-type.xtm';
    $this->readAndParse($file);
    $this->_testTopicType();
  }
  
  public function testTopicTypeDuplicate() {
    $file = $this->xtmIncPath . 'topic-type-duplicate.xtm';
    $this->readAndParse($file);
    $this->_testTopicType();
  }
  
  public function testItemId() {
    $file = $this->xtmIncPath . 'itemid.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 1);
    $topic = $topics[0];
    
    $iids = $topic->getItemIdentifiers();
    $this->assertEquals(count($iids), 2);
    
    $_iids = array(
      $this->tmLocator . '#topic' => $this->tmLocator . '#topic', 
      'http://example.org/#topic' => 'http://example.org/#topic'
    );
    
    foreach ($iids as $iid) {
      $this->assertTrue(in_array($iid, $_iids));
      unset($_iids[$iid]);
    }
  }
  
  public function testMergeThreeWay() {
    $file = $this->xtmIncPath . 'merge-three-way.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 1);
    $topic = $topics[0];
    $iids = $topic->getItemIdentifiers();
    $this->assertEquals(count($iids), 3);
    
    $_iids = array(
      $this->tmLocator . '#topic' => $this->tmLocator . '#topic', 
      $this->tmLocator . '#topic2' => $this->tmLocator . '#topic2',  
      $this->tmLocator . '#topic3' => $this->tmLocator . '#topic3',
    );
    
    foreach ($iids as $iid) {
      $this->assertTrue(in_array($iid, $_iids));
      unset($_iids[$iid]);
    }
  }
  
  public function testSubjid() {
    $file = $this->xtmIncPath . 'subjid.xtm';
    $this->readAndParse($file);
    
    $this->_testSid('http://example.org/#topic');
  }
  
  public function testSubjidDuplicate() {
    $file = $this->xtmIncPath . 'subjid-duplicate.xtm';
    $this->readAndParse($file);
    
    $this->_testSid('photo.jpg');
  }
  
  public function testSubjidEscaping() {
    $file = $this->xtmIncPath . 'subjid-escaping.xtm';
    $this->readAndParse($file);
    
    $this->_testSid('http://example.org/test+folder/#topic');
  }
  
  public function testSubjidFragment() {
    $file = $this->xtmIncPath . 'subjid-fragment.xtm';
    $this->readAndParse($file);
    
    $this->_testSid('#topic');
  }
  
  public function testSubjloc() {
    $file = $this->xtmIncPath . 'subjloc.xtm';
    $this->readAndParse($file);
    
    $this->_testSlo('http://example.org/#topic');
  }
  
  public function testSubjlocDUplicate() {
    $file = $this->xtmIncPath . 'subjloc-duplicate.xtm';
    $this->readAndParse($file);
    
    $this->_testSlo('photo.jpg');
  }
  
  public function testSubjlocFragment() {
    $file = $this->xtmIncPath . 'subjloc-fragment.xtm';
    $this->readAndParse($file);
    
    $this->_testSlo('#topic');
  }
  
  public function testSubjlocRelative() {
    $file = $this->xtmIncPath . 'subjloc-relative.xtm';
    $this->readAndParse($file);
    
    $this->_testSlo('photo.jpg');
  }
  
  public function testSubjlocMultiple() {
    $file = $this->xtmIncPath . 'subjloc-multiple.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 1);
    $topic = $topics[0];
    
    $iids = $topic->getItemIdentifiers();
    $this->assertEquals(count($iids), 1);
    $iid = $iids[0];
    $fragment = $this->getIidFragment($iid);
    $this->assertEquals($fragment, '#topic');
    
    $slos = $topic->getSubjectLocators();
    $this->assertEquals(count($slos), 2);

    $_slos = array(
      'http://example.org/#subject' => 'http://example.org/#subject',
      'http://example.org/#topic' => 'http://example.org/#topic'
    );
    
    foreach ($slos as $slo) {
      $this->assertTrue(in_array($slo, $_slos));
      unset($_slos[$slo]);
    }
    
    $sids = $topic->getSubjectIdentifiers();
    $this->assertEquals(count($sids), 0);
  }
  
  private function _testSid($_sid) {
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 1);
    $topic = $topics[0];
    
    $iids = $topic->getItemIdentifiers();
    $this->assertEquals(count($iids), 1);
    $iid = $iids[0];
    $fragment = $this->getIidFragment($iid);
    $this->assertEquals($fragment, '#topic');
    
    $sids = $topic->getSubjectIdentifiers();
    $this->assertEquals(count($sids), 1);
    $sid = $sids[0];
    $this->assertEquals($sid, $_sid);
    
    $slos = $topic->getSubjectLocators();
    $this->assertEquals(count($slos), 0);
  }
  
  private function _testSlo($_slo) {
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 1);
    $topic = $topics[0];
    
    $iids = $topic->getItemIdentifiers();
    $this->assertEquals(count($iids), 1);
    $iid = $iids[0];
    $fragment = $this->getIidFragment($iid);
    $this->assertEquals($fragment, '#topic');
    
    $slos = $topic->getSubjectLocators();
    $this->assertEquals(count($slos), 1);
    $slo = $slos[0];
    $this->assertEquals($slo, $_slo);
    
    $sids = $topic->getSubjectIdentifiers();
    $this->assertEquals(count($sids), 0);
  }
  
  private function _testTopicType() {
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 2);
    
    $topic1 = $topics[0];
    $this->assertTrue($topic1 instanceof Topic);
    $topic2 = $topics[1];
    $this->assertTrue($topic2 instanceof Topic);
    
    $types = $topic1->getTypes();
    if (!empty($types)) {
      $type = $types[0];
      $iids = $topic1->getItemidentifiers();
      $this->assertEquals(count($iids), 1);
      $iid = $iids[0];
      $fragment = $this->getIidFragment($iid);
      $this->assertEquals($fragment, '#xtm');
      $iids = $topic2->getItemidentifiers();
      $this->assertEquals(count($iids), 1);
      $iid = $iids[0];
      $fragment = $this->getIidFragment($iid);
      $this->assertEquals($fragment, '#format');
    } else {
      $types = $topic2->getTypes();
      $type = $types[0];
      $iids = $topic2->getItemidentifiers();
      $this->assertEquals(count($iids), 1);
      $iid = $iids[0];
      $fragment = $this->getIidFragment($iid);
      $this->assertEquals($fragment, '#xtm');
      $iids = $topic1->getItemidentifiers();
      $this->assertEquals(count($iids), 1);
      $iid = $iids[0];
      $fragment = $this->getIidFragment($iid);
      $this->assertEquals($fragment, '#format');
    }
  }
  
}
?>