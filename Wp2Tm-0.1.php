<?php
/*
Plugin Name: WP Topic Maps
Plugin URI: http://www.topicobserver.com/wp2tm/
Description: Exports your Wordpress blog &amp; entries to XML Topic Maps (XTM 1.0). May enable the sharing of knowledge across Wordpress blogs. Configure via WP Admin Panel. [<strong><a href="http://www.topicobserver.com/wp2tm/">Plug-in Home</a></strong>].
Author: Trond K. Pettersen
Version: 0.1
Author URI: http://www.topicobserver.com/wp2tm/
Feed URI: http://www.topicobserver.com/wp2tm/category/releases/feed/
*/

/**
 * @todo clean up code
 * @todo PHP5/OOPify (not the WP way) - TM obj model for WP - keep it simple.
 * @todo pages xtm
 * @todo possibly archives xtm
 * @todo associate tags with fuzzzy-tags (in some way, by some means), if possible
 * @todo move to WP admin UI: feed (tm) name + description, default no of posts 
 *
 * Use at free will and own risk.
 * No guarantees what-so-ever.
 * 
 * Exports Wordpress blog &amp; entries to XML Topic Maps (XTM 1.0).
 * Add list of feeds to blog by using wp2tm_printFeedLinks() in theme templates.
 * 
 * Inspired by LMG's blog entry at <http://www.garshol.priv.no/blog/145.html> 
 * Topic Map structure in part inspired by Dmitry's XTM at <http://subjectcentric.com/>
 * Some subject identifiers refer to concepts defined by the SIOC project <http://sioc-project.org/>
 */

define('WP2TM_VERSION', '0.1');
define('WP2TM_URL', 'http://www.topicobserver.com/wp2tm/');
define('WP2TM_XTM1NS', 'http://www.topicmaps.org/xtm/1.0/');
define('WP2TM_XLINKNS', 'http://www.w3.org/1999/xlink');


// _________________ XTM Elements _________________ \\

/**
 * @todo create new name/instanceOf if already in $topicList but $name is different
 * 
 * Creates and returns a <topic> element with characteristics set 
 * 
 * @param String $name Basename
 * @param String $identitiyRef Reference to item identity
 * @param String $instanceOfRef Reference to type
 * @param String $nameType Reference to name type (currently not in use -> XTM 2.0)
 * @return DOMElement <topic> Element
 */
function wp2tm_createTopic($name, $identityRef='', $instanceOfRef='', $nameType='') {
	static $topicList = array();
	if(strlen($identityRef) && array_key_exists($identityRef, $topicList)) {
		return $topicList[$identityRef];
	}
	$domDoc = wp2tm_getDomDoc();
	$topic = $domDoc->createElement('topic');
	// Make sure all topics have an id (for topicRefs sake)
	$idAttr = wp2tm_createAttribute('id', wp2tm_createId($identityRef));
	$topic->appendChild($idAttr);
	if(strlen($instanceOfRef) > 0) {
		$topic->appendChild(wp2tm_createInstanceOf($instanceOfRef));
	}
	if(strlen($identityRef) > 0) {
		$topic->appendChild(wp2tm_createSubjectIdentity($identityRef));
	}
	$topic->appendChild(wp2tm_createBaseName($name));
	if(strlen($identityRef > 0)) {
		$topicList[$identityRef] = $topic;
	}
	return $topic;
}

/**
 * Creates and returns an <association> element
 *
 * @param String $assocTypePsi
 * @param Array  $members array('memberTopic' => DOMElement, 'roleTopic' => DOMElement)
 * @param String $scopeRef
 * @return DOMElement <association>
 */
function wp2tm_createAssociation($assocTypePsi, Array $members, $scopeRef = '') {
	$domDoc = wp2tm_getDomDoc();
	$assoc = $domDoc->createElement('association');
	if(strlen($assocTypePsi)) {
		$instanceOf = wp2tm_createInstanceOf($assocTypePsi);
		$assoc->appendChild($instanceOf);
	}
	if(strlen($scopeRef)) {
		$scope = wp2tm_createScope($scopeRef);
		$assoc->appendChild($scope);
	}
	foreach($members as $m) {
		$topicRef = $m['memberTopic'] ? '#' . $m['memberTopic']->getAttribute('id') : '';
		$roleRef  = $m['roleTopic']   ? '#' . $m['roleTopic']->getAttribute('id')   : '';
		$member = wp2tm_createMember($topicRef, $roleRef);
		$assoc->appendChild($member);
	}
	return $assoc;
}


/**
 * Creates and returns a <member> element
 * 
 * @param String $topicRef
 * @param String $roleTopicRef
 * @return DOMElement <member>
 */
function wp2tm_createMember($topicRef, $roleTopicRef = '') {
	$domDoc = wp2tm_getDomDoc();
	$member = $domDoc->createElement('member');
	if($roleTopicRef) {
		$roleSpec = $domDoc->createElement('roleSpec');
		$roleSpecRef = wp2tm_createTopicRef($roleTopicRef);
		$roleSpec->appendChild($roleSpecRef);
		$member->appendChild($roleSpec);
	}
	$memberTopicRef = wp2tm_createTopicRef($topicRef);
	$member->appendChild($memberTopicRef);
	return $member;	
}


/**
 * Creates and returns an internal occurrence, with resourceData set
 * 
 * @param String $instanceOfRef
 * @param String $data resource data
 * @return DOMElement <occurrence>
 */
