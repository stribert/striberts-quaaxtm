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

require_once('PHPTMAPIGenericWriter.class.php');

/**
 * Writes JTM 1.0 according to {@link http://www.cerny-online.com/jtm/1.0/}.
 * 
 * @package out
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class PHPTMAPIJTM10Writer extends PHPTMAPIGenericWriter {
  
  /**
   * Constructor.
   * 
   * @return void
   */
  public function __construct() {
    parent::__construct();
  }
  
  /**
   * Destructor.
   * 
   * @return void
   */
  public function __destruct() {
    parent::__destruct();
  }
  
  /**
   * Writes JTM 1.0.
   * 
   * @param TopicMap The topic map to write. 
   * @return string The JTM 1.0.
   */
  public function write(TopicMap $topicMap) {
    parent::_setup('topics', 'id', false);
    $this->_struct['version'] = '1.0';
    $this->_struct['item_type'] = 'topicmap';
    parent::write($topicMap);
    return json_encode($this->_struct);
  }
}
?>