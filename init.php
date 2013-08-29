<?php defined('SYSPATH') OR die('No direct access allowed.');

// SwiftMailer autoloader
function swiftmailer_autoload($class)
{
	if ($class == 'Swift_Mailer')
	{
		require_once 'vendor'.DS.'swiftmailer'.DS.'swift_required'.EXT;
	}
}

spl_autoload_register('swiftmailer_autoload');
