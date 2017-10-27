<?php

require 'bootstrap.php';

$labelIsEmpty = function($data) {
	return trim(@$data['label']) === '';
};

if ( isset($_POST['activities']) ) {
	ClassActivity::_updates($_POST['activities'], $labelIsEmpty);
	return do_redirect();
}

if ( isset($_POST['days']) ) {
	DayDimension::_updates($_POST['days'], $labelIsEmpty);
	return do_redirect();
}

if ( isset($_POST['times']) ) {
	TimeDimension::_updates($_POST['times'], $labelIsEmpty);
	return do_redirect();
}

$activities = ClassActivity::all('1');
// $activities[] = new ClassActivity;

$days = DayDimension::all('1');
// $days[] = new DayDimension;

$times = TimeDimension::all('1');
// $times[] = new TimeDimension;

$costsTpl = @$_GET['ctpl'] === '2' ? '2d' : '1d';

?>
<style>
table {
	border-collapse: collapse;
}
td, th {
	border: solid #bbb 1px;
	padding: 4px;
}
td.fat, th.fat {
	border-left: solid #999 3px;
}
table table td {
	text-align: center;
}

::placeholder {
	font-style: italic;
	color: #bbb;
}

.price {
	width: 4em;
	text-align: center;
}
</style>

<p><a href="?ctpl=1">1D</a> | <a href="?ctpl=2">2D</a></p>

<h2>Class activities</h2>

<form method="post">
	<table>
		<? foreach (array_merge($activities, [new ClassActivity]) as $activity): ?>
			<tbody>
				<tr>
					<th><?= $activity->id ?></th>
					<td><input name="activities[<?= $activity->id ?: 0 ?>][label]" value="<?= html($activity->label) ?>" placeholder="Activity name" /></td>
				</tr>
				<? if ($activity->id): ?>
					<tr>
						<td></td>
						<td colspan="2" style="padding: 0">
							<? $source = ['activities', $activity->id, $activity->costs]; include "tpl.costs-{$costsTpl}.php" ?>
						</td>
					</tr>
				<? endif ?>
			</tbody>
		<? endforeach ?>
		<tfoot>
			<tr>
				<td></td>
				<td colspan="2"><button>Save</button></td>
			</tr>
		</tfoot>
	</table>
</form>

<h2>Day dimension</h2>

<form method="post">
	<table>
		<thead>
			<tr>
				<th></th>
				<th>Label</th>
				<th>Days</th>
			</tr>
		</thead>
		<tbody>
			<? foreach (array_merge($days, [new DayDimension]) as $day): ?>
				<tr>
					<th><?= $day->id ?></th>
					<td><input name="days[<?= $day->id ?: 0 ?>][label]" value="<?= html($day->label) ?>" placeholder="Day label" /></td>
					<td><input name="days[<?= $day->id ?: 0 ?>][days]" value="<?= html($day->days) ?>" placeholder="Days" /></td>
				</tr>
			<? endforeach ?>
		</tbody>
		<tfoot>
			<tr>
				<td></td>
				<td colspan="2"><button>Save</button></td>
			</tr>
		</tfoot>
	</table>
</form>

<h2>Time dimension</h2>

<form method="post">
	<table>
		<thead>
			<tr>
				<th></th>
				<th>Label</th>
			</tr>
		</thead>
		<tbody>
			<? foreach (array_merge($times, [new TimeDimension]) as $time): ?>
				<tr>
					<th><?= $time->id ?></th>
					<td><input name="times[<?= $time->id ?: 0 ?>][label]" value="<?= html($time->label) ?>" placeholder="Time label" /></td>
				</tr>
			<? endforeach ?>
		</tbody>
		<tfoot>
			<tr>
				<td></td>
				<td colspan="2"><button>Save</button></td>
			</tr>
		</tfoot>
	</table>
</form>

<details>
	<summary>Queries (<?= count($db->queries) ?>)</summary>
	<pre><? print_r($db->queries) ?></pre>
</details>
