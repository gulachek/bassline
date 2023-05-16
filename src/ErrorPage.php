<?php

namespace Gulachek\Bassline;

class ErrorPage extends Responder
{
	public function __construct(
		public readonly int $errorCode = 400,
		public readonly string $title = 'Error',
		public readonly string $msg = 'An error occurred.'
	)
	{
	}
	
	public function respond(RespondArg $arg): mixed
	{
		\http_response_code($this->errorCode);
		return $arg->renderPage(
			title: $this->title,
			template: __DIR__ . '/../template/error_page.php',
			args: [
				'msg' => $this->msg,
				'title' => $this->title
			]
		);
	}
}
