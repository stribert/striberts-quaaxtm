<?php
/*
 * QuaaxTM is an implementation of PHPTMAPI which uses MySQL with InnoDB as 
 * storage engine.
 * 
 * Copyright (C) 2008 Johannes Schmidt <joschmidt@users.sourceforge.net>
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
 * Represents an occurrence item.
 * 
 * See {@link http://www.isotopicmaps.org/sam/sam-model/#sect-occurrence}.
 * 
 * Inherited method <var>getParent()</var> from {@link ConstructImpl} returns the 
 * {@link TopicImpl} to which this occurrence belongs.
 *
 * @package core
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
final class OccurrenceImpl extends ScopedImpl implements Occurrence {
  
  /**
   * Constructor.
   * 
   * @param int The database id.
   * @param Mysql The Mysql object.
   * @param array The configuration data.
   * @param TopicImpl The parent topic.
   * @param TopicMapImpl The containing topic map.
   * @return void
   */
  public function __construct($dbId, Mysql $mysql, array $config, Topic $parent, 
    TopicMap $topicMap) {
    parent::__construct(__CLASS__ . '-' . $dbId, $parent, $mysql, $config, $topicMap);
  }
  
  /**
   * Destructor. If enabled duplicate removal in database takes place.
   * 
   * @return void
   */
  public function __destruct() {
    if ($this->topicMap->getTopicMapSystem()->getFeature(VocabularyUtils::QTM_FEATURE_AUTO_DUPL_REMOVAL) && 
      !is_null($this->dbId) && !is_null($this->parent->dbId)) $this->parent->finished($this);
  }
  
  /**
   * Returns the string representation of the value.
   * 
   * @return string The string representation of the value (never <var>null</var>).
   */
  public function getValue() {
    $query = 'SELECT value FROM ' . $this->config['table']['occurrence'] . 
      ' WHERE id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetch();
    return (string) $result['value'];
  }

  /**
   * Returns the URI identifying the datatype of the value.
   * I.e. http://www.w3.org/2001/XMLSchema#string indicates a string value.
   *
   * @return string The datatype of this construct (never <var>null</var>).
   */
  public function getDatatype() {
    $query = 'SELECT datatype FROM ' . $this->config['table']['occurrence'] . 
      ' WHERE id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetch();
    return (string) $result['datatype'];
  }

  /**
   * Sets the value and the datatype.
   *
   * @param string The string representation of the value; must not be <var>null</var>.
   * @param string The URI identifying the datatype of the value; must not be <var>null</var>. 
   *        I.e. http://www.w3.org/2001/XMLSchema#string indicates a string value.
   * @return void
   * @throws {@link ModelConstraintException} If the <var>value</var> or <var>datatype</var> 
   *        is <var>null</var>.
   */
  public function setValue($value, $datatype) {
    if (!is_null($value) && !is_null($datatype)) {
      $value = CharacteristicUtils::canonicalize($value);
      $this->mysql->startTransaction();
      $query = 'UPDATE ' . $this->config['table']['occurrence'] . 
        ' SET value = "' . $value . '", datatype = "' . $datatype . '"' .
        ' WHERE id = ' . $this->dbId;
      $this->mysql->execute($query);
      
      $hash = $this->parent->getOccurrenceHash($this->getType(), $value, $datatype, 
        $this->getScope());
      $this->parent->updateOccurrenceHash($this->dbId, $hash);
      $this->mysql->finishTransaction();
    } else {
      throw new ModelConstraintException($this, __METHOD__ . 
        ConstructImpl::VALUE_DATATYPE_NULL_ERR_MSG);
    }
  }
  
  /**
   * Returns the type of this occurrence.
   *
   * @return TopicImpl
   */
  public function getType() {
    $query = 'SELECT type_id FROM ' . $this->config['table']['occurrence'] . 
      ' WHERE id = ' . $this->dbId;
    $mysqlResult = $this->mysql->execute($query);
    $result = $mysqlResult->fetch();
    return $this->topicMap->getConstructById(TopicMapImpl::TOPIC_CLASS_NAME . '-' . 
      $result['type_id']);
  }

  /**
   * Sets the type of this occurrence.
   * Any previous type is overridden.
   * 
   * @param TopicImpl The topic that should define the nature of this occurrence.
   * @return void
   * @throws {@link ModelConstraintException} If the <var>type</var> does not belong 
   *        to the parent topic map.
   */
  public function setType(Topic $type) {
    if (!$this->getType()->equals($type)) {
      if (!$this->topicMap->equals($type->topicMap)) {
        throw new ModelConstraintException($this, __METHOD__ . 
          parent::SAME_TM_CONSTRAINT_ERR_MSG);
      }
      $this->mysql->startTransaction();
      $query = 'UPDATE ' . $this->config['table']['occurrence'] . 
        ' SET type_id = ' . $type->dbId . 
        ' WHERE id = ' . $this->dbId;
      $this->mysql->execute($query);
      
      $hash = $this->parent->getOccurrenceHash($type, $this->getValue(), $this->getDatatype(), 
        $this->getScope());
      $this->parent->updateOccurrenceHash($this->dbId, $hash);
      $this->mysql->finishTransaction();
    } else {
      return;
    }
  }
  
  /**
   * Returns the reifier of this construct.
   * 
   * @return TopicImpl The topic that reifies this occurrence or
   *        <var>null</var> if this occurrence is not reified.
   */
  public function getReifier() {
    return $this->_getReifier();
  }

  /**
   * @see ConstructImpl::_setReifier()
   */
  public function setReifier($reifier) {
    $this->_setReifier($reifier);
  }
  
  /**
   * Deletes this occurrence.
   * 
   * @override
   * @return void
   */
  public function remove() {
    $query = 'DELETE FROM ' . $this->config['table']['occurrence'] . 
      ' WHERE id = ' . $this->dbId;
    $this->mysql->execute($query);
    if (!$this->mysql->hasError()) {
      $this->id = null;
      $this->dbId = null;
    }
  }
}
?>