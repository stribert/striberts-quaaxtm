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
 * Occurrence tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class OccurrenceTest extends TestCase {

  public function testTopicMapSystem() {
    $this->assertTrue($this->sharedFixture instanceof TopicMapSystem);
  }

  public function testOccurrence() {
    $file = $this->xtmIncPath . 'occurrence.xtm';
    $this->readAndParse($file);
    $this->_testOccurrence(
			'Testing resource data.',
      parent::$dtString
    );
  }

  public function testOccurrenceUmlaut() {
    $file = $this->xtmIncPath . 'occurrence-umlaut.xtm';
    $this->readAndParse($file);
    $this->_testOccurrence(
    	'Blöße', 
      parent::$dtString
    );
  }
  
  public function testOccurrenceCdata() {
    $file = $this->xtmIncPath . 'occurrence-cdata.xtm';
    $this->readAndParse($file);
    $this->_testOccurrence(
    	'<?xml version="1.0"?><馬籠>öäüß</馬籠>', 
      'http://www.w3.org/2001/XMLSchema#anyType'
    );
  }
  
  public function testOccurrenceScope() {
    $file = $this->xtmIncPath . 'occurrence-scope.xtm';
    $this->readAndParse($file);
    $this->_testOccurrenceScope(
    	'Testing resource data.', 
      parent::$dtString, 
      '#english'
    );
  }
  
  public function testOccurrenceScopeDuplicate() {
    $file = $this->xtmIncPath . 'occurrence-scope-duplicate.xtm';
    $this->readAndParse($file);
    $this->_testOccurrenceScope(
    	'Testing resource data.', 
      parent::$dtString, 
      '#english'
    );
  }
  
  public function testOccurrenceResourceRef() {
    $file = $this->xtmIncPath . 'occurrence-resourceref.xtm';
    $this->readAndParse($file);
    $this->_testOccurrence(
    	'http://www.isotopicmaps.org', 
      parent::$dtUri
    ); 
  }
  
  public function testOccurrenceResourceRefRelative() {
    $file = $this->xtmIncPath . 'occurrence-resourceref-relative.xtm';
    $this->readAndParse($file);
    $this->_testOccurrence(
    	'photo.jpg', 
      parent::$dtUri
    ); 
  }
  
  public function testOccurrenceResourceDataUri() {
    $file = $this->xtmIncPath . 'occurrence-resourcedata-uri.xtm';
    $this->readAndParse($file);
    $this->_testOccurrence(
    	'http://www.isotopicmaps.org', 
      parent::$dtUri
    ); 
  }
  
  public function testOccurrenceResourceDataUriRelative() {
    $file = $this->xtmIncPath . 'occurrence-resourcedata-uri-relative.xtm';
    $this->readAndParse($file);
    $this->_testOccurrence(
    	'photo.jpg', 
      parent::$dtUri
    ); 
  }
  
  public function testOccurrenceReifier() {
    $file = $this->xtmIncPath . 'occurrence-reifier.xtm';
    $this->readAndParse($file);
    
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);
    
    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 3);

    $topic = $tm->getConstructByItemIdentifier($this->tmLocator . '#occtype');
    $this->assertTrue($topic instanceof Topic);

    $occTopic = $tm->getConstructByItemIdentifier($this->tmLocator . '#topic');
    $this->assertTrue($occTopic instanceof Topic);
    $occs = $occTopic->getOccurrences();
    $this->assertEquals(count($occs), 1);
    
    $occ = $occs[0];
    $reifier = $occ->getReifier();
    $this->assertTrue($reifier instanceof Topic);
    $_reifier = $tm->getConstructByItemidentifier($this->tmLocator . '#reifier');
    $this->assertEquals($reifier->getId(), $_reifier->getId());
  }
  
  public function testOccurrenceDuplicate() {
    $file = $this->xtmIncPath . 'occurrence-duplicate.xtm';
    $this->readAndParse($file);
    $this->_testOccurrence(
    	'Testing resource data.', 
      parent::$dtString
    ); 
  }
  
  public function testOccurrenceDatatypeUnknown() {
    $file = $this->xtmIncPath . 'occurrence-datatype-unknown.xtm';
    $this->readAndParse($file);
    $this->_testOccurrence(
    	'BOGUS', 
      'http://example.com/bogus'
    ); 
  }
  
  private function _testOccurrenceScope($value, $datatype, $scopeIidFragment) {
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);

    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 3);

    $topic = $tm->getConstructByItemIdentifier($this->tmLocator . '#occtype');
    $this->assertTrue($topic instanceof Topic);

    $occTopic = $tm->getConstructByItemIdentifier($this->tmLocator . '#topic');
    $this->assertTrue($occTopic instanceof Topic);
    $occs = $occTopic->getOccurrences();
    $this->assertEquals(count($occs), 1);

    $occ = $occs[0];
    $this->assertEquals($occ->getValue(), $value);
    $this->assertEquals($occ->getDatatype(), $datatype);
    $type = $occ->getType();
    $this->assertTrue($type instanceof Topic);
    $iids = $type->getItemIdentifiers();
    $this->assertEquals(count($iids), 1);
    
    $iid = $iids[0];
    $fragment = $this->getIidFragment($iid);
    $this->assertEquals($fragment, '#occtype');
    
    $scope = $occ->getScope();
    $this->assertEquals(count($scope), 1);
    
    $theme = $scope[0];
    $this->assertTrue($theme instanceof Topic);
    $iids = $theme->getItemIdentifiers();
    $this->assertEquals(count($iids), 1);
    
    $iid = $iids[0];
    $fragment = $this->getIidFragment($iid);
    $this->assertEquals($fragment, $scopeIidFragment);
  }

  private function _testOccurrence($value, $datatype) {
    $tm = $this->sharedFixture->getTopicMap($this->tmLocator);
    $this->assertTrue($tm instanceof TopicMap);

    $topics = $tm->getTopics();
    $this->assertEquals(count($topics), 2);

    $topic = $tm->getConstructByItemIdentifier($this->tmLocator . '#occtype');
    $this->assertTrue($topic instanceof Topic);

    $occTopic = $tm->getConstructByItemIdentifier($this->tmLocator . '#topic');
    $this->assertTrue($occTopic instanceof Topic);
    $occs = $occTopic->getOccurrences();
    $this->assertEquals(count($occs), 1);

    $occ = $occs[0];
    $this->assertEquals($occ->getValue(), $value);
    $this->assertEquals($occ->getDatatype(), $datatype);
    $type = $occ->getType();
    $this->assertTrue($type instanceof Topic);
    $iids = $type->getItemIdentifiers();
    $this->assertEquals(count($iids), 1);

    $iid = $iids[0];
    $fragment = $this->getIidFragment($iid);
    $this->assertEquals($fragment, '#occtype');
  }
}
?>