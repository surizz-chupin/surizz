<?php
/**
 * PHPMailer RFC821 SMTP email transport class.
 * PHP Version 5.5.
 *
 * @see       https://github.com/PHPMailer/PHPMailer/ The PHPMailer GitHub project
 *
 * @author    Marcus Bointon (Synchro/coolbru) <phpmailer@synchromedia.co.uk>
 * @author    Jim Jagielski (jimjag) <jimjag@gmail.com>
 * @author    Andy Prevost (codeworxtech) <codeworxtech@users.sourceforge.net>
 * @author    Brent R. Matzelle (original founder)
 * @copyright 2012 - 2020 Marcus Bointon
 * @copyright 2010 - 2012 Jim Jagielski
 * @copyright 2004 - 2009 Andy Prevost
 * @license   http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @note      This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace PHPMailer\PHPMailer;

/**
 * PHPMailer RFC821 SMTP email transport class.
 * Implements RFC 821 SMTP commands and provides methods to send emails using SMTP.
 *
 * @author Marcus Bointon <phpmailer@synchromedia.co.uk>
 * @author Jim Jagielski <jimjag@gmail.com>
 * @author Andy Prevost <codeworxtech@users.sourceforge.net>
 */
class SMTP
{
    /**
     * The PHPMailer SMTP version number.
     *
     * @var string
     */
    const VERSION = '6.6.0';

    /**
     * SMTP line break constant.
     *
     * @var string
     */
    const LE = "\r\n";

    /**
     * The SMTP port to use if one is not specified.
     *
     * @var int
     */
    const DEFAULT_PORT = 587;

    /**
     * The maximum line length allowed by RFC 5321 section 4.5.3.1.6,
     * *excluding* a trailing CRLF break.
     *
     * @var int
     */
    const MAX_LINE_LENGTH = 998;

    /**
     * The maximum line length allowed for replies in RFC 5321 section 4.5.3.1.5,
     * *excluding* a trailing CRLF break.
     *
     * @var int
     */
    const MAX_REPLY_LENGTH = 512;

    /**
     * Debug level for no output.
     *
     * @var int
     */
    const DEBUG_OFF = 0;

    /**
     * Debug level to show client -> server messages.
     *
     * @var int
     */
    const DEBUG_CLIENT = 1;

    /**
     * Debug level to show client -> server and server -> client messages.
     *
     * @var int
     */
    const DEBUG_SERVER = 2;

    /**
     * Debug level to show connection status, client -> server and server -> client messages.
     *
     * @var int
     */
    const DEBUG_CONNECTION = 3;

    /**
     * Debug level to show all messages.
     *
     * @var int
     */
    const DEBUG_LOWLEVEL = 4;

    /**
     * Authentication methods.
     * Basic authentication.
     *
     * @var string
     */
    const AUTH_PLAIN = 'PLAIN';

    /**
     * Authentication methods.
     * Login authentication.
     *
     * @var string
     */
    const AUTH_LOGIN = 'LOGIN';

    /**
     * Authentication methods.
     * CRAM-MD5 authentication.
     *
     * @var string
     */
    const AUTH_CRAM_MD5 = 'CRAM-MD5';

    /**
     * SMTP response error code.
     *
     * @var int
     */
    const SMTP_REPLIED_OK = 250;

    /**
     * The socket for the server connection.
     *
     * @var ?resource
     */
    protected $smtp_conn;

    /**
     * Error information, if any, for the last SMTP command.
     *
     * @var array
     */
    protected $error = [
        'error' => '',
        'detail' => '',
        'smtp_code' => '',
        'smtp_code_ex' => '',
    ];

    /**
     * The reply the server sent to us for HELO.
     * If null, no HELO string has yet been received.
     *
     * @var string|null
     */
    protected $helo_rply;

    /**
     * The set of SMTP extensions sent in reply to EHLO command.
     * Indexes of the array are extension names.
     * Value at index 'HELO' or 'EHLO' (according to command that was sent)
     * represents the server name. In case of HELO it is the only element of the array.
     * Other values can be boolean TRUE or an array containing extension options.
     * If null, no HELO/EHLO string has yet been received.
     *
     * @var array|null
     */
    protected $server_caps;

    /**
     * The most recent reply received from the server.
     *
     * @var string
     */
    protected $last_reply = '';

    /**
     * Output debugging info via a user-selected method.
     *
     * @var callable
     */
    protected $Debugoutput;

    /**
     * How to handle debug output.
     * Options:
     * * `echo` Output plain-text as-is, appropriate for CLI
     * * `html` Output escaped, line breaks converted to `<br>`, appropriate for browser output
     * * `error_log` Output to error log as configured in php.ini
     * Alternatively, you can provide a callable expecting two params: a message string and the debug level:
     *
     * ```php
     * $smtp->Debugoutput = function($str, $level) {echo "debug level $level; message: $str";};
     * ```
     *
     * @var string|callable
     */
    public $Debugoutput = 'echo';

    /**
     * Whether to use VERP.
     *
     * @see http://en.wikipedia.org/wiki/Variable_envelope_return_path
     * @see http://www.postfix.org/VERP_README.html
     *
     * @var bool
     */
    public $do_verp = false;

