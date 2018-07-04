<?php
/******************************************************************************
* Subs.php                                                                    *
*******************************************************************************
* SMF: Simple Machines Forum                                                  *
* Open-Source Project Inspired by Zef Hemel (zef@zefhemel.com)                *
* =========================================================================== *
* Software Version:           SMF 1.0 RC2                                     *
* Software by:                Simple Machines (http://www.simplemachines.org) *
* Copyright 2001-2004 by:     Lewis Media (http://www.lewismedia.com)         *
* Support, News, Updates at:  http://www.simplemachines.org                   *
*******************************************************************************
* This program is free software; you may redistribute it and/or modify it     *
* under the terms of the provided license as published by Lewis Media.        *
*                                                                             *
* This program is distributed in the hope that it is and will be useful,      *
* but WITHOUT ANY WARRANTIES; without even any implied warranty of            *
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                        *
*                                                                             *
* See the "license.txt" file for details of the Simple Machines license.      *
* The latest version can always be found at http://www.simplemachines.org.    *
******************************************************************************/


/*	This file is has all the main functions in it that relate to, well,
	everything.  It provides all of the following functions:

	resource db_query(string database_query, string __FILE__, int __LINE__)
		- should always be used in place of mysql_query.
		- executes a query string, and implements needed error checking.
		- always use the magic constants __FILE__ and __LINE__.
		- returns a MySQL result resource, to be freed with mysql_free_result.

	int db_affected_rows()
		- should always be used in place of db_insert_id.
		- returns the number of affected rows by the most recently executed
		  query.
		- handles the current connection so the forum with other connections
		  active at the same time.

	int db_insert_id()
		- should always be used in place of mysql_insert_id.
		- returns the most recently generated auto_increment column.
		- handles the current connection so the forum with other connections
		  active at the same time.

	void updateLastMessages(array ID_BOARDs)
		// !!!

	void updateStats(string statistic, string condition = '1')
		// !!!

	void updateMemberData($ID_MEMBER, $array)
		// !!!

	void updateSettings($changeArray)
		// !!!

	string constructPageIndex($base_url, &$start, $max_value, $num_per_page,
			$compact_start = false)
		// !!!

	string comma_format($number)
		// !!!

	string timeformat($logTime, $show_today = true)
		// !!!

	string un_htmlspecialchars(string text)
		- removes the base entities (&lt;, &quot;, etc.) from text.
		- should be used instead of html_entity_decode for PHP version
		  compatibility reasons.
		- additionally converts &nbsp; and &#039;.

	int forum_time(bool use_user_offset = true)
		- returns the current time with offsets.
		- always applies the offset in the time_offset setting.
		- if use_user_offset is true, applies the user's offset as well.
		- returns seconds since the unix epoch.

	string doUBBC(string message, bool enableSmileys = true)
		// !!!

	void parsecode(string &message)
		// !!!

	void parsesmileys(string &message)
		// !!!

	string highlight_php_code(string code)
		// !!!

	void writeLog(bool force = false)
		// !!!

	void redirectexit(string setLocation = '', bool add = true, bool refresh = false)
		// !!!

	void obExit(bool do_header = true, bool do_footer = do_header)
		// !!!

	void adminIndex($area)
		// !!!

	int logAction($action, $extra = array())
		// !!!

	void trackStats($stats = array())
		- caches statistics changes, and flushes them if you pass nothing.
		- if '+' is used as a value, it will be incremented.
		- does not actually commit the changes until the end of the page view.
		- depends on the trackStats setting.

	void spamProtection(string error_type)
		- attempts to protect from spammed messages and the like.
		- takes a $txt index. (not an actual string.)
		- depends on the spamWaitTime setting.

	array url_image_size(string url)
		- uses getimagesize() to determine the size of a file.
		- attempts to connect to the server first so it won't time out.
		- returns false on failure, otherwise the output of getimagesize().

	void determineTopicClass(array &topic_context)
		// !!!

	void setupThemeContext()
		// !!!

	void template_rawdata()
		// !!!

	void template_header()
		// !!!

	void theme_copyright(bool get_it = false)
		// !!!

	void template_footer()
		// !!!

	void db_debug_junk()
		// !!!

	void getAttachmentFilename(string filename, int ID_ATTACH, bool new = true)
		// !!!
*/

function ToUnix($timestamp) {
	// Timestamp - 1 due to CET vs GMT
	$timestamp = $timestamp - 3600;
	
	// Form date variables
	$m = date("m", $timestamp);	// Month
	$d = date("d", $timestamp);	// Day
	$y = date("Y", $timestamp);	// Year
	$h = date("H", $timestamp);	// Hours
	$i = date("i", $timestamp);	// Minutes
	$a = date("a", $timestamp); // AM/PM

	return "$d/$m/$y, $h:$i GMT";
}

// Removes special entities from strings.  Compatibility...
function un_htmlspecialchars($string)
{
	return strtr($string, array_flip(get_html_translation_table(HTML_SPECIALCHARS, ENT_QUOTES)) + array('&#039;' => '\'', '&nbsp;' => ' '));
}

