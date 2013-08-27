<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Email module. Based on Swift Mailer.
 * @see https://github.com/swiftmailer/swiftmailer
 * 
 * @abstract
 * @package   Email
 * @category  Base
 * @author    WinterSilence
 * @author    Alexey Popov
 * @author    Kohana Team
 * @copyright (c) 2009-2013 Kohana Team
 * @license   http://kohanaphp.com/license.html
 */
abstract class Kohana_Email
{
	/**
	 * Default instance name
	 *
	 * @static
	 * @var string
	 */
	public static $default = 'default';
	
	/**
	 * Database instances
	 *
	 * @static
	 * @var array
	 */
	public static $instances = array();
	
	/**
	 * Instance name
	 *
	 * @var string
	 */
	protected $_instance;

	/**
	 * Raw server connection
	 * 
	 * @var mixed
	 */
	protected $_connection;

	/**
	 * Configuration array
	 *
	 * @var array
	 */
	protected $_config;
	
	/**
	 * Email sender
	 *
	 * @var mixed
	 */
	protected $_from;
	
	/**
	 * Attached files array
	 *
	 * @var array
	 */
	protected $_attachments = NULL;
	
	/**
	 * Reply-to data
	 *
	 * @var mixed
	 */
	protected $_reply_to;
	
	/**
	 * Array of receivers
	 *
	 * @var array
	 */
	protected $_to = array();
	
	/**
	 * Array of receivers
	 *
	 * @var array
	 */
	protected $_cc = array();
	
	/**
	 * Array of receivers
	 *
	 * @var array
	 */
	protected $_bcc = array();
	
	/**
	 * Email subject
	 *
	 * @var string
	 */
	protected $_subject;
	
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
	protected $_html = TRUE;
	
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
	 * @static
	 * @access public
	 * @param  string $name   Instance name
	 * @param  array  $config Configuration parameters
	 * @return Email
	 * @throw  Kohana_Exception
	 * @uses   Kohana::$config->load
	 */
	public static function instance($name = NULL, array $config = NULL)
	{
		if ( ! $name)
		{
			// Use the default instance name
			$name = self::$default;
		}
		
		if ( ! isset(self::$instances[$name]))
		{
			if (is_null($config))
			{
				// Load the configuration for this email instance
				$config = Kohana::$config->load('email')->as_array();
			}
			if ( ! isset($config[$name]))
			{
				throw new Kohana_Exception('Email configuration :name is not defined', array(':name' => $name));
			}
			// Store the email instance
			self::$instances[$name] = new Email($name, $config[$name]);
		}
		
		return self::$instances[$name];
	}

	/**
	 *
	 *
	 * @access protected
	 * @return  void
	 */
	protected function __clone() {}

	/**
	 *
	 *
	 * @access protected
	 * @return  void
	 */
	protected function __wakeup() {}

	/**
	 * Stores the email configuration locally and name the instance.
	 *
	 * [!!] This method cannot be accessed directly, you must use [Email::instance].
	 *
	 * @access protected
	 * @param  string $name
	 * @param  array  $config
	 * @return void
	 * @uses   Kohana::find_file
	 * @uses   Swift_SmtpTransport::newInstance
	 * @uses   Swift_MailTransport::newInstance
	 * @uses   Swift_Mailer::newInstance
	 */
	protected function __construct($name, array $config)
	{
		// Set the instance name
		$this->_instance = $name;
		// Select & config driver
		switch ($this->_config['driver'])
		{
			case 'smtp':
				$options = $this->_config['options'];
				// Set port
				$port = empty($options['port']) ? 25 : (int) $options['port'];
				// Create SMTP Transport
				$transport = Swift_SmtpTransport::newInstance($options['hostname'], $port);
				// Set encryption
				if ( ! empty($options['encryption']))
				{
					$transport->setEncryption($options['encryption']);
				}
				// Do authentication, if part of the DSN
				empty($options['username']) OR $transport->setUsername($options['username']);
				empty($options['password']) OR $transport->setPassword($options['password']);
				// Set the timeout to 5 seconds
				$transport->setTimeout(empty($options['timeout']) ? 5 : (int) $options['timeout']);
				break;
			
			default:
				// Use the native connection
				$transport = Swift_MailTransport::newInstance($this->_config['options']);
				break;
		}
		$this->_connection = Swift_Mailer::newInstance($transport);
	}
	
