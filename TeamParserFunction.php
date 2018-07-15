<?php

class TeamParserFunction
{
	public static function onParserFirstCallInit(&$parser)
	{
		$parser->setHook("team", "TeamParserFunction::team");
		$this->csv = array_map('str_getcsv', file(dirname(__FILE__) . '/teams.csv'));
		$infos_index = 0;
		$this->info_lookup = array();
		$this->name_lookup = array();
		foreach($this->csv as $key => $value) {
			$parts = explode("|", $value[0]);
			$infos = array(
				"abbrev" => $value[1],
				"wikipage" => $value[2],
				"fullname" => $value[3],
				"logo_small" => $value[4],
				"logo_square" => $value[5]
			);
			$this->info_lookup[$infos_index] = $infos;
			foreach($parts as $pkey => $part) {
				$this->name_lookup[$part] = $infos_index;
			}
			$infos_index += 1;
		}
		return true;
	}

	public static function team($input, array $args, Parser $parser, PPFrame $frame)
	{
		// TODO: handle various modes of team template
		$team = strtolower(reset($args));
		if (array_key_exists($team, $this->name_lookup)) {

		} else {
			return "FOO";
		}
	}

	private function __construct() {}
}

$wgHooks["ParserFirstCallInit"][] = "TeamParserFunction::onParserFirstCallInit";

?>
