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
 * A generic interface to a PHPTMAPI system. 
 * 
 * Any PHPTMAPI system must be capable of providing access to one or more 
 * {@link TopicMapImpl} objects. A PHPTMAPI system may be capable of allowing a client
 * to create new {@link TopicMapImpl} instances.
 *
 * @package core
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
final class TopicMapSystemImpl implements TopicMapSystem
{
  private $_mysql,
          $_config,
          $_properties,
          $_features;
  
  /**
   * Constructor.
   * 
   * @param Mysql The Mysql object.
   * @param array The configuration data of this Topic Map System.
   * @param array The properties of this Topic Map System.
   * @param array The features of this Topic Map System.
   * @return void
   */
  public function __construct(Mysql $mysql, array $config, array $properties, array $features)
  {
    $this->_config = $config;
    $this->_mysql = $mysql;
    $this->_properties = $properties;
    $this->_features = $features;
  }
   
  /**
   * Retrieves a {@link TopicMapImpl} managed by this system with the
   * specified storage address <var>uri</var>. 
   * The string is assumed to be in URI notation.
   * 
   * @param string The storage address to retrieve the {@link TopicMapImpl} from.
   * @return TopicMapImpl|null The instance managed by this system which 
   *        is stored at the specified <var>uri</var>, or <var>null</var> if no 
   *        such {@link TopicMapImpl} is found.
   */
  public function getTopicMap($uri)
  {
    $query = 'SELECT id FROM ' . $this->_config['table']['topicmap'] . 
      ' WHERE locator = "' . $uri . '"';
    $mysqlResult = $this->_mysql->execute($query);
    $rows = $mysqlResult->getNumRows();
    if ($rows > 0) {
      $result = $mysqlResult->fetch();
      return new TopicMapImpl($result['id'], $this->_mysql, $this->_config, $this);
    } else {
      return null;
    }
  }

  /**
   * Creates a new {@link TopicMapImpl} and stores it within the system under the
   * specified URI. 
   * 
   * @param string The address which should be used to store the {@link TopicMapImpl}.
   * @return TopicMapImpl|null Returns the created {@link TopicMapImpl}, <var>null</var> 
   *         otherwise if <var>uri</var> is empty. 
   * @throws {@link TopicMapExistsException} If this TopicMapSystem already manages a
   *        {@link TopicMapImpl} under the specified URI.
   */
  public function createTopicMap($uri)
  {
    if (empty($uri)) {
      return null;
    }
    // check if locator already exists
    $query = 'SELECT COUNT(*) FROM ' . $this->_config['table']['topicmap'] . 
      ' WHERE locator = "' . $uri . '"';
    $mysqlResult = $this->_mysql->execute($query);
    $result = $mysqlResult->fetchArray();
    if ($result[0] == 0) {
      $this->_mysql->startTransaction();
      $query = 'INSERT INTO ' . $this->_config['table']['topicmap'] . 
        ' (id, locator) VALUES (NULL, "' . $uri . '")';
      $this->_mysql->execute($query);
      $lastId = $mysqlResult->getLastId();
      
      $query = 'INSERT INTO ' . $this->_config['table']['topicmapconstruct'] . 
        ' (topicmap_id) VALUES (' . $lastId . ')';
      $this->_mysql->execute($query);
      $this->_mysql->finishTransaction();
      return new TopicMapImpl($lastId, $this->_mysql, $this->_config, $this);
    } else {
      throw new TopicMapExistsException(
        __METHOD__ . ': Topic map with locator "' . $uri . '" already exists!'
      );
    }
  }

  /**
   * Returns all storage addresses of {@link TopicMapImpl} instances known by this
   * system.
   * The return value may be empty but must never be <var>null</var>.
   * 
   * @return array An array containing URIs of known {@link TopicMapImpl} instances.
   */
  public function getLocators()
  {
    $locators = array();
    $query = 'SELECT locator FROM ' . $this->_config['table']['topicmap'];
    $mysqlResult = $this->_mysql->execute($query);
    while ($result = $mysqlResult->fetch()) {
      $locators[] = $result['locator'];
    }
    return $locators;
  }

  /**
   * Returns the value of the feature specified by <var>featureName</var>
   * for this TopicMapSystem instance. 
   * The features supported by the TopicMapSystem and the value for each 
   * feature is set when the TopicMapSystem is created by a call to 
   * {@link TopicMapSystemFactoryImpl::newTopicMapSystem()} and cannot be modified
   * subsequently.
   * 
   * @param string The name of the feature to check.
   * @return boolean <var>true</var> if the named feature is enabled for this
   *        TopicMapSystem instance; <var>false</var> if the named feature is 
   *        disabled for this instance.
   * @throws {@link FeatureNotRecognizedException} If the underlying implementation 
   *        does not recognize the named feature.
   */
  public function getFeature($featureName)
  {
    if (array_key_exists($featureName, $this->_features)) {
      return $this->_features[$featureName];
    } else {
      throw new FeatureNotRecognizedException(
        __METHOD__ . ': The feature "' . $featureName . '" is not recognized!'
      );
    }
  }

  /**
   * Returns a property in the underlying implementation of {@link TopicMapSystemImpl}.
   * A list of the core properties defined by TMAPI can be found at 
   * {@link http://tmapi.org/properties/}.
   * An implementation is free to support properties other than the core ones. The 
   * properties supported by the TopicMapSystem and the value for each property is 
   * set when the TopicMapSystem is created by a call to 
   * {@link TopicMapSystemFactoryImpl::newTopicMapSystem()} and cannot be modified 
   * subsequently.
   * 
   * @param string The name of the property to retrieve.
   * @return mixed The value set for the property or <var>null</var> 
   *        if no value is set.
   */
  public function getProperty($propertyName)
  {
    return array_key_exists($propertyName, $this->_properties) 
      ? $this->_properties[$propertyName] 
      : null;
  }

  /**
   * Applications SHOULD call this method when the TopicMapSystem instance is 
   * no longer required. 
   * Once the TopicMapSystem instance is closed, the TopicMapSystem and any 
   * object retrieved from or created in this TopicMapSystem MUST NOT be used
   * by the application.
   * An implementation of the TopicMapSystem interface may use this method to
   * clean up any resources used by the implementation.
   * 
   * @return void
   */
  public function close()
  {
    $this->_mysql->close();
  }
}
?>