<?php

require __DIR__ . '/vendor/autoload.php';

$db = db_sqlite::open(['database' => __DIR__ . '/db/br-costs.sqlite3']);

$db->ensureSchema(require 'inc.db-schema.php');

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
	protected function relate_costs() {
		return $this->to_one(Costs::class, 'costs_id')->eager(['prices'])->collect(function(self $object) {
			if ( !$object->costs_id ) {
				$costs = Costs::create(get_class($object) . ' ' . $object->id);
				$object->update(['costs_id' => $costs->id]);
			}
			return $object->costs_id;
		});
	}

	protected function preUpdateCosts( array &$data ) {
		$prices = $data['costs'] ?? null;
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

	protected function relate_timesets() {
		return $this->to_many(ResourceTimeset::class, 'resource_id')->order('start_date');
	}

	protected function relate_resource_price() {
		return $this->to_one(ResourcePrice::class, 'resource_price_id')->eager(['costs']);
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

	static function presave( array &$data ) {
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

	protected function relate_resource() {
		return $this->to_one(Resource::class, 'resource_id');
	}

	protected function relate_open_timeset() {
		return $this->to_one(Timeset::class, 'open_timeset_id');
	}

	protected function relate_peak_times() {
		return $this->to_many(ResourcePeakTime::class, 'resource_timeset_id');
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

	protected function relate_timeset() {
		return $this->to_one(Timeset::class, 'timeset_id');
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

	static function presave( array &$data ) {
		foreach (range(0, 6) as $day) {
			foreach (['open', 'clos'] as $which) {
				$col = "{$which}_{$day}";
				isset($data[$col]) and $data[$col] = (int) $data[$col];
			}
		}
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

	static function presave( array &$data ) {
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

	protected function relate_datas() {
		return $this->to_many(MemberTypeData::class, 'member_type_id')->order('start_date')->eager(['costs']);
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
 * @property CostsDatum[] $prices
 */
class Costs extends Model {
	const COSTS_DATA = 'costs_data';

	static public $_table = 'costs';

	public $costs;

	protected function relate_prices() {
		return $this->to_many(CostsDatum::class, 'costs_id');
	}

	protected function initPrices() {
		if ( $this->costs !== null ) return;

		$this->costs = [];
		foreach ( $this->prices as $price ) {
			$this->costs["{$price->day_dimension_id}-{$price->time_dimension_id}-{$price->context}"] = $price->costs;
		}
	}

	public function saveCosts() {
		$this->initPrices();

		self::$_db->begin();
		self::$_db->delete(CostsDatum::$_table, ['costs_id' => $this->id]);
		foreach ( $this->costs as $key => $price ) {
			list($did, $tid, $context) = explode('-', $key);
			self::$_db->insert(CostsDatum::$_table, [
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
		$this->initPrices();

		$this->costs["{$did}-{$tid}-{$context}"] = (float)$price;
	}

	public function get( $did, $tid, $context ) {
		$this->initPrices();

		return (float) ($this->costs["{$did}-{$tid}-{$context}"] ?? 0.0);
	}

	public function getInput( $did, $tid, $context ) {
		$price = $this->get($did, $tid, $context);
		if ( $price > 0 ) {
			return number_format($price, 2);
		}

		return '0';
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

/**
 * @property int $costs_id
 * @property int $day_dimension_id
 * @property int $time_dimension_id
 * @property string $context
 * @property float $costs
 */
class CostsDatum extends Model {
	public static $_table = 'costs_data';
}