function wp2tm_createInternalOccurrence($instanceOfRef, $data) {
	$domDoc = wp2tm_getDomDoc();
	$occ = $domDoc->createElement('occurrence');
	$resourceData = $domDoc->createElement('resourceData', $data);
	if(strlen($instanceOfRef)) {
		$occ->appendChild(wp2tm_createInstanceOf($instanceOfRef));
	}
	$occ->appendChild($resourceData);
	return $occ;
}


/**
 * Creates and returns a basename
 * 
 * @param String $nameStr
 * @param String $scopeRef
 * @return DOMElement <baseName>
 */
function wp2tm_createBaseName($nameStr, $scopeRef='') {
	$domDoc = wp2tm_getDomDoc();
	$bName = $domDoc->createElement('baseName');
	if(strlen($scopeRef)) { 
		$scope = wp2tm_createScope($scopeRef);
		$bName->appendChild($scope);
	}
	$bName->appendChild(wp2tm_createBaseNameString($nameStr));
	return $bName;
}

/**
 * Creates and returns a scope
 * 
 * @param String $ref
 * @return DOMElement <scope>
 */
function wp2tm_createScope($ref) {
	$domDoc = wp2tm_getDomDoc();
	$scope  = $domDoc->createElement('scope');
	$topicRef = wp2tm_createTopicRef($ref);
	$scope->appendChild($topicRef);
	return $scope;
}

/**
 * Creates and returns a topicRef element
 * 
 * @param String $ref
 * @return DOMElement <topicRef>
 */
function wp2tm_createTopicRef($ref) {
	$domDoc = wp2tm_getDomDoc();
	$topicRef = $domDoc->createElement('topicRef');
	$topicRef->appendChild(wp2tm_createAttribute('xlink:href', $ref));
	return $topicRef;
}

/**
 * Creates and returns a subject indicator element
 * 
 * @param String $ref
 * @return DOMElement <subjectIndicatorRef>
 */
function wp2tm_createSubjectIndicatorRef($ref) {
	$domDoc = wp2tm_getDomDoc();
	$subRef = $domDoc->createElement('subjectIndicatorRef');
	$subRef->appendChild(wp2tm_createAttribute('xlink:href', $ref));
	return $subRef;
}

/**
 * Creates and returns a resourceRef element
 * 
 * @param String $ref
 * @return DOMElement <resourceRef>
 */
function wp2tm_createResourceRef($ref) {
	$domDoc = wp2tm_getDomDoc();
	$resourceRef = $domDoc->createElement('resourceRef');
	$resourceRef->appendChild(wp2tm_createAttribute('xlink:href', $ref));
	return $resourceRef;
}

/**
 * Creates and returns a subjectIdentity element
 * 
 * @param String $ref
 * @param String $type (subjectIndicatorRef|resourceRef|topicRef) 
 * @return DOMElement <subjectIdentity>
 */
function wp2tm_createSubjectIdentity($refStr, $type = 'subjectIndicatorRef') {
	$domDoc = wp2tm_getDomDoc();
	$subjId = $domDoc->createElement('subjectIdentity');
	if($type == 'resourceRef') {
		$ref = wp2tm_createResourceRef($refStr);
	} elseif ($type == 'topicRef') {
		$ref = wp2tm_createTopicRef($refStr);
	} else {
		$ref = wp2tm_createSubjectIndicatorRef($refStr);
	}
	$subjId->appendChild($ref);
	return $subjId;
}

/**
 * Creates and returns an instanceOf element
 * @param String $typePsi
 * @return DOMElement <instanceOf> 
 */
function wp2tm_createInstanceOf($typePsi) {
	$domDoc = wp2tm_getDomDoc();
	$instanceOf = $domDoc->createElement('instanceOf');
	$instanceOf->appendChild(wp2tm_createSubjectIndicatorRef($typePsi));
	return $instanceOf;	
}

/**
 * Creates and return a basename string element
 * 
 * @param String $value
 * @return DOMElement <baseNameString>
 */
function wp2tm_createBaseNameString($value = '') {
	return wp2tm_getDomDoc()->createElement('baseNameString', $value);
}

/**
 * Singleton factory.
 * If not set, creates a new DOMDocument instance with <topicMap> element.  
 * 
 * @return DOMDocument the root node of the topic map
 */
function wp2tm_getDomDoc() {
	static $domDoc;
	if(!isset($domDoc)) {		
		$domDoc  = new DOMDocument('1.0', 'utf-8');
		$tm = $domDoc->createElement('topicMap');
		$tm->appendChild(wp2tm_createAttribute('xmlns', WP2TM_XTM1NS));
		$tm->appendChild(wp2tm_createAttribute('xmlns:xlink', WP2TM_XLINKNS));
		$tm->appendChild(wp2tm_createAttribute('id', wp2tm_createId($_SERVER['REQUEST_URI'])));
		// Feel free to remove the next 6 lines
		$comment = "\n\nXTM generated using Wordpress to ". 
		           'Topic Maps (WP2TM) v' . WP2TM_VERSION . 
		           ' from ' . WP2TM_URL . "\n\n" .
				   '(Get rid of this comment by removing lines ' . 
				   (__LINE__ - 4) . ' to ' . (__LINE__+1) . 
				   " in your wp2tm.php file).\n\n";
		$tm->appendChild($domDoc->createComment($comment));
		$domDoc->appendChild($tm);
	}
	return $domDoc;
}


