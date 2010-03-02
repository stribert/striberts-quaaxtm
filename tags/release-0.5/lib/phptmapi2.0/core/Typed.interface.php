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
 
require_once('Construct.interface.php');

/**
 * Indicates that a Topic Maps construct is typed.
 * 
 * {@link Association}s, {@link Role}s, {@link Occurrence}s, and 
 * {@link Name}s are typed.
 *
 * @package core
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @version svn:$Id: Typed.interface.php 52 2009-07-15 21:57:32Z joschmidt $
 */
interface Typed extends Construct {

  /**
   * Returns the type of this construct.
   *
   * @return Topic
   */
  public function getType();

  /**
   * Sets the type of this construct.
   * Any previous type is overridden.
   * 
   * @param Topic The topic that should define the nature of this construct.
   * @return void
   * @throws {@link ModelConstraintException} If the <var>type</var> does not belong 
   *        to the parent topic map.
   */
  public function setType(Topic $type);
}
?>