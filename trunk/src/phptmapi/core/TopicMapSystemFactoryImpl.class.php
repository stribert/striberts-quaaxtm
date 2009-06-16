<?php
/*
 * QuaaxTM is an implementation of PHPTMAPI which uses MySQL with InnoDB as 
 * storage engine.
 * 
 * Copyright (C) 2008 Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
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
 * This factory class provides access to a topic map system. 
 * 
 * A new {@link TopicMapSystemImpl} instance is created by invoking the 
 * {@link newTopicMapSystem()} method. 
 * Configuration properties for the new {@link TopicMapSystemImpl} instance 
 * can be set by calling the {@link setFeature()} and / or {@link setProperty()} 
 * methods prior to invoking {@link newTopicMapSystem()}.
 *
 * @package core
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
final class TopicMapSystemFactoryImpl extends TopicMapSystemFactory {
  
  private static $instance = null;
  private $properties,
          $features,
          $fixFeatures;
  
  /**
   * Constructor.
   * 
   * @return void
   */
  protected function __construct() {
    $this->properties = $this->features = $this->fixFeatures = array();
    // URI of feature, value, fix?
    $this->setupFeatures(VocabularyUtils::TMAPI_FEATURE_AUTOMERGE, true, true);
    $this->setupFeatures(VocabularyUtils::TMAPI_FEATURE_MODEL_XTM_1_0, false, true);
    $this->setupFeatures(VocabularyUtils::TMAPI_FEATURE_MODEL_XTM_1_1, true, true);
    $this->setupFeatures(VocabularyUtils::TMAPI_FEATURE_MERGE_BY_NAME, false, true);
    $this->setupFeatures(VocabularyUtils::TMAPI_FEATURE_NOTATION_URI, false, true);
    $this->setupFeatures(VocabularyUtils::TMAPI_FEATURE_READONLY, false, true);
    $this->setupFeatures(VocabularyUtils::QTM_FEATURE_AUTO_DUPL_REMOVAL, false, false);
  }
  
  /**
   * Creates a clone.
   * Declared "private" in order to prevent cloning from outside (singleton).
   * 
   * @return void
   */
  private function __clone() {
    return;
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
  public function getFeature($featureName) {
    if (array_key_exists($featureName, $this->features)) {
      return $this->features[$featureName];
    } else {
      throw new FeatureNotRecognizedException(__METHOD__ . 
        ': The feature "' . $featureName . '" is not recognized!');
    }
  }

  /**
   * Sets a particular feature in the underlying implementation of 
   * {@link TopicMapSystemImpl}. 
   * A list of the core features can be found at {@link http://tmapi.org/features/}.
   * 
   * @param string The name of the feature to be set.
   * @param boolean true to enable the feature, false to disable it.
   * @return void
   * @throws {@link FeatureNotRecognizedException} If the underlying implementation 
   *        does not recognize the named feature.
   * @throws {@link FeatureNotSupportedException} If the underlying implementation 
   *        recognizes the named feature but does not support enabling or 
   *        disabling it (as specified by the enabled parameter).
   */
  public function setFeature($featureName, $enable) {
    if (array_key_exists($featureName, $this->features)) {
      if ($this->fixFeatures[$featureName]) {
        throw new FeatureNotSupportedException(__METHOD__ . 
          ': The feature "' . $featureName . '" is fix!');
      } else {
        $this->features[$featureName] = $enable;
      }
    } else {
      throw new FeatureNotRecognizedException(__METHOD__ . 
        ': The feature "' . $featureName . '" is not recognized!');
    }
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
  public function hasFeature($featureName) {
    return array_key_exists($featureName, $this->features) ? true : false;
  }

  /**
   * Gets the value of a property in the underlying implementation of 
   * {@link TopicMapSystemImpl}.
   * A list of the core properties defined by TMAPI can be found at 
   * {@link http://tmapi.org/properties/}.
   * An implementation is free to support properties other than the core ones.
   * 
   * @param string The name of the property to retrieve.
   * @return object|null Object The value set for this property or <var>null</var> 
   *        if no value is currently set for the property.
   */
  public function getProperty($propertyName) {
    return array_key_exists($propertyName, $this->properties) ? 
      $this->properties[$propertyName] : null;
  }

  /**
   * Sets a property in the underlying implementation of 
   * {@link TopicMapSystemImpl}.
   * A list of the core properties defined by TMAPI can be found at 
   * {@link http://tmapi.org/properties/}
   * An implementation is free to support properties other than the core ones.
   * 
   * @param string The name of the property to be set.
   * @param object|null Object The value to be set of this property or null to 
   *        remove the property from the current factory configuration.
   * @return void
   */
  public function setProperty($propertyName, $value) {
    if (!is_null($value)) $this->properties[$propertyName] = $value;
    else unset($this->properties[$propertyName]);
  }

  /**
   * Obtains a new instance of a TopicMapSystemFactory. This static method
   * creates a new factory instance.
   *
   * @return TopicMapSystemFactoryImpl
   * @static
   */
  public static function newInstance() {
    if (is_null(self::$instance)) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * Creates a new {@link TopicMapSystemImpl} instance using the currently
   * configured factory parameters.
   *
   * @return TopicMapSystemImpl
   */
  public function newTopicMapSystem() {
    $config = array();
    require(
      dirname(__FILE__) . 
      DIRECTORY_SEPARATOR . 
      '..' . 
      DIRECTORY_SEPARATOR . 
      '..' . 
      DIRECTORY_SEPARATOR . 
      '..' . 
      DIRECTORY_SEPARATOR . 
      'lib' . 
      DIRECTORY_SEPARATOR . 
      'phptmapi2.0' . 
      DIRECTORY_SEPARATOR . 
      'config.php'
    );
    $mysql = new Mysql($config);
    return new TopicMapSystemImpl($mysql, $config, $this->properties, $this->features);
  }
  
  /**
   * Autoload.
   * 
   * @param string The class name.
   * @return void
   * @static
   */
  public static function autoload($className) {
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
    set_include_path($path . 
      PATH_SEPARATOR . 
      $phptmapiCorePath . 
      PATH_SEPARATOR . 
      $phptmapiIndexPath .
      PATH_SEPARATOR . 
      $implCorePath . 
      PATH_SEPARATOR . 
      $implIndexPath . 
      PATH_SEPARATOR . 
      $utilPath);
    $file = $className . self::getFileExtension($className);
    require_once($file);
    set_include_path($path);
  }
  
  /**
   * Gets the file extension of the class to be autoloaded.
   * 
   * @param string The class name.
   * @return string
   */
  private static function getFileExtension($className) {
    if (eregi('impl', $className) || 
        eregi('exception', $className) ||
        eregi('mysql', $className) ||
        eregi('topicmapsystemfactory', $className) ||
        eregi('utils', $className)) {
      return '.class.php';
    } else {
      return '.interface.php';
    }
  }
  
  /**
   * Sets up the TMAPI features of QuaaxTM.
   * 
   * @param string The feature name.
   * @param boolean The value. <var>True</var> to enable the feature, 
   *        <var>false</var> to disable it.
   * @param boolean The setting ability. <var>True</var> if the feature is adjustable, 
   *        <var>false</var> if not. In QuaaxTM all features are fix.
   * @return void
   */
  private function setupFeatures($featureName, $value, $fix) {
    $this->fixFeatures[$featureName] = $fix;
    $this->features[$featureName] = $value;
  }
}
?>