/**
 * Creates and returns an attribute node
 *
 * @param String $name
 * @param String $value
 * @return DOMAttribute
 */
function wp2tm_createAttribute($name, $value='') {
	$doc = wp2tm_getDomDoc();
	$attr = $doc->createAttribute($name);
	$attr->nodeValue = $value;
	return $attr;
}

// _________________ Helper functions _________________ \\



/**
 * Generates an XML compliant ID based on fromStr.
 * If no fromStr is given, returns random id.
 * 
 * @return String id
 */
function wp2tm_createId($fromStr = '') {
	$id = strlen($fromStr) ? $fromStr : uniqid('wp2tm', true);
	return 'id' . substr(md5($id), 0, 8);
}


/**
 * @todo break up if need/point
 * @todo if single post feed: remove categories not used by that post (?)
 * 
 * Creates default topic types (xTT), topics (xTopic), 
 * association types (xAT), role types (xRT) and occurrence types (xOT)
 * and adds these to the DOMDocument's topicMap element
 */
function wp2tm_addDefaultTao($singlePost = false) {
	
	global $blogTT, $blogTopic, $blogEntryTT, $subjectTT, $resourceRT, 
		   $broaderRT, $narrowerRT, $broaderNarrowerAT, $valueRT, $containerRT, 
		   $containeeRT, $categoryTT, $termRT, $relatedTermRT, $termRelationshipAT, 
		   $blogHasRelatedTerms, $blogHasBroaderNarrower;

	// Set this to true if superclass-subclass should
	// be included as topic-, role- and association types. 
	// No need to bloat the TM unless needed.
	$blogHasBroaderNarrower = false;
	$blogHasRelatedTerms    = false;
		   
	$domDoc = wp2tm_getDomDoc();
	$xtmElm = $domDoc->firstChild;
	
	// Topic Types
	$blogTT        = wp2tm_createTopic('Blog', wp2tm_getPsi('onto:blog'));
	$blogEntryTT   = wp2tm_createTopic('Blog Post', wp2tm_getPsi('onto:blog-post'));
	$personTT      = wp2tm_createTopic('Person', wp2tm_getPsi('onto:person'));
	$descriptionOT = wp2tm_createTopic('Description', wp2tm_getPsi('dc:description')); 
	$resourceRT    = wp2tm_createTopic('Resource (Dublin Core / Topic Maps mapping)', wp2tm_getPsi('dc:resource'));
	$valueRT       = wp2tm_createTopic('Value (Dublin Core / Topic Maps mapping)', wp2tm_getPsi('dc:value'));
	$createdOT     = wp2tm_createTopic('Created Date', wp2tm_getPsi('dc:created'));
	$modifiedOT    = wp2tm_createTopic('Modified Date', wp2tm_getPsi('dc:modified'));
	$subjectTT     = wp2tm_createTopic('Subject', wp2tm_getPsi('onto:subject'));
	$categoryTT    = wp2tm_createTopic('Category', wp2tm_getPsi('sioc:container'));
	$containerRT   = wp2tm_createTopic('Container', wp2tm_getPsi('onto:container'));
	$containeeRT   = wp2tm_createTopic('Containee', wp2tm_getPsi('onto:containee'));
	$topicMapTT    = wp2tm_createTopic('Topic Map', wp2tm_getPsi('onto:tm'));
	
	$xtmElm->appendChild($topicMapTT);
	$xtmElm->appendChild($blogTT);
	$xtmElm->appendChild($blogEntryTT);
	$xtmElm->appendChild($personTT);
	$xtmElm->appendChild($descriptionOT);
	$xtmElm->appendChild($resourceRT);
	$xtmElm->appendChild($valueRT);
	$xtmElm->appendChild($createdOT);
	$xtmElm->appendChild($modifiedOT);
	$xtmElm->appendChild($categoryTT);
	$xtmElm->appendChild($subjectTT);
	$xtmElm->appendChild($containeeRT);
	$xtmElm->appendChild($containerRT);
	
	// Blog Instance
	$blogTopic = wp2tm_createTopic(wp2tm_xmltxt(get_bloginfo('name')), '', wp2tm_getPsi('onto:blog'));
	$blogSubjId = wp2tm_createSubjectIdentity(get_bloginfo('url'), 'resourceRef');
	$blogTopic->insertBefore($blogSubjId, $blogTopic->firstChild->nextSibling);
	$blogDescr = wp2tm_xmltxt(get_bloginfo('description'));
	$blogTopic->appendChild(wp2tm_createInternalOccurrence(wp2tm_getPsi('dc:description'), $blogDescr));
	$xtmElm->appendChild($blogTopic);
	
	// Broader <-> Narrower
	$broaderRT     = wp2tm_createTopic('Broader', wp2tm_getPsi('tech:broader'));
	$narrowerRT    = wp2tm_createTopic('Narrower', wp2tm_getPsi('tech:narrower'));
	$broaderNarrowerAT = wp2tm_createTopic('Broader/Narrower', wp2tm_getPsi('tech:broader-narrower'));
	$broaderNarrowerAT->appendChild(wp2tm_createBaseName('More specific', '#' . $broaderRT->getAttribute('id')));
	$broaderNarrowerAT->appendChild(wp2tm_createBaseName('More general',  '#' . $narrowerRT->getAttribute('id')));
	
	// These are for tag <-> related tag. Might want to replace with others.
	$termRT        = wp2tm_createTopic('Term', wp2tm_getPsi('tech:term')); 
	$relatedTermRT = wp2tm_createTopic('Related Term', wp2tm_getPsi('tech:related-term'));
	$termRelationshipAT = wp2tm_createTopic('Term Relationship', wp2tm_getPsi('tech:term-relationship'));
	$termRelationshipAT->appendChild(wp2tm_createBaseName('Has related term',    '#' . $termRT->getAttribute('id')));
	$termRelationshipAT->appendChild(wp2tm_createBaseName('Is related term for', '#' . $relatedTermRT->getAttribute('id')));	
	
	// Blog/Post Assoc. Type
	$hasPostAT  = wp2tm_createTopic('Blog has post', wp2tm_getPsi('onto:has-post'));
	$hasPostAT->appendChild(wp2tm_createBaseName('Has posts', '#' . $blogTT->getAttribute('id')));
	$hasPostAT->appendChild(wp2tm_createBaseName('Posted in', '#' . $blogEntryTT->getAttribute('id')));
	$xtmElm->appendChild($hasPostAT);
	
	// Post/Author Assoc. Type
	$hasAuthorAT = wp2tm_createTopic('Creator (Dublin Core)', wp2tm_getPsi('dc:creator'));
	$hasAuthorAT->appendChild(wp2tm_createBaseName('Creator of', '#' . $valueRT->getAttribute('id')));
	$hasAuthorAT->appendChild(wp2tm_createBaseName('Creators',   '#' . $resourceRT->getAttribute('id')));
	$xtmElm->appendChild($hasAuthorAT);
	
	// Post <-> Subject
	$subjectAT = wp2tm_createTopic('Subject (Dublin Core)', wp2tm_getPsi('dc:subject'));
	$subjectAT->appendChild(wp2tm_createBaseName('Subject for', '#' . $valueRT->getAttribute('id')));
	$subjectAT->appendChild(wp2tm_createBaseName('Subjects', '#' .   $resourceRT->getAttribute('id'))); 
	$xtmElm->appendChild($subjectAT);
	
	// Post <-> Category
	$categoryAT = wp2tm_createTopic('Container of', wp2tm_getPsi('sioc:container-of'));
	$categoryAT->appendChild(wp2tm_createBaseName('Contains', '#' . $containerRT->getAttribute('id')));
	$categoryAT->appendChild(wp2tm_createBaseName('Contained in', '#' . $containeeRT->getAttribute('id'))); 
	$xtmElm->appendChild($categoryAT);
	
	// Topic Map Reifier
	if($singlePost !== true) {
		$name = wp2tm_xmltxt(get_bloginfo('name'));
		$tmTopic = wp2tm_createTopic($name, '#'.$xtmElm->getAttribute('id'), wp2tm_getPsi('onto:tm')); 
		$descr = $name . ' (' . get_bloginfo('url') . ').';
		$descr = 'WP2TM Topic Map fragment representing ' . wp2tm_xmltxt($descr);
		$tmDescr = wp2tm_createInternalOccurrence(wp2tm_getPsi('dc:description'), $descr);
		$tmTopic->appendChild($tmDescr);
		$xtmElm->appendChild($tmTopic);	
	}
	
}


