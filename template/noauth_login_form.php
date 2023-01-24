<form method="POST" action="<?=text($POST_URI)?>">
	<select name="user-id">
		<?php foreach ($USERS as $id => $user): ?>
		<option value="<?=$id?>"> <?=text($user['username'])?> </option>
		<?php endforeach; ?>
	</select>
	<input type="submit" value="Log in" />
</form>
<p>
	You are who you say you are. I trust you no matter what.
</p>
