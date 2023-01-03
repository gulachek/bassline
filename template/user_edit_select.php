<?php
function esc(string $str): string
{
	return htmlspecialchars($str);
}
?>

<?php if ($ERROR): ?>
	<dialog open>
		<h2> Error </h2>
		<p> <?=esc($ERROR)?> </p>
		<form method="dialog"><button> Ok </button></form>
	</dialog>
<?php endif; ?>

<h1> Select a user </h1>

<ul>
<?php foreach ($USERS as $id => $user): ?>
<li>
	<a href="/site/admin/users?user_id=<?=$id?>">
		<?=esc($user['username'])?>
	</a>
</li>
<?php endforeach; ?>
</ul>

<form method="POST">
<input type="text"
	title="Enter a username (letters, numbers, or underscores)"
	pattern="<?=$USERNAME_PATTERN?>"
	name="username"
	value="new_user"
	required
	/>
	
<input type="submit" name="action" value="Create" />
</form>
