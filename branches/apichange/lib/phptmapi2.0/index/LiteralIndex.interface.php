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

require_once('Index.interface.php');

/**
 * Index for literal values stored in a topic map.
 *
 * @package index
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @version svn:$Id: LiteralIndex.interface.php 18 2009-02-07 23:00:17Z joschmidt $
 */
interface LiteralIndex extends Index {

  /**
   * Retrieves the topic names in the topic map which have a value equal to 
   * <var>value</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param string The value of the {@link Name}s to be returned.
   * @return array An array containing {@link Name}s.
   * @throws InvalidArgumentException If the value is <var>null</var>.
   */
  public function getNames($value);

  /**
   * Returns the {@link Occurrence}s in the topic map whose value property 
   * matches <var>value</var> and whose datatye is <var>datatype</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param string The value of the {@link Occurrence}s to be returned.
   * @param string A URI indicating the datatype of the {@link Occurrence}s. 
   *        E.g. http://www.w3.org/2001/XMLSchema#string indicates a string value.
   * @return array An array containing {@link Occurrence}s.
   * @throws InvalidArgumentException If the value or datatype is <var>null</var>.
   */
  public function getOccurrences($value, $datatype);

  /**
   * Returns the {@link Variant}s in the topic map whose value property 
   * matches <var>value</var> and whose datatye is <var>datatype</var>.
   * The return value may be an empty array but must never be <var>null</var>.
   * 
   * @param string The value of the {@link Variant}s to be returned.
   * @param string A URI indicating the datatype of the {@link Variant}s. 
   *        E.g. http://www.w3.org/2001/XMLSchema#string indicates a string value.
   * @return array An array containing {@link Variant}s.
   * @throws InvalidArgumentException If the value or datatype is <var>null</var>.
   */
  public function getVariants($value, $datatype);
}
?>