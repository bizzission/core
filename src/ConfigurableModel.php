<?php

namespace Amethyst\Core;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Railken\Bag;
use Railken\EloquentInstance\HasRelationships;
use Railken\EloquentMapper\Concerns\Relationer;
use Railken\EloquentMapper\Contracts\Map as MapContract;
use Railken\LaraEye\Filter;
use Railken\Lem\Attributes;

trait ConfigurableModel
{
    use Relationer;
    use HasRelationships;

    public static $__vars = null;
    public $__config = null;

    /**
     * Initialize the model by the configuration.
     *
     * @param string $config
     * @param bool   $reset
     */
    public function ini(string $config = null, bool $reset = false)
    {
        $this->__config = $config;

        /*
        if ($reset) {
            static::$__vars = null;
        }

        if (static::$__vars === null) {
            static::$__vars = new Bag();
        }*/

        static::$__vars = $this->retrieveVars();

        $vars = static::$__vars;

        $this->table = $vars->get('table');
        $this->fillable = $vars->get('fillable');
        $this->casts = $vars->get('casts');
        $this->hidden = $vars->get('hidden');
    }

    public function retrieveManager()
    {
        $class = Config::get($this->__config.'.manager');

        return new $class();
    }

    public function retrieveTableName()
    {
        return Config::get($this->__config.'.table');
    }

    /**
     * Initialize the model by the configuration.
     *
     * @param string $config
     *
     * @return Bag
     */
    public function retrieveVars(): Bag
    {
        $vars = new Bag();

        $vars->set('table', $this->retrieveTableName());

        $attributes = collect($this->retrieveManager()->getAttributes());

        $vars->set('fillable', $this->retrieveAttributeFillable($attributes));
        $vars->set('casts', $this->retrieveAttributeCasts($attributes));
        $vars->set('hidden', $this->retrieveAttributeHidden($attributes));

        return $vars;
    }

    /**
     * Initialize fillable by attributes.
     *
     * @param Collection $attributes
     *
     * @return array
     */
    public function retrieveAttributeFillable(Collection $attributes): array
    {
        return $attributes->filter(function ($attribute) {
            return $attribute->getFillable();
        })->map(function ($attribute) {
            return $attribute->getName();
        })->toArray();
    }

    /**
     * Initialize hidden by attributes.
     *
     * @param Collection $attributes
     *
     * @return array
     */
    public function retrieveAttributeHidden(Collection $attributes): array
    {
        return $attributes->filter(function ($attribute) {
            return $attribute->getHidden();
        })->map(function ($attribute) {
            return $attribute->getName();
        })->toArray();
    }

    /**
     * Initialize dates by attributes.
     *
     * @param Collection $attributes
     *
     * @return array
     */
    public function retrieveAttributeCasts(Collection $attributes): array
    {
        return $attributes->mapWithKeys(function ($attribute) {
            return [$attribute->getName() => $attribute];
        })->map(function ($attribute) {
            if ($attribute instanceof Attributes\ObjectAttribute) {
                return 'object';
            }

            if ($attribute instanceof Attributes\ArrayAttribute) {
                return 'array';
            }

            if ($attribute instanceof Attributes\BooleanAttribute) {
                return 'boolean';
            }

            if ($attribute instanceof Attributes\DateAttribute) {
                return 'date:'.$attribute->getFormat();
            }

            if ($attribute instanceof Attributes\DateTimeAttribute) {
                return 'datetime:'.$attribute->getFormat();
            }

            if ($attribute instanceof Attributes\NumberAttribute) {
                return 'float';
            }

            if ($attribute instanceof Attributes\TextAttribute) {
                return 'string';
            }

            return null;
        })->filter(function ($item) {
            return $item !== null;
        })->toArray();
    }

    public function getMorphName()
    {
        return app(MapContract::class)->modelToKey($this);
    }

    /**
     * Retrieve the actual class name for a given morph class.
     *
     * @param string $class
     *
     * @return string
     */
    public static function getActualClassNameForMorph($class)
    {
        return app(MapContract::class)->keyToModel($class);
    }

    public function filter(string $string)
    {
        $filter = new Filter($this->getTable(), ['*']);

        $builder = $this->newQuery();

        $filter->build($builder, $string);

        return $builder;
    }

    /**
     * @inherit
     */
    protected function newMorphTo(Builder $query, Model $parent, $foreignKey, $ownerKey, $type, $relation)
    {
        return new MorphTo(...func_get_args());
    }
}
