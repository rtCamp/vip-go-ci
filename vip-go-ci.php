#!/usr/bin/php
<?php

require_once( __DIR__ . '/defines.php' );
require_once( __DIR__ . '/github-api.php' );
require_once( __DIR__ . '/git-repo.php' );
require_once( __DIR__ . '/misc.php' );
require_once( __DIR__ . '/statistics.php' );
require_once( __DIR__ . '/phpcs-scan.php' );
require_once( __DIR__ . '/lint-scan.php' );
require_once( __DIR__ . '/auto-approval.php' );
require_once( __DIR__ . '/ap-file-types.php' );
require_once( __DIR__ . '/ap-hashes-api.php' );
require_once( __DIR__ . '/ap-svg-files.php' );
require_once( __DIR__ . '/svg-scan.php' );
require_once( __DIR__ . '/other-web-services.php' );

/*
 * Handle boolean parameters given on the command-line.
 *
 * Will set a default value for the given parameter name,
 * if no value is set. Will then proceed to check if the
 * value given is a boolean and will then convert the value
 * to a boolean-type, and finally set it in $options.
 */

function vipgoci_option_bool_handle(
	&$options,
	$parameter_name,
	$default_value
) {

	/* If no default is given, set it */
	if ( ! isset( $options[ $parameter_name ] ) ) {
		$options[ $parameter_name ] = $default_value;
	}

	/* Check if the gien value is a false or true */
	if (
		( $options[ $parameter_name ] !== 'false' ) &&
		( $options[ $parameter_name ] !== 'true' )
	) {
		vipgoci_sysexit(
			'Parameter --' . $parameter_name .
				' has to be either false or true',
			array(),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}

	/* Convert the given value to a boolean type value */
	if ( $options[ $parameter_name ] === 'false' ) {
		$options[ $parameter_name ] = false;
	}

	else {
		$options[ $parameter_name ] = true;
	}
}

/*
 * Handle integer parameters given on the command-line.
 *
 * Will set a default value for the given parameter name,
 * if no value is set. Will then proceed to check if the
 * value given is an integer-value, then forcibly convert
 * it to integer-value to make sure it is of that type,
 * then check if it is in a list of allowable values.
 * If any of these fail, it will exit the program with an error.
 */

function vipgoci_option_integer_handle(
	&$options,
	$parameter_name,
	$default_value,
	$allowed_values = null
) {
	/* If no value is set, set the default value */
	if ( ! isset( $options[ $parameter_name ] ) ) {
		$options[ $parameter_name ] = $default_value;
	}

	/* Make sure it is a numeric */
	if ( ! is_numeric( $options[ $parameter_name ] ) ) {
		vipgoci_sysexit(
			'Usage: Parameter --' . $parameter_name . ' is not ' .
				'an integer-value.',
			array(),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}

	/* Forcibly convert to integer-value */
	$options[ $parameter_name ] =
		(int) $options[ $parameter_name ];

	/*
	 * Check if value is in range
	 */

	if (
		( null !== $allowed_values )
		&&
		( ! in_array(
			$options[ $parameter_name ],
			$allowed_values,
			true
		) )
	) {
		vipgoci_sysexit(
			'Parameter --' . $parameter_name . ' is out ' .
				'of allowable range.',
			array(),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}

}

/*
 * Handle array-like option parameters given on the command line
 *
 * Parses the parameter, turns it into a real array,
 * makes sure forbidden values are not contained in it.
 * Does not return the result, but rather alters
 * $options directly.
 *
 * Allows for array-item separator to be specified.
 */
function vipgoci_option_array_handle(
	&$options,
	$option_name,
	$default_value = array(),
	$forbidden_value = null,
	$array_separator = ','
) {
	if ( ! isset( $options[ $option_name ] ) ) {
		$options[ $option_name ] = array();
	}

	else {
		$options[ $option_name ] = explode(
			$array_separator,
			strtolower(
				$options[ $option_name ]
			)
		);

		if ( ! empty( $forbidden_value ) ) {
			if ( in_array(
				$forbidden_value,
				$options[ $option_name ],
				true
			) ) {
				vipgoci_sysexit(
					'Parameter --' .
						$option_name . ' ' .
						'can not contain \'' .
						$forbidden_value .
						'\' as one of ' .
						'the values',
					array(),
					VIPGOCI_EXIT_USAGE_ERROR
				);
			}
		}
	}
}


/*
 * Handle parameter that expects the value
 * of it to be a file. Allow a default value
 * to be set if none is set.
 */

function vipgoci_option_file_handle(
	&$options,
	$option_name,
	$default_value = null
) {

	if (
		( ! isset( $options[ $option_name ] ) ) &&
		( null !== $default_value )
	) {
		$options[ $option_name ] = $default_value;
	}

	else if (
		( ! isset( $options[ $option_name ] ) ) ||
		( ! is_file( $options[ $option_name ] ) )
	) {
		vipgoci_sysexit(
			'Parameter --' . $option_name .
				' has to be a valid path',
			array(),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}
}

/*
 * Handle parameter that we expect to be a URL.
 *
 * If the parameter is not empty, and is not really
 * a URL (not starting with http:// or https://),
 * exit with error. If empty, sets a default.
 */

function vipgoci_option_url_handle(
	&$options,
	$option_name,
	$default_value
) {
	/*
	 * If not set, assume default value.
	 */
	if (
		( ! isset( $options[ $option_name ] ) ) ||
		( empty( $options[ $option_name ] ) )
	) {
		$options[ $option_name ] = $default_value;
	}

	/*
	 * If not default value, check if it looks like an URL,
	 * and if so, use it, but if not, exit with error.
	 */
	if ( $default_value !== $options[ $option_name ] ) {
		$options[ $option_name ] = trim(
			$options[ $option_name ]
		);

		if (
			( 0 !== strpos(
				$options[ $option_name ],
				'http://'
			) )
			&&
			( 0 !== strpos(
				$options[ $option_name ],
				'https://'
			) )
		) {
			vipgoci_sysexit(
				'Option --' . $option_name . ' should ' .
					'be an URL',
				array(
				),
				VIPGOCI_EXIT_USAGE_ERROR
			);
		}
	}
}

/*
 * Determine exit status.
 *
 * If any 'error'-type issues were submitted to
 * GitHub we announce a failure to our parent-process
 * by returning with a non-zero exit-code.
 *
 * If we only submitted warnings, we do not announce failure.
 */

function vipgoci_exit_status( $results ) {
	foreach (
		array_keys(
			$results['stats']
		)
		as $stats_type
	) {
		if (
			! isset( $results['stats'][ $stats_type ] ) ||
			null === $results['stats'][ $stats_type ]
		) {
			/* In case the type of scan was not performed, skip */
			continue;
		}

		foreach (
			array_keys(
				$results['stats'][ $stats_type ]
			)
			as $pr_number
		) {
			if (
				0 !== $results['stats']
					[ $stats_type ]
					[ $pr_number ]
					['error']
			) {
				// Some errors were found, return non-zero
				return 250;
			}
		}

	}

	return 0;
}


/*
 * Main invocation function.
 */
function vipgoci_run() {
	global $argv;
	global $vipgoci_debug_level;

	$hashes_oauth_arguments =
		array(
			'hashes-oauth-token',
			'hashes-oauth-token-secret',
			'hashes-oauth-consumer-key',
			'hashes-oauth-consumer-secret'
		);

	vipgoci_log(
		'Initializing...',
		array()
	);

	/*
	 * Refuse to run as root.
	 */
	if ( 0 === posix_getuid() ) {
		vipgoci_sysexit(
			'Will not run as root. Please run as non-privileged user.',
			array(),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}

	/*
	 * Set how to deal with errors:
	 * Report all errors, and display them.
	 */
	ini_set( 'error_log', '' );

	error_reporting( E_ALL );
	ini_set( 'display_errors', 'on' );


	// Set with a temp value for now, user value set later
	$vipgoci_debug_level = 0;

	$startup_time = time();

	$options = getopt(
		null,
		array(
			'repo-owner:',
			'repo-name:',
			'commit:',
			'token:',
			'review-comments-max:',
			'review-comments-total-max:',
			'review-comments-ignore:',
			'dismiss-stale-reviews:',
			'branches-ignore:',
			'output:',
			'dry-run:',
			'informational-url:',
			'phpcs-path:',
			'phpcs-standard:',
			'phpcs-severity:',
			'phpcs-sniffs-exclude:',
			'hashes-api-url:',
			'hashes-oauth-token:',
			'hashes-oauth-token-secret:',
			'hashes-oauth-consumer-key:',
			'hashes-oauth-consumer-secret:',
			'irc-api-url:',
			'irc-api-token:',
			'irc-api-bot:',
			'irc-api-room:',
			'pixel-api-url:',
			'pixel-api-groupprefix:',
			'php-path:',
			'local-git-repo:',
			'skip-folders:',
			'lint:',
			'phpcs:',
			'svg-checks:',
			'autoapprove:',
			'autoapprove-filetypes:',
			'autoapprove-label:',
			'help',
			'debug-level:',
			'hashes-api:',
	)
	);

	// Validate args
	if (
		! isset( $options['repo-owner'] ) ||
		! isset( $options['repo-name'] ) ||
		! isset( $options['commit'] ) ||
		! isset( $options['token'] ) ||
		! isset( $options['local-git-repo']) ||
		isset( $options['help'] )
	) {
		print 'Usage: ' . $argv[0] . PHP_EOL .
			"\t" . 'Options --repo-owner, --repo-name, --commit, --token, --local-git-repo, --phpcs-path are ' . PHP_EOL .
			"\t" . 'mandatory, while others are optional.' . PHP_EOL .
			PHP_EOL .
			"\t" . 'Note that if option --autoapprove is specified, --autoapprove-label needs to' . PHP_EOL .
			"\t" . 'be specified as well.' . PHP_EOL .
			PHP_EOL .
			"\t" . '--repo-owner=STRING            Specify repository owner, can be an organization' . PHP_EOL .
			"\t" . '--repo-name=STRING             Specify name of the repository' . PHP_EOL .
			"\t" . '--commit=STRING                Specify the exact commit to scan (SHA)' . PHP_EOL .
			"\t" . '--token=STRING                 The access-token to use to communicate with GitHub' . PHP_EOL .
			"\t" . '--review-comments-max=NUMBER   Maximum number of inline comments to submit' . PHP_EOL .
			"\t" . '                               to GitHub in one review. If the number of ' . PHP_EOL .
			"\t" . '                               comments exceed this number, additional reviews ' . PHP_EOL .
			"\t" . '                               will be submitted.' . PHP_EOL .
			"\t" . '--review-comments-total-max=NUMBER  Maximum number of inline comments submitted to'   . PHP_EOL .
			"\t" . '                                    a single Pull-Request by the program -- includes' . PHP_EOL .
			"\t" . '                                    comments from previous executions. A value of ' . PHP_EOL .
			"\t" . '                                    \'0\' indicates no limit.' . PHP_EOL .
			"\t" . '--review-comments-ignore=STRING     Specify which result comments to ignore' . PHP_EOL .
			"\t" . '                                    -- e.g. useful if one type of message is to be ignored' . PHP_EOL .
			"\t" . '                                    rather than a whole PHPCS sniff. Should be a ' . PHP_EOL .
			"\t" . '                                    whole string with items separated by \"|||\".' . PHP_EOL .
			"\t" . '--dismiss-stale-reviews=BOOL   Dismiss any reviews associated with Pull-Requests ' . PHP_EOL .
			"\t" . '                               that we process which have no active comments. ' . PHP_EOL .
			"\t" . '                               The Pull-Requests we process are those associated ' . PHP_EOL .
			"\t" . '                               with the commit specified.' . PHP_EOL .
			"\t" . '--informational-url=STRING     URL to documentation on what this bot does. Should ' . PHP_EOL .
			"\t" . '                               start with https:// or https:// ' . PHP_EOL .
			"\t" . '--phpcs=BOOL                   Whether to run PHPCS (true/false)' . PHP_EOL .
			"\t" . '--phpcs-path=FILE              Full path to PHPCS script' . PHP_EOL .
			"\t" . '--phpcs-standard=STRING        Specify which PHPCS standard to use' . PHP_EOL .
			"\t" . '--phpcs-severity=NUMBER        Specify severity for PHPCS' . PHP_EOL .
			"\t" . '--phpcs-sniffs-exclude=STRING  Specify which sniff to exclude from PHPCS scanning' . PHP_EOL .
			"\t" . '--autoapprove=BOOL             Whether to auto-approve Pull-Requests' . PHP_EOL .
			"\t" . '                               altering only files of certain types' . PHP_EOL .
			"\t" . '--autoapprove-filetypes=STRING Specify what file-types can be auto-' . PHP_EOL .
			"\t" . '                               approved. PHP files cannot be specified' . PHP_EOL .
			"\t" . '--autoapprove-label=STRING     String to use for labels when auto-approving' . PHP_EOL .
			"\t" . '--php-path=FILE                Full path to PHP, if not specified the' . PHP_EOL .
			"\t" . '                               default in $PATH will be used instead' . PHP_EOL .
			"\t" . '--svg-checks=BOOL              Enable or disable SVG checks, both' . PHP_EOL .
			"\t" . '                               auto-approval of SVG files and problem' . PHP_EOL .
			"\t" . '                               checking of these files. Note that if' . PHP_EOL .
			"\t" . '                               auto-approvals are turned off globally, no' . PHP_EOL .
			"\t" . '                               auto-approval is performed for SVG files.' . PHP_EOL .
			"\t" . '--hashes-api=BOOL              Whether to do hashes-to-hashes API verfication ' . PHP_EOL .
			"\t" . '                               with individual PHP files found to be altered ' . PHP_EOL .
			"\t" . '                               in the branch specified' . PHP_EOL .
			"\t" . '--hashes-api-url=STRING        URL to hashes-to-hashes HTTP API root' . PHP_EOL .
			"\t" . '                               -- note that it should not include any specific' . PHP_EOL .
			"\t" . '                               paths to individual parts of the API.' . PHP_EOL .
			PHP_EOL .
			"\t" . '--hashes-oauth-token=STRING,        --hashes-oauth-token-secret=STRING, ' . PHP_EOL .
			"\t" . '--hashes-oauth-consumer-key=STRING, --hashes-oauth-consumer-secret=STRING ' . PHP_EOL .
			"\t" . '                               OAuth 1.0 token, token secret, consumer key and ' . PHP_EOL .
			"\t" . '                               consumer secret needed for hashes-to-hashes HTTP requests' . PHP_EOL .
			"\t" . '                               All required for hashes-to-hashes requests.' . PHP_EOL .
			PHP_EOL .
			"\t" . '--irc-api-url=STRING           URL to IRC API to send alerts' . PHP_EOL .
			"\t" . '--irc-api-token=STRING         Access-token to use to communicate with the IRC ' . PHP_EOL .
			"\t" . '                               API' . PHP_EOL .
			"\t" . '--irc-api-bot=STRING           Name for the bot which is supposed to send the IRC ' .PHP_EOL .
			"\t" . '                               messages.' . PHP_EOL .
			"\t" . '--irc-api-room=STRING          Name for the chatroom to which the IRC messages should ' . PHP_EOL .
			"\t" . '                               be sent. ' . PHP_EOL .
			"\t" . '--branches-ignore=STRING,...   What branches to ignore -- useful to make sure' . PHP_EOL .
			"\t" . '                               some branches never get scanned. Separate branches' . PHP_EOL .
			"\t" . '                               with commas' . PHP_EOL .
			"\t" . '--local-git-repo=FILE          The local git repository to use for direct access to code' . PHP_EOL .
			"\t" . '--skip-folders=STRING          Specify folders relative to the git repository in which not ' . PHP_EOL .
			"\t" . '                               to look into for files to PHP lint or scan using PHPCS. ' . PHP_EOL .
			"\t" . '                               Note that this argument is not employed with auto-approvals. ' . PHP_EOL .
			"\t" . '                               Values are comma separated' . PHP_EOL .
			"\t" . '--dry-run=BOOL                 If set to true, will not make any changes to any data' . PHP_EOL .
			"\t" . '                               on GitHub -- no comments will be submitted, etc.' . PHP_EOL .
			"\t" . '--output=FILE                  Where to save output made from running PHPCS' . PHP_EOL .
			"\t" . '--lint=BOOL                    Whether to do PHP linting (true/false)' . PHP_EOL .
			"\t" . '--help                         Displays this message' . PHP_EOL .
			"\t" . '--debug-level=NUMBER           Specify minimum debug-level of messages to print' . PHP_EOL .
			"\t" . '                                -- higher number indicates more detailed debugging-messages.' . PHP_EOL .
			"\t" . '                               Default is zero' . PHP_EOL;

		exit( VIPGOCI_EXIT_USAGE_ERROR );
	}


	/*
	 * Process the --branches-ignore parameter,
	 * -- expected to be an array
	 */

	vipgoci_option_array_handle(
		$options,
		'branches-ignore',
		array()
	);


	/*
	 * Process --phpcs-path -- expected to
	 * be a file
	 */

	vipgoci_option_file_handle(
		$options,
		'phpcs-path',
		null
	);

	/*
	 * Process --phpcs-standard -- expected to be
	 * a string
	 */

	if ( empty( $options['phpcs-standard'] ) ) {
		$options['phpcs-standard'] = 'WordPress-VIP-Go';
	}

	$options['phpcs-standard'] = trim(
		$options['phpcs-standard']
	);

	/*
	 * Process --phpcs-sniffs-exclude -- expected to be
	 * a string.
	 */
	if ( empty( $options['phpcs-sniffs-exclude'] ) ) {
		$options['phpcs-sniffs-exclude'] = null;
	}

	else {
		$options['phpcs-sniffs-exclude'] = trim(
			$options['phpcs-sniffs-exclude']
		);
	}

	/*
	 * Process --review-comments-ignore -- expected
	 * to be an array (items separated by "|||"). 
	 * Then transform all of the messages to lower-case.
	 */

	vipgoci_option_array_handle(
		$options,
		'review-comments-ignore',
		array(),
		array(),
		'|||'
	);

	if ( ! empty( $options[ 'review-comments-ignore' ] ) ) {
		$options['review-comments-ignore'] = array_map(
			'strtolower',
			$options['review-comments-ignore']
		);
	}

	/*
	 * Process --phpcs-severity -- expected to be
	 * an integer-value.
	 */

	vipgoci_option_integer_handle(
		$options,
		'phpcs-severity',
		1,
		array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 )
	);

	/*
	 * Process --php-path -- expected to be a file,
	 * default value is 'php' (then relies on $PATH)
	 */

	vipgoci_option_file_handle(
		$options,
		'php-path',
		'php'
	);


	/*
	 * Process --hashes-api and --svg-checks
	 * -- expected to be a boolean.
	*/

	vipgoci_option_bool_handle( $options, 'hashes-api', 'false' );

	vipgoci_option_bool_handle( $options, 'svg-checks', 'false' );

	/*
	 * Process --hashes-api-url -- expected to
	 * be an URL to a webservice.
	 */

	if ( isset( $options['hashes-api-url'] ) ) {
		$options['hashes-api-url'] = trim(
			$options['hashes-api-url']
		);

		$options['hashes-api-url'] = rtrim(
			$options['hashes-api-url'],
			'/'
		);
	}

	/*
	 * Process hashes-oauth arguments
	 */

	foreach( $hashes_oauth_arguments as $tmp_key ) {
		if ( ! isset( $options[ $tmp_key ] ) ) {
			continue;
		}

		$options[ $tmp_key ] = rtrim( trim(
			$options[ $tmp_key ]
		) );
	}


	/*
	 * Handle --local-git-repo parameter
	 */

	$options['local-git-repo'] = rtrim(
		$options['local-git-repo'],
		'/'
	);


	vipgoci_gitrepo_ok(
		$options['commit'],
		$options['local-git-repo']
	);


	/*
	 * Handle --skip-folders parameter
	 */
	vipgoci_option_array_handle(
		$options,
		'skip-folders',
		array()
	);

	/*
	 * Handle optional --debug-level parameter
	 */

	vipgoci_option_integer_handle(
		$options,
		'debug-level',
		0,
		array( 0, 1, 2 )
	);

	// Set the value to global
	$vipgoci_debug_level = $options['debug-level'];

	/*
	 * Maximum number of inline comments posted to
	 * Github with one review -- from 5 to 100.
	 */

	vipgoci_option_integer_handle(
		$options,
		'review-comments-max',
		10,
		range( 5, 100, 1 )
	);

	/*
	 * Overall maximum number of inline comments
	 * posted to GitHub Pull-Request Reviews -- from
	 * 0 to 500. 0 means unlimited.
	 */

	vipgoci_option_integer_handle(
		$options,
		'review-comments-total-max',
		200,
		range( 0, 500, 1 )
	);

	/*
	 * Handle optional --informational-url --
	 * URL to information on what this bot does.
	 */

	vipgoci_option_url_handle(
		$options,
		'informational-url',
		null
	);


	/*
	 * Handle boolean parameters
	 */

	vipgoci_option_bool_handle( $options, 'dry-run', 'false' );

	vipgoci_option_bool_handle( $options, 'phpcs', 'true' );

	vipgoci_option_bool_handle( $options, 'lint', 'true' );

	vipgoci_option_bool_handle( $options, 'dismiss-stale-reviews', 'false' );

	if (
		( false === $options['lint'] ) &&
		( false === $options['phpcs'] )
	) {
		vipgoci_sysexit(
			'Both --lint and --phpcs set to false, nothing to do!',
			array(),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}


	/*
	 * Should we auto-approve Pull-Requests when
	 * only altering certain file-types?
	 */

	vipgoci_option_bool_handle( $options, 'autoapprove', 'false' );

	vipgoci_option_array_handle(
		$options,
		'autoapprove-filetypes',
		array(),
		'php'
	);

	/*
	 * Handle IRC API parameters
	 */

	$irc_params_defined = 0;

	foreach( array(
			'irc-api-url',
			'irc-api-token',
			'irc-api-bot',
			'irc-api-room'
		) as $irc_api_param ) {

		if ( isset( $options[ $irc_api_param ] ) ) {
			$options[ $irc_api_param ] = trim(
				$options[ $irc_api_param ]
			);

			$options[ $irc_api_param ] = rtrim(
				$options[ $irc_api_param ]
			);

			$irc_params_defined++;
		}
	}

	if ( isset( $options['irc-api-url'] ) ) {
		vipgoci_option_url_handle(
			$options,
			'irc-api-url',
			null
		);
	}

	if (
		( $irc_params_defined > 0 ) &&
		( $irc_params_defined !== 4 )
	) {
		vipgoci_sysexit(
			'Some IRC API parameters defined but not all; all must be defined to be useful',
			array(
			),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}

	unset( $irc_params_defined );

	/*
	 * Handle settings for the pixel API.
	 */
	if ( isset( $options['pixel-api-url'] ) ) {
		vipgoci_option_url_handle(
			$options,
			'pixel-api-url',
			null
		);
	}

	if ( isset( $options['pixel-api-groupprefix'] ) ) {
		$options['pixel-api-groupprefix'] = trim(
			$options['pixel-api-groupprefix']
		);
	}


	/*
	 * Do some sanity-checking on the parameters
	 *
	 * Note: Parameters should not be set after
	 * this point.
	 */

	$options['autoapprove-filetypes'] = array_map(
		'strtolower',
		$options['autoapprove-filetypes']
	);

	if ( empty( $options['autoapprove-label'] ) ) {
		$options['autoapprove-label'] = false;
	}

	else {
		$options['autoapprove-label'] = trim(
			$options['autoapprove-label']
		);
	}


	if (
		( true === $options['autoapprove'] ) &&
		( false === $options['autoapprove-label'] )
	) {
		vipgoci_sysexit(
			'To be able to auto-approve, file-types to approve ' .
			'must be specified, as well as a label; see --help ' .
			'for information',
			array(),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}

	/*
	 * Do sanity-checking with hashes-api-url
	 * and --hashes-oauth-* parameters
	 */
	if ( isset( $options['hashes-api-url'] ) ) {
		foreach ( $hashes_oauth_arguments as $tmp_key ) {
			if ( ! isset( $options[ $tmp_key ] ) ) {
				vipgoci_sysexit(
					'Asking to use --hashes-api-url without --hashes-oauth-* parameters, but that is not possible, as authorization is needed for hashes-to-hashes API',
					array(),
					VIPGOCI_EXIT_USAGE_ERROR
				);
			}
		}

		if ( false === $options['autoapprove'] ) {
			vipgoci_sysexit(
				'Asking to use --hashes-api-url without --autoapproval set to true, but for hashes-to-hashes functionality to be useful, --autoapprove must be enabled. Otherwise the functionality will not really be used',
				array(),
				VIPGOCI_EXIT_USAGE_ERROR
			);
		}
	}

	if (
		( true === $options['autoapprove'] ) &&

		/*
		 * Cross-reference: We disallow autoapproving
		 * PHP and JS files here, because they chould contain
		 * contain dangerous code.
		 */
		(
			( in_array(
				'php',
				$options['autoapprove-filetypes'],
				true
			) )
		||
			( in_array(
				'js',
				$options['autoapprove-filetypes'],
				true
			) )
		)
	) {
		vipgoci_sysexit(
			'PHP and JS files cannot be auto-approved on file-type basis, as they ' .
				'can cause serious problems for execution',
			array(
			),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}


	/*
	 * Ask GitHub about information about
	 * the user the token belongs to
	 */
	$current_user_info = vipgoci_github_authenticated_user_get(
		$options['token']
	);

	if (
		( false === $current_user_info ) ||
		( ! isset( $current_user_info->login ) ) ||
		( empty( $current_user_info->login ) )
	) {
		vipgoci_sysexit(
			'Unable to get information about token-holder user from GitHub',
			array(
			),
			VIPGOCI_EXIT_GITHUB_PROBLEM
		);
	}

	else {
		vipgoci_log(
			'Got information about token-holder user from GitHub',
			array(
				'login' => $current_user_info->login,
				'html_url' => $current_user_info->html_url,
			)
		);
	}


	/*
	 * Log that we started working,
	 * and the arguments provided as well.
	 *
	 * Make sure not to print out any secrets.
	 */

	$options_clean = $options;
	$options_clean['token'] = '***';

	if ( isset( $options_clean['irc-api-token'] ) ) {
		$options_clean['irc-api-token'] = '***';
	}

	foreach( $hashes_oauth_arguments as $hashes_oauth_argument ) {
		if ( isset( $options_clean[ $hashes_oauth_argument ] ) ) {
			$options_clean[ $hashes_oauth_argument ] = '***';
		}
	}

	vipgoci_log(
		'Starting up...',
		array(
			'options' => $options_clean
		)
	);

	$results = array(
		'issues'	=> array(),

		'stats'		=> array(
			VIPGOCI_STATS_PHPCS	=> null,
			VIPGOCI_STATS_LINT	=> null,
			VIPGOCI_STATS_HASHES_API => null,
		),
	);

	unset( $options_clean );



	/*
	 * If no Pull-Requests are implicated by this commit,
	 * bail now, as there is no point in continuing running.
	 */

	$prs_implicated = vipgoci_github_prs_implicated(
		$options['repo-owner'],
		$options['repo-name'],
		$options['commit'],
		$options['token'],
		$options['branches-ignore']
	);

	if ( empty( $prs_implicated ) ) {
		vipgoci_sysexit(
			'Skipping scanning entirely, as the commit ' .
				'is not a part of any Pull-Request',
			array(),
			VIPGOCI_EXIT_NORMAL
		);
	}


	/*
	 * Make sure we are working with the latest
	 * commit to each implicated PR.
	 *
	 * If we detect that we are doing linting,
	 * and the commit is not the latest, skip linting
	 * as it becomes useless if this is not the
	 * latest commit: There is no use in linting
	 * an obsolete commit.
	 */
	foreach ( $prs_implicated as $pr_item ) {
		$commits_list = vipgoci_github_prs_commits_list(
			$options['repo-owner'],
			$options['repo-name'],
			$pr_item->number,
			$options['token']
		);

		// If no commits, skip checks
		if ( empty( $commits_list ) ) {
			continue;
		}

		// Reverse array, so we get the last commit first
		$commits_list = array_reverse( $commits_list );


		// If latest commit to the PR, we do not care at all
		if ( $commits_list[0] === $options['commit'] ) {
			continue;
		}

		/*
		 * At this point, we have found an inconsistency;
		 * the commit we are working with is not the latest
		 * to the Pull-Request, and we have to deal with that.
		 */

		if (
			( true === $options['lint'] ) &&
			( false === $options['phpcs'] )
		) {
			vipgoci_sysexit(
				'The current commit is not the latest one ' .
					'to the Pull-Request, skipping ' .
					'linting, and not doing PHPCS ' .
					'-- nothing to do',
				array(
					'repo_owner' => $options['repo-owner'],
					'repo_name' => $options['repo-name'],
					'pr_number' => $pr_item->number,
				),
				VIPGOCI_EXIT_NORMAL
			);
		}

		else if (
			( true === $options['lint'] ) &&
			( true === $options['phpcs'] )
		) {
			// Skip linting, useless if not latest commit
			$options['lint'] = false;

			vipgoci_log(
				'The current commit is not the latest ' .
					'one to the Pull-Request, ' .
					'skipping linting',
				array(
					'repo_owner' => $options['repo-owner'],
					'repo_name' => $options['repo-name'],
					'pr_number' => $pr_item->number,
				)
			);
		}

		/*
		 * As for lint === false && true === phpcs,
		 * we do not care, as then we will not be linting.
		 */

		unset( $commits_list );
	}


	/*
	 * Init stats
	 */
	vipgoci_stats_init(
		$options,
		$prs_implicated,
		$results
	);

	/*
	 * Clean up old comments made by us previously
	 */
	vipgoci_github_pr_comments_cleanup(
		$options['repo-owner'],
		$options['repo-name'],
		$options['commit'],
		$options['token'],
		$options['branches-ignore'],
		array(
			VIPGOCI_SYNTAX_ERROR_STR,
			VIPGOCI_GITHUB_ERROR_STR,
			VIPGOCI_REVIEW_COMMENTS_TOTAL_MAX,
		),
		$options['dry-run']
	);

	/*
	 * Run all checks requested and store the
	 * results in an array
	 */

	if ( true === $options['lint'] ) {
		vipgoci_lint_scan_commit(
			$options,
			$results['issues'],
			$results['stats'][ VIPGOCI_STATS_LINT ]
		);
	}

	/*
	 * Note: We run this, even if linting fails, to make sure
	 * to catch all errors incrementally.
	 */

	if ( true === $options['phpcs'] ) {
		vipgoci_phpcs_scan_commit(
			$options,
			$results['issues'],
			$results['stats'][ VIPGOCI_STATS_PHPCS ]
		);
	}

	/*
	 * If to do auto-approvals, then do so now.
	 * First ask all 'auto-approval modules'
	 * to do their scanning, collecting all files that
	 * can be auto-approved, and then actually do the
	 * auto-approval if possible.
	 */
	if ( true === $options['autoapprove'] ) {
		/*
		 * If to auto-approve based on file-types,
		 * scan through the files in the PR, and
		 * register which can be auto-approved.
		 */
		$auto_approved_files_arr = array();

		if ( ! empty( $options[ 'autoapprove-filetypes' ] ) ) {
			vipgoci_ap_file_types(
				$options,
				$auto_approved_files_arr
			);
		}

		/*
		 * Do scanning of all altered files, using
		 * the hashes-to-hashes database API, collecting
		 * which files can be auto-approved.
		 */

		if ( true === $options['hashes-api'] ) {
			vipgoci_ap_hashes_api_scan_commit(
				$options,
				$results['issues'],
				$results['stats'][ VIPGOCI_STATS_HASHES_API ],
				$auto_approved_files_arr
			);
		}

		if ( true === $options['svg-checks'] ) {
			vipgoci_ap_svg_files(
				$options,
				$auto_approved_files_arr
			);
		}

		vipgoci_auto_approval(
			$options,
			$auto_approved_files_arr,
			$results // FIXME: dry-run
		);
	}


	/*
	 * Remove issues from $results for files
	 * that are approved in hashes-to-hashes API.
	 */

	vipgoci_approved_files_comments_remove(
		$options,
		$results,
		$auto_approved_files_arr
	);

	/*
	 * Remove comments from $results that have
	 * already been submitted.
	 */

	vipgoci_remove_existing_github_comments_from_results(
		$options,
		$prs_implicated,
		$results,
		true
	);

	/*
	 * Remove ignorable comments from $results.
	 */

	if ( ! empty( $options['review-comments-ignore'] ) ) {
		$file_issues_arr_master =
			vipgoci_results_filter_ignorable(
				$options,
				$results
			);
	}

	/*
	 * Keep records of how many issues we found.
	 */
	vipgoci_counter_update_with_issues_found(
		$results
	);

	/*
	 * Limit number of issues in $results.
	 *
	 * If set to zero, skip this part.
	 */

	if ( $options['review-comments-total-max'] > 0 ) {
		$prs_comments_maxed = array();

		vipgoci_github_results_filter_comments_to_max(
			$options,
			$results,
			$prs_comments_maxed
		);
	}

	/*
	 * Submit any remaining issues to GitHub
	 */

	vipgoci_github_pr_generic_comment_submit(
		$options['repo-owner'],
		$options['repo-name'],
		$options['token'],
		$options['commit'],
		$results,
		$options['informational-url'],
		$options['dry-run']
	);


	vipgoci_github_pr_review_submit(
		$options['repo-owner'],
		$options['repo-name'],
		$options['token'],
		$options['commit'],
		$results,
		$options['informational-url'],
		$options['dry-run'],
		$options['review-comments-max']
	);

	if ( true === $options['dismiss-stale-reviews'] ) {
		/*
		 * Dismiss any reviews that contain *only*
		 * inactive comments -- i.e. comments that
		 * are obsolete as the code has been changed.
		 *
		 * Note that we do this again here because we might
		 * just have deleted comments from a Pull-Request which
		 * would then remain without comments.
		 */

		foreach ( $prs_implicated as $pr_item ) {
			vipgoci_github_pr_reviews_dismiss_non_active_comments(
				$options,
				$pr_item->number
			);
		}
	}

	/*
	 * If we reached maximum number of
	 * comments earlier, put a message out
	 * so people actually know it.
	 */

	if ( $options['review-comments-total-max'] > 0 ) {
		foreach( array_keys(
			$prs_comments_maxed
		) as $pr_number ) {
			vipgoci_github_pr_comments_error_msg(
				$options['repo-owner'],
				$options['repo-name'],
				$options['token'],
				$options['commit'],
				$pr_number,
				VIPGOCI_REVIEW_COMMENTS_TOTAL_MAX
			);
		}
	}


	/*
	 * At this point, we have started to prepare
	 * for shutdown and exit -- no review-critical
	 * actions should be performed after this point.
	 */


	/*
	 * Send out to IRC API any alerts
	 * that are queued up.
	 */

	if (
		( ! empty( $options['irc-api-url'] ) ) &&
		( ! empty( $options['irc-api-token'] ) ) &&
		( ! empty( $options['irc-api-bot'] ) ) &&
		( ! empty( $options['irc-api-room'] ) )
	) {
		vipgoci_irc_api_alerts_send(
			$options['irc-api-url'],
			$options['irc-api-token'],
			$options['irc-api-bot'],
			$options['irc-api-room']
		);
	}

	$github_api_rate_limit_usage =
		vipgoci_github_rate_limit_usage(
			$options['token']
		);

	/*
	 * Prepare to send statistics to external service,
	 * also keep for exit-message.
	 */
	$counter_report = vipgoci_counter_report(
		VIPGOCI_COUNTERS_DUMP,
		null,
		null
	);

	/*
	 * Actually send statistics if configured
	 * to do so.
	 */

	if (
		( ! empty( $options['pixel-api-url'] ) ) &&
		( ! empty( $options['pixel-api-groupprefix' ] ) )
	) {
		vipgoci_send_stats_to_pixel_api(
			$options['pixel-api-url'],

			/*
			 * Which statistics to send.
			 */
			array(
				/*
				 * Generic statistics pertaining
				 * to all repositories.
				 */
				$options['pixel-api-groupprefix'] .
					'-actions' =>
				array(
					'github_pr_approval',
					'github_pr_non_approval',
					'github_api_request_get',
					'github_api_request_post',
					'github_api_request_put',
					'github_api_request_fetch',
					'github_api_request_delete'
				),

				/*
				 * Repository-specific statistics.
				 */
				$options['pixel-api-groupprefix'] .
					'-' .
					$options['repo-name']
				=> array(
					'github_pr_approval',
					'github_pr_non_approval',
					'github_pr_files_scanned',
					'github_pr_lines_scanned',
					'github_pr_files_linted',
					'github_pr_lines_linted',
					'github_pr_phpcs_issues',
					'github_pr_lint_issues'
				)
			),
			$counter_report
		);
	}


	/*
	 * Final logging before quitting.
	 */
	vipgoci_log(
		'Shutting down',
		array(
			'run_time_seconds'	=> time() - $startup_time,
			'run_time_measurements'	=>
				vipgoci_runtime_measure(
					VIPGOCI_RUNTIME_DUMP,
					null
				),
			'counters_report'	=> $counter_report,

			'github_api_rate_limit' =>
				$github_api_rate_limit_usage->resources->core,

			'results'		=> $results,
		)
	);


	/*
	 * Determine exit code.
	 */
	return vipgoci_exit_status(
		$results
	);
}

/*
 * 'main()' called
 */
$ret = vipgoci_run();

exit( $ret );
