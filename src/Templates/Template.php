<?php

declare(strict_types=1);

namespace Zoghal\IrnicApi\Templates;

class Template
{
	static $blocks = array();
	static $cache_path = 'cache/';
	static $cache_enabled = FALSE;

	/**
	 * Renders a view file with the given data and returns the resulting XML.
	 *
	 * @param string $file The path to the view file.
	 * @param array $data An associative array of data to be extracted into the view.
	 * @return string The rendered XML.
	 */
	static function view($file, $data = array())
	{
		$cached_file = self::cache($file);

		ob_start();
		extract($data, EXTR_SKIP);
		include $cached_file;
		$xml =  ob_get_clean();

		$xml = str_replace(array('<|?', '?|>'), array('<?', '?>'), $xml);
		$foo = new \DOMDocument('1.0');
		$foo->preserveWhiteSpace = false;
		$foo->formatOutput = true;
		$foo->loadXML($xml);
		$xml = $foo->saveXML();
		return $xml;
	}

	/**
	 * Cache the file content if cache is enabled and not expired.
	 *
	 * @param string $file The file to be cached.
	 * @return string The path to the cached file.
	 */
	static function cache($file)
	{
		if (!file_exists(self::$cache_path)) {
			mkdir(self::$cache_path, 0744);
		}
		$cached_file = self::$cache_path . str_replace(array('/'), array('_'), $file . '.xml');
		if (!self::$cache_enabled || !file_exists($cached_file) || filemtime($cached_file) < filemtime($file)) {
			$code = self::includeFiles($file);
			$code = self::compileCode($code);
			file_put_contents($cached_file, $code);
		}
		return $cached_file;
	}

	/**
	 * Clears all files within the cache directory.
	 *
	 * @throws Exception If an error occurs during file deletion.
	 */
	static function clearCache()
	{
		foreach (glob(self::$cache_path . '*') as $file) {
			unlink($file);
		}
	}

	/**
	 * Compiles the given code by performing a series of transformations on it.
	 *
	 * @param string $code The code to be compiled.
	 * @return string The compiled code.
	 */
	static function compileCode($code)
	{
		$code = self::compileBlock($code);
		$code = self::compileYield($code);
		$code = self::compileEscapedEchos($code);
		$code = self::compileEchos($code);
		$code = self::compilePHP($code);
		return $code;
	}

	/**
	 * Includes the content of the given file and replaces the {% include %} and {% extends %} tags with the content of the included file.
	 *
	 * @param string $file The path to the file to be included.
	 * @return string The content of the included file with the {% include %} and {% extends %} tags replaced.
	 */
	static function includeFiles($file)
	{
		$code = file_get_contents(self::pathFixer($file));
		$code = str_replace(array('<?', '?>'), array('<|?', '?|>'), $code);
		preg_match_all('/{% ?(extends|include) ?\'?(.*?)\'? ?%}/i', $code, $matches, PREG_SET_ORDER);
		foreach ($matches as $value) {
			$code = str_replace($value[0], self::includeFiles($value[2]), $code);
		}
		$code = preg_replace('/{% ?(extends|include) ?\'?(.*?)\'? ?%}/i', '', $code);
		return $code;
	}

	/**
	 * Compiles PHP code by replacing template tags with PHP code.
	 *
	 * @param string $code The code to be compiled.
	 * @return string The compiled PHP code.
	 */
	protected static function compilePHP($code)
	{
		return preg_replace('~\{%\s*(.+?)\s*\%}~is', '<?php $1 ?>', $code);
	}

	/**
	 * Compiles the given code by replacing template tags with PHP code that echoes the enclosed expression.
	 *
	 * @param string $code The code to be compiled.
	 * @throws Exception If an error occurs during the compilation.
	 * @return string The compiled PHP code with echo statements.
	 */
	protected static function compileEchos($code)
	{
		return preg_replace('~\{{\s*(.+?)\s*\}}~is', '<?php echo $1 ?>', $code);
	}

	/**
	 * Compiles escaped echos in the provided code by replacing triple curly braces with PHP code that
	 * echoes the HTML-escaped content of the enclosed expression.
	 *
	 * @param string $code The code to be compiled.
	 * @return string The compiled code with escaped echos replaced.
	 */
	protected static function compileEscapedEchos($code)
	{
		return preg_replace('~\{{{\s*(.+?)\s*\}}}~is', '<?php echo htmlentities($1, ENT_QUOTES, \'UTF-8\') ?>', $code);
	}

	/**
	 * Compiles the block sections in the provided code.
	 *
	 * @param string $code The code containing block sections.
	 * @return string The processed code after compiling block sections.
	 */
	protected static function compileBlock($code)
	{
		preg_match_all('/{% ?block ?(.*?) ?%}(.*?){% ?endblock ?%}/is', $code, $matches, PREG_SET_ORDER);
		foreach ($matches as $value) {
			if (!array_key_exists($value[1], self::$blocks)) self::$blocks[$value[1]] = '';
			if (strpos($value[2], '@parent') === false) {
				self::$blocks[$value[1]] = $value[2];
			} else {
				self::$blocks[$value[1]] = str_replace('@parent', self::$blocks[$value[1]], $value[2]);
			}
			$code = str_replace($value[0], '', $code);
		}
		return $code;
	}

	/**
	 * Compiles the yield blocks in the provided code.
	 *
	 * @param string $code The code to be processed.
	 * @return string The processed code after compiling yield blocks.
	 */
	protected static function compileYield($code)
	{
		foreach (self::$blocks as $block => $value) {
			$code = preg_replace('/{% ?yield ?' . $block . ' ?%}/', $value, $code);
		}
		$code = preg_replace('/{% ?yield ?(.*?) ?%}/i', '', $code);
		return $code;
	}

	/**
	 * Fixes the path by replacing directory separators and returns the absolute path.
	 *
	 * @param string $path The path to be fixed.
	 * @return string The fixed path.
	 */
	protected static function pathFixer($path)
	{
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
			$SEPARATOR = "\\";
		else
			$SEPARATOR = "/";

		return __DIR__ . $SEPARATOR . str_replace('/', $SEPARATOR, $path);
	}
}
