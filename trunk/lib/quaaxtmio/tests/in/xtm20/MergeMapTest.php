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
 * Merge map tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class MergeMapTest extends TestCase {
  
  public function testTopicMapSystem() {
    $this->assertTrue($this->sharedFixture instanceof TopicMapSystem);
  }
  
  public function testMergeMap() {
    $file = $this->xtmIncPath . 'mergemap.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 3);
    
    foreach ($topics as $topic) {
      $iids = $topic->getItemIdentifiers();
      foreach ($iids as $iid) {
        $fragment = $this->getIidFragment($iid);
        $this->assertEquals($fragment, '#topic');
      }
    }
  }
  
  public function testMergeMapMerge() {
    $file = $this->xtmIncPath . 'mergemap-merge.xtm';
    $this->readAndParse($file);
    
    $tmDir = $this->getTmDir();
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 3);
    
    $nameType = $tm->getTopicBySubjectIdentifier(
    	'http://psi.topicmaps.org/iso13250/model/topic-name'
    );
    $this->assertTrue($nameType instanceof Topic);
    
    $topicType = $tm->getConstructByItemIdentifier(
      $tmDir . DIRECTORY_SEPARATOR . 'mergemap-merge.sub#type'
    );
    $this->assertTrue($topicType instanceof Topic);
    $names = $topicType->getNames();
    $this->assertEquals(count($names), 1);

    $name = $names[0];
    $this->assertEquals($name->getValue(), 'Type');
    
    $topic = $tm->getConstructByItemIdentifier($this->tmLocator . '#topic');
    $this->assertTrue($topic instanceof Topic);
  }
  
  public function testMergeMapItemId() {
    $file = $this->xtmIncPath . 'mergemap-itemid.xtm';
    $this->readAndParse($file);
    
    $tmDir = $this->getTmDir();
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $iids = $tm->getItemIdentifiers();
    $this->assertEquals(count($iids), 1);
    
    $iid = $iids[0];
    $this->assertEquals(
      $iid, $tmDir . DIRECTORY_SEPARATOR . 'mergemap-itemid.sub#the-topic-map'
    );
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 2);
    
    $topic = $tm->getConstructByItemIdentifier($this->tmLocator . '#topic');
    $this->assertTrue($topic instanceof Topic);
    
    $topic = $tm->getConstructByItemIdentifier(
      $tmDir . DIRECTORY_SEPARATOR . 'mergemap-itemid.sub#topic'
    );
    $this->assertTrue($topic instanceof Topic);
  }
  
}
?>