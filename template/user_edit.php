<?php
function esc(string $str): string
{
	return htmlspecialchars($str);
}
?>

<h1> Edit User </h1>

<form method="POST">

<input type="hidden"
	name="user_id"
	value="<?=$USER['id']?>"
	/>

<label> username:
	<input type="text"
		title="Enter a username"
		pattern="<?=$USERNAME_PATTERN?>"
		value="<?=$USER['username']?>"
		/>

<input type="submit" name="action" value="Save" />

</form>
