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
  '..' . 
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
  '..' . 
  DIRECTORY_SEPARATOR . 
  '..' . 
  DIRECTORY_SEPARATOR . 
  'src' . 
  DIRECTORY_SEPARATOR . 
  'in' . 
  DIRECTORY_SEPARATOR . 
  'XTM20TopicMapReader.class.php'
);

/**
 * Provides common functionality for tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class TestCase extends PHPUnit_Framework_TestCase {
  
  protected static  $dtString = 'http://www.w3.org/2001/XMLSchema#string', 
  					        $dtUri = 'http://www.w3.org/2001/XMLSchema#anyURI';
  
  protected $sharedFixture, 
            $tmLocator,
            $xtmIncPath;
  
  private $preservedBaseLocators;
  
  protected function setUp() {
    // allow all extending tests being stand alone
    if (!$this->sharedFixture instanceof TopicMapSystem) {
      $tmSystemFactory = TopicMapSystemFactory::newInstance();
      // QuaaxTM specific feature
      try {
        $tmSystemFactory->setFeature(VocabularyUtils::QTM_FEATURE_AUTO_DUPL_REMOVAL, false);
      } catch (FeatureNotRecognizedException $e) {
        // no op.
      }
      $tmSystem = $tmSystemFactory->newTopicMapSystem();
      $this->sharedFixture = $tmSystem;
    }
    $this->preservedBaseLocators = $this->sharedFixture->getLocators();
    $this->tmLocator = null;
    $this->xtmIncPath = dirname(__FILE__) . 
                        DIRECTORY_SEPARATOR . 
                        'xtm20src' . 
                        DIRECTORY_SEPARATOR;
  }
  
  protected function tearDown() {
    $locators = $this->sharedFixture->getLocators();
    foreach ($locators as $locator) {
      if (!in_array($locator, $this->preservedBaseLocators)) {
        $tm = $this->sharedFixture->getTopicMap($locator);
        $tm->close();
        $tm->remove();
      }
    }
    $this->tmLocator = null;
  }
  
  protected function readAndParse($file) {
    $tmLocator = 'file://' . $file;
    $tmHandler = new PHPTMAPITopicMapHandler($this->sharedFixture, $tmLocator);
    $this->tmLocator = $tmHandler->getBaseLocator();
    $reader = new XTM20TopicMapReader($tmHandler);
    $reader->readFile($file);
  }
  
  protected function getIidFragment($iid) {
    return substr($iid, strpos($iid, '#'), strlen($iid));
  }
  
  protected function getTmDir() {
    return substr($this->tmLocator, 0, strrpos($this->tmLocator, DIRECTORY_SEPARATOR));
  }
}
?>