// Parse smileys (if $enableSmileys is true) and BBC in $message.
function doUBBC($message, $enableSmileys = true)
{
	global $txt, $modSettings, $context;

	// Item codes - for <li> types.  Used with [*], [@], [o], etc.
	static $itemcode = array(
		'[*]' => '<li>',
		'[@]' => '<li type="disc">',
		'[+]' => '<li type="square">',
		'[x]' => '<li type="square">',
		'[#]' => '<li type="square">',
		'[o]' => '<li type="circle">',
		'[O]' => '<li type="circle">',
		'[0]' => '<li type="circle">'
	);

//	if (empty($modSettings['enableBBC']))
//		return $message;

	if (substr($message, 0, 1) == ' ')
		$message = '&nbsp;' . substr($message, 1);

	// Rip apart code tags.
	$parts = preg_split('~\[/?code\](<br />)?~', ' ' . strtr($message, array("\n" => '<br />')));

	// For each part....
	for ($i = 0, $n = count($parts); $i < $n; $i++)
	{
		// If we're outside a block... (0: outside, 1: inside, 2: outside, 3: inside, etc.)
		if ($i % 2 == 0)
		{
			// Close the Code block, unless this is the first block. (meaning it wasn't opened yet.)
			if ($i > 0)
				$parts[$i] = '</div>' . $parts[$i];

			// Find any [php] code tags.... CAPTURING the delimiter.
			$php_parts = preg_split('~(\[php\])(?:<br />)?|(\[/php\])~', $parts[$i], -1, PREG_SPLIT_DELIM_CAPTURE);

			for ($php_i = 0, $php_n = count($php_parts); $php_i < $php_n; $php_i++)
			{
				// Do PHP code ing. (this is a start tag, so everything until a [/php] should be highlighted.)
				if ($php_parts[$php_i] == '[php]')
				{
					// Get rid of the start tag.
					$php_parts[$php_i] = '';

					$php_string = '';
					while ($php_i < count($php_parts) && $php_parts[$php_i] != '[/php]')
					{
						$php_string .= $php_parts[$php_i];

						// This makes it easier; jut clear it out and let the implode do all the work.
						$php_parts[$php_i++] = '';
					}

					// Highlight the PHP code, and then remove the ?php and ? we added to do so.
					$php_parts[$php_i] = highlight_php_code(substr(trim($php_string), 0, 5) != '&lt;?' ? '&lt;?php' . $php_string . '?&gt;' : $php_string);
					if (substr(trim($php_string), 0, 5) != '&lt;?')
						$php_parts[$php_i] = preg_replace(array('~^(.+?)&lt;\?php~', '~\?&gt;((?:</font>)*)$~'), '$1', $php_parts[$php_i], 1);

					$did_php = true;
				}
				else
					$did_php = false;

				// Parse any URLs.... have to get rid of the @ problems some things cause... stupid email addresses.
				if (!empty($modSettings['autoLinkUrls']) && (strpos($php_parts[$php_i], '://') !== false || strpos($php_parts[$php_i], 'www.') !== false))
				{
					// Switch out quotes really quick because they can cause problems.
					$php_parts[$php_i] = strtr($php_parts[$php_i], array('&#039;' => '\'', '&nbsp;' => '\xA0', '&quot;' => '>">', '"' => '<"<', '&lt;' => '<lt<'));
					$php_parts[$php_i] = preg_replace(array('~(?<=[\s>\.(;\'"])((?:http|https|ftp|ftps)://[\w\-_@:|]+(?:\.[\w\-_]+)*(?::\d+)?(?:/[\w\-_\~%\.@,\?&;=#+:\']*|\([\w\-_\~%\.@,\?&;=#()+:\']*)*[/\w\-_\~%@\?;=#])~i', '~(?<=[\s>(\'])(www(?:\.[\w\-_]+)+(?::\d+)?(?:/[\w\-_\~%\.@,\?&;=#+:\']*|\([\w\-_\~%\.@,\?&;=#()+:\']*)*[/\w\-_\~%@\?;=#])~i'), array('[url]$1[/url]', '[url=http://$1]$1[/url]'), $php_parts[$php_i]);
					$php_parts[$php_i] = strtr($php_parts[$php_i], array('\'' => '&#039;', '\xA0' => '&nbsp;', '>">' => '&quot;', '<"<' => '"', '<lt<' => '&lt;'));
				}

				// Parse code.....
				parsecode($php_parts[$php_i]);

				// Parse smileys?
				if (!$enableSmileys || $did_php)
					continue;

				// This isn't code; change any tabs to spaces.
				$php_parts[$php_i] = strtr($php_parts[$php_i], array("\t" => '&nbsp;&nbsp;&nbsp;'));

				// Often requested but also very kldugey; break long words.
				if (!empty($modSettings['fixLongWords']))
				{
					// This is SADLY and INCREDIBLY browser dependent.
					if ($context['browser']['is_gecko'] || strpos($_SERVER['HTTP_USER_AGENT'], 'Konqueror') !== false)
						$breaker = '<span style="margin: 0 -0.5ex 0 0;"> </span>';
					// Opera...
					elseif ($context['browser']['is_opera'])
						$breaker = '<span style="margin: 0 -0.65ex 0 -1px;"> </span>';
					// Internet Explorer...
					else
						$breaker = '<span style="width: 0; margin: 0 -0.6ex 0 -1px;"> </span>';

					// The idea is, find words xx long, and then replace them with xx + space + more.
					$php_parts[$php_i] = preg_replace(
						'/(?<=[>;:\?\.\! \xA0\]()])(\w{' . $modSettings['fixLongWords'] . ',})/e',
						"preg_replace('/(.{" . $modSettings['fixLongWords'] . "})/', '\\\$1$breaker', '\$1')",
						$php_parts[$php_i]);
				}

				// Figure out smileys...
				parsesmileys($php_parts[$php_i]);

				// List items - warning they might disrupt your code...
				if (preg_match('~[\s,](list|li)[\s,]~', $modSettings['disabledBBC']) == 0)
					$php_parts[$php_i] = strtr($php_parts[$php_i], $itemcode);
				else
					$php_parts[$php_i] = str_replace(array_keys($itemcode), '<br />', $php_parts[$php_i]);
			}

			$parts[$i] = implode('', $php_parts);
		}
		// Add the Code: part.
		elseif ($i <= count($parts) - 1)
		{
			$php_parts = preg_split('~(&lt;\?php|\?&gt;)~', $parts[$i], -1, PREG_SPLIT_DELIM_CAPTURE);

			for ($php_i = 0, $php_n = count($php_parts); $php_i < $php_n; $php_i++)
			{
				// Do PHP code coloring?
				if ($php_parts[$php_i] != '&lt;?php')
					continue;

				$php_string = '';
				while ($php_i + 1 < count($php_parts) && $php_parts[$php_i] != '?&gt;')
				{
					$php_string .= $php_parts[$php_i];
					$php_parts[$php_i++] = '';
				}
				$php_parts[$php_i] = highlight_php_code($php_string . $php_parts[$php_i]);
			}

			// Fix the PHP code stuff...
			$parts[$i] = str_replace("<pre style=\"display: inline;\">\t</pre>", "\t", implode('', $php_parts));

			// Older browsers are annoying, aren't they?
			if ($context['browser']['is_ie4'] || $context['browser']['is_ie5'] || $context['browser']['is_ie5.5'])
				$parts[$i] = str_replace("\t", "<pre style=\"display: inline;\">\t</pre>", $parts[$i]);
			else
				$parts[$i] = str_replace("\t", "<span style=\"white-space: pre;\">\t</span>", $parts[$i]);

			$parts[$i] = '<div class="codeheader">' . $txt['smf238'] . ':</div><div class="code">' . $parts[$i];
		}
	}
	$message = substr(implode('', $parts), 1);

	// Fix things.
	$message = strtr($message, array('  ' => ' &nbsp;', "\r" => '', "\n" => '<br />'));

	return $message;
}

