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
				<td><input class="price" placeholder="Price" /> / <input class="price" placeholder="Cancel" /></td>
			<? endforeach ?>
		</tr>
	<? endforeach ?>
</table>
