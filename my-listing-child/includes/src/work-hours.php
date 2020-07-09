<?php

namespace MyListing\Src;

use \DateTime as DateTime;

class Work_Hours {

	protected $hours = [],
			  $raw_hours = [];

	protected $status = '',
			  $message = '',
			  $open_now = false,
			  $active_day = '';

	protected $timezone = '',
			  $weekdays = [],
			  $weekdays_l10n = [];

	public function __construct( $hours ) {
		$this->raw_hours = $hours;
		$this->timezone  = date_default_timezone_get();
		$this->weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
		$this->weekdays_l10n = [
			__( 'Sunday', 'my-listing' ),
			__( 'Monday', 'my-listing' ),
			__( 'Tuesday', 'my-listing' ),
			__( 'Wednesday', 'my-listing' ),
			__( 'Thursday', 'my-listing' ),
			__( 'Friday', 'my-listing' ),
			__( 'Saturday', 'my-listing' )
			
		];

		$this->weekdays_l10n = array_combine( $this->weekdays, $this->weekdays_l10n );

		$this->to_parseable_format();

		if ( ! empty( $this->raw_hours['timezone'] ) ) {
			date_default_timezone_set( $this->raw_hours['timezone'] );
		}

		$this->parse();

		if ( ! empty( $this->raw_hours['timezone'] ) ) {
			date_default_timezone_set( $this->timezone );
		}
	}

	/*
	 * Convert hours to the format of a multidimensional array with day names as keys,
	 * and each day with arrays of hour ranges, with 'from' and 'to' keys.
	 */
	public function to_parseable_format() {
		foreach ( $this->weekdays as $weekday ) {
			$this->hours[ $weekday ] = [];

			if ( ! empty( $this->raw_hours[ $weekday ] ) && is_array( $this->raw_hours[ $weekday ] ) ) {
				$this->hours[ $weekday ] = $this->raw_hours[ $weekday ];

				// Convert from the single-range day format used in earlier versions.
				if (
					isset( $this->raw_hours[ $weekday ]['from'] ) ||
					isset( $this->raw_hours[ $weekday ]['to'] )
				) {
					$this->hours[ $weekday ] = [ $this->raw_hours[ $weekday ] ];
				}
			} else {
				$this->hours[ $weekday ] = [];
			}

			// Day status was added in v1.6. Default to 'enter-hours' for compatibility with earlier versions.
			if ( empty( $this->hours[ $weekday ][ 'status' ] ) ) {
				$this->hours[ $weekday ][ 'status' ] = 'enter-hours';
			}
		}
	}

	public function parse() {
		$today = isset( $this->hours[ date('l') ]) ? $this->hours[date('l')] : false;
		$yesterday = isset( $this->hours[ date( 'l', strtotime('-1 day') ) ]) ?  $this->hours[ date( 'l', strtotime('-1 day' ) ) ] : false;
		$now = DateTime::createFromFormat( 'H:i', date('H:i') );
		$this->active_day = $now->format('l');

		if ( $today && $this->parse_day( $today, $now ) ) {
			return true;
		}

		if ( $yesterday && $this->parse_day( $yesterday, $now, true ) ) {
			$this->active_day = date( 'l', strtotime('-1 day') );
			return true;
		}
	}


