<?php
/*
 * QuaaxTM is an implementation of PHPTMAPI which uses MySQL with InnoDB as 
 * storage engine.
 * 
 * Copyright (C) 2009 Johannes Schmidt <joschmidt@users.sourceforge.net>
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

require_once('PHPTMAPITestCase.php');

/**
 * TMAPI/PHPTMAPI feature string tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class FeatureStringTest extends PHPTMAPITestCase {
  
  private static $automerge = 'http://tmapi.org/features/automerge/',
                  $readonly = 'http://tmapi.org/features/readOnly/',
                  $typeInstAssoc = 'http://tmapi.org/features/type-instance-associations';
          
  private $tmSystemFactory;
  
  /**
   * @override
   */
  public function setUp() {
    $this->tmSystemFactory = TopicMapSystemFactory::newInstance();
  }
  
  /**
   * @override
   */
  public function tearDown() {
    $this->tmSystemFactory = null;
  }
  
  public function testTypeInstanceAssociations() {
    $this->_testFeature(self::$typeInstAssoc);
  }

  public function testAutomerge() {
    $this->_testFeature(self::$automerge);
  }

  public function testReadOnly() {
    $this->_testFeature(self::$readonly);
  }
  
  private function _testFeature($featureString) {
    $valueInFactory = $this->tmSystemFactory->getFeature($featureString);
    try {
      $this->tmSystemFactory->setFeature($featureString, true);
    } catch (FeatureNotRecognizedException $e) {
      $this->fail('This engine is not PHPTMAPI 2.0 compatible!');
    } catch (FeatureNotSupportedException $e) {
      // no op. - just check if feature string is recognized
    }
    
    $tmSystem = $this->_createTopicMapystem();
    $valueInSystem = $tmSystem->getFeature($featureString);
    $this->assertEquals($valueInFactory, $valueInSystem, 
      'The system has a different value of ' . $featureString .  ' than the factory!');
  }
  
  private function _createTopicMapystem() {
    return $this->tmSystemFactory->newTopicMapSystem();
  }
}
?>
