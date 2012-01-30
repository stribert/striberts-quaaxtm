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

require_once('ModelConstraintException.class.php');

/**
 * This exception is used to report identity constraint violations.
 * 
 * Assigning an item identifier, a subject identifier, or a subject locator
 * to different objects causes an <var>IdentityConstraintException</var> to be
 * thrown.
 * 
 * @package core
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @version svn:$Id: IdentityConstraintException.class.php 88 2011-09-14 12:13:11Z joschmidt $
 */
class IdentityConstraintException extends ModelConstraintException
{
  /**
   * The {@link Construct} which already has the identity represented
   * by the locator in {@link getLocator()}.
   * 
   * @var Construct
   */  
  private $_existing;
  
  /**
   * The locator representing the identity that caused the exception.
   * 
   * @var string
   */
  private $_locator;
  
  /**
   * Constructor.
   * 
   * @param Construct The construct to which the identity should have been assigned to. 
   *        In case a factory method has thrown this exception it is the construct 
   *        which provides the factory method.
   * @param Construct The construct which has the same identity.
   * @param string The locator representing the identity.
   * @param string The error message.
   * @return void
   */
  public function __construct(Construct $reporter, Construct $existing, $locator, $msg)
  {
    parent::__construct($reporter, $msg);
    $this->_existing = $existing;
    $this->_locator = $locator;
  }
  
  /**
   * Returns the {@link Construct} which already has the identity represented
   * by the locator in {@link getLocator()}.
   *
   * @return Construct The existing construct.
   */
  public function getExisting()
  {
    return $this->_existing;
  }

  /**
   * Returns the locator representing the identity that caused the exception.
   *
   * @return string The locator representing the identity that caused the exception.
   */
  public function getLocator()
  {
    return $this->_locator;
  }
}
?>