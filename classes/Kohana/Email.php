<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Email module
 *
 * @package    Email
 * @author     Alexey Popov
 * @author     Kohana Team
 * @copyright  (c) 2009-2013 Leemo studio
 * @license    http://kohanaphp.com/license.html
 */
class Kohana_Email {

	/**
	 * Default instance name
	 *
	 * @var  string
	 */
	public static $default = 'default';

	/**
	 * Database instances
	 *
	 * @var  array
	 */
	public static $instances = array();

	/**
	 * Get a singleton Email instance. If configuration is not specified,
	 * it will be loaded from the email configuration file using the same
	 * group as the name.
	 *
	 *     // Load the instance with default config
	 *     $email = Email::instance();
	 *
	 *     // Create a custom configured instance
	 *     $email = Email::instance('custom', $config);
	 *
	 * @param   string   $name    instance name
	 * @param   array    $config  configuration parameters
	 * @return  Email
	 */
	public static function instance($name = NULL, array $config = NULL)
	{
		if ($name === NULL)
		{
			// Use the default instance name
			$name = Email::$default;
		}

		if ( ! isset(Email::$instances[$name]))
		{
			if ($config === NULL)
			{
				// Load the configuration for this email instance
				$config = Kohana::$config->load('email')
					->as_array();
			}

			if ( ! isset($config[$name]))
			{
				throw new Kohana_Exception(':name configuration is not defined',
					array(':name' => $name));
			}

			// Store the email instance
			Email::$instances[$name] = new Email($name, $config[$name]);
		}

		return Email::$instances[$name];
	}

	/**
	 * Instance name
	 *
	 * @var string
	 */
	protected $_instance;

	/**
	 * Raw server connection
	 */
	protected $_connection;

	/**
	 * Configuration array
	 *
	 * @var array
	 */
	protected $_config;

	/**
	 * Stores the email configuration locally and name the instance.
	 *
	 * [!!] This method cannot be accessed directly, you must use [Email::instance].
	 *
	 * @return  void
	 */
	public function __construct($name, array $config)
	{
		if ( ! class_exists('Swift_Mailer', FALSE))
		{
			// Load SwiftMailer
			require Kohana::find_file('vendor', 'swiftmailer/lib/swift_required');
		}

		// Set the instance name
		$this->_instance = $name;

		// Store the config locally
		$this->_config = $config;

		switch ($config['driver'])
		{
			case 'smtp':
				// Set port
				$port = empty($config['options']['port']) ? 25 : (int) $config['options']['port'];

				// Create SMTP Transport
				$transport = Swift_SmtpTransport::newInstance($config['options']['hostname'], $port);

				if ( ! empty($config['options']['encryption']))
				{
					// Set encryption
					$transport->setEncryption($config['options']['encryption'])
						->setStreamOptions(array('ssl' => array('verify_peer' => FALSE, 'verify_peer_name' => FALSE)));
				}

				// Do authentication, if part of the DSN
				empty($config['options']['username']) OR $transport->setUsername($config['options']['username']);
				empty($config['options']['password']) OR $transport->setPassword($config['options']['password']);

				// Set the timeout to 5 seconds
				$transport->setTimeout(empty($config['options']['timeout']) ? 5 : (int) $config['options']['timeout']);
				break;

			default:
				// Use the native connection
				$transport = Swift_MailTransport::newInstance($config['options']);
				break;
		}

		$this->_connection = Swift_Mailer::newInstance($transport);
	}

	/**
	 * Email sender
	 *
	 * @var mixed
	 */
	protected $_from;

	/**
	 * Specifies the email sender
	 *
	 * @param   string  $email
	 * @param   string  $name
	 * @return  Email
	 */
	public function from($email, $name = NULL)
	{
		$this->_from = (empty($name)) ? $email : array($email => $name);

		return $this;
	}

	/**
	 * Reply-to data
	 *
	 * @var mixed
	 */
	protected $_reply_to;

	/**
	 * Specifies the reply-to field
	 *
	 * @param   string  $email
	 * @param   string  $name
	 * @return  Email
	 */
	public function reply_to($email, $name = NULL)
	{
		$this->_reply_to = (empty($name)) ? $email : array($email => $name);

		return $this;
	}

	/**
	 * Array of receivers
	 *
	 * @var array
	 */
	protected $_to = array();

	/**
	 * Adds a recipient
	 *
	 * @param   string  $email
	 * @param   string  $name
	 * @return  Email
	 */
	public function to($email, $name = NULL)
	{
		if (empty($name))
		{
			$this->_to[] = $email;
		}
		else
		{
			$this->_to[$email] = $name;
		}

		return $this;
	}

	/**
	 * Array of receivers
	 *
	 * @var array
	 */
	protected $_cc = array();

	/**
	 * Adds a recipient
	 *
	 * @param   string  $email
	 * @param   string  $name
	 * @return  Email
	 */
	public function cc($email, $name = NULL)
	{
		if (empty($name))
		{
			$this->_cc[] = $email;
		}
		else
		{
			$this->_cc[$email] = $name;
		}

		return $this;
	}

	/**
	 * Array of receivers
	 *
	 * @var array
	 */
	protected $_bcc = array();

	/**
	 * Adds a recipient
	 *
	 * @param   string  $email
	 * @param   string  $name
	 * @return  Email
	 */
	public function bcc($email, $name = NULL)
	{
		if (empty($name))
		{
			$this->_bcc[] = $email;
		}
		else
		{
			$this->_bcc[$email] = $name;
		}

		return $this;
	}

	/**
	 * Email subject
	 *
	 * @var string
	 */
	protected $_subject;

	/**
	 * Specifies the email subject
	 *
	 * @param  string $subject
	 * @return Email
	 */
	public function subject($subject)
	{
		$this->_subject = $subject;

		return $this;
	}

	/**
	 * Email message content
	 *
	 * @var string
	 */
	protected $_message;

	/**
	 * Message type identifier
	 *
	 * @var boolean
	 */
	protected $_html;

	/**
	 * Specifies the email message
	 *
	 * @param  string  $message
	 * @param  boolean $html
	 * @return Email
	 */
	public function message($message, $html = FALSE)
	{
		$this->_message = $message;
		$this->_html    = (bool) $html;

		return $this;
	}

	/**
	 * Sends prepared email
	 *
	 * @return void
	 */
	public function send()
	{
		// Determine the message type
		$html = ($this->_html) ? 'text/html' : 'text/plain';

		$message = Swift_Message::newInstance($this->_subject, $this->_message, $html, 'utf-8')
			->setFrom($this->_from);

		foreach (array('to', 'cc', 'bcc') as $param)
		{
			if (sizeof($this->{'_'.$param}) > 0)
			{
				$method = 'set'.UTF8::ucfirst($param);

				$message->$method($this->{'_'.$param});
			}
		}

		if ( ! empty($this->_reply_to))
		{
			$message->setReplyTo($this->_reply_to);
		}

		// Send message
		$this->_connection->send($message);

		return $this;
	}

	/**
	 * Reset all recipients (to, cc, bcc rows)
	 *
	 * @return Email
	 */
	public function reset()
	{
		foreach (array('to', 'cc', 'bcc') as $param)
		{
			$this->{'_'.$param} = array();
		}

		return $this;
	}

} // End Kohana_Email