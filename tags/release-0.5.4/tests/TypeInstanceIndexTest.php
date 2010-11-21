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
 * Type instance index tests.
 *
 * @package test
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class TypeInstanceIndexTest extends PHPTMAPITestCase {
  
  public function testGetTopics() {
    $tm = $this->topicMap;
    $this->assertTrue($tm instanceof TopicMap);
    $index = $tm->getIndex('TypeInstanceIndexImpl');
    $this->assertTrue($index instanceof Index);

    $type1 = $tm->createTopic();
    $type2 = $tm->createTopic();
    $instance1 = $tm->createTopic();
    $instance2 = $tm->createTopic();
    $instance3 = $tm->createTopic();
    
    $instance1->addtype($type1);
    $instance1->addtype($type2);
    $instance2->addtype($type1);
    $instance2->addtype($type2);
    $instance3->addtype($type1);
    
    $topics = $index->getTopics(array(), true);
    $this->assertEquals(count($topics), 3);
    $this->assertFalse($topics[0]->equals($topics[1]));
    $this->assertFalse($topics[0]->equals($topics[2]));
    $this->assertFalse($topics[1]->equals($topics[2]));
    
    $topics = $index->getTopics(array($type1), true);
    $this->assertEquals(count($topics), 3);
    $this->assertFalse($topics[0]->equals($topics[1]));
    $this->assertFalse($topics[0]->equals($topics[2]));
    $this->assertFalse($topics[1]->equals($topics[2]));
    
    $topics = $index->getTopics(array($type1), false);
    $this->assertEquals(count($topics), 3);
    $this->assertFalse($topics[0]->equals($topics[1]));
    $this->assertFalse($topics[0]->equals($topics[2]));
    $this->assertFalse($topics[1]->equals($topics[2]));
    
    $topics = $index->getTopics(array($type1, $type2), false);
    $this->assertEquals(count($topics), 3);
    $this->assertFalse($topics[0]->equals($topics[1]));
    $this->assertFalse($topics[0]->equals($topics[2]));
    $this->assertFalse($topics[1]->equals($topics[2]));
    
    $topics = $index->getTopics(array($type1, $type2), true);
    $this->assertEquals(count($topics), 2);
    $this->assertFalse($topics[0]->equals($topics[1]));
    
    $topics = $index->getTopics(array($tm->createTopic()), true);
    $this->assertEquals(count($topics), 0);
  }
}
?>