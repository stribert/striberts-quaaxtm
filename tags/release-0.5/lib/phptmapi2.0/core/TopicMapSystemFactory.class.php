<?php
/*
 * PHPTMAPI is hereby released into the public domain; 
 * and comes with NO WARRANTY.
 * 
 * No one owns PHPTMAPI: you may use it freely in both commercial and
 * non-commercial applications, bundle it with your software
 * distribution, include it on a CD-ROM, list the source code in a
 * book, mirror the documentation at your own web site, or use it in
 * any other way you see fit.
 */

require_once('FactoryConfigurationException.class.php');

/**
 * This factory class provides access to a topic map system. 
 * 
 * A new {@link TopicMapSystem} instance is created by invoking the 
 * {@link newTopicMapSystem()} method. 
 * Configuration properties for the new {@link TopicMapSystem} instance 
 * can be set by calling the {@link setFeature()} and / or {@link setProperty()} 
 * methods prior to invoking {@link newTopicMapSystem()}.
 *
 * @package core
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @version svn:$Id: TopicMapSystemFactory.class.php 32 2009-06-19 20:15:37Z joschmidt $
 */
abstract class TopicMapSystemFactory {
   
  /**
   * Returns the particular feature requested for in the underlying
   * implementation of {@link TopicMapSystem}.
   * 
   * @param string The name of the feature to check.
   * @return boolean <var>true</var> if the named feature is enabled for
   *        {@link TopicMapSystem} instances created by this factory;
   *        <var>false</var> if the named feature is disabled for
   *        {@link TopicMapSystem} instances created by this factory.
   * @throws {@link FeatureNotRecognizedException} If the underlying implementation 
   *        does not recognize the named feature.
   */
  abstract public function getFeature($featureName);

  /**
   * Sets a particular feature in the underlying implementation of 
   * {@link TopicMapSystem}. 
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
  abstract public function setFeature($featureName, $enable);

  /**
   * Returns if the particular feature is supported by the 
   * {@link TopicMapSystem}.
   * Opposite to {@link getFeature} this method returns if the requested 
   * feature is generally available / supported by the underlying 
   * {@link TopicMapSystem} and does not return the state (enabled/disabled) 
   * of the feature.
   * 
   * @param string The name of the feature to check.
   * @return boolean <var>true</var> if the requested feature is supported, 
   *        otherwise <var>false</var>.
   */
  abstract public function hasFeature($featureName);

  /**
   * Gets the value of a property in the underlying implementation of 
   * {@link TopicMapSystem}.
   * A list of the core properties defined by TMAPI can be found at 
   * {@link http://tmapi.org/properties/}.
   * An implementation is free to support properties other than the core ones.
   * 
   * @param string The name of the property to retrieve.
   * @return object|null Object The value set for this property or <var>null</var> 
   *        if no value is currently set for the property.
   */
  abstract public function getProperty($propertyName);

  /**
   * Sets a property in the underlying implementation of 
   * {@link TopicMapSystem}.
   * A list of the core properties defined by TMAPI can be found at 
   * {@link http://tmapi.org/properties/}
   * An implementation is free to support properties other than the core ones.
   * 
   * @param string The name of the property to be set.
   * @param object|null Object The value to be set of this property or null to 
   *        remove the property from the current factory configuration.
   * @return void
   */
  abstract public function setProperty($propertyName, $value);

  /**
   * Obtain a new instance of a TopicMapSystemFactory.
   * Once an application has obtained a reference to a TopicMapSystemFactory 
   * it can use the factory to configure and obtain {@link TopicMapSystem} 
   * instances. 
   *
   * @return TopicMapSystemFactory
   * @throws {@link FactoryConfigurationException}
   * @static
   */
  public static function newInstance() {
    $implementation = self::getImplementationClass();
    if ($implementation instanceof TopicMapSystemFactory) {
      return $implementation->newInstance();
    } else {
      throw new FactoryConfigurationException(__METHOD__ . ': ' .
        'Implementation is not an instance of TopicMapSystemFactory!');
    }
  }

  /**
   * Creates a new {@link TopicMapSystem} instance using the currently
   * configured factory parameters.
   *
   * @return TopicMapSystem
   * @throws {@link PHPTMAPIException} If a <var>TopicMapSystem</var> cannot be created 
   *        which satisfies the requested configuration.
   */
  abstract public function newTopicMapSystem();
  
  /**
   * Locates the implementation of TopicMapSystemFactory from config.php 
   * and returns a new instance.
   * 
   * @return TopicMapSystemFactory
   * @static
   */
  private static function getImplementationClass() {
    $factoryImplLocation = $factoryImpl = null;
    require(
      dirname(__FILE__) . 
      DIRECTORY_SEPARATOR . 
      '..' . 
      DIRECTORY_SEPARATOR . 
      'config.php'
    );
    require_once($factoryImplLocation);
    return new $factoryImpl;
  }
}
?>