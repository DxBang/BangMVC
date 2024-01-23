<?php
namespace Bang;

abstract class Controller {
	public
		$model,
		$view;
	protected static
		$instance;
	function __construct() {
		if (self::$instance) {
			die('Only ONE Controller instance is allowed: '.get_class());
		}
		self::$instance = true;
	}
	function model(string $model) {
		$models = SITE_PRIVATE.'/models';
		if (Core::isAPI()) {
			$models .= '/api';
		}
		if (Core::isSpecial()) {
			$models .= '/'.Core::URI(0);
		}
		$file =  $models.'/'.$model.'.php';
		if (!file_exists($file)) {
			return;
		}
		Core::mark('Bang\Controller::_mode('.$file.')');
		require_once $file;
		$modelName = $model.'Model';
		$this->model = new $modelName();
	}
	function view() {
		$this->view = new View();
		$this->view->model = &$this->model;
		$this->view->uri = Core::URI(-1);
		$this->view->urn = Core::URN();
		$this->view->token = Visitor::token();
		$this->view->host = Core::host();
		$this->view->domain = Core::domain();
		$this->view->subdomain = Core::subdomain();
	}
	/*
	function exception($e) {
		Core::exception($e);
	}
	function ready() {
		return (is_object($this->view) && is_object($this->model));
	}
	function render(string $view) {
		if ($this->view) return $this->view->render($view);
	}
	static function json($data) {
		return View::json($data);
	}
	static function text(string $text, bool $forceHtml = true, bool $allowLines = false) {
		return View::text($text);
	}
	*/
	function __set(string $k, $v) {
		return View::set($k, $v);
	}
	function __get(string $k) {
		return View::get($k);
	}
	function __isset(string $k) {
		return View::isset($k);
	}
	function __unset(string $k) {
		return View::unset($k);
	}
}