/**
 * Adds Topic, Associations and Occurrences for 
 * the current post, set up by Wordpress' setup_postdata(),
 * to the topicMap element of the DOMDocument.
 * 
 * @return void
 */
function wp2tm_addPostTao()  {
	
	global $post, $postTopic, $blogTT, $blogTopic, $blogEntryTT, $subjectTT,  
		   $resourceRT, $valueRT, $containerRT, $containeeRT, $categoryTT,
		   $termRT, $relatedTermRT, $blogHasRelatedTerms;
	
	// For keeping track of which associated topics have already been added to the topic map 
	static $addedTagUrls = array();
	static $addedRelTagUrls = array();
	static $addedAuthorUrls = array();
	$xtmElm = wp2tm_getDomDoc()->firstChild;
	
 	// Topics
 	$title     = wp2tm_xmlTxt(the_title('','',false));
 	$link      = wp2tm_xmlTxt(get_permalink('','',false));
 	$postTopic = wp2tm_createTopic($title, '', wp2tm_getPsi('onto:blog-post'));
 	$subjectId = wp2tm_createSubjectIdentity($link, 'resourceRef');
 	$postTopic->insertBefore($subjectId, $postTopic->firstChild->nextSibling); // after instanceOf
	$xtmElm->appendChild($postTopic);
	
	// Post occurrences
	$createdOcc = wp2tm_createInternalOccurrence(wp2tm_getPsi('dc:created'), get_the_time('r'));
	$lastModifiedOcc = wp2tm_createInternalOccurrence(wp2tm_getPsi('dc:modified'), get_the_modified_date('r'));
	$postTopic->appendChild($createdOcc);
	$postTopic->appendChild($lastModifiedOcc);
	$maxDescrLength = (int) wp2tm_getOption('wp2tm_max_length_description');
	$excerpt = trim(get_the_excerpt()); // first go for the excerpt; if empty, use the content
	if(strlen($excerpt) < 1) {
		$excerpt = trim(strip_tags(get_the_content()));
	}
	if(strlen($excerpt) > 0) {
		if($maxDescrLength > 0 && strlen($excerpt) > $maxDescrLength) {
			$excerpt = substr($excerpt, 0, min($maxDescrLength, strlen($excerpt) - 1)) . '...';
		}
		$excerpt = wp2tm_xmlTxt($excerpt) . '...';
		$excerptOcc = wp2tm_createInternalOccurrence(wp2tm_getPsi('dc:description'), $excerpt);
		$postTopic->appendChild($excerptOcc);
	}
	
	// Post <-> Blog Assoc.
	$pbAssocMembers = array( array('roleTopic'  => $blogTT, 'memberTopic'      => $blogTopic),
				             array('roleTopic'  => $blogEntryTT, 'memberTopic' => $postTopic));
	$postBlogAssoc = wp2tm_createAssociation(wp2tm_getPsi('onto:has-post'), $pbAssocMembers); 
	$xtmElm->appendChild($postBlogAssoc);
	
	// Post <-> Creator Assoc.
	$author = wp2tm_xmlTxt(get_the_author());
	$authorUrl = get_the_author_url();
	$authorUrl = strlen($authorUrl) > 0 ? $authorUrl : wp2tm_createAuthorUrl(get_the_author_ID());
	$authorTopic = wp2tm_createTopic($author, $authorUrl, wp2tm_getPsi('onto:person'));
	$paAssocMembers = array( array('roleTopic'  => $resourceRT, 'memberTopic' => $postTopic),
				             array('roleTopic'  => $valueRT,    'memberTopic' => $authorTopic));
	$postAuthorAssoc = wp2tm_createAssociation(wp2tm_getPsi('dc:creator'), $paAssocMembers); 
	if(!in_array($authorUrl, $addedAuthorUrls)) {
		$xtmElm->appendChild($authorTopic);
		array_push($addedAuthorUrls, $authorUrl);
	}
	$xtmElm->appendChild($postAuthorAssoc);
	
	// Tags
	$postTags = get_the_tags($post->ID);
	if ($postTags !== false) {
		foreach($postTags as $tag) {
			$tagUrl = get_tag_link($tag->term_id);
			$tagTopic = wp2tm_createTopic(wp2tm_xmlTxt($tag->name), $tagUrl, wp2tm_getPsi('onto:subject'));
			$tagAssocMembers = array( array('roleTopic' => $valueRT,    'memberTopic' => $tagTopic ),
                                array('roleTopic' => $resourceRT, 'memberTopic' => $postTopic));
			$authorScopeRef = '#'.$authorTopic->getAttribute('id');
			$assocPsi = wp2tm_getPsi('dc:subject');
			$tagPostAssoc = wp2tm_createAssociation($assocPsi, $tagAssocMembers, $authorScopeRef);
			if(!in_array($tagUrl, $addedTagUrls)) {
				array_push($addedTagUrls, $tagUrl);
				$xtmElm->appendChild($tagTopic);
			}
			$xtmElm->appendChild($tagPostAssoc);
			// Tag <-> Tag
			$relTagDataList   = wp2tm_getRelatedTagData($tag->term_id);
			$relTagAssocPsi   = wp2tm_getPsi('tech:term-relationship');
			$relTagAssocScope = $authorScopeRef;
			foreach($relTagDataList as $relTagData) {
		        $relTagUri  = wp2tm_xmlTxt($relTagData['uri']);
		        if($relTagData['useAsSubjectIndicator'] == 1) {
		          // add new subjectIndicator to tag topic
		          $subjectIndicatorRef = wp2tm_createSubjectIndicatorRef($relTagUri);
		          // @todo account for no subjectIdentity element
		          $tagTopic->getElementsByTagName('subjectIdentity')->item(0)->appendChild($subjectIndicatorRef);
		        } else {
		          // add new tag <-> rel. tag relationship
		          $blogHasRelatedTerms = true;
		          $relTagName = wp2tm_xmlTxt($relTagData['displayName']);
		          $relTagTopic = wp2tm_createTopic($relTagName, $relTagUri, wp2tm_getPsi('onto:subject'));
		          $relTagAssocMembers = array( array('roleTopic' => $termRT,        'memberTopic' => $tagTopic ),
		                                       array('roleTopic' => $relatedTermRT, 'memberTopic' => $relTagTopic) );
		          $relTagAssoc = wp2tm_createAssociation($relTagAssocPsi, $relTagAssocMembers, $relTagAssocScope);
		          if(!in_array($relTagUri, $addedRelTagUrls)) {
		            array_push($addedRelTagUrls, $relTagUri);
		            $xtmElm->appendChild($relTagTopic);
		          }
		          $xtmElm->appendChild($relTagAssoc);
		        }
			}
		}
	}
	
	// Categories
	$categoryList = get_the_category();
	foreach($categoryList as $category) {
		$categoryTopic = wp2tm_addCategory($category);
		$catAssocMembers = array( array('roleTopic' => $containerRT, 'memberTopic' => $categoryTopic),
								  array('roleTopic' => $containeeRT, 'memberTopic' => $postTopic));
		$typePsi  = wp2tm_getPsi('sioc:container-of');
		$scopeRef = $authorTopic ? '#'.$authorTopic->getAttribute('id') : '';
		$postCatAssoc = wp2tm_createAssociation($typePsi, $catAssocMembers, $scopeRef);
		$xtmElm->appendChild($postCatAssoc);
	}
	
}

