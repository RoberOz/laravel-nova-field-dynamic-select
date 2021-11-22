<?php

namespace Hubertnnn\LaravelNova\Fields\DynamicSelect\Http\Controllers;

use Hubertnnn\LaravelNova\Fields\DynamicSelect\DynamicSelect;
use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;

class OptionsController extends Controller
{
    public function index(NovaRequest $request)
    {
        $attribute = $request->input('attribute');
        $dependValues = $request->input('depends');
        $search = $request->input('search');

        if ($action = $request->get('action')) {
            $resource = new $action();
            if ($resource) {
                $resource->withMeta([
                    'resourceId' => $request->get('resourceId'),
                ]);
            }

            $fields = $resource->fields($request);
        } else {
            $resource = $request->newResource();

            // Nova tabs compatibility:
            // https://github.com/eminiarts/nova-tabs
            if (method_exists($resource, 'parentUpdateFields')) {
                $fields = $resource->parentUpdateFields($request);
                $field = $fields->findFieldByAttribute($attribute);
            } else {
                $fields = $resource->updateFields($request);
                $field = $fields->findFieldByAttribute($attribute);

                if (!$field) {
                    $fields = $resource->fields($request);
                }
            }
        }


        if (!isset($field)) {
            foreach ($fields as $updateField) {
                // Flexible content compatibility:
                // https://github.com/whitecube/nova-flexible-content
                if ($updateField->component == 'nova-flexible-content') {
                    foreach ($updateField->meta['layouts'] as $layout) {
                        foreach ($layout->fields() as $layoutField) {
                            if ($layoutField->attribute === $attribute) {
                                $field = $layoutField;
                            }
                        }
                    }

                // Dependency container compatibility:
                // https://github.com/epartment/nova-dependency-container
                } elseif ($updateField->component == 'nova-dependency-container') {
                    foreach ($updateField->meta['fields'] as $layoutField) {
                        if ($layoutField->attribute === $attribute) {
                            $field = $layoutField;
                        }
                    }

                // Conditional container compatibility:
                // https://github.com/dcasia/conditional-container
                } elseif ($updateField->component === 'conditional-container') {
                    foreach ($updateField->fields as $layouts) {
                        if ($layouts->component === 'nova-flexible-content') {
                            foreach ($layouts->meta['layouts'] as $layout) {
                                foreach ($layout->fields() as $layoutField) {
                                    if ($layoutField->attribute === $attribute) {
                                        $field = $layoutField;
                                    }
                                }
                            }

                            // if in only conditional container
                        }  elseif ($layouts->attribute === $attribute) {
                            $field = $layouts; // field
                        }
                    }
                } else if ($updateField->attribute === $attribute) {
                    $field = $updateField;
                }
            }
        }

        /** @var DynamicSelect $field */
        $options = $request->boolean('searchable') ? $field->getAsyncSearchResult($search) : $field->getOptions($dependValues);

        return [
            'options' => $options,
        ];
    }
}
