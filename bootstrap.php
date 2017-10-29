<?php

require __DIR__ . '/vendor/autoload.php';

require 'inc.functions.php';

$db = db_sqlite::open(['database' => __DIR__ . '/db/br-costs.sqlite3']);

require 'inc.ensure-db-schema.php';

db_generic_model::$_db = $db;

/**
 * @property int $id
 */
class Model extends db_generic_model {
	static function _updates( array $updates, callable $isEmpty ) {
		foreach ( $updates as $id => $data ) {
			if ( $id ) {
				if ( $isEmpty($data) ) {
					static::find($id)->delete();
				} else {
					static::find($id)->update($data);
				}
			} else {
				if ( !$isEmpty($data) ) {
					static::insert($data);
				}
			}
		}
	}
}

/**
 *
 */
trait WithCosts {
	protected function get_costs() {
		if ( $this->costs_id ) {
			return Costs::find($this->costs_id);
		}

		$costs = Costs::create(get_class($this) . ' ' . $this->id);
		$this->update(['costs_id' => $costs->id]);

		return $costs;
	}

	protected function preUpdateCosts( array &$data ) {
		$prices = @$data['costs'];
		unset($data['costs']);

		return $prices;
	}

	protected function postUpdateCosts( $prices ) {
		if ( $prices ) {
			/** @var Costs $costs */
			$costs = $this->costs;
			foreach ( $prices as $key => $price ) {
				list($did, $tid, $context) = explode('-', $key);
				$costs->set($did, $tid, $context, $price);
			}
			$costs->saveCosts();
		}
	}
}

/**
 * @property string $label
 * @property int $resource_price_id
 * @property ResourceTimeset[] $timesets
 * @property ResourcePrice $resource_price
 */
class Resource extends Model {
	static public $_table = 'resources';

	protected function get_timesets() {
		return ResourceTimeset::all('resource_id = ? ORDER BY start_date', [$this->id]);
	}

	protected function get_resource_price() {
		return ResourcePrice::find($this->resource_price_id);
	}

	/** @return ResourceTimeset */
	function getDatedTimesetFor( $date ) {
		/** @var ResourceTimeset $timeset */
		foreach ( array_reverse($this->timesets) as $timeset ) {
			if ( $date >= $timeset->start_date && $date <= $timeset->end_date ) {
				return $timeset;
			}
		}
	}

	/** @return bool */
	function isOpenFor( $date, $startTime, $endTime ) {
		$today = date('w', strtotime($date));
		$activeTimeset = $this->getDatedTimesetFor($date);
		if ( $activeTimeset->open_timeset->{"open_$today"} < $endTime && $activeTimeset->open_timeset->{"clos_$today"} > $startTime ) {
			return true;
		}

		return false;
	}

	/** @return Timeset */
	function getTimesetFor( $date, $startTime, $endTime ) {
		if ( $this->isOpenFor($date, $startTime, $endTime) ) {
			$timeset = $this->getSpecialFor($date, $startTime, $endTime);
			if ( $timeset ) {
				return $timeset->timeset;
			}

			$activeTimeset = $this->getDatedTimesetFor($date);
			return $activeTimeset->open_timeset;
		}

		// Closed
		return null;
	}

	/** @return TimeDimension */
	function getTimeDimensionFor( $date, $startTime, $endTime ) {
		if ( $this->isOpenFor($date, $startTime, $endTime) ) {
			$timeset = $this->getSpecialFor($date, $startTime, $endTime);
			if ( $timeset ) {
				return $timeset->time_dimension;
			}

			// The default
			return TimeDimension::getDefault();
		}

		// Closed
		return null;
	}

	/** @return ResourcePeakTime */
	function getSpecialFor( $date, $startTime, $endTime ) {
		$today = date('w', strtotime($date));
		$activeTimeset = $this->getDatedTimesetFor($date);
		foreach ( $activeTimeset->peak_times as $timeset ) {
			if ( $timeset->timeset->{"open_$today"} < $endTime && $timeset->timeset->{"clos_$today"} > $startTime ) {
				return $timeset;
			}
		}

		// No peak-ish time
		return null;
	}

	static function presave( &$data ) {
		parent::presave($data);

		$data['resource_price_id'] = $data['resource_price_id'] ?: null;
	}

	function update( $data ) {
		$timesets = @$data['timesets'];
		unset($data['timesets']);

		parent::update($data);

		if ( $timesets ) {
			ResourceTimeset::_updates($timesets, function( array &$data ) {
				$data['resource_id'] = $this->id;

				return empty($data['start_date']) || empty($data['end_date']);
			});
		}
	}

	function __toString() {
		return $this->label ?: '';
	}
}

/**
 * @property int $resource_id
 * @property string $start_date
 * @property string $end_date
 * @property int $open_timeset_id
 * @property Resource $resource
 * @property Timeset $open_timeset
 * @property ResourcePeakTime[] $peak_times
 */
class ResourceTimeset extends Model {
	static public $_table = 'resource_timesets';

	protected function get_resource() {
		return Resource::find($this->resource_id);
	}

	protected function get_open_timeset() {
		return Timeset::find($this->open_timeset_id);
	}

	protected function get_peak_times() {
		return ResourcePeakTime::all(['resource_timeset_id' => $this->id]);
	}

	function update( $data ) {
		$peaks = @$data['peak_times'];
		unset($data['peak_times']);

		parent::update($data);

		if ( $peaks ) {
			ResourcePeakTime::_updates($peaks, function( array &$data ) {
				$data['resource_timeset_id'] = $this->id;

				return empty($data['timeset_id']) || empty($data['time_dimension_id']);
			});
		}
	}
}