/**
 * Adds category and broader <-> narrower relationships to topic map
 * 
 * @param Object $category Wordpress category object
 * @return DOMElement Category topic
 */
function wp2tm_addCategory(stdClass $category) {
	global $blogTopic, $narrowerRT, $broaderRT, $blogHasBroaderNarrower;
	static $addedCategoryUrls = array();
	$catUrl  = get_category_link($category->cat_ID);
	$catName = wp2tm_xmlTxt($category->cat_name);
	$catTypePsi = wp2tm_getPsi('sioc:container'); 
	$categoryTopic = wp2tm_createTopic($catName, $catUrl, $catTypePsi);
	if(!in_array($catUrl, $addedCategoryUrls)) {
		array_push($addedCategoryUrls, $catUrl);
		$xtmElm  = wp2tm_getDomDoc()->firstChild;
		$xtmElm->appendChild($categoryTopic);
		if($category->category_parent > 0) {
			// recursively add parent categories
			$blogHasBroaderNarrower = true;
			$parentCat = get_category($category->category_parent);
			wp2tm_addCategory($parentCat);
			$parentName = wp2tm_xmltxt($parentCat->cat_name);
			$parentCatTopic = wp2tm_createTopic($parentName, get_category_link($parentCat->cat_ID), $catTypePsi);
			$broadNarrowMembers = array( array('roleTopic' => $narrowerRT, 'memberTopic' => $categoryTopic),
									     array('roleTopic' => $broaderRT,  'memberTopic' => $parentCatTopic));
			$broaderNarrowerPsi = wp2tm_getPsi('tech:broader-narrower');
			$scopeRef = '#' . $blogTopic->getAttribute('id');
			$broaderNarrowerCatAssoc = wp2tm_createAssociation($broaderNarrowerPsi, $broadNarrowMembers, $scopeRef);
			$xtmElm->appendChild($broaderNarrowerCatAssoc);
		}
	}
	return $categoryTopic;
}