	public function parse_day( $day, $time, $yesterday_flag = false ) {
		if ( ! empty( $day['status'] ) && ! $yesterday_flag ) {
			if ( $day['status'] == 'open-all-day' ) {
				$this->status = 'open';
				$this->message = __( 'Open', 'my-listing' );
				return true;
			}

			if ( $day['status'] == 'closed-all-day' ) {
				$this->status = 'closed';
				$this->message = __( 'Closed', 'my-listing' );
				return false;
			}

			if ( $day['status'] == 'by-appointment-only' ) {
				$this->status = 'open';
				$this->message = __( 'Open', 'my-listing' );
				return false;
			}
		}

		unset( $day['status'] ); // so $day only contains hour ranges.

		$ranges = [];
		foreach ( $day as $range ) {
			if ( empty( $range['from'] ) || empty( $range['to'] ) ) {
				continue;
			}

			$ranges[] = $range;
			$start = DateTime::createFromFormat('H:i', $range['from']);
			$end = DateTime::createFromFormat('H:i', $range['to']);

			if ( ! $start || ! $end ) {
				continue;
			}

			if ( $yesterday_flag ) {
				$start->modify('-1 day');
				$end->modify('-1 day');
			}

			/*
			 * If the end time is smaller than the start time, it means
			 * the end time belongs to tomorrow. E.g. 17:00 - 03:00
			 */
			if ( $end <= $start ) {
				$end->modify('+1 day');
			}

			/*
			 * Business is open.
			 */
			if ( $time >= $start && $time < $end ) {
				// Time until closes, in minutes.
				$time_until_closes = ( $end->getTimestamp() - $time->getTimestamp() ) / 60;

				$this->open_now = true;

				if ( $time_until_closes <= 5 ) {
					$this->status  = 'closing';
					$this->message = __( 'Closes in a few minutes', 'my-listing' );
				} elseif ( $time_until_closes <= 30 ) {
					$this->status  = 'closing';
					$this->message = sprintf( __( 'Closes in %d minutes', 'my-listing' ), ( round( $time_until_closes / 5 ) * 5 ) );
				} else {
					$this->status = 'open';
					$this->message = __( 'Open', 'my-listing' );
				}

				return true;
			}

			/*
			 * Business is closed.
			 */
			if ( $time < $start ) {
				// Time until opens, in minutes.
				$time_until_opens = ( $start->getTimestamp() - $time->getTimestamp() ) / 60;
				// dump('__' . $time_until_opens);

				if ( $time_until_opens <= 5 ) {
					$this->message = __( 'Opens in a few minutes', 'my-listing' );
					$this->status = 'opening';

					return true;
				} elseif ( $time_until_opens <= 30 ) {
					$this->message = sprintf( __( 'Opens in %d minutes', 'my-listing' ), ( round( $time_until_opens / 5 ) * 5 ) );
					$this->status = 'opening';

					return true;
				} else {
					$this->status = 'closed';
					$this->message = __( 'Closed', 'my-listing' );
				}
			}
		}

		if ( empty( $ranges ) && ! $yesterday_flag ) {
			$this->status = 'not-available';
			$this->message = __( 'Not Available', 'my-listing' );
			return false;
		}

		if ( ! $yesterday_flag ) {
			$this->status = 'closed';
			$this->message = __( 'Closed', 'my-listing' );

			return false;
		}
	}

	public function get_open_now() {
		return in_array( $this->status, [ 'open', 'closing', 'open-all-day', 'by-appointment-only' ] );
	}

	public function get_status() {
		return $this->status;
	}

	public function get_message() {
		return $this->message;
	}

	public function get_active_day() {
		return $this->active_day;
	}

	public function get_day_schedule( $day ) {
		if ( ! isset( $this->hours[ $day ] ) ) {
			return __( 'N/A', 'my-listing' );
		}

		if ( $this->hours[$day]['status'] == 'open-all-day' ) {
			return __( 'Open 24h', 'my-listing' );
		}

		if ( $this->hours[$day]['status'] == 'closed-all-day' ) {
			return __( 'Closed', 'my-listing' );
		}

		if ( $this->hours[$day]['status'] == 'by-appointment-only' ) {
			return __( 'By appointment only', 'my-listing' );
		}

		$hours = array_filter( $this->hours[ $day ] );
		$ranges = [];
		unset( $hours['status'] );

		foreach ( $hours as $range ) {
			if ( ! empty( $range['from'] ) && ! empty( $range['to'] ) ) {
				$ranges[] = $range;
			}
		}

		if ( empty( $ranges ) ) {
			return __( 'N/A', 'my-listing' );
		}

		$output = '';
		foreach ( $ranges as $range ) {
			$output .= sprintf( '<span>%s - %s</span>', $this->format_time( $range['from'] ), $this->format_time( $range['to'] ) );
		}

		return $output;
	}

	public function get_todays_schedule() {
		if ( ! isset( $this->hours[ $this->active_day ] ) ) {
			return __( 'Today\'s work schedule is not available', 'my-listing' );
		}

		if ( $this->hours[$this->active_day]['status'] == 'open-all-day' ) {
			return __( 'Open 24h today', 'my-listing' );
		}

		if ( $this->hours[$this->active_day]['status'] == 'closed-all-day' ) {
			return __( 'Closed today', 'my-listing' );
		}

		if ( $this->hours[$this->active_day]['status'] == 'by-appointment-only' ) {
			return __( 'Open hours today: By appointment only', 'my-listing' );
		}

		$hours = array_filter( $this->hours[ $this->active_day ] );
		$ranges = [];
		unset( $hours['status'] );

		foreach ( $hours as $range ) {
			if ( empty( $range['from'] ) || empty( $range['to'] ) ) {
				continue;
			}

			$start = DateTime::createFromFormat( 'H:i', $range['from'] );
			$end = DateTime::createFromFormat( 'H:i', $range['to'] );

			if ( ! $start || ! $end ) {
				continue;
			}

			$ranges[] = $range;
		}

		if ( empty( $ranges ) ) {
			return __( 'Today\'s work schedule is not available', 'my-listing' );
		}

		return sprintf( __( 'Open hours today:', 'my-listing' ) . ' <span>%s - %s</span>', $this->format_time( $ranges[0]['from'] ), $this->format_time( $ranges[0]['to'] ) );
	}

