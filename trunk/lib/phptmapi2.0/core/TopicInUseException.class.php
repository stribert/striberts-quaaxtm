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
 * Thrown when an attempt is made to remove a {@link Topic} which is being used
 * as a type, as a reifier, or as a role player in an association, or in a scope.
 * 
 * @package core
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @version svn:$Id: TopicInUseException.class.php 88 2011-09-14 12:13:11Z joschmidt $
 */
class TopicInUseException extends ModelConstraintException
{
  /**
   * Constructor.
   * 
   * @param Topic The topic which is not removable.
   * @param string The error message.
   * @return void
   */
  public function __construct(Topic $topic, $msg)
  {
    parent::__construct($topic, $msg);
  }
}
?>