<?php
/**
 * Class for generating SQL clauses that filter a primary query according to date.
 *
 * WP_Date_Query is a helper that allows primary query classes, such as WP_Query, to filter
 * their results by date columns, by generating `WHERE` subclauses to be attached to the
 * primary SQL query string.
 *
 * Attempting to filter by an invalid date value (eg month=13) will generate SQL that will
 * return no results. In these cases, a _doing_it_wrong() error notice is also thrown.
 * See WP_Date_Query::validate_date_values().
 *
 * @link https://codex.wordpress.org/Function_Reference/WP_Query Codex page.
 *
 * @since 3.7.0
 */
class WP_Date_Query {
	/**
	 * Array of date queries.
	 *
	 * See WP_Date_Query::__construct() for information on date query arguments.
	 *
	 * @since 3.7.0
	 * @access public
	 * @var array
	 */
	public $queries = array();

	/**
	 * The default relation between top-level queries. Can be either 'AND' or 'OR'.
	 *
	 * @since 3.7.0
	 * @access public
	 * @var string
	 */
	public $relation = 'AND';

	/**
	 * The column to query against. Can be changed via the query arguments.
	 *
	 * @since 3.7.0
	 * @access public
	 * @var string
	 */
	public $column = 'post_date';

	/**
	 * The value comparison operator. Can be changed via the query arguments.
	 *
	 * @since 3.7.0
	 * @access public
	 * @var array
	 */
	public $compare = '=';

	/**
	 * Supported time-related parameter keys.
	 *
	 * @since 4.1.0
	 * @access public
	 * @var array
	 */
	public $time_keys = array( 'after', 'before', 'year', 'month', 'monthnum', 'week', 'w', 'dayofyear', 'day', 'dayofweek', 'dayofweek_iso', 'hour', 'minute', 'second' );

