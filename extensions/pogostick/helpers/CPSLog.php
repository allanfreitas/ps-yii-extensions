<?php
/**
 * This file is part of the psYiiExtensions package.
 *
 * @copyright Copyright &copy; 2010 Pogostick, LLC
 * @link http://www.pogostick.com Pogostick, LLC.
 * @license http://www.pogostick.com/licensing
 */

/**
 * Logging helper methods
 *
 * @package	 psYiiExtensions
 * @subpackage	 helpers
 *
 * @author		 Jerry Ablan <jablan@pogostick.com>
 * @version	 SVN: $Id: CPSLog.php 401 2010-08-31 21:04:18Z jerryablan@gmail.com $
 * @since		 v1.0.6
 *
 * @filesource
 */
class CPSLog implements IPSBase
{
	//**************************************************************************
	//* Constants
	//**************************************************************************

	/**
	 * @const string The string to use for each log entry indentation
	 */
	const
		INDENT_STRING = '  ';

	//********************************************************************************
	//* Private Members
	//********************************************************************************

	/**
	 * @staticvar boolean If true, all applicable log entries will be echoed to the screen
	 */
	public static $echoData = false;
	/**
	 * @staticvar string Prepended to each log entry before writing.
	 */
	public static $prefix = null;
	/**
	 * @staticvar integer The base level for getting source of log entry
	 */
	public static $baseLevel = 4;
	/**
	 * @staticvar integer The current indent level
	 */
	public static $currentIndent = 0;
	/**
	 * @staticvar string
	 */
	protected static $_defaultLevelIndicator = '.';
	/**
	 * @staticvar array
	 */
	protected static $_levelIndicators = array(
		'info' => '*',
		'notice' => '?',
		'warning' => '-',
		'error' => '!',
	);

	//********************************************************************************
	//* Public Methods
	//********************************************************************************

	/**
	 * Creates an 'info' log entry
	 * @param string $category The message category. Please use only word letters. Note, category 'yii' is reserved for Yii framework core code use. See {@link CPhpMessageSource} for more interpretation about message category.
	 * @param string $message The message to log
	 * @param string $level The message level
	 * @param array $options Parameters to be applied to the message using <code>strtr</code>.
	 * @param string $source Which message source application component to use.
	 * @param string $language The target language. If null (default), the {@link CApplication::getLanguage application language} will be used.
	 * @return string
	 */
	public static function log( $category, $message = null, $level = 'info', $options = array(), $source = null, $language = null )
	{
		//	Allow null categories
		if ( null !== $category && null === $message )
		{
			$message = $category;
			$category = null;
		}

		if ( null === $category )
			$category = self::_getCallingMethod();

		//	Get the indent, if any
		$_unindent = ( 0 > ( $_newIndent = self::_processMessage( $message ) ) );

		$_levelList = explode( '|', $level );
		$_logEntry = $message;

		//	Handle writing to multiple levels at once.
		foreach ( $_levelList as $_level )
		{
			$_indicator = ( in_array( $_level, self::$_levelIndicators ) ? self::$_levelIndicators[$_level] : self::$_defaultLevelIndicator );
			$_logEntry = self::$prefix . ( class_exists( 'Yii' ) ? Yii::t( $category, $message, $options, $source, $language ) : $message );

			//	Echo if we're CLI && user wants it...
			if ( PS::isCLI() && self::$echoData )
			{
				echo date( 'Y.m.d h.i.s' ) . '[' . strtoupper( $_level[0] ) . '] ' .
					sprintf( '[%35.35s]', $category ) .
					$_logEntry;
				flush();
			}

			//	Indent...
			$_tempIndent = self::$currentIndent;

			if ( $_unindent )
			{
				$_tempIndent--;
			}

			if ( $_tempIndent < 0 )
			{
				$_tempIndent = 0;
			}

			$_logEntry = str_repeat( self::INDENT_STRING, $_tempIndent ) . $_indicator . ' ' . $message;

			try
			{
				if ( @class_exists( 'Yii' ) )
					Yii::log( $_logEntry, $_level, $category );
				else if ( @class_exists( 'SimpleLogger' ) )
					@SimpleLogger::getInstance()->write( $_logEntry, 6 );
				else
					@error_log( $_logEntry );
			}
			catch ( Exception $_ex )
			{
				@error_log( 'CPSLog::_log exception: ' . $_ex->getMessage() );
				@error_log( '             Log Entry: ' . $_logEntry );
			}
		}

		//	Set indent level...
		self::$currentIndent += $_newIndent;

		return $_logEntry;
	}

