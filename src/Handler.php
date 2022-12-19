<?php

namespace Shell;

abstract class Handler
{
	abstract public function handleRequest(): Page | Redirect | null;
}
