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

spl_autoload_register('TopicMapSystemFactoryImpl::autoload');

/**
 * This factory class provides access to a Topic Maps system. 
 * 
 * A new {@link TopicMapSystemImpl} instance is created by invoking the 
 * {@link newTopicMapSystem()} method. 
 * Configuration properties for the new {@link TopicMapSystemImpl} instance 
 * can be set by calling the {@link setFeature()} and / or {@link setProperty()} 
 * methods prior to invoking {@link newTopicMapSystem()}.
 *
 * @package core
 * @author Johannes Schmidt <joschmidt@users.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
final class TopicMapSystemFactoryImpl extends TopicMapSystemFactory
{
  /**
   * The Topic Maps system properties.
   * 
   * @var array
   */
  private $_properties;
  
  /**
   * The TMAPI and QuaaxTM feature strings and their "supported/not supported" status.
   * 
   * @var array
   */
  private $_features;
  
  /**
   * The TMAPI and QuaaxTM feature strings and their "manipulation enabled/disabled" status.
   * 
   * @var array
   */
  private $_fixFeatures;
  
  /**
   * The instance of <var>TopicMapSystemFactoryImpl</var>; or <var>null</var>.
   * Default <var>null</var>.
   * 
   * @var TopicMapSystemFactoryImpl|null
   * @static
   */
  private static $_instance = null;
  
  /**
   * Constructor.
   * 
   * @return void
   */
  private function __construct()
  {
    $this->_properties = 
    $this->_features = 
    $this->_fixFeatures = array();
    // URI of feature, value, fix?
    $this->_setupFeature(VocabularyUtils::TMAPI_FEATURE_AUTOMERGE, true, true);
    $this->_setupFeature(VocabularyUtils::TMAPI_FEATURE_READONLY, false, true);
    $this->_setupFeature(VocabularyUtils::TMAPI_FEATURE_TYPE_INST_ASSOC, false, true);
    $this->_setupFeature(VocabularyUtils::QTM_FEATURE_AUTO_DUPL_REMOVAL, false, false);
    $this->_setupFeature(VocabularyUtils::QTM_FEATURE_RESULT_CACHE, false, false);
    
    self::$_instance = $this;
  }
  
  /**
   * Returns the particular feature requested for in the underlying
   * implementation of {@link TopicMapSystemImpl}.
   * 
   * @param string The name of the feature to check.
   * @return boolean <var>true</var> if the named feature is enabled for
   *        {@link TopicMapSystemImpl} instances created by this factory;
   *        <var>false</var> if the named feature is disabled for
   *        {@link TopicMapSystemImpl} instances created by this factory.
   * @throws {@link FeatureNotRecognizedException} If the underlying implementation 
   *        does not recognize the named feature.
   */
  public function getFeature($featureName)
  {
    if (!array_key_exists($featureName, $this->_features)) {
      throw new FeatureNotRecognizedException(
        __METHOD__ . ': The feature "' . $featureName . '" is unknown!'
      );
    }
    return $this->_features[$featureName];
  }

  /**
   * Sets a particular feature in the underlying implementation of 
   * {@link TopicMapSystemImpl}. 
   * A list of the core features can be found at {@link http://tmapi.org/features/}.
   * 
   * @param string The name of the feature to be set.
   * @param boolean <var>True</var> to enable the feature, <var>false</var> 
   * 				to disable it.
   * @return void
   * @throws {@link FeatureNotRecognizedException} If the underlying implementation 
   *        does not recognize the named feature.
   * @throws {@link FeatureNotSupportedException} If the underlying implementation 
   *        recognizes the named feature but does not support enabling or 
   *        disabling it (as specified by the enabled parameter).
   */
  public function setFeature($featureName, $enable)
  {
    if (!array_key_exists($featureName, $this->_features)) {
      throw new FeatureNotRecognizedException(
        __METHOD__ . ': The feature "' . $featureName . '" is not recognized!'
      ); 
    }
    if ($this->_fixFeatures[$featureName]) {
      throw new FeatureNotSupportedException(
        __METHOD__ . ': The feature "' . $featureName . '" is fix!'
      );
    }
    $this->_features[$featureName] = $enable;
  }

  /**
   * Returns if the particular feature is supported by the 
   * {@link TopicMapSystemImpl}.
   * Opposite to {@link getFeature} this method returns if the requested 
   * feature is generally available / supported by the underlying 
   * {@link TopicMapSystemImpl} and does not return the state (enabled/disabled) 
   * of the feature.
   * 
   * @param string The name of the feature to check.
   * @return boolean <var>true</var> if the requested feature is supported, 
   *        otherwise <var>false</var>.
   */
  public function hasFeature($featureName)
  {
    return array_key_exists($featureName, $this->_features);
  }

  /**
   * Gets the value of a property in the underlying implementation of 
   * {@link TopicMapSystemImpl}.
   * A list of the core properties defined by TMAPI can be found at 
   * {@link http://tmapi.org/properties/}.
   * An implementation is free to support properties other than the core ones.
   * 
   * @param string The name of the property to retrieve.
   * @return mixed The value set for this property or <var>null</var> 
   *        if no value is set.
   */
  public function getProperty($propertyName)
  {
    return array_key_exists($propertyName, $this->_properties) 
      ? $this->_properties[$propertyName] 
      : null;
  }

  /**
   * Sets a property in the underlying implementation of 
   * {@link TopicMapSystemImpl}.
   * A list of the core properties defined by TMAPI can be found at 
   * {@link http://tmapi.org/properties/}
   * An implementation is free to support properties other than the core ones.
   * 
   * @param string The name of the property to be set.
   * @param mixed The value to be set of this property or <var>null</var> to 
   *        remove the property from the current factory configuration.
   * @return void
   */
  public function setProperty($propertyName, $value)
  {
    if (!is_null($value)) {
      $this->_properties[$propertyName] = $value;
    } else {
      unset($this->_properties[$propertyName]);
    }
  }

  /**
   * Obtains a new instance of a TopicMapSystemFactory. This static method
   * creates a new factory instance.
   *
   * @return TopicMapSystemFactoryImpl
   * @static
   */
  public static function newInstance()
  {
    return self::$_instance instanceof TopicMapSystemFactory 
      ? self::$_instance
      : new self();
  }

  /**
   * Creates a new {@link TopicMapSystemImpl} instance using the currently
   * configured factory parameters.
   *
   * @return TopicMapSystemImpl
   * @throws {@link PHPTMAPIRuntimeException} If the connection to MySQL or, if set up, the 
   * 				connection to memcached cannot be established, or if PHP memcached support using 
   * 				libmemcached (Memcached) is not available, or if the optionally provided MySQL 
   * 				property is not an instance of class "Mysql".
   */
  public function newTopicMapSystem()
  {
    $config = array();
    require(
      dirname(__FILE__) . 
      DIRECTORY_SEPARATOR . 
      '..' . 
      DIRECTORY_SEPARATOR . 
      'config.php'
    );
    
    try {
      $mysqlProperty = $this->getProperty(VocabularyUtils::QTM_PROPERTY_MYSQL);
      
      if (is_null($mysqlProperty)) {
        $enableResultCache = $this->getFeature(VocabularyUtils::QTM_FEATURE_RESULT_CACHE);
        $mysql = new Mysql($config, $enableResultCache);
        if ($enableResultCache) {
          $mysql->setResultCacheExpiration($config['resultcache']['expiration']);
        }
      } else {
        if (!$mysqlProperty instanceof Mysql) {
          throw new Exception(
            'Error in ' . __METHOD__ . 
            	': The provided MySQL property is not an instance of class "Mysql".'
          );
        }
        $mysql = $mysqlProperty;
      }
      
      return new TopicMapSystemImpl($mysql, $config, $this->_properties, $this->_features);
    
    } catch (Exception $e) {
      throw new PHPTMAPIRuntimeException($e->getMessage());
    }
  }
  
  /**
   * Autoloads all required classes.
   * 
   * @param string The class name.
   * @return void
   * @static
   */
  public static function autoload($className)
  {
    $path = get_include_path();
    $thisPath = dirname(__FILE__);
    $qtmPath = $thisPath . 
      DIRECTORY_SEPARATOR . 
      '..' . 
      DIRECTORY_SEPARATOR . 
      '..' . 
      DIRECTORY_SEPARATOR . 
      '..';
    $phptmapiCorePath = $qtmPath . 
      DIRECTORY_SEPARATOR . 
      'lib' . 
      DIRECTORY_SEPARATOR . 
      'phptmapi2.0' . 
      DIRECTORY_SEPARATOR . 
      'core';
    $phptmapiIndexPath = $qtmPath . 
      DIRECTORY_SEPARATOR . 
      'lib' . 
      DIRECTORY_SEPARATOR . 
      'phptmapi2.0' . 
      DIRECTORY_SEPARATOR . 
      'index';
    $implCorePath = $thisPath;
    $implIndexPath = $qtmPath . 
      DIRECTORY_SEPARATOR . 
      'src' . 
      DIRECTORY_SEPARATOR .
      'phptmapi' . 
      DIRECTORY_SEPARATOR . 
      'index';
    $utilPath = $qtmPath . 
      DIRECTORY_SEPARATOR . 
      'src' . 
      DIRECTORY_SEPARATOR .
      'utils';
    set_include_path(
      $path . 
      PATH_SEPARATOR . 
      $phptmapiCorePath . 
      PATH_SEPARATOR . 
      $phptmapiIndexPath .
      PATH_SEPARATOR . 
      $implCorePath . 
      PATH_SEPARATOR . 
      $implIndexPath . 
      PATH_SEPARATOR . 
      $utilPath
    );
    $file = $className . self::_getFileExtension($className);
    require_once($file);
    set_include_path($path);
  }
  
  /**
   * Gets the file extension of the class to be autoloaded.
   * 
   * @param string The class name.
   * @return string The file extension.
   */
  private static function _getFileExtension($className)
  {
    if (
        stristr($className, 'impl') ||
        stristr($className, 'utils') ||
        stristr($className, 'mysql') ||
        stristr($className, 'topicmapsystemfactory') ||
        stristr($className, 'exception')
    ) {
      return '.class.php';
    }
    return '.interface.php';
  }
  
  /**
   * Sets up a TMAPI as well as a QuaaxTM specific feature.
   * 
   * @param string The feature name.
   * @param boolean The value. <var>True</var> to enable the feature, 
   *        <var>false</var> to disable it.
   * @param boolean The setting ability. <var>True</var> if the feature is adjustable, 
   *        <var>false</var> if not.
   * @return void
   */
  private function _setupFeature($featureName, $value, $fix)
  {
    $this->_fixFeatures[$featureName] = $fix;
    $this->_features[$featureName] = $value;
  }
}
?>