	/**
	 * Constructor.
	 *
	 * Time-related parameters that normally require integer values ('year', 'month', 'week', 'dayofyear', 'day',
	 * 'dayofweek', 'dayofweek_iso', 'hour', 'minute', 'second') accept arrays of integers for some values of
	 * 'compare'. When 'compare' is 'IN' or 'NOT IN', arrays are accepted; when 'compare' is 'BETWEEN' or 'NOT
	 * BETWEEN', arrays of two valid values are required. See individual argument descriptions for accepted values.
	 *
	 * @since 3.7.0
	 * @since 4.0.0 The $inclusive logic was updated to include all times within the date range.
	 * @since 4.1.0 Introduced 'dayofweek_iso' time type parameter.
	 * @access public
	 *
	 * @param array $date_query {
	 *     Array of date query clauses.
	 *
	 *     @type array {
	 *         @type string $column   Optional. The column to query against. If undefined, inherits the value of
	 *                                the `$default_column` parameter. Accepts 'post_date', 'post_date_gmt',
	 *                                'post_modified','post_modified_gmt', 'comment_date', 'comment_date_gmt'.
	 *                                Default 'post_date'.
	 *         @type string $compare  Optional. The comparison operator. Accepts '=', '!=', '>', '>=', '<', '<=',
	 *                                'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'. Default '='.
	 *         @type string $relation Optional. The boolean relationship between the date queries. Accepts 'OR' or 'AND'.
	 *                                Default 'OR'.
	 *         @type array {
	 *             Optional. An array of first-order clause parameters, or another fully-formed date query.
	 *
	 *             @type string|array $before {
	 *                 Optional. Date to retrieve posts before. Accepts `strtotime()`-compatible string,
	 *                 or array of 'year', 'month', 'day' values.
	 *
	 *                 @type string $year  The four-digit year. Default empty. Accepts any four-digit year.
	 *                 @type string $month Optional when passing array.The month of the year.
	 *                                     Default (string:empty)|(array:1). Accepts numbers 1-12.
	 *                 @type string $day   Optional when passing array.The day of the month.
	 *                                     Default (string:empty)|(array:1). Accepts numbers 1-31.
	 *             }
	 *             @type string|array $after {
	 *                 Optional. Date to retrieve posts after. Accepts `strtotime()`-compatible string,
	 *                 or array of 'year', 'month', 'day' values.
	 *
	 *                 @type string $year  The four-digit year. Accepts any four-digit year. Default empty.
	 *                 @type string $month Optional when passing array. The month of the year. Accepts numbers 1-12.
	 *                                     Default (string:empty)|(array:12).
	 *                 @type string $day   Optional when passing array.The day of the month. Accepts numbers 1-31.
	 *                                     Default (string:empty)|(array:last day of month).
	 *             }
	 *             @type string       $column        Optional. Used to add a clause comparing a column other than the
	 *                                               column specified in the top-level `$column` parameter. Accepts
	 *                                               'post_date', 'post_date_gmt', 'post_modified', 'post_modified_gmt',
	 *                                               'comment_date', 'comment_date_gmt'. Default is the value of
	 *                                               top-level `$column`.
	 *             @type string       $compare       Optional. The comparison operator. Accepts '=', '!=', '>', '>=',
	 *                                               '<', '<=', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'. 'IN',
	 *                                               'NOT IN', 'BETWEEN', and 'NOT BETWEEN'. Comparisons support
	 *                                               arrays in some time-related parameters. Default '='.
	 *             @type bool         $inclusive     Optional. Include results from dates specified in 'before' or
	 *                                               'after'. Default false.
	 *             @type int|array    $year          Optional. The four-digit year number. Accepts any four-digit year
	 *                                               or an array of years if `$compare` supports it. Default empty.
	 *             @type int|array    $month         Optional. The two-digit month number. Accepts numbers 1-12 or an
	 *                                               array of valid numbers if `$compare` supports it. Default empty.
	 *             @type int|array    $week          Optional. The week number of the year. Accepts numbers 0-53 or an
	 *                                               array of valid numbers if `$compare` supports it. Default empty.
	 *             @type int|array    $dayofyear     Optional. The day number of the year. Accepts numbers 1-366 or an
	 *                                               array of valid numbers if `$compare` supports it.
	 *             @type int|array    $day           Optional. The day of the month. Accepts numbers 1-31 or an array
	 *                                               of valid numbers if `$compare` supports it. Default empty.
	 *             @type int|array    $dayofweek     Optional. The day number of the week. Accepts numbers 1-7 (1 is
	 *                                               Sunday) or an array of valid numbers if `$compare` supports it.
	 *                                               Default empty.
	 *             @type int|array    $dayofweek_iso Optional. The day number of the week (ISO). Accepts numbers 1-7
	 *                                               (1 is Monday) or an array of valid numbers if `$compare` supports it.
	 *                                               Default empty.
	 *             @type int|array    $hour          Optional. The hour of the day. Accepts numbers 0-23 or an array
	 *                                               of valid numbers if `$compare` supports it. Default empty.
	 *             @type int|array    $minute        Optional. The minute of the hour. Accepts numbers 0-60 or an array
	 *                                               of valid numbers if `$compare` supports it. Default empty.
	 *             @type int|array    $second        Optional. The second of the minute. Accepts numbers 0-60 or an
	 *                                               array of valid numbers if `$compare` supports it. Default empty.
	 *         }
	 *     }
	 * }
	 * @param array $default_column Optional. Default column to query against. Default 'post_date'.
	 *                              Accepts 'post_date', 'post_date_gmt', 'post_modified', 'post_modified_gmt',
	 *                              'comment_date', 'comment_date_gmt'.
	 */
	public function __construct( $date_query, $default_column = 'post_date' ) {
		if ( isset( $date_query['relation'] ) && 'OR' === strtoupper( $date_query['relation'] ) ) {
			$this->relation = 'OR';
		} else {
			$this->relation = 'AND';
		}

		if ( ! is_array( $date_query ) ) {
			return;
		}

		// Support for passing time-based keys in the top level of the $date_query array.
		if ( ! isset( $date_query[0] ) && ! empty( $date_query ) ) {
			$date_query = array( $date_query );
		}

		if ( empty( $date_query ) ) {
			return;
		}

		if ( ! empty( $date_query['column'] ) ) {
			$date_query['column'] = esc_sql( $date_query['column'] );
		} else {
			$date_query['column'] = esc_sql( $default_column );
		}

		$this->column = $this->validate_column( $this->column );

		$this->compare = $this->get_compare( $date_query );

		$this->queries = $this->sanitize_query( $date_query );
	}

