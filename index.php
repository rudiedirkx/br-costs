<?php

require 'bootstrap.php';

$labelIsEmpty = function(array $data) {
	return trim(@$data['label']) === '';
};

if ( isset($_POST['member_types']) ) {
	MemberType::_updates($_POST['member_types'], $labelIsEmpty);
	return do_redirect();
}

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
$memberTypes = MemberType::all('1');
$days = DayDimension::all('1');
$times = TimeDimension::all('1');

$costsTpl = @$_GET['ctpl'] === '2' ? '2d' : '1d';

?>
<style>
table {
	border-collapse: collapse;
}
td, th {
	border: solid #bbb 1px;
	padding: 6px;
}
td.fat, th.fat {
	border-left: solid #999 3px;
}
table table td {
	text-align: center;
}
input ~ table {
	margin-top: 0.5em;
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

<h2>Member types</h2>

<form method="post">
	<table>
		<? foreach (array_merge($memberTypes, [new MemberType]) as $type): ?>
			<tbody>
				<tr>
					<th><?= $type->id ?></th>
					<td><input name="member_types[<?= $type->id ?: 0 ?>][label]" value="<?= html($type->label) ?>" placeholder="Member type name" /></td>
				</tr>
				<? if ($type->id): ?>
					<tr>
						<td></td>
						<td colspan="2">
							<? foreach (array_merge($type->datas, [new MemberTypeData]) as $data): ?>
								<input name="member_types[<?= $type->id ?: 0 ?>][datas][<?= $data->id ?: 0 ?>][start_date]" value="<?= html($data->start_date) ?>" placeholder="2014-04-21" /><br>
								<? if ($data->id): ?>
									<? $source = ["member_types[$type->id][datas][$data->id]", $data->costs]; include "tpl.costs-{$costsTpl}.php" ?>
									<br>
									<hr>
								<? endif ?>
								<br>
							<? endforeach ?>
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
						<td colspan="2">
							<? $source = ["activities[$activity->id]", $activity->costs]; include "tpl.costs-{$costsTpl}.php" ?>
							<br>
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
				<th>Default</th>
			</tr>
		</thead>
		<tbody>
			<? foreach (array_merge($times, [new TimeDimension]) as $time): ?>
				<tr>
					<th><?= $time->id ?></th>
					<td><input name="times[<?= $time->id ?: 0 ?>][label]" value="<?= html($time->label) ?>" placeholder="Time label" /></td>
					<td><input type="checkbox" name="times[<?= $time->id ?: 0 ?>][is_default]" <?= $time->is_default ? 'checked' : '' ?> /></td>
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
