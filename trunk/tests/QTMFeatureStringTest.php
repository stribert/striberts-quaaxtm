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
 * QuaaxTM feature string tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class QTMFeatureStringTest extends PHPTMAPITestCase {
  
  private static $duplRemoval = 'http://quaaxtm.sourceforge.net/features/auto-duplicate-removal/';
          
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
  
  public function testDuplicateRemoval() {
    $this->tmSystemFactory->setFeature(self::$duplRemoval, true);
    $setting = $this->tmSystemFactory->getFeature(self::$duplRemoval);
    $this->assertTrue($setting, 'Expected feature enabled!');
    $tmSystem = $this->tmSystemFactory->newTopicMapSystem();
    $setting = $tmSystem->getFeature(self::$duplRemoval);
    $this->assertTrue($setting, 'Expected feature enabled!');
    
    $this->tmSystemFactory->setFeature(self::$duplRemoval, false);
    $setting = $this->tmSystemFactory->getFeature(self::$duplRemoval);
    $this->assertFalse($setting, 'Expected feature disabled!');
    $tmSystem = $this->tmSystemFactory->newTopicMapSystem();
    $setting = $tmSystem->getFeature(self::$duplRemoval);
    $this->assertFalse($setting, 'Expected feature disabled!');
  }

}
?>