/**
 * Creates a URL to the author indicated by given id
 * Meant for use when WP's get_the_author_url() returns an empty string
 *
 * @param int $id
 * @return String URL
 */
function wp2tm_createAuthorUrl($id) {
	return get_bloginfo('url').'?author='.((int) $id);
}

/**
 * @todo "enum"
 * @param string $psiId
 *
 * PSI "container" since we are non-OOP her
 * 
 * @param string $psiId array key
 * @return string PSI
 * @return String
 */
function wp2tm_getPsi($psiId) {
	static $psis = array(
		'onto:tm'          => 'http://psi.ontopedia.net/Topic_Maps/Topic_Map',
		'onto:blog'        => 'http://psi.ontopedia.net/Blogging/Blog',
		'onto:blog-post'   => 'http://psi.ontopedia.net/Blogging/Post',
		'onto:has-post'    => 'http://psi.ontopedia.net/Blogging/Blog_has_post',
		'onto:subject'     => 'http://psi.ontopedia.net/Topic_Maps/Subject',
		'onto:container'   => 'http://psi.ontopedia.net/Container',
		'onto:containee'   => 'http://psi.ontopedia.net/Containee',
		'onto:person'      => 'http://psi.ontopedia.net/Person',
		'tech:part-whole'  => 'http://www.techquila.com/psi/thesaurus/#part-whole',
		'tech:part'        => 'http://www.techquila.com/psi/thesaurus/#part',
		'tech:whole'       => 'http://www.techquila.com/psi/thesaurus/#whole',
		'tech:broader'     => 'http://www.techquila.com/psi/thesaurus/#broader',
		'tech:narrower'    => 'http://www.techquila.com/psi/thesaurus/#narrower',
		'tech:broader-narrower' => 'http://www.techquila.com/psi/thesaurus/#broader-narrower',
		'tech:term'			=> 'http://www.techquila.com/psi/thesaurus/#term',				      // we really want something else
		'tech:related-term' => 'http://www.techquila.com/psi/thesaurus/#related-term',			  // goes for this too
		'tech:term-relationship'  => 'http://www.techquila.com/psi/thesaurus/#term-relationship', // and this
		'dc:subject'       => 'http://purl.org/dc/elements/1.1/subject',
		'dc:creator'       => 'http://purl.org/dc/elements/1.1/creator',
		'dc:created'       => 'http://purl.org/dc/terms/created',
		'dc:modified'      => 'http://purl.org/dc/terms/modified',
		'dc:description'   => 'http://purl.org/dc/elements/1.1/description', 
		'dc:abstract'      => 'http://purl.org/dc/terms/abstract',
		'dc:resource'      => 'http://psi.topicmaps.org/iso29111/resource',
		'dc:value'         => 'http://psi.topicmaps.org/iso29111/value',
		'xtm:superclass'   => 'http://www.topicmaps.org/xtm/1.0/core.xtm#superclass',
		'xtm:subclass'     => 'http://www.topicmaps.org/xtm/1.0/core.xtm#subclass',
		'xtm:superclass-subclass' => 'http://www.topicmaps.org/xtm/1.0/core.xtm#superclass-subclass',
		// For categories
		'sioc:container-of' => 'http://rdfs.org/sioc/ns#container_of',	// PSIs would be better
		'sioc:container'    => 'http://rdfs.org/sioc/ns#Container'		// here too
	);
	return $psis[$psiId];
}



/**
 * Escapes and returns string to be included in XML output
 *
 * @return String
 */
function wp2tm_xmlTxt($str) {
	return htmlspecialchars($str, ENT_COMPAT, 'utf-8');
}

/**
 * @param Array $wp2tmParams parameters for WP2TM
 * @param String $wpParams parameters passed on to Wordpress' get_posts(). See Wordpress doc.
 * @return String XML Topic Maps representation of blog/posts
 */
