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
 * Index tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class IndexTest extends PHPTMAPITestCase
{
  public function testIndex()
  {
    $this->assertTrue($this->_topicMap instanceof TopicMap);
    $index = $this->_topicMap->getIndex('TypeInstanceIndexImpl');
    $this->assertTrue($index instanceof Index);
    $index = $this->_topicMap->getIndex('LiteralIndexImpl');
    $this->assertTrue($index instanceof Index);
    $index = $this->_topicMap->getIndex('ScopedIndexImpl');
    $this->assertTrue($index instanceof Index);
    try {
      $this->_topicMap->getIndex('Index' . time());
      $this->fail('This index is not supported!');
    } catch (FeatureNotSupportedException $e) {
      // no op.
    }
    $this->assertNull($index->open());
    $this->assertNull($index->close());
    $this->assertTrue($index->isOpen());
    $this->assertTrue($index->isAutoUpdated());
    $this->assertNull($index->reindex());
  }
}
?>