	/**
	 * Creates an 'info' log entry
	 * @param mixed $category The message category. Please use only word letters. Note, category 'yii' is reserved for Yii framework core code use. See {@link CPhpMessageSource} for more interpretation about message category.
	 * @param mixed $message The message to log
	 * @param mixed $options Parameters to be applied to the message using <code>strtr</code>.
	 * @param mixed $source Which message source application component to use.
	 * @param mixed $language The target language. If null (default), the {@link CApplication::getLanguage application language} will be used.
	 * @return string
	 */
	public static function info( $category, $message = null, $options = array(), $source = null, $language = null )
	{
		return self::log( $category, $message, 'info', $options, $source, $language );
	}

	/**
	 * Creates an 'error' log entry
	 * @param mixed $category The message category. Please use only word letters. Note, category 'yii' is reserved for Yii framework core code use. See {@link CPhpMessageSource} for more interpretation about message category.
	 * @param mixed $message The message to log
	 * @param mixed $options Parameters to be applied to the message using <code>strtr</code>.
	 * @param mixed $source Which message source application component to use.
	 * @param mixed $language The target language. If null (default), the {@link CApplication::getLanguage application language} will be used.
	 * @return string
	 */
	public static function error( $category, $message = null, $options = array(), $source = null, $language = null )
	{
		return self::log( $category, $message, 'error', $options, $source, $language );
	}

	/**
	 * Creates an 'warning' log entry
	 * @param mixed $category The message category. Please use only word letters. Note, category 'yii' is reserved for Yii framework core code use. See {@link CPhpMessageSource} for more interpretation about message category.
	 * @param mixed $message The message to log
	 * @param mixed $options Parameters to be applied to the message using <code>strtr</code>.
	 * @param mixed $source Which message source application component to use.
	 * @param mixed $language The target language. If null (default), the {@link CApplication::getLanguage application language} will be used.
	 * @return string
	 */
	public static function warning( $category, $message = null, $options = array(), $source = null, $language = null )
	{
		self::log( $category, $message, 'warning', $options, $source, $language );
	}

	/**
	 * Creates an 'trace' log entry
	 * @param mixed $category The message category. Please use only word letters. Note, category 'yii' is reserved for Yii framework core code use. See {@link CPhpMessageSource} for more interpretation about message category.
	 * @param mixed $message The message to log
	 * @param mixed $options Parameters to be applied to the message using <code>strtr</code>.
	 * @param mixed $source Which message source application component to use.
	 * @param mixed $language The target language. If null (default), the {@link CApplication::getLanguage application language} will be used.
	 * @return string
	 */
	public static function trace( $category, $message = null, $options = array(), $source = null, $language = null )
	{
		if ( defined( 'PYE_TRACE_LEVEL' ) || defined( 'YII_DEBUG' ) || defined( 'YII_TRACE_LEVEL' ) )
			return self::log( $category, $message, 'trace', $options, $source, $language );
	}

	/**
	 * Creates an 'api' log entry
	 * @param string $apiCall The API call made
	 * @param mixed $response The API response to log
	 * @return string
	 */
	public static function api( $apiCall, $response = null )
	{
		return self::log( $apiCall, PHP_EOL . print_r( $response, true ) . PHP_EOL, 'api' );
	}

	/**
	 * Creates a 'debug' log entry
	 * @param mixed $category The message category. Please use only word letters. Note, category 'yii' is reserved for Yii framework core code use. See {@link CPhpMessageSource} for more interpretation about message category.
	 * @param mixed $message The message to log
	 * @return string
	 */
	public static function debug( $category, $message = null )
	{
		return self::log( $category, $message, 'debug' );
	}

