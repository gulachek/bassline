<?php

namespace Shell;

require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/Page.php';

class Shell
{
	public function __construct(
		private Page $page
	)
	{
	}

	public function title()
	{
		return $this->page->title();
	}

	public function stylesheets()
	{
		yield '/static/main.css';

		foreach ($this->page->stylesheets() as $style)
			yield $style;
	}

	public function mainBody()
	{
		return $this->page->body();
	}

	public function username()
	{
		return null;
	}
}
