<?php
/******************************************************************************

	BBCode to HTML conversion for PHP

	Michael Rydén <zynex@zoik.se>
	https://github.com/zynexiz

	This is public domain software. Anyone is free to copy, modify, publish,
	use, compile, sell, or distribute this software, either in source code
	form or as a compiled binary, for any purpose, commercial or
	non-commercial,	and by any means.

******************************************************************************/

class BBCode {
	// Tag aliases.  Item on left translates to item on right.
	const TAG_ALIAS = [
		'code' => 'pre',
		'quote' => 'blockquote',
		'*' => 'li',
	];

	// Alias for tag arguments, fx. size = font-size
	const ARG_ALIAS = [
		'size'=>'font-size',
		'bcolor'=>'background-color',
	];

	/*	Array with allowed tags and how to convert them.
		start_tag	The first part of the html code. Can pass parameters and
					arguments to the html tag. {PARAM} can be passed as
					[tag=PARAM], or [tag]PARAM[/tag]. {ARG} needs the arg
					parameter to define what arguments are allowed.
		end_tag		(optional) The closing tag for the html code.
		arg			(optional) Comma-separated values of what arguments are
					passed. Must contain the key itself and {ARG}. Fx.
					"color:{ARG};" will pass [tag color=red] as "color:red;" and
					"width={ARG}" will pass [tag width=25px] as width=25px as
					argument to start_tag.
		parent		(optional) Comma-separated values of parent tags the
					tag must be inside.
	*/

	const BBCode=array(
		'i'=>		array('start_tag'=>'<i>', 'end_tag'=>'</i>'),
		'url'=>		array('start_tag'=>'<a href="{PARAM}">', 'end_tag'=>'</a>'),
		'img'=>		array('start_tag'=>'<img src="{PARAM}" {ARG} />', 'arg'=>'width={ARG},height={ARG}'),
		'b'=>		array('start_tag'=>'<b>','end_tag'=>'</b>'),
		'br'=>		array('start_tag' => '<br>'),
		'font'=>	array('start_tag'=>'<span style="{ARG}">', 'end_tag'=>'</span>', 'arg'=>'color:{ARG};,size:{ARG};'),
		'ul'=>		array('start_tag'=>'<ul>' , 'end_tag'=>'</ul>'),
		'ol'=>		array('start_tag'=>'<ol>' , 'end_tag'=>'</ol>'),
		'li'=>		array('start_tag'=>'<li>', 'end_tag'=>'</li>', 'parent'=>'ul,ol'),
		'table'=>	array('start_tag'=>'<table style="{ARG}">', 'end_tag'=>'</table>', 'arg'=>'color:{ARG};,width:{ARG};,bcolor:{ARG};'),
		'tr'=>		array('start_tag'=>'<tr>', 'end_tag'=>'</tr>', 'parent'=>'table'),
		'td'=>		array('start_tag'=>'<td>', 'end_tag'=>'</td>', 'parent'=>'tr'),
		'th'=>		array('start_tag'=>'<th>', 'end_tag'=>'</th>', 'parent'=>'tr'),
	);


	// helper function: normalize a potential "tag"
	//  convert to lowercase and check against the alias list
	//  returns a named array with details about the tag
	static private function decode_tag($input) : array {
		// get tag name
		$inner = ($input[1] === '/') ? substr($input, 2, -1) : substr($input, 1, -1);

		// oneliner to burst inner by spaces, then burst each of those by equals signs
		$params = array_map(function(&$a) { return explode('=', $a, 2); }, explode(' ', $inner));

		// first "param" is special - it's the tag name and (optionally) the default arg
		$first = array_shift($params);

		// use tag alias if defined
		$name = strtolower($first[0]);
		if (isset(self::TAG_ALIAS[$name])) {
			$name = self::TAG_ALIAS[$name];
		}

		// "default" (unnamed) argument
		$args = null;
		if (isset ($first[1])) {
			$args['default'] = $first[1];
		}

		// put the rest of the args in the list
		foreach ($params as &$param) {
			$k = isset($param[0]) ? strtolower($param[0]) : '';
			$v = isset($param[1]) ? $param[1] : '';
			$args[$k] = $v;
		}
		$args['end_tag'] = ($input[1] === '/') ? true : false;

		return [ 'name' => $name, 'args' => $args ];
	}

	// helper function: normalize HTML entities, with newline handling
	static private function encode($input) : string	{
		// break substring into individual unicode chars
		$characters = preg_split('//u', $input, null, PREG_SPLIT_NO_EMPTY);

		// append each one-at-a-time to create output
		$lf = 0;
		$output = '';
		foreach ($characters as &$ch)
		{
			if ($ch === "\n") {
				$lf ++;
			} elseif ($ch === "\r") {
				continue;
			} else {
				if ($lf === 1) {
					$output .= "\n<br>";
					$lf = 0;
				} elseif ($lf > 1) {
					$output .= "\n\n<p>";
					$lf = 0;
				}

				if ($ch === '<') {
					$output .= '&lt;';
				} elseif ($ch === '>') {
					$output .= '&gt;';
				} elseif ($ch === '&') {
					$output .= '&amp;';
				} elseif ($ch === "\u{00A0}") {
					$output .= '&nbsp;';
				} else {
					$output .= $ch;
				}
			}
		}

		// trailing linefeed handle
		if ($lf === 1) {
			$output .= "\n<br>";
		} elseif ($lf > 1) {
			$output .= "\n\n<p>";
		}

		return $output;
	}