    /**
     * The timeout value for connection, in seconds.
     * Default of 5 minutes (300sec) is from RFC2821 section 4.5.3.2.
     * This needs to be quite high to function correctly with hosts using greetdelay as an anti-spam measure.
     *
     * @see http://tools.ietf.org/html/rfc2821#section-4.5.3.2
     *
     * @var int
     */
    public $Timeout = 300;

    /**
     * How long to wait for commands to complete, in seconds.
     * Default of 5 minutes (300sec) is from RFC2821 section 4.5.3.2.
     *
     * @var int
     */
    public $Timelimit = 300;

    /**
     * Patterns to extract an SMTP transaction id from reply to a DATA command.
     * The first capture group in each regex will be used as the ID.
     * MS ESMTP returns the message ID, which may not be correct for internal tracking.
     *
     * @var string[]
     */
    protected $smtp_transaction_id_patterns = [
        'exim' => '/[\d]{3} OK id=(.*)/',
        'sendmail' => '/[\d]{3} 2.0.0 (.*) Message/',
        'postfix' => '/[\d]{3} 2.0.0 Ok: queued as (.*)/',
        'Microsoft_ESMTP' => '/[0-9]{3} 2.[\d].0 (.*)@(?:.*) Queued mail for delivery/',
        'Amazon_SES' => '/[\d]{3} Ok (.*)/',
        'SendGrid' => '/[\d]{3} Ok: queued as (.*)/',
        'CampaignMonitor' => '/[\d]{3} 2.0.0 OK:([a-zA-Z\d]{48})/',
        'Haraka' => '/[\d]{3} Message Queued \((.*)\)/',
    ];

    /**
     * The SMTP host to connect to.
     *
     * @var string
     */
    public $Host = 'localhost';

    /**
     * The SMTP username to use if POP before SMTP is enabled.
     *
     * @var string
     */
    public $Username = '';

    /**
     * The SMTP password to use if POP before SMTP is enabled.
     *
     * @var string
     */
    public $Password = '';

    /**
     * The SMTP port number to connect to.
     *
     * @var int
     */
    public $Port = 25;

    /**
     * The SMTP HELO/EHLO name used for the SMTP connection.
     * Default is $Hostname. If your server is on a dedicated IP, this should be the hostname of the server.
     * If your server shares an IP, use the name of the virtual host taking the connection (like in Apache config).
     *
     * @var string
     */
    public $Helo = '';

    /**
     * The hostname to use in HELO command.
     * If empty, the value passed to server with HELO was the client's IP address.
     * If your server doesn't have a hostname, you must set this to an IP address.
     *
     * @var string
     */
    public $Hostname = '';

    /**
     * An implementation of the PHPMailer OAuthTokenProvider interface.
     *
     * @var OAuthTokenProvider
     */
    protected $oauth;

    /**
     * The SMTP authentication method to use.
     * Options are CRAM-MD5, LOGIN, PLAIN, XOAUTH2.
     * If not specified, the first one from that list that the server supports will be selected.
     *
     * @var string
     */
    public $AuthType = '';

    /**
     * SMTP authentication username.
     *
     * @var string
     */
    public $Username = '';

    /**
     * SMTP authentication password.
     *
     * @var string
     */
    public $Password = '';

    /**
     * SMTP authentication OAuth token.
     *
     * @var string
     */
    public $OAuth = '';

    /**
     * If true, send SMTP commands in debug output.
     *
     * @var bool
     */
    public $do_debug = false;

    /**
     * Provides the ability to have the TO command process individual recipients.
     * If true, will call sendOrMail multiple times per message in order to
     * send to addresses added by addAnAddress, rather than just send to the
     * one address added with setFrom.
     *
     * @var bool
     */
    protected $smtpSendEachTo = false;

    /**
     * Provides the ability to have the TO command process individual recipients.
     * If true, will call sendOrMail multiple times per message in order to
     * send to addresses added by addAnAddress, rather than just send to the
     * one address added with setFrom.
     *
     * @deprecated Use $smtpSendEachTo instead
     *
     * @var bool
     */
    public $SingleTo = false;

    /**
     * The function/method to use for debugging output.
     *
     * @var string
     */
    protected $Debugoutput = 'echo';

    /**
     * The SMTP timeout value for reads, in seconds.
     *
     * @var int
     */
    protected $Timeout = 180;

    /**
     * Whether to enable TLS encryption automatically if the server supports it,
     * even if `SMTPSecure` is not set to 'tls'.
     *
     * @var bool
     */
    public $autoTls = true;

    /**
     * Whether to use SMTP authentication.
     *
     * @var bool
     */
    public $useAuth = true;

    /**
     * The secure connection prefix.
     *
     * @var string
     */
    public $SMTPSecure = '';

