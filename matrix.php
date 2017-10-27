<?php

require 'bootstrap.php';

$today = (int) date('w');

$resources = Resource::all('1');
// $day = DayDimension::first('days LIKE ?', [$today]);
$open = min(array_map(function(Resource $resource) use ($today) {
	return $resource->open_timeset->{"open_$today"};
}, $resources));
$clos = min(array_map(function(Resource $resource) use ($today) {
	return $resource->open_timeset->{"clos_$today"};
}, $resources));
$times = range($open, $clos-1);

?>
<table border="1">
	<thead>
		<tr>
			<th></th>
			<? foreach ($resources as $resource): ?>
				<th nowrap><?= html($resource) ?></th>
			<? endforeach ?>
		</tr>
	</thead>
	<tbody>
		<? foreach ($times as $time): ?>
			<tr>
				<th><?= $time ?> - <?= $time+1 ?></th>
				<? foreach ($resources as $resource): ?>
					<td></td>
				<? endforeach ?>
			</tr>
		<? endforeach ?>
	</tbody>
</table>
