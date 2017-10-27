<?php

$fk = function($tbl, $col, $null = false, $delete = null) {
	return ['null' => $null, 'type' => 'int', 'references' => [$tbl, $col, $delete]];
};
$weekday = ['type' => 'int', 'null' => false, 'default' => 0];

return [
	'version' => 12,
	'tables' => [
		'day_dimension' => [
			'id' => ['pk' => true],
			'label' => ['null' => false],
			'days' => ['null' => false],
		],
		'time_dimension' => [
			'id' => ['pk' => true],
			'label' => ['null' => false],
			'is_default' => ['null' => false, 'type' => 'int', 'default' => 0],
			'is_peak' => ['null' => false, 'type' => 'int', 'default' => 0],
			'color' => ['null' => false, 'default' => ''],
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

		'timesets' => [
			'id' => ['pk' => true],
			'label' => ['null' => false],
			'open_1' => $weekday,
			'clos_1' => $weekday,
			'open_2' => $weekday,
			'clos_2' => $weekday,
			'open_3' => $weekday,
			'clos_3' => $weekday,
			'open_4' => $weekday,
			'clos_4' => $weekday,
			'open_5' => $weekday,
			'clos_5' => $weekday,
			'open_6' => $weekday,
			'clos_6' => $weekday,
			'open_0' => $weekday,
			'clos_0' => $weekday,
		],

		'resources' => [
			'id' => ['pk' => true],
			'label' => ['null' => false],
			'open_timeset_id' => $fk('timesets', 'id'),
		],
		'resource_timesets' => [
			'id' => ['pk' => true],
			'resource_id' => $fk('resources', 'id', false, 'cascade'),
			'timeset_id' => $fk('timesets', 'id', false, 'cascade'),
			'time_dimension_id' => $fk('time_dimension', 'id', false, 'cascade'),
		],
	],
];
