<?php

class TeamParserFunction {

	static $csv;
	static $info_lookup;
	static $name_lookup;

	public static function setup(&$parser)
	{
		$parser->setFunctionHook("team", array(
			'TeamParserFunction',
			'team'
			),
			SFH_OBJECT_ARGS
		);
		$parser->setFunctionHook("roster", array(
			'TeamParserFunction',
			'roster'
			),
			SFH_OBJECT_ARGS
		);
		self::$csv = array_map('str_getcsv', file(dirname(__FILE__) . '/teams.csv'));
		$infos_index = 0;
		self::$info_lookup = array();
		self::$name_lookup = array();
		foreach(self::$csv as $key => $value) {
			$parts = explode("|", $value[0]);
			$infos = array(
				"abbrev" => $value[1],
				"wikipage" => $value[2],
				"fullname" => $value[3],
				"logo_small" => $value[4],
				"logo_square" => $value[5]
			);
			self::$info_lookup[$infos_index] = $infos;
			foreach($parts as $pkey => $part) {
				self::$name_lookup[strtolower($part)] = $infos_index;
			}
			if (strlen($value[3])) {
				self::$name_lookup[strtolower($value[3])] = $infos_index;
			}
			$infos_index += 1;
		}
		return true;
	}

	static function processArgs( $frame, $args, $defaults ) {
		$new_args = array();
		$num_args = count($args);
		$num_defaults = count($defaults);
		$count = ($num_args > $num_defaults) ? $num_args : $num_defaults;
		
		for ($i=0; $i<$count; $i++) {
			if ( isset($args[$i]) )
				$new_args[$i] = trim( $frame->expand($args[$i]) );
			else
				$new_args[$i] = $defaults[$i];
		}
		return $new_args;
	}

	public static function roster(&$parser, $frame, $args) {
		$args = self::processArgs( $frame, $args, array("", ""));
		$players = array();
		$flags = array();
		$roles = array();
		$links = array();
		$team = "none";
		foreach ($args as $key => $arg) {
			$asplit = explode("=", $arg);
			if($asplit[0] == "team") {
				$team = $asplit[1];
			} else if (strncmp($asplit[0], 'player', 6) === 0) {
				if (strlen($asplit[1]) == 0)
					continue;
				$ix = intval(substr($asplit[0], 6));
				$players[$ix] = trim($asplit[1]);
			} else if (strncmp($asplit[0], 'flag', 4) === 0) {
				$ix = intval(substr($asplit[0], 4));
				$flag = strtolower(trim($asplit[1]));	
				if ($flag == "denmark")
					$flag = "dk";
				$flags[$ix] = strtolower($flag[0] . $flag[1]);
			} else if (strncmp($asplit[0], 'role', 4) === 0) {
				$ix = intval(substr($asplit[0], 4));
				$roles[$ix] = trim($asplit[1]);
			} else if (strncmp($asplit[0], 'link', 4) === 0) {
				$ix = intval(substr($asplit[0], 4));
				$links[$ix] = trim($asplit[1]);
			}
		}
		$output = '<table class="prettytable rostertable">';
		$output .= '<tr><th><span style="font-weight:700;">{{team|' . $team . '}}</span></th></tr>';
		foreach ($players as $ix => $player) {
			$output .= '<tr><td class="RosterPlayers" width="150" style="font-weight:700;line-height:22px;">';
			if (array_key_exists($ix, $roles)) {
				$output .= $roles[$ix] . " ";
			}
			if (array_key_exists($ix, $links)) {
				$output .= '[[' . $links[$ix] . '|' . $player . ']] ';
			} else {
				$output .= '[[' . $player . ']] ';
			}
			if (array_key_exists($ix, $flags)) {
				$output .= '[[File:' . $flags[$ix] . '.png|16px|link=]]';
			}
			$output .= '</td></tr>';
		}
		$output .= '</table>';
		return(array($output, 'noparse' => false));
	}

	public static function team(&$parser, $frame, $args)
	{
		$args = self::processArgs( $frame, $args, array("", ""));
		$thang = strtolower($args[0]);
		$rlen = count($args);
		$render = "default";
		$abbrev = "none";
		if ($rlen == 0) {
			return;
		} else if ($rlen >= 2) {
			$render=$args[1];
		}
		if (($rlen == 3) && ($args[2] == "no-link=true")) {
			if ($render == "default")
				$render = "defaultnolink";
		}
		if (array_key_exists($thang, self::$name_lookup)) {
			$info = self::$info_lookup[self::$name_lookup[$thang]];
			$wikipage = trim($info["wikipage"]);
			$fullname = $info["fullname"];
			$logo = $info["logo_small"];
			$abbrev = $info["abbrev"];
		} else {
			error_log("did not find ". $thang);
			$logo = "Blanklogo std.png";
			$nolink = true;
			$wikipage = "None";
			$fullname = "Not found";
		}
		$nolink = false;
		if ($args[1] == "no-link=true") {
			$nolink = true;
		}
		if (($rlen == 3) && ($args[2] == "no-link=true")) {
			$nolink = true;
		}
		if ($nolink === true) {
			if ($render == "default")
				$render = "defaultnolink";
		}

		switch ($render) {
			default:
			case "default":
				if ($nolink === true) {
					return array("[[File:" . $logo. '|link=|60px]]', 'noparse' => false);
				} else {
					if (strlen($wikipage) == 0) {
						return array("[[File:" . $logo. '|link=|60px]] [[' . $fullname . ']]', 'noparse' => false);
					} else {
						return array("[[File:" . $logo. '|link=|60px]] [[' . $wikipage. '|' . $fullname . ']]', 'noparse' => false);
					}
				}
				break;
			case "leftlong":
				if ($nolink === true) {
					return array('[[File:' . $logo. '|link=|60px]]', 'noparse' => false);
				} else {
					if (strlen($wikipage) == 0) {
						return array('[[' . $fullname . ']] [[File:' . $logo. '|link=|60px]]', 'noparse' => false);
					} else {
						return array('[[' . $wikipage . '|' . $fullname . ']] [[File:' . $logo. '|link=|60px]]', 'noparse' => false);
					}
				}
				break;
			case "rightshort":
				if ($nolink === true) {
					return array("[[File:" . $logo. '|link=|38px]] ', 'noparse' => false);
				} else {
					return array("[[File:" . $logo. '|link=|38px]] '. $abbrev, 'noparse' => false);
				}
				break;
			case "leftshort":
				if ($nolink === true) {
					return array("[[File:" . $logo. '|link=|38px]]', 'noparse' => false);
				} else {
					return array($abbrev . " [[File:" . $logo. '|link=|38px]]', 'noparse' => false);
				}
				break;
			case "rightshortlinked":
				if (strlen($wikipage) == 0) {
					return array("[[File:" . $logo. '|link=|38px]] [['. $fullname . '|'.  $abbrev . ']]', 'noparse' => false);
				} else {
					return array("[[File:" . $logo. '|link=|38px]] [['. $wikipage. '|' . $abbrev . ']]', 'noparse' => false);
				}
				break;
			case "leftshortlinked":
				if (strlen($wikipage) == 0) {
					return array('[[' . $fullnaem . '|' . $abbrev . "]] [[File:" . $logo. '|link=|38px]]', 'noparse' => false);
				} else {
					return array('[[' . $wikipage . '|' . $abbrev . "]] [[File:" . $logo. '|link=|38px]]', 'noparse' => false);
				}
				break;
		}
	}
}

$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['TeamParserFunction'] = $dir . 'TeamParserFunction.i18n.php';
$wgHooks['ParserFirstCallInit'][] = 'TeamParserFunction::setup';