function wp2tm_makeXtm(Array $wp2tmParams, $wpParams) {
	global $post, $narrowerRT, $broaderRT, $broaderNarrowerAT, 
		   $termRT, $relatedTermRT, $termRelationshipAT, 
		   $blogHasBroaderNarrower, $blogHasRelatedTerms;
	$singlePost = $wp2tmParams['postId'] > 0; // NB! 1 post is not the same as single post
	$postList = $singlePost ? array(get_post($wp2tmParams['postId'], OBJECT)) : get_posts($wpParams);
	$noPosts = count($postList);
	wp2tm_addDefaultTao($singlePost);
	for($i = 0; $i < $noPosts; $i++){
		$post = $postList[$i];
		setup_postdata($post);
		if(wp2tm_excludePost()) {
      		continue;
		}
		wp2tm_addPostTao();
	}
	$xtmElm = wp2tm_getDomDoc()->firstChild;
	if($blogHasBroaderNarrower === true) {
		$xtmElm->appendChild($broaderRT);
		$xtmElm->appendChild($narrowerRT);
		$xtmElm->appendChild($broaderNarrowerAT);
	}	
	if($blogHasRelatedTerms === true) {
		$xtmElm->appendChild($termRT);
		$xtmElm->appendChild($relatedTermRT);
		$xtmElm->appendChild($termRelationshipAT);
	}
	return wp2tm_getDomDoc()->saveXML();
}


/**
 * Must be used with "the loop"
 * @return boolean true if current post belongs to any of the excluded categories, else false
 */
function wp2tm_excludePost() {
  $categoryList = get_the_category();
  foreach($categoryList as $category) { 
    if(in_array($category->cat_ID, wp2tm_getOption('wp2tm_exclude_categories'))) {
      return true;
    }
  }
  return false;
}


// _________________ Plugin Setup and Admin _________________ \\


add_action('init',       'wp2tm_addFeed');
add_action('wp_footer',  'wp2tm_footer');
add_action('admin_menu', 'wp2tm_addOptionsPage');

/**
 * Add feed to WP feeds. Must be called after init
 * @return void
 */
function wp2tm_addFeed() {
	add_feed('xtm1', 'wp2tm_printXtm');
}

/**
 * @todo should print link to post feed if post belongs to excluded category
 *
 * Prints feed links
 * @return void
 */
function wp2tm_printFeedLinks() {
	echo '<ul id="wp2tm-feeds"><li>' .
	     '<a href="' . get_bloginfo('url'). '?feed=xtm1">XTM 1.0 (Blog)</a></li>';
	if(is_single() && wp2tm_getOption('wp2tm_enable_post_feed') == 1) {
		echo '<li><a href="' . get_bloginfo('url') . '?feed=xtm1&amp;p=';
		the_ID();
		echo '">XTM 1.0 (Post)</a>';
	}
	echo '</ul>';
}

/**
 * WP footer actions
 * @return void
 */
function wp2tm_footer() {
	$options = wp2tm_getOptions();
	if($options['wp2tm_enable_footer_links'] == 1) {
		wp2tm_printFeedLinks();
	}
}


/**
 * Prints XTM feed from Wordpress
 * @return void
 */
function wp2tm_printXtm() {
	if(!headers_sent()) {
		header('Content-Type: application/xml; charset=utf-8', true);
	}
	$postId      = array_key_exists('p', $_GET) && wp2tm_getOption('wp2tm_enable_post_feed') == 1 ? (int)$_GET['p'] : 0; // single post page
	$wpParams    = 'numberposts=' . wp2tm_getOption('wp2tm_no_posts_in_feed');	// passed on to Wordpress' get_posts()
	$wp2tmParams = array('postId' => $postId);
	echo wp2tm_makeXtm($wp2tmParams, $wpParams);
	exit;
}


/**
 * @todo better error handling / messages
 *
 * The options page and associated actions
 */
function wp2tm_optionsPage() {
	echo '<div class="wrap"><h2>Wordpress Topic Maps (WP2TM) Options</h2>';
	if(array_key_exists('update_wp2tm_options', $_POST)) {
		if( wp2tm_updateOptions() ) {
			echo '
	            <div id="message" class="updated fade">
	                <p><strong>The WP2TM Options were successfully updated</strong>.</p>
	                <p>
	                	Please note that Wordpress probably caches the output and that this 
	                    version of WP2TM does not include an internal cache. 
	                    Therefore, <strong>it might take a while before the changes appear</strong>
	                    in WP2TM generated XTM.
	                </p>
	            </div>
	        ';
       	} else {
            echo '<div id="message" class="error fade">';
            echo '<p><strong>Error</strong><br />Failed to update one or more WP2TM options.</p>';
            echo '</div>';
		}
	}
	$options = wp2tm_getOptions();
	include( dirname(__FILE__) . '/templates/options.php' );
	echo '</div>';
}

/**
 * @todo input validation / exception handling on invalid values
 *
 * Updates options with data set in the WP admin panel
 *
 * @return boolean true iff every update succeeded, else false
 */