	static public function bbcode2html($input) : string {
		// split input string into array using regex so we get a list of
		// tags to work with. Throw error if something went wrong.
		$match_count = preg_match_all("/\[[A-Za-z0-9 \-._~:\/?#@!$&'()*+,;=%]+\]/u", $input, $matches, PREG_OFFSET_CAPTURE);
		if ($match_count === FALSE) {
			throw new RuntimeException('Fatal error in preg_match_all for BBCode tags');
		}

		$output = '';
		$input_ptr = 0;
		$stack = [];

		for ($match_idx = 0; $match_idx < $match_count; $match_idx ++) {
			list($match, $offset) = $matches[0][$match_idx];

			// pick up chars between tags and HTML-encode them and advance
			// input_ptr to just past the current tag
			$output .= self::encode(substr($input, $input_ptr, $offset - $input_ptr));
			$input_ptr = $offset + strlen($match);

			list('name' => $name, 'args' => $args) = self::decode_tag($match);

			if ($args['end_tag']) {
				// search the tag stack and see if the opening tag was pushed into it
				if (array_search($name, $stack, TRUE) === FALSE) {
					// attempted to close a tag that was not on the stack!
					$output = $output . self::encode($match);
				} else {
					// repeat until we find the tag, and close everything on the way
					do {
						$popped_name = array_pop($stack);
						$output = $output . self::BBCode[$popped_name]['end_tag'];
					} while ($name !== $popped_name);
				}
			} else {
				// check if the tag must be used inside a another tag
				if (isset(self::BBCode[$name]['parent'])) {
					$parent_tag = (!empty(array_intersect(explode(',',self::BBCode[$name]['parent']), $stack))) ? true : false;
				}

				// check that the tag is valid and process it
				if (isset(self::BBCode[$name]) && (isset($parent_tag)?$parent_tag:true)) {
					// add to stack if the tag should have a end_tag
					if (isset(self::BBCode[$name]['end_tag'])) {$stack[] =  $name;}
					$arg_string = '';
					$start_tag = self::BBCode[$name]['start_tag'];

					// if arguments are found process them, skip if tag dosn't allow args
					$arg_count = count($args) - 1;
					if ($arg_count > 0 && isset(self::BBCode[$name]['arg'])) {
						$keys = array_keys($args);
						// look thru the valid arguments and match against tag args
						for ($i = 0; $i < $arg_count; $i++) {
							if (preg_match("/($keys[$i])[^,]+/",self::BBCode[$name]['arg'],$found) > 0) {
								if (isset(self::ARG_ALIAS[$keys[$i]])) {
									$found[0] = str_replace($keys[$i],self::ARG_ALIAS[$keys[$i]],$found[0]);
								}
								$arg_string .= str_replace("{ARG}",$args[$keys[$i]],$found[0]).' ';
							}
						}

						$start_tag = str_replace("{ARG}",$arg_string,$start_tag);
					}

					// check if the tag requires a parameter
					if (strpos(self::BBCode[$name]['start_tag'], '{PARAM}') !== FALSE) {
						// look for end tag and grab content if found
						$content = null;
						$i = $match_idx + 1;
						if ($i < $match_count) {
							list($search_match, $search_offset) = $matches[0][$i];
							$search_tag = self::decode_tag($search_match);

							/* if next tag is an end tag, and match the previous tag, grab the content
							if no end tag found, but html code requires a closing tag, add it
							and use {PARAM} as the content and remove it from the stack */
							if ($search_tag['args']['end_tag'] && $search_tag['name'] === $name) {
								$content = substr($input, $input_ptr, $search_offset - $input_ptr);
								// if html code doesn't have a closing tag, advance to next tag
								if (!isset(self::BBCode[$name]['end_tag'])) {
									$input_ptr = $search_offset + strlen($search_match);
									$match_idx = $i;
								}
							} elseif (isset(self::BBCode[$name]['end_tag'])) {
								$start_tag .= "{PARAM}".self::BBCode[$name]['end_tag'];
								array_pop($stack);
							}
						}

						$param = (isset($args['default'])) ? $args['default'] : $content;
						$start_tag = str_replace("{PARAM}",$param,$start_tag);
					}

					$output = $output . $start_tag;
				} else {
					$output .= self::encode($match);
					unset($parent_tag);
				}
			}
		}

		// pick up any stray chars and HTML-encode them
		$output .= self::encode(substr($input, $input_ptr));

		// close any remaining stray tags left in the stack so we don't
		// breake the html code on the page
		while ($stack) {
			$tag = array_pop($stack);
			$output = $output . '</' . $tag . '>';
		}

		return $output;
	}
}

function bbcode2html($input) : string {
	return BBCode::bbcode2html($input);
}
