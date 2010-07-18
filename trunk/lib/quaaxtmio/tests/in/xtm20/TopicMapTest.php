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
 * Topic map tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class TopicMapTest extends TestCase {
  
  public function testTopicMapSystem() {
    $this->assertTrue($this->sharedFixture instanceof TopicMapSystem);
  }
  
  public function testReifier() {
    $file = $this->xtmIncPath . 'tm-reifier.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 1);
    
    $topic = $topics[0];
    $this->assertTrue($topic instanceof Topic);
    
    $reifier = $tm->getReifier();
    $this->assertEquals($reifier->getId(), $topic->getId());
    
    $iids = $topic->getItemIdentifiers();
    $this->assertEquals(count($iids), 1);
    
    $iid = $iids[0];
    
    $fragment = $this->getIidFragment($iid);
    $this->assertEquals($fragment, '#reifier');
  }
  
  public function testEmpty() {
    $file = $this->xtmIncPath . 'empty.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $this->assertEquals(count($tm->getTopics()), 0);
    
    $this->assertEquals(count($tm->getAssociations()), 0);
    
    $this->assertTrue(is_null($tm->getReifier()));
  }
}
?>