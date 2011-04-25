<?php
/*
 * QuaaxTM is an implementation of PHPTMAPI which uses MySQL with InnoDB as 
 * storage engine.
 * 
 * Copyright (C) 2011 Johannes Schmidt <joschmidt@users.sourceforge.net>
 * 
 * This library is free software; you can redistribute it and/or modify it under the 
 * terms of the GNU Lesser General Public License as published by the Free Software 
 * Foundation; either version 3 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 * See the GNU Lesser General Public License for more details.
 *  
 * You should have received a copy of the GNU Lesser General Public License along with this 
 * library; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, 
 * Boston, MA 02111-1307 USA
 */

/*
 * Notice: Before running the example below please follow the installation instructions 1 and 2 
 * in README.
 * To ensure a proper QuaaxTM installation you SHOULD run the test suites (see README again) - 
 * and not naively rely on the following example.
 * See the PHPTMAPI documentation <http://phptmapi.sourceforge.net/2.0/docs/> for all API features.
 */

// require TopicMapSystemFactory from PHPTMAPI
require_once(
  dirname(__FILE__) . 
  DIRECTORY_SEPARATOR . 
  'lib' . 
  DIRECTORY_SEPARATOR . 
  'phptmapi2.0' . 
  DIRECTORY_SEPARATOR . 
  'core' . 
  DIRECTORY_SEPARATOR . 
  'TopicMapSystemFactory.class.php'
);
// require the XTM writer from QuaaxTMIO for serializing the created topic map to XTM
require_once(
  dirname(__FILE__) . 
  DIRECTORY_SEPARATOR . 
  'lib' . 
  DIRECTORY_SEPARATOR . 
  'quaaxtmio' . 
  DIRECTORY_SEPARATOR . 
  'src' . 
  DIRECTORY_SEPARATOR . 
  'out' . 
  DIRECTORY_SEPARATOR . 
  'PHPTMAPIXTM201Writer.class.php'
);
// create an instance of TopicMapSystemFactory
$tmSystemFactory = TopicMapSystemFactory::newInstance();
// set up QuaaxTM specific duplicate removal feature
$tmSystemFactory->setFeature(VocabularyUtils::QTM_FEATURE_AUTO_DUPL_REMOVAL, true);
// create an instance of TopicMapSystem
$tmSystem = $tmSystemFactory->newTopicMapSystem();
// create a topic map; see http://phptmapi.sourceforge.net/2.0/docs/core/TopicMap.html 
// for all topic map features
$topicMap = $tmSystem->createTopicMap('http://localhost/tm/' . uniqid());
// create a topic with an automatically generated item identifier; see 
// http://phptmapi.sourceforge.net/2.0/docs/core/Topic.html for all topic features
$topic = $topicMap->createTopic();
// create a topic name with the default name type in the unconstrained scope; 
// see http://phptmapi.sourceforge.net/2.0/docs/core/Name.html for all topic name features
$name = $topic->createName('foo');
// serialize the topic map to XTM 2.1
$xtmWriter = new PHPTMAPIXTM201Writer();
$xtm = $xtmWriter->write($topicMap);
var_dump($xtm);
// remove the created topic map
$topicMap->remove();
?>