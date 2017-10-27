<?php
/** @var Costs $scosts */
list($sname, $scosts) = $source;
?>

<table>
	<tr>
		<? foreach (array_values($days) as $i => $day): ?>
			<th class="<?= $i ? 'fat' : '' ?>" colspan="<?= count($times) ?>"><?= html($day) ?></th>
		<? endforeach ?>
	</tr>
	<tr>
		<? foreach (array_values($days) as $i => $day): ?>
			<? foreach (array_values($times) as $j => $time): ?>
				<th class="<?= $i && !$j ? 'fat' : '' ?>"><?= html($time) ?></th>
			<? endforeach ?>
		<? endforeach ?>
	</tr>
	<tr>
		<? foreach (array_values($days) as $i => $day): ?>
			<? foreach (array_values($times) as $j => $time): ?>
				<td class="<?= $i && !$j ? 'fat' : '' ?>">
					<input class="price" name="<?= $sname ?>[costs][<?= $day->id . '-' . $time->id . '-make' ?>]" value="<?= $scosts->getInput($day->id, $time->id, 'make') ?>" placeholder="Price" />
				</td>
			<? endforeach ?>
		<? endforeach ?>
	</tr>
	<tr>
		<? foreach (array_values($days) as $i => $day): ?>
			<? foreach (array_values($times) as $j => $time): ?>
				<td class="<?= $i && !$j ? 'fat' : '' ?>">
					<input class="price" name="<?= $sname ?>[costs][<?= $day->id . '-' . $time->id . '-cancel' ?>]" value="<?= $scosts->getInput($day->id, $time->id, 'cancel') ?>" placeholder="Cancel" />
				</td>
			<? endforeach ?>
		<? endforeach ?>
	</tr>
</table>
