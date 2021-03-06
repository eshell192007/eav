<?php

namespace Eav;

use ReflectionException;
use Eav\Attribute\Collection;
use Eav\Attribute\Relations\HasManyThrough as HasManyThroughOptions;
use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    const TYPE_STATIC                = 'static';
    
    public $timestamps = false;
    
    protected $primaryKey = 'attribute_id';
    
    protected $fillable = [
        'attribute_code', 'backend_class', 'backend_type',
        'backend_table', 'frontend_class', 'frontend_type',
        'frontend_label', 'source_class',  'default_value',
        'is_required', 'required_validate_class', 'entity_id'
    ];
    
    protected $with = [
        //'optionValues'
    ];

    /**
     * Entity instance
     *
     * @var Eav\Entity
     */
    protected $entity;

    /**
     * Backend instance
     *
     * @var Eav\Attribute\Backend
     */
    protected $backend;

    /**
     * Frontend instance
     *
     * @var Eav\Attribute\Frontend
     */
    protected $frontend;

    /**
     * Source instance
     *
     * @var Eav\Attribute\Source
     */
    protected $source;

    /**
     * Attribute id cache
     *
     * @var array
     */
    protected $attributeIdCache  = [];

    /**
     * Attribute data table name
     *
     * @var string
     */
    protected $dataTable  = null;
    
    
    /**
     * Attribute options
     *
     * @var array
     */
    protected $optionArray  = [];
    
    /**
     * Set attribute code
     *
     * @param   string $code
     * @return $this
     */
    public function setAttributeCode($code)
    {
        return $this->setAttribute('attribute_code', $code);
    }
    
    /**
     * Set attribute entity instance
     *
     * @param Eav\Entity $entity
     * @return $this
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
        return $this;
    }
    
    /**
     * Get attribute identifuer
     *
     * @return int | null
     */
    public function getAttributeId()
    {
        return $this->getKey();
    }
    
    
    /**
     * Get attribute name
     *
     * @return string
     */
    public function getAttributeCode()
    {
        return $this->getAttribute('attribute_code');
    }
    
    /**
     * Get Entity Type Id
     *
     * @return int|string $code
     */
    public function getEntityTypeId()
    {
        return $this->getAttribute('entity_id');
    }
    
    /**
     * Retreive entity type
     *
     * @return string
     */
    public function getEntityType()
    {
        return Entity::findById($this->getEntityTypeId());
    }
    
    /**
     * Retreive backend type
     *
     * @return string
     */
    public function getBackendType()
    {
        return $this->getAttribute('backend_type');
    }
    
    /**
     * Retreive frontend type
     *
     * @return string
     */
    public function getFrontendInput()
    {
        return $this->getAttribute('frontend_type');
    }
    
    /**
     * Retreive frontend label
     *
     * @return string
     */
    public function getFrontendLabel()
    {
        return $this->getAttribute('frontend_label');
    }
    
    /**
     * Retreive default value
     *
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->getAttribute('default_value');
    }
        

    /**
     * Retrieve entity instance
     *
     * @return Eav\Entity
     */
    public function getEntity()
    {
        if (!$this->entity) {
            $this->entity = $this->getEntityType();
        }
        return $this->entity;
    }
    
    /**
     * Retrieve backend instance
     *
     * @return Eav\Attribute\Backend
     */
    public function getBackend()
    {
        if (empty($this->backend)) {
            try {
                if (!$this->getAttribute('backend_class')) {
                    throw new ReflectionException('No class specified');
                }
                $backend = app($this->getAttribute('backend_class'));
            } catch (ReflectionException $e) {
                throw new \Exception('Invalid backend class specified: ' . $this->getAttribute('backend_class'));
            }

            $this->backend = $backend->setAttribute($this);
        }

        return $this->backend;
    }

    /**
     * Retrieve frontend instance
     *
     * @return Eav\Attribute\Frontend
     */
    public function getFrontend()
    {
        if (empty($this->frontend)) {
            try {
                if (!$this->getAttribute('frontend_class')) {
                    throw new ReflectionException('No class specified');
                }
                $frontend = app($this->getAttribute('frontend_class'));
            } catch (ReflectionException $e) {
                throw new \Exception('Invalid frontend class specified: ' . $this->getAttribute('frontend_class'));
            }
            
            $this->frontend = $frontend->setAttribute($this);
        }

        return $this->frontend;
    }

    /**
     * Retrieve source instance
     *
     * @return Eav\Attribute\Source
     */
    public function getSource()
    {
        if (empty($this->source)) {
            try {
                if (!$this->getAttribute('source_class')) {
                    throw new ReflectionException('No class specified');
                }
                $source = app($this->getAttribute('source_class'));
            } catch (ReflectionException $e) {
                throw new \Exception('Invalid source class specified: ' . $this->getAttribute('source_class'));
            }
            
            $this->source = $source->setAttribute($this);
        }
        return $this->source;
    }

    public function usesSource()
    {
        return ($this->getAttribute('frontend_type') === 'select' || $this->getAttribute('frontend_type') === 'multiselect')
            && !empty($this->getAttribute('source_class'));
    }
    
    /**
     * Get attribute backend table name
     *
     * @return string
     */
    public function getBackendTable()
    {
        if ($this->dataTable === null) {
            $backendTable = trim($this->getAttribute('backend_table'));
            if (empty($backendTable)) {
                $backendTable  = $this->getEntity()->getEntityTablePrefix().'_'.$this->getAttribute('backend_type');
            }
            $this->dataTable = $backendTable;
        }
        return $this->dataTable;
    }
    
    protected function getDefaultBackendClass()
    {
        return static::DEFAULT_BACKEND_CLASS;
    }

    protected function getDefaultFrontendClass()
    {
        return static::DEFAULT_FRONTEND_CLASS;
    }
    
    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }
    
    public static function findByCode($code, $entityCode)
    {
        $entity = Entity::findByCode($entityCode);
        
        $instance = new static;
        
        return $instance->newQuery()->where([
            'attribute_code' => $code,
            'entity_id' => $entity->getkey()
        ])->firstOrFail();
    }
    
    
    /**
     * Return attribute id
     *
     * @param string $entityType
     * @param string $code
     * @return int | null
     */
    public function getIdByCode($entityType, $code)
    {
        $k = "{$entityType}|{$code}";
        if (!isset($this->attributeIdCache[$k])) {
            $attribute = \DB::table($this->getTable())
                ->select('attribute_id')
                ->where('attribute_code', $code)
                ->where('entity_id', $entityType)
                ->first();
            if ($attribute) {
                $this->attributeIdCache[$k] = $attribute->attribute_id;
            } else {
                return null;
            }
        }
        return $this->attributeIdCache[$k];
    }
    
    public function insertAttribute($insertData)
    {
        $insertData['entity_type_id'] = $this->getEntity()->getKey();
        $insertData['attribute_id'] = $this->getKey();
        
        return $this->newBaseQueryBuilder()
            ->from($this->getBackendTable())
            ->insert($insertData);
    }

    public function updateAttribute($insertData, $entityId, $storeId = 0)
    {
        return $this->newBaseQueryBuilder()
            ->from($this->getBackendTable())
            ->where('entity_type_id', '=', $this->getEntity()->getKey())
            ->where('attribute_id', '=', $this->getKey())
            ->where('entity_id', '=', $entityId)
            ->where('store_id', '=', $storeId)
            ->update($insertData);
    }
    
    public function getAttributeInsertQuery($insertData)
    {
        $insertData['entity_type_id'] = $this->getEntity()->getKey();
        $insertData['attribute_id'] = $this->getKey();
        
        return $this->newBaseQueryBuilder()
            ->from($this->getBackendTable())
            ->getInsertSql($insertData);
    }
    
    public static function add($data)
    {
        $instance = new static;
                
        try {
            $eavEntity = Entity::where('entity_code', '=', $data['entity_code'])->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new \Exception("Unable to load Entity : ".$data['entity_code']);
        }
        
        unset($data['entity_code']);
        
        $data['entity_id'] = $eavEntity->entity_id;
        
        $options = [];
        
        if ($data['frontend_type'] == 'select' && empty($data['source_class'])) {
            if (isset($data['options'])) {
                $options = $data['options'];
                unset($data['options']);
            }
        }
        
        
        $instance->fill($data)->save();
        
        if ($instance->getKey()) {
            AttributeOption::add($instance, $options);
        }
    }
        
    public static function remove($data)
    {
        $instance = new static;
                
        try {
            $eavEntity = Entity::where('entity_code', '=', $data['entity_code'])->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new \Exception("Unable to load Entity : ".$data['entity_code']);
        }
        
        unset($data['entity_code']);
        
        $data['entity_id'] = $eavEntity->entity_id;
        
        $instance->where($data)->delete();
    }
    
    /**
     * Check if attribute is static
     *
     * @return bool
     */
    public function isStatic()
    {
        return $this->getAttribute('backend_type') == self::TYPE_STATIC || $this->getAttribute('backend_type') == '';
    }
    
    public function optionsArray()
    {
        if (empty($this->optionArray) && $this->usesSource()) {
            $this->optionArray = $this->getSource()->getOptionArray();
        }
        
        return $this->optionArray;
    }
    
    public function setOptionsArray($options)
    {
        return $this->optionArray = $options;
    }
        
    public function options()
    {
        return $this->hasMany(AttributeOption::class, 'attribute_id');
    }
            
    public function optionValues()
    {
        return $this->hasManyThroughOptions(AttributeOptionValue::class, AttributeOption::class, 'attribute_id', 'option_id');
    }
    
    /**
     * Define a has-many-through relationship.
     *
     * @param  string  $related
     * @param  string  $through
     * @param  string|null  $firstKey
     * @param  string|null  $secondKey
     * @param  string|null  $localKey
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function hasManyThroughOptions($related, $through, $firstKey = null, $secondKey = null, $localKey = null)
    {
        $through = new $through;

        $firstKey = $firstKey ?: $this->getForeignKey();

        $secondKey = $secondKey ?: $through->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        return new HasManyThroughOptions((new $related)->newQuery(), $this, $through, $firstKey, $secondKey, $localKey);
    }
    
    public function addToSelect($query, $joinType = 'inner', $callback = null)
    {
        if ($this->isStatic()) {
            return $this;
        }

        $this->addAttributeJoin($query, $joinType, $callback);

        $query->addSelect([$this->getSelectColumn()]);
    }
    
    public function getSelectColumn()
    {
        return "{$this->getAttributeCode()}_at.value as {$this->getAttributeCode()}";
    }
    
    
    public function addAttributeJoin($query, $joinType = 'inner', $callback = null)
    {
        if ($this->isStatic() || isset($query->joinCache[$this->getAttributeCode()])) {
            return $this;
        }

        $query->joinCache[$this->getAttributeCode()] = 1;
        
        if (is_callable($callback)) {
            $callback = function ($join) use ($query) {
                $callback($join, $query, "{$this->getAttributeCode()}_at");
            };
            
            if ($joinType == 'left') {
                $query->leftJoin("{$this->getBackendTable()} as {$this->getAttributeCode()}_at", $callback);
            } else {
                $query->join("{$this->getBackendTable()} as {$this->getAttributeCode()}_at", $callback);
            }
        } else {
            if ($joinType == 'left') {
                $query->leftJoin("{$this->getBackendTable()} as {$this->getAttributeCode()}_at", function ($join) use ($query) {
                    $join->on("{$query->from}.id", '=', "{$this->getAttributeCode()}_at.entity_id")
                        ->where("{$this->getAttributeCode()}_at.attribute_id", "=", $this->getAttributeId());
                });
            } else {
                $query->join("{$this->getBackendTable()} as {$this->getAttributeCode()}_at", function ($join) use ($query) {
                    $join->on("{$query->from}.id", '=', "{$this->getAttributeCode()}_at.entity_id")
                        ->where("{$this->getAttributeCode()}_at.attribute_id", "=", $this->getAttributeId());
                });
            }
        }
        
        return $this;
    }

    public function addAttributeOrderBy($query, $binding)
    {
        if ($this->isStatic()) {
            $query->orderBy("{$query->from}.{$binding['column']}", $binding['direction']);
        } else {
            $query->orderBy("{$this->getAttributeCode()}_at.value", $binding['direction']);
        }
    }
    
    public function addAttributeWhere($query, $binding)
    {
        $method = 'where'.lcfirst($binding['type']);
        $this->$method($query, $binding);

        return $this;
    }

    public function whereBasic($query, $binding)
    {
        if ($this->isStatic()) {
            $query->where("{$query->from}.{$binding['column']}", $binding['operator'], $binding['value'], $binding['boolean']);
        } else {
            $query->where("{$this->getAttributeCode()}_at.value", $binding['operator'], $binding['value'], $binding['boolean']);
        }
    }

    public function whereBetween($query, $binding)
    {
        if ($this->isStatic()) {
            $query->whereBetween("{$query->from}.{$binding['column']}", $binding['values'], $binding['boolean'], $binding['not']);
        } else {
            $query->whereBetween("{$this->getAttributeCode()}_at.value", $binding['values'], $binding['boolean'], $binding['not']);
        }
    }

    public function whereIn($query, $binding)
    {
        if ($this->isStatic()) {
            $query->whereIn("{$query->from}.{$binding['column']}", $binding['values'], $binding['boolean'], $binding['not']);
        } else {
            $query->whereIn("{$this->getAttributeCode()}_at.value", $binding['values'], $binding['boolean'], $binding['not']);
        }
    }

    public function whereNotIn($query, $binding)
    {
        if ($this->isStatic()) {
            $query->whereNotIn("{$query->from}.{$binding['column']}", $binding['values'], $binding['boolean'], $binding['not']);
        } else {
            $query->whereNotIn("{$this->getAttributeCode()}_at.value", $binding['values'], $binding['boolean'], $binding['not']);
        }
    }

    public function whereNull($query, $binding)
    {
        if ($this->isStatic()) {
            $query->whereNull("{$query->from}.{$binding['column']}", $binding['boolean'], $binding['not']);
        } else {
            $query->whereNull("{$this->getAttributeCode()}_at.value", $binding['boolean'], $binding['not']);
        }
    }

    public function whereNotNull($query, $binding)
    {
        if ($this->isStatic()) {
            $query->whereNotNull("{$query->from}.{$binding['column']}", $binding['boolean'], $binding['not']);
        } else {
            $query->whereNotNull("{$this->getAttributeCode()}_at.value", $binding['boolean'], $binding['not']);
        }
    }

    public function whereDate($query, $binding)
    {
        if ($this->isStatic()) {
            $query->whereDate("{$query->from}.{$binding['column']}", $binding['operator'], $binding['value'], $binding['boolean']);
        } else {
            $query->whereDate("{$this->getAttributeCode()}_at.value", $binding['operator'], $binding['value'], $binding['boolean']);
        }
    }

    public function whereDay($query, $binding)
    {
        if ($this->isStatic()) {
            $query->whereDay("{$query->from}.{$binding['column']}", $binding['operator'], $binding['value'], $binding['boolean']);
        } else {
            $query->whereDay("{$this->getAttributeCode()}_at.value", $binding['operator'], $binding['value'], $binding['boolean']);
        }
    }

    public function whereMonth($query, $binding)
    {
        if ($this->isStatic()) {
            $query->whereMonth("{$query->from}.{$binding['column']}", $binding['operator'], $binding['value'], $binding['boolean']);
        } else {
            $query->whereMonth("{$this->getAttributeCode()}_at.value", $binding['operator'], $binding['value'], $binding['boolean']);
        }
    }

    public function whereYear($query, $binding)
    {
        if ($this->isStatic()) {
            $query->whereYear("{$query->from}.{$binding['column']}", $binding['operator'], $binding['value'], $binding['boolean']);
        } else {
            $query->whereYear("{$this->getAttributeCode()}_at.value", $binding['operator'], $binding['value'], $binding['boolean']);
        }
    }
}
