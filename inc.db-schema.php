<?php

$fk = function($tbl, $col, $null = false) {
	return ['null' => $null, 'type' => 'int', 'references' => [$tbl, $col]];
};

return [
	'version' => 5,
	'tables' => [
		'day_dimension' => [
			'id' => ['pk' => true],
			'label' => ['null' => false],
			'days' => ['null' => false],
		],
		'time_dimension' => [
			'id' => ['pk' => true],
			'label' => ['null' => false],
		],

		'costs' => [
			'id' => ['pk' => true],
			'label' => ['null' => false],
		],
		'costs_data' => [
			'costs_id' => $fk('costs', 'id'),
			'day_dimension_id' => $fk('day_dimension', 'id'),
			'time_dimension_id' => $fk('time_dimension', 'id'),
			'context' => ['null' => false, 'default' => ''],
			'costs' => ['type' => 'float'],
		],

		'class_activities' => [
			'id' => ['pk' => true],
			'label' => ['null' => false],
			'costs_id' => $fk('costs', 'id', true),
		],

		'member_types' => [
			'id' => ['pk' => true],
			'label' => ['null' => false],
		],
		'member_type_datas' => [
			'id' => ['pk' => true],
			'member_type_id' => $fk('member_types', 'id'),
			'start_date' => ['null' => false, 'type' => 'date'],
			'costs_id' => $fk('costs', 'id', true),
		],

		// @todo Time sets
		// @todo Resources
	],
];
