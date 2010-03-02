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
 * Base interface for all indices.
 *
 * @package index
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @version svn:$Id: Index.interface.php 9 2008-11-03 20:55:37Z joschmidt $
 */
interface Index {

  /**
   * Open the index.
   * This method must be invoked before using any other method (aside from
   * {@link isOpen()}) exported by this interface or derived interfaces.
   * 
   * @return void
   */
  public function open();

  /**
   * Close the index.
   * 
   * @return void
   */
  public function close();

  /**
   * Indicates if the index is open.
   * 
   * @return boolean <var>true</var> if index is already opened, <var>false</var> otherwise.
   */
  public function isOpen();

  /**
   * Indicates whether the index is updated automatically.
   * If the value is <var>true</var>, then the index is automatically kept
   * synchronized with the topic map as values are changed.
   * If the value is <var>false</var>, then the {@link Index::reindex()}
   * method must be called to resynchronize the index with the topic map
   * after values are changed.
   * 
   * @return boolean <var>true</var> if index is updated automatically, 
   *        <var>false</var> otherwise.
   */
  public function isAutoUpdated();

  /**
   * Synchronizes the index with data in the topic map.
   *
   * @return void
   */
  public function reindex();
}
?>