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

require_once(
  dirname(__FILE__) . 
  DIRECTORY_SEPARATOR . 
  '..' . 
  DIRECTORY_SEPARATOR . 
  '..' . 
  DIRECTORY_SEPARATOR . 
  'phptmapi2.0' . 
  DIRECTORY_SEPARATOR . 
  'core' . 
  DIRECTORY_SEPARATOR . 
  'TopicMapSystemFactory.class.php'
);
require_once(
  dirname(__FILE__) . 
  DIRECTORY_SEPARATOR . 
  '..' . 
  DIRECTORY_SEPARATOR . 
  'src' . 
  DIRECTORY_SEPARATOR . 
  'in' . 
  DIRECTORY_SEPARATOR . 
  'PHPTMAPITopicMapHandler.class.php'
);
require_once(
  dirname(__FILE__) . 
  DIRECTORY_SEPARATOR . 
  '..' . 
  DIRECTORY_SEPARATOR . 
  'src' . 
  DIRECTORY_SEPARATOR . 
  'in' . 
  DIRECTORY_SEPARATOR . 
  'XTM201TopicMapReader.class.php'
);
require_once(
  dirname(__FILE__) . 
  DIRECTORY_SEPARATOR . 
  '..' . 
  DIRECTORY_SEPARATOR . 
  'src' . 
  DIRECTORY_SEPARATOR . 
  'out' . 
  DIRECTORY_SEPARATOR . 
  'PHPTMAPIXTM201Writer.class.php'
);
require_once(
  dirname(__FILE__) . 
  DIRECTORY_SEPARATOR . 
  '..' . 
  DIRECTORY_SEPARATOR . 
  'src' . 
  DIRECTORY_SEPARATOR . 
  'in' . 
  DIRECTORY_SEPARATOR . 
  'JTM101TopicMapReader.class.php'
);
require_once(
  dirname(__FILE__) . 
  DIRECTORY_SEPARATOR . 
  '..' . 
  DIRECTORY_SEPARATOR . 
  'src' . 
  DIRECTORY_SEPARATOR . 
  'out' . 
  DIRECTORY_SEPARATOR . 
  'PHPTMAPIJTM10Writer.class.php'
);
require_once(
  dirname(__FILE__) . 
  DIRECTORY_SEPARATOR . 
  '..' . 
  DIRECTORY_SEPARATOR . 
  'src' . 
  DIRECTORY_SEPARATOR . 
  'out' . 
  DIRECTORY_SEPARATOR . 
  'PHPTMAPICXTMWriter.class.php'
);

/**
 * Provides common functions for IO tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class TestCase extends PHPUnit_Framework_TestCase
{  
  protected $_sharedFixture,
            $_tmLocator,
            $_cxtmIncPath;
  
  private $_preservedBaseLocators;
  
  /**
   * @see PHPUnit_Framework_TestCase::setUp()
   * @override
   */
  protected function setUp()
  {
    $tmSystemFactory = TopicMapSystemFactory::newInstance();
    // QuaaxTM specific feature
    try {
      $tmSystemFactory->setFeature(VocabularyUtils::QTM_FEATURE_AUTO_DUPL_REMOVAL, false);
    } catch (FactoryConfigurationException $e) {
      // no op.
    }
    $this->_sharedFixture = $tmSystemFactory->newTopicMapSystem();
    $this->_preservedBaseLocators = $this->_sharedFixture->getLocators();
    $this->_tmLocator = null;
    $this->_cxtmIncPath = dirname(__FILE__) . 
      DIRECTORY_SEPARATOR . 
  		'cxtm-tests-0.4' . 
      DIRECTORY_SEPARATOR;
  }
  
  /**
   * @see PHPUnit_Framework_TestCase::tearDown()
   * @override
   */
  protected function tearDown()
  {
    $locators = $this->_sharedFixture->getLocators();
    foreach ($locators as $locator) {
      if (!in_array($locator, $this->_preservedBaseLocators)) {
        $tm = $this->_sharedFixture->getTopicMap($locator);
        $tm->close();
        $tm->remove();
      }
    }
    $this->_sharedFixture->close();
    $this->_sharedFixture = 
    $this->_tmLocator = null;
  }
  
  protected function _readSrcFile($file, $reader)
  {
    $tmLocator = 'file://' . $file;
    $tmHandler = new PHPTMAPITopicMapHandler($this->_sharedFixture, $tmLocator);
    $this->_tmLocator = $tmHandler->getBaseLocator();
    $reader = new $reader($tmHandler);
    $reader->readFile($file);
  }
  
  protected function _read($xtm, $reader)
  {
    $tmHandler = new PHPTMAPITopicMapHandler($this->_sharedFixture, $this->_tmLocator);
    $reader = new $reader($tmHandler);
    $reader->read($xtm);
  }
  
  protected function _readCxtmFile($file)
  {
    $cxtm = MIOUtil::readFile('file://' . $file);
    return trim($cxtm);
  }
  
  protected function _getSrcFiles($dir)
  {
    $files = array();
    $cxtmIncPath = dirname(__FILE__) . 
      DIRECTORY_SEPARATOR . 
  		'cxtm-tests-0.4' . 
      DIRECTORY_SEPARATOR;
    $allFiles = array_diff(scandir($cxtmIncPath . $dir), array('.', '..', '.svn'));
    foreach ($allFiles as $file) {
      if (substr($file, -3) != 'sub') {
        $files[] = array($file);
      }
    }
    return $files;
  }
}
?>