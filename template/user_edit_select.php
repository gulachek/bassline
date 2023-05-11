<h1> Select a user </h1>

<ul>
<?php foreach ($TEMPLATE['users'] as $id => $user): ?>
<li>
	<a href="<?=$URI->cur(query: ['user_id' => $id, 'action' => 'edit'])?>">
		<?=text($user['username'])?>
	</a>
</li>
<?php endforeach; ?>
</ul>

<form method="POST">
<button type="submit" name="action" value="Create">
	New User
</button>
</form>
