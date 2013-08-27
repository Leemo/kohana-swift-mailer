# Email Module For Kohana 3.3

Based on Swift Mailer library.

## Installation

Edit your bootstrap file `APPPATH/bootstrap.php` and enable email module:

<pre>
Kohana::modules(array(
  // Some modules
  'email' => MODPATH.'email',
  // Some other modules
  ));
</pre>

Then copy `MODPATH/email/config/email.php` to `APPPATH/config/email.php`.

## Example of usage

<pre>
Email::instance()
	->from('sender@example.com')
	->to('first.recipient@example.com')
	->to('second.recipient@example.com', 'Mr. Recipient')
	->subject('Hi there!')
	->body('Hi, guys! This is my awesome email.')
	// or use View body: ->body('email/view/name', $message_data_array, TRUE)
	->send();
</pre>
