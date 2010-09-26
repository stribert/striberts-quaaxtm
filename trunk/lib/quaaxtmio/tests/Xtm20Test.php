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
 * XTM 2.0 writing tests using CXTM.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class Xtm20Test extends TestCase {
  
  private static $reader = 'XTM20TopicMapReader';
  
  public function testTopicMapSystem() {
    $this->assertTrue($this->sharedFixture instanceof TopicMapSystem);
  }
  
  /**
   * @dataProvider getValidFiles
   */
  public function testValidXtm($xtmFile) {
    $xtmDir = $this->cxtmIncPath . 'xtm2' . DIRECTORY_SEPARATOR . 'in';
    $cxtmDir = $this->cxtmIncPath . 'xtm2' . DIRECTORY_SEPARATOR . 'baseline';
    // read source XTM
    $this->readSrcFile($xtmDir . DIRECTORY_SEPARATOR . $xtmFile, self::$reader);
    $cxtmBase = $this->readCxtmFile($cxtmDir . DIRECTORY_SEPARATOR . $xtmFile . '.cxtm');
    // get the topic map and write the XTM
    $topicMap = $this->sharedFixture->getTopicMap($this->tmLocator);
    $xtmWriter = new PHPTMAPIXTM20Writer();
    $xtm = $xtmWriter->write($topicMap, $this->tmLocator);
    $topicMap->remove();
    // read written XTM
    $this->read($xtm, self::$reader);
    // get the topic map and write the CXTM
    $topicMap = $this->sharedFixture->getTopicMap($this->tmLocator);
    $cxtmWriter = new PHPTMAPICXTMWriter();
    $cxtm = $cxtmWriter->write($topicMap, $this->tmLocator, true, 'TopicImpl-');
    $this->assertEquals($cxtm, $cxtmBase);
  }

  public function getValidFiles() {
    return $files = $this->getSrcFiles('xtm2' . DIRECTORY_SEPARATOR . 'in');
  }
  
}
?>