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
  'XTM20TopicMapReader.class.php'
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
  'PHPTMAPIXTM20Writer.class.php'
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
 * Provides common functionality for io tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class TestCase extends PHPUnit_Framework_TestCase {
  
  protected $sharedFixture;
  
  private $preservedBaseLocators;
  
  protected function setUp() {
    // allow all extending tests being stand alone
    if (!$this->sharedFixture instanceof TopicMapSystem) {
      $tmSystemFactory = TopicMapSystemFactory::newInstance();
      // QuaaxTM specific feature
      try {
        $tmSystemFactory->setFeature(VocabularyUtils::QTM_FEATURE_AUTO_DUPL_REMOVAL, false);
      } catch (FactoryConfigurationException $e) {
        // no op.
      }
      $this->sharedFixture = $tmSystemFactory->newTopicMapSystem();
    }
    $this->preservedBaseLocators = $this->sharedFixture->getLocators();
    $this->tmLocator = null;
    $this->cxtmIncPath = dirname(__FILE__) . 
                          DIRECTORY_SEPARATOR . 
  												'cxtm-tests-0.3' . 
                          DIRECTORY_SEPARATOR;
  }
  
  protected function tearDown() {
    $this->tmLocator = null;
    $locators = $this->sharedFixture->getLocators();
    foreach ($locators as $locator) {
      if (!in_array($locator, $this->preservedBaseLocators)) {
        $tm = $this->sharedFixture->getTopicMap($locator);
        $tm->close();
        $tm->remove();
      }
    }
  }
  
  protected function readSrcFile($file, $reader) {
    $tmLocator = 'file://' . $file;
    $tmHandler = new PHPTMAPITopicMapHandler($this->sharedFixture, $tmLocator);
    $this->tmLocator = $tmHandler->getBaseLocator();
    $reader = new $reader($tmHandler);
    $reader->readFile($file);
  }
  
  protected function read($xtm, $reader) {
    $tmHandler = new PHPTMAPITopicMapHandler($this->sharedFixture, $this->tmLocator);
    $reader = new $reader($tmHandler);
    $reader->read($xtm);
  }
  
  protected function readCxtmFile($file) {
    $cxtm = MIOUtil::readFile('file://' . $file);
    return trim($cxtm);
  }
  
  protected function getSrcFiles($dir) {
    $files = array();
    $cxtmIncPath = dirname(__FILE__) . 
                    DIRECTORY_SEPARATOR . 
  									'cxtm-tests-0.3' . 
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