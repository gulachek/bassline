<?php

namespace Shell;

abstract class Page
{
	abstract public function title();
	abstract public function body();

	public function stylesheets()
	{
		return [];
	}
}
