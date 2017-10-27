<?php

require 'bootstrap.php';

$date = $_GET['date'] ?? date('Y-m-d');
$utc = strtotime($date);
$today = (int) date('w', $utc);

/** @var Resource[] $resources */
$resources = Resource::all('1');

$open = min(array_map(function(Resource $resource) use ($today) {
	return $resource->open_timeset->{"open_$today"};
}, $resources));
$clos = min(array_map(function(Resource $resource) use ($today) {
	return $resource->open_timeset->{"clos_$today"};
}, $resources));
$times = range($open, $clos-1);

$prevDate = date('Y-m-d', strtotime('-1 day', $utc));
$nextDate = date('Y-m-d', strtotime('+1 day', $utc));

include 'tpl.header.php';

?>
<p>
	<a href="?date=<?= $prevDate ?>">prev day</a>
	<?= date('l', $utc) . ' ' . $date ?>
	<a href="?date=<?= $nextDate ?>">next day</a>
</p>

<table>
	<thead>
		<tr>
			<th></th>
			<? foreach ($resources as $resource): ?>
				<th><?= html($resource) ?></th>
			<? endforeach ?>
		</tr>
	</thead>
	<tbody>
		<? foreach ($times as $time): ?>
			<tr>
				<th><?= $time ?> - <?= $time+1 ?></th>
				<? foreach ($resources as $resource):
					$timeset = $resource->getTimesetFor($today, $time, $time+1);
					$timeDim = $resource->getTimeDimensionFor($today, $time, $time+1);
					?>
					<td bgcolor="<?= $timeDim ? $timeDim->color : '' ?>">
						<?= html($timeDim) ?>
					</td>
				<? endforeach ?>
			</tr>
		<? endforeach ?>
	</tbody>
</table>

<?php

include 'tpl.footer.php';
