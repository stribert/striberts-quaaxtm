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
 * Indicates that a {@link Construct} is reifiable.
 * 
 * Every Topic Maps construct that is not a {@link Topic} is reifiable.
 *
 * @package core
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @version svn:$Id: Reifiable.interface.php 51 2009-07-15 21:56:43Z joschmidt $
 */
interface Reifiable extends Construct {

  /**
   * Returns the reifier of this {@link Construct}.
   * 
   * @return Topic|null The topic that reifies this construct or
   *        <var>null</var> if this construct is not reified.
   */
  public function getReifier();

  /**
   * Sets the reifier of this {@link Construct}.
   * The specified <var>reifier</var> MUST NOT reify another information item.
   *
   * @param Topic|null The topic that should reify this construct or <var>null</var>
   *        if an existing reifier should be removed.
   * @return void
   * @throws {@link ModelConstraintException} If the specified <var>reifier</var> 
   *        reifies another construct or the <var>reifier</var> does not belong to
   *        the parent topic map.
   */
  public function setReifier($reifier);
}
?>