// Parses the code in message, using the normal syntax used by some many forums...
function parsecode(&$message)
{
	global $modSettings, $scripturl, $txt, $settings, $context;
	static $codefromcache = array(), $codetocache = array();

	// If it wasn't already set, set it.
	if (empty($codefromcache))
	{
		// If any tags are disabled then we won't add them.
		if (!empty($modSettings['disabledBBC']))
		{
			$disabled = array_flip(explode(',', $modSettings['disabledBBC']));

			foreach ($disabled as $tag => $dummy)
				$disabled[trim($tag)] = true;
		}

		$code_to_from = array(
			// A named email address. [email=me@some.place.com]me[/email]
			// Find an inside link. (named?) ie. [iurl]www.simplemachines.org[/iurl]
			'~\[iurl=([^\]]+?)\]([^\[\]]+?)\[/iurl\]~i' => isset($disabled['iurl']) ? (!isset($disabled['url']) ? '<a href="$1" target="_blank">$2</a>' : '$2 ($1)') : '<a href="$1">$2</a>',
			'~\[iurl=(.+?)\](.+?)\[/iurl\]~i' => isset($disabled['iurl']) ? (!isset($disabled['url']) ? '\'<a href="$1" target="_blank">\' . preg_replace(\'~(\[url.*?\]|\[/url\])~\', \'\', strtr(\'$2\', array(\'@\' => \'&#64;\'))) . \'</a>\'' : 'preg_replace(\'~(\[url.*?\]|\[/url\])~\', \'\', \'$2\') . \' ($1)\'') : '\'<a href="$1">\' . preg_replace(\'~(\[url.*?\]|\[/url\])~\', \'\', strtr(\'$2\', array(\'@\' => \'&#64;\'))) . \'</a>\'',
			'~\[iurl\](.+?)\[/iurl\]~i' => isset($disabled['iurl']) ? (!isset($disabled['url']) ? '<a href="$1" target="_blank">$1</a>' : '$1') : '<a href="$1">$1</a>',
			// FTP link.  Named...?  [ftp=upload.sourceforge.net]Sourceforge[/ftp]
			// Find a (named?) URL... [url]www.simplemachines.org[/url] or [url=www.simplemachines.org]SMF[/url]
			'~\[url=([^\]]+?)\]([^\]]*?\[url(?:=[^\]]+?)?\].+?\[/url\].*?)\[/url\]~i' => isset($disabled['url']) ? 'preg_replace(\'~(\[url.*?\]|\[/url\])~\', \'\', \'$2\') . \' ($1)\'' : '\'<a href="$1" target="_blank">\' . preg_replace(\'~(\[url.*?\]|\[/url\])~\', \'\', strtr(\'$2\', array(\'@\' => \'&#64;\'))) . \'</a>\'',
			'~\[url=(.+?)\](.+?)\[/url\]~i' => isset($disabled['url']) ? '\'$2 ($1)\'' : '\'<a href="$1" target="_blank">\' . strtr(\'$2\', array(\'@\' => \'&#64;\')) . \'</a>\'',
			'~\[url\](?:<br />)*(.+?)(?:<br />)*\[/url\]~i' => isset($disabled['url']) ? '$1' : '<a href="$1" target="_blank">$1</a>',
			// Bold, italics, underline, strikeout.
			'~\[b\](.+?)\[/b\]~i' => isset($disabled['b']) ? '$1' : '<b>$1</b>',
			'~\[i\](.+?)\[/i\]~i' => isset($disabled['i']) ? '$1' : '<i>$1</i>',
			'~\[u\](.+?)\[/u\]~i' => isset($disabled['u']) ? '$1' : '<span style="text-decoration: underline;">$1</span>',
			'~\[s\](.+?)\[/s\]~i' => isset($disabled['s']) ? '$1' : '<del>$1</del>',
			// A quote.  May or may not specify an author and/or link and date.
			'~\[quote(?: author)?=&quot;(.{1,80}?)&quot;\](?:<br />)?~i' => isset($disabled['quote']) ? '<div>' : '<div class="quoteheader">' . $txt['smf239'] . ': $1</div><div class="quote">',
			'~\[quote author=(.{1,80}?) link=(?:board=\d+;)?((?:topic|threadid)=[\dmsg#\./]{1,40}(?:;start=[\dmsg#\./]{1,40})?) date=(\d+)\](?:<br />)?~i' => isset($disabled['quote']) ? '\'<div>\'' : '\'<div class="quoteheader"><a href="' . $scripturl . '?$2">' . $txt['smf239'] . ': $1 ' . $txt[176] . ' \' . timeformat(\'$3\') . \'</a></div><div class="quote">\'',
			'~\[quote author=(.{1,80}?)\](?:<br />)?~i' => isset($disabled['quote']) ? '<div>' : '<div class="quoteheader">' . $txt['smf239'] . ': $1</div><div class="quote">',
			'~\[quote\](?:<br />)?~i' => isset($disabled['quote']) ? '<div>' : '<div class="quoteheader">' . $txt['smf240'] . '</div><div class="quote">',
			'~\[/quote\](?:<br />)?~i' => isset($disabled['quote']) ? '</div>' : '</div>',
			// An image.  Width and height can be are optional.
			'~\[img(\s+width=([\d]+))?(\s+height=([\d]+))?\s*\](?:<br />)*(.+?)(?:<br />)*\[/img\]~i' => isset($disabled['img']) ? '\'$5\'' : '\'<img src="$5" alt=""\' . (\'$2\' != \'\' ? \' width="$2"\' : \'\') . (\'$4\' != \'\' ? \' height="$4"\' : \'\') . \' border="0" />\'',
			// Size the font.  [size=large]HELLO![/size]
			'~\[size=([\d]{1,2}p[xt]|(?:x-)?small(?:er)?|(?:x-)?large[r]?)\](.+?)\[/size\]~i' => isset($disabled['size']) ? '$2' : '<span style="font-size: $1;">$2</span>',
			'~\[size=([\d])\](.+?)\[/size\]~i' => isset($disabled['size']) ? '$2' : '<font size="$1">$2</font>',
			// Performat/justify text.  [center]Justifying is good.[/center]
			'~\[pre\](.+?)\[/pre\]~i' => isset($disabled['pre']) ? '$1' : '<pre>$1</pre>',
			'~\[left\](.+?)\[/left\]~i' => isset($disabled['left']) ? '$1' : '<div align="left">$1</div>',
			'~\[right\](.+?)\[/right\]~i' => isset($disabled['right']) ? '$1' : '<div align="right">$1</div>',
			'~\[center\](.+?)\[/center\]~i' => isset($disabled['center']) ? '$1' : '<div align="center">$1</div>',
			// Teletyped text.  Monospace, in other words.
			'~\[tt\](.+?)\[/tt\]~i' => isset($disabled['tt']) ? '$1' : '<tt>$1</tt>',
			// Subscript and superscript.  6[sup]2[/sup] = 36.
			'~\[sub\](.+?)\[/sub\]~i' => isset($disabled['sub']) ? '$1' : '<sub>$1</sub>',
			'~\[sup\](.+?)\[/sup\]~i' => isset($disabled['sup']) ? '$1' : '<sup>$1</sup>',
			// An email address. [email]me@some.place.com[/email]
			'~\[email\](?:<br />)*(.+?)(?:<br />)*\[/email\]~i' => isset($disabled['email']) ? '$1' : '<a href="mailto:$1">$1</a>',
			// Specify a specific font.  [font=Comic Sans]Blah![/font]
			'~\[font=([\w,\-\s]+?)\](.+?)\[/font\]~i' => isset($disabled['font']) ? '$2' : '<span style="font-family: $1;">$2</span>',
			// Colors.... [red]See?[/red]
			'~\[color=(#[\da-fA-F]{3}|#[\da-fA-F]{6}|[\w]{1,12})\](.*?)\[/color\]~i' => isset($disabled['color']) ? '$2' : '<span style="color: $1;">$2</span>',
			'~\[(black|white|red|green|blue)\](.+?)\[/\1\]~i' => isset($disabled['color']) ? '$2' : '<span style="color: $1;">$2</span>',
			'~\[(chr|k)issy\](.+?)\[/\1issy\]~i' => isset($disabled['color']) ? '$2' : '<span style="color: #CC0099;">$2 :-*</span>',
			// Lists... [list][*]First, ...[o]Second![li]THIRD!!![/li][/list]
			'~\[list\](?:<br />)?~i' => isset($disabled['list']) || isset($disabled['li']) ? '' : '<ul style="margin-top: 0; margin-bottom: 0;">',
			'~\[/list\](?:<br />)?~i' => isset($disabled['list']) || isset($disabled['li']) ? '' : '</ul>',
			'~(?:<br />|&nbsp;|\s)*\[li\](.+?)\[/li\](?:<br />|&nbsp;|\s)*~i' => isset($disabled['list']) || isset($disabled['li']) ? '<br />$1<br />' : '<li>$1</li>',
			// Horizontal rule. [hr] => ------------------
			'~\[hr(?:\s*/)?\]~i' => isset($disabled['hr']) ? '' : '<hr />',
			// A break.  [br] or [br /]. (it makes no sense to disable this one :P.)
			'~\[br(?:\s*/)?\]~i' => '<br />',
			// Right-to-left and left-to-right strings.
			'~\[(ltr|rtl)\](.+?)\[/\1\]~i' => isset($disabled['ltr']) || isset($disabled['rtl']) ? '$2' : '<div dir="$1">$2</div>',
			// Acronyms and abbreviations... [acronym=Bulletin Board Code]BBC[/acronym]
			'~\[abbr=((?:&quot;)?)(.+?)\\1\](.+?)\[/abbr\]~i' => isset($disabled['abbr']) ? '$3 ($2)' : '<abbr title="$2">$3</abbr>',
			'~\[acronym=((?:&quot;)?)(.+?)\\1\](.+?)\[/acronym\]~i' => isset($disabled['acronym']) ? '$3 ($2)' : '<acronym title="$2">$3</acronym>',
			// An email address they just typed in.  Don't match if there's already a mailto: or = before it.
			'~(?<=[\?\s\xA0[\]()*\\\;>]|^)([\w\-\.]{1,80}@[\w\-]+\.[\w\-\.]+[\w\-])(?=[?,\s\xA0\[\]()*\\\]|$|<br />|&nbsp;|&gt;|&lt;|&quot;|&#039;|\.(?:\.|&nbsp;|\s|$|<br />))~i' => '<a href="mailto:$1">$1</a>',
			'~(?<=<br />)([\w\-\.]{1,80}@[\w\-]+\.[\w\-\.]+[\w\-])(?=[?\.,\s\xA0\[\]()*\\\]|$|<br />|&nbsp;|&gt;|&lt;|&quot;|&#039;)~i' => '<a href="mailto:$1">$1</a>',
			// This last one fixes spaces at the beginning of lines.
			'~<br /> ~' => '<br />&nbsp;',
			// Match a table... hopefully with everything in the right place.
			'~\[table\](?:\s|\xA0|<br />|&nbsp;)*((?:\[tr\](?:\s|\xA0|<br />|&nbsp;)*\[td\]).*?(?:(?:\s|\xA0|<br />|&nbsp;)*\[/td\]\[/tr\])*)(?:\s|\xA0|<br />|&nbsp;)*\[/table\](?:\s|\xA0|<br />|&nbsp;)?~i' => '<table>$1</table>',
		
		);

		$codefromcache = array_keys($code_to_from);
		$codetocache = array_values($code_to_from);

		// GLOWING or /shadowed/ text.
		$codefromcache[] = '~\[glow=([#\w]{3,12}),([012]\d{0,2})(,[^]]+)?\](.+?)\[/glow\]~i';
		$codetocache[] = isset($disabled['glow']) ? '$4' : '<table border="0" cellpadding="0" cellspacing="0" style="display: inline; vertical-align: middle; font: inherit;"><tr><td style="filter: Glow(color=$1, strength=$2); font: inherit;">$4</td></tr></table> ';
		$codefromcache[] = '~\[shadow=([#\w]{3,12}),(left|right|top|bottom|[0123]\d{0,2})\](.+?)\[/shadow\]~i';
		$codetocache[] = isset($disabled['shadow']) ? 'strtr(\'$3\', array(\'\\"\' => \'"\')' : '\'<table border="0" cellpadding="0" cellspacing="0" style="display: inline; vertical-align: middle; font: inherit;"><tr><td style="filter: Shadow(color=$1, direction=\' . (isset($shadow_directions[\'$2\']) ? $shadow_directions[\'$2\'] : (int) \'$2\') . \'); font: inherit;">\' . strtr(\'$3\', array(\'\\"\' => \'"\')) . \'</td></tr></table> \'';

		// Moving text... [move]stuff[/move]
		$codefromcache[] = '~\[move\](.+?)\[/move\]~i';
		$codetocache[] = isset($disabled['move']) ? '$1' : '<marquee>$1</marquee>';

		// Handle flash.
		$codefromcache[] = '~\[flash=(\d+),(\d+)\](?:<br />)*(.+?)(?:<br />)*\[/flash\]~i';
		if (empty($modSettings['enableEmbeddedFlash']) || $disabled['flash'])
			$codetocache[] = isset($disabled['url']) ? '$3' : '<a href="$3" target="_new">$3</a>';
		else
			$codetocache[] = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="$1" height="$2"><param name="movie" value="$3" /><param name="play" value="true" /><param name="loop" value="true" /><param name="quality" value="high" /><param name="AllowScriptAccess" value="never" /><embed src="$3" width="$1" height="$2" play="true" loop="true" quality="high" AllowScriptAccess="never"></embed></object>';
	}

	// Nothing to parse... ?
	if (!strstr($message, '[') && !strstr($message, '://') && !strstr($message, '@') && !strstr($message, '/me') && !strstr($message, '&lt;'))
		return;

	// Replace <a href="something">somewhere</a> with HTML.
	if (!empty($modSettings['enablePostHTML']) && strstr($message, '&lt;'))
	{
		$message = preg_replace('~&lt;a\s+href=(?:&quot;)?(?:\[url\])?((?:http://|ftp:/\|https://|ftps://|mailto:).+?)(?:\[/url\])?(?:&quot;)?&gt;(.+?)&lt;/a&gt;~ie', '\'<a href="$1">\' . preg_replace(\'~(\[url.*?\]|\[/url\])~\', \'\', \'$2\') . \'</a>\'', $message);

		// Do <img ... /> - with security... action= -> action-.
		preg_match_all('~&lt;img\s+src=(?:&quot;)?(?:\[url\])?((?:http://|ftp://|https://|ftps://).+?)(?:\[/url\])?(?:&quot;)?(?:\s+alt=(?:&quot;)?(.*?)(?:&quot;)?)?(?:\s?/)?&gt;~i', $message, $matches, PREG_PATTERN_ORDER);
		if (!empty($matches[0]))
		{
			$replaces = array();
			foreach ($matches[1] as $match => $imgtag)
			{
				// No alt?
				if (!isset($matches[2][$match]))
					$matches[2][$match] = '';

				// Remove action= from the URL - no funny business, now.
				if ($imgtag != preg_replace('~action(=|%3d)(?!dlattach)~i', 'action-', $imgtag))
					$replaces[$matches[0][$match]] = '<img src="' . preg_replace('~action(=|%3d)(?!dlattach)~i', 'action-', $imgtag) . '" alt="' . $matches[2][$match] . '" border="0" />';

				// Check if the image is larger than allowed.
				if (!empty($modSettings['maxwidth']) && !empty($modSettings['maxheight']))
				{
					list ($width, $height) = url_image_size($imgtag);

					if ($width > $modSettings['maxwidth'] || $height > $modSettings['maxheight'])
					{
						if ($width > $modSettings['maxwidth'] && !empty($modSettings['maxwidth']))
						{
							$height = floor($modSettings['maxwidth'] / $width * $height);
							$width = $modSettings['maxwidth'];
							if ($height > $modSettings['maxheight'] && !empty($modSettings['maxheight']))
							{
								$width = floor($modSettings['maxheight'] / $height * $width);
								$height = $modSettings['maxheight'];
							}
						}
						else
						{
							if ($height > $modSettings['maxheight'] && !empty($modSettings['maxheight']))
							{
								$width = floor($modSettings['maxheight'] / $height * $width);
								$height = $modSettings['maxheight'];
							}
						}
					}

					// Set the new image tag.
					$replaces[$matches[0][$match]] = '<img src="' . preg_replace('~action(=|%3d)(?!dlattach)~i', 'action-', $imgtag) . '" width="' . $width . '" height="' . $height . '" alt="' . $matches[2][$match] . '" border="0" />';
				}
				elseif (strpos($matches[0][$match], '[url]') !== false || substr($matches[0][$match], 0, 4) == '&lt;')
					$replaces[$matches[0][$match]] = '<img src="' . preg_replace('~action(=|%3d)(?!dlattach)~i', 'action-', $imgtag) . '" alt="' . $matches[2][$match] . '" border="0" />';
			}

			$message = strtr($message, $replaces);
		}
	}

	// Do the code if necessary.
	if (strstr($message, '[') || strstr($message, '://') || strstr($message, '@') || strstr($message, '/m'))
	{
		$shadow_directions = array('left' => '270', 'right' => '90', 'top' => '0', 'bottom' => '180');


		$message = preg_replace($codefromcache, $codetocache, $message);

		// Tables need to be done twice or they won't work properly.
		if (strpos($message, '<table>') !== false)
		{
			for ($i = 0; $i < 2; $i++)
				$message = preg_replace(
					array(
						'~((?:<table>|</tr>|\[/tr\])(?:\s|\xA0|<br />|&nbsp;)*(?:<tr>|\[tr\])|</td>|\[/td\])(?:\s|\xA0|<br />|&nbsp;)*\[td\](?:\s|\xA0|<br />|&nbsp;)*(.*?)(?:\s|\xA0|<br />|&nbsp;)*\[/td\](?:\s|\xA0|<br />|&nbsp;)*((?:</tr>|\[/tr\])(?:\s|\xA0|<br />|&nbsp;)*(?:<tr>|\[tr\]|</table>)|<td(?: valign="top")?>|\[td\])~i',
						'~(<table>|</tr>|\[/tr\])(?:\s|\xA0|<br />|&nbsp;)*\[tr\](?:\s|\xA0|<br />|&nbsp;)*(.*?)(?:\s|\xA0|<br />|&nbsp;)*\[/tr\](?:\s|\xA0|<br />|&nbsp;)*(</table>|<tr>|\[tr\])~i'
					),
					array(
						'$1<td valign="top" class="notes">$2</td>$3',
						'$1<tr>$2</tr>$3'
					), $message
				);
		}
	}

	// Enable Basic HTML?
	if (!empty($modSettings['enablePostHTML']) && strstr($message, '&lt;'))
	{
		// b, u, i, s, pre... basic tags.
		$closable_tags = array('b', 'u', 'i', 's', 'pre', 'blockquote');
		foreach ($closable_tags as $tag)
		{
			$opens = substr_count($message, '&lt;' . $tag . '&gt;');
			$closes = substr_count($message, '&lt;/' . $tag . '&gt;');
			$message = str_replace(array('&lt;' . $tag . '&gt;', '&lt;/' . $tag . '&gt;'), array('<' . $tag . '>', '</' . $tag . '>'), $message);

			if ($closes < $opens)
				$message .= str_repeat('</' . $tag . '>', $opens - $closes);
		}

		// <br /> should be empty.
		$empty_tags = array('br');
		foreach ($empty_tags as $tag)
			$message = str_replace(array('&lt;' . $tag . '&gt;', '&lt;' . $tag . '/&gt;', '&lt;' . $tag . ' /&gt;'), '<' . $tag . ' />', $message);
	}
}

