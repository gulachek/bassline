<?php

namespace Gulachek\Bassline;

class GroupEditPage extends Responder
{
	const GROUPNAME_PATTERN = "^[a-zA-Z0-9_]+$";

	public function __construct(
		private SecurityDatabase $db,
		private array $allApps // TODO - add description to caps in db
	)
	{
	}

	public function respond(RespondArg $arg): mixed
	{
		if (!$arg->userCan('edit_security'))
			return new ErrorPage(401, 'Not authorized', "You don't have permission to edit groups.");

		$path = $arg->path;
		if ($path->count() > 1)
			return new NotFound();

		$action = $path->isRoot() ? 'select' : $path->at(0);

		switch ($action) {
			case 'select':
				return $this->select($arg);
			case 'edit':
				return $this->edit($arg);
			case 'create':
				return $this->create($arg);
			case 'save':
				return $this->save($arg);
			default:
				return new ErrorPage(404, 'Not Found', "Unknown action '$action'");
		}
	}

	private function save(RespondArg $arg): GroupSaveResponse
	{
		$req = $arg->parseBody(GroupSaveRequest::class);
		if (!$req)
			return new GroupSaveResponse(400, ['error' => 'Bad group format']);

		if (!$this->db->lock())
			return new GroupSaveResponse(503, [
				'errorMsg' => 'System unavailable'
			]);

		try
		{
			$current_group = $this->db->loadGroup($req->group->id);
			if (!$current_group)
				return new GroupSaveResponse(404, [
					'errorMsg' => "Group not found"
				]);

			$token = SaveToken::tryReserveEncoded(
				$arg->uid(),
				$current_group['save_token'],
				$req->saveKey
			);

			if (!$token)
			{
				$currentToken = SaveToken::decode($current_group['save_token']);
				$uname = $arg->username($currentToken->userId);
				return new GroupSaveResponse(409, [
					'errorMsg' => "This group was recently edited by '{$uname}' and the information you see may be inaccurate. You will not be able to edit this group until you successfully reload the page."
				]);
			}

			$req->group->save_token = $token->encode();
			$this->db->saveGroup($req->group, $error);

			return new GroupSaveResponse(200, ['newSaveKey' => $token->key]);
		}
		finally
		{
			$this->db->unlock();
		}
	}

	private function select(RespondArg $arg): mixed
	{
		$groups = $this->db->loadGroups();

		$arg->renderPage([
			'template' => __DIR__ . '/../template/group_select.php',
			'title' => 'Select a group',
			'args' => [
				'groups' => $groups
			]
		]);

		return null;
	}

	private function edit(RespondArg $arg): mixed
	{
		$id = \intval($_REQUEST['id'] ?? 0);

		if (!$this->db->lock())
			return self::systemUnavailable();

		try
		{
			$group = $this->db->loadGroup($id);
			if (!$group)
				return new NotFound();

			$token = SaveToken::tryReserveEncoded($arg->uid(), $group['save_token']);
			if (!$token)
			{
				$currentToken = SaveToken::decode($group['save_token']);
				$uname = $arg->username($currentToken->userId);
				return self::groupUnavailable($uname);
			}

			$group['save_token'] = $token->encode();
			$groupToSave = Group::fromArray($group);
			$this->db->saveGroup($groupToSave, $error);

			if ($error)
				return new ErrorPage(400, "Failed to save group", $error);

			$caps = $this->allCapabilities();

			$model = [
				'group' => $group,
				'capabilities' => $caps,
				'initialSaveKey' => $token->key
			];

			ReactPage::render($arg, [
				'title' => "Edit {$group['groupname']}",
				'scripts' => ['/assets/group_edit.js'],
				'model' => $model
			]);
			return null;
		}
		finally
		{
			$this->db->unlock();
		}
	}

	private function create(RespondArg $arg): mixed
	{
		if (!$this->db->lock())
		{
			// TODO - redirect to error page
			return new Redirect("/site/admin/groups");
		}

		try
		{
			$group = $this->db->createGroup();
			return new Redirect("/site/admin/groups/edit?id={$group['id']}");
		}
		finally
		{
			$this->db->unlock();
		}
	}

	private static function systemUnavailable(): ErrorPage
	{
		\header('Retry-After: 5');
		return new ErrorPage(503, 'System Unavailable', 'The system is currently too busy to allow editing users. Try again.');
	}

	private static function groupUnavailable(string $uname): ErrorPage
	{
		return new ErrorPage(
			errorCode: 409, 
			title: 'Group Unavailable',
			msg: "This group is being edited by '{$uname}'. Try again when the group is no longer being edited."
		);
	}

	private function allCapabilities(): array
	{
		$caps = $this->db->loadCapabilities();
		$apps = $this->allApps;
		$merged = [];

		$last_app = null;
		$app_caps = null;

		foreach ($caps as $id => $cap)
		{
			$app_key = $cap['app'];
			$cap_name = $cap['name'];

			if ($last_app !== $app_key)
			{
				$merged[$app_key] = [];
				$app_caps = $apps[$app_key]->capabilities();
				$last_app = $app_key;
			}

			\array_push($merged[$app_key], [
				'id' => $id,
				'app' => $app_key,
				'name' => $cap_name,
				'description' => $app_caps[$cap_name]['description']
			]);
		}

		return $merged;
	}

}

class GroupSaveResponse extends Responder
{
	public function __construct(
		private int $errorCode,
		private array $obj
	)
	{
	}

	public function respond(RespondArg $arg): mixed
	{
		\http_response_code($this->errorCode);
		\header('Content-Type: application/json');
		echo \json_encode($this->obj);
		return null;
	}
}

class GroupSaveRequest
{
	public Group $group;
	public string $saveKey;
}

