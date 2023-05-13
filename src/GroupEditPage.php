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
		$group = $arg->parseBody(Group::class);
		if (!$group)
			return new GroupSaveResponse(400, ['error' => 'Bad group format']);

		$this->db->saveGroup($group, $error);

		return new GroupSaveResponse(200, ['error' => null]);
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
		$id = intval($_REQUEST['id']);
		$group = $this->db->loadGroup($id);
		if (!$group)
			return new NotFound();

		$caps = $this->allCapabilities();

		$model = [
			'group' => $group,
			'capabilities' => $caps
		];

		ReactPage::render($arg, [
			'title' => "Edit {$group['groupname']}",
			'scripts' => ['/assets/group_edit.js'],
			'model' => $model
		]);
		return null;
	}

	private function create(RespondArg $arg): mixed
	{
		$group = $this->db->createGroup();
		return new Redirect("/site/admin/groups/edit?id={$group['id']}");
	}

	private static function systemUnavailable(): ErrorPage
	{
		\header('Retry-After: 5');
		return new ErrorPage(503, 'System Unavailable', 'The system is currently too busy to allow editing users. Try again.');
	}

	private static function userUnavailable(string $uname): ErrorPage
	{
		return new ErrorPage(
			errorCode: 409, 
			title: 'User Unavailable',
			msg: "This user is being edited by '{$uname}'. Try again when the user is no longer being edited."
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
	public string $key;
}

