<?php

function html( $text ) {
	return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8') ?: htmlspecialchars((string)$text, ENT_QUOTES, 'ISO-8859-1');
}

function html_options( $options, $selected = null, $empty = '' ) {
	$selected = (array) $selected;

	$html = '';
	$empty && $html .= '<option value="">' . $empty;
	foreach ( $options AS $value => $label ) {
		if ( $label instanceof Model ) {
			$value = $label->id;
		}

		$isSelected = in_array($value, $selected) ? ' selected' : '';
		$html .= '<option value="' . html($value) . '"' . $isSelected . '>' . html($label) . '</option>';
	}
	return $html;
}

function do_redirect( $uri = null ) {
	$uri or $uri = get_url();
	header("Location: " . $uri);
	exit;
}

function get_url() {
	$scheme = @$_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
	$host = $_SERVER['HTTP_HOST'];
	$uri = $_SERVER['REQUEST_URI'];
	return $scheme . $host . $uri;
}
