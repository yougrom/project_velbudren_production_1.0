<?php

class wfWAFSQLiParser extends wfWAFBaseParser {

	const FLAG_PARSE_MYSQL_PORTABLE_COMMENTS = wfWAFSQLiLexer::FLAG_TOKENIZE_MYSQL_PORTABLE_COMMENTS;

	/**
	 * @param string $param
	 * @return bool
	 */
	public static function testForSQLi($param) {
		static $instance;
		static $tests;
		if (!$instance) {
			$instance = new self(new wfWAFSQLiLexer());
		}
		if (!$tests) {
			// SQL statement and token count for lexer
			$tests = array(
				array('%s', 1),
				array('SELECT * FROM t WHERE i = %s ', 8),
				array("SELECT * FROM t WHERE i = '%s' ", 8),
				array('SELECT * FROM t WHERE i = "%s" ', 8),
				array('SELECT * FROM t WHERE i = (%s) ', 10),
				array("SELECT * FROM t WHERE i = ('%s') ", 10),
				array('SELECT * FROM t WHERE i = ("%s") ', 10),
				array('SELECT * FROM t WHERE i = ((%s)) ', 12),
				array("SELECT * FROM t WHERE i = (('%s')) ", 12),
				array('SELECT * FROM t WHERE i = (("%s")) ', 12),
				array('SELECT * FROM t WHERE i = (((%s))) ', 14),
				array("SELECT * FROM t WHERE i = ((('%s'))) ", 14),
				array('SELECT * FROM t WHERE i = ((("%s"))) ', 14),

				array('SELECT * FROM t WHERE i = %s and j = (1
) ', 14),
				array("SELECT * FROM t WHERE i = '%s' and j = (1
) ", 14),
				array('SELECT * FROM t WHERE i = "%s" and j = (1
) ', 14),

				array('SELECT MATCH(t) AGAINST (%s) from t ', 11),
				array("SELECT MATCH(t) AGAINST ('%s') from t ", 11),
				array('SELECT MATCH(t) AGAINST ("%s") from t ', 11),

//				array('SELECT CASE WHEN %s THEN 1 ELSE 0 END from t ', 11),
//				array("SELECT CASE WHEN '%s' THEN 1 ELSE 0 END from t ", 11),
//				array('SELECT CASE WHEN "%s" THEN 1 ELSE 0 END from t ', 11),
//
//				array('SELECT (CASE WHEN (%s) THEN 1 ELSE 0 END) from t ', 15),
//				array("SELECT (CASE WHEN ('%s') THEN 1 ELSE 0 END) from t ", 15),
//				array('SELECT (CASE WHEN ("%s") THEN 1 ELSE 0 END) from t ', 15),

				array('SELECT * FROM (select %s) ', 7),
				array("SELECT * FROM (select '%s') ", 7),
				array('SELECT * FROM (select "%s") ', 7),
				array('SELECT * FROM (select (%s)) ', 9),
				array("SELECT * FROM (select ('%s')) ", 9),
				array('SELECT * FROM (select ("%s")) ', 9),
				array('SELECT * FROM (select ((%s))) ', 11),
				array("SELECT * FROM (select (('%s'))) ", 11),
				array('SELECT * FROM (select (("%s"))) ', 11),
//
//				array('SELECT * FROM t JOIN t2 on i = %s ', 10),
//				array("SELECT * FROM t JOIN t2 on i = '%s' ", 10),
//				array('SELECT * FROM t JOIN t2 on i = "%s" ', 10),
//				array('SELECT * FROM t JOIN t2 on i = (%s) ', 12),
//				array("SELECT * FROM t JOIN t2 on i = ('%s') ", 12),
//				array('SELECT * FROM t JOIN t2 on i = ("%s") ', 12),
//				array('SELECT * FROM t JOIN t2 on i = ((%s)) ', 14),
//				array("SELECT * FROM t JOIN t2 on i = (('%s')) ", 14),
//				array('SELECT * FROM t JOIN t2 on i = (("%s")) ', 14),
//				array('SELECT * FROM t JOIN t2 on i = (((%s))) ', 16),
//				array("SELECT * FROM t JOIN t2 on i = ((('%s'))) ", 16),
//				array('SELECT * FROM t JOIN t2 on i = ((("%s"))) ', 16),

				array('SELECT * FROM %s ', 4),
				array('INSERT INTO t (col) VALUES (%s) ', 10),
				array("INSERT INTO t (col) VALUES ('%s') ", 10),
				array('INSERT INTO t (col) VALUES ("%s") ', 10),
				array('UPDATE t1 SET col1 = %s ', 6),
				array('UPDATE t1 SET col1 = \'%s\' ', 6),
			);
		}
		$lexerFlags = array(0, wfWAFSQLiLexer::FLAG_TOKENIZE_MYSQL_PORTABLE_COMMENTS);
		foreach ($lexerFlags as $flags) {
			foreach ($tests as $test) {
//				$startTime = microtime(true);
				list($sql, $expectedTokenCount) = $test;
				try {
					$instance->setFlags($flags);
					$instance->setSubject(sprintf($sql, $param));
					if (($instance->hasMoreThanNumTokens($expectedTokenCount) && $instance->evaluate())
						|| $instance->hasMultiplePortableCommentVersions()) {
//						printf("%s took %f seconds\n", $sql, microtime(true) - $startTime);
						return true;
					}
//					printf("%s took %f seconds\n", $sql, microtime(true) - $startTime);
				} catch (wfWAFParserSyntaxError $e) {

				}
			}
		}
		return false;
	}

	private $subject;

	/**
	 * @var int
	 */
	private $flags;
	/** @var wfWAFSQLiLexer */
	protected $lexer;
	private $portableCommentVersions = array();

	private $intervalUnits = array(
		'SECOND',
		'MINUTE',
		'HOUR',
		'DAY_SYM',
		'WEEK',
		'MONTH',
		'QUARTER',
		'YEAR',
		'SECOND_MICROSECOND',
		'MINUTE_MICROSECOND',
		'MINUTE_SECOND',
		'HOUR_MICROSECOND',
		'HOUR_SECOND',
		'HOUR_MINUTE',
		'DAY_MICROSECOND',
		'DAY_SECOND',
		'DAY_MINUTE',
		'DAY_HOUR',
		'YEAR_MONTH',
	);

