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

/**
 * A generic interface to a PHPTMAPI system. 
 * 
 * Any PHPTMAPI system must be capable of providing access to one or more 
 * {@link TopicMap} objects. A PHPTMAPI system may be capable of allowing a client
 * to create new {@link TopicMap} instances.
 *
 * @package core
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @version svn:$Id: TopicMapSystem.interface.php 29 2009-04-09 20:13:45Z joschmidt $
 */
interface TopicMapSystem {

  /**
   * Retrieves a {@link TopicMap} managed by this system with the
   * specified storage address <var>uri</var>. 
   * The string is assumed to be in URI notation.
   * 
   * @param string The storage address to retrieve the {@link TopicMap} from.
   * @return TopicMap|null The instance managed by this system which 
   *        is stored at the specified <var>uri</var>, or <var>null</var> if no 
   *        such {@link TopicMap} is found.
   */
  public function getTopicMap($uri);

  /**
   * Creates a new {@link TopicMap} and stores it within the system under the
   * specified URI. 
   * 
   * @param string The address which should be used to store the {@link TopicMap}.
   * @return TopicMap
   * @throws {@link TopicMapExistsException} If this TopicMapSystem already manages a
   *        {@link TopicMap} under the specified URI.
   */
  public function createTopicMap($uri);

  /**
   * Returns all storage addresses of {@link TopicMap} instances known by this
   * system.
   * The return value may be empty but must never be <var>null</var>.
   * 
   * @return array An array containing URIs of known {@link TopicMap} instances.
   */
  public function getLocators();

  /**
   * Returns the value of the feature specified by <var>featureName</var>
   * for this TopicMapSystem instance. 
   * The features supported by the TopicMapSystem and the value for each 
   * feature is set when the TopicMapSystem is created by a call to 
   * {@link TopicMapSystemFactory::newTopicMapSystem()} and cannot be modified
   * subsequently.
   * 
   * @param string The name of the feature to check.
   * @return boolean <var>true</var> if the named feature is enabled for this
   *        TopicMapSystem instance; <var>false</var> if the named feature is 
   *        disabled for this instance.
   * @throws {@link FeatureNotRecognizedException} If the underlying implementation 
   *        does not recognize the named feature.
   */
  public function getFeature($featureName);

  /**
   * Returns a property in the underlying implementation of {@link TopicMapSystem}.
   * A list of the core properties defined by TMAPI can be found at 
   * {@link http://tmapi.org/properties/}.
   * An implementation is free to support properties other than the core ones. The 
   * properties supported by the TopicMapSystem and the value for each property is 
   * set when the TopicMapSystem is created by a call to 
   * {@link TopicMapSystemFactory::newTopicMapSystem()} and cannot be modified 
   * subsequently.
   * 
   * @param string The name of the property to retrieve.
   * @return object|null Object The value set for the property or <var>null</var> 
   *        if no value is set for the specified <var>propertyName</var>.
   */
  public function getProperty($propertyName);

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
  public function close();
}
?>