	public function get_schedule() {
		$days = [];
		foreach ( $this->hours as $weekday => $ranges ) {
			$daystatus = $ranges['status'];
			unset( $ranges['status'] ); // so $weekdays only contains hour ranges.

			$days[ $weekday ] = [
				'day' => $weekday,
				'day_l10n' => $this->weekdays_l10n[ $weekday ],
				'ranges' => $ranges,
				'status' => $daystatus,
			];
		}

		return $days;
	}

	public function is_empty() {
		foreach ( $this->hours as $ranges ) {
			if ( $ranges['status'] != 'enter-hours' ) {
				return false;
			}

			unset( $ranges['status'] ); // so $weekdays only contains hour ranges.

			if ( ! empty( $ranges ) ) {
				return false;
			}
		}

		return true;
	}

	public function format_time( $time ) {
		return date_i18n( get_option('time_format'), strtotime( $time ) );
	}

	public function get_day_ranges( $day ) {
		return array_filter( (array) $this->hours[ $day ], function( $day ) {
			return is_array( $day );
		} );
	}

	public function schema_format() {
		$days = [];
		foreach ( $this->hours as $weekday => $ranges ) {

			if ( $ranges['status'] == 'closed-all-day' || $ranges['status'] == 'by-appointment-only' ) {
				continue;
			}

			if ( $ranges['status'] == 'open-all-day' ) {
				$days[] = substr( $weekday, 0, 2 );
				continue;
			}

			foreach ( (array) $ranges as $range ) {
				if ( empty( $range['from'] ) || empty( $range['to'] ) ) {
					continue;
				}

				$start = DateTime::createFromFormat( 'H:i', $range['from'] );
				$end = DateTime::createFromFormat( 'H:i', $range['to'] );

				if ( ! $start || ! $end ) {
					continue;
				}

				$days[] = sprintf( '%s %s-%s', substr( $weekday, 0, 2 ), $start->format('H:i'), $end->format('H:i') );
			}
		}

		return $days;
	}

	public function get_short_format() {
		$hours = [];

		foreach ( $this->hours as $weekday => $ranges ) {
			$day = substr( $weekday, 0, 2 );
			$status = $ranges['status'];

			if ( $status === 'closed-all-day' ) {
				$hours[ $day ] = 'C';
			}

			if ( $status === 'by-appointment-only' ) {
				$hours[ $day ] = 'A';
			}

			if ( $status === 'open-all-day' ) {
				$hours[ $day ] = 'O';
			}

			if ( $ranges['status'] === 'enter-hours' ) {
				unset( $ranges['status'] );
				$hours[ $day ] = [];

				foreach ( $ranges as $range ) {
					$hours[ $day ][] = join( '-', $range );
				}
			}
		}

		if ( ! empty( $this->raw_hours['timezone'] ) ) {
			$hours['Tz'] = $this->raw_hours['timezone'];
		}

		return $hours;
	}

	public static function parse_short_format( $short_hours ) {
		$hours = [];
		$day_map = [
			'Su' => 'Sunday',
			'Mo' => 'Monday',
			'Tu' => 'Tuesday',
			'We' => 'Wednesday',
			'Th' => 'Thursday',
			'Fr' => 'Friday',
			'Sa' => 'Saturday',
		];

		if ( ! empty( $short_hours['Tz'] ) ) {
			$hours['timezone'] = $short_hours['Tz'];
			unset( $short_hours['Tz'] );
		}

		foreach ( $short_hours as $day => $ranges ) {
			if ( ! isset( $day_map[ $day ] ) ) {
				continue;
			}

			$weekday = $day_map[ $day ];

			if ( $ranges === 'C' ) {
				$hours[ $weekday ] = [ 'status' => 'closed-all-day' ];
			}

			if ( $ranges === 'A' ) {
				$hours[ $weekday ] = [ 'status' => 'by-appointment-only' ];
			}

			if ( $ranges === 'O' ) {
				$hours[ $weekday ] = [ 'status' => 'open-all-day' ];
			}

			if ( is_array( $ranges ) ) {
				$hours[ $weekday ] = [];
				$hours[ $weekday ]['status'] = 'enter-hours';

				foreach ( $ranges as $range ) {
					$range = explode( '-', $range );
					$hours[ $weekday ][] = [
						'from' => $range[0],
						'to' => $range[1],
					];
				}
			}
		}

		return new self( $hours );
	}
}