    /**
     * Options array passed to stream_context_create when connecting.
     *
     * @var array
     */
    public $SMTPOptions = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->Debugoutput = 'echo';
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->smtpClose();
    }

    /**
     * Output debugging info via a user-selected method.
     *
     * @param string $str   Debug string to output
     * @param int    $level The debug level of this message; see DEBUG_* constants
     *
     * @see SMTP::$Debugoutput
     * @see SMTP::$do_debug
     */
    protected function edebug($str, $level = 0)
    {
        if ($level > $this->do_debug) {
            return;
        }
        //Is this a PSR-3 logger?
        if ($this->Debugoutput instanceof \Psr\Log\LoggerInterface) {
            $this->Debugoutput->debug($str);

            return;
        }
        //Avoid clash with built-in function names
        if (is_callable($this->Debugoutput) && !in_array($this->Debugoutput, ['error_log', 'html', 'echo'])) {
            call_user_func($this->Debugoutput, $str, $level);

            return;
        }
        switch ($this->Debugoutput) {
            case 'error_log':
                //Don't output, just log
                error_log($str);
                break;
            case 'html':
                //Cleans up output a bit for a better looking, HTML-safe output
                echo gmdate('Y-m-d H:i:s'), ' ', htmlentities(
                    preg_replace('/[\r\n]+/', '', $str),
                    ENT_QUOTES,
                    'UTF-8'
                ), "<br>\n";
                break;
            case 'echo':
            default:
                //Normalize line breaks
                $str = preg_replace('/\r\n|\r/m', "\n", $str);
                echo gmdate('Y-m-d H:i:s'),
                "\t",
                    //Trim trailing space
                trim(
                    //Indent for readability, except for trailing break
                    str_replace(
                        "\n",
                        "\n                   \t                  ",
                        trim($str)
                    )
                ),
                "\n";
        }
    }

    /**
     * Connect to an SMTP server.
     *
     * @param string $host    SMTP server IP or host name
     * @param int    $port    The port number to connect to
     * @param int    $timeout How long to wait for the connection to open
     * @param array  $options An array of options for stream_context_create()
     *
     * @return bool
     */
    public function connect($host, $port = null, $timeout = 30, $options = [])
    {
        //Clear errors to avoid confusion
        $this->setError('');
        //Make sure we are __not__ connected
        if ($this->connected()) {
            //Already connected, generate error
            $this->setError('Already connected to a server');

            return false;
        }
        if (empty($port)) {
            $port = self::DEFAULT_PORT;
        }
        //Connect to the SMTP server
        $this->edebug(
            "Connection: opening to $host:$port, timeout=$timeout, options=" .
            (count($options) > 0 ? var_export($options, true) : 'array()'),
            self::DEBUG_CONNECTION
        );

        $errno = 0;
        $errstr = '';
        if ($this->SMTPOptions === []) {
            $socket_context = stream_context_create($options);
            set_error_handler([$this, 'errorHandler']);
            $this->smtp_conn = stream_socket_client(
                $host . ':' . $port,
                $errno,
                $errstr,
                $timeout,
                STREAM_CLIENT_CONNECT,
                $socket_context
            );
            restore_error_handler();
        } else {
            //Use SMTP extensions context
            $socket_context = stream_context_create($this->SMTPOptions);
            set_error_handler([$this, 'errorHandler']);
            $this->smtp_conn = stream_socket_client(
                $host . ':' . $port,
                $errno,
                $errstr,
                $timeout,
                STREAM_CLIENT_CONNECT,
                $socket_context
            );
            restore_error_handler();
        }

        //Verify we connected properly
        if (!is_resource($this->smtp_conn)) {
            $this->setError(
                'Failed to connect to server',
                '',
                (string) $errno,
                (string) $errstr
            );
            $this->edebug(
                'SMTP ERROR: ' . $this->error['error']
                . ": $errstr ($errno)",
                self::DEBUG_CLIENT
            );

            return false;
        }

        //SMTP server can take longer to respond, give longer timeout for first read
        //Windows does not have support for this timeout function
        if (strpos(PHP_OS, 'WIN') !== 0) {
            $max = (int) ini_get('max_execution_time');
            //Don't bother if unlimited, or if set_time_limit is disabled
            if (0 !== $max && $timeout > $max && false !== set_time_limit($timeout)) {
                @set_time_limit($timeout);
            }
            stream_set_timeout($this->smtp_conn, $timeout, 0);
        }

        //Get any announcement
        $announce = $this->get_lines();
        $this->edebug('SERVER -> CLIENT: ' . $announce, self::DEBUG_SERVER);

        return true;
    }

    /**
     * Initiate a TLS (encrypted) session.
     *
     * @return bool
     */
    public function startTLS()
    {
        if (!$this->sendCommand('STARTTLS', 'STARTTLS', 220)) {
            return false;
        }

        //Allow the best TLS version(s) we can
        $crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT;

        //PHP 5.6.7 dropped inclusion of TLS 1.1 and 1.2 in STREAM_CRYPTO_METHOD_TLS_CLIENT
        //so add them back in manually if we can
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
        }

        //Begin encrypted connection
        set_error_handler([$this, 'errorHandler']);
        $crypto_ok = stream_socket_enable_crypto(
            $this->smtp_conn,
            true,
            $crypto_method
        );
        restore_error_handler();

        return (bool) $crypto_ok;
    }

    /**
     * Perform SMTP authentication.
     * Must be run after hello().
     *
     * @see    hello()
     *
     * @param string $username The user name
     * @param string $password The password
     * @param string $authtype The auth type (CRAM-MD5, PLAIN, LOGIN, XOAUTH2)
     * @param OAuthTokenProvider $OAuth An optional OAuthTokenProvider
     *
     * @return bool True if successfully authenticated
     */
    public function authenticate(
        $username,
        $password,
        $authtype = null,
        $OAuth = null
    ) {
        if (!$this->server_caps) {
            $this->setError('Authentication is not allowed before HELO/EHLO');

            return false;
        }

        if (array_key_exists('AUTH', $this->server_caps)) {
            //If the server doesn't support authentication, nothing to do
            if (empty($this->server_caps['AUTH'])) {
                $this->setError('Authentication is not supported by the server');

                return false;
            }

            //If authentication types are explicitly listed, check them
            if (
                is_array($this->server_caps['AUTH']) &&
                !in_array($authtype, $this->server_caps['AUTH'], true) &&
                !(
                    null === $authtype &&
                    (
                        (in_array(self::AUTH_PLAIN, $this->server_caps['AUTH'], true) &&
                            in_array(self::AUTH_LOGIN, $this->server_caps['AUTH'], true))
                    )
                )
            ) {
                //Request auth type not available
                $this->setError('Requested authentication type "' . $authtype . '" is not supported by the server');

                return false;
            }

            if (null === $authtype) {
                //If no auth mechanism is specified, attempt to use the most secure
                //one that the server supports
                if (in_array(self::AUTH_CRAM_MD5, $this->server_caps['AUTH'], true)) {
                    $authtype = self::AUTH_CRAM_MD5;
                } elseif (in_array(self::AUTH_PLAIN, $this->server_caps['AUTH'], true)) {
                    $authtype = self::AUTH_PLAIN;
                } elseif (in_array(self::AUTH_LOGIN, $this->server_caps['AUTH'], true)) {
                    $authtype = self::AUTH_LOGIN;
                }
            }

            $this->edebug('Auth method requested: ' . ($authtype ?: 'UNSPECIFIED'), self::DEBUG_LOWLEVEL);
            $this->edebug(
                'Auth methods available on the server: ' . implode(',', $this->server_caps['AUTH']),
                self::DEBUG_LOWLEVEL
            );

            switch ($authtype) {
                case self::AUTH_CRAM_MD5:
                    return $this->authCRAMMD5($username, $password);
                case self::AUTH_PLAIN:
                    return $this->authPLAIN($username, $password);
                case self::AUTH_LOGIN:
                    return $this->authLogin($username, $password);
                case 'XOAUTH2':
                    return $this->authXOAuth2($username, $OAuth);
                default:
                    $this->setError("Authentication method \"$authtype\" is not supported");

                    return false;
            }
        }

        return false;
    }

    /**
     * Get the response from the SMTP server.
     *
     * @param int $code      Expected response code
     * @param string $command The command that was sent that is resulting in this response
     *
     * @return bool
     */
    protected function handleResponse($code, $command)
    {
        $response = $this->getResponse();
        if (null === $response) {
            return false;
        }

        $isSuccess = (int) ($response['code'] / 100) === (int) ($code / 100);
        if (!$isSuccess) {
            $this->setError(
                "$command command failed",
                $response['fctext'],
                (string) $response['code'],
                $response['ctext']
            );
        }

        return $isSuccess;
    }

    /**
     * Send an SMTP DATA command.
     * Issues a data command and sends the msg_data to the server,
     * finializing the mail transaction. $msg_data is the message
     * that is to be send with the headers. Each header needs to be
     * on a single line followed by a <CRLF> with the message headers
     * and the message body being separated by an additional <CRLF>.
     * Implements RFC 821: DATA <CRLF>.
     *
     * @param string $msg_data Message data to send
     *
     * @return bool
     */
    public function data($msg_data)
    {
        //This will use the standard timelimit
        if (!$this->sendCommand('DATA', 'DATA', 354)) {
            return false;
        }

        /* The server is ready to accept data!
         * According to rfc821 we should not send more than 1000 characters on a single line (including the LE)
         * so we will break the data up into lines by \r and/or \n then if needed we will break each of those into
         * smaller lines to fit within the limit.
         * We will also look for lines that start with a '.' and prepend an additional '.'.
         * NOTE: this does not count towards line-length limit.
         */

        //Normalize line breaks before exploding
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $msg_data));

        /* To distinguish between a complete RFC822 message and a plain message body, we check if the first field
         * of the first line (':' separated) does not contain a space then it _should_ be a header and we will
         * process all lines before a blank line as headers.
         */

        $field = substr($lines[0], 0, strpos($lines[0], ':'));
        $in_headers = false;
        if (!empty($field) && strpos($field, ' ') === false) {
            $in_headers = true;
        }

        foreach ($lines as $line) {
            $lines_out = [];
            if ($in_headers && $line === '') {
                $in_headers = false;
            }
            //Break this line up into several smaller lines if it's too long
            //Micro-optimisation: isset($str[$max]) is faster than (strlen($str) > $max)
            while (isset($line[self::MAX_LINE_LENGTH])) {
                //Working backwards, try to find a space within the last MAX_LINE_LENGTH chars of the line to break on
                //so as to avoid breaking in the middle of a word
                $pos = (int) strrpos(substr($line, 0, self::MAX_LINE_LENGTH), ' ');

                //Deliberately matches both false and 0
                if (!$pos) {
                    //No nice break found, add a hard break
                    $pos = self::MAX_LINE_LENGTH - 1;
                    $lines_out[] = substr($line, 0, $pos);
                    $line = substr($line, $pos);
                } else {
                    //Break at the found point
                    $lines_out[] = substr($line, 0, $pos);
                    $line = substr($line, $pos + 1);
                }
                //If processing headers add a LWSP-char to the front of new line RFC822 section 3.1.1
                if ($in_headers) {
                    if ($line !== '' && strpos($line, ' ') !== 0) {
                        $line = ' ' . $line;
                    }
                }
            }
            $lines_out[] = $line;

            //Send the lines to the server
            foreach ($lines_out as $line_out) {
                //Dot-stuffing as per RFC5321 section 4.5.2
                //https://tools.ietf.org/html/rfc5321#section-4.5.2
                if (!empty($line_out) && $line_out[0] === '.') {
                    $line_out = '.' . $line_out;
                }
                $this->client_send($line_out . static::LE, 'DATA');
            }
        }

        //Message data has been sent, complete the command
        //Increase timelimit for end of DATA command
        $savetimelimit = $this->Timelimit;
        $this->Timelimit *= 2;
        $result = $this->sendCommand('DATA END', '.', 250);
        $this->Timelimit = $savetimelimit;

        return $result;
    }

    /**
     * Send an SMTP HELO or EHLO command.
     * Used to identify the sending server to the receiving server.
     * This makes sure that client and server are in a known state.
     * Implements RFC 821: HELO <SP> <domain> <CRLF>
     * and RFC 2821 EHLO.
     *
     * @param string $host The host name or IP to connect to
     *
     * @return bool
     */
    public function hello($host = '')
    {
        //Try extended hello first (RFC 2821)
        if ($this->sendHello('EHLO', $host)) {
            return true;
        }

        //Some servers shut down the SMTP service here (RFC 5321)
        if (substr($this->helo_rply, 0, 3) == '421') {
            return false;
        }

        return $this->sendHello('HELO', $host);
    }

    /**
     * Send an SMTP HELO or EHLO command.
     * Low-level implementation used by hello().
     *
     * @param string $hello The HELO string
     * @param string $host  The hostname to say we are
     *
     * @return bool
     *
     * @see    hello()
     */
    protected function sendHello($hello, $host)
    {
        $noerror = $this->sendCommand($hello, $hello . ' ' . $host, 250);
        $this->helo_rply = $this->last_reply;
        if ($noerror) {
            $this->parseHelloFields($hello);
        } else {
            $this->server_caps = null;
        }

        return $noerror;
    }

    /**
     * Parse a reply to HELO/EHLO command to discover server extensions.
     * In case of HELO, the only parameter that can be discovered is a server name.
     *
     * @param string $type `HELO` or `EHLO`
     */
    protected function parseHelloFields($type)
    {
        $this->server_caps = [];
        $lines = explode("\n", $this->helo_rply);

        foreach ($lines as $n => $s) {
            //First 4 chars contain response code followed by - or space
            $s = trim(substr($s, 4));
            if (empty($s)) {
                continue;
            }
            $fields = explode(' ', $s);
            if (!empty($fields)) {
                if (!$n) {
                    $name = $type;
                    $fields = $fields[0];
                } else {
                    $name = array_shift($fields);
                    switch ($name) {
                        case 'SIZE':
                            $fields = ($fields ? $fields[0] : 0);
                            break;
                        case 'AUTH':
                            if (is_array($fields)) {
                                if ($fields) {
                                    $fields = array_unique($fields);
                                } else {
                                    $fields = [];
                                }
                            }
                            break;
                        default:
                            $fields = true;
                    }
                }
                $this->server_caps[$name] = $fields;
            }
        }
    }

    /**
     * Send an SMTP MAIL command.
     * Starts a mail transaction from the given sender address.
     * Returns true if successful or false otherwise. If True
     * the mail transaction is started and then one or more recipient
     * commands may be called followed by a data command.
     * Implements RFC 821: MAIL <SP> FROM:<reverse-path> <CRLF>.
     *
     * @param string $from Source address of this message
     *
     * @return bool
     */
    public function mail($from)
    {
        $useVerp = ($this->do_verp ? ' XVERP' : '');

        return $this->sendCommand(
            'MAIL FROM',
            'MAIL FROM:<' . $from . '>' . $useVerp,
            250
        );
    }

    /**
     * Send an SMTP QUIT command.
     * Closes the socket if there is no error or the $close_on_error argument is true.
     * Implements from RFC 821: QUIT <CRLF>.
     *
     * @param bool $close_on_error Should close connection if there is an error
     *
     * @return bool
     */
    public function quit($close_on_error = true)
    {
        $noerror = $this->sendCommand('QUIT', 'QUIT', 221);
        $err = $this->error; //Save any error
        if ($noerror || $close_on_error) {
            $this->close();
        }
        return $noerror;
    }

    /**
     * Send an SMTP RCPT command.
     * Sets the TO argument to $toaddr.
     * Returns true if the recipient was accepted false otherwise.
     * Implements from RFC 821: RCPT <SP> TO:<forward-path> <CRLF>.
     *
     * @param string $address The address the message is being sent to
     * @param string $dsn     Comma separated list of DSN notifications. NEVER, SUCCESS, FAILURE, or DELAY.
     *
     * @return bool
     */
    public function recipient($address, $dsn = '')
    {
        if (empty($dsn)) {
            $rcpt = 'RCPT TO:<' . $address . '>';
        } else {
            $rcpt = 'RCPT TO:<' . $address . '> NOTIFY=' . $dsn;
        }

        return $this->sendCommand(
            'RCPT TO',
            $rcpt,
            [250, 251]
        );
    }

    /**
     * Send an SMTP RSET command.
     * Abort any transaction that is currently in progress.
     * Implements RFC 821: RSET <CRLF>.
     *
     * @return bool True on success
     */
    public function reset()
    {
        return $this->sendCommand('RSET', 'RSET', 250);
    }

    /**
     * Send a command to an SMTP server and check its return code.
     *
     * @param string    $command       The command name - not sent to the server
     * @param string    $commandstring The actual command to send
     * @param int|array $expect        One or more expected integer success codes
     *
     * @return bool True on success
     */
    protected function sendCommand($command, $commandstring, $expect)
    {
        if (!$this->connected()) {
            $this->setError("Called $command without being connected");

            return false;
        }
        //Send the command
        $this->client_send($commandstring . static::LE, $command);

        $this->last_reply = $this->get_lines();
        //Validate the response code
        $code = (int) substr($this->last_reply, 0, 3);

        //Always check if the command succeeded
        if ($this->handleResponse($code, $command)) {
            if (in_array($code, (array) $expect, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Send an SMTP SAML command.
     * Starts a mail transaction from the given sender address.
     * Returns true if successful or false otherwise. If True
     * the mail transaction is started and then one or more recipient
     * commands may be called followed by a data command. This command
     * will send the message to the users terminal if they are logged
     * in and send them an email.
     * Implements RFC 821: SAML <SP> FROM:<reverse-path> <CRLF>.
     *
     * @param string $from The address the message is from
     *
     * @return bool
     */
    public function sendAndMail($from)
    {
        return $this->sendCommand('SAML', "SAML FROM:$from", 250);
    }

    /**
     * Send an SMTP VRFY command.
     *
     * @param string $name The name to verify
     *
     * @return bool
     */
    public function verify($name)
    {
        return $this->sendCommand('VRFY', "VRFY $name", [250, 251]);
    }

    /**
     * Send an SMTP NOOP command.
     * Used to keep keep-alives alive, doesn't actually do anything.
     *
     * @return bool
     */
    public function noop()
    {
        return $this->sendCommand('NOOP', 'NOOP', 250);
    }

    /**
     * Send an SMTP TURN command.
     * This is an optional command for SMTP that this class does not support.
     * This method is here to make the RFC821 Definition complete for this class
     * and _may_ be implemented in future.
     * Implements from RFC 821: TURN <CRLF>.
     *
     * @return bool
     */
    public function turn()
    {
        $this->setError('The SMTP TURN command is not implemented');
        $this->edebug('SMTP NOTICE: ' . $this->error['error'], self::DEBUG_CLIENT);

        return false;
    }

    /**
     * Send raw data to the server.
     *
     * @param string $data    The data to send
     * @param string $command Optionally, the command this is part of, used only for controlling debug output
     *
     * @return int|bool The number of bytes sent to the server or false on error
     */
    public function client_send($data, $command = '')
    {
        //If SMTP transcripts are left enabled, or debug output is posted online
        //it can leak credentials, so hide credentials in all but lowest level
        if (
            self::DEBUG_LOWLEVEL > $this->do_debug &&
            in_array($command, ['User & Password', 'Username', 'Password'], true)
        ) {
            $this->edebug('CLIENT -> SERVER: [credentials hidden]', self::DEBUG_CLIENT);
        } else {
            $this->edebug('CLIENT -> SERVER: ' . $data, self::DEBUG_CLIENT);
        }
        set_error_handler([$this, 'errorHandler']);
        $result = fwrite($this->smtp_conn, $data);
        restore_error_handler();

        return $result;
    }

    /**
     * Get the latest error.
     *
     * @return array
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Get SMTP extensions available on the server.
     *
     * @return array|null
     */
    public function getServerExtList()
    {
        return $this->server_caps;
    }

    /**
     * Get the last reply from the server.
     *
     * @return string
     */
    public function getLastReply()
    {
        return $this->last_reply;
    }

    /**
     * Check connection state.
     *
     * @return bool True if connected
     */
    public function connected()
    {
        if (is_resource($this->smtp_conn)) {
            $sock_status = stream_get_meta_data($this->smtp_conn);
            if ($sock_status['eof']) {
                //The socket is valid but we are not connected
                $this->edebug(
                    'SMTP NOTICE: EOF caught while checking if connected',
                    self::DEBUG_CLIENT
                );
                $this->close();

                return false;
            }

            return true; //everything looks good
        }

        return false;
    }

    /**
     * Close the socket and clean up the state of the class.
     * Don't use this function without first trying to use QUIT.
     */
    public function close()
    {
        $this->setError('');
        $this->server_caps = null;
        $this->helo_rply = null;
        if (is_resource($this->smtp_conn)) {
            //Close the connection and cleanup
            fclose($this->smtp_conn);
            $this->smtp_conn = null; //Makes the connection null - Not false
        }
    }

    /**
     * Send an SMTP AUTH command.
     * Unauthenticated users may not issue more than one transaction.
     *
     * @param string $username The user name
     * @param string $password The password
     * @param string $authtype The auth type
     * @param OAuthTokenProvider $OAuth  An optional OAuthTokenProvider
     *
     * @return bool True if successfully authenticated
     */
    public function authenticate($username, $password, $authtype = null, $OAuth = null)
    {
        if (!$this->server_caps) {
            $this->setError('Authentication is not allowed before HELO/EHLO');

            return false;
        }

        if (array_key_exists('AUTH', $this->server_caps)) {
            //If the server doesn't support authentication, nothing to do
            if (empty($this->server_caps['AUTH'])) {
                $this->setError('Authentication is not supported by the server');

                return false;
            }

            //If authentication types are explicitly listed, check them
            if (
                is_array($this->server_caps['AUTH']) &&
                !in_array($authtype, $this->server_caps['AUTH'], true) &&
                !(
                    null === $authtype &&
                    (
                        (in_array(self::AUTH_PLAIN, $this->server_caps['AUTH'], true) &&
                            in_array(self::AUTH_LOGIN, $this->server_caps['AUTH'], true))
                    )
                )
            ) {
                //Request auth type not available
                $this->setError('Requested authentication type "' . $authtype . '" is not supported by the server');

                return false;
            }

            if (null === $authtype) {
                //If no auth mechanism is specified, attempt to use the most secure
                //one that the server supports
                if (in_array(self::AUTH_CRAM_MD5, $this->server_caps['AUTH'], true)) {
                    $authtype = self::AUTH_CRAM_MD5;
                } elseif (in_array(self::AUTH_PLAIN, $this->server_caps['AUTH'], true)) {
                    $authtype = self::AUTH_PLAIN;
                } elseif (in_array(self::AUTH_LOGIN, $this->server_caps['AUTH'], true)) {
                    $authtype = self::AUTH_LOGIN;
                }
            }

            $this->edebug('Auth method requested: ' . ($authtype ?: 'UNSPECIFIED'), self::DEBUG_LOWLEVEL);
            $this->edebug(
                'Auth methods available on the server: ' . implode(',', $this->server_caps['AUTH']),
                self::DEBUG_LOWLEVEL
            );

            switch ($authtype) {
                case self::AUTH_CRAM_MD5:
                    return $this->authCRAMMD5($username, $password);
                case self::AUTH_PLAIN:
                    return $this->authPLAIN($username, $password);
                case self::AUTH_LOGIN:
                    return $this->authLogin($username, $password);
                case 'XOAUTH2':
                    return $this->authXOAuth2($username, $OAuth);
                default:
                    $this->setError("Authentication method \"$authtype\" is not supported");

                    return false;
            }
        }

        return false;
    }

    /**
     * Get the response from the last SMTP command.
     *
     * @return array|bool Two element array containing the response code and the response string, or false on error
     */
    protected function getResponse()
    {
        while ($this->sendHello('EHLO', $this->Hostname)) {
            return false;
        }

        $code = (int) substr($this->last_reply, 0, 3);
        $keyword = substr($this->last_reply, 4, 4);
        $message = substr($this->last_reply, 9);

        return [
            'code' => $code,
            'text' => $message,
            'fctext' => $message,
            'ctext' => $message,
        ];
    }

    /**
     * Get the number of remaining bytes in the socket input buffer.
     *
     * @return int
     */
    protected function getBytes()
    {
        if (!is_resource($this->smtp_conn)) {
            return 0;
        }

        $socket_meta = stream_get_meta_data($this->smtp_conn);

        return $socket_meta['unread_bytes'];
    }

    /**
     * Read the SMTP server's response.
     * Either before eof or socket timeout occurs on the operation.
     * With SMTP we can tell if we have more lines to read if the
     * 4th character is '-' symbol. If it is a space then we don't
     * need to read anything else.
     *
     * @return string
     */
    protected function get_lines()
    {
        //If the connection is bad, give up straight away
        if (!is_resource($this->smtp_conn)) {
            return '';
        }
        $data = '';
        $endtime = 0;
        if ($this->Timelimit > 0) {
            $endtime = time() + $this->Timelimit;
        }
        $selR = [$this->smtp_conn];
        $selW = null;
        while (is_resource($this->smtp_conn) && !feof($this->smtp_conn)) {
            //Must pass vars in here as params are by reference
            //solution for signals inspired by https://github.com/symfony/symfony/pull/4489
            set_error_handler([$this, 'errorHandler']);
            $n = stream_select($selR, $selW, $selW, $this->Timelimit);
            restore_error_handler();

            if ($n === false) {
                $message = $this->getError()['error'];
                $this->edebug(
                    'SMTP -> get_lines(): select failed (' . $message . ')',
                    self::DEBUG_LOWLEVEL
                );

                //stream_select returns false when the `select` system call is interrupted
                //by an incoming signal, try the select again
                if (stripos($message, 'interrupted system call') !== false) {
                    $this->edebug(
                        'SMTP -> get_lines(): retrying stream_select',
                        self::DEBUG_LOWLEVEL
                    );
                    $this->setError('');
                    continue;
                }

                break;
            }

            if (!$n) {
                $this->edebug(
                    'SMTP -> get_lines(): select timed-out in (' . $this->Timelimit . ' sec)',
                    self::DEBUG_LOWLEVEL
                );
                break;
            }

            //Deliberate noise suppression - errors are handled afterwards
            $str = @fgets($this->smtp_conn, self::MAX_REPLY_LENGTH);
            $this->edebug('SMTP INBOUND: "' . trim($str) . '"', self::DEBUG_LOWLEVEL);
            $data .= $str;
            //If response is only 3 chars (not valid, but RFC5321 S4.2 says it must be handled),
            //or 4th character is a space or a line break char, we are done reading, break the loop.
            //String array access is a significant micro-optimisation over strlen
            if (!isset($str[3]) || $str[3] === ' ' || $str[3] === "\r" || $str[3] === "\n") {
                break;
            }
            //Timed-out? Log and break
            $info = stream_get_meta_data($this->smtp_conn);
            if ($info['timed_out']) {
                $this->edebug(
                    'SMTP -> get_lines(): stream timed-out (' . $this->Timeout . ' sec)',
                    self::DEBUG_LOWLEVEL
                );
                break;
            }

            //Now check if reads took too long
            if ($endtime && time() > $endtime) {
                $this->edebug(
                    'SMTP -> get_lines(): timelimit reached (' .
                    $this->Timelimit . ' sec)',
                    self::DEBUG_LOWLEVEL
                );
                break;
            }
        }

        return $data;
    }

    /**
     * Enable or disable VERP address generation.
     *
     * @param bool $enabled
     */
    public function setVerp($enabled = false)
    {
        $this->do_verp = $enabled;
    }

    /**
     * Get VERP address generation mode.
     *
     * @return bool
     */
    public function getVerp()
    {
        return $this->do_verp;
    }

    /**
     * Set error messages and codes.
     *
     * @param string $message      The error message
     * @param string $detail       Further detail on the error
     * @param string $smtp_code    An associated SMTP error code
     * @param string $smtp_code_ex Extended SMTP code
     */
    protected function setError($message, $detail = '', $smtp_code = '', $smtp_code_ex = '')
    {
        $this->error = [
            'error' => $message,
            'detail' => $detail,
            'smtp_code' => $smtp_code,
            'smtp_code_ex' => $smtp_code_ex,
        ];
    }

    /**
     * Set debug output method.
     *
     * @param string|callable $method The name of the mechanism to use for debugging output, or a callable to handle it
     */
    public function setDebugOutput($method = 'echo')
    {
        $this->Debugoutput = $method;
    }

    /**
     * Get debug output method.
     *
     * @return string
     */
    public function getDebugOutput()
    {
        return $this->Debugoutput;
    }

    /**
     * Set debug output level.
     *
     * @param int $level
     */
    public function setDebugLevel($level = 0)
    {
        $this->do_debug = $level;
    }

    /**
     * Get debug output level.
     *
     * @return int
     */
    public function getDebugLevel()
    {
        return $this->do_debug;
    }

    /**
     * Set SMTP timeout.
     *
     * @param int $timeout The timeout duration in seconds
     */
    public function setTimeout($timeout = 0)
    {
        $this->Timeout = $timeout;
    }

    /**
     * Get SMTP timeout.
     *
     * @return int
     */
    public function getTimeout()
    {
        return $this->Timeout;
    }

    /**
     * Reports an error number and string.
     *
     * @param int    $errno   The error number returned by PHP
     * @param string $errmsg  The error message returned by PHP
     * @param string $errfile The file the error occurred in
     * @param int    $errline The line number the error occurred on
     */
    protected function errorHandler($errno, $errmsg, $errfile = '', $errline = 0)
    {
        $notice = 'Connection failed.';
        $this->setError(
            $notice,
            $errmsg,
            (string) $errno
        );
        $this->edebug(
            "$notice Error #$errno: $errmsg [$errfile line $errline]",
            self::DEBUG_CONNECTION
        );

        return true;
    }

    /**
     * Extract and return the ID of the last SMTP transaction based on
     * a list of patterns provided in SMTP::$smtp_transaction_id_patterns.
     * Relies on the host providing the ID in response to a DATA command.
     * If no reply has been received yet, it will return null.
     * If no pattern was matched, it will return false.
     *
     * @return bool|string|null
     */
    protected function recordLastTransactionID()
    {
        $reply = $this->getLastReply();

        if (empty($reply)) {
            $this->edebug('No reply was received from the SMTP server', self::DEBUG_LOWLEVEL);

            return null;
        }

        //This will be processed every time a message is sent
        $smtp_transaction_id = null;

        foreach ($this->smtp_transaction_id_patterns as $smtp_transaction_id_pattern) {
            $matches = [];
            if (preg_match($smtp_transaction_id_pattern, $reply, $matches)) {
                //Get the ID
                $smtp_transaction_id = trim($matches[1]);
                break;
            }
        }

        $this->last_smtp_transaction_id = $smtp_transaction_id;

        if (null === $smtp_transaction_id) {
            $this->edebug('Failed to get SMTP transaction ID', self::DEBUG_LOWLEVEL);

            return false;
        }

        $this->edebug('Detected SMTP transaction id: ' . $smtp_transaction_id, self::DEBUG_LOWLEVEL);

        return $smtp_transaction_id;
    }

    /**
     * Get the queue/transaction ID of the last SMTP transaction
     * If no reply has been received yet, it will return null.
     * If no pattern was matched, it will return false.
     *
     * @return bool|string|null
     *
     * @see recordLastTransactionID()
     */
    public function getLastTransactionID()
    {
        if (!empty($this->last_smtp_transaction_id)) {
            return $this->last_smtp_transaction_id;
        }
    }
}