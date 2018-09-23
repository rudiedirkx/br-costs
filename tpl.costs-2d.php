<?php
/** @var Costs $scosts */
list($sname, $scosts) = $source;
?>

<table>
	<tr>
		<th></th>
		<? foreach ($days as $day): ?>
			<th><?= html($day) ?></th>
		<? endforeach ?>
	</tr>
	<? foreach ($times as $time): ?>
		<tr>
			<th><?= html($time) ?></th>
			<? foreach ($days as $day): ?>
				<td>
					<input class="price" name="<?= $sname ?>[costs][<?= $day->id . '-' . $time->id . '-make' ?>]" value="<?= $scosts->getInput($day->id, $time->id, 'make') ?>" placeholder="Price" title="Price" />
					/
					<input class="price" name="<?= $sname ?>[costs][<?= $day->id . '-' . $time->id . '-cancel' ?>]" value="<?= $scosts->getInput($day->id, $time->id, 'cancel') ?>" placeholder="Cancel" title="Cancel" />
				</td>
			<? endforeach ?>
		</tr>
	<? endforeach ?>
</table>
