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
class QTMFeatureStringTest extends PHPTMAPITestCase
{
  private $_tmSystemFactory;
  
  private static $_duplRemoval = 'http://quaaxtm.sourceforge.net/features/auto-duplicate-removal/';
  
  /**
   * @override
   */
  protected function setUp()
  {
    $this->_tmSystemFactory = TopicMapSystemFactory::newInstance();
  }
  
  /**
   * @override
   */
  protected function tearDown()
  {
    $this->_tmSystemFactory = null;
  }
  
  public function testDuplicateRemoval()
  {
    // true
  	$this->_tmSystemFactory->setFeature(self::$_duplRemoval, true);
    $setting = $this->_tmSystemFactory->getFeature(self::$_duplRemoval);
    $this->assertTrue($setting, 'Expected feature enabled!');
    $tmSystem = $this->_tmSystemFactory->newTopicMapSystem();
    $setting = $tmSystem->getFeature(self::$_duplRemoval);
    $this->assertTrue($setting, 'Expected feature enabled!');
    // false
    $this->_tmSystemFactory->setFeature(self::$_duplRemoval, false);
    $setting = $this->_tmSystemFactory->getFeature(self::$_duplRemoval);
    $this->assertFalse($setting, 'Expected feature disabled!');
    $tmSystem = $this->_tmSystemFactory->newTopicMapSystem();
    $setting = $tmSystem->getFeature(self::$_duplRemoval);
    $this->assertFalse($setting, 'Expected feature disabled!');
  }
}
?>
