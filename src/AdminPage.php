<?php

namespace Shell;

require_once __DIR__ . '/Page.php';
class AdminPage extends Page
{
	public function __construct(
		private Config $config
	)
	{
	}
	
	public function title(): string
	{
		return 'Admin';
	}

	public function body(): void
	{
		include __DIR__ . '/../template/admin_page.php';
	}

	public function stylesheets(): array
	{
		return ['/static/admin_page.css'];
	}
}
