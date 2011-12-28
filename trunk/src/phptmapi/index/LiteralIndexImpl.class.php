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

/**
 * Index for literal values stored in a topic map.
 *
 * @package index
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
final class LiteralIndexImpl extends IndexImpl implements LiteralIndex
{  
  /**
   * Retrieves the topic names in the topic map which have a value equal to 
   * <var>value</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param string The value of the {@link NameImpl}s to be returned.
   * @return array An array containing {@link NameImpl}s.
   * @throws InvalidArgumentException If the value is <var>null</var>.
   */
  public function getNames($value)
  {
    if (is_null($value)) {
      throw new InvalidArgumentException(
      	'Error in ' . __METHOD__ . ': Value must not be null!'
      );
    }
    $names = array();
    // create a legal SQL string
    $escapedValue = $this->_mysql->escapeString($value);
    $query = 'SELECT t1.id, t1.topic_id, t1.type_id 
    	FROM ' . $this->_config['table']['topicname'] . ' t1  
      INNER JOIN ' . $this->_config['table']['topic'] . ' t2 ON t1.topic_id = t2.id
			WHERE t1.value = "' . $escapedValue . '" AND t2.topicmap_id = ' . $this->_tmDbId;
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $propertyHolder['type_id'] = $result['type_id'];
      $propertyHolder['value'] = $value;
      
      $parent = new TopicImpl(
        $result['topic_id'], $this->_mysql, $this->_config, $this->_topicMap
      );
      
      $names[] = new NameImpl(
        $result['id'], 
        $this->_mysql, 
        $this->_config, 
        $parent, 
        $this->_topicMap, 
        $propertyHolder
      );
    }
    return $names;
  }

  /**
   * Returns the {@link OccurrenceImpl}s in the topic map whose value property 
   * matches <var>value</var> and whose datatye is <var>datatype</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param string The value of the {@link OccurrenceImpl}s to be returned.
   * @param string A URI indicating the datatype of the {@link OccurrenceImpl}s. 
   *        E.g. http://www.w3.org/2001/XMLSchema#string indicates a string value.
   * @return array An array containing {@link OccurrenceImpl}s.
   * @throws InvalidArgumentException If the value or datatype is <var>null</var>.
   */
  public function getOccurrences($value, $datatype)
  {
    if (is_null($value) || is_null($datatype)) {
      throw new InvalidArgumentException(
      	'Error in ' . __METHOD__ . ': Value and/or datatype must not be null!'
      );
    }
    $occs = array();
    // create legal SQL strings
    $escapedValue = $this->_mysql->escapeString($value);
    $escapedDatatype = $this->_mysql->escapeString($datatype);
    $query = 'SELECT t1.id, t1.topic_id, t1.type_id 
    	FROM ' . $this->_config['table']['occurrence'] . ' t1  
      INNER JOIN ' . $this->_config['table']['topic'] . ' t2 ON t1.topic_id = t2.id
			WHERE t1.value = "' . $escapedValue . '" 
			AND t1.datatype = "' . $escapedDatatype . '" 
			AND t2.topicmap_id = ' . $this->_tmDbId;
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $propertyHolder['type_id'] = $result['type_id'];
      $propertyHolder['value'] = $value;
      $propertyHolder['datatype'] = $datatype;
      
      $parent = new TopicImpl(
        $result['topic_id'], $this->_mysql, $this->_config, $this->_topicMap
      );
      
      $occs[] = new OccurrenceImpl(
        $result['id'], 
        $this->_mysql, 
        $this->_config, 
        $parent, 
        $this->_topicMap, 
        $propertyHolder
      );
    }
    return $occs;
  }

  /**
   * Returns the {@link VariantImpl}s in the topic map whose value property 
   * matches <var>value</var> and whose datatye is <var>datatype</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param string The value of the {@link VariantImpl}s to be returned.
   * @param string A URI indicating the datatype of the {@link VariantImpl}s. 
   *        E.g. http://www.w3.org/2001/XMLSchema#string indicates a string value.
   * @return array An array containing {@link VariantImpl}s.
   * @throws InvalidArgumentException If the value or datatype is <var>null</var>.
   */
  public function getVariants($value, $datatype)
  {
    if (is_null($value) || is_null($datatype)) {
      throw new InvalidArgumentException(
      	'Error in ' . __METHOD__ . ': Value and/or datatype must not be null!'
      );
    }
    $variants = array();
    // create legal SQL strings
    $escapedValue = $this->_mysql->escapeString($value);
    $escapedDatatype = $this->_mysql->escapeString($datatype);
    $query = 'SELECT t1.id, t1.topicname_id, t1.hash, t2.topic_id, t2.type_id, t2.value AS name_value 
    	FROM ' . $this->_config['table']['variant'] . ' t1  
      INNER JOIN ' . $this->_config['table']['topicname'] . ' t2 ON t1.topicname_id = t2.id
    	INNER JOIN ' . $this->_config['table']['topic'] . ' t3 ON t2.topic_id = t3.id
			WHERE t1.value = "' . $escapedValue . '" 
			AND t1.datatype = "' . $escapedDatatype . '" 
			AND t3.topicmap_id = ' . $this->_tmDbId;
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $parentTopic = new TopicImpl(
        $result['topic_id'], $this->_mysql, $this->_config, $this->_topicMap
      );
      
      $propertyHolder['type_id'] = $result['type_id'];
      $propertyHolder['value'] = $result['name_value'];
      
      $parentName = new NameImpl(
        $result['topicname_id'], 
        $this->_mysql, 
        $this->_config, 
        $parentTopic, 
        $this->_topicMap, 
        $propertyHolder
      );
      
      $propertyHolder = array();
      
      $propertyHolder['value'] = $value;
      $propertyHolder['datatype'] = $datatype;
      
      $variants[] = new VariantImpl(
        $result['id'], 
        $this->_mysql, 
        $this->_config, 
        $parentName, 
        $this->_topicMap, 
        $propertyHolder, 
        $result['hash']
      );
    }
    return $variants;
  }
}
?>