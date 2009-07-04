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

require_once('PHPTMAPIException.class.php');

/**
 * Exception thrown when a {@link TopicMapSystemFactory} instance cannot be 
 * instantiated through the method {@link TopicMapSystemFactory::newInstance()}.
 *
 * @package core
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @version svn:$Id: FactoryConfigurationException.class.php 9 2008-11-03 20:55:37Z joschmidt $
 */
class FactoryConfigurationException extends PHPTMAPIException {}
?>