function wp2tm_updateOptions() {
	$result = true;
	$options = wp2tm_getOptions();
	if( array_key_exists('wp2tm_max_length_description', $_POST) && 
	 	$_POST['wp2tm_max_length_description'] != $options['wp2tm_max_length_description']) {
		$result = $result && wp2tm_setOption('wp2tm_max_length_description', 
											  (int)$_POST['wp2tm_max_length_description']);
	}
	if( array_key_exists('wp2tm_no_posts_in_feed', $_POST) && 
	    $_POST['wp2tm_no_posts_in_feed'] != $options['wp2tm_no_posts_in_feed']) {
		$result = $result && wp2tm_setOption('wp2tm_no_posts_in_feed', 
											  (int)$_POST['wp2tm_no_posts_in_feed']);
	}
	$enablePostFeed    = (int) array_key_exists('wp2tm_enable_post_feed', $_POST);
	$enableFooterLinks = (int) array_key_exists('wp2tm_enable_footer_links', $_POST);
	if($enablePostFeed != $options['wp2tm_enable_post_feed']) {
		$result = $result && wp2tm_setOption('wp2tm_enable_post_feed', $enablePostFeed);
	}
	if($enableFooterLinks != $options['wp2tm_enable_footer_links']) {
		$result = $result && wp2tm_setOption('wp2tm_enable_footer_links', $enableFooterLinks);
	}
	$exclCatIds = array();
	$categoryIdList = is_array($_POST['wp2tm_exclude_categories']) ? $_POST['wp2tm_exclude_categories'] : array();
	foreach($categoryIdList as $categoryId) {
		array_push($exclCatIds, (int) $categoryId);
	}
	if($exclCatIds != $options['wp2tm_exclude_categories']) {
		$result = $result && wp2tm_setOption('wp2tm_exclude_categories', $exclCatIds);
	}
	$relTagsToAdd = array();
	if(array_key_exists('wp2tm_related_tags', $_POST)) {
    	$uriPattern = "/^[a-z0-9]+.+$/i"; // no need for a strict rule at this point
    	$relatedTagsList = is_array($_POST['wp2tm_related_tags']) ? $_POST['wp2tm_related_tags'] : array();
		foreach($relatedTagsList as $tagId => $relTags) {
			foreach($relTags as $relTagData) {
		        $relTagData['uri'] = trim($relTagData['uri']);
		        $relTagData['displayName'] = trim($relTagData['displayName']);
		        $relTagData['useAsSubjectIndicator'] = (int)$relTagData['useAsSubjectIndicator'];
		        if(preg_match($uriPattern, $relTagData['uri'])) {
		        	if(!array_key_exists($tagId, $relTagsToAdd)) {
		        		$relTagsToAdd[$tagId] = array();
		        	}
		      	    array_push($relTagsToAdd[$tagId], $relTagData);
		        }
			}
		}
	}
	if($relTagsToAdd != $options['wp2tm_related_tags']) {
		//print_r(htmlentities(serialize($relTagsToAdd)));
		$result = $result && wp2tm_setOption('wp2tm_related_tags', $relTagsToAdd);
	}
	return $result;
}

/**
 * Adds WP2TM's option page to the WP admin panel
 * @return void
 */
function wp2tm_addOptionsPage() {
	add_options_page( 'Wordpress Topic Maps Plugin', 
					  'WP Topic Maps', 
					  'manage_options', 
					  __FILE__, 
					  'wp2tm_optionsPage'
					);
}

/**
 * @return Array WP2TM options
 */
function wp2tm_getOptions($forceUpdate = false) {
	static $options = array();
	if(!empty($options) && $forceUpdate !== true) {
		return $options;
	}
	$defaultOptions = array(
		'wp2tm_no_posts_in_feed'       => 15,
		'wp2tm_enable_post_feed'       => 1,
		'wp2tm_enable_footer_links'    => 1,
		'wp2tm_max_length_description' => 300,
		'wp2tm_exclude_categories'     => array(),
		'wp2tm_related_tags'           => array(),	    // Array ( 'tagId' => Array( 'psi1', 'psi2', ... 'psiN' ) )
		'wp2tm_version'                => WP2TM_VERSION	// keep track of options for given version
	);
	// Fetch from DB
	foreach($defaultOptions as $key => $value) {
		if($key == 'wp2tm_related_tags') {
			$options[$key] = get_option($key);
		} else {
			$options[$key] = get_option($key);
		}
	}
	// Insert if first time, else check version (and other opt)
	if($options['wp2tm_version'] == '') {
		// add initial values
		foreach($defaultOptions as $name => $value) {
			if(add_option($name, $value, '', 'no')) {
				$options[$name] = $value;
			}
		}
	} else if ($options['wp2tm_version'] != WP2TM_VERSION) {
		// probably need to do other stuff here later...
		update_option('wp2tm_version', WP2TM_VERSION, '', 'no');
	}
	return $options;
}

/**
 * @todo add_option for new option
 * @return boolean true if option is updated, else false (incl. equal values)
 */
function wp2tm_setOption($name, $value) {
	return update_option($name, $value, '', 'no');
}

/**
 * @return mixed option value
 */
function wp2tm_getOption($name) {
	$options = wp2tm_getOptions();
	return array_key_exists($name, $options) ? $options[$name] : NULL;
}

/**
 * @return boolean
 */
function wp2tm_setRelatedTagData($tagId, Array $relatedTagData) {
	$options =& wp2tm_getOptions();
	$options['wp2tm_related_tags'][$tagId] = $relatedTagData;
	return wp2tm_setOption('wp2tm_related_tags', $options['wp2tm_related_tags']);
}

/**
 * @return Array data about related tag
 */
function wp2tm_getRelatedTagData($tagId) {
	$options = wp2tm_getOptions();
	$relatedTagsData = $options['wp2tm_related_tags'];
	return array_key_exists($tagId, $relatedTagsData) ? $relatedTagsData[$tagId] : array();
}

?>