	/**
	 * Recursive-friendly query sanitizer.
	 *
	 * Ensures that each query-level clause has a 'relation' key, and that
	 * each first-order clause contains all the necessary keys from
	 * `$defaults`.
	 *
	 * @since 4.1.0
	 * @access public
	 *
	 * @param array $queries
	 * @param array $parent_query
	 *
	 * @return array Sanitized queries.
	 */
	public function sanitize_query( $queries, $parent_query = null ) {
		$cleaned_query = array();

		$defaults = array(
			'column'   => 'post_date',
			'compare'  => '=',
			'relation' => 'AND',
		);

		// Numeric keys should always have array values.
		foreach ( $queries as $qkey => $qvalue ) {
			if ( is_numeric( $qkey ) && ! is_array( $qvalue ) ) {
				unset( $queries[ $qkey ] );
			}
		}

		// Each query should have a value for each default key. Inherit from the parent when possible.
		foreach ( $defaults as $dkey => $dvalue ) {
			if ( isset( $queries[ $dkey ] ) ) {
				continue;
			}

			if ( isset( $parent_query[ $dkey ] ) ) {
				$queries[ $dkey ] = $parent_query[ $dkey ];
			} else {
				$queries[ $dkey ] = $dvalue;
			}
		}

		// Validate the dates passed in the query.
		if ( $this->is_first_order_clause( $queries ) ) {
			$this->validate_date_values( $queries );
		}

		foreach ( $queries as $key => $q ) {
			if ( ! is_array( $q ) || in_array( $key, $this->time_keys, true ) ) {
				// This is a first-order query. Trust the values and sanitize when building SQL.
				$cleaned_query[ $key ] = $q;
			} else {
				// Any array without a time key is another query, so we recurse.
				$cleaned_query[] = $this->sanitize_query( $q, $queries );
			}
		}

		return $cleaned_query;
	}

	/**
	 * Determine whether this is a first-order clause.
	 *
	 * Checks to see if the current clause has any time-related keys.
	 * If so, it's first-order.
	 *
	 * @since 4.1.0
	 * @access protected
	 *
	 * @param  array $query Query clause.
	 * @return bool True if this is a first-order clause.
	 */
	protected function is_first_order_clause( $query ) {
		$time_keys = array_intersect( $this->time_keys, array_keys( $query ) );
		return ! empty( $time_keys );
	}

