<?php
namespace Bang;

class Stats {
	static function usage() {
		echo '<div><small>Server Memory: '.Format::humanDataSize(memory_get_peak_usage(0)).' peaked</small></div>';
		if (!empty($_SERVER['REQUEST_TIME_FLOAT'])) {
			echo '<div><small>Server Runtime: '.round(microtime(1) - $_SERVER['REQUEST_TIME_FLOAT'], 6).'sec</small></div>';
		}
		$a = get_included_files();
		echo '<div><small><details><summary>Files: '.count($a).'</summary>'
			.'<ul>';
			foreach ($a as $k => $v) {
				echo '<li>'.$v.'</li>';
			}
		echo '</ul></details></small></div>';

		$a = get_declared_classes();
		echo '<div><small><details><summary>Classes: '.count($a).'</summary>'
			.'<ul>';
			foreach ($a as $k => $v) {
				echo '<li>'.$v.'</li>';
			}
		echo '</ul></details></small></div>';

		$a = get_declared_interfaces();
		echo '<div><small><details><summary>Interfaces: '.count($a).'</summary>'
			.'<ul>';
			foreach ($a as $k => $v) {
				echo '<li>'.$v.'</li>';
			}
		echo '</ul></details></small></div>';

		$a = get_defined_functions(true)['user'];
		echo '<div><small><details><summary>Functions: '.count($a).'</summary>'
			.'<ul>';
			foreach ($a as $k => $v) {
				echo '<li>'.$v.'</li>';
			}
		echo '</ul></details></small></div>';

		$a = get_defined_constants(true)['user'];
		echo '<div><small><details><summary>Constants: '.count($a).'</summary>'
			.'<ul>';
			foreach ($a as $k => $v) {
				echo '<li>'.$k.':'.$v.'</li>';
			}
		echo '</ul></details></small></div>';
	}
}
