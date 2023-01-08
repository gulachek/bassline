<?php require_once $UTIL; ?>

<?php if ($err = $TEMPLATE['error']): ?>
	<dialog open>
		<h2> Error </h2>
		<p> <?=text($err)?> </p>
		<form method="dialog"><button> Ok </button></form>
	</dialog>
<?php endif; ?>

<h1> Select a user </h1>

<ul>
<?php foreach ($TEMPLATE['users'] as $id => $user): ?>
<li>
	<a href="/site/admin/users?user_id=<?=$id?>">
		<?=text($user['username'])?>
	</a>
</li>
<?php endforeach; ?>
</ul>

<form method="POST">
<input type="text"
	title="Enter a username (letters, numbers, or underscores)"
	pattern="<?=$TEMPLATE['username_pattern']?>"
	name="username"
	value="new_user"
	required
	/>
	
<input type="submit" name="action" value="Create" />
</form>
