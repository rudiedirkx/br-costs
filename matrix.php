<?php

require __DIR__ . '/inc.bootstrap.php';

$date = $_GET['date'] ?? date('Y-m-d');
$utc = strtotime($date);
$today = (int) date('w', $utc);

/** @var DayDimension $day */
$day = DayDimension::first('days LIKE ?', ["%$today%"]);

/** @var Resource[] $resources */
$resources = Resource::all('EXISTS (SELECT * FROM resource_timesets WHERE resource_id = resources.id AND ? BETWEEN start_date AND end_date)', [$date]);
$timesets = Resource::eager('timesets', $resources);
ResourceTimeset::eager('open_timeset', $timesets);
$peaks = ResourceTimeset::eager('peak_times', $timesets);
ResourcePeakTime::eager('timeset', $peaks);
$prices = Resource::eager('resource_price', $resources);

/** @var MemberType[] $memberTypes */
$memberTypes = MemberType::all('1');
$datas = MemberType::eager('datas', $memberTypes);

$open = $clos = null;
$times = [];
if ( count($resources) ) {
	$open = min(array_map(function(Resource $resource) use ($date, $today) {
		return $resource->getDatedTimesetFor($date)->open_timeset->{"open_$today"};
	}, $resources));
	$clos = min(array_map(function(Resource $resource) use ($date, $today) {
		return $resource->getDatedTimesetFor($date)->open_timeset->{"clos_$today"};
	}, $resources));
	$times = range($open, $clos-1);
}

$prevDate = date('Y-m-d', strtotime('-1 day', $utc));
$nextDate = date('Y-m-d', strtotime('+1 day', $utc));

include 'tpl.header.php';

?>
<style>
td {
	cursor: pointer;
}
td:not(.show-meta) > div {
	display: none;
}
</style>

<p>
	<a href="index.php">Config</a>
	|
	<a href="?date=<?= $prevDate ?>">prev day</a>
	<?= date('l', $utc) . ' ' . $date ?>
	<a href="?date=<?= $nextDate ?>">next day</a>
</p>

<table>
	<thead>
		<tr>
			<th></th>
			<? foreach ($resources as $resource):
				$timeset = $resource->getDatedTimesetFor($date)->open_timeset;
				?>
				<th title="Open hours: <?= html($timeset) ?> (<?= html($timeset->id) ?>)"><?= html($resource) ?></th>
			<? endforeach ?>
		</tr>
	</thead>
	<tbody>
		<? foreach ($times as $time): ?>
			<tr>
				<th><?= $time ?> - <?= $time+1 ?></th>
				<? foreach ($resources as $resource):
					$timeset = $resource->getTimesetFor($date, $time, $time+1);
					$timeDim = $resource->getTimeDimensionFor($date, $time, $time+1);
					if ($timeDim):
						$did = $day->id;
						$tid = $timeDim->id;
						?>
						<td style="background-color: <?= $timeDim->color ?>">
							<?= html($timeDim) ?>
							<div>Timeset: <?= html($timeset) ?></div>
							<div>Guest: <?= $resource->resource_price->costs->getDisplay($did, $tid, 'make') ?></div>
							<? foreach ($memberTypes as $type): ?>
								<div><?= html($type) ?>: <?= $type->getDataFor($date)->costs->getDisplay($did, $tid, 'make') ?></div>
							<? endforeach ?>
						</td>
					<? else: ?>
						<td></td>
					<? endif ?>
				<? endforeach ?>
			</tr>
		<? endforeach ?>
	</tbody>
</table>

<script>
document.querySelector('table').addEventListener('click', function(e) {
	var prev = document.querySelector('.show-meta');
	var curr = e.target.nodeName == 'TD' ? e.target : e.target.parentNode;

	if (prev == curr) {
		prev.classList.remove('show-meta');
		return;
	}
	else if (prev) {
		prev.classList.remove('show-meta');
	}
	curr.classList.add('show-meta');
});
</script>

<?php

include 'tpl.footer.php';