	/**
	 * Specifies the email sender
	 *
	 * @param  string $email
	 * @param  mixed  $name
	 * @return Email
	 */
	public function from($email, $name = NULL)
	{
		$this->_from = $name ? $email : array($email => $name);
		
		return $this;
	}
	
	/**
	 * Specifies the reply-to field
	 *
	 * @param  string $email
	 * @param  mixed  $name
	 * @return Email
	 */
	public function reply_to($email, $name = NULL)
	{
		$this->_reply_to = $name ? $email : array($email => $name);
		
		return $this;
	}
	
	/**
	 * Adds a recipient
	 *
	 * @param  string $email
	 * @param  mixed  $name
	 * @return Email
	 */
	public function to($email, $name = NULL)
	{
		if ($name)
		{
			$this->_to[$email] = $name;
		}
		else
		{
			$this->_to[] = $email;
		}
		return $this;
	}
	
	/**
	 * Adds a recipient
	 *
	 * @param  string $email
	 * @param  mixed  $name
	 * @return Email
	 */
	public function cc($email, $name = NULL)
	{
		if ($name)
			$this->_cc[$email] = $name;
		else
			$this->_cc[] = $email;
		
		return $this;
	}
	
	/**
	 * Adds a recipient
	 *
	 * @param  string $email
	 * @param  string  $name
	 * @return Email
	 */
	public function bcc($email, $name = NULL)
	{
		if ($name)
			$this->_bcc[$email] = $name;
		else
			$this->_bcc[] = $email;
		
		return $this;
	}

	/**
	 * Sets\gets specifies the email subject
	 *
	 * @param  string $subject
	 * @param  string $i18n    Translate lang
	 * @return mixed
	 * @uses   I18n::get
	 */
	public function subject($subject = NULL, $i18n = NULL)
	{
		if ($subject)
		{
			// Translate subject
			if (Arr::get($this->_config, 'i18n', FALSE))
			{
				$subject = I18n::get($subject, $i18n);
			}
			$this->_subject = $subject;
			
			return $this;
		}
		return $this->_subject;
	}

	/**
	 * Sets\gets specifies the email message
	 *
	 * @param  string $message
	 * @param  array  $data
	 * @param  bool   $html
	 * @return mixed
	 */
	public function message($message = NULL, array $data = array(), $html = TRUE)
	{
		if ($message)
		{
			$this->_html = (bool) $html;
			// Use View as message\body
			if ($this->_html AND ! empty(Kohana::find_file('views', $message)))
			{
				$message = View::factory($message, $data)->render();
			}
			$this->_message = $message;
			
			return $this;
		}
		return $this->_message;
	}

	/**
	 * Message method alias
	 *
	 * @param  string  $message
	 * @param  array   $data
	 * @param  boolean $html
	 * @return mixed
	 */
	public function body($message = NULL, array $data = array(), $html = TRUE)
	{
		return $this->message($message, $data, $html);
	}

	/**
	 * Adds file as an attachment for email
	 *
	 * @param  string $file_path - path to file attachment
	 * @param  string $mime - mime type
	 * @param  mixed $file - filename to attach as
	 * @return Email
	 * @uses   Swift_Attachment::fromPath
	 */
	public function add_attachment_file($path, $mime, $file = NULL)
	{
		$attachment = Swift_Attachment::fromPath($path, $mime);
		if ($file)
		{
			 $attachment->setFilename($file);
		}
		$this->_attachments[] = $attachment;
		
		return $this;
	}

	/**
	 * Sends prepared email
	 *
	 * @return Email
	 * @uses   Swift_Message::newInstance
	 * @uses   Kohana::$charset
	 */
	public function send()
	{
		// Determine the message type
		$html = $this->_html ? 'text/html' : 'text/plain';
		
		$message = Swift_Message::newInstance($this->_subject, $this->_message, $html, Kohana::$charset);
		$message->setFrom($this->_from);
		
		foreach (array('to', 'cc', 'bcc') as $param)
		{
			if ($this->{'_'.$param})
			{
				$method = 'set'.ucfirst($param);
				$message->$method($this->{'_'.$param});
			}
		}
		
		if ($this->_reply_to)
		{
			$message->setReplyTo($this->_reply_to);
		}
		
		if ($this->_attachments)
		{
			foreach ($this->_attachments as $attachment)
			{
				$message->attach($attachment);
			}
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

} // End Email