	/**
	 * Creates an user-defined log entry
	 * @param mixed $message The message
	 * @param string $level The message level
	 * @param mixed $category The message category. Please use only word letters. Note, category 'yii' is reserved for Yii framework core code use. See {@link CPhpMessageSource} for more interpretation about message category.
	 * @return string
	 */
	public static function write( $message, $level = null, $category = null )
	{
		return self::log( $category, $message, $level );
	}

	/**
	 * Safely decrements the current indent level
	 */
	public static function decrementIndent( $howMuch = 1 )
	{
		self::$currentIndent -= $howMuch;

		if ( self::$currentIndent < 0 )
		{
			self::$currentIndent = 0;
		}
	}

	//*************************************************************************
	//* Private Methods
	//*************************************************************************

	/**
	 * Returns the name of the method that made the call
	 * @param integer $level The level of the call
	 * @return string
	 */
	protected static function _getCallingMethod( $level = null )
	{
		$_className = get_class();
		$level = ( null === $level ? self::$baseLevel : $level );

		try
		{
			$_trace = debug_backtrace();

			while ( $level >= 0 && isset( $_trace[$level] ) )
			{
				if ( null === ( $_caller = PS::o( $_trace, $level ) ) )
				{
					break;
				}

				//	If we see our self, then we must go again
				if ( null !== ( $_class = PS::o( $_caller, 'class' ) ) && $_class != $_className )
				{
					return $_class . '::' . PS::o( $_caller, 'function' );
				}

				//	If we see our self, then we must go again
				if ( $_className != basename( PS::o( $_caller, 'file' ) ) )
				{
					return basename( PS::o( $_caller, 'file' ) ) . '::' .
						PS::o( $_caller, 'function' ) .
						' (Line ' . PS::o( $_caller, 'line' ) . ')';
				}

				$level--;
			}
		}
		catch ( Exception $_ex )
		{
			//	Error logging shouldn't create more errors...
		}

		return null;
	}

	/**
	 * Processes the indent level for the messages
	 * @param string $message
	 * @return integer The indent difference AFTER this message
	 */
	protected static function _processMessage( &$message )
	{
		$_newIndent = 0;

		switch ( substr( $message, 0, 2 ) )
		{
			case '>>':
				$_newIndent = 1;
				$message = trim( substr( $message, 2 ) );
				break;

			case '<<':
				$_newIndent = -1;
				$message = trim( substr( $message, 2 ) );
				break;
		}

		return $_newIndent;
	}

	//*************************************************************************
	//* Properties
	//*************************************************************************

	/**
	 * @param $defaultLevelIndicator
	 */
	public static function setDefaultLevelIndicator( $defaultLevelIndicator )
	{
		self::$_defaultLevelIndicator = $defaultLevelIndicator;
	}

	/**
	 * @return string
	 */
	public static function getDefaultLevelIndicator()
	{
		return self::$_defaultLevelIndicator;
	}

	/**
	 * @param $levelIndicators
	 */
	public static function setLevelIndicators( $levelIndicators )
	{
		self::$_levelIndicators = $levelIndicators;
	}

	/**
	 * @return array
	 */
	public static function getLevelIndicators()
	{
		return self::$_levelIndicators;
	}

	/**
	 * @param $baseLevel
	 */
	public static function setBaseLevel( $baseLevel )
	{
		self::$baseLevel = $baseLevel;
	}

	/**
	 * @return int
	 */
	public static function getBaseLevel()
	{
		return self::$baseLevel;
	}

	/**
	 * @param $currentIndent
	 */
	public static function setCurrentIndent( $currentIndent )
	{
		self::$currentIndent = $currentIndent;
	}

	/**
	 * @return int
	 */
	public static function getCurrentIndent()
	{
		return self::$currentIndent;
	}

	/**
	 * @param $echoData
	 */
	public static function setEchoData( $echoData )
	{
		self::$echoData = $echoData;
	}

	/**
	 * @return bool
	 */
	public static function getEchoData()
	{
		return self::$echoData;
	}

	/**
	 * @param $prefix
	 */
	public static function setPrefix( $prefix )
	{
		self::$prefix = $prefix;
	}

	/**
	 * @return null
	 */
	public static function getPrefix()
	{
		return self::$prefix;
	}
}