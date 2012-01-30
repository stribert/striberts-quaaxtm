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
 * XTM 2.0 and XTM 2.1 reading and writing tests using CXTM.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class Xtm201Test extends TestCase
{  
  private static $_reader = 'XTM201TopicMapReader';
  
  public function testTopicMapSystem()
  {
    $this->assertTrue($this->_sharedFixture instanceof TopicMapSystem);
  }
  
  /**
   * @dataProvider getValid21Files
   */
  public function testValidXtm21($xtmFile)
  {
    $xtmDir = $this->_cxtmIncPath . 'xtm21' . DIRECTORY_SEPARATOR . 'in';
    $cxtmDir = $this->_cxtmIncPath . 'xtm21' . DIRECTORY_SEPARATOR . 'baseline';
    // read source XTM
    $this->_readSrcFile($xtmDir . DIRECTORY_SEPARATOR . $xtmFile, self::$_reader);
    $cxtmBase = $this->_readCxtmFile($cxtmDir . DIRECTORY_SEPARATOR . $xtmFile . '.cxtm');
    // get the topic map and write the XTM
    $topicMap = $this->_sharedFixture->getTopicMap($this->_tmLocator);
    $xtmWriter = new PHPTMAPIXTM201Writer();
    $xtm = $xtmWriter->write($topicMap, $this->_tmLocator);
    $topicMap->remove();
    // read written XTM
    $this->_read($xtm, self::$_reader);
    // get the topic map and write the CXTM
    $topicMap = $this->_sharedFixture->getTopicMap($this->_tmLocator);
    $cxtmWriter = new PHPTMAPICXTMWriter();
    $cxtm = $cxtmWriter->write($topicMap, $this->_tmLocator, true, 'TopicImpl-');
    $this->assertEquals($cxtm, $cxtmBase);
  }
  
  /**
   * @dataProvider getInvalid21Files
   */
  public function testInvalidXtm21($xtmFile)
  {
    try {
      $xtmDir = $this->_cxtmIncPath . 'xtm21' . DIRECTORY_SEPARATOR . 'invalid';
      $this->_readSrcFile($xtmDir . DIRECTORY_SEPARATOR . $xtmFile, self::$_reader);
      $this->fail('Expected exception while parsing ' . $xtmFile . '.');
    } catch (MIOException $e) {
      // no op.
    } catch (PHPTMAPIException $e) {
      // no op.
    } catch (PHPTMAPIRuntimeException $e) {
      // no op.
    }
  }
  
  public function testValidRemoteXtm21File()
  {
    $host = 'http://quaaxtm.sourceforge.net/tests/';
    $xtmFile = 'topic-no-id-iid.xtm';
    $cxtmDir = $this->_cxtmIncPath . 'xtm21' . DIRECTORY_SEPARATOR . 'baseline';
    // read source XTM from remote server
    $this->_readSrcFile($host . $xtmFile, self::$_reader);
    $cxtmBase = $this->_readCxtmFile($cxtmDir . DIRECTORY_SEPARATOR . $xtmFile . '.cxtm');
    // get the topic map and write the XTM
    $topicMap = $this->_sharedFixture->getTopicMap($this->_tmLocator);
    $xtmWriter = new PHPTMAPIXTM201Writer();
    $xtm = $xtmWriter->write($topicMap, $this->_tmLocator);
    $topicMap->remove();
    // read written XTM
    $this->_read($xtm, self::$_reader);
    // get the topic map and write the CXTM
    $topicMap = $this->_sharedFixture->getTopicMap($this->_tmLocator);
    $cxtmWriter = new PHPTMAPICXTMWriter();
    $cxtm = $cxtmWriter->write($topicMap, $this->_tmLocator, true, 'TopicImpl-');
    $this->assertEquals($cxtm, $cxtmBase);
  }
  
  /**
   * @dataProvider getValid20Files
   */
  public function testValidXtm20($xtmFile)
  {
    $xtmDir = $this->_cxtmIncPath . 'xtm2' . DIRECTORY_SEPARATOR . 'in';
    $cxtmDir = $this->_cxtmIncPath . 'xtm2' . DIRECTORY_SEPARATOR . 'baseline';
    // read source XTM
    $this->_readSrcFile($xtmDir . DIRECTORY_SEPARATOR . $xtmFile, self::$_reader);
    $cxtmBase = $this->_readCxtmFile($cxtmDir . DIRECTORY_SEPARATOR . $xtmFile . '.cxtm');
    // get the topic map and write the XTM
    $topicMap = $this->_sharedFixture->getTopicMap($this->_tmLocator);
    $xtmWriter = new PHPTMAPIXTM201Writer();
    $xtm = $xtmWriter->write($topicMap, $this->_tmLocator, '2.0');
    $topicMap->remove();
    // read written XTM
    $this->_read($xtm, self::$_reader);
    // get the topic map and write the CXTM
    $topicMap = $this->_sharedFixture->getTopicMap($this->_tmLocator);
    $cxtmWriter = new PHPTMAPICXTMWriter();
    $cxtm = $cxtmWriter->write($topicMap, $this->_tmLocator, true, 'TopicImpl-');
    $this->assertEquals($cxtm, $cxtmBase);
  }
  
  /**
   * @dataProvider getInvalid20Files
   */
  public function testInvalidXtm20($xtmFile)
  {
    try {
      $xtmDir = $this->_cxtmIncPath . 'xtm2' . DIRECTORY_SEPARATOR . 'invalid';
      $this->_readSrcFile($xtmDir . DIRECTORY_SEPARATOR . $xtmFile, self::$_reader);
      $this->fail('Expected exception while parsing ' . $xtmFile . '.');
    } catch (MIOException $e) {
      // no op.
    } catch (PHPTMAPIException $e) {
      // no op.
    } catch (PHPTMAPIRuntimeException $e) {
      // no op.
    }
  }
  
  public function testInvalidXml()
  {
    $invalidXml = '<foo><bar>baz</bar</foo>';
    $tmLocator = 'http://localhost/tm/' . uniqid();
    $tmHandler = new PHPTMAPITopicMapHandler($this->_sharedFixture, $tmLocator);
    $reader = new XTM201TopicMapReader($tmHandler);
    try {
      $reader->read($invalidXml);
      $this->fail('XML to read is invalid.');
    } catch (MIOException $e) {
      $msg = $e->getMessage();
      $this->assertTrue(!empty($msg));
    }
  }
  
  public function testInvalidXmlEncoding()
  {
    $tmLocator = 'http://localhost/tm/' . uniqid();
    $tmHandler = new PHPTMAPITopicMapHandler($this->_sharedFixture, $tmLocator);
    try {
      $reader = new XTM201TopicMapReader($tmHandler, 'foo');
      $this->fail('Should not have been able to create a parser with encoding "foo".');
    } catch (MIOException $e) {
      $msg = $e->getMessage();
      $this->assertTrue(!empty($msg));
    }
  }

  public function getValid21Files()
  {
    return $files = $this->_getSrcFiles('xtm21' . DIRECTORY_SEPARATOR . 'in');
  }
  
  public function getInvalid21Files()
  {
    return $files = $this->_getSrcFiles('xtm21' . DIRECTORY_SEPARATOR . 'invalid');
  }
  
  public function getValid20Files()
  {
    return $files = $this->_getSrcFiles('xtm2' . DIRECTORY_SEPARATOR . 'in');
  }
  
  public function getInvalid20Files()
  {
    return $files = $this->_getSrcFiles('xtm2' . DIRECTORY_SEPARATOR . 'invalid');
  }
}
?>