	/**
	 * Determines and validates what comparison operator to use.
	 *
	 * @since 3.7.0
	 * @access public
	 *
	 * @param array $query A date query or a date subquery.
	 * @return string The comparison operator.
	 */
	public function get_compare( $query ) {
		if ( ! empty( $query['compare'] ) && in_array( $query['compare'], array( '=', '!=', '>', '>=', '<', '<=', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN' ) ) )
			return strtoupper( $query['compare'] );

		return $this->compare;
	}

	/**
	 * Validates the given date_query values and triggers errors if something is not valid.
	 *
	 * Note that date queries with invalid date ranges are allowed to
	 * continue (though of course no items will be found for impossible dates).
	 * This method only generates debug notices for these cases.
	 *
	 * @since  4.1.0
	 * @access public
	 *
	 * @param  array $date_query The date_query array.
	 * @return bool  True if all values in the query are valid, false if one or more fail.
	 */
	public function validate_date_values( $date_query = array() ) {
		if ( empty( $date_query ) ) {
			return false;
		}

		$valid = true;

		/*
		 * Validate 'before' and 'after' up front, then let the
		 * validation routine continue to be sure that all invalid
		 * values generate errors too.
		 */
		if ( array_key_exists( 'before', $date_query ) && is_array( $date_query['before'] ) ){
			$valid = $this->validate_date_values( $date_query['before'] );
		}

		if ( array_key_exists( 'after', $date_query ) && is_array( $date_query['after'] ) ){
			$valid = $this->validate_date_values( $date_query['after'] );
		}

		// Array containing all min-max checks.
		$min_max_checks = array();

		// Days per year.
		if ( array_key_exists( 'year', $date_query ) ) {
			/*
			 * If a year exists in the date query, we can use it to get the days.
			 * If multiple years are provided (as in a BETWEEN), use the first one.
			 */
			if ( is_array( $date_query['year'] ) ) {
				$_year = reset( $date_query['year'] );
			} else {
				$_year = $date_query['year'];
			}

			$max_days_of_year = date( 'z', mktime( 0, 0, 0, 12, 31, $_year ) ) + 1;
		} else {
			// otherwise we use the max of 366 (leap-year)
			$max_days_of_year = 366;
		}

		$min_max_checks['dayofyear'] = array(
			'min' => 1,
			'max' => $max_days_of_year
		);

		// Days per week.
		$min_max_checks['dayofweek'] = array(
			'min' => 1,
			'max' => 7
		);

		// Days per week.
		$min_max_checks['dayofweek_iso'] = array(
			'min' => 1,
			'max' => 7
		);

		// Months per year.
		$min_max_checks['month'] = array(
			'min' => 1,
			'max' => 12
		);

		// Weeks per year.
		if ( isset( $_year ) ) {
			/*
			 * If we have a specific year, use it to calculate number of weeks.
			 * Note: the number of weeks in a year is the date in which Dec 28 appears.
			 */
			$week_count = date( 'W', mktime( 0, 0, 0, 12, 28, $_year ) );

		} else {
			// Otherwise set the week-count to a maximum of 53.
			$week_count = 53;
		}

		$min_max_checks['week'] = array(
			'min' => 1,
			'max' => $week_count
		);

		// Days per month.
		$min_max_checks['day'] = array(
			'min' => 1,
			'max' => 31
		);

		// Hours per day.
		$min_max_checks['hour'] = array(
			'min' => 0,
			'max' => 23
		);

		// Minutes per hour.
		$min_max_checks['minute'] = array(
			'min' => 0,
			'max' => 59
		);

		// Seconds per minute.
		$min_max_checks['second'] = array(
			'min' => 0,
			'max' => 59
		);

		// Concatenate and throw a notice for each invalid value.
		foreach ( $min_max_checks as $key => $check ) {
			if ( ! array_key_exists( $key, $date_query ) ) {
				continue;
			}

			// Throw a notice for each failing value.
			foreach ( (array) $date_query[ $key ] as $_value ) {
				$is_between = $_value >= $check['min'] && $_value <= $check['max'];

				if ( ! is_numeric( $_value ) || ! $is_between ) {
					$error = sprintf(
						/* translators: Date query invalid date message: 1: invalid value, 2: type of value, 3: minimum valid value, 4: maximum valid value */
						__( 'Invalid value %1$s for %2$s. Expected value should be between %3$s and %4$s.' ),
						'<code>' . esc_html( $_value ) . '</code>',
						'<code>' . esc_html( $key ) . '</code>',
						'<code>' . esc_html( $check['min'] ) . '</code>',
						'<code>' . esc_html( $check['max'] ) . '</code>'
					);

					_doing_it_wrong( __CLASS__, $error, '4.1.0' );

					$valid = false;
				}
			}
		}

		// If we already have invalid date messages, don't bother running through checkdate().
		if ( ! $valid ) {
			return $valid;
		}

		$day_month_year_error_msg = '';

		$day_exists   = array_key_exists( 'day', $date_query ) && is_numeric( $date_query['day'] );
		$month_exists = array_key_exists( 'month', $date_query ) && is_numeric( $date_query['month'] );
		$year_exists  = array_key_exists( 'year',