<?php

namespace Eav\Traits;

use Eav\Attribute\Collection;

trait Attribute
{
    protected $attributesCollection = null;
    
    protected $attributesCollectionKeys = [];
        
    public function loadAttributes($attributes = array(), $static = false, $required = false)
    {
        $attributes = array_unique($attributes);

        $alreadyLoadedAttkeys = array_intersect($this->attributesCollectionKeys, $attributes);
        
        if (count($alreadyLoadedAttkeys) && count($alreadyLoadedAttkeys) == count($attributes)) {
            return $this->attributesCollection->intersectKeys($attributes);
        } elseif (count($alreadyLoadedAttkeys) && count($alreadyLoadedAttkeys) < count($attributes)) {
            $newAttkeys = array_diff($attributes, $this->attributesCollectionKeys);
            $loadedAttributes = $this->fetchAttributes($newAttkeys, $static);

            $this->attributesCollection = $this->attributesCollection->merge($loadedAttributes);
            $loadedAttributes = $this->attributesCollection->intersectKeys($attributes);
        } else {
            $loadedAttributes = $this->fetchAttributes($attributes, $static, $required);
            $this->attributesCollection = $loadedAttributes;
        }
        
        $this->attributesCollectionKeys = array_merge($this->attributesCollectionKeys, $loadedAttributes->keys()->toArray());
                
        return $loadedAttributes;
    }
    
    protected function fetchAttributes($attributes = array(), $static = false, $required = false)
    {
        $loadedAttributes = $this->baseEntity()->eavAttributes();
         				
		$loadedAttributes->where(function ($query) use ($static, $required, $attributes) {
			 if (!empty($attributes)) {	
				$query->orWhereIn('attribute_code', $attributes);
			 }
			
			if ($static) {
                $query->orWhere('backend_type', 'static');
            }
            if ($required) {
                $query->orWhere('is_required', 1);
            }	
		});
        
        return $loadedAttributes->get();
    }
    
    public function extractAttributes($columns = null)
    {
        return [['*'], $this->getEavTableAttribute($columns)];
    }
    
    public function getMainTableAttribute($loadedAttributes)
    {
        $mainTableAttributeCollection = $loadedAttributes->filter(function ($attribute) {
            if ($attribute->isStatic()) {
                return true;
            }
        });
        
        $mainTableAttribute = $mainTableAttributeCollection->keys()->toArray();
        
        $mainTableAttribute[] = 'entity_id';
        $mainTableAttribute[] = 'attribute_set_id';
        
        return $mainTableAttribute;
    }
    
    protected function getEavTableAttribute($columns)
    {
        $loadedAttributes = $this->loadAttributes();

        $eavTableAttributeCollection = $loadedAttributes->filter(function ($attribute) use ($columns) {
            if (!$attribute->isStatic() && ($columns == null || in_array($attribute->getAttributeCode(), $columns))) {
                return true;
            }
        });
        
        /*$eavTableAttribute = $eavTableAttributeCollection->map(function($attribute) {
            return $attribute->getAttributeCode();
        })->toArray();*/
        
        return $eavTableAttributeCollection;
    }
}