/**
 * @property int $resource_timeset_id
 * @property int $timeset_id
 * @property int $time_dimension_id
 * @property ResourceTimeset $resource_timeset
 * @property Timeset $timeset
 * @property TimeDimension $time_dimension
 */
class ResourcePeakTime extends Model {
	static public $_table = 'resource_peak_times';

	protected function get_resource_timeset() {
		return ResourceTimeset::find($this->resource_timeset_id);
	}

	protected function get_timeset() {
		return Timeset::find($this->timeset_id);
	}

	protected function get_time_dimension() {
		return TimeDimension::find($this->time_dimension_id);
	}
}

/**
 * @property string $label
 * @property string $open_N
 * @property string $clos_N
 */
class Timeset extends Model {
	static public $_table = 'timesets';

	function __toString() {
		return $this->label ?: '';
	}
}

/**
 * @property string $label
 * @property int $costs_id
 * @property Costs $costs
 */
class ResourcePrice extends Model {
	use WithCosts;

	static public $_table = 'resource_prices';

	function update( $data ) {
		$prices = $this->preUpdateCosts($data);
		parent::update($data);
		$this->postUpdateCosts($prices);
	}

	function __toString() {
		return $this->label ?: '';
	}
}

/**
 * @property string $label
 * @property string $days
 */
class DayDimension extends Model {
	static public $_table = 'day_dimension';

	function __toString() {
		return $this->label ?: '';
	}
}

/**
 * @property string $label
 * @property bool $is_default
 * @property bool $is_peak
 * @property string $color
 */
class TimeDimension extends Model {
	static public $_table = 'time_dimension';

	static function presave( &$data ) {
		$data['is_default'] = !empty($data['is_default']);
		$data['is_peak'] = !empty($data['is_peak']);
	}

	/** @return TimeDimension */
	static function getDefault() {
		static $cache;
		if ( $cache === null ) {
			$cache = TimeDimension::first(['is_default' => true]);
		}

		return $cache;
	}

	function __toString() {
		return $this->label ?: '';
	}
}

/**
 * @property string $label
 * @property int $costs_id
 * @property Costs $costs
 */
class ClassActivity extends Model {
	use WithCosts;

	static public $_table = 'class_activities';

	protected function get_costs() {
		if ( $this->costs_id ) {
			return Costs::find($this->costs_id);
		}

		$costs = Costs::create('class activity ' . $this->id);
		$this->update(['costs_id' => $costs->id]);

		return $costs;
	}

	function update( $data ) {
		$prices = $this->preUpdateCosts($data);
		parent::update($data);
		$this->postUpdateCosts($prices);
	}
}

/**
 * @property string $label
 * @property MemberTypeData[] $datas
 */
class MemberType extends Model {
	static public $_table = 'member_types';

	protected function get_datas() {
		return MemberTypeData::all('member_type_id = ? ORDER BY start_date', [$this->id]);
	}

	/** @return MemberTypeData */
	function getDataFor( $date ) {
		/** @var MemberTypeData $data */
		foreach ( array_reverse($this->datas) as $data ) {
			if ( $data->start_date <= $date ) {
				return $data;
			}
		}
	}

	function update( $data ) {
		$datas = @$data['datas'];
		unset($data['datas']);

		parent::update($data);

		if ( $datas ) {
			MemberTypeData::_updates($datas, function( array &$data ) {
				$data['member_type_id'] = $this->id;

				return empty($data['start_date']);
			});
		}
	}

	static function insert( array $data ) {
		$id = parent::insert($data);
		MemberTypeData::insert([
			'member_type_id' => $id,
			'start_date'     => '0000-00-00',
		]);

		return $id;
	}

	function __toString() {
		return $this->label ?: '';
	}
}

/**
 * @property int $meber_type_id
 * @property string $start_date
 * @property int $costs_id
 * @property Costs $costs
 */
class MemberTypeData extends Model {
	use WithCosts;

	static public $_table = 'member_type_datas';

	function update( $data ) {
		$prices = $this->preUpdateCosts($data);
		parent::update($data);
		$this->postUpdateCosts($prices);
	}
}

/**
 * @property string $label
 */
class Costs extends Model {
	const COSTS_DATA = 'costs_data';

	static public $_table = 'costs';

	public $costs = [];

	public function init() {
		$this->fetchCosts();
	}

	protected function fetchCosts() {
		$this->costs = [];
		$prices = self::$_db->select(self::COSTS_DATA, ['costs_id' => $this->id]);
		foreach ( $prices as $price ) {
			$this->costs["{$price->day_dimension_id}-{$price->time_dimension_id}-{$price->context}"] = $price->costs;
		}
	}

	public function saveCosts() {
		self::$_db->begin();
		self::$_db->delete(self::COSTS_DATA, ['costs_id' => $this->id]);
		foreach ( $this->costs as $key => $price ) {
			list($did, $tid, $context) = explode('-', $key);
			self::$_db->insert(self::COSTS_DATA, [
				'costs_id'          => $this->id,
				'day_dimension_id'  => $did,
				'time_dimension_id' => $tid,
				'context'           => $context,
				'costs'             => $price,
			]);
		}
		self::$_db->commit();
	}

	public function set( $did, $tid, $context, $price ) {
		$this->costs["{$did}-{$tid}-{$context}"] = (float)$price;
	}

	public function get( $did, $tid, $context ) {
		return (float)@$this->costs["{$did}-{$tid}-{$context}"];
	}

	public function getInput( $did, $tid, $context ) {
		$price = $this->get($did, $tid, $context);
		if ( $price > 0 ) {
			return number_format($price, 2);
		}

		return '';
	}

	public function getDisplay( $did, $tid, $context ) {
		$price = (float)$this->get($did, $tid, $context);

		return number_format($price, 2);
	}

	static function create( $label ) {
		$id = self::insert(['label' => $label]);

		return self::find($id);
	}
}
