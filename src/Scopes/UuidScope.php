<?php
/**
 * Uuid Scope
 *
 * This conveniently adds local scopes to handle UUIDs to the Eloquent Query Builder. These scopes are only
 * valid for the Builder instance itself, and doesn't interfere with other builders of other models. You
 * can register this Scope all by yourself, but it's better to use the UsesUuid trait in your models.
 *
 * MIT License
 *
 * Copyright (c) Italo Israel Baeza Cabrera
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * Laravel is a Trademark of Taylor Otwell. Copyright © 2011-2020 Laravel LLC.
 *
 * @link https://github.com/DarkGhostHunter/Laratraits
 */

namespace DarkGhostHunter\Laratraits\Scopes;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UuidScope implements Scope
{
    use MacrosEloquent;

    /**
     * @inheritDoc
     */
    public function apply(Builder $builder, Model $model)
    {
        // Nothing to add, really.
    }

    /**
     * Return a Closure that find a model by its UUID.
     *
     * @return \Closure
     */
    protected function macroFindUuid()
    {
        return function(Builder $builder, $uuid, $columns = ['*']) {
            if (is_array($uuid) || $uuid instanceof Arrayable) {
                return $builder->findManyUuid($uuid, $columns);
            }

            return $builder->whereUuid($uuid)->first($columns);
        };
    }

    /**
     * Return a Closure that find multiple models by their UUID.
     *
     * @return \Closure
     */
    protected function macroFindManyUuid()
    {
        return function(Builder $builder, $uuids, $columns = ['*']) {
            $uuids = $uuids instanceof Arrayable ? $uuids->toArray() : $uuids;

            if (empty($uuids)) {
                return $builder->getModel()->newCollection();
            }

            return $builder->whereUuid($uuids)->get($columns);
        };
    }

    /**
     * Return a Closure that find a model by its UUID or throw an exception.
     *
     * @return \Closure
     */
    protected function macroFindUuidOrFail()
    {
        return function(Builder $builder, $uuid, $columns = ['*']) {
            $result = $builder->findUuid($uuid, $columns);

            if (is_array($uuid)) {
                if (count($result) === count(array_unique($uuid))) {
                    return $result;
                }
            } elseif ($result !== null) {
                return $result;
            }

            throw (new ModelNotFoundException)->setModel(
                get_class($builder->getModel()), $uuid
            );
        };
    }

    /**
     * Return a Closure that find a model by its UUID or return fresh model instance.
     *
     * @return \Closure
     */
    protected function macroFindUuidOrNew()
    {
        return function(Builder $builder, $uuid, $columns = ['*']) {
            if (($model = $builder->findUuid($uuid, $columns)) !== null) {
                return $model;
            }

            return $builder->newModelInstance();
        };
    }

    /**
     * Return a Closure that adds a where clause on the UUID column to the query.
     *
     * @return \Closure
     */
    protected function macroWhereUuid()
    {
        return function(Builder $builder, $uuid) {
            if (is_array($uuid) || $uuid instanceof Arrayable) {
                $builder->getQuery()->whereIn(
                    $builder->getModel()->getQualifiedUuidColumn(), $uuid
                );

                return $builder;
            }

            return $builder->where($builder->getModel()->getQualifiedUuidColumn(), '=', $uuid);
        };
    }

    /**
     * Return a Closure that add a where clause on the primary key to the query.
     *
     * @return \Closure
     */
    protected function macroWhereUuidNot()
    {
        return function (Builder $builder, $uuid) {
            if (is_array($uuid) || $uuid instanceof Arrayable) {
                $builder->getQuery()->whereNotIn(
                    $builder->getModel()->getQualifiedUuidColumn(), $uuid
                );

                return $builder;
            }

            return $builder->where($builder->getModel()->getQualifiedUuidColumn(), '!=', $uuid);
        };
    }

}
