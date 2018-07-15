<?php

$csv = array_map('str_getcsv', file(dirname(__FILE__) . '/teams.csv'));
$infos_index = 0;
$info_lookup = array();
$name_lookup = array();
foreach($csv as $key => $value) {
	$parts = explode("|", $value[0]);
	$infos = array(
		"abbrev" => $value[1],
		"wikipage" => $value[2],
		"fullname" => $value[3],
		"logo_small" => $value[4],
		"logo_square" => $value[5]
	);
	$info_lookup[$infos_index] = $infos;
	foreach($parts as $pkey => $part) {
		$name_lookup[$part] = $infos_index;
	}
	$infos_index += 1;
	print_r($parts);
	print_r($infos);
}
print_r($name_lookup);
print_r($info_lookup);
