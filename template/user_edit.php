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

<h1> Edit User </h1>

<form method="POST">

<input type="hidden"
	name="user_id"
	value="<?=$USER['id']?>"
	/>

<label> username:
	<input type="text"
		name="username"
		title="Enter a username (letters, numbers, or underscores)"
		pattern="<?=$USERNAME_PATTERN?>"
		value="<?=$USER['username']?>"
		required
		/>

<input type="submit" name="action" value="Save" />

</form>
