<?php

require 'env.php';

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

	protected function preUpdate( array &$data ) {
		$prices = @$data['costs'];
		unset($data['costs']);
		return $prices;
	}

	protected function postUpdate( $prices ) {
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
 */
class TimeDimension extends Model {
	static public $_table = 'time_dimension';

	static function presave( &$data ) {
		$data['is_default'] = !empty($data['is_default']);
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
		$prices = $this->preUpdate($data);
		parent::update($data);
		$this->postUpdate($prices);
	}
}

/**
 * @property string $label
 */
class MemberType extends Model {
	static public $_table = 'member_types';

	protected function get_datas() {
		return MemberTypeData::all('member_type_id = ? ORDER BY start_date', [$this->id]);
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
		$prices = $this->preUpdate($data);
		parent::update($data);
		$this->postUpdate($prices);
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

	public function getDisplay( $did, $tid, $context ) {
		$price = $this->get($did, $tid, $context);
		if ( $price > 0 ) {
			return number_format($price, 2);
		}

		return '';
	}

	static function create( $label ) {
		$id = self::insert(['label' => $label]);

		return self::find($id);
	}
}
