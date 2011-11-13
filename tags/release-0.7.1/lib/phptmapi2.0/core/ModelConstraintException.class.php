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
 
require_once('PHPTMAPIRuntimeException.class.php');

/**
 * This exception is used to report Topic Maps Data Model (TMDM)
 * ({@link http://www.isotopicmaps.org/sam/sam-model/}) constraint violations. 
 *
 * @package core
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @version svn:$Id: ModelConstraintException.class.php 89 2011-09-15 15:37:45Z joschmidt $
 */
class ModelConstraintException extends PHPTMAPIRuntimeException
{
  /**
   * The {@link Construct} which has thrown the exception.
   * 
   * @var Construct
   */
  private $_reporter;

  /**
   * Constructor.
   * 
   * @param Construct The construct which should have been modified.
   * @param string The error message.
   * @return void
   */
  public function __construct(Construct $reporter, $msg)
  {
    parent::__construct($msg);
    $this->_reporter = $reporter;
  }
  
  /**
   * Returns the {@link Construct} which has thrown the exception.
   *
   * @return Construct The construct which has thrown the exception.
   */
  public function getReporter()
  {
    return $this->_reporter;
  }
}
?>