// Parse smileys in the passed message.
function parsesmileys(&$message)
{
	global $modSettings, $db_prefix, $txt, $user_info;
	static $smileyfromcache = array(), $smileytocache = array();
	$smileys_url = "http://cove.fantasyworld.nl/forum/Smileys/default/";
	
	// If the smiley array hasn't been set, do it now.
	if (empty($smileyfromcache) && $user_info['smiley_set'] != 'none')
	{
		// Use the default smileys if it is disabled. (better for "portability" of smileys.)
		if (empty($modSettings['smiley_enable']))
		{
			$smileysfrom = array('>:D', ':D', '::)', '>:(', ':)', ';)', ';D', ':(', ':o', '8)', ':P', '???', ':-[', ':-X', ':-*', ':\'(', ':-\\', '^-^', 'O0', 'C:-)', '0:)');
			$smileysto = array('evil.gif', 'cheesy.gif', 'rolleyes.gif', 'angry.gif', 'smiley.gif', 'wink.gif', 'grin.gif', 'sad.gif', 'shocked.gif', 'cool.gif', 'tongue.gif', 'huh.gif', 'embarassed.gif', 'lipsrsealed.gif', 'kiss.gif', 'cry.gif', 'undecided.gif', 'azn.gif', 'afro.gif', 'police.gif', 'angel.gif');
			$smileysdescs = array('', $txt[289], $txt[450], $txt[288], $txt[287], $txt[292], $txt[293], $txt[291], $txt[294], $txt[295], $txt[451], $txt[296], $txt[526], $txt[527], $txt[529], $txt[530], $txt[528], '', '', '', '');
		}
		else
		{
			// Load the smileys in reverse order by length so they don't get parsed wrong.
			$result = db_query("
				SELECT code, filename, description
				FROM {$db_prefix}smileys
				ORDER BY LENGTH(code) DESC", __FILE__, __LINE__);
			$smileysfrom = array();
			$smileysto = array();
			$smileysdescs = array();
			while ($row = mysql_fetch_assoc($result))
			{
				$smileysfrom[] = $row['code'];
				$smileysto[] = $row['filename'];
				$smileysdescs[] = $row['description'];
			}
			mysql_free_result($result);
		}

		// This smiley regex makes sure it doesn't parse smileys within code tags (so [url=mailto:David@bla.com] doesn't parse the :D smiley)
		for ($i = 0, $n = count($smileysfrom); $i < $n; $i++)
		{
			$smileyfromcache[] = '/(?<=[>:\?\.\s\xA0[\]()*\\\;])(' . preg_quote($smileysfrom[$i], '/') . '|' . preg_quote(htmlspecialchars($smileysfrom[$i], ENT_QUOTES), '/') . ')(?=[^[:alpha:]0-9]|$)/';
			$smileytocache[] = '<img src="' . $smileys_url . '/' . $user_info['smiley_set'] . '/' . $smileysto[$i] . '" alt="' . $smileysdescs[$i] . '" border="0" />';
		}
	}

	// Replace away! (do it twice just in case.)
	$message = preg_replace($smileyfromcache, $smileytocache, $message);
}

// Highlight any code...
function highlight_php_code($code)
{
	global $context;

	// Remove special characters.
	$code = un_htmlspecialchars(strtr($code, array('<br />' => "\n", "\t" => 'SMF_TAB();')));

	$oldlevel = error_reporting(0);

	// It's easier in 4.2.x+.
	if ((float) PHP_VERSION < 4.2)
	{
		ob_start();
		@highlight_string($code);
		$buffer = str_replace("\n", '', ob_get_contents());
		ob_end_clean();
	}
	else
		$buffer = str_replace("\n", '', @highlight_string($code, true));

	error_reporting($oldlevel);

	// Yes, I know this is kludging it, but this is the best way to preserve tabs from PHP :P.
	$buffer = preg_replace('~SMF_TAB(</font><font color="[^"]*?">)?\(\);~', "<pre style=\"display: inline;\">\t</pre>", $buffer);

	return strtr($buffer, array('\'' => '&#039;', '<code>' => '', '</code>' => ''));
}

// Put this user in the online log.
function writeLog($force = false)
{
	global $db_prefix, $ID_MEMBER, $user_info, $sc, $modSettings;

	// Don't mark them as online more than every so often.
	if (empty($_SESSION['log_time']) || $_SESSION['log_time'] < (time() - 8) || $force)
		$_SESSION['log_time'] = time();
	else
		return;

	if (!empty($modSettings['who_enabled']))
	{
		$serialized = $_GET + array('USER_AGENT' => $_SERVER['HTTP_USER_AGENT']);
		unset($serialized['sesc']);
		$serialized = addslashes(serialize($serialized));
	}
	else
		$serialized = '';

	// Guests use 0, members use their session ID.
	$session_id = $user_info['is_guest'] ? 'ip' . $user_info['ip'] : session_id();

	db_query("
		DELETE FROM {$db_prefix}log_online
		WHERE logTime < NOW() - INTERVAL " . ($modSettings['lastActive'] * 60) . " SECOND
			OR session = '$session_id'" . (empty($ID_MEMBER) ? '' : " OR ID_MEMBER = $ID_MEMBER"), __FILE__, __LINE__);
	db_query("
		INSERT IGNORE INTO {$db_prefix}log_online
			(session, ID_MEMBER, ip, url)
		VALUES ('$session_id', $ID_MEMBER, IFNULL(INET_ATON('$user_info[ip]'), 0), '$serialized')", __FILE__, __LINE__);

	// Well, they are online now.
	if (empty($_SESSION['timeOnlineUpdated']))
		$_SESSION['timeOnlineUpdated'] = time();

	// Set their login time, if not already done within the last minute.
	if (!empty($user_info['last_login']) && $user_info['last_login'] < time() - 60)
	{
		// Don't count longer than 15 minutes.
		if (time() - $_SESSION['timeOnlineUpdated'] > 60 * 15)
			$_SESSION['timeOnlineUpdated'] = time();

		updateMemberData($ID_MEMBER, array('lastLogin' => time(), 'memberIP' => '\'' . $user_info['ip'] . '\'', 'totalTimeLoggedIn' => 'totalTimeLoggedIn + ' . (time() - $_SESSION['timeOnlineUpdated'])));

		$user_info['total_time_logged_in'] += time() - $_SESSION['timeOnlineUpdated'];
		$_SESSION['timeOnlineUpdated'] = time();
	}
}

/* Make sure the browser doesn't come back and repost the form data.
	Should be used whenever anything is posted. */
function redirectexit($setLocation = '', $add = true, $refresh = false)
{
	global $scripturl, $modSettings;

	// Add the scripturl on if needed.
	if (WIRELESS)
	{
		if ($add)
			$setLocation = $scripturl . '?' . $setLocation;

		$char = strpos($setLocation, '?') === false ? '?' : ';';

		if (strpos($setLocation, '#') ==! false)
			$setLocation = strtr($setLocation, array('#' => $char . WIRELESS_PROTOCOL . '#'));
		else
			$setLocation .= $char . WIRELESS_PROTOCOL;
	}
	elseif ($add)
		$setLocation = $scripturl . ($setLocation != '' ? '?' . $setLocation : '');

	// Put the session ID in.
	if (defined('SID') && SID != '')
		$setLocation = preg_replace('/' . preg_quote($scripturl, '/') . '(?!\?' . preg_quote(SID, '/') . ')(\?)?/', $scripturl . '?' . SID . '&', $setLocation);

	// Send the header only.
	if (empty($modSettings['redirectMetaRefresh']))
	{
		// We send a Refresh header only in special cases because Location looks better. (and is quicker...)
		if ($refresh)
			header('Refresh: 0; URL=' . strtr($setLocation, array(' ' => '%20', ';' => '%3b')));
		else
			header('Location: ' . str_replace(' ', '%20', $setLocation));
	}
	else
	{
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<meta http-equiv="refresh" content="0;URL=', strtr($setLocation, array(' ' => '%20', ';' => '%3b')), '" />
	</head>
	<body style="font-family: Georgia, serif; font-size: 1.3em; margin-top: 20%; text-align: center; background-color: white;">
		<a href="', str_replace(' ', '%20', $setLocation), '" style="color: black;">', $setLocation, '</a>
	</body>
</html>';
	}

	obExit(false);
}

// Ends execution.  Takes care of template loading and remembering the previous URL.
function obExit($do_header = null, $do_footer = null, $from_index = false)
{
	global $context, $modSettings;
	static $header_done = false;

	// Clear out the stat cache.
	trackStats();

	if ($do_header === null)
		$do_header = !$header_done;
	if ($do_footer === null)
		$do_footer = $do_header;

	// Has the template/header been done yet?
	if ($do_header)
	{
		// Start up the session URL fixer.
		ob_start('ob_sessrewrite');

		// Display the screen in the logical order.
		template_header();
		$header_done = true;
	}
	if ($do_footer)
	{
		// Just show the footer, then.
		loadSubTemplate(isset($context['sub_template']) ? $context['sub_template'] : 'main');
		template_footer();

		// (since this is just debugging... it's okay that it's after </html>.)
		db_debug_junk();
	}

	// Remember this URL incase someone doesn't like sending HTTP_REFERER.
	$_SESSION['old_url'] = $_SERVER['REQUEST_URI'];

	// For session check verfication.... don't switch browsers...
	$_SESSION['USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];

	// Don't exit if we're coming from index.php; that will pass through normally.
	if (!$from_index || WIRELESS)
		exit;
}

// Set up the administration sections.
function adminIndex($area)
{
	global $txt, $context, $scripturl, $sc, $modSettings, $user_info, $settings;

	// Load the language and templates....
	loadLanguage('Admin');
	loadTemplate('Admin');

	// Admin area 'Forum Controls.'
	$context['admin_areas']['forum'] = array(
		'title' => $txt[427],
		'areas' => array(
			'index' => '<a href="' . $scripturl . '?action=admin">' . $txt[208] . '</a>',
			'credits' => '<a href="' . $scripturl . '?action=admin;credits">' . $txt['support_credits_title'] . '</a>',
		)
	);

	if (allowedTo('edit_news'))
		$context['admin_areas']['forum']['areas']['edit_news'] = '<a href="' . $scripturl . '?action=editnews">' . $txt[7] . '</a>';

	if (allowedTo('manage_boards'))
		$context['admin_areas']['forum']['areas']['manage_boards'] =  '<a href="' . $scripturl . '?action=manageboards">' . $txt[4] . '</a>';

	if (allowedTo('admin_forum'))
		$context['admin_areas']['forum']['areas']['manage_packages'] =  '<a href="' . $scripturl . '?action=packages">' . $txt['package1'] . '</a>';

	if (allowedTo('manage_attachments'))
		$context['admin_areas']['forum']['areas']['manage_attachments'] = '<a href="' . $scripturl . '?action=manageattachments">' . $txt['smf201'] . '</a>';

	// Admin area 'Forum Configuration'.
	if (allowedTo(array('manage_smileys', 'admin_forum', 'moderate_forum')))
	{
		$context['admin_areas']['config'] = array(
			'title' => $txt[428],
			'areas' => array()
		);

		if (allowedTo('admin_forum'))
		{
			$context['admin_areas']['config']['areas']['edit_mods_settings'] = '<a href="' . $scripturl . '?action=modifyModSettings">' . $txt['modSettings_title'] . '</a>';
			$context['admin_areas']['config']['areas']['edit_settings'] = '<a href="' . $scripturl . '?action=modsettings;sesc=' . $sc . '">' . $txt[222] . '</a>';
			$context['admin_areas']['config']['areas']['edit_theme_settings'] = '<a href="' . $scripturl . '?action=theme;sa=settings;id=' . $settings['theme_id'] . ';sesc=' . $sc . '">' . $txt['theme_current_settings'] . '</a>';
			$context['admin_areas']['config']['areas']['manage_themes'] = '<a href="' . $scripturl . '?action=theme;sa=admin;sesc=' . $sc . '">' . $txt['theme_admin'] . '</a>';
		}

		if (allowedTo('manage_smileys'))
			$context['admin_areas']['config']['areas']['manage_smileys'] = '<a href="' . $scripturl . '?action=smileys">' . $txt['smileys_manage'] . '</a>';

		if (allowedTo('moderate_forum'))
		{
			$context['admin_areas']['config']['areas']['edit_censored'] = '<a href="' . $scripturl . '?action=setcensor">' . $txt[135] . '</a>';
			$context['admin_areas']['config']['areas']['edit_agreement'] = '<a href="' . $scripturl . '?action=editagreement">' . $txt['smf11'] . '</a>';
		}
	}

	// Admin area 'Member Controls.'
	if (allowedTo(array('moderate_forum', 'manage_membergroups', 'manage_bans', 'send_mail', 'manage_permissions')))
	{
		$context['admin_areas']['members'] = array(
			'title' => $txt[426],
			'areas' => array()
		);

		if (allowedTo('manage_membergroups'))
			$context['admin_areas']['members']['areas']['edit_groups'] = '<a href="' . $scripturl . '?action=membergroups">' . $txt[8] . '</a>';

		if (allowedTo('manage_permissions'))
			$context['admin_areas']['members']['areas']['edit_permissions'] = '<a href="' . $scripturl . '?action=permissions">' . $txt['edit_permissions'] . '</a>';

		if (allowedTo('moderate_forum'))
		{
			$context['admin_areas']['members']['areas']['registration_center'] = '<a href="' . $scripturl . '?action=regcenter">' . (!empty($modSettings['registration_method']) && $modSettings['registration_method'] != 3 ? $txt['registration_center'] : $txt['registration_member']) . '</a>';
			$context['admin_areas']['members']['areas']['view_members'] = '<a href="' . $scripturl . '?action=viewmembers">' . $txt[5] . '</a>';
			$context['admin_areas']['members']['areas']['edit_reserved_names'] = '<a href="' . $scripturl . '?action=setreserve">' . $txt[207] . '</a>';
		}

		if (allowedTo('send_mail'))
			$context['admin_areas']['members']['areas']['email_members'] = '<a href="' . $scripturl . '?action=mailing">' . $txt[6] . '</a>';

		if (allowedTo('manage_bans'))
			$context['admin_areas']['members']['areas']['ban_members'] = '<a href="' . $scripturl . '?action=ban">' . $txt['ban_title'] . '</a>';
	}

	// Admin area 'Maintenance Controls.'
	if (allowedTo('admin_forum'))
	{
		$context['admin_areas']['maintenance'] = array(
			'title' => $txt[501],
			'areas' => array(
				'maintain_forum' => '<a href="' . $scripturl . '?action=maintain">' . $txt['maintain_title'] . '</a>',
				'view_errors' => '<a href="' . $scripturl . '?action=viewErrorLog">' . $txt['errlog1'] . '</a>'
			)
		);

		if (!empty($modSettings['modlog_enabled']))
			$context['admin_areas']['maintenance']['areas']['view_moderation_log'] = '<a href="' . $scripturl . '?action=modlog">' . $txt['modlog_view'] . '</a>';
	}

	// Make sure the administrator has a valid session...
	validateSession();

	// Figure out which one we're in now...
	foreach ($context['admin_areas'] as $id => $section)
		if (isset($section[$area]))
			$context['admin_section'] = $id;
	$context['admin_area'] = $area;

	// obExit will know what to do!
	$context['template_layers'][] = 'admin';
}

// Usage: logAction('remove', array('starter' => $ID_MEMBER_STARTED));
function logAction($action, $extra = array())
{
	global $db_prefix, $ID_MEMBER, $modSettings, $user_info;

	if (!empty($modSettings['modlog_enabled']))
	{
		db_query("
			INSERT INTO {$db_prefix}log_actions
				(logTime, ID_MEMBER, IP, action, extra)
			VALUES (" . time() . ", $ID_MEMBER, '$user_info[ip]', '$action',
				'" . addslashes(serialize($extra)) . "')", __FILE__, __LINE__);

		return db_insert_id();
	}

	return false;
}

// Track Statistics.
function trackStats($stats = array())
{
	global $db_prefix, $modSettings;
	static $cache_stats = array();

	if (empty($modSettings['trackStats']))
		return false;
	if (!empty($stats))
		return $cache_stats = array_merge($cache_stats, $stats);
	elseif (empty($cache_stats))
		return false;

	$setStringUpdate = '';
	foreach ($cache_stats as $field => $change)
	{
		$setStringUpdate .= '
			' . $field . ' = ' . ($change === '+' ? $field . ' + 1' : $change) . ',';

		if ($change === '+')
			$cache_stats[$field] = 1;
	}

	$date = strftime('%Y%m%d', forum_time(false));
	db_query("
		UPDATE {$db_prefix}log_activity
		SET" . substr($setStringUpdate, 0, -1) . "
		WHERE date = $date
		LIMIT 1", __FILE__, __LINE__);
	if (db_affected_rows() == 0)
	{
		db_query("
			INSERT IGNORE INTO {$db_prefix}log_activity
				(date, " . implode(', ', array_keys($cache_stats)) . ")
			VALUES ($date, " . implode(', ', $cache_stats) . ')', __FILE__, __LINE__);
	}

	return true;
}

// Make sure the user isn't posting over and over again.
function spamProtection($error_type)
{
	global $modSettings, $txt, $db_prefix, $user_info;

	// Delete old entries... if you can moderate this board or this is login, override spamWaitTime with 2.
	if ($error_type == 'spam' && !allowedTo('moderate_board'))
		db_query("
			DELETE FROM {$db_prefix}log_floodcontrol
			WHERE logTime < " . (time() - $modSettings['spamWaitTime']), __FILE__, __LINE__);
	else
		db_query("
			DELETE FROM {$db_prefix}log_floodcontrol
			WHERE (logTime < " . (time() - 2) . " AND ip = '$user_info[ip]')
				OR logTime < " . (time() - $modSettings['spamWaitTime']), __FILE__, __LINE__);

	// Add a new entry, deleting the old if necessary.
	db_query("
		REPLACE INTO {$db_prefix}log_floodcontrol
			(ip, logTime)
		VALUES ('$user_info[ip]', " . time() . ")", __FILE__, __LINE__);
	// If affected is 0 or 2, it was there already.
	if (db_affected_rows() != 1)
	{
		// Spammer!  You only have to wait a *few* seconds!
		fatal_lang_error($error_type . 'WaitTime_broken', false, array($modSettings['spamWaitTime']));
		return true;
	}

	// They haven't posted within the limit.
	return false;
}

// Get the size of a specified image with better error handling.
function url_image_size($url)
{
	// Get the host to pester...
	preg_match('~^\w+://(.+?)/(.*)$~', $url, $match);

	// Can't figure it out, just try the image size.
	if ($url == '' || $url == 'http://' || $url == 'https://')
		return false;
	elseif (!isset($match[1]))
		return @getimagesize($url);

	// Try to connect to the server... give it one full second.
	$temp = 0;
	$fp = @fsockopen($match[1], 80, $temp, $temp, 1);

	// Successful?  Continue...
	if ($fp != false)
	{
		// Send the HEAD request.
		fwrite($fp, 'HEAD /' . $match[2] . ' HTTP/1.1' . "\r\n" . 'Host: ' . $match[1] . "\r\n\r\n");
		// Read in the HTTP/1.1 or whatever.
		$test = substr(fgets($fp, 11), -1);
		fclose($fp);

		// See if it returned a 404/403 or something.
		if ($test < 4)
			return @getimagesize($url);
	}

	// Didn't work.
	return false;
}

function determineTopicClass(&$topic_context)
{
	// Set topic class depending on locked status and number of replies.
	if ($topic_context['is_very_hot'])
		$topic_context['class'] = 'veryhot';
	elseif ($topic_context['is_hot'])
		$topic_context['class'] = 'hot';
	else
		$topic_context['class'] = 'normal';

	$topic_context['class'] .= $topic_context['is_poll'] ? '_poll' : '_post';

	if ($topic_context['is_locked'])
		$topic_context['class'] .= '_locked';

	if ($topic_context['is_sticky'])
		$topic_context['class'] .= '_sticky';

	// This is so old themes will still work.
	$topic_context['extended_class'] = &$topic_context['class'];
}

// Sets up the basic theme context stuff.
function setupThemeContext()
{
	global $modSettings, $user_info, $scripturl, $context, $settings, $options, $txt, $maintenance;

	// Get some news...
	$context['news_lines'] = explode("\n", str_replace("\r", '', trim(addslashes($modSettings['news']))));
	$context['fader_news_lines'] = array();
	for ($i = 0, $n = count($context['news_lines']); $i < $n; $i++)
	{
		if (trim($context['news_lines'][$i]) == '')
			continue;

		// Clean it up for presentation ;).
		$context['news_lines'][$i] = doUBBC(stripslashes(trim($context['news_lines'][$i])));

		// Gotta be special for the javascript.
		$context['fader_news_lines'][$i] = strtr(addslashes($context['news_lines'][$i]), array('/' => '\/', '<a href=' => '<a hre" + "f='));
	}
	$context['random_news_line'] = $context['news_lines'][rand(0, count($context['news_lines']) - 1)];

	if (!$user_info['is_guest'])
	{
		$context['user']['messages'] = &$user_info['messages'];
		$context['user']['unread_messages'] = &$user_info['unread_messages'];

		// Personal message popup...
		if ($user_info['unread_messages'] > (isset($_SESSION['unread_messages']) ? $_SESSION['unread_messages'] : 0))
			$context['user']['popup_messages'] = true;
		else
			$context['user']['popup_messages'] = false;
		$_SESSION['unread_messages'] = $user_info['unread_messages'];

		if (allowedTo('moderate_forum'))
			$context['unapproved_members'] = !empty($modSettings['registration_method']) && $modSettings['registration_method'] == 2 ? $modSettings['unapprovedMembers'] : 0;

		$context['user']['avatar'] = array();

		// Figure out the avatar... uploaded?
		if ($user_info['avatar']['url'] == '')
		{
			// If they are allowed to use an uploaded avatar.
			if (!empty($user_info['avatar']['ID_ATTACH']) && !empty($modSettings['avatar_allow_upload']))
				$context['user']['avatar']['href'] = $scripturl . '?action=dlattach;id=' . $user_info['avatar']['ID_ATTACH'] . ';type=avatar';
		}
		// Full URL?
		elseif (substr($user_info['avatar']['url'], 0, 7) == 'http://' && !empty($modSettings['avatar_allow_external_url']))
		{
			$context['user']['avatar']['href'] = $user_info['avatar']['url'];
			if ($modSettings['avatar_action_too_large'] == 'option_html_resize')
			{
				if (!empty($modSettings['avatar_max_width_external']))
					$context['user']['avatar']['width'] = $modSettings['avatar_max_width_external'];
				if (!empty($modSettings['avatar_max_height_external']))
					$context['user']['avatar']['height'] = $modSettings['avatar_max_height_external'];
			}
		}
		// Server stored?
		elseif (!empty($modSettings['avatar_allow_server_stored']))
			$context['user']['avatar']['href'] = $modSettings['avatar_url'] . '/' . htmlspecialchars($user_info['avatar']['url']);

		if (!empty($context['user']['avatar']))
			$context['user']['avatar']['image'] = '<img src="' . $context['user']['avatar']['href'] . '"' . (isset($context['user']['avatar']['width']) ? ' width="' . $context['user']['avatar']['width'] . '"' : '') . (isset($context['user']['avatar']['height']) ? ' height="' . $context['user']['avatar']['height'] . '"' : '') . ' alt="" border="0" />';

		// Figure out how long they've been logged in.
		$context['user']['total_time_logged_in'] = array(
			'days' => floor($user_info['total_time_logged_in'] / 86400),
			'hours' => floor(($user_info['total_time_logged_in'] % 86400) / 3600),
			'minutes' => floor(($user_info['total_time_logged_in'] % 3600) / 60)
		);
	}
	else
	{
		$context['user']['messages'] = 0;
		$context['user']['unread_messages'] = 0;
		$context['user']['avatar'] = array();
		$context['user']['total_time_logged_in'] = array('days' => 0, 'hours' => 0, 'minutes' => 0);
		$context['user']['popup_messages'] = false;

		if (!empty($modSettings['registration_method']) && $modSettings['registration_method'] == 1)
			$txt['welcome_guest'] .= $txt['welcome_guest_activate'];
	}

	// Set up the menu privileges.
	$context['allow_search'] = allowedTo('search_posts');
	$context['allow_admin'] = allowedTo(array('admin_forum', 'manage_boards', 'manage_permissions', 'moderate_forum', 'manage_membergroups', 'manage_bans', 'send_mail', 'edit_news', 'manage_attachments', 'manage_smileys'));
	$context['allow_edit_profile'] = !$user_info['is_guest'] && allowedTo(array('profile_view_own', 'profile_view_any', 'profile_identity_own', 'profile_identity_any', 'profile_extra_own', 'profile_extra_any', 'profile_remove_own', 'profile_remove_any', 'moderate_forum', 'manage_membergroups'));
	$context['allow_calendar'] = allowedTo('calendar_view') && !empty($modSettings['cal_enabled']);

	$context['allow_pm'] = allowedTo('pm_read');

	$context['in_maintenance'] = !empty($maintenance);
	$context['current_time'] = timeformat(time(), false);
	$context['show_vBlogin'] = !empty($modSettings['enableVBStyleLogin']) && $user_info['is_guest'];

	// This is here because old index templates might still use it.
	$context['show_news'] = !empty($settings['enable_news']);

	// This is done to make it easier to add to all themes...
	if ($context['user']['popup_messages'] && !empty($options['popup_messages']))
	{
		$context['html_headers'] .= '
	<script language="JavaScript" type="text/javascript"><!--
		if (confirm("' . $txt['show_personal_messages'] . '"))
			window.open("' . $scripturl . '?action=pm");
	// --></script>';
	}

	if (!isset($context['page_title']))
		$context['page_title'] = '';
}

// This is the only template included in the sources...
function template_rawdata()
{
	global $context;

	echo $context['raw_data'];
}

function template_header()
{
	global $txt, $modSettings, $context, $settings, $user_info;

	setupThemeContext();

	// Print stuff to prevent caching of pages.
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

	foreach ($context['template_layers'] as $layer)
	{
		loadSubTemplate($layer . '_above', true);

		// May seem contrived, but this is done incase the main layer isn't there...
		if ($layer == 'main' && allowedTo('admin_forum'))
		{
			$securityFiles = array('install.php', 'upgrade.php', 'repair_paths.php', 'repair_settings.php');
			foreach ($securityFiles as $i => $securityFile)
			{
				if (!file_exists($securityFile))
					unset($securityFiles[$i]);
			}

			if (!empty($securityFiles))
			{
				echo '
		<div class="windowbg" style="margin: 2ex; padding: 2ex; border: 2px dashed red; color: red;">
			<span style="text-decoration: underline;">', $txt['smf299'], '</span>
			<div style="padding-left: 4ex;">';

				foreach ($securityFiles as $securityFile)
					echo '
			', $txt['smf300'], $securityFile, '!<br />';

				echo '
			</div>
		</div>';
			}
		}
		// If the user is banned from posting inform them of it.
		elseif ($layer == 'main' && !empty($_SESSION['ban']['cannot_post']['is_banned']))
		{
			echo '
				<div class="windowbg" style="margin: 2ex; padding: 2ex; border: 2px dashed red; color: red;">
					', sprintf($txt['you_are_post_banned'], $user_info['is_guest'] ? $txt[28] : $user_info['name']);

			// The substr here is used to get rid of the first of two <br />s.
			if (isset($_SESSION['ban']['cannot_post']['reason']))
				echo '
					<div style="padding-left: 4ex;">', substr($_SESSION['ban']['cannot_post']['reason'], 6), '</div>';

			echo '
				</div>';
		}
	}

	if (isset($settings['use_default_images']) && $settings['use_default_images'] == 'defaults' && isset($settings['default_template']))
	{
		$settings['theme_url'] = $settings['default_theme_url'];
		$settings['images_url'] = $settings['default_images_url'];
		$settings['theme_dir'] = $settings['default_theme_dir'];
	}
}

// Show the copyright...
function theme_copyright($get_it = false)
{
	global $forum_copyright, $context, $boardurl, $forum_version, $txt;
	static $found = false;

	// Meaning, this is the footer checking in..
	if ($get_it === true)
		return $found;

	echo '
		<span class="smalltext" style="display: inline; visibility: visible; font-family: Verdana, Arial, sans-serif;">';

	if ($get_it == 'none')
	{
		$found = true;
		echo '
			The administrator doesn\'t want a copyright notice saying this is copyright 2001-2004 by <a href="http://www.lewismedia.com/">Lewis Media</a>, and named <a href="http://www.simplemachines.org/">SMF</a>, so the forum will honor this request.';
	}
	// If it's in the copyright, and we are outputting it... it's been found.
	elseif ((strpos($forum_copyright, '<a href="http://www.simplemachines.org/" onclick="this.href += \'referer.php?forum=' . urlencode($context['forum_name'] . '|' . $boardurl . '|' . $forum_version) . '\';" target="_blank">SMF') !== false || strpos($forum_copyright, '<a href="http://www.simplemachines.org/" target="_blank">SMF') !== false) && strpos($forum_copyright, '<a href="http://www.lewismedia.com/">Lewis Media</a>') !== false)
	{
		$found = true;
		echo $forum_copyright;
	}

	echo '
		</span>';
}

function template_footer()
{
	global $context, $settings, $modSettings, $time_start, $db_count;

	// Show the load time?  (only makes sense for the footer.)
	$context['show_load_time'] = $modSettings['timeLoadPageEnable'] == 1;
	$time_start = explode(' ', $time_start);
	$time_end = explode(' ', microtime());
	$context['load_time'] = round($time_end[0] + $time_end[1] - $time_start[0] - $time_start[1], 3);
	$context['load_queries'] = $db_count;

	if (isset($settings['use_default_images']) && $settings['use_default_images'] == 'defaults' && isset($settings['default_template']))
	{
		$settings['theme_url'] = $settings['actual_theme_url'];
		$settings['images_url'] = $settings['actual_images_url'];
		$settings['theme_dir'] = $settings['actual_theme_dir'];
	}

	foreach (array_reverse($context['template_layers']) as $layer)
		loadSubTemplate($layer . '_below', true);

	// Do not remove hard-coded text - it's in here so users cannot change the text easily. (as if it were in language file)
	if (!theme_copyright(true) && !empty($context['template_layers']) && SMF !== 'SSI' && !WIRELESS)
	{
		echo '
			<div align="center" style="display: block !important; visibility: visible !important; font-size: xx-large !important; font-weight: bold; color: black !important; background-color: white !important;">
				Sorry, the copyright must be in the template.<br />
				Please notify this ' . "forum's" . ' administrator that this site is using an <span style="color: #FF0000;">ILLEGAL</span> copy of <a href="http://www.simplemachines.org/" style="color: black !important; font-size: xx-large !important;">SMF</a>!
			</div>';

		log_error('Copyright removed!!');
	}
}

// Debugging.
function db_debug_junk()
{
	global $db_cache, $db_count, $context, $scripturl;

	// Add to Settings.php if you want to show the debugging information.
	if (!isset($GLOBALS['db_show_debug']) || $GLOBALS['db_show_debug'] !== true || (isset($_GET['action']) && $_GET['action'] == 'viewquery'))
		return;

	if (empty($_SESSION['view_queries']))
		$_SESSION['view_queries'] = 0;

	$_SESSION['debug'] = $db_cache;
	echo '
<div align="left" class="smalltext">', (isset($context['template']) ? '
	Template: <i>' . $context['template'] . '</i>' . (isset($context['sub_template']) ? ' (<i>' . $context['sub_template'] . '</i>)' : '') . '.<br />' : ''), '
	<a href="', $scripturl, '?action=viewquery" target="_new">Queries used: ', $db_count, '</a>.<br />
	<br />';

	if ($_SESSION['view_queries'] == 1)
		foreach ($db_cache as $q => $qq)
			echo '
	<b>', substr(trim($qq['q']), 0, 6) == 'SELECT' ? '<a href="' . $scripturl . '?action=viewquery;qq=' . ($q + 1) . '#qq' . $q . '" target="_new">' : '', nl2br(str_replace("\t", '&nbsp;&nbsp;&nbsp;', ltrim($qq['q'], "\n\r"))) . '</a></b> in <i>' . $qq['f'] . '</i> line <i>' . $qq['l'] . '</i>, which took ' . $qq['t'] . ' seconds.<br />
	<br />';

	echo '
	<a href="' . $scripturl . '?action=viewquery;sa=hide">[' . (empty($_SESSION['view_queries']) ? 'show' : 'hide') . ' queries]</a>
</div>';
}

// Get an attachment's encrypted filename.  If $new is true, won't check for file existence.
function getAttachmentFilename($filename, $attachment_id, $new = false)
{
	global $modSettings;

	// Remove special accented characters - ie. s�.
	$clean_name = strtr($filename, '������������������������������������������������������������', 'SZszYAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy');
	$clean_name = strtr($clean_name, array('�' => 'TH', '�' => 'th', '�' => 'DH', '�' => 'dh', '�' => 'ss', '�' => 'OE', '�' => 'oe', '�' => 'AE', '�' => 'ae', '�' => 'u'));

	// Sorry, no spaces, dots, or anything else but letters allowed.
	$clean_name = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $clean_name);

	$enc_name = $attachment_id . '_' . str_replace('.', '_', $clean_name) . md5($clean_name);

	if ($attachment_id == false || ($new && empty($modSettings['attachmentEncryptFilenames'])))
		return $clean_name;
	elseif ($new)
		return $enc_name;

	if (file_exists($modSettings['attachmentUploadDir'] . '/' . $enc_name))
		$filename = $modSettings['attachmentUploadDir'] . '/' . $enc_name;
	else
		$filename = $modSettings['attachmentUploadDir'] . '/' . $clean_name;

	return $filename;
}

?>