	private $keywords = array(
		'ID',
		'TIME',
		'DATE',
		'SQLTIME',
		'ACCESSIBLE',
		'ADD',
		'ALL',
		'ALTER',
		'ANALYZE',
		'AND',
		'AS',
		'ASC',
		'ASENSITIVE',
		'BEFORE',
		'BETWEEN',
		'BIGINT',
		'BINARY',
		'BLOB',
		'BOTH',
		'BY',
		'CALL',
		'CASCADE',
		'CASE',
		'CHANGE',
		'CHAR',
		'CHARACTER',
		'CHECK',
		'COLLATE',
		'COLUMN',
		'CONDITION',
		'CONSTRAINT',
		'CONTINUE',
		'CONVERT',
		'CREATE',
		'CROSS',
		'CURRENT_DATE',
		'CURRENT_TIME',
		'CURRENT_TIMESTAMP',
		'CURRENT_USER',
		'CURSOR',
		'DATABASE',
		'DATABASES',
		'DAY_HOUR',
		'DAY_MICROSECOND',
		'DAY_MINUTE',
		'DAY_SECOND',
		'DEC',
		'DECIMAL',
		'DECLARE',
		'DEFAULT',
		'DELAYED',
		'DELETE',
		'DESC',
		'DESCRIBE',
		'DETERMINISTIC',
		'DISTINCT',
		'DISTINCTROW',
		'DIV',
		'DOUBLE',
		'DROP',
		'DUAL',
		'EACH',
		'ELSE',
		'ELSEIF',
		'ENCLOSED',
		'ESCAPED',
		'EXISTS',
		'EXIT',
		'EXPLAIN',
		'FALSE',
		'FETCH',
		'FLOAT',
		'FLOAT4',
		'FLOAT8',
		'FOR',
		'FORCE',
		'FOREIGN',
		'FROM',
		'FULLTEXT',
		'GRANT',
		'GROUP',
		'HAVING',
		'HIGH_PRIORITY',
		'HOUR_MICROSECOND',
		'HOUR_MINUTE',
		'HOUR_SECOND',
		'IF',
		'IGNORE',
		'IN',
		'INDEX',
		'INFILE',
		'INNER',
		'INOUT',
		'INSENSITIVE',
		'INSERT',
		'INT',
		'INT1',
		'INT2',
		'INT3',
		'INT4',
		'INT8',
		'INTEGER',
		'INTERVAL',
		'INTO',
		'IS',
		'ITERATE',
		'JOIN',
		'KEY',
		'KEYS',
		'KILL',
		'LEADING',
		'LEAVE',
		'LEFT',
		'LIKE',
		'LIMIT',
		'LINEAR',
		'LINES',
		'LOAD',
		'LOCALTIME',
		'LOCALTIMESTAMP',
		'LOCK',
		'LONG',
		'LONGBLOB',
		'LONGTEXT',
		'LOOP',
		'LOW_PRIORITY',
		'MASTER_SSL_VERIFY_SERVER_CERT',
		'MATCH',
		'MEDIUMBLOB',
		'MEDIUMINT',
		'MEDIUMTEXT',
		'MIDDLEINT',
		'MINUTE_MICROSECOND',
		'MINUTE_SECOND',
		'MOD',
		'MODIFIES',
		'NATURAL',
		'NOT',
		'NO_WRITE_TO_BINLOG',
		'NULL',
		'NUMERIC',
		'ON',
		'OPTIMIZE',
		'OPTION',
		'OPTIONALLY',
		'OR',
		'ORDER',
		'OUT',
		'OUTER',
		'OUTFILE',
		'PRECISION',
		'PRIMARY',
		'PROCEDURE',
		'PURGE',
		'RANGE',
		'READ',
		'READS',
		'READ_WRITE',
		'REAL',
		'REFERENCES',
		'REGEXP',
		'RELEASE',
		'RENAME',
		'REPEAT',
		'REPLACE',
		'REQUIRE',
		'RESTRICT',
		'RETURN',
		'REVOKE',
		'RIGHT',
		'RLIKE',
		'SCHEMA',
		'SCHEMAS',
		'SECOND_MICROSECOND',
		'SELECT',
		'SENSITIVE',
		'SEPARATOR',
		'SET',
		'SHOW',
		'SMALLINT',
		'SPATIAL',
		'SPECIFIC',
		'SQL',
		'SQLEXCEPTION',
		'SQLSTATE',
		'SQLWARNING',
		'SQL_BIG_RESULT',
		'SQL_CALC_FOUND_ROWS',
		'SQL_SMALL_RESULT',
		'SSL',
		'STARTING',
		'STRAIGHT_JOIN',
		'TABLE',
		'TERMINATED',
		'THEN',
		'TINYBLOB',
		'TINYINT',
		'TINYTEXT',
		'TO',
		'TRAILING',
		'TRIGGER',
		'TRUE',
		'UNDO',
		'UNION',
		'UNIQUE',
		'UNLOCK',
		'UNSIGNED',
		'UPDATE',
		'USAGE',
		'USE',
		'USING',
		'UTC_DATE',
		'UTC_TIME',
		'UTC_TIMESTAMP',
		'VALUES',
		'VARBINARY',
		'VARCHAR',
		'VARCHARACTER',
		'VARYING',
		'WHEN',
		'WHERE',
		'WHILE',
		'WITH',
		'WRITE',
		'XOR',
		'YEAR_MONTH',
		'ZEROFILL',
		'ACCESSIBLE',
		'LINEAR',
		'MASTER_SSL_VERIFY_SERVER_CERT',
		'RANGE',
		'READ_ONLY',
		'READ_WRITE',
	);

	private $numberFunctions = array(
		'ABS',
		'ACOS',
		'ASIN',
		'ATAN2',
		'ATAN',
		'CEIL',
		'CEILING',
		'CONV',
		'COS',
		'COT',
		'CRC32',
		'DEGREES',
		'EXP',
		'FLOOR',
		'LN',
		'LOG10',
		'LOG2',
		'LOG',
		'MOD',
		'PI',
		'POW',
		'POWER',
		'RADIANS',
		'RAND',
		'ROUND',
		'SIGN',
		'SIN',
		'SQRT',
		'TAN',
		'TRUNCATE',
	);

	private $charFunctions = array(
		'ASCII_SYM',
		'BIN',
		'BIT_LENGTH',
		'CHAR_LENGTH',
		'CHAR',
		'CONCAT_WS',
		'CONCAT',
		'ELT',
		'EXPORT_SET',
		'FIELD',
		'FIND_IN_SET',
		'FORMAT',
		'FROM_BASE64',
		'HEX',
		'INSERT',
		'INSTR',
		'LEFT',
		'LENGTH',
		'LOAD_FILE',
		'LOCATE',
		'LOWER',
		'LPAD',
		'LTRIM',
		'MAKE_SET',
		'MID',
		'OCT',
		'ORD',
		'QUOTE',
		'REPEAT',
		'REPLACE',
		'REVERSE',
		'RIGHT',
		'RPAD',
		'RTRIM',
		'SOUNDEX',
		'SPACE',
		'STRCMP',
		'SUBSTRING_INDEX',
		'SUBSTRING',
		'TO_BASE64',
		'TRIM',
		'UNHEX',
		'UPPER',
		'WEIGHT_STRING',
	);

	private $timeFunctions = array(
		'ADDDATE',
		'ADDTIME',
		'CONVERT_TZ',
		'CURDATE',
		'CURTIME',
		'DATE_ADD',
		'DATE_FORMAT',
		'DATE_SUB',
		'DATE_SYM',
		'DATEDIFF',
		'DAYNAME',
		'DAYOFMONTH',
		'DAYOFWEEK',
		'DAYOFYEAR',
		'EXTRACT',
		'FROM_DAYS',
		'FROM_UNIXTIME',
		'GET_FORMAT',
		'HOUR',
		'LAST_DAY ',
		'MAKEDATE',
		'MAKETIME ',
		'MICROSECOND',
		'MINUTE',
		'MONTH',
		'MONTHNAME',
		'NOW',
		'PERIOD_ADD',
		'PERIOD_DIFF',
		'QUARTER',
		'SEC_TO_TIME',
		'SECOND',
		'STR_TO_DATE',
		'SUBTIME',
		'SYSDATE',
		'TIME_FORMAT',
		'TIME_TO_SEC',
		'TIME_SYM',
		'TIMEDIFF',
		'TIMESTAMP',
		'TIMESTAMPADD',
		'TIMESTAMPDIFF',
		'TO_DAYS',
		'TO_SECONDS',
		'UNIX_TIMESTAMP',
		'UTC_DATE',
		'UTC_TIME',
		'UTC_TIMESTAMP',
		'WEEK',
		'WEEKDAY',
		'WEEKOFYEAR',
		'YEAR',
		'YEARWEEK',
	);

