<?php
/**
 * Simplecart Orders dashboard block
 *
 * This file holds the functions needed for the Simplecart Orders dashboard block
 *
 * @copyright	http://smartfactory.ca The SmartFactory
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @author		marcan aka Marc-Andre Lanciault <marcan@smartfactory.ca>
 * @author		Madfish
 * @since		1.0
 * @package		news
 * @version		$Id$
 */

if (!defined("ICMS_ROOT_PATH")) die("ICMS root path not defined");

/**
 * Prepares the recent news block for display
 *
 * @param array $options
 * @return string
 */
function simplecart_orders_dashboard_show($options) {

	$module_handler = icms::handler('simplecart');
	$module_handler->init();


	// retrieve the last XX articles
	if (icms_get_module_status("sprockets") && ($options[1] || $untagged_content)) { // filter by tag
		$article_object_array = array();
		$sprockets_taglink_handler = icms_getModuleHandler('taglink', $sprocketsModule->getVar('dirname'),
				'sprockets');

		// Ensure $options[1] is properly cast to integer
		if ($untagged_content) {
			$options[1] = 0;
		}
		$tid = (int)$options[1];

		// Use sprintf for secure query construction with proper integer casting
		$query = sprintf(
			"SELECT * FROM %s, %s WHERE `article_id` = `iid` AND `online_status` = '1' AND `date` <= %d AND `tid` = %d AND `mid` = %d AND `item` = 'article' ORDER BY `date` DESC LIMIT 0, %d",
			$news_article_handler->table,
			$sprockets_taglink_handler->table,
			(int)time(),
			$tid,
			(int)$newsModule->getVar('mid'),
			(int)$options[0] + 1
		);

		$result = icms::$xoopsDB->query($query);

		if (!$result) {
			icms_core_Message::error('Error: Recent articles block');
			return array();

		} else {
			$rows = $news_article_handler->convertResultSet($result);
			foreach ($rows as $key => $row) {
				$article_object_array[$row->getVar('article_id')] = $row;
			}
		}

		$block['recent_news_articles'] = $article_object_array;

	} else { // do not filter by tag

		$criteria = new icms_db_criteria_Compo();
		$criteria->setStart(0);
		$criteria->setLimit($options[0] +1); // spotlighted article will not be included in the list
		$criteria->setSort('date');
		$criteria->setOrder('DESC');
		$criteria->add(new icms_db_criteria_Item('online_status', TRUE));
		$criteria->add(new icms_db_criteria_Item('date', time(), '<'));

		// retrieve the news articles to show in the block
		$article_object_array = $news_article_handler->getObjects($criteria, TRUE, TRUE);
		$block['recent_news_articles'] = $article_object_array;
	}

	/*
	 * Determine the relative path to the web root, required for display of uploaded images.
	 * I don't like to call $GLOBALS['xoops'] but due to differences between Linux and
	 * Windows servers finding the relative path to root is a lot more complicated than you'd
	 * like to think.
	 */
	$script_name = $GLOBALS['xoops']->urls['phpself'];
	$base_name = basename($script_name);
	// Block is being displayed on home page without a start module (start = none)
	if (strpos($script_name, '/modules/') === FALSE) {
		switch ($base_name) {
			case "index.php":
				$document_root = str_replace('index.php', '', $script_name);
			break;
		case "index.html":
				$document_root = str_replace('index.html', '', $script_name);
			break;
		case "index.htm":
				$document_root = str_replace('index.htm', '', $script_name);
			break;
		}
	} else { // Block is being displayed on a module page
		$document_root = substr($script_name, 0, strpos($script_name, "/modules/"));
	}

	// check if spotlight mode is active, and if spotlight article has already been retrieved
	if ($options[4] == TRUE && (!empty($block['recent_news_articles']))) {
		if (array_key_exists($options[5], $block['recent_news_articles'])) {
			$spotlightObj = $block['recent_news_articles'][$options[5]];
			unset($block['recent_news_articles'][$options[5]]);
		} elseif ($options[5] == 0) {
			$spotlightObj = array_shift($block['recent_news_articles']);
		} else {
			$spotlightObj = $news_article_handler->get($options[5]);
			$trim = array_pop($block['recent_news_articles']);
		}

		// prepare spotlight for display
		global $newsConfig;

		//problem - stylesheet does not seem to get imported when block is cached
		//global $xoTheme;
		//$xoTheme->addStylesheet(ICMS_URL . '/modules/news/module.css');

		$block['recent_news_spotlight_title'] = $spotlightObj->getVar('title');
		if ($spotlightObj->getVar('image')) {
			$block['recent_news_spotlight_display_image'] = $spotlightObj->getVar('display_image', 'e');
			$block['recent_news_spotlight_display_lead_image'] = &$block['recent_news_spotlight_display_image']; // legacy template compatibility
			$block['recent_news_spotlight_display_topic_image'] = $spotlightObj->getVar('display_topic_image', 'e');
			$block['recent_news_spotlight_image'] = $document_root  . $spotlightObj->get_image_tag();
			$block['recent_news_spotlight_lead_image'] = &$block['recent_news_spotlight_image']; // legacy template compatibilty
			$block['recent_news_image_display_width'] = icms_getConfig('image_display_width', 'news');
			$block['recent_news_lead_image_display_width'] = &$block['recent_news_image_display_width']; // legacy template compatibility
		} else {
			$block['recent_news_spotlight_image'] = FALSE;
			$block['recent_news_spotlight_lead_image'] = &$block['recent_news_spotlight_image']; // legacy template compatibility
		}
		$block['recent_news_spotlight_description'] = $spotlightObj->getVar('description');
		$block['recent_news_spotlight_link'] = $spotlightObj->getItemLink(TRUE);
		$short_url = $spotlightObj->getVar('short_url');
		if (!empty($short_url))
		{
			$block['recent_news_spotlight_link'] .= '&amp;title=' . $short_url;
		}
		$block['recent_news_title'] = _MB_NEWS_ARTICLE_SPOTLIGHT_RECENT_ARTICLES;
	}

	// prepare article links for display
	foreach ($block['recent_news_articles'] as &$article) {
		$title = $article->getVar('title');
		$itemLink = $article->getItemLinkWithSEOString();
		$image_tag = $article->get_image_tag();
		// trim the title if its length exceeds the block preferences
		if (strlen($title) > $options[3]) {
			$article->setVar('title', substr($title, 0, ($options[3] - 3)) . '...');
		}

		// formats timestamp according to the block options
		$date = $article->getVar('date', 'e');
		// Use block option date format, fallback to module config if empty
		$dateFormat = !empty($options[2]) ? $options[2] : icms_getConfig('date_format', 'news');
		$date = date($dateFormat, $date);
		$article = $article->toArrayWithoutOverrides();
		$article['date'] = $date;

		// Fix the image path
		if ($article['image']) {
			$article['image'] = $document_root . $image_tag;
		}

		// Add the SEO string to the itemLink
		$article['itemLink'] = $itemLink;
		if (!empty($article['short_url'])) {
			$article['itemUrl'] .= '&amp;title=' . $article['short_url'];
		}
	}

	// Optional tagging support (only used in teaser mode)
	if (icms_get_module_status("sprockets") && $options[8]) {
		$article_ids = $article_tags = $tagList = array();
		$sprockets_tag_handler = icms_getModuleHandler('tag', 'sprockets', 'sprockets');
		$sprockets_taglink_handler = icms_getModuleHandler('taglink', $sprocketsModule->getVar('dirname'),
				'sprockets');
		$tagList = $sprockets_tag_handler->getTagBuffer(FALSE);
		$article_ids = array_keys($article_object_array);
		if (!empty($article_ids)) {
			$article_tags = $sprockets_taglink_handler->getTagsForObjects($article_ids, 'article');
			foreach ($block['recent_news_articles'] as &$article) {
				if ($article_tags[$article['article_id']]) {
					foreach ($article_tags[$article['article_id']] as $tag) {
						if ($tag == '0') {
							$tagLinks[] = '<a href="' . ICMS_URL . '/modules/news/article.php?tag_id=untagged">' . $tagList[$tag] . '</a>';
						} else {
							$tagLinks[] = '<a href="' . ICMS_URL . '/modules/news/article.php?tag_id=' . $tag . '">' . $tagList[$tag] . '</a>';
						}
					}
					$article['tags'] = implode(", ", $tagLinks);
					unset($tagLinks);
				}
			}
		}
	}

	// Set some preferences
	if ($block['recent_news_articles']) {
		$block['recent_news_image_position'] = $options[6];
		$block['recent_news_image_width'] = $options[7];
		$block['recent_news_display_mode'] = $options[8];
	} else {
		$block = array();
	}

	return $block;
}

