<?php
function esc(string $str): string
{
	return htmlspecialchars($str);
}
?>

<?php foreach ($USERS as $id => $user): ?>
<div>
	<a href="/site/admin/users?user_id=<?=$id?>">
		<?=esc($user['username'])?>
	</a>
</div>
<?php endforeach; ?>
