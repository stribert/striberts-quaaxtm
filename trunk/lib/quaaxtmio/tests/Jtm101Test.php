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
 * JTM 1.0 and JTM 1.1 reading and writing tests using CXTM.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class Jtm101Test extends TestCase {
  
  private static $reader = 'JTM101TopicMapReader';
  
  public function testTopicMapSystem() {
    $this->assertTrue($this->sharedFixture instanceof TopicMapSystem);
  }
  
  /**
   * @dataProvider getJtm10Files
   */
  public function testJtm10($jtmFile) {
    $jtmDir = $this->cxtmIncPath . 'jtm' . DIRECTORY_SEPARATOR . 'in';
    $cxtmDir = $this->cxtmIncPath . 'jtm' . DIRECTORY_SEPARATOR . 'baseline';
    // read source JTM
    $this->readSrcFile($jtmDir . DIRECTORY_SEPARATOR . $jtmFile, self::$reader);
    $cxtmBase = $this->readCxtmFile($cxtmDir . DIRECTORY_SEPARATOR . $jtmFile . '.cxtm');
    // get the topic map and write the JTM
    $topicMap = $this->sharedFixture->getTopicMap($this->tmLocator);
    $jtmWriter = new PHPTMAPIJTM10Writer();
    $jtm = $jtmWriter->write($topicMap, $this->tmLocator);
    $topicMap->remove();
    // read written JTM
    $this->read($jtm, self::$reader);
    // get the topic map and write the CXTM
    $topicMap = $this->sharedFixture->getTopicMap($this->tmLocator);
    $writer = new PHPTMAPICXTMWriter();
    $cxtm = $writer->write($topicMap, $this->tmLocator);
    $this->assertEquals($cxtm, $cxtmBase);
  }
  
  /**
   * @dataProvider getInvalidJtm10Files
   */
  public function testInvalidJtm10($jtmFile) {
    try {
      $jtmDir = $this->cxtmIncPath . 'jtm' . DIRECTORY_SEPARATOR . 'invalid';
      $this->readSrcFile($jtmDir . DIRECTORY_SEPARATOR . $jtmFile, self::$reader);
      $this->fail('Expected MIOException while parsing ' . $jtmFile . '.');
    } catch (MIOException $e) {
      // no op.
    }
  }
  
  /**
   * @dataProvider getJtm11Files
   */
  public function testJtm11($jtmFile) {
    $jtmDir = $this->cxtmIncPath . 'jtm11' . DIRECTORY_SEPARATOR . 'in';
    $cxtmDir = $this->cxtmIncPath . 'jtm11' . DIRECTORY_SEPARATOR . 'baseline';
    // read source JTM
    $this->readSrcFile($jtmDir . DIRECTORY_SEPARATOR . $jtmFile, self::$reader);
    $cxtmBase = $this->readCxtmFile($cxtmDir . DIRECTORY_SEPARATOR . $jtmFile . '.cxtm');
    // get the topic map and write the JTM
    $topicMap = $this->sharedFixture->getTopicMap($this->tmLocator);
    $jtmWriter = new PHPTMAPIJTM10Writer();
    $jtm = $jtmWriter->write($topicMap, $this->tmLocator);
    $topicMap->remove();
    // read written JTM
    $this->read($jtm, self::$reader);
    // get the topic map and write the CXTM
    $topicMap = $this->sharedFixture->getTopicMap($this->tmLocator);
    $writer = new PHPTMAPICXTMWriter();
    $cxtm = $writer->write($topicMap, $this->tmLocator);
    $this->assertEquals($cxtm, $cxtmBase);
  }
  
  /**
   * @dataProvider getInvalidJtm11Files
   */
  public function testInvalidJtm11($jtmFile) {
    try {
      $jtmDir = $this->cxtmIncPath . 'jtm' . DIRECTORY_SEPARATOR . 'invalid';
      $this->readSrcFile($jtmDir . DIRECTORY_SEPARATOR . $jtmFile, self::$reader);
      $this->fail('Expected MIOException while parsing ' . $jtmFile . '.');
    } catch (MIOException $e) {
      // no op.
    }
  }
  
  public function getJtm10Files() {
    return $this->getSrcFiles('jtm' . DIRECTORY_SEPARATOR . 'in');
  }
  
  public function getInvalidJtm10Files() {
    return $this->getSrcFiles('jtm' . DIRECTORY_SEPARATOR . 'invalid');
  }
  
  public function getJtm11Files() {
    return $this->getSrcFiles('jtm11' . DIRECTORY_SEPARATOR . 'in');
  }
  
  public function getInvalidJtm11Files() {
    return $this->getSrcFiles('jtm11' . DIRECTORY_SEPARATOR . 'invalid');
  }
  
}
?>