<link rel="stylesheet" type="text/css" href="/assets/group_select.css" />
<h1> Select a group </h1>

<div class="group-container">
<form action="<?=$URI->rel('edit')?>">
	<?php foreach ($TEMPLATE['groups'] as $id => $group): ?>
		<div>
			<button
				class="clickable"
				name="id"
				value="<?=$id?>"
				>
				<?=text($group['groupname'])?>
			</button>
		</div>
	<?php endforeach; ?>
</form>
</div>

	<form method="POST" action="<?=$URI->rel('create')?>">
	<label> groupname:
		<input
			type="text"
			class="editable"
			name="groupname"
		/>
	</label>
	<input class="clickable" type="submit" value="Create" />
</form>
