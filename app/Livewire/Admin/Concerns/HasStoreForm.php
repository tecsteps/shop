<?php

namespace App\Livewire\Admin\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Shared authorization and store resolution for admin form components.
 *
 * Subclasses must define a `modelProperty()` method returning the name of
 * the public property that holds the Eloquent model (e.g. "product").
 */
trait HasStoreForm
{
    /**
     * Return the name of the public property holding the model instance.
     */
    abstract protected function modelProperty(): string;

    /**
     * Return the fully-qualified model class string.
     *
     * @return class-string<Model>
     */
    abstract protected function modelClass(): string;

    /**
     * Authorize the current save action and return the resolved store.
     *
     * @param  array<string, mixed>  $rules
     */
    protected function authorizeAndValidate(array $rules): \App\Models\Store
    {
        $property = $this->modelProperty();
        $model = $this->{$property};

        if ($model) {
            $this->authorize('update', $model);
        } else {
            $this->authorize('create', $this->modelClass());
        }

        $this->validate($rules);

        return app('current_store');
    }

    /**
     * Persist the model (create or update) and flash a success message.
     *
     * @param  array<string, mixed>  $data
     */
    protected function persistModel(array $data, string $redirectRoute): Model
    {
        $property = $this->modelProperty();
        $model = $this->{$property};
        $modelClass = $this->modelClass();
        $label = class_basename($modelClass);

        if ($model) {
            $model->update($data);
        } else {
            $model = $modelClass::query()->create($data);
        }

        session()->flash('success', $this->{$property} ? "{$label} updated." : "{$label} created.");
        $this->redirect(route($redirectRoute));

        return $model;
    }
}