/**
 * Edit options for the recent news block
 *
 * @param array $options
 * @return string
 */
function news_article_recent_edit($options) {

	$newsModule = icms_getModuleInfo('news');
	include_once(ICMS_ROOT_PATH . '/modules/' . $newsModule->getVar('dirname') . '/include/common.php');
	$news_article_handler = icms_getModuleHandler('article', $newsModule->getVar('dirname'), 'news');

	// select number of recent articles to display in the block
	$form = '<table><tr>';
	$form .= '<tr><td>' . _MB_NEWS_ARTICLE_RECENT_LIMIT . '</td>';
	$form .= '<td>' . '<input type="text" name="options[]" value="' . $options[0] . '" /></td>';
	$form .= '</tr>';

	// optionally display results from a single tag - only if sprockets module is installed
	$sprocketsModule = icms_getModuleInfo('sprockets');
	if (icms_get_module_status("sprockets")) {
		$sprockets_tag_handler = icms_getModuleHandler('tag', $sprocketsModule->getVar('dirname'),
				'sprockets');
		$form .= '<tr><td>' . _MB_NEWS_ARTICLE_RECENT_TAG . '</td>';
		// Parameters icms_form_elements_Select: ($caption, $name, $value = null, $size = 1, $multiple = FALSE)
		$form_select = new icms_form_elements_Select('', 'options[]', $options[1], '1', FALSE);
		$criteria = icms_buildCriteria(array('label_type' => '0'));
		$criteria->setSort('title');
		$criteria->setOrder('ASC');
		$tagList = $sprockets_tag_handler->getList($criteria);
		$tagList = array(0 => 'All') + $tagList;
		$form_select->addOptionArray($tagList);
		$form .= '<td>' . $form_select->render() . '</td></tr>';
	}

	// customise date format string as per PHP's date() method
	$form .= '<td>' . _MB_NEWS_ARTICLE_DATE_STRING . '</td>';
	$form .= '<td>' . '<input type="text" name="options[2]" value="' . $options[2]
		. '" /></td></tr>';

	// limit title length
	$form .= '<tr><td>' . _MB_NEWS_ARTICLE_TITLE_LENGTH . '</td>';
	$form .= '<td>' . '<input type="text" name="options[3]" value="' . $options[3]
		. '" /></td></tr>';

	// activate spotlight feature
	$form .= '<tr><td>' . _MB_NEWS_ARTICLE_ACTIVATE_SPOTLIGHT . '</td>';
	$form .= '<td><input type="radio" name="options[4]" value="1"';
		if ($options[4] == 1) {
			$form .= ' checked="checked"';
		}
	$form .= '/>' . _MB_NEWS_ARTICLE_YES;
	$form .= '<input type="radio" name="options[4]" value="0"';
	if ($options[4] == 0) {
		$form .= 'checked="checked"';
	}
	$form .= '/>' . _MB_NEWS_ARTICLE_NO . '</td></tr>';


	// build select box for choosing article to spotlight - need to filter by tag (if set)
	if (icms_get_module_status("sprockets") && $options[1]) {

		$query = $rows = $tag_article_count = '';
		$article_array = array(0 => _MB_NEWS_ARTICLE_MOST_RECENT_ARTICLE);
		$sprockets_taglink_handler = icms_getModuleHandler('taglink', $sprocketsModule->getVar('dirname'),
				'sprockets');

		$query = "SELECT * FROM " . $news_article_handler->table . ", "
					. $sprockets_taglink_handler->table
					. " WHERE `article_id` = `iid`"
					. " AND `online_status` = '1'"
					. " AND `date` <= '" . time() . "'"
					. " AND `tid` = '" . $options[1] . "'"
					. " AND `mid` = '" . $newsModule->getVar('mid') . "'"
					. " AND `item` = 'article'"
					. " ORDER BY `date` DESC"
					. " LIMIT " . '0, ' . $options[0]++;

		$result = icms::$xoopsDB->query($query);

		if (!$result) {
			echo 'Error: Recent articles block';
			exit;

		} else {
			$rows = $news_article_handler->convertResultSet($result);
			foreach ($rows as $key => $row) {
				$article_array[$row->getVar('article_id')] = $row->getVar('title');
			}
		}
	} else {
		$criteria = new icms_db_criteria_Compo();
		$criteria->setStart(0);
		$criteria->setLimit($options[0]+1);
		$criteria->setSort('date');
		$criteria->setOrder('DESC');
		$criteria->add(new icms_db_criteria_Item('online_status', TRUE));
		$criteria->add(new icms_db_criteria_Item('date', time(), '<'));

		// retrieve the articles
		$article_array = $news_article_handler->getList($criteria);
		$article_array = array(0 => _MB_NEWS_ARTICLE_MOST_RECENT_ARTICLE) + $article_array;
	}

	// build a select box of article titles
	$form .= '<tr><td>' . _MB_NEWS_ARTICLE_SPOTLIGHTED_ARTICLE . '</td>';
	// Parameters icms_form_elements_Select: ($caption, $name, $value = null, $size = 1, $multiple = FALSE)
	$form_spotlight = new icms_form_elements_Select('', 'options[5]', $options[5], '1', FALSE);
	$form_spotlight->addOptionArray($article_array);
	$form .= '<td>' . $form_spotlight->render() . '</td></tr>';

	// Position of teaser images
	$form .= '<tr><td>' . _MB_NEWS_ARTICLE_IMAGE_POSITION . '</td>';
	$form_select2 = new icms_form_elements_Select('', 'options[6]', $options[6], '1', FALSE);
	$form_select2->addOptionArray(array(0 => _MB_NEWS_ARTICLE_IMAGE_NONE,
		1 => _MB_NEWS_ARTICLE_IMAGE_LEFT, 2 => _MB_NEWS_ARTICLE_IMAGE_RIGHT));
	$form .= '<td>' . $form_select2->render() . '</td></tr>';

	// Size of teaser image (automatically resized and cached by Smarty plugin)
	$form .= '<tr><td>' . _MB_NEWS_ARTICLE_IMAGE_WIDTH . '</td>';
	$form .= '<td><input type="text" name="options[7]" value="' . $options[7] . '" /></td></tr>';

	// Select display mode: Simple list (0) or teasers (1)
	$form .= '<tr><td>' . _MB_NEWS_ARTICLE_DISPLAY_MODE . '</td>';
	$form_select3 = new icms_form_elements_Select('', 'options[8]', $options[8], '1', FALSE);
	$form_select3->addOptionArray(array(0 => _MB_NEWS_ARTICLE_LIST, 1 => _MB_NEWS_ARTICLE_TEASERS));
	$form .= '<td>' . $form_select3->render() . '</td></tr>';

	// Dynamic tag filtering - overrides the tag filter
	$form .= '<tr><td>' . _MB_NEWS_ARTICLE_DYNAMIC_TAG . '</td>';
	$form .= '<td><input type="radio" name="options[9]" value="1"';
	if ($options[9] == 1) {
		$form .= ' checked="checked"';
	}
	$form .= '/>' . _MB_NEWS_ARTICLE_YES;
	$form .= '<input type="radio" name="options[9]" value="0"';
	if ($options[9] == 0) {
		$form .= 'checked="checked"';
	}
	$form .= '/>' . _MB_NEWS_ARTICLE_NO . '</td></tr>';

	$form .= '</table>';

	return $form;
}