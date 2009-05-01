<?php
/*
 * QuaaxTM is an implementation of PHPTMAPI which uses MySQL with InnoDB as 
 * storage engine.
 * 
 * Copyright (C) 2008 Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * 
 * This library is free software; you can redistribute it and/or modify it under the 
 * terms of the GNU Lesser General Public License as published by the Free Software 
 * Foundation; either version 2.1 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 * See the GNU Lesser General Public License for more details.
 *  
 * You should have received a copy of the GNU Lesser General Public License along with this 
 * library; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, 
 * Boston, MA 02111-1307 USA
 */

/**
 * Provides merge utilities.
 *
 * @package utils
 * @author Johannes Schmidt <phptmapi-discuss@lists.sourceforge.net>
 * @license http://www.gnu.org/licenses/lgpl.html GNU LGPL
 * @version $Id$
 */
class MergeUtils {

  /**
   * Constructor.
   * 
   * @return void
   */
  private function __construct() {}
  
  /**
   * Copies names of the source topic to the target topic.
   * 
   * @param TopicImpl $source
   * @param TopicImpl $target
   * @return void
   */
  public static function copyNames(Topic $source, Topic $target) {
    $othersNames = $source->getNames();
    foreach ($othersNames as $othersName) {
      $name = $target->createTypedName($othersName->getType(), 
                                      $othersName->getValue(), 
                                      $othersName->getScope()
                                    );
      // other's name's iids
      $othersNameIids = $othersName->getItemIdentifiers();
      foreach ($othersNameIids as $othersNameIid) {
        $name->addItemIdentifier($othersNameIid);
      }
      // other's name's reifier
      $name->setReifier($othersName->getReifier());
      // other's name's variants
      $othersNameVariants = $othersName->getVariants();
      foreach ($othersNameVariants as $othersNameVariant) {
        $variant = $name->createVariant($othersNameVariant->getValue(), 
                                        $othersNameVariant->getDatatype(), 
                                        $othersNameVariant->getScope()
                                        );
        // other's variant's iids
        $othersNameVariantsIids = $othersNameVariant->getItemIdentifiers();
        foreach ($othersNameVariantsIids as $othersNameVariantsIid) {
          $variant->addItemIdentifier($othersNameVariantsIid);
        }
        // other's variant's reifier
        $variant->setReifier($othersNameVariant->getReifier());
      }
    }
  }
}
?>