	private $otherFunctions = array(
		'MAKE_SET', 'LOAD_FILE',
		'IF', 'IFNULL',
		'AES_ENCRYPT', 'AES_DECRYPT',
		'DECODE', 'ENCODE',
		'DES_DECRYPT', 'DES_ENCRYPT',
		'ENCRYPT', 'MD5',
		'OLD_PASSWORD', 'PASSWORD',
		'BENCHMARK', 'CHARSET', 'COERCIBILITY', 'COLLATION', 'CONNECTION_ID',
		'CURRENT_USER', 'DATABASE', 'SCHEMA', 'USER', 'SESSION_USER', 'SYSTEM_USER',
		'VERSION_SYM',
		'FOUND_ROWS', 'LAST_INSERT_ID', 'DEFAULT',
		'GET_LOCK', 'RELEASE_LOCK', 'IS_FREE_LOCK', 'IS_USED_LOCK', 'MASTER_POS_WAIT',
		'INET_ATON', 'INET_NTOA',
		'NAME_CONST',
		'SLEEP',
		'UUID',
		'VALUES',
	);

	private $groupFunctions = array(
		'AVG', 'COUNT', 'MAX_SYM', 'MIN_SYM', 'SUM',
		'BIT_AND', 'BIT_OR', 'BIT_XOR',
		'GROUP_CONCAT',
		'STD', 'STDDEV', 'STDDEV_POP', 'STDDEV_SAMP',
		'VAR_POP', 'VAR_SAMP', 'VARIANCE',
	);


	/**
	 * @param wfWAFSQLiLexer $lexer
	 * @param string $subject
	 * @param int $flags
	 */
	public function __construct($lexer, $subject = null, $flags = 0) {
		parent::__construct($lexer);
		$this->setSubject($subject);
		$this->setFlags($flags);
	}

	protected function _init() {
		$this->portableCommentVersions = array();
		$this->index = -1;
	}

	/**
	 * @param int $num
	 * @return bool
	 */
	public function hasMoreThanNumTokens($num) {
		$this->_init();

		$savePoint = $this->index;
		for ($i = 0; $i <= $num; $i++) {
			if (!$this->nextToken()) {
				$this->index = $savePoint;
				return false;
			}
		}
		$this->index = $savePoint;
		return true;
	}

	/**
	 * @return bool
	 */
	public function evaluate() {
		try {
			$this->parse();
			return true;
		} catch (wfWAFParserSyntaxError $e) {
			return false;
		}
	}

	public function parse() {
		$this->_init();
		if (
			$this->parseSelectStatement()
			|| $this->parseInsertStatement()
			|| $this->parseUpdateStatement()
//			|| $this->parseDeleteStatement()
//			|| $this->parseReplaceStatement()
		) {
			$token = $this->nextToken();
			if ($token && !$this->isTokenOfType($token, wfWAFSQLiLexer::SEMICOLON)) {
				$this->triggerSyntaxError($this->currentToken());
			}
		} else {
			$this->triggerSyntaxError($this->expectNextToken());
		}
	}

	/**
	 * @param int $index
	 * @return bool
	 */
	protected function getToken($index) {
		if (array_key_exists($index, $this->tokens)) {
			return $this->tokens[$index];
		}
		while ($token = $this->getLexer()->nextToken()) {
			if (!$this->isCommentToken($token)) {
				$this->tokens[$index] = $token;
				return $this->tokens[$index];
			}
		}
		return false;
	}


	/**
	 * @param wfWAFLexerToken $token
	 * @return bool
	 */
	public function isCommentToken($token) {
		if ($this->isTokenOfType($token, wfWAFSQLiLexer::MYSQL_PORTABLE_COMMENT_START)) {
			$this->portableCommentVersions[(int) preg_replace('/[^\d]/', '', $token->getValue())] = 1;
		}

		return $this->isTokenOfType($token, array(
			wfWAFSQLiLexer::SINGLE_LINE_COMMENT,
			wfWAFSQLiLexer::MULTI_LINE_COMMENT,
			wfWAFSQLiLexer::MYSQL_PORTABLE_COMMENT_START,
			wfWAFSQLiLexer::MYSQL_PORTABLE_COMMENT_END,
		));
	}

	public function hasMultiplePortableCommentVersions() {
		return count($this->portableCommentVersions) > 1;
	}

	/**
	 * Expects the next token to be an identifier with the supplied case-insensitive value
	 *
	 * @param $keyword
	 * @return wfWAFLexerToken
	 * @throws wfWAFParserSyntaxError
	 */
	protected function expectNextIdentifierEquals($keyword) {
		$nextToken = $this->expectNextToken();
		$this->expectTokenTypeEquals($nextToken, wfWAFSQLiLexer::UNQUOTED_IDENTIFIER);
		if ($nextToken->getLowerCaseValue() !== wfWAFUtils::strtolower($keyword)) {
			$this->triggerSyntaxError($nextToken);
		}
		return $nextToken;
	}

	private function parseSelectStatement() {
		$startIndex = $this->index;
		$hasSelect = false;
		while ($this->parseSelectExpression()) {
			$hasSelect = true;
			$savePoint = $this->index;
			if ($this->isIdentifierWithValue($this->nextToken(), 'union')) {
				$hasSelect = false;
				if (!$this->isIdentifierWithValue($this->nextToken(), 'all')) {
					$this->index--;
				}
				continue;
			}
			$this->index = $savePoint;
			break;
		}
		if ($hasSelect) {
			return true;
		}
		$this->index = $startIndex;
		return false;
	}

	private function parseSelectExpression() {
		$savePoint = $this->index;
		if ($this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::OPEN_PARENTHESIS) &&
			$this->parseSelectExpression() &&
			$this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::CLOSE_PARENTHESIS)
		) {
			return true;
		}
		$this->index = $savePoint;

		if ($this->parseSelect()) {
			if ($this->parseFrom()) {
				$this->parseWhere();
				$this->parseProcedure();
				$this->parseGroupBy();
				$this->parseHaving();
			}
			$this->parseOrderBy();
			$this->parseLimit();

			return true;
		}
		return false;
	}

	/**
	 * @throws wfWAFParserSyntaxError
	 */
	private function parseSelect() {
		$startPoint = $this->index;
		if ($this->isIdentifierWithValue($this->nextToken(), 'select')) {
			$optionalSelectParamsRegex = '/ALL|DISTINCT(?:ROW)?|HIGH_PRIORITY|MAX_STATEMENT_TIME|STRAIGHT_JOIN|SQL_SMALL_RESULT|SQL_BIG_RESULT|SQL_BUFFER_RESULT|SQL_CACHE|SQL_NO_CACHE|SQL_CALC_FOUND_ROWS/i';
			while (true) {
				$savePoint = $this->index;
				$token = $this->nextToken();
				if ($token) {
					$value = $token->getLowerCaseValue();
					if (preg_match($optionalSelectParamsRegex, $value)) {
						if ($value == 'max_statement_time') {
							$this->expectTokenTypeEquals($this->expectNextToken(), wfWAFSQLiLexer::EQUALS_SYMBOL);
							$this->expectTokenTypeInArray($this->expectNextToken(), array(
								wfWAFSQLiLexer::INTEGER_LITERAL,
								wfWAFSQLiLexer::BINARY_NUMBER_LITERAL,
								wfWAFSQLiLexer::HEX_NUMBER_LITERAL,
								wfWAFSQLiLexer::BINARY_NUMBER_LITERAL,
							));
						}
						continue;
					}
				}
				$this->index = $savePoint;
				break;
			}
			return $this->parseSelectList();
		}
		$this->index = $startPoint;
		return false;
	}

	/**
	 * @throws wfWAFParserSyntaxError
	 */
	private function parseSelectList() {
		$startPoint = $this->index;
		$hasSelects = false;
		if ($this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::ASTERISK)) {
			$hasSelects = true;
			if (!$this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::COMMA)) {
				// Just SELECT * [FROM ...]
				$this->index--;
				return true;
			}
		} else {
			$this->index = $startPoint;
		}
		while ($this->parseDisplayedColumn()) {
			$hasSelects = true;
			if ($this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::COMMA)) {
				continue;
			}
			$this->index--;
			break;
		}
		if ($hasSelects) {
			return true;
		}
		$this->index = $startPoint;
		return false;
	}

	private function parseDisplayedColumn() {
		/*
		( table_spec DOT ASTERISK )
		|
		( column_spec (alias)? )
		|
		( bit_expr (alias)? )
		*/
		$savePoint = $this->index;
		if ($this->parseTableSpec() &&
			$this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::DOT) &&
			$this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::ASTERISK)
		) {
			return true;
		}
		$this->index = $savePoint;

		$savePoint = $this->index;
		if ($this->parseExpression()) {
			$this->parseAlias();
			return true;
		}
		$this->index = $savePoint;

		$savePoint = $this->index;
		if ($this->parseColumnSpec()) {
			$this->parseAlias();
			return true;
		}
		$this->index = $savePoint;

		return false;
	}

	/**
	 * @return bool
	 */
	private function parseExpressionList() {
		$startIndex = $this->index;
		if ($this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::OPEN_PARENTHESIS)) {
			$hasExpressions = false;
			while ($this->parseExpression()) {
				$hasExpressions = true;
				if ($this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::COMMA)) {
					continue;
				}
				$this->index--;
				break;
			}
			if ($hasExpressions && $this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::CLOSE_PARENTHESIS)) {
				return true;
			}
		}
		$this->index = $startIndex;
		return false;
	}

	private function parseExpression() {
		// Combines these:
		// exp_factor3 ( AND_SYM exp_factor3 )* ;
		// expression:	exp_factor1 ( OR_SYM exp_factor1 )* ;
		// exp_factor1:	exp_factor2 ( XOR exp_factor2 )* ;
		// exp_factor2:	exp_factor3 ( AND_SYM exp_factor3 )* ;

		$savePoint = $this->index;
		$hasExpression = false;
		while ($this->parseExpressionFactor3()) {
			$hasExpression = true;
			$savePoint2 = $this->index;
			$token = $this->nextToken();
			if ($this->isOrToken($token) || $this->isAndToken($token) || $this->isIdentifierWithValue($token, 'xor')) {
				continue;
			}
			$this->index = $savePoint2;
			break;
		}
		if ($hasExpression) {
			return true;
		}
		$this->index = $savePoint;
		return false;
	}

	private function parseExpressionFactor3() {
		// (NOT_SYM)? exp_factor4 ;
		$savePoint = $this->index;
		if (!$this->isNotSymbolToken($this->nextToken())) {
			$this->index--;
		}
		if ($this->parseExpressionFactor4()) {
			return true;
		}
		$this->index = $savePoint;
		return false;
	}

	private function parseExpressionFactor4() {
		// bool_primary ( IS_SYM (NOT_SYM)? (boolean_literal|NULL_SYM) )? ;
		$savePoint = $this->index;
		if ($this->parseBoolPrimary()) {
			$savePoint = $this->index;
			if ($this->isIdentifierWithValue($this->nextToken(), 'is')) {
				if (!$this->isNotSymbolToken($this->nextToken())) {
					$this->index--;
				}
				if ($this->isIdentifierWithValue($this->nextToken(), array(
					'true', 'false', 'null',
				))
				) {
					return true;
				}
			}
			$this->index = $savePoint;
			return true;
		}
		$this->index = $savePoint;
		return false;
	}

	/**
	 * @return bool
	 */
	private function parseBoolPrimary() {
		$startIndex = $this->index;
		$token = $this->nextToken();
		if ($token) {
			$hasNot = false;
			if ($this->isNotSymbolToken($token)) {
				$hasNot = true;
				$token = $this->nextToken();
			}
			$val = $token->getLowerCaseValue();
			if ($token->getType() === wfWAFSQLiLexer::UNQUOTED_IDENTIFIER) {
				if ($val === 'exists' && $this->parseSubquery()) {
					return true;
				} else if ($hasNot) {
					$this->index = $startIndex;
					return false;
				}
			}
			if (!$hasNot) {
				$this->index = $startIndex;
			}
		}

		if ($this->parsePredicate()) {
			$savePoint = $this->index;
			$opToken = $this->nextToken();
			if ($opToken) {
				switch ($opToken->getType()) {
					case wfWAFSQLiLexer::EQUALS_SYMBOL:
					case wfWAFSQLiLexer::LESS_THAN:
					case wfWAFSQLiLexer::GREATER_THAN:
					case wfWAFSQLiLexer::LESS_THAN_EQUAL_TO:
					case wfWAFSQLiLexer::GREATER_THAN_EQUAL_TO:
					case wfWAFSQLiLexer::NOT_EQUALS:
					case wfWAFSQLiLexer::SET_VAR:
						$savePoint2 = $this->index;
						if ($this->isIdentifierWithValue($this->nextToken(), array(
								'any', 'all'
							)) &&
							$this->parseSubquery()
						) {
							return true;
						}
						$this->index = $savePoint2;

						$savePoint2 = $this->index;
						if ($this->testForSubquery() && $this->parseSubquery()) {
							return true;
						}
						$this->index = $savePoint2;

						if ($this->parsePredicate()) {
							return true;
						}
						$this->index = $startIndex;
						return false;
				}
			}
			$this->index = $savePoint;
			return true;
		}
		$this->index = $startIndex;
		return false;
	}

	private function parsePredicate() {
		$startIndex = $this->index;
		if ($this->parseBitExpression()) {
			$savePoint = $this->index;
			$token = $this->nextToken();
			if ($token) {
				if ($hasNot = $this->isNotSymbolToken($token)) {
					$token = $this->nextToken();
					if (!$token) {
						$this->index = $startIndex;
						return false;
					}
				}
				$val = $token->getLowerCaseValue();

				if ($token->getType() === wfWAFSQLiLexer::UNQUOTED_IDENTIFIER) {
					switch ($val) {
						case 'in':
							if ($this->parseSubquery() || $this->parseExpressionList()) {
								return true;
							}
							break;

						case 'between':
							if ($this->parseBitExpression() && $this->isIdentifierWithValue($this->nextToken(), 'and') &&
								$this->parsePredicate()
							) {
								return true;
							}
							break;

						case 'sounds':
							if ($this->isIdentifierWithValue($this->nextToken(), 'like') &&
								$this->parseBitExpression()
							) {
								return true;
							}
							break;

						case 'like':
						case 'rlike':
							if ($this->parseSimpleExpression()) {
								// We've got a LIKE statement at this point
								$savePoint = $this->index;
								if ($this->isIdentifierWithValue($this->nextToken(), 'escape') &&
									$this->parseSimpleExpression()
								) {
									return true;
								}
								$this->index = $savePoint;
								return true;
							}
							break;

						case 'regexp':
							if ($this->parseBitExpression()) {
								return true;
							}
							break;

						default:
							if ($hasNot) {
								$this->index = $startIndex;
								return false;
							}
							break;
					}
				}
			}
			$this->index = $savePoint;
			return true;
		}
		$this->index = $startIndex;
		return false;
	}

	/**
	 * @return bool
	 */
	private function parseBitExpression() {
		// factor1 ( VERTBAR factor1 )? ;
		$savePoint = $this->index;
		if ($this->parseBitExprFactor5()) {
			$savePoint = $this->index;
			$token = $this->nextToken();
			if (($this->isTokenOfType($token, array(
						wfWAFSQLiLexer::BIT_OR,
						wfWAFSQLiLexer::BIT_AND,
						wfWAFSQLiLexer::BIT_XOR,
						wfWAFSQLiLexer::BIT_LEFT_SHIFT,
						wfWAFSQLiLexer::BIT_RIGHT_SHIFT,
						wfWAFSQLiLexer::BIT_INVERSION,
						wfWAFSQLiLexer::PLUS,
						wfWAFSQLiLexer::MINUS,
						wfWAFSQLiLexer::ASTERISK,
						wfWAFSQLiLexer::DIVISION,
						wfWAFSQLiLexer::MOD,
					))
					||
					$this->isIdentifierWithValue($token, array(
						'div', 'mod'
					))) &&
				$this->parseBitExpression()
			) {
				return true;
			}
			$this->index = $savePoint;
			return true;
		}
		$this->index = $savePoint;
		return false;
	}


	private function parseBitExprFactor5() {
		// factor6 ( (PLUS|MINUS) interval_expr )? ;
		$savePoint = $this->index;
		if ($this->parseBitExprFactor6()) {
			$savePoint = $this->index;
			if ($this->isTokenOfType($this->nextToken(), array(
					wfWAFSQLiLexer::PLUS,
					wfWAFSQLiLexer::MINUS,
				)) && $this->parseIntervalExpression()
			) {
				return true;
			}
			$this->index = $savePoint;
			return true;
		}
		$this->index = $savePoint;
		return false;
	}

	private function parseBitExprFactor6() {
		// (PLUS | MINUS | NEGATION | BINARY) simple_expr
		// | simple_expr ;

		$startPoint = $this->index;
		$savePoint = $this->index;
		while (
			($token = $this->nextToken()) &&
			(
				$this->isTokenOfType($token, array(
					wfWAFSQLiLexer::PLUS,
					wfWAFSQLiLexer::MINUS,
				)) ||
				($this->isTokenOfType($token, wfWAFSQLiLexer::BIT_INVERSION)) ||
				($this->isIdentifierWithValue($token, 'BINARY'))
			)
		) {
			$savePoint = $this->index;
		}
		$this->index = $savePoint;

		if ($this->parseSimpleExpression()) {
			return true;
		}
		$this->index = $startPoint;
		return false;

	}

	/**
	 * literal_value
	 * | column_spec
	 * | function_call
	 * | USER_VAR
	 * | expression_list
	 * | (ROW_SYM expression_list)
	 * | subquery
	 * | EXISTS subquery
	 * | {identifier expr}
	 * | match_against_statement
	 * | case_when_statement
	 * | interval_expr
	 *
	 * @return bool
	 */
	private function parseSimpleExpression() {
		$startPoint = $this->index;
		$simple = ($parseLiteral = $this->parseLiteral()) ||
			($parseMatchAgainst = $this->parseMatchAgainst()) ||
			($parseFunctionCall = $this->parseFunctionCall()) ||
			($parseVariable = $this->parseVariable()) ||
			($parseExpressionList = $this->parseExpressionList()) ||
			($parseSubquery = $this->parseSubquery()) ||
			($parseExistsSubquery = $this->parseExistsSubquery()) ||
			($parseCaseWhen = $this->parseCaseWhen()) ||
			($parseODBCExpression = $this->parseODBCExpression()) ||
			($parseIntervalExpression = $this->parseIntervalExpression()) ||
			($parseColumnSpec = $this->parseColumnSpec());

		if ($simple) {
			$token = $this->nextToken();
			if ($token && $token->getLowerCaseValue() == 'collate') {
				$savePoint = $this->index;
				if ($this->parseCollationName()) {
					return true;
				}
				$this->index = $savePoint;
			} else {
				$this->index--;
			}
			return true;
		}
		$this->index = $startPoint;
		return false;
	}

	/**
	 * @return bool
	 */
	private function parseLiteral() {
		$startIndex = $this->index;
		$savePoint = $this->index;
		while ($this->isTokenOfType($this->nextToken(), array(
			wfWAFSQLiLexer::PLUS,
			wfWAFSQLiLexer::MINUS,
		))) {
			$savePoint = $this->index;
		}
		$this->index = $savePoint;

		$nextToken = $this->nextToken();
		if ($nextToken) {
			switch ($nextToken->getType()) {
				case wfWAFSQLiLexer::INTEGER_LITERAL:
				case wfWAFSQLiLexer::BINARY_NUMBER_LITERAL:
				case wfWAFSQLiLexer::HEX_NUMBER_LITERAL:
				case wfWAFSQLiLexer::REAL_NUMBER_LITERAL:
					return true;
				// Allow concatenation: 'test' 'test' is valid
				case wfWAFSQLiLexer::DOUBLE_STRING_LITERAL:
				case wfWAFSQLiLexer::SINGLE_STRING_LITERAL:
					$savePoint = $this->index;
					while ($this->isTokenOfType($this->nextToken(), array(
						wfWAFSQLiLexer::DOUBLE_STRING_LITERAL,
						wfWAFSQLiLexer::SINGLE_STRING_LITERAL
					))) {
						$savePoint = $this->index;
					}
					$this->index = $savePoint;
					return true;

				case wfWAFSQLiLexer::UNQUOTED_IDENTIFIER:
					if ($nextToken->getLowerCaseValue() === 'null') {
						return true;
					}
					break;
			}
		}
		$this->index = $startIndex;
		return false;
	}

	/**
	 * @return bool
	 */
	private function parseColumnSpec() {
		$savePoint = $this->index;
		if ($this->parseTableSpec()) {
			$savePoint = $this->index;
			if ($this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::DOT)) {
				$nextToken = $this->nextToken();
				if ($nextToken && ($nextToken->getType() == wfWAFSQLiLexer::UNQUOTED_IDENTIFIER ||
						$nextToken->getType() == wfWAFSQLiLexer::QUOTED_IDENTIFIER)
				) {
					return true;
				}
				$this->index = $savePoint;
				return false;
			}

			$this->index = $savePoint;
			return true;
		}
		$this->index = $savePoint;
		return false;
	}

	/**
	 * CAST_SYM LPAREN expression AS_SYM cast_data_type RPAREN  )
	 * | (  CONVERT_SYM LPAREN expression COMMA cast_data_type RPAREN  )
	 * | (  CONVERT_SYM LPAREN expression USING_SYM transcoding_name RPAREN  )
	 * | (  group_functions LPAREN ( ASTERISK | ALL | DISTINCT )? bit_expr RPAREN  )
	 *
	 * @return bool
	 */
	private function parseFunctionCall() {
		$startPoint = $this->index;
		$functionToken = $this->nextToken();
		if ($functionToken && $functionToken->getType() === wfWAFSQLiLexer::UNQUOTED_IDENTIFIER) {
			if ($this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::OPEN_PARENTHESIS)) {
				switch ($functionToken->getLowerCaseValue()) {
					case 'cast':
						if ($this->parseExpression() &&
							$this->isIdentifierWithValue($this->nextToken(), 'as') &&
							$this->parseCastDataType() &&
							$this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::CLOSE_PARENTHESIS)
						) {
							return true;
						}
						break;

					case 'convert':
						if ($this->parseExpression()) {
							$savePoint = $this->index;
							if ($this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::COMMA) &&
								$this->parseCastDataType() &&
								$this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::CLOSE_PARENTHESIS)
							) {
								return true;
							}
							$this->index = $savePoint;
							$savePoint = $this->index;
							if ($this->isIdentifierWithValue($this->nextToken(), 'using') &&
								$this->parseTranscodingName() &&
								$this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::CLOSE_PARENTHESIS)
							) {
								return true;
							}
							$this->index = $savePoint;
						}
						break;

					default:
						$savePoint = $this->index;
						if (in_array($functionToken->getUpperCaseValue(), $this->groupFunctions)) {
							$token = $this->nextToken();
							if (!$this->isIdentifierWithValue($token, array(
									'all', 'distinct',
								)) && !$this->isTokenOfType($token, wfWAFSQLiLexer::ASTERISK)
							) {
								$this->index--;
							}
							$this->parseBitExpression();
							if ($this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::CLOSE_PARENTHESIS)) {
								return true;
							}
						}
						$this->index = $savePoint;

						while ($this->parseExpression()) {
							if ($this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::COMMA)) {
								continue;
							}
							$this->index--;
							break;
						}

						if ($this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::CLOSE_PARENTHESIS)) {
							return true;
						}
						break;
				}
			}
		}
		$this->index = $startPoint;
		return false;
	}

	/**
	 * BINARY (INTEGER_NUM)?
	 * | CHAR (INTEGER_NUM)?
	 * | DATE_SYM
	 * | DATETIME
	 * | DECIMAL_SYM ( INTEGER_NUM (COMMA INTEGER_NUM)? )?
	 * | SIGNED_SYM (INTEGER_SYM)?
	 * | TIME_SYM
	 * | UNSIGNED_SYM (INTEGER_SYM)?
	 *
	 * @return bool
	 */
	private function parseCastDataType() {
		$startPoint = $this->index;
		$token = $this->nextToken();
		if ($this->isKeywordToken($token)) {
			switch ($token->getLowerCaseValue()) {
				case 'binary':
				case 'char':
					$savePoint = $this->index;
					if ($this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::INTEGER_LITERAL)) {
						return true;
					}
					$this->index = $savePoint;
					return true;

				case 'date':
				case 'datetime':
				case 'time':
					return true;

				case 'signed':
				case 'unsigned':
					if (!$this->isIdentifierWithValue($this->nextToken(), 'integer')) {
						$this->index--;
					}
					return true;

				case 'decimal':
					$savePoint = $this->index;
					while ($this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::INTEGER_LITERAL)) {
						if ($this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::COMMA)) {
							continue;
						}
						$this->index--;
						return true;
					}
					$this->index = $savePoint;
					return true;
			}
		}
		$this->index = $startPoint;
		return false;
	}

	private function parseTranscodingName() {
		$savePoint = $this->index;
		$token = $this->nextToken();
		if ($token && $token->getType() === wfWAFSQLiLexer::UNQUOTED_IDENTIFIER) {
			return false;
		}
		$this->index = $savePoint;
		return false;
	}

	private function parseVariable() {
		$nextToken = $this->nextToken();
		if ($nextToken && $nextToken->getType() === wfWAFSQLiLexer::VARIABLE) {
			return true;
		}
		$this->index--;
		return false;
	}

	/**
	 * @return bool
	 */
	private function parseSubquery() {
		$startIndex = $this->index;
		if ($this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::OPEN_PARENTHESIS) &&
			$this->parseSelectStatement() &&
			$this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::CLOSE_PARENTHESIS)
		) {
			return true;
		}
		$this->index = $startIndex;
		return false;
	}

	private function testForSubquery() {
		$startIndex = $this->index;
		$nextToken = $this->nextToken();
		if ($nextToken && $nextToken->getType() === wfWAFSQLiLexer::OPEN_PARENTHESIS) {
			$selectToken = $this->nextToken();
			if ($this->isIdentifierWithValue($selectToken, 'select')) {
				$this->index = $startIndex;
				return true;
			}
		}
		$this->index = $startIndex;
		return false;
	}

	/**
	 *
	 *
	 * @return bool
	 */
	private function parseExistsSubquery() {
		$startIndex = $this->index;
		$existsToken = $this->nextToken();
		if ($this->isIdentifierWithValue($existsToken, 'exists')) {
			if ($this->parseSubquery()) {
				return true;
			}
		}
		$this->index = $startIndex;
		return false;
	}

	/**
	 * MATCH (column_spec (COMMA column_spec)* ) AGAINST (expression (search_modifier)? )
	 *
	 * @return bool
	 */
	private function parseMatchAgainst() {
		$startIndex = $this->index;
		if ($this->isIdentifierWithValue($this->nextToken(), 'match')) {
			$savePoint = $this->index;
			if (!$this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::OPEN_PARENTHESIS)) {
				$this->index = $savePoint;
			}
			$hasColumns = false;
			while ($this->parseColumnSpec()) {
				$hasColumns = true;
				$savePoint = $this->index;
				if ($this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::COMMA)) {
					continue;
				}
				$this->index = $savePoint;
				break;
			}
			$savePoint = $this->index;
			if (!$this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::CLOSE_PARENTHESIS)) {
				$this->index = $savePoint;
			}
			if ($hasColumns && $this->isIdentifierWithValue($this->nextToken(), 'against') &&
				$this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::OPEN_PARENTHESIS) &&
				$this->parseExpression() &&
				($this->parseSearchModifier() || true) &&
				$this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::CLOSE_PARENTHESIS)
			) {
				return true;
			}
		}
		$this->index = $startIndex;
		return false;
	}

	/**
	 * Used in match/against
	 *
	 * @link https://dev.mysql.com/doc/refman/5.6/en/fulltext-search.html
	 * @return bool
	 */
	private function parseSearchModifier() {
		$startIndex = $this->index;

		$startToken = $this->nextToken();
		if ($this->isIdentifierWithValue($startToken, 'in')) {
			$next = $this->nextToken();
			if ($this->isIdentifierWithValue($next, 'natural') &&
				$this->isIdentifierWithValue($this->nextToken(), 'language') &&
				$this->isIdentifierWithValue($this->nextToken(), 'mode')
			) {
				$saveIndex = $this->index;
				if ($this->isIdentifierWithValue($this->nextToken(), 'with') &&
					$this->isIdentifierWithValue($this->nextToken(), 'query') &&
					$this->isIdentifierWithValue($this->nextToken(), 'expansion')
				) {
					return true;
				}
				$this->index = $saveIndex;
				return true;

			} else if ($this->isIdentifierWithValue($next, 'boolean') &&
				$this->isIdentifierWithValue($this->nextToken(), 'mode')
			) {
				return true;
			}
		} else if ($this->isIdentifierWithValue($startToken, 'with')) {
			if ($this->isIdentifierWithValue($this->nextToken(), 'query') &&
				$this->isIdentifierWithValue($this->nextToken(), 'expansion')
			) {
				return true;
			}
		}

		$this->index = $startIndex;
		return false;
	}

	/**
	 * @return bool
	 */
	private function parseCaseWhen() {
		$startIndex = $this->index;
		if ($this->isIdentifierWithValue($this->nextToken(), 'case')) {
			$hasWhen = false;
			while (true) {
				if (!$this->isIdentifierWithValue($this->nextToken(), 'when')) {
					$this->index--;
					break;
				}
				if ($this->parseExpression()) {
					if ($this->isIdentifierWithValue($this->nextToken(), 'then') && $this->parseBitExpression()) {
						$hasWhen = true;
						continue;
					}
					$this->index--;
				}
				$this->index--;
				break;
			}
			if ($hasWhen) {
				$endToken = $this->nextToken();
				if ($this->isIdentifierWithValue($endToken, 'else')) {
					if (!$this->parseBitExpression()) {
						$this->index = $startIndex;
						return false;
					}
					$endToken = $this->nextToken();
				}
				if ($this->isIdentifierWithValue($endToken, 'end')) {
					return true;
				}
			}
		}
		$this->index = $startIndex;
		return false;
	}

	/**
	 * @return bool
	 */
	private function parseODBCExpression() {
		$startIndex = $this->index;
		if ($this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::OPEN_BRACKET) && $this->isIdentifier($this->nextToken()) && $this->parseExpression() && $this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::CLOSE_BRACKET)) {
			return true;
		}
		$this->index = $startIndex;
		return false;
	}

	/**
	 * @return bool
	 */
	private function parseIntervalExpression() {
		$startIndex = $this->index;
		if ($this->isIdentifierWithValue($this->nextToken(), 'interval') && $this->parseExpression()) {
			$intervalUnitToken = $this->nextToken();
			if ($intervalUnitToken && in_array($intervalUnitToken->getType(), $this->intervalUnits)) {
				return true;
			}
		}
		$this->index = $startIndex;
		return false;
	}

	/**
	 * @return bool
	 */
	public function parseCollationName() {
		$startIndex = $this->index;
		$token = $this->nextToken();
		if ($token && $token->getType() === wfWAFSQLiLexer::UNQUOTED_IDENTIFIER) {
			return true;
		}
		$this->index = $startIndex;
		return false;
	}

	/**
	 * @return bool
	 */
	private function parseFrom() {
		$startIndex = $this->index;
		$token = $this->nextToken();
		if ($this->isIdentifierWithValue($token, 'from')) {
			return $this->parseTableReferences();
		}
		$this->index = $startIndex;
		return false;
	}

	/**
	 * @link http://dev.mysql.com/doc/refman/5.6/en/join.html
	 * @return bool
	 */
	private function parseTableReferences() {
		$startPoint = $this->index;
		$hasReferences = false;
		while ($this->parseEscapedTableReference()) {
			$hasReferences = true;
			$savePoint = $this->index;
			$token = $this->nextToken();
			if ($this->isTokenOfType($token, wfWAFSQLiLexer::COMMA)) {
				continue;
			}
			$this->index = $savePoint;
			break;
		}
		if ($hasReferences) {
			return true;
		}
		$this->index = $startPoint;
		return false;
	}

	/**
	 * @return bool
	 */
	private function parseEscapedTableReference() {
		$startPoint = $this->index;
		if ($this->parseTableReference() ||
			(
				$this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::OPEN_BRACKET) &&
				$this->isIdentifierWithValue($this->nextToken(), 'oj') &&
				$this->parseTableReference() &&
				$this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::CLOSE_BRACKET)
			)
		) {
			return true;
		}

		$this->index = $startPoint;
		return false;
	}

	/**
	 * @return bool
	 */
	private function parseTableReference() {
		$savePoint = $this->index;
		$hasTables = false;
		if ($this->parseTableFactor()) {
			$hasTables = true;
			while ($this->parseJoinTable()) {

			}
		}
		if ($hasTables) {
			return true;
		}
		$this->index = $savePoint;
		return false;
	}

	/**
	 * table_factor:
	 *   tbl_name [PARTITION (partition_names)] [[AS] alias] [index_hint_list]
	 *   | table_subquery [AS] alias
	 *   | ( table_references )
	 */
	private function parseTableFactor() {
		$savePoint = $this->index;
		if ($this->parseTableSpec()) {
			$savePoint2 = $this->index;
			if (!$this->parsePartitionClause()) {
				$this->index = $savePoint2;
			}

			$this->parseAlias();
			$this->parseIndexHintList();

			return true;
		}
		$this->index = $savePoint;

		$savePoint = $this->index;
		if ($this->parseSubquery() && $this->parseAlias()) {
			return true;
		}
		$this->index = $savePoint;

		$savePoint = $this->index;
		if ($this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::OPEN_PARENTHESIS) &&
			$this->parseTableReferences() &&
			$this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::CLOSE_PARENTHESIS)
		) {
			return true;
		}
		$this->index = $savePoint;
		return false;
	}

	/**
	 * PARTITION (partition_names)
	 *
	 * @return bool
	 */
	private function parsePartitionClause() {
		$startIndex = $this->index;
		if ($this->isIdentifierWithValue($this->nextToken(), 'partition') &&
			$this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::OPEN_PARENTHESIS) &&
			$this->parsePartitionNames() &&
			$this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::CLOSE_PARENTHESIS)
		) {
			return true;
		}
		$this->index = $startIndex;
		return false;
	}

	/**
	 * @return bool
	 */
	private function parsePartitionNames() {
		$startPoint = $this->index;
		$hasPartition = false;
		while ($this->parsePartitionName()) {
			$hasPartition = true;
			$savePoint = $this->index;
			if (!$this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::COMMA)) {
				$this->index = $savePoint;
				break;
			}
		}
		if ($hasPartition) {
			return true;
		}
		$this->index = $startPoint;
		return false;
	}

	/**
	 * @return bool
	 */
	private function parsePartitionName() {
		$startPoint = $this->index;
		$token = $this->nextToken();
		if ($this->isTokenOfType($token, wfWAFSQLiLexer::QUOTED_IDENTIFIER) ||
			$this->isValidNonKeywordIdentifier($token)
		) {
			return true;
		}
		$this->index = $startPoint;
		return false;
	}

	/**
	 * join_table:
	 *   table_reference [INNER | CROSS] JOIN table_factor [join_condition]
	 *   | table_reference STRAIGHT_JOIN table_factor
	 *   | table_reference STRAIGHT_JOIN table_factor ON conditional_expr
	 *   | table_reference {LEFT|RIGHT} [OUTER] JOIN table_reference join_condition
	 *   | table_reference NATURAL [{LEFT|RIGHT} [OUTER]] JOIN table_factor
	 *
	 * @return bool
	 */
	private function parseJoinTable() {
		$savePoint = $this->index;
		if (!$this->isIdentifierWithValue($this->nextToken(), array(
			'inner', 'cross',
		))
		) {
			$this->index = $savePoint;
		}
		if ($this->isIdentifierWithValue($this->nextToken(), 'join') && $this->parseTableFactor()) {
			$this->parseJoinCondition();
			return true;
		}
		$this->index = $savePoint;

		$savePoint = $this->index;
		if ($this->isIdentifierWithValue($this->nextToken(), 'straight_join') &&
			$this->parseTableFactor()
		) {
			$savePoint = $this->index;
			if (!($this->isIdentifierWithValue($this->nextToken(), 'on') && $this->parseExpression())) {
				$this->index = $savePoint;
			}
			return true;
		}
		$this->index = $savePoint;

		$savePoint = $this->index;
		if ($this->isIdentifierWithValue($this->nextToken(), array(
			'left', 'right',
		))
		) {
			$savePoint2 = $this->index;
			if (!$this->isIdentifierWithValue($this->nextToken(), array(
				'outer',
			))
			) {
				$this->index = $savePoint2;
			}
		} else {
			$this->index = $savePoint;
		}
		if ($this->isIdentifierWithValue($this->nextToken(), 'join') &&
			$this->parseTableReference() &&
			$this->parseJoinCondition()
		) {
			return true;
		}
		$this->index = $savePoint;

		$savePoint = $this->index;
		if ($this->isIdentifierWithValue($this->nextToken(), array(
			'natural',
		))
		) {
			if ($this->isIdentifierWithValue($this->nextToken(), array(
				'left', 'right',
			))
			) {
				$savePoint2 = $this->index;
				if (!$this->isIdentifierWithValue($this->nextToken(), array(
					'outer',
				))
				) {
					$this->index = $savePoint2;
				}
			} else {
				$this->index = $savePoint;
			}
			if ($this->isIdentifierWithValue($this->nextToken(), 'join') &&
				$this->parseTableFactor()
			) {
				return true;
			}

		}
		$this->index = $savePoint;
		return false;
	}

	/**
	 * (ON expression) | (USING_SYM column_list)
	 *
	 * @return bool
	 */
	private function parseJoinCondition() {
		$savePoint = $this->index;
		if ($this->isIdentifierWithValue($this->nextToken(), 'on') && $this->parseExpression()) {
			return true;
		}
		$this->index = $savePoint;

		$savePoint = $this->index;
		if ($this->isIdentifierWithValue($this->nextToken(), 'using') && $this->parseColumnList()) {
			return true;
		}
		$this->index = $savePoint;
		return false;
	}

	/**
	 * @return bool
	 */
	private function parseTableSpec() {
		$savePoint = $this->index;
		if ($this->isTokenOfType($this->nextToken(), array(
			wfWAFSQLiLexer::UNQUOTED_IDENTIFIER,
			wfWAFSQLiLexer::QUOTED_IDENTIFIER,
		))
		) {
			$savePoint = $this->index;
			if ($this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::DOT) &&
				$this->isTokenOfType($this->nextToken(), array(
					wfWAFSQLiLexer::UNQUOTED_IDENTIFIER,
					wfWAFSQLiLexer::QUOTED_IDENTIFIER,
				))
			) {
				return true;
			}
			$this->index = $savePoint;
			return true;
		}
		$this->index = $savePoint;
		return false;
	}

	/**
	 * @return bool
	 */
	private function parseAlias() {
		$savePoint = $this->index;
		$token = $this->nextToken();
		if ($this->isIdentifierWithValue($token, 'as')) {
			$token = $this->nextToken();
		}
		if ($this->isValidNonKeywordIdentifier($token)) {
			return true;
		}
		$this->index = $savePoint;
		return false;
	}

	/**
	 * @return bool
	 */
	private function parseIndexHintList() {
		$startPoint = $this->index;
		$hasHints = false;
		while ($this->parseIndexHint()) {
			$hasHints = true;
			$savePoint = $this->index;
			if (!$this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::COMMA)) {
				$this->index = $savePoint;
				break;
			}
		}
		if ($hasHints) {
			return true;
		}
		$this->index = $startPoint;
		return false;
	}

	/**
	 * @return bool
	 */
	private function parseIndexHint() {
		// USE_SYM    index_options LPAREN (index_list)? RPAREN
		$savePoint = $this->index;
		if ($this->isIdentifierWithValue($this->nextToken(), 'use') &&
			$this->parseIndexOptions() &&
			$this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::OPEN_PARENTHESIS)
		) {
			$this->parseIndexList();
			if ($this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::CLOSE_PARENTHESIS)) {
				return true;
			}
		}
		$this->index = $savePoint;

		// IGNORE_SYM index_options LPAREN index_list RPAREN
		$savePoint = $this->index;
		if ($this->isIdentifierWithValue($this->nextToken(), 'ignore') &&
			$this->parseIndexOptions() &&
			$this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::OPEN_PARENTHESIS) &&
			$this->parseIndexList() &&
			$this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::CLOSE_PARENTHESIS)
		) {
			return true;
		}
		$this->index = $savePoint;

		// FORCE_SYM  index_options LPAREN index_list RPAREN
		$savePoint = $this->index;
		if ($this->isIdentifierWithValue($this->nextToken(), 'force') &&
			$this->parseIndexOptions() &&
			$this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::OPEN_PARENTHESIS) &&
			$this->parseIndexList() &&
			$this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::CLOSE_PARENTHESIS)
		) {
			return true;
		}
		$this->index = $savePoint;

		return false;
	}

	/**
	 * (INDEX_SYM | KEY_SYM) (  FOR_SYM ((JOIN_SYM) | (ORDER_SYM BY_SYM) | (GROUP_SYM BY_SYM))  )?
	 *
	 * @return bool
	 */
	private function parseIndexOptions() {
		$savePoint = $this->index;
		$token = $this->nextToken();
		if ($this->isIdentifierWithValue($token, 'index') ||
			$this->isIdentifierWithValue($token, 'key')
		) {
			$savePoint = $this->index;
			if ($this->isIdentifierWithValue($this->nextToken(), 'for')) {

				$savePoint = $this->index;
				if ($this->isIdentifierWithValue($this->nextToken(), 'join')) {
					return true;
				}
				$this->index = $savePoint;

				$savePoint = $this->index;
				if ($this->isIdentifierWithValue($this->nextToken(), 'order') &&
					$this->isIdentifierWithValue($this->nextToken(), 'by')
				) {
					return true;
				}
				$this->index = $savePoint;

				$savePoint = $this->index;
				if ($this->isIdentifierWithValue($this->nextToken(), 'group') &&
					$this->isIdentifierWithValue($this->nextToken(), 'by')
				) {
					return true;
				}
				$this->index = $savePoint;

				return true;
			}
			$this->index = $savePoint;
			return true;
		}
		$this->index = $savePoint;
		return false;
	}

	private function parseIndexList() {
		$startPoint = $this->index;
		$hasIndex = false;
		while ($this->parseIndexName()) {
			$hasIndex = true;
			$savePoint = $this->index;
			if (!$this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::COMMA)) {
				$this->index = $savePoint;
				break;
			}
		}
		if ($hasIndex) {
			return true;
		}
		$this->index = $startPoint;
		return false;
	}

	private function parseIndexName() {
		$startPoint = $this->index;
		$token = $this->nextToken();
		if ($this->isValidNonKeywordIdentifier($token)) {
			return true;
		}
		$this->index = $startPoint;
		return false;
	}

	/**
	 * LPAREN column_spec (COMMA column_spec)* RPAREN
	 *
	 * @return bool
	 */
	private function parseColumnList() {
		$startPoint = $this->index;
		if ($this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::OPEN_PARENTHESIS)) {
			$hasColumn = false;
			while ($this->parseColumnSpec()) {
				$hasColumn = true;
				$savePoint = $this->index;
				if (!$this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::COMMA)) {
					$this->index = $savePoint;
					break;
				}
			}
			if ($hasColumn && $this->isTokenOfType($this->nextToken(), wfWAFSQLiLexer::CLOSE_PARENTHESIS)) {
				return true;
			}
		}
		$this->index = $startPoint;
		return false;
	}

	private function parseWhere() {
		$startIndex = $this->index;
		$token = $this->nextToken();
		if ($this->isIdentifierWithValue($token